<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * Called by Persony APS installer to remove a brand and any related records
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("dbobjects/vbrand.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vviewer.php");
require_once("dbobjects/vmailtemplate.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vcontent.php");
require_once("dbobjects/vbackground.php");
require_once("dbobjects/vimage.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vviewer.php");

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
	ExitOnErr("A required parameter is not provided.1");
}

GetArg('brand', $brand);
GetArg('site_login', $siteLogin);
GetArg('site_code', $siteCode);
if ($brand=='' || $siteLogin=='' || $siteCode=='') {
	ExitOnErr("A required parameter is not provided.");
}


$len=strlen($siteUrl);
if ($len>0 && $siteUrl[$len-1]!='/')
	$siteUrl.='/';
	
// check if the site file still exists
// don't allow the deletion if the site still exists
$getUrl=$siteUrl."vversion.php";
$resp=@file_get_contents($getUrl);
if ($resp) {
	// check if the response is a version number (check the string length)
	$len=strlen($resp);
	if ($len>0 && $len<16)
		ExitOnErr("The request is not allowed.");
}
	
$query="name= '".$brand."' AND site_url = '".$siteUrl."'";
$errMsg=VObject::Select(TB_BRAND, $query, $brandInfo);
if ($errMsg!='')
	ExitOnErr("A database error occurred while processing your request.");

if (!isset($brandInfo['id'])) {
	ExitOnErr("The site you want to remove does not exist in our records");
}

$adminInfo=array();
$providerInfo=array();
$viewerInfo=array();
$webServerInfo=array();
$groupInfo=array();	

$query="brand_id= '".$brandInfo['id']."' AND url= '".$siteUrl."'";
$errMsg=VObject::Select(TB_WEBSERVER, $query, $webServerInfo);
if ($errMsg!='')
	ExitOnErr("A database error occurred while processing your request.");
/*
if ($webServerInfo['login']!=$siteLogin || md5($webServerInfo['password'])!=$siteCode) {
	ExitOnErr("The site login or password does not match our records.");	
}
*/

$provider=new VProvider($brandInfo['provider_id']);
$provider->Get($providerInfo);

/*

// only allow the removal if the provider matches the site
// this only works if the provider and the brand are created with add_brand.php
if ($providerInfo['login']!=$brandInfo['name']) {
	ExitOnErr("You are not allowed to remove this site.");	
}
*/


$query="provider_id= '".$providerInfo['id'];
$errMsg=VObject::Count(TB_BRAND, $query, $num);
if ($num<=1) {
	// there is only one brand in this provider account
	// delete the provider record
	if ($provider->Drop()!=ERR_NONE) {
		//	ExitOnErr($provider->GetErrorMsg());
	}
}

// delete the webserver record
if (isset($webServerInfo['id'])) {
	$webServer=new VWebServer($webServerInfo['id']);
	if ($webServer->Drop()!=ERR_NONE) {
//		ExitOnErr($webServer->GetErrorMsg());
	}
}

// delete the viewer
if ($brandInfo['viewer_id']!='0') {
	$viewer=new VViewer($brandInfo['viewer_id']);
	if ($viewer->Drop()!=ERR_NONE) {
//		ExitOnErr($viewer->GetErrorMsg());
	}
}
// delete all groups of the site
$query="brand_id= '".$brandInfo['id']."'";
VObject::SelectAll(TB_GROUP, $query, $result);
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	$group=new VGroup($row['id']);
	if ($group->Drop()!=ERR_NONE) {
//		ExitOnErr($group->GetErrorMsg());
	}
}

// delete all users and their meetings and contents
$query="brand_id= '".$brandInfo['id']."' and login <> '_root'";
VObject::SelectAll(TB_USER, $query, $userResults);
while ($row = mysql_fetch_array($userResults, MYSQL_ASSOC)) {
	if ($row['viewer_id']!='0') {
		$viewer=new VViewer($row['viewer_id']);
		if ($viewer->Drop()!=ERR_NONE) {
//			ExitOnErr($viewer->GetErrorMsg());
		}

	}
	if ($row['logo_id']!='0') {
		$image=new VImage($row['logo_id']);
		if ($image->Drop()!=ERR_NONE) {
//			ExitOnErr($image->GetErrorMsg());
		}

	}
	$meetingQuery="brand_id='".$brandInfo['id']."' AND host_id='".$row['id']."'";
	VObject::SelectAll(TB_MEETING, $meetingQuery, $meetingResults);
	while ($meetingRow = mysql_fetch_array($meetingResults, MYSQL_ASSOC)) {
		$meeting=new VMeeting($meetingRow['id']);
		if ($meeting->Drop()!=ERR_NONE) {
//			ExitOnErr($meeting->GetErrorMsg());
		}
	}
	$backQuery="brand_id='".$brandInfo['id']."' AND author_id='".$row['id']."'";
	VObject::SelectAll(TB_BACKGROUND, $backQuery, $backResults);
	while ($backRow = mysql_fetch_array($backResults, MYSQL_ASSOC)) {
		$back=new VBackground($backRow['id']);
		if ($back->Drop()!=ERR_NONE) {
//			ExitOnErr($back->GetErrorMsg());
		}
	}
	$contentQuery="brand_id='".$brandInfo['id']."' AND owner_id='".$row['id']."'";
	VObject::SelectAll(TB_CONTENT, $contentQuery, $contentResults);
	while ($contentRow = mysql_fetch_array($contentResults, MYSQL_ASSOC)) {
		$content=new VContent($contentRow['id']);
		if ($content->Drop()!=ERR_NONE) {
//			ExitOnErr($content->GetErrorMsg());
		}
	}
	$user=new VUser($row['id']);
	if ($user->Drop()!=ERR_NONE) {
//		ExitOnErr($user->GetErrorMsg());
	}
}

// delete the brand
$brand=new VBrand($brandInfo['id']);
if ($brand->Drop()!=ERR_NONE) {
//	ExitOnErr($user->GetErrorMsg());
}

// send email to the admin
// find the admin user of the brand
$admin=new VUser($brandInfo['admin_id']);
$admin->Get($adminInfo);

if (isset($adminInfo['id'])) {
	$adminName=VUser::GetFullName($adminInfo);
	
	$subject="Remove Web Conferencing Site";
	$body="The following web conferencing site and all its users have been removed from our records.\n";
	$body.="URL: $siteUrl\n";
	$toName=$adminName;
	$toEmail=$adminInfo['email'];
	if (valid_email($toEmail))	
		VMailTemplate::Send($brandInfo['from_name'], $brandInfo['from_email'], $toName, $toEmail, $subject, $body,
				'', '', "", false, null, $brandInfo);
	
}

echo ("OK");
exit();

?>