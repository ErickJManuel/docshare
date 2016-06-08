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

require_once('api_includes/meeting_common.php');
require_once('dbobjects/vhook.php');
require_once('dbobjects/vbrand.php');
//require_once('includes/brand.php');

//require_once('api_includes/user_common.php');

//if ($userErrMsg!='')
//	return API_EXIT(API_ERR, $userErrMsg." 1");

if ($errMsg!='')
	return API_EXIT(API_ERR, $errMsg);
	
if (!isset($meetingInfo['id']))
	return API_EXIT(API_ERR, "Meeting id not set");
	
$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in");

$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, "Member not found.");


if ($meetingInfo['host_id']!=$memberId) {
		
	// check if the member is an admin of the brand
	if ($memberPerm!='ADMIN' || $memberBrand!=$meetingInfo['brand_id']) 
	{
		return API_EXIT(API_ERR, "Not authorized");
	}	
	
}

$brand=new VBrand($memberBrand);
$brand->GetValue('hook_id', $hookId);
$hookInfo=array();
if ($hookId>0) {
	$hook=new VHook($hookId);
	$hook->Get($hookInfo);
}

if (isset($hookInfo['delete_meeting']) && $hookInfo['delete_meeting']!='') {
	$args=array();
	$args['member_id']=$memberInfo['access_id'];
	$args['meeting_id']=$meetingInfo['access_id'];
	if ($hook->CallHook($hookInfo['delete_meeting'], $args, $resp)) {
		$code='';
		if (isset($resp['code'])) {
			$code=$resp['code'];
		}
		
		if ($code=='400') {
			if (isset($resp['message']))
				$meetingErrMsg.=$resp['message'];
			else
				$meetingErrMsg="API Hook 'delete_meeting' refused the request.";
									
			return API_EXIT(API_ERR, $meetingErrMsg);
		}
	} else {
		return API_EXIT(API_ERR, "API Hook 'delete_meeting' failed to respond.");
	}
}

if ($meeting->DeleteMeeting()!=ERR_NONE) {
	return API_EXIT(API_ERR, "DeleteMeeting: ".$meeting->GetErrorMsg());
}

if (isset($hookInfo['meeting_deleted']) && $hookInfo['meeting_deleted']!='') {
	$args=array();
	$args['member_id']=$memberInfo['access_id'];
	$args['meeting_id']=$meetingInfo['access_id'];
	if ($hook->CallHook($hookInfo['meeting_deleted'], $args, $resp)) {
	} else {
		return API_EXIT(API_ERR, "API Hook 'meeting_deleted' failed to respond.");
	}
}	

?>