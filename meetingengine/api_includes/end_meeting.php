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

//require_once('api_includes/meeting_common.php');
require_once('dbobjects/vhook.php');
require_once('dbobjects/vbrand.php');

if ($errMsg!='')
return API_EXIT(API_ERR, $errMsg);
	
if (!isset($meetingInfo['id']))
return API_EXIT(API_ERR, "Meeting id not set.");

// end only idle meetings (no current attendees)
GetArg('idle_only', $idleOnly);

// restart==1 if the meeting will be restarted immediately after it ends.
// don't call hooks in this case.
GetArg('restart', $restart);	

if ($cmd=='END_MEETING' && $idleOnly!='1' && $meetingInfo['status']=='START_REC') {
	return API_EXIT(API_ERR, "Recording is in progress. Stop the recording first.");
}

// the session may have expired when this is called.
// we want to still allow the meeting to end even if the session has expired.
// these values may be undefined
$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

$brandId=$meetingInfo['brand_id'];
$brand=new VBrand($brandId);
$brand->GetValue('hook_id', $hookId);
$hookInfo=array();
if ($hookId>0) {
	$hook=new VHook($hookId);
	$hook->Get($hookInfo);
}

if ($idleOnly=='1') {	
//	$meetingInfo=array();
//	$meeting->Get($meetingInfo);
	if (($meetingInfo['status']=='START')
		&& !VMeeting::IsMeetingInProgress($meetingInfo)) {
		if ($meeting->EndMeeting()!=ERR_NONE)
		return API_EXIT(API_ERR, $meeting->GetErrorMsg());		
	}
	
} else {
	
	// END_MEETING should be called only by the moderator. Participants will call LEAVE_MEETING
	if ($cmd=='END_MEETING') {
		if ($restart!='1' && isset($hookInfo['end_meeting']) && $hookInfo['end_meeting']!='') {
			if ($memberId!='')
				$hostId=$memberId;
			else {
				require_once('dbobjects/vuser.php');
				$host=new VUser($meetingInfo['host_id']);
				$host->Get('access_id', $hostId);
			}

			$args=array();
			$args['member_id']=$hostId;
			$args['meeting_id']=$meetingInfo['access_id'];
			$args['session_id']=$meetingInfo['session_id'];

			if ($hook->CallHook($hookInfo['end_meeting'], $args, $resp)) {
				
				$code='';
				if (isset($resp['code'])) {
					$code=$resp['code'];
				}
				if ($code!='200' && $code!='400')
					return API_EXIT(API_ERR, "API Hook 'end_meeting' returned an invalid code.");
				
				if ($code=='400') {
					if (isset($resp['message']))
						$meetingErrMsg=$resp['message'];
					else
						$meetingErrMsg="API Hook 'end_meeting' refused the request.";
					
					return API_EXIT(API_ERR, $meetingErrMsg);
				}
			} else {
				return API_EXIT(API_ERR, "API Hook 'end_meeting' failed to respond.");
			}			
		}
		
		if ($meeting->EndMeeting()!=ERR_NONE)
			return API_EXIT(API_ERR, $meeting->GetErrorMsg());
	}
		
	if ($restart!='1' && isset($hookInfo['meeting_ended']) && $hookInfo['meeting_ended']!='') {
		$args=array();
		$args['meeting_id']=$meetingInfo['access_id'];
		$args['session_id']=$meetingInfo['session_id'];
		
		if ($hook->CallHook($hookInfo['meeting_ended'], $args, $resp)) {
			
			$code='';
			if (isset($resp['code'])) {
				$code=$resp['code'];
			}
			
			if ($code!='200' && $code!='300')
				return API_EXIT(API_ERR, "API Hook 'meeting_ended' returned an invalid code.");
			
			// ignore redirect if called from the REST API ($restApi is set)
			if (!isset($restApi) && $code=='300') {
				if (isset($resp['link']) && $resp['link']!='') {
					$redirectUrl=$resp['link'];
					header("Location: $redirectUrl");
					API_EXIT(API_NOMSG);
				} else {
					$meetingErrMsg="API Hook 'meeting_ended' did not return a redirect link.";
					return API_EXIT(API_ERR, $meetingErrMsg);
				}
			}	

		} else {
			return API_EXIT(API_ERR, "API Hook 'meeting_ended' failed to respond.");
		}
	}	
}

?>