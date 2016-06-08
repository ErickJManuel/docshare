<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common.php");
require_once("includes/brand.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");

function ErrorExit2($errMsg)
{
	$siteUrl=GetSessionValue('brand_url');
	if ($siteUrl=='')
		$siteUrl="index.php?brand=".GetSessionValue('brand_name');

	$errPage="$siteUrl?page=".PG_HOME_ERROR."&".SID."&error=".rawurlencode($errMsg);
	header("Location: ".$errPage);
	exit;
}

$memberId=GetSessionValue('member_id');

// already signed in; redirect to the meetings page
if ($memberId!='') {
	header("Location: meetings.php?".SID);
	exit();
}

if (!GetArg('group_id', $groupId))
	ErrorExit2("Missing group_id");

if (!GetArg('brand', $brandName))
	ErrorExit2("Missing brand name");
	
if (!GetArg('license_id', $licId))
	ErrorExit2("Missing license_id");
/*	
$brandId='';
$brandInfo=array();
$errMsg=VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
if ($errMsg!='')
	ErrorExit2($errMsg);
if (!isset($brandInfo['id']))
	ErrorExit2("Brand '$brandName' not found");
*/

// called by a guest user to start a meeting without signning up

$guestLogin=VUSER_GUEST;

$userInfo=array();
$query="brand_id='".$gBrandInfo['id']."' AND login='".$guestLogin."'";
$errMsg=VObject::Select(TB_USER, $query, $userInfo);

if ($errMsg!='')
	ErrorExit2($errMsg);	
	
// the guest_user doesn't exist, create one
if (!isset($userInfo['id'])) {
	
	$user=new VUser();	

	$userInfo['brand_id']=$gBrandInfo['id'];	
	$userInfo['license_id']=$licId;
	$userInfo['viewer_id']=$gBrandInfo['viewer_id'];
	$userInfo['login']=$guestLogin;
	$userInfo['create_date']=date('Y-m-d H:i:s');		

	$userInfo['first_name']='Guest';	
	$userInfo['last_name']='User';
	
	$userInfo['permission']='HOST';
	$userInfo['group_id']=$groupId;
	$userInfo['password']=RandomPassword();
	$userInfo['room_description']="";
	
	if ($user->Insert($userInfo)!=ERR_NONE)
		ErrorExit2($user->GetErrorMsg());
			
	$user->GetValue('id', $theId);
	$userInfo['id']=$theId;
	
	$user->GetValue('access_id', $userId);
} else {
	$user=new VUser($userInfo['id']);
}

SetSessionValue('member_id', $userInfo['id']);
SetSessionValue('member_login', $userInfo['login']);

if ($user->UpdateServer()!=ERR_NONE)
	ErrorExit2($user->GetErrorMsg());

GetArg('meeting', $meetingId);
$meetingInfo=array();

if ($meetingId!='') {
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
	if ($errMsg!='')
		ErrorExit2($errMsg);
}

if (!isset($meetingInfo['id'])) {
	$meeting=new VMeeting();
	$meetingInfo['host_id']=$userInfo['id'];
	$meetingInfo['brand_id']=$userInfo['brand_id'];
	$meetingInfo['title']="My Meeting";
	$meetingInfo['description']="";
	$meetingInfo['keyword']="";
//	$webServerId=VUser::GetWebServerId($userInfo);
	if ($meetingInfo['webserver_id']!=0)
		$webServerId=$meetingInfo['webserver_id'];
	else
		$webServerId=VUser::GetWebServerId($userInfo);
	
	if ($webServerId<=0) {
		ErrorExit2("Web conference server is not set.");
	}
	$meetingInfo['webserver_id']=$webServerId;
	
	if ($meeting->Insert($meetingInfo)!=ERR_NONE) {
		ErrorExit2($meeting->GetErrorMsg());
	}
	if ($meeting->UpdateServer()!=ERR_NONE) {
		ErrorExit2($meeting->GetErrorMsg());
	}
	
	$meeting->Get($meetingInfo);
	SetSessionValue('meeting_id', $meetingInfo['access_id']);
	
} else {
	$meeting=new VMeeting($meetingInfo['id']);
	SetSessionValue('meeting_id', $meetingInfo['access_id']);
}

if  (!isset($meetingInfo['status']) || $meetingInfo['status']=='STOP') {	
	
	require_once('api_includes/common.php');			
	
	$api_error_message='';
	$api_exit=false;
	
	require_once('api_includes/start_meeting.php');			

	if ($api_error_message!='') {
		ErrorExit2($api_error_message);
	}
} else {
	
	
}
	
if ($meeting->GetViewerUrl(true, $meetingUrl, true)!=ERR_NONE) {
	ErrorExit2($meeting->GetErrorMsg());
}

if (SID!='') {
	$sessIdStr=str_replace("=", "%3D", SID);
	$meetingUrl.="&sid=".$sessIdStr;
}
header("Location: $meetingUrl");
exit();


?>