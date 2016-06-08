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

require_once("dbobjects/vuser.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vtoken.php");
/*
GetArg("token", $tokenCode);
if ($tokenCode!='')
	VToken::GetToken($tokenCode, $tokenInfo);
*/


$storageUrl='';
$storageId='';
$storageCode='';
if (GetArg('meeting', $arg)) {
require_once("dbobjects/vmeeting.php");
	$meetingId=$arg;
	
	$meetingFile=VMeeting::GetSessionCachePath($meetingId);	
	if (VMeeting::IsSessionCacheValid($meetingFile)) {
		// no database access
		@include_once($meetingFile);
		
		$storageUrl=$_storageUrl;
		$storageId=$_storageId;
		$storageCode=$_storageCode;
		
		$userInfo=array();
		$userInfo['access_id']=GetSessionValue("member_access_id");
		$userInfo['permission']=GetSessionValue("member_perm");
		$userInfo['login']=GetSessionValue("member_login");

	} else {		
		$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
		if ($errMsg=='') {
			if (isset($meetingInfo['id']))
				$meeting=new VMeeting($meetingInfo['id']);	
			else
				$errMsg="Meeting not found.";
		}
		
		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg);	

		$brandId=$meetingInfo['brand_id'];
		$host=new VUser($meetingInfo['host_id']);
		$host->Get($hostInfo);

		VUser::GetStorageUrl($brandId, $hostInfo, $storageUrl, $storageId, $storageCode, $storageServerId);
		$userInfo=array();
		$userInfo['access_id']=GetSessionValue("member_access_id");
		$userInfo['permission']=GetSessionValue("member_perm");
		$userInfo['login']=GetSessionValue("member_login");
		
	}
} else {
	
	require_once('api_includes/meeting_common.php');
	require_once('api_includes/user_common.php');
	
	GetArg('brand_id', $brandId);
	if ($brandId=='' && isset($userInfo['brand_id']))
		$brandId=$userInfo['brand_id'];
		
	VUser::GetStorageUrl($brandId, $userInfo, $storageUrl, $storageId, $storageCode, $storageServerId);

}

/*
if ($sitePath=='') {	
	$brand=new VBrand($brandId);
	$brandInfo=array();
	if ($brand->Get($brandInfo)!=ERR_NONE) {
		API_EXIT(API_ERR, $brand->GetErrorMsg());
	}
	$sitePath=$brandInfo['site_url'];
}
*/
// need to remove the trailing slash or the library browser won't work
$sitePath=$storageUrl;
$len=strlen($storageUrl);
if ($storageUrl[$len-1]=='/')
	$sitePath=substr($storageUrl, 0, $len-1);


if ($cmd=='GET_LIB_UPLOAD') {
@include_once("download/vpresent_version.php");

//	if (!IsLogin())
//		API_EXIT(API_ERR, 'Not signed in');

	$version='';
	if (isset($vpresent_version))
		$version=$vpresent_version;
		
	$minVersion='';
	if (isset($required_version))
		$minVersion=$required_version;		
/*		
	if (($errMsg=VSite::GetSiteUrl($siteUrl))!='') {
		API_EXIT(API_ERR, $errMsg);
	}
*/
	$siteUrl=SITE_URL;
	$downloadUrl=VWebServer::AddPaths($siteUrl, "download/download.php");
/*	
	if (!GetArg('user', $access_id) || $access_id=='')
		API_EXIT(API_ERR, "Missing user");

	VObject::Find(TB_USER, 'access_id', $access_id, $userInfo);
*/
	if (!isset($userInfo['id']))
		API_EXIT(API_ERR, "User not found");
		
	if ($userInfo['access_id']!=GetSessionValue("member_access_id")) {
		API_EXIT(API_ERR, "User not signed in.");
	}
		
	$xml=XML_HEADER."\n";
	
	$xml.="<vshowsc\n";
	
	$xml.="version=\"".$version."\"\n";
	$xml.="minVersion=\"".$minVersion."\"\n";
	$xml.="downloadUrl=\"".$downloadUrl."\"\n";
/*	
	$serverUrl=$brandInfo['site_url'];
	$query="brand_id='".$brandInfo['id']."' AND url = '$serverUrl'";
	$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
	if ($errMsg) {
		API_EXIT(API_ERR, $errMsg);	
	}
	if (!isset($serverInfo['id'])) {
		API_EXIT(API_ERR, "Site '$serverUrl' not found in our records. $query");	
	}
	
	$webServerId=VUser::GetWebServerId($userInfo);
	if ($webServerId<=0) {
		API_EXIT(API_ERR, "webserver_id not set");
	}		
	$webServer=new VWebServer($webServerId);

	$serverInfo=array();
	if ($webServer->Get($serverInfo)!=ERR_NONE) {
		API_EXIT(API_ERR, $webServer->GetErrorMsg());
	}
*/
	
//	$xml.="debug=\"".$webServerId."\"\n";
/*
	$php_ext=$serverInfo['php_ext'];
	$url=VWebServer::GetScriptUrl('', $php_ext);
	$postUrl=$url."?s=".SC_VFTP;
*/	
	$postUrl=SC_SCRIPT.".php?s=".SC_VFTP;
	$brandName=GetSessionValue('brand_name');
	$theUserId='0';
	if (isset($userInfo['access_id']))
		$theUserId=$userInfo['access_id'];
		
	$token=VToken::AddToken($brandName, "0", $theUserId, $userInfo);
	$fileUrl=$postUrl."&id=token&code=".VToken::GetBUMToken($brandName, $theUserId, "0", $token);
//	$fileUrl=$postUrl."&id=".$userInfo['access_id']."&code=$storageCode";
//	$fileUrl=$postUrl."&id=$storageId&code=$storageCode";

//	if (isset($_SERVER['REMOTE_ADDR']))
//		$fileUrl=$postUrl."&id=sig&code=".ComputeScriptSignature($_SERVER['REMOTE_ADDR']);
//	else {
//		$fileUrl=$postUrl."&id=${serverInfo['login']}&code=${serverInfo['password']}";
//	}
	//$fileUrl=rawurlencode($fileUrl);
	
	$xml.="fileScript=\"".VObject::StrToXml($fileUrl)."\"\n";
	$xml.="serverUrl=\"".$storageUrl."\"\n";	
//	$xml.="serverUrl=\"".$serverInfo['url']."\"\n";	
//	$xml.="hostID=\"".$userInfo['access_id']."\"\n";

	GetArg('public', $public);
	if ($public=='1' && $userInfo['permission']=='ADMIN')
		$xml.="libDir=\"vlibrary/\" ";
	else
		$xml.="libDir=\"".$userInfo['access_id']."/vlibrary/\" ";

	$xml.="/>";	

	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/xml");
	echo $xml;		
	API_EXIT(API_NOMSG);
	
}
if (isset($userInfo['access_id']))
	VUser::GetLibraryPath($userInfo['access_id'], $pubLibPath, $myLibPath);
else
	VUser::GetLibraryPath('', $pubLibPath, $myLibPath);
	
$pubLibName=_Text("Public Library");
$myLibName=_Text("My Library");

// must add this for IE7 to work on SSL download
header('Pragma: private');
header('Cache-control: private, must-revalidate');

header("Content-Type: text/xml");
echo XML_HEADER."\n";

//$pubLibPath="vlibrary"; // add a rand to defeat the cache. must be preceded with a |


if (!isset($userInfo['access_id']) || $userInfo['access_id']=='' || $userInfo['login']==VUSER_GUEST) {
	
	
print <<<END
<libraries>
  <library name="$pubLibName" url="$sitePath" path="$pubLibPath" permissions="read"/> 
</libraries>
END;


} else {

if ($userInfo['permission']=='ADMIN')
	$pubPerms='read/write/delete';
else
	$pubPerms='read';
	
//$myLibPath=$userInfo['access_id']."/vlibrary"; // add a rand to defeat the cache. must be preceded with a |

print <<<END
<libraries>
  <library name="$myLibName" url="$sitePath" path="$myLibPath" permissions="read/write/delete"/>
  <library name="$pubLibName" url="$sitePath" path="$pubLibPath" permissions="$pubPerms"/> 
</libraries>

END;
}
API_EXIT(API_NOMSG);
?>

