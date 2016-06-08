<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vstorageserver.php");
require_once("includes/common.php");
require_once("api_includes/common.php");

//if (!isset($_SESSION['member_id']))
//	exit();

// this function is called from a Flash viewer and 
// the session values don't seem to be always preserved so don't use the session values here
//$memberId=GetSessionValue('member_id');
//$memberPerm=GetSessionValue('member_perm');
//$memberBrand=GetSessionValue('member_brand');

if (GetArg("user", $accessId)) {
	VObject::Find(TB_USER, 'access_id', $accessId, $userInfo);

	if (isset($userInfo['id'])) {
		$userId=$userInfo['id'];
		$user=new VUser($userId);
	} else {
		API_EXIT(APP_ERR, "User is not found in our records.");
//		ShowError("User is not found in our records.");
//		DoExit();
	}
	
} else if (GetArg('user_id', $userId)) {
	
	$user=new VUser($userId);
	$user->Get($userInfo);
	if (!isset($userInfo['brand_id'])) {
		API_EXIT(APP_ERR, "User is not found in our records.");
//		ShowError("User is not found in our records.");
//		DoExit();
	}
} else {
	API_EXIT(APP_ERR, "User is not provided in the input parameters.");
//	ShowError("User is not provided in the input parameters.");
//	DoExit();
}

// this function is called from a Flash viewer and 
// the session value doesn't seem to be always preserved
// disable the checking for now
if ($memberId!=$userId) {
//	API_EXIT(APP_ERR, "User is not signed in. me=$memberId u=$userId");
//	ShowError("User is not signed in. me=$memberId u=$userId");
//	DoExit();
}

$brandId=$userInfo['brand_id'];
/*
$brand=new VBrand($brandId);
$brandInfo=array();
if ($brand->Get($brandInfo)!=ERR_NONE) {
	ShowError("User site not found.");
	DoExit();
}
*/

//$sitePath=$brandInfo['site_url'];
$errMsg=VUser::GetStorageUrl($brandId, $userInfo, $sitePath, $id, $password, $storageServerId);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}
// need to remove the trailing slash or the library browser won't work
$len=strlen($sitePath);
if ($sitePath[$len-1]=='/')
	$sitePath=substr($sitePath, 0, $len-1);
	
if ($userInfo['permission']=='ADMIN')
	$pubPerms='read/write/delete';
else
	$pubPerms='read';
	
/*	
$locale=$brandInfo['locale'];
if ($locale=='')
	$locale='en';
*/	
include_once("includes/common_text.php");

// must add this for IE7 to work on SSL download
header('Pragma: private');
header('Cache-control: private, must-revalidate');

header("Content-Type: text/xml");
echo XML_HEADER."\n";

$myLibText=$gText['M_MY_LIBRARY'];
$pubLibText=$gText['M_PUB_LIBRARY'];

$pubLibPath="vlibrary"; 
$myLibPath=$userInfo['access_id']."/vlibrary"; 

// add a rand path to defeat the browser cache
// rand_path needs to be in the form of /rand_xxxx/..
/* not needed for v 2.2.13+ because the library browser now adds a rand to each request
if (GetArg('rand_path', $arg)) {
	$randPath="/rand_".$arg."/..";
	$pubLibPath.=$randPath;
	$myLibPath.=$randPath;
}
*/

if ($userInfo['login']==VUSER_GUEST) {
	print <<<END
<libraries>
  <library name="$pubLibText" url="$sitePath" path="$pubLibPath" permissions="$pubPerms" type="public"/> 
</libraries>

END;
} else {


	print <<<END
<libraries>
  <library name="$myLibText" url="$sitePath" path="$myLibPath" permissions="read/write/delete"/>
  <library name="$pubLibText" url="$sitePath" path="$pubLibPath" permissions="$pubPerms" type="public"/> 
</libraries>

END;
	
}
?>

