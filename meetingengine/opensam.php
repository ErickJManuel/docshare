<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common.php");
require_once("dbobjects/vbrand.php");
require_once("opensam_sso.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vlicensekey.php");


$g_StorageServerUrl = $g_StorageUserName = $g_StorageSessionId =  $g_StoragePassword = null;
$g_StorageDomainToConfirm = $g_WebDAVUrlParameters = $g_HTTPStatus =  null;

if (GetArg('StorageServerUrl', $arg))
	$g_StorageServerUrl=$arg;
	
if (GetArg('StorageUserName', $arg))
	$g_StorageUserName=$arg;
	
if (GetArg('StorageUserEmailAddress', $arg))
	$g_StorageUserEmailAddress=$arg;

if (GetArg('StorageSessionId', $arg))
	$g_StorageSessionId=$arg;
else
	$g_StorageSessionId='null';	// g_StorageSessionId can't be empty so we need to assign some value to it
	
if (GetArg('StorageSessionTerm', $arg))
	$g_StorageSessionTerm=$arg;

if (GetArg('StoragePassword', $arg))
	$g_StoragePassword=$arg;
	
if (GetArg('StorageOrg', $arg))
	$g_StorageOrg=$arg;

if (GetArg('StorageProvisionSkel', $arg))
	$g_StorageProvisionSkel=$arg;

if (GetArg('PSRedirectUrl', $arg))
	$g_PSRedirectUrl=$arg;

// brand parameter is added automatically when accessing a branded Persony web conferencing site.
if (!isset($_GET['brand']) || $_GET['brand']=='') {
	ErrorExit("You are missing an input paramter-probably because you are accessing the wrong site.");
}

// check if StorageServerUrl is authorized
VObject::Find(TB_BRAND, "name", $_GET['brand'], $brandInfo);
if (!isset($brandInfo['sso_host']))
	ErrorExit("The site you are accessing cannot be found in our records.");

$ssoHost=$brandInfo['sso_host'];

if ($ssoHost=='') {
	ErrorExit("No SSO Host is allowed for this site. Set 'SSO Host Name' under the Administration/API page.");
}	

// the url host name needs to match one of the authorized host names.
if ($ssoHost!='') {

	$urlItems=@parse_url($g_StorageServerUrl);
	if (!isset($urlItems['host']))
		ErrorExit("Invalid StorageServerUrl '$g_StorageServerUrl'.");
	
	// go through every authorized host names to see if there is a match.	
	$urlHost=$urlItems['host'];
	$ssoHostList=explode(',', $ssoHost);
	$hostFound=false;
	foreach ($ssoHostList as $ahost) {
		$ahost=trim($ahost);
		if (strpos($urlHost, $ahost)!==false) {
			// a match found
			$hostFound=true;
			break;
		}
	}
	if (!$hostFound) {
		ErrorExit("StorageServerUrl '$g_StorageServerUrl' is not an authorized SSO Host.");
	}
}

if (isset($g_StorageSessionTerm) && $g_StorageSessionTerm!='')
    SetSessionExpiration((integer)$g_StorageSessionTerm*60);

$redirectPage='';

if (isset($brandInfo['site_url'])) {
	$redirectPage=$brandInfo['site_url']."?page=".PG_MEETINGS;
	//$redirectPage=SITE_URL."index.php?page=".PG_MEETINGS."&brand=".$$brandInfo['name'];
	if (SID!='')
		$redirectPage.="&".SID;	
}
		
if (isset($g_PSRedirectUrl) && $g_PSRedirectUrl!='')
	$redirectPage=$g_PSRedirectUrl;
/*
$sessId=GetSessionValue("StorageSessionId");
if ($g_StorageSessionId!='' && $sessId==$g_StorageSessionId) {
	// user already logged in
	header("Location: $redirectPage");
	exit();	
}
*/

// Attempt an SSO log in using the opensam SSO helper. The helper uses the SSO CGI parameters.
// The SSO CGI parameters might be present in a variety of requests, so we check for them every time.
$sso_ret = opensam_sso_authenticate( $g_StorageServerUrl, $g_StorageUserName, $g_StorageSessionId, $g_StoragePassword, 
  $g_StorageDomainToConfirm, $g_WebDAVUrlParameters, $g_HTTPStatus );

if( $sso_ret === 0 ) {
	// NO SSO ATTEMPT MADE. No CGI parameters found.
	ErrorExit("No CGI parameters found");
	
} else if ($sso_ret===true) {
require_once("dbobjects/vuser.php");
//require_once("includes/brand.php");

	$login=$g_StorageUserName;
	
	// search to see if the user exists
//	$query="login='$login' AND brand_id='".$gBrandInfo['id']."'";
	$query="LOWER(login)='".addslashes(strtolower($login))."' AND brand_id='".$brandInfo['id']."'";
	
	$userInfo=array();
	$errMsg=VObject::Select(TB_USER, $query, $userInfo);
	
	if ($errMsg!='')
		ErrorExit($errMsg);
		
	// new user; create an account
	if (!isset($userInfo['id'])) {
		
		$trialLicId=1; // assume the first license is the trial license
//		$trialGroupId=$gBrandInfo['trial_group_id'];
		$trialGroupId=$brandInfo['trial_group_id'];
//		$brandName=$gBrandInfo['name'];
		$brandName=$brandInfo['name'];
		
		$groupId=$trialGroupId;
		if ($g_StorageOrg!='')
			$groupId=$g_StorageOrg;
			
		$licenseId=$trialLicId;
		if ($g_StorageProvisionSkel!='') {
			require_once("dbobjects/vlicense.php");
			$query="code='".addslashes($g_StorageProvisionSkel)."' OR id='".addslashes($g_StorageProvisionSkel)."'";
			VObject::Select(TB_LICENSE, $query, $licInfo);
			if (isset($licInfo['id']))
				$licenseId=$licInfo['id'];
			else
				ErrorExit("License code '".$g_StorageProvisionSkel."' cannot be found.");
			
			//$licenseId=$g_StorageProvisionSkel;
		}
		
		$signupUrl=SITE_URL.VM_API."?cmd=ADD_USER";
		$signupUrl.="&brand=".$brandName;
		$signupUrl.="&license_id=".$licenseId;
		$signupUrl.="&group_id=".$groupId;
		$signupUrl.="&add_meeting=1";
		$signupUrl.="&login=".rawurlencode($login);
		$signupUrl.="&full_name=".rawurlencode($g_StorageUserName);
		$signupUrl.="&password=".rawurlencode($g_StoragePassword);
		
		if ($g_StorageUserEmailAddress!='')
			$signupUrl.="&email=".rawurlencode($g_StorageUserEmailAddress);
				
//		echo $signupUrl;

		// create the user
//		$response=@file_get_contents($signupUrl);
		$response=HTTP_Request($signupUrl);
		if (strpos($response, "<error")!==false) {
			ErrorExit("Couldn't create user ".$response);
		}
		
		// get the user info
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);
		
		if ($errMsg!='')
			ErrorExit($errMsg);
		
		if (!isset($userInfo['id']))
			ErrorExit("Couldn't create user");
/*		
	} else if ($g_StoragePassword!=$userInfo['password']) {

		$newInfo=array();
		$newInfo['password']=$g_StoragePassword;
		
		$user=new VUser($userInfo['id']);
		if ($user->Update($newInfo)!='ERR_NONE') {
			ErrorExit($user->GetErrorMsg());
		}
*/
	} else {
		if ($userInfo['active']!='Y') {
			ErrorExit("The user's account is not active.");					
		}

		if ((defined('ENABLE_LICENSE_KEY') && constant('ENABLE_LICENSE_KEY')=='1')) {
			
			if ($loginMsg=='') {
				if ($userInfo['licensekey_id']!='0') {
					if (VUser::VerifyLicense($userInfo, $lcMsg)!=true) {
						ErrorExit($lcMsg);
					}
				} else {
					$providerId=$brandInfo['provider_id'];
					$provider=new VProvider($providerId);
					$provider->GetValue('licensekey_id', $plicId);
					if ($plicId!='0') {
						$licKey=new VLicenseKey($plicId);
						$licKey->GetValue('license_text', $licenseText);
						if (!VLicenseKey::VerifyLicenseText($licenseText, $keyInfo, $errMsg)) {
							ErrorExit($errMsg);	
/*							
						} else if ($keyInfo['expiry_date']>"1961-01-01") {
							$today=date("Y-m-d");
							if ($keyInfo['expiry_date']<$today) {
								ErrorExit("The site's license key has expired on ".$keyInfo['expiry_date']);		
							} */
						}
						
					}
				}
			}	
		}

	}
	
	SetSessionValue("StorageSessionId", $g_StorageSessionId);
	SetSessionValue('brand_name', $brandName);
	
	include_once("includes/signin_user.php");
	include_once("includes/house_keeping.php");
	
	// set login session variables
/*
	$memberName=VUSer::GetFullName($userInfo);
	SetSessionValue("member_name", $memberName);
	SetSessionValue("member_id", $userInfo['id']);					
	SetSessionValue("member_perm", $userInfo['permission']);					
	SetSessionValue("member_brand", $userInfo['brand_id']);	
	if ($userInfo['time_zone']!='')
		SetSessionValue("time_zone", $userInfo['time_zone']);	
*/
//	SetSessionValue("embed", 1);
//	SetSessionValue("hide_signin", 1);
	
	header("Location: $redirectPage");

} else {
	
	if ($sso_ret!='') {
		$msg=$sso_ret;
	} else {
	
		$msg="Couldn't autenticate<br>\n";
		$msg.="Call opensam_sso_authenticate with:<br>
		StorageServerUrl=$g_StorageServerUrl<br>
		StorageUserName=$g_StorageUserName<br>
		StorageSessionId=$g_StorageSessionId<br>
		StoragePassword=$g_StoragePassword<br>
		<br>
		StorageDomainToConfirm=$g_StorageDomainToConfirm<br>
		WebDAVUrlParameters=$g_WebDAVUrlParameters<br>
		HTTPStatus=$g_HTTPStatus<br>";
	}
	ErrorExit($msg);

}

?>