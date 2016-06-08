<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

//require_once('includes/common.php');

require_once('includes/common_lib.php');
require_once("api_includes/common.php");
require_once("dbobjects/vwebserver.php");

GetArg('cmd', $cmd);

if (isset($_GET['token']) && $_GET['token']!='') {
	require_once("header.php");
	StartSession();

	// token based authentication
	require_once("dbobjects/vtoken.php");
	require_once("dbobjects/vuser.php");
	VToken::GetToken($_GET['token'], $tokenInfo);
	
	if (!isset($tokenInfo['token'])) {
		$tokenCode=$_GET['token'];
		API_EXIT(API_ERR, "Token $tokenCode is not found or invalid. ".$_SERVER['QUERY_STRING']);
	}			

	if (isset($tokenInfo['user_id']) && $tokenInfo['user_id']!='0' && $tokenInfo['user_id']!=GetSessionValue('member_access_id')) {

		// get user info from the cache
		// write the member info to a cache file
		$cacheKey=TB_USER.'access_id'.$tokenInfo['user_id'];
		$cacheFile=VObject::GetCachePath($cacheKey);
		
		// log in as the user
		if (!VObject::ReadFromCache($cacheFile, $userInfo) || !isset($userInfo['id'])) {
			API_EXIT(API_ERR, "User associated with the token cannot be found.");
		}
	
		
		/* don't access DB here
		$errMsg=VObject::Find(TB_USER, 'access_id', $tokenInfo['user_id'], $userInfo);
		if ($errMsg!='') {
			API_EXIT(API_ERR, $errMsg);
		}
		if (!isset($userInfo['id'])) {
			API_EXIT(API_ERR, "User associated with the token cannot be found.");
		}
		*/
		$memberName=VUSer::GetFullName($userInfo);
		SetSessionValue("member_name", $memberName);
		SetSessionValue("member_login", $userInfo['login']);
		SetSessionValue("member_id", $userInfo['id']);					
		SetSessionValue("member_perm", $userInfo['permission']);					
		SetSessionValue("member_brand_name", $tokenInfo['brand']);	
		SetSessionValue("member_brand", $userInfo['brand_id']);	
		if ($userInfo['time_zone']!='')
			SetSessionValue("time_zone", $userInfo['time_zone']);			
		SetSessionValue("member_access_id", $userInfo['access_id']);		
	}
	
	SetSessionValue("meeting_access_id", $tokenInfo['meeting_id']);	
	SetSessionValue('brand_name', $tokenInfo['brand']);
} else if (isset($_GET['signature'])) {
	require_once("dbobjects/vbrand.php");
	
	if (!isset($_REQUEST['brand'])) {
		API_EXIT(API_ERR, "'brand' parameter is not provided.");
	}
	
	$brandName=$_REQUEST['brand'];
	$brandInfo=array();
	VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
	if (!isset($brandInfo['id'])) {
		API_EXIT(API_ERR, "Brand '$brandName' is not found in our records.");
	}
	
	if (!isset($brandInfo['api_key'])) {
		API_EXIT(API_ERR, "API access key is not set for this site.");
	}
	
	if ($brandInfo['status']=='INACTIVE')
		API_EXIT(API_ERR, "The brand is not active.");
	
	$query=$_SERVER['QUERY_STRING'];
	
	// signature based authentication
	// remove signature from the query string
	$query=str_replace("&signature=".$_REQUEST['signature'], "", $query);	

	if ($_REQUEST['signature']!=md5($query.$brandInfo['api_key'])) {
		API_EXIT(API_ERR, "Invalid signature. Input: ".htmlspecialchars($query));
	}
	
	// log in as the site admin
	$adminId=$brandInfo['admin_id'];
	$admin=new VUser($adminId);
	$admin->Get($userInfo);
	if (!isset($userInfo['id'])) {
		API_EXIT(API_ERR, "Site admin is not set or cannot be found.");			
	}
	$memberName=VUSer::GetFullName($userInfo);
	SetSessionValue("member_name", $memberName);
	SetSessionValue("member_login", $userInfo['login']);
	SetSessionValue("member_id", $userInfo['id']);					
	SetSessionValue("member_perm", $userInfo['permission']);					
	SetSessionValue("member_brand", $userInfo['brand_id']);	
	SetSessionValue("member_brand_name", $brandName);	
	if ($userInfo['time_zone']!='')
		SetSessionValue("time_zone", $userInfo['time_zone']);			
	SetSessionValue("member_access_id", $userInfo['access_id']);
	SetSessionValue('brand_name', $brandName);
} else {
	
	// These calls are maded by the Flash viewer during a meeting and we don't need (and don't want) to create a session file for them
	if ($cmd!='GET_TELE_PARTICIPANTS' && $cmd!='SET_SESSION' && $cmd!='SET_STATS' && $cmd!='GET_VIEWER_BACKGROUND' && $cmd!='GET_CONTENT') {	
		require_once("header.php");
		StartSession();
	}

	if (isset($_REQUEST['brand'])) {
		SetSessionValue('brand_name', $_REQUEST['brand']);
	}	
}

/*
// one of these session values should be set
// these values are set when the user visit a site, has logged in, or access with an API token or signature
if (GetSessionValue("brand_name")=='' && GetSessionValue("meeting_access_id")=='' && GetSessionValue("member_id")=='')
	API_EXIT(API_ERR, "Access is not authorized.");
*/		


//print_r ($_POST);
//print_r($_FILES);

if ($cmd=='SET_SESSION') {
include_once('api_includes/set_session.php');
} elseif ($cmd=='SET_MEETING' || $cmd=='ADD_MEETING') {
include_once('api_includes/set_meeting.php');	
} else if ($cmd=='END_MEETING' || $cmd=='LEAVE_MEETING') {
include_once('api_includes/end_meeting.php');		
} else if ($cmd=='START_MEETING') {
include_once('api_includes/start_meeting.php');
} else if ($cmd=='GET_HOSTURL') {
include_once('api_includes/meeting_url.php');
} else if ($cmd=='GET_ATTURL') {
include_once('api_includes/meeting_url.php');
} else if ($cmd=='DELETE_MEETING') {
include_once('api_includes/delete_meeting.php');
} else if ($cmd=='GET_MEETING_INFO' || $cmd=='GET_VIEWER_INFO' || $cmd=='GET_SHARING_INFO' || $cmd=='GET_VIEWER_BACKGROUND'
	|| $cmd=='GET_VSA' || $cmd=='GET_ICAL') {
include_once('api_includes/get_meeting.php');
} else if ($cmd=='GET_JNLP') {
include_once('api_includes/vpresent_jnlp.php');
/* live attendees are no longer stored in the database. use live meeting events to get/set attendees
} else if ($cmd=='ADD_ATTENDEE' || $cmd=='GET_ATTENDEE_LIST' || $cmd=='SET_ATTENDEE') {
include_once('api_includes/attendee.php');
*/
} else if ($cmd=='CHECK_LOGIN') {
// this is not used anymore
//include_once('api_includes/check_login.php');
} else if ($cmd=='GET_VCARD') {
include_once('api_includes/user.php');
} else if ($cmd=='SET_USER' || $cmd=='ADD_USER' || $cmd=='DELETE_USER') {
include_once('api_includes/user.php');
} else if ($cmd=='GET_USER_LIST') {
	include_once('api_includes/users.php');
} else if ($cmd=='SEND_USER') {
include_once('api_includes/send_user.php');
} else if ($cmd=='SET_GROUP' || $cmd=='ADD_GROUP' || $cmd=='DELETE_GROUP') {
include_once('api_includes/group.php');
} else if ($cmd=='SET_ROOM') {
include_once('api_includes/room.php');
} else if ($cmd=='SET_VIEWER') {
include_once('api_includes/viewer.php');
} else if ($cmd=='ADD_WEB' || $cmd=='SET_WEB' || $cmd=='DELETE_WEB') {
include_once('api_includes/web.php');
//} else if ($cmd=='ADD_AWS' || $cmd=='CHECK_AWS' || $cmd=='DELETE_AWS') {
//include_once('api_includes/aws.php');
} else if ($cmd=='ADD_VIDEO' || $cmd=='SET_VIDEO' || $cmd=='DELETE_VIDEO') {
include_once('api_includes/video.php');
} else if ($cmd=='ADD_TELE' || $cmd=='SET_TELE' || $cmd=='DELETE_TELE') {
include_once('api_includes/teleserver.php');
} else if ($cmd=='ADD_REMOTE' || $cmd=='SET_REMOTE' || $cmd=='DELETE_REMOTE') {
include_once('api_includes/remote.php');
} else if ($cmd=='ADD_STORAGE' || $cmd=='SET_STORAGE' || $cmd=='DELETE_STORAGE') {
include_once('api_includes/storage.php');
} else if ($cmd=='ADD_CONVERSION' || $cmd=='DELETE_CONVERSION' || $cmd=='SET_CONVERSION') {
include_once('api_includes/conversionserver.php');
} else if ($cmd=='SET_BRAND') {
include_once('api_includes/brand.php');
} else if ($cmd=='ADD_PROVIDER_BRAND'|| $cmd=='SET_PROVIDER_BRAND') {
include_once('api_includes/add_brand.php');
} else if ($cmd=='DELETE_PROVIDER_BRAND') {
include_once('api_includes/delete_brand.php');
} else if ($cmd=='GET_LIBRARY' || $cmd=='GET_LIB_UPLOAD') {
include_once('api_includes/library.php');
} else if ($cmd=='REGISTER_MEETING') {
include_once('api_includes/register.php');
} else if ($cmd=='GET_REGISTRATIONS' || $cmd=='DELETE_REGISTRATION') {
include_once('api_includes/registrations.php');
} else if ($cmd=='GET_SESSION_INFO') {
include_once('api_includes/session.php');
} else if ($cmd=='ADD_COMMENT' || $cmd=='SET_COMMENT') {
include_once('api_includes/comment.php');
} else if ($cmd=='END_RECORDING') {
include_once('api_includes/end_recording.php');
} else if ($cmd=='START_RECORDING') {
include_once('api_includes/start_recording.php');
} else if ($cmd=='GET_PORT_INFO') {
include_once('api_includes/port.php');
} else if ($cmd=='CHECK_RECORDING_STATUS' ||
	$cmd=='CHECK_RECORDING_FILE' || $cmd=='CREATE_RECORDING_FILE' || $cmd=='GET_RECORDING_FILE' ) {
include_once('api_includes/audio_recording.php');
} else if ($cmd=='DOWNLOAD_RECORDING') {
include_once('api_includes/download_recording.php');
} else if ($cmd=='SET_TELE_PARTICIPANT' || $cmd=='SET_TELE_CONFERENCE' || $cmd=='GET_TELE_PARTICIPANTS') {
include_once('api_includes/teleconf.php');
} else if ($cmd=='INSTALL_LICENSE' || $cmd=='REMOVE_LICENSE') {
include_once('api_includes/install_license.php');	
} else if ($cmd=='SET_VERSION') {
include_once('api_includes/set_version.php');
} else if ($cmd=='SET_MEETINGS') {
include_once('api_includes/meetings.php');
} else if ($cmd=='EXPORT_REC') {
include_once('api_includes/export_rec.php');
} else if ($cmd=='SET_FOLDER' || $cmd=='DELETE_FOLDER') {
include_once('api_includes/folder.php');
} else if ($cmd=='GET_LOGIN') {
include_once('api_includes/get_login.php');	
} else if ($cmd=='GET_INSTALL_DIR' || $cmd=='GET_INSTALL_FILE') {
include_once('api_includes/get_install.php');
} else if ($cmd=='ADD_CONTENT' || $cmd=='DELETE_CONTENT' || $cmd=='SET_CONTENT' || $cmd=='GET_CONTENT') {
include_once('api_includes/content.php');
} else if ($cmd=='ADD_REGFORM' || $cmd=='SET_REGFORM') {
include_once('api_includes/regform.php');
} else if ($cmd=='ADD_QUESTION' || $cmd=='SET_QUESTION' || $cmd=='DELETE_QUESTION' || $cmd=='MOVE_QUESTION' || $cmd=='GET_QUESTION') {
include_once('api_includes/question.php');
} else if ($cmd=='NOTIFY_USER') {
include_once('api_includes/notify.php');
} else if ($cmd=='SET_REPLAY') {
include_once('api_includes/replay.php');
} else if ($cmd=='SEND_SMS') {
include_once('api_includes/sms.php');
} else if ($cmd=='GET_SERVERS') {
include_once('api_includes/get_server.php');
} else if ($cmd=='UPDATE_LICENSE') {
include_once('api_includes/update_license.php');
} else if ($cmd=='SET_STATS' || $cmd=='GET_STATS') {
include_once('api_includes/stats.php');
} else {
	API_EXIT(API_ERR, "cmd $cmd is missing or invalid");
}

if (GetArg('return', $page))
{
	$page=VWebServer::DecodeDelimiter1($page);

	if (defined('SID') && SID!='') {
		if (strpos($page, '?')===false)
			$page.="?".SID;
		else
			$page.="&".SID;
	}
	header("Location: $page");
} else {

	API_EXIT();
}

?>