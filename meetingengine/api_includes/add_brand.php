<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * Called by Persony APS installer to create a brand and a provider account
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

// This script is used to create a new brand (or update an existing one) with or without a provider account
// If the provider account is not given, it will be created
// This is called from either vinstall.php (launched via Persony Provider Console) or a Plesk APS installer
// If it is called from Plesk APS, a provider account will be created

require_once("dbobjects/vbrand.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vviewer.php");
require_once("dbobjects/vmailtemplate.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vteleserver.php");

// check if the brand url already exists
$brandInfo=array();
GetArg('site_url', $siteUrl);

function ExitOnErr($msg) {
	include_once("includes/log_error.php");
	LogError($msg);

	die("ERROR\n$msg");
}

$siteUrl=trim($siteUrl);
if ($siteUrl=='') {
	ExitOnErr("A required parameter 'site_url' is not provided.");
}

// make sure this is a valid url

$urlItems=@parse_url($siteUrl);
if (!isset($urlItems['scheme']) || 
	($urlItems['scheme']!='http' && $urlItems['scheme']!='https') ||
		!isset($urlItems['host'])) 
{
	ExitOnErr("Invalid site url '$siteUrl'");			
}

$required=array("admin_login", "admin_password");

GetArg('check_url', $checkUrl);
GetArg('admin_login', $adminLogin);
GetArg('admin_first_name', $adminFirstName);
GetArg('admin_last_name', $adminLastName);
GetArg('admin_email', $adminEmail);
GetArg('admin_password', $adminPassword);
GetArg('site_email_name', $siteEmailName);
GetArg('site_email_address', $siteEmailAddr);
GetArg('site_title', $siteTitle);
GetArg('locale', $localeName);
GetArg('brand', $brand);
GetArg('site_login', $siteLogin);
GetArg('site_code', $siteCode);
GetArg('provider_id', $providerId);
GetArg('provider_login', $providerLogin);
GetArg('provider_mpass', $providerPass);
GetArg('config', $config);	// request source:"APS", "SMB"

$len=strlen($siteUrl);
if ($len>0 && $siteUrl[$len-1]!='/')
	$siteUrl.='/';

// check if the remote host can be reached
// always check unless check_url is 0
/* turn off the checking because the site may not be set up yet
if ($checkUrl!='0') {
	$resp=@file_get_contents($siteUrl);
	if ($resp===false) {
		$thisHost=$_SERVER['SERVER_NAME'];
		ExitOnErr("Your site '$siteUrl' is not responding to HTTP connections from '$thisHost'. Make sure your site domain name is correct and it is accepting incoming HTTP connections.");
	}
}
*/
$adminInfo=array();
$providerInfo=array();
$viewerInfo=array();
$webServerInfo=array();
$groupInfo=array();	

if ($providerId!='') {
	$provider=new VProvider($providerId);
	$provider->Get($providerInfo);
	if (!isset($providerInfo['id']))
		ExitOnErr("Could not find the provider account.");
	if ($providerLogin!=$providerInfo['login'] || $providerPass!=md5($providerInfo['password']))
		ExitOnErr("The provider account login or password is incorrect.");
	
}

if ($cmd=='ADD_PROVIDER_BRAND') {
	foreach ($required as $req) {
		if (!GetArg($req, $arg) || $arg=='')
			ExitOnErr("A required parameter '$req' not provided.".$_SERVER['QUERY_STRING']);
		
	}
	if ( $adminFirstName=='' && $adminLastName=='') {
		$adminFirstName=$adminLogin;
	}

/*
	if (isset($brandInfo['id']))
		ExitOnErr("The site record you want to add already exists.");
*/	
	// If a provider account already exists, make sure the site limit is not exceeded			
	if (isset($providerInfo['max_sites']) && ($maxSites=(int)$providerInfo['max_sites'])>0) {
		$query="provider_id='$providerId' AND status='ACTIVE'";
		$errMsg=VObject::Count(TB_BRAND, $query, $numSites);
		if ($numSites>=$maxSites) {
			ExitOnErr("You have exceeded the number of sites that you can create with your provider account. Please deactivate a site first.");
		}		
	}
} else {
	// setting an existing brand	
	if ($brand=='' || $siteLogin=='' || $siteCode=='') {
		ExitOnErr("A required parameter is not provided.");
	}
	
	$brandQuery="name= '".$brand."'";
	if ($providerId!='')
		$brandQuery.=" AND provider_id='".$providerId."'";
	
	$errMsg=VObject::Select(TB_BRAND, $brandQuery, $brandInfo);
	if ($errMsg!='')
		ExitOnErr("A database error occurred while processing your request.");
	
	if (!isset($brandInfo['id']))
		ExitOnErr("The site record you want to reconfigure does not exist.");

}

if (isset($brandInfo['id'])) {
	// the brand already exists
	
	// find the admin user of the brand
	$admin=new VUser($brandInfo['admin_id']);
	$admin->Get($adminInfo);

/*	
	// find the admin user of the brand
	$query="LOWER(login)= '".addslashes(strtolower($adminLogin))."' AND brand_id = '".$brandInfo['id']."' AND permission='ADMIN'";
	VObject::Select(TB_USER, $query, $adminInfo);
	
	// only allow to reset an existing site if the admin password matches
	if (!isset($adminInfo['id']))
		ExitOnErr("The admin user cannot be found.");
*/		
	// find all existing records related to this site
	$query="id= '".$brandInfo['provider_id']."'";
	VObject::Select(TB_PROVIDER, $query, $providerInfo);
	
	$query="id= '".$brandInfo['viewer_id']."'";
	VObject::Select(TB_VIEWER, $query, $viewerInfo);
	
	$found=false;
	$query="brand_id= '".$brandInfo['id']."' AND url= '".$brandInfo['site_url']."'";
	VObject::SelectAll(TB_WEBSERVER, $query, $result);
	while ($webServerInfo = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($webServerInfo['login']==$siteLogin || md5($webServerInfo['password'])==$siteCode) {
			$found=true;	
		}
	}
	if (!$found)
		ExitOnErr("The site login or password does not match our records.");	
	
	$query="brand_id= '".$brandInfo['id']."'";
	VObject::Select(TB_GROUP, $query, $groupInfo);
	
	// initialize input values
	if ($cmd=='SET_PROVIDER_BRAND') {
		if ($adminFirstName=='' && isset($adminInfo['first_name']))
			$adminFirstName=$adminInfo['first_name'];
		if ($adminLastName=='' && isset($adminInfo['last_name']))
			$adminLastName=$adminInfo['last_name'];
		if ($adminEmail=='' && isset($adminInfo['email']))
			$adminEmail=$adminInfo['email'];
		if ($adminLogin=='' && isset($adminInfo['login']))
			$adminLogin=$adminInfo['login'];
		if ($adminLogin=='' && isset($adminInfo['login']))
			$adminLogin=$adminInfo['login'];
	}
	
GetArg('admin_login', $adminLogin);
GetArg('admin_first_name', $adminFirstName);
GetArg('admin_last_name', $adminLastName);
GetArg('admin_email', $adminEmail);
GetArg('admin_password', $adminPassword);
GetArg('site_email_name', $siteEmailName);
GetArg('site_email_address', $siteEmailAddr);
GetArg('site_title', $siteTitle);
GetArg('locale', $localeName);
GetArg('brand', $brand);
GetArg('site_login', $siteLogin);
GetArg('site_code', $siteCode);
	
}


// create a unique provider login id
if (!isset($providerInfo['id'])) {
//ExitOnErr("Shouldn't happen. provider=$providerId");
	for ($i=0; $i<10; $i++) {
		// 10 digits
		$providerLogin=mt_rand(1000000000, 9999999999);
		if (!VObject::InTable(TB_PROVIDER, 'login', $providerLogin))
			break;
		elseif ($i==9) {
			ExitOnErr("Couldn't find an available id to assign to the site.");
		}
	}

	// create a provider record
	$providerInfo['login']=$providerLogin;
	$providerInfo['account_id']=md5(microtime());
	// user site with unlimited trial licenses
//	$licArg='S;TPV1:100,P10:0,PV10:0,P25:0,PV25:0,P100:0,PV100:0';
	$licArg='S;TPV1:10,P10:0,PV10:0,P25:0,PV25:0,P100:0,PV100:0';
	$providerInfo['license']=$licArg.";\n".VLicense::EncryptLicense($licArg, $providerLogin);
	$providerInfo['create_time']='#NOW()';
	$providerInfo['password']=$adminPassword;
	$providerInfo['first_name']=$adminFirstName;
	$providerInfo['last_name']=$adminLastName;
	$providerInfo['company_name']=$siteTitle;
	$providerInfo['admin_email']=$adminEmail;

	$provider=new VProvider();
	if ($provider->Insert($providerInfo)!=ERR_NONE) {
		ExitOnErr($provider->GetErrorMsg());
	}

	$provider->GetValue('id', $providerId);
} else {

	if (!$provider) {
		$providerId=$providerInfo['id'];
		$providerLogin=$providerInfo['login'];

		$providerUpdateInfo=array();
		$providerUpdateInfo['first_name']=$adminFirstName;
		$providerUpdateInfo['last_name']=$adminLastName;
		$providerUpdateInfo['company_name']=$siteTitle;
		$providerUpdateInfo['admin_email']=$adminEmail;
		
		$provider=new VProvider($providerId);
		if ($provider->Update($providerUpdateInfo)!=ERR_NONE) {
			ExitOnErr($provider->GetErrorMsg());
		}
	}
}

// if a license key file is provided, install it
$file_key='license_file';
$licenseInfo=array();
$licenseText='';

if (isset($_FILES[$file_key]['tmp_name'])) {
	
	$keyFile=$_FILES[$file_key]['tmp_name'];
	$srcFileName=$_FILES[$file_key]['name'];
	$licenseText=file_get_contents($keyFile);
	$licXml = simplexml_load_string($licenseText);
	if ($licXml==false)
		ExitOnErr("Couldn't read the license file");
	
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
			ExitOnErr("A required input field '$aField' is missing.");		
	}
	if (!isset($licenseInfo['license_key'])) {
		GetArg('license_key', $arg);
		$licenseInfo['license_key']=$arg;
		$licenseText.="<license_key>".htmlspecialchars($arg)."</license_key>\n";
	}
	$licenseText.="</license>";
}
	
if ($licenseText!='' && isset($licenseInfo['license_key'])) {
	require_once("dbobjects/vlicensekey.php");
	
	if (!VLicenseKey::VerifyLicenseText($licenseText, $kInfo, $errMsg))
		ExitOnErr($errMsg);		
	
	$keyInfo=array();
	VObject::Find(TB_LICENSEKEY, "license_key", $licenseInfo['license_key'], $keyInfo);
	
	if (isset($keyInfo['id']) && $keyInfo['provider_id']!=$providerId) {		
		// check if the key's provider still exists:
		VObject::Count(TB_PROVIDER, "id='".$keyInfo['provider_id']."'", $numRows);
		// the key has been assigned to someone else, exit
		if ($numRows!=0)
			ExitOnErr("The license key has been previously activated by someone else.");
		
	} 
	
	$licenseCode=$licenseInfo['license_code'];
	$pre=substr($licenseCode, 0, 2);
	if ($pre!='S;' && $pre!='N;' && $pre!='P;' && $pre!='U;')
		ExitOnErr("The license key is not the right type.");
	
	$lcInfo=array();
	$lcInfo['provider_id']=$providerId;
	$lcInfo['license_key']=$licenseInfo['license_key'];
	$lcInfo['license_text']=$licenseText;						
	
	if (!isset($keyInfo['id'])) {
		$licKey=new VLicenseKey();
		if ($licKey->Insert($lcInfo)!=ERR_NONE)
			ExitOnErr($licKey->GetErrorMsg());				
	} else {
		$licKey=new VLicenseKey($keyInfo['id']);
		if ($licKey->Update($lcInfo)!=ERR_NONE)
			ExitOnErr($licKey->GetErrorMsg());
	}
	if ($licKey->Get($keyInfo)!=ERR_NONE)
		ExitOnErr($licKey->GetErrorMsg());		
	
	$licenseCode.=";".VLicense::EncryptLicense($licenseCode, $providerInfo['login']);
	
	$updateInfo=array();
	$updateInfo['license']=$licenseCode;
	$updateInfo['licensekey_id']=$keyInfo['id'];
	if ($provider->Update($updateInfo)!=ERR_NONE)
		ExitOnErr($provider->GetErrorMsg());	
}


//$brandName=$providerLogin;		// use the provider login as the brand name since there is only one brand per provider

$brandInfo['from_email']=$siteEmailAddr;
$brandInfo['site_url']=$siteUrl;
$brandInfo['from_name']=$siteEmailName;
$brandInfo['product_name']=$siteTitle;
$brandInfo['locale']=$localeName;

if (!isset($brandInfo['id'])) {

	// create a unique brand name
	for ($i=0; $i<10; $i++) {
		// 7 digits
		$brandName=mt_rand(1000000, 9999999);
		if (!VObject::InTable(TB_BRAND, 'name', $brandName))
			break;
		elseif ($i==9) {
			ExitOnErr ("Couldn't find an available brand id.");
		}
	}
	$brandInfo['name']=$brandName;
	$brandInfo['provider_id']=$providerId;

	// find a trial license
	$query="code='TPV1' AND enabled='Y'";
	VObject::Select(TB_LICENSE, $query, $tlicInfo);
	
	if (isset($tlicInfo['id'])) {
		$trialId=$tlicInfo['id'];	
		$brandInfo['trial_license_id']=$trialId;
	}
		
	$brandInfo['theme']='default';	
	$brandInfo['footer1_label']="About Us";	
	$brandInfo['footer2_label']="Contact Us";	
	$brandInfo['footer3_label']="Terms of Service";	
	$brandInfo['footer4_label']="Privacy Policy";	
	$powerUrl="images/poweredby_persony.png";
	$brandInfo['footnote']="<a target=_blank href='http://www.persony.com'><img src='$powerUrl'></a>";	
	$brandInfo['logo_id']="2";	// this should match the wc2_image database record id for "default_banner.jpg"
	// if this is the SMB package, allow the site admin to install a license key for the site
	if ($config=='SMB') {
		$brandInfo['enable_licensekey']='SITE';
	}
	if ($config=='SMB' || $config=='APS') {
		$brandInfo['trial_signup']='N'; // disable free trials by default
		$brandInfo['offerings']='TPV1,P25,PV25,P100,PV100';	
	} else {
		$brandInfo['offerings']='TPV1,P10,PV10,P25,PV25,P50,PV50,P100,PV100,P250,PV250';
	}
	
} else {
	$trialId=$brandInfo['trial_license_id'];
	$brandName=$brandInfo['name'];
}

if (!isset($viewerInfo['id'])) {
	$viewer=new VViewer();
	$viewerInfo['logo_id']=1;
	$viewerInfo['back_id']=1;
	if ($viewer->Insert($viewerInfo)!=ERR_NONE) {
		ExitOnErr($viewer->GetErrorMsg());
	}

	$viewer->GetValue("id", $viewerId);
	$brandInfo['viewer_id']=$viewerId;
}

if (!isset($brandInfo['id'])) {
	$brand=new VBrand();

	$brandInfo['create_time']='#NOW()';
	if ($brand->Insert($brandInfo)!=ERR_NONE) {
		ExitOnErr($brand->GetErrorMsg());
	}
		
	$brand->GetValue("id", $brandId);
} else {
	$brand=new VBrand($brandInfo['id']);
	if ($brand->Update($brandInfo)!=ERR_NONE) {
		ExitOnErr($brand->GetErrorMsg());
	}
}

// create a hosting server profile for the site
if (!isset($webServerInfo['id'])) {
	$webServer=new VWebServer();
	$webServerInfo['brand_id']=$brandId;
	$webServerInfo['name']=$urlItems['host'];
	$webServerInfo['login']='host';
	$webServerInfo['password']=(string)mt_rand(100000, 999999);
	$webServerInfo['url']=$siteUrl;
//	$version=@file_get_contents($siteUrl."vversion.php");
//	$webServerInfo['installed_version']=$version;

	if ($webServer->Insert($webServerInfo)!=ERR_NONE) {
		ExitOnErr($webServer->GetErrorMsg());
	}
	$webServer->GetValue("id", $webserverId);
} else {
	$webserverId=$webServerInfo['id'];
	if ($siteUrl!=$webServerInfo['url']) {
		$webServer=new VWebServer($webserverId);
		$updateInfo=array();
		$updateInfo['url']=$siteUrl;
		$webServer->Update($updateInfo);
	}
}

$siteLogin=$webServerInfo['login'];
$sitePassword=md5($webServerInfo['password']);
	
// create a default group
if (!isset($groupInfo['id'])) {

	$group=new VGroup();
	$groupInfo['brand_id']=$brandId;
	$groupInfo['name']='default';
	$groupInfo['webserver_id']=$webserverId;
	$groupInfo['description']='';

	// configure the group with pre-defined group profile in the database
	// the profile is selected by the product_id in the license key if it is installed at the site creation time
	// Otherwise, the 'config' parameter passed in from the request (if provided)
	$profile=$config;
	if (isset($licenseInfo['product_id']))
		$profile=$licenseInfo['product_id'];
	if ($profile!='') {
		$aquery="(brand_id= '65535') AND (name LIKE '%".$profile."%')";
		VObject::Select(TB_GROUP, $aquery, $defInfo);
		// a default profile is found
		if (isset($defInfo['id'])) {
			foreach ($defInfo as $key => $val) {
				if ($key=='webserver_id' || $key=='videoserver_id' || $key=='remoteserver_id' || $key=='storageserver_id' ||
					$key=='webserver2_id' || $key=='videoserver2_id' || $key=='remoteserver2_id' || $key=='storageserver2_id' ||					
					$key=='teleserver_id' || $key=='conversionserver_id' ) 
				{
					if ($val!='0' && $val!='') {
						$groupInfo[$key]=$val;
					}
				}
			}
		}
		
		if (isset($groupInfo['teleserver_id']) && $groupInfo['teleserver_id']!='0') {
			$teleServer=new VTeleServer($groupInfo['teleserver_id']);
			$teleServer->Get($teleInfo);
		}
		
	}

/*	
	// find a default video conferencing server to assign to the group
	// the server must have brand_id equal to '0' or '65535' and
	// and the profile name must start with APS
	$query="(brand_id='0' OR brand_id='65535') AND (name LIKE 'APS%')";
	VObject::Select(TB_VIDEOSERVER, $query, $videoInfo);
	if (isset($videoInfo['id']))
		$groupInfo['videoserver_id']=$videoInfo['id'];
	
	// find a default audio conferencing server to assign to the group
	// the server must have brand_id equal to '0' or '65535' and
	// and the profile name must start with APS
	$query="(brand_id='0' OR brand_id='65535') AND (name LIKE 'APS%')";
	VObject::Select(TB_TELESERVER, $query, $teleInfo);
	if (isset($teleInfo['id']))
		$groupInfo['teleserver_id']=$teleInfo['id'];

		
	// find a default remote control server to assign to the group
	// the profile name must start with APS
	$query="(brand_id='0' OR brand_id='65535') AND (name LIKE 'APS%')";
	VObject::Select(TB_REMOTESERVER, $query, $remoteInfo);
	if (isset($remoteInfo['id']))
		$groupInfo['remoteserver_id']=$remoteInfo['id'];
		
	// find a default conversion server to assign to the group
	// the profile name must start with APS
	$query="(brand_id='0' OR brand_id='65535') AND (name LIKE 'APS%')";
	VObject::Select(TB_CONVERSIONSERVER, $query, $convInfo);
	if (isset($convInfo['id']))
		$groupInfo['conversionserver_id']=$convInfo['id'];
*/

	if ($group->Insert($groupInfo)!=ERR_NONE) {
		ExitOnErr($group->GetErrorMsg());
	}
	$group->GetValue("id", $groupId);
} else {
	$groupId=$groupInfo['id'];
}

// create admin user
if (!isset($adminInfo['id'])) {

	$admin=new VUser();
	$adminInfo['brand_id']=$brandId;
	$adminInfo['login']=$adminLogin;		
	$adminInfo['first_name']=$adminFirstName;	
	$adminInfo['last_name']=$adminLastName;
	$adminInfo['email']=$adminEmail;
	$adminInfo['password']=$adminPassword;
	$adminInfo['permission']='ADMIN';
	$adminInfo['group_id']=$groupId;
	$adminInfo['license_id']=$trialId;
	$adminInfo['create_date']=date('Y-m-d H:i:s');
	
	// assign a conf number to the user	
	if (isset($teleInfo['id']) && $teleInfo['can_getconf']=='Y') {
		require_once("api_includes/free_conf.php");
		GetFreeConfManager($adminInfo, $confMgr, $confUser, $confPass);
		if ($confMgr!='') {
			$freeNum=$freeMcode=$freePcode='';	
			FreeConfRequest($confMgr, $confUser, $confPass, $freeNum, $freeMcode, $freePcode);
			
			if ($freeNum!='') {
				$freeNum=AddSpacesToPhone($freeNum);				
				$adminInfo['conf_num']=$freeNum;
				$adminInfo['conf_mcode']=$freeMcode;
				$adminInfo['conf_pcode']=$freePcode;
				$adminInfo['use_teleserver']='Y';
			}
		}
	}	
			
	if ($admin->Insert($adminInfo)!=ERR_NONE) {
		ExitOnErr($admin->GetErrorMsg());
	}
	$admin->GetValue("id", $adminId);

} else {
	$adminId=$adminInfo['id'];
	
	$adminUpdateInfo=array();
	$adminUpdateInfo['first_name']=$adminFirstName;	
	$adminUpdateInfo['last_name']=$adminLastName;
	$adminUpdateInfo['email']=$adminEmail;
	$admin=new VUser($adminId);
	if ($admin->Update($adminUpdateInfo)!=ERR_NONE) {
		ExitOnErr($admin->GetErrorMsg());
	}
}
		
// update the brand's info
$newInfo=array();
$newInfo['admin_id']=$adminId;
$newInfo['trial_group_id']=$groupId;
if ($brand->Update($newInfo)!=ERR_NONE) {
	ExitOnErr($brand->GetErrorMsg());
}

if ($cmd=='ADD_PROVIDER_BRAND') {
	// add a new meeting automatically for the user
	$meeting=new VMeeting();
	$meetingInfo=array();
	$meetingInfo['host_id']=$adminId;
	$meetingInfo['title']=_Text("My Meeting");
	$meetingInfo['brand_id']=$brandId;
	$meetingInfo['description']="";
	$meetingInfo['keyword']="";
	
	// if the user has a teleconf number assigned, add that number to the meeting automatically
	if (isset($adminInfo['conf_num']) && $adminInfo['conf_num']!='') {
		$meetingInfo['tele_num']=$adminInfo['conf_num'];
		$meetingInfo['tele_mcode']=$adminInfo['conf_mcode'];
		$meetingInfo['tele_pcode']=$adminInfo['conf_pcode'];
		$meetingInfo['tele_conf']='Y';
		if (isset($adminInfo['conf_num2']))
			$meetingInfo['tele_num2']=$adminInfo['conf_num2'];
		
	}
	
	// add a new meeting
	if ($meeting->Insert($meetingInfo)!=ERR_NONE)
		ExitOnErr($meeting->GetErrorMsg());
}

// send email to the admin
if ($adminEmail!='' && valid_email($adminEmail)) {
	$subject="New Web Conferencing Site";
	$body="A web conferencing site has been created.\n";
	$body.="URL: $siteUrl\n";
	$body.="Admin login: ".$adminInfo['login']."\n";
	$body.="Admin password: ".$adminInfo['password']."\n";
	$toName=$adminFirstName." ".$adminLastName;
	$toEmail=$adminEmail;
	
	VMailTemplate::Send('', SERVER_EMAIL, $toName, $toEmail, $subject, $body);
//	VMailTemplate::Send($siteEmailName, $siteEmailAddr, $toName, $toEmail, $subject, $body);
}

$resp="OK\nbrand=$brandName&login=$siteLogin&password=$sitePassword";
echo ($resp);
exit();

?>