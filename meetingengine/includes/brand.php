<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

// the brand parameter may be passed in with GET or POST
if (isset($_REQUEST['brand']))
	$brandName=$_REQUEST['brand'];
else
	$brandName=GetSessionValue('brand_name');
	
if ($brandName=='') {
//	ShowError("Brand name is not set");
	ShowError("Your session has expired or session cookies are not enabled. Make sure your browser accept cookies.");
	DoExit();
}

require_once("dbobjects/vobject.php");
require_once('dbobjects/vbrand.php');
require_once('dbobjects/vimage.php');

$gBrandInfo=array();

$cacheKey=TB_BRAND.'name'.$brandName;
$dbOk=false;
if (VObject::CanOpenDB()) {
	$errMsg=VObject::Find(TB_BRAND, 'name', $brandName, $gBrandInfo);
	if ($errMsg!='') {
		ShowError($errMsg);
		DoExit();
	}
	if (!isset($gBrandInfo['id'])) {
		ShowError("Brand '$brandName' is not found in our records.");
		DoExit();
	}
	$dbOk=true;
	
	// update the cache file for the brand
	// update if the cache file doesn't exist or is older than 300 seconds
	$cacheFile=VObject::GetCachePath($cacheKey);
	$updateCache=false;
	if (!file_exists($cacheFile))
		$updateCache=true;
	else if (time()-filemtime($cacheFile)>300)
		$updateCache=true;

	if ($updateCache) {
		VObject::WriteToCache($cacheFile, $gBrandInfo);
	}
	
} else {
	
	$cacheKey=TB_BRAND.'name'.$brandName;
	$cacheFile=VObject::GetCachePath($cacheKey);
	if (!VObject::ReadFromCache($cacheFile, $gBrandInfo) || !isset($gBrandInfo['id'])) {
		ShowError("Couldn't get info for the brand.");
		DoExit();	
	}
}

if ($gBrandInfo['status']=='INACTIVE') {
	ShowError("The site is not active.");
	DoExit();	
}

$oldBrand=GetSessionValue('brand_name');
if ($oldBrand!='' && $oldBrand!=$brandName) {

	// make these values persistent even after signing out
	$tz=GetSessionValue('time_zone');
	$ilogin=GetSessionValue('iphone_login');
	$iuser=GetSessionValue('iphone_username');
	EndSession();
	StartSession();
	if ($tz!='')
		SetSessionValue('time_zone', $tz);
	if ($ilogin!='')
		SetSessionValue('iphone_login', $ilogin);
	if ($iuser!='')
		SetSessionValue('iphone_username', $iuser);
}

SetSessionValue('brand_name', $brandName);
if (GetSessionValue('time_zone')=='') {
	SetSessionValue('time_zone', $gBrandInfo['time_zone']);
}
//echo ("brand=".$brandName." brand_id=".$brandInfo['id']);
	
$pictFile='';
if ($gBrandInfo['logo_id']>0 && $dbOk) {
	$pict=new VImage($gBrandInfo['logo_id']);
	if ($pict->GetValue('file_name', $pictFile)!=ERR_NONE) {
		// don't show the error here
		ShowError ($pict->GetErrorMsg());
	} else {
//		$pictFile=DIR_IMAGE.$pictFile;
		$pictFile=VImage::GetFileUrl($pictFile);
	}
}

if ($gBrandInfo['embed_site']=='Y' || GetSessionValue('embed')=='1') {
	$GLOBALS['TARGET']='_self';
	$GLOBALS['BRAND_URL']=SITE_URL;
} else {
	$GLOBALS['TARGET']='_parent';
	
	if (isset($_REQUEST['brandUrl'])) {
		$GLOBALS['BRAND_URL']=$_REQUEST['brandUrl'];
	} else {
		$GLOBALS['BRAND_URL']=$gBrandInfo['site_url'];
	}
}
SetSessionValue('brand_url', $GLOBALS['BRAND_URL']);

$GLOBALS['BRAND_NAME']=$gBrandInfo['name'];
$GLOBALS['BRAND_ID']=$gBrandInfo['id'];
$GLOBALS['THEME']=$gBrandInfo['theme'];
$GLOBALS['LOGO_URL']=$pictFile;

if (isset($_REQUEST['locale']) && $_REQUEST['locale']!='') {
	$locale=$_REQUEST['locale'];
	SetSessionValue('locale', $locale);
} else if (GetSessionValue('locale')!='') {
	$locale=GetSessionValue('locale');
} else {
	$locale=$gBrandInfo['locale'];
}

// if the used is logged in and the following session values are not set yet, set them here once
$memberId=GetSessionValue("member_id");
$canRecord=GetSessionValue("can_record");
if ($memberId!='' && $canRecord=='') {

	require_once('dbobjects/vuser.php');
	require_once('dbobjects/vlicense.php');
	require_once('dbobjects/vgroup.php');
	require_once('dbobjects/vteleserver.php');
	
	$user=new VUser($memberId);
	$user->Get($theUserInfo);
	
	// check if recording, library, and registration are enabled for the user
	$canRecord='Y';
	$hasLibrary='Y';
	$hasRegist='Y';
	$canPoll='Y';
	
	// check first if the user's license allows recording
	$licenseId=$theUserInfo['license_id'];
	$theLicense=new VLicense($licenseId);
	$theLicense->GetValue('btn_disabled', $licDisabled);
	if ($licDisabled && $licDisabled!='') {
		$btns=explode(",", $licDisabled);
		foreach ($btns as $abtn) {
			if ($abtn=='record') {
				$canRecord='N';
			} else if ($abtn=='library') {
				$hasLibrary='N';
			} else if ($abtn=='register') {
				$hasRegist='N';
			} else if ($abtn=='poll') {
				$canPoll='N';
			}
		}
	}
	
	// check next if the user's group has recording enabled
	if ($canRecord=='Y') {
		$theGroup=new VGroup($theUserInfo['group_id']);
		$theGroup->GetValue('teleserver_id', $theTeleId);
		if ($theTeleId && $theTeleId!='0') {
			$theTeleServer=new VTeleServer($theTeleId);
			$theTeleServer->GetValue('can_record', $canRecord);
		}
	}
	
	SetSessionValue("can_record", $canRecord);
	SetSessionValue("has_library", $hasLibrary);
	SetSessionValue("has_regist", $hasRegist);
	if (defined('ENABLE_POLLING') && constant('ENABLE_POLLING')=='1') {
		SetSessionValue("can_poll", $canPoll);
	} else {
		SetSessionValue("can_poll", "0");
	}

}

/* 
// Trying to get PHP gettext to work with setlocale but couldn't.
// The following code works on xampp on Windows but you have to restart PHP every time you change the locale file
// Couldn't get it to work on touchmeeting.net Linux/Apache
// Give up for now. Use our own locale file instead of gettext's .po files.
$codeset = "UTF8";
$domain="wc2";	// needs to match the .po file name in the locales directory

// for windows compatibility (e.g. xampp) : theses 3 lines are useless for linux systems
putenv('LANG='.$locale.'.'.$codeset);
putenv('LANGUAGE='.$locale.'.'.$codeset);
bind_textdomain_codeset($domain, $codeset); 

putenv ("LC_ALL=".$locale); 

// Specify location of translation tables 
bindtextdomain ($domain, "locales"); 

// Choose domain 
textdomain ($domain);
*/

//$GLOBALS['LOCALE_FILE']="locales/".$gBrandInfo['locale'].".php";
$GLOBALS['LOCALE']=$locale;
$GLOBALS['LOCALE_FILE']="locales/".$locale."/wc2.php";

for ($i=1; $i<5; $i++) {		
	$bkey="footer".$i."_label";
	$key="FOOTER_".$i;
	$GLOBALS[$key]=$gBrandInfo[$bkey];
}
	
//	$GLOBALS['FOOTNOTE']="&copy; ".date('Y')." ".$brandInfo['footnote'];
$GLOBALS['FOOTNOTE']=$gBrandInfo['footnote'];

if (isset($gBrandInfo['custom_tabs']) && $gBrandInfo['custom_tabs']!='')
	$GLOBALS['MAIN_TABS']=$gBrandInfo['custom_tabs'];
else
	$GLOBALS['MAIN_TABS']="HOME,MEETINGS,LIBRARY,ACCOUNT,ADMIN";
		
if (isset($gBrandInfo['site_level']))
	$GLOBALS['SITE_LEVEL']=$gBrandInfo['site_level'];

?>