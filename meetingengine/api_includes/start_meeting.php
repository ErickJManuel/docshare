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


if (!isset($meetingInfo['id']))
	require_once('api_includes/meeting_common.php');

if (!isset($meeting)) {
	require_once("dbobjects/vmeeting.php");
	$meeting=new VMeeting($meetingInfo['id']);
}
//require_once('api_includes/user_common.php');
//require_once('includes/brand.php');
require_once("dbobjects/vhook.php");
require_once("dbobjects/vbrand.php");

//if ($userErrMsg!='')
//return API_EXIT(API_ERR, $userErrMsg);

if ($errMsg!='')
return API_EXIT(API_ERR, $errMsg);
	
if (!isset($meetingInfo['id']))
return API_EXIT(API_ERR, "Meeting id not set");

//if (SID!='')
//	return API_EXIT(API_ERR, "Session expired or cookies not enabled.");


//if ($meetingInfo['host_id']!=$userInfo['id'])
//return API_EXIT(API_ERR, "Not a host of the meeting");

// make sure this is not a recording
if ($meetingInfo['status']=='REC')
return API_EXIT(API_ERR, "You cannot start a recorded meeting.");

// make sure the meeting is not in progress
if ($meetingInfo['status']!='STOP')
return API_EXIT(API_ERR, "The meeting is in progress. Stop the meeting first.");

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if (isset($userInfo['login']) && $userInfo['login']==VUSER_GUEST) {
	// this is a guest user, no need to sign in
	$memberId=$userInfo['access_id'];
} else {
	
	if ($memberId=='')
		return API_EXIT(API_ERR, "Not signed in");

	if ($meetingInfo['host_id']!=$memberId) {
			
		// check if the member is an admin of the brand
		if ($memberPerm!='ADMIN' || $memberBrand!=$meetingInfo['brand_id']) 
		{
			return API_EXIT(API_ERR, "Not authorized");
		}		
	}
}

$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $member->GetErrorMsg());

$brand=new VBrand($meetingInfo['brand_id']);
//$brand->GetValue('hook_id', $hookId);
if ($brand->Get($brandInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $brand->GetErrorMsg());

if ($brandInfo['status']=='INACTIVE')
	return API_EXIT(API_ERR, "The brand is not active.");

$hookId=$brandInfo['hook_id'];
$hookInfo=array();
if ($hookId>0) {
	$hook=new VHook($hookId);
	$hook->Get($hookInfo);
}

if (isset($hookInfo['start_meeting']) && $hookInfo['start_meeting']!='') {
	$args=array();
	$args['meeting_id']=$meetingInfo['access_id'];
	$args['member_id']=$memberInfo['access_id'];
	if ($hook->CallHook($hookInfo['start_meeting'], $args, $resp)) {
		$code='';
		if (isset($resp['code'])) {
			$code=$resp['code'];
		}
		
		if ($code=='400') {
			if (isset($resp['message']))
				$meetingErrMsg=$resp['message'];
			else
				$meetingErrMsg="API Hook 'start_meeting' refused the request.";
			return API_EXIT(API_ERR, $meetingErrMsg);
		}
	} else {
		return API_EXIT(API_ERR, "API Hook 'start_meeting' failed to respond.");
	}
}	

if ($meeting->StartMeeting()!=ERR_NONE)
	return API_EXIT(API_ERR, $meeting->GetErrorMsg());

if (isset($hookInfo['meeting_started']) && $hookInfo['meeting_started']!='') {
	$args=array();
	$args['member_id']=$memberInfo['access_id'];
	$args['meeting_id']=$meetingInfo['access_id'];
	$args['session_id']=$meetingInfo['session_id'];

	if ($hook->CallHook($hookInfo['meeting_started'], $args, $resp)) {
		
		$code='';
		if (isset($resp['code'])) {
			$code=$resp['code'];
		}		
		// ignore redirect if called from the REST API ($restApi is set)
		if (!isset($restApi) && $code=='300') {
			if (isset($resp['link']) && $resp['link']!='') {
				$redirectUrl=$resp['link'];
				header("Location: $redirectUrl");
				DoExit(); // need to exit here or the redirect won't work
				// API_EXIT(API_NOMSG);
			} else {
				$meetingErrMsg="API Hook 'meeting_started' did not return a redirect link.";
				return API_EXIT(API_ERR, $meetingErrMsg);
			}
		}		
	} else {
		return API_EXIT(API_ERR, "API Hook 'meeting_started' failed to respond.");
	}
}

?>