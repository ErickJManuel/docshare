<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once('api_includes/common.php');
require_once("dbobjects/vuser.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vlicensekey.php");
require_once("dbobjects/vlicense.php");

// The license can be installed in one of two ways
// 1. Install from the APS package: The request will be sent from an APS site and the site login/passcode should be sent with the request
// We will use site_login and site_code to authenticate the request and the license will be installed for the site only
// 2. The user logs in to the site as an admin and upload the license file.
// We will use the login session data to authenticate and the user can install the license for the site or the user's account
if (GetArg('site_login', $siteLogin) && GetArg('brand', $brandName)) {
	$errMsg=VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
	if ($errMsg!='')
		return API_EXIT(API_ERR, $errMsg);
	
	if (!isset($brandInfo['id']))
		return API_EXIT(API_ERR, "The brand record is not found");

	GetArg('site_url', $siteUrl);
	GetArg('site_code', $siteCode);
	
	$found=false;
	$query="brand_id= '".$brandInfo['id']."' AND url= '".$siteUrl."'";
	VObject::SelectAll(TB_WEBSERVER, $query, $result);
	while ($webServerInfo = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($webServerInfo['login']==$siteLogin || md5($webServerInfo['password'])==$siteCode) {
			$found=true;	
		}
	}
	if (!$found)
		ExitOnErr("The site login or password does not match our records.");
		
	$memberPerm='ADMIN';
	$brand=new VBrand($brandInfo['id']);
	
} else {
	$memberId=GetSessionValue('member_id');
	$memberPerm=GetSessionValue('member_perm');
	$memberBrand=GetSessionValue('member_brand');
	
	if ($memberId=='')
		return API_EXIT(API_ERR, "Not signed in");
		
	$brandId=$memberBrand;
	$brand=new VBrand($brandId);
	if ($brand->Get($brandInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $brand->GetErrorMsg());

}

// If 'user' or 'user_id' is set, install the license for the user account
// Otherwise, install for the site
if (GetArg('user', $arg) && $arg!='') {
	VObject::Find(TB_USER, 'access_id', $arg, $userInfo);
	if (isset($userInfo['id']))
		$user=new VUser($userInfo['id']);	
	else
		return API_EXIT(API_ERR, "User not found");

} else if (GetArg('user_id', $arg) && $arg!='') {
	$user=new VUser($arg);
	if ($user->Get($userInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $user->GetErrorMsg());
	
	elseif (!isset($userInfo['id']))
		return API_EXIT(API_ERR, "User not found");
}

/* The license key fields are 
* "requester", "purchase_id", "running_no", "product_id", "license_code", "reg_name", "test_mode", "subscription_date", "start_date", "expiry_date", "domain"
* plus "license_key", which is a hash of the above fields.
* "license_code" defines the user account type (e.g. PV25) or site's provider license (e.g. S;TPV1:10,P10:1), depending on if the key is applied to a user or a site.
* The key can be uploaded via an xml file or from url parameters
*/
$licenseInfo=array();
$licenseText='';
if ($cmd=='INSTALL_LICENSE') {
	$file_key='license_file';
	// check if the license file is uploaded with a multi-part form upload
	if (isset($_FILES[$file_key]['tmp_name'])) {
		if ($_FILES[$file_key]['tmp_name']=='')
			return API_EXIT(API_ERR, "License key file is missing");
		
		$keyFile=$_FILES[$file_key]['tmp_name'];
		$srcFileName=$_FILES[$file_key]['name'];
		$licenseText=file_get_contents($keyFile);
		$licXml = simplexml_load_string($licenseText);
		if ($licXml==false)
			return API_EXIT(API_ERR, "Couldn't read the license file");
		
		foreach ($licXml->children() as $child) {
			$licenseInfo[$child->getName()]=(string)$child;
		}
		
	} else if (GetArg('license_fields', $arg)) {
		
		// get the license key info from the input parameters
		$keyFields=explode(",", $arg);
		$licenseText="<license xmlns=\"http://schemas.persony.com/wc2/license/1.0\">\n";
		foreach ($keyFields as $aField) {
			if (GetArg($aField, $arg)) {
				$licenseInfo[$aField]=$arg;
				$licenseText.="<$aField>".htmlspecialchars($arg)."</$aField>\n";
			} else
				return API_EXIT(API_ERR, "A required input field '$aField' is missing.");		
		}
		if (!isset($licenseInfo['license_key'])) {
			GetArg('license_key', $arg);
			$licenseInfo['license_key']=$arg;
			$licenseText.="<license_key>".htmlspecialchars($arg)."</license_key>\n";
		}
		$licenseText.="</license>";
	} else {
		return API_EXIT(API_ERR, "License key file is not provided");		
	}
	
	if (!VLicenseKey::VerifyLicenseText($licenseText, $keyInfo, $errMsg))
		return API_EXIT(API_ERR, $errMsg);		

	
}

// install the license for a user account
if (isset($userInfo['id'])) {
	
	if ($memberPerm!='ADMIN' || $memberBrand!=$userInfo['brand_id']) 
	{
		// if the member is not an admin and wants to set another user
		if ($memberId!=$userInfo['id'])
			return API_EXIT(API_ERR, "Not authorized");
		
		if ($userInfo['login']==VUSER_GUEST)
			return API_EXIT(API_ERR, "You cannot set the properties of a default user.");
		
	}
	if ($cmd=='INSTALL_LICENSE') {
		
		$keyInfo=array();
		VObject::Find(TB_LICENSEKEY, "license_key", $licenseInfo['license_key'], $keyInfo);
		
		if (isset($keyInfo['id']) && $keyInfo['user_id']!=$userInfo['id']) {		
			// check if the key's user still exists:
			VObject::Count(TB_USER, "id='".$keyInfo['user_id']."'", $numRows);
			// the key has been assigned to someone else, exit
			if ($numRows!=0)				
				return API_EXIT(API_ERR, "The license key has been previously activated by someone else.");
			
		}
						
		// find the license type
		$licenseCode=$licenseInfo['license_code'];
		VObject::Find(TB_LICENSE, "code", $licenseInfo['license_code'], $licInfo);
		if (!isset($licInfo['id']))
			return API_EXIT(API_ERR, "The license code is invalid.");
			
		$lcInfo=array();
		$lcInfo['user_id']=$userInfo['id'];
		$lcInfo['license_key']=$licenseInfo['license_key'];
		$lcInfo['license_text']=$licenseText;
		
		if (!isset($keyInfo['id'])) {
			$licKey=new VLicenseKey();
			if ($licKey->Insert($lcInfo)!=ERR_NONE)
				return API_EXIT(API_ERR, $licKey->GetErrorMsg());
			$licKey->GetValue('id', $keyId);
		} else {
			$licKey=new VLicenseKey($keyInfo['id']);
			if ($licKey->Update($lcInfo)!=ERR_NONE)
				return API_EXIT(API_ERR, $licKey->GetErrorMsg());
			
			$keyId=$keyInfo['id'];
		}		
		
		// provision the user account with the license
		$newInfo=array();
		$newInfo['license_id']=$licInfo['id'];
		$newInfo['licensekey_id']=$keyId;
		if ($user->Update($newInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $user->GetErrorMsg());

	} else if ($cmd=='REMOVE_LICENSE') {
/*
		$keyId=$userInfo['licensekey_id'];
		if ($keyId!=0) {
			$licKey=new VLicenseKey($keyId);
			$licKey->Drop();	
		}
*/
		$updateInfo=array();			
//		$updateInfo['active']='N';
		$updateInfo['license_id']='1';	// trial license
		$updateInfo['licensekey_id']='0';
		$user->Update($updateInfo);
		
	}
} else {
	require_once("dbobjects/vprovider.php");
	// install the license for a brand
	if ($memberPerm!='ADMIN') 
		return API_EXIT(API_ERR, "Not authorized");
		
	$provider=new VProvider($brandInfo['provider_id']);
	if ($provider->Get($providerInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $provider->GetErrorMsg());
	
	if ($cmd=='INSTALL_LICENSE') {
		$keyInfo=array();
		VObject::Find(TB_LICENSEKEY, "license_key", $licenseInfo['license_key'], $keyInfo);

//		if (isset($keyInfo['id']) && $keyInfo['provider_id']!=$providerInfo['id']) {		
		if (isset($keyInfo['id']) && $keyInfo['provider_id']!='') {		
			// check if the key's provider still exists:
//			VObject::Count(TB_PROVIDER, "id='".$keyInfo['provider_id']."'", $numRows);
			// the key has been assigned to someone else, exit
//			if ($numRows!=0)
				return API_EXIT(API_ERR, "The license key has been previously activated.");		
		} 
		
		// Verify license_code is valid.
		$licenseCode=$licenseInfo['license_code'];
		$pre=substr($licenseCode, 0, 2);
		if ($pre!='S;' && $pre!='N;' && $pre!='P;' && $pre!='U;')
			return API_EXIT(API_ERR, "The license key is not the right type.");
		
		$lcInfo=array();
		$lcInfo['provider_id']=$providerInfo['id'];
		$lcInfo['license_key']=$licenseInfo['license_key'];
		$lcInfo['license_text']=$licenseText;						
			
		if (!isset($keyInfo['id'])) {
			$licKey=new VLicenseKey();
			if ($licKey->Insert($lcInfo)!=ERR_NONE)
				return API_EXIT(API_ERR, $licKey->GetErrorMsg());				
		} else {
			$licKey=new VLicenseKey($keyInfo['id']);
			if ($licKey->Update($lcInfo)!=ERR_NONE)
				return API_EXIT(API_ERR, $licKey->GetErrorMsg());
		}
		if ($licKey->Get($keyInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $licKey->GetErrorMsg());		
		
		// Need to append a hash to the license code that includes the provider login id
		// This ensures the license code is unique for the provider and not modifed or copied later in the database
		$licenseCode.=";".VLicense::EncryptLicense($licenseCode, $providerInfo['login']);
				
		$updateInfo=array();
		$updateInfo['license']=$licenseCode;
		$updateInfo['licensekey_id']=$keyInfo['id'];
		if ($provider->Update($updateInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $provider->GetErrorMsg());	
			
		if ($brandInfo['status']!='ACTIVE') {
			$updateInfo=array();
			$updateInfo['status']='ACTIVE';
			$brand->Update($updateInfo);
		}
		
		// need to update the group hosting profiles based on the license's 'product_id'
		// the default hosting profiles are set in the database group table.
		$query="brand_id= '65535' AND name LIKE '%".$licenseInfo['product_id']."%'";
		VObject::Select(TB_GROUP, $query, $defInfo);
		// a default profile is found for this product_id
		if (isset($defInfo['id'])) {
			// update the default group of the brand
			$query="brand_id= '".$brandInfo['id']."' AND name='default'";
			VObject::Select(TB_GROUP, $query, $groupInfo);
			if (isset($groupInfo['id'])) {
				$updateInfo=array();
				foreach ($defInfo as $key => $val) {
					if ($val!='0') {
						$updateInfo[$key]=$val;
					}
				}
				
				$group=new VGroup($groupInfo['id']);
				$group->Update($updateInfo);
			}
			
		}		

	} else if ($cmd=='REMOVE_LICENSE') {
/*	
		$keyId=$providerInfo['licensekey_id'];
		if ($keyId!=0) {
			$licKey=new VLicenseKey($keyId);
			$licKey->Drop();	
		}
	
		// reset the license code to a trial site
		// assuming this is an SMB or APS site
		//$licenseCode='S;TPV1:10,P10:0,PV10:0,P25:0,PV25:0,P100:0,PV100:0';
		$licenseCode='S;TPV1:10,P25:0,PV25:0,P100:0,PV100:0';
		$licenseCode.=";".VLicense::EncryptLicense($licenseCode, $providerInfo['login']);
		$updateInfo=array();
		$updateInfo['license']=$licenseCode;
		$updateInfo['license_id']=0;
		$provider->Update($updateInfo);

		// reset everyone to the trial license
		$query="brand_id= '".$brandInfo['id']."'";
		VObject::SelectAll(TB_USER, $query, $result);
		while ($rowInfo = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$auser=new VUser($rowInfo['id']);
			$updateInfo=array();
			$updateInfo['license_id']='1';	// assume the trial license id is 1
			$auser->Update($updateInfo);
		}
*/	
		// deactivate the site
		$updateInfo=array();
		$updateInfo['status']='INACTIVE';
		$brand->Update($updateInfo);
		
	}
}



?>