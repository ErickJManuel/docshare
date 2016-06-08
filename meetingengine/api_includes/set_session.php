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

if ($errMsg!='')
	return API_EXIT(API_ERR, $errMsg);
	

// Set the session record to so we know a live meeting is in progress by inspecting the database record alone.
// The moderator will call this once every 15 seconds during a live meeting session
// It is possible for a meeting to be in progress without the moderator being present
// In that case, the only way to know if someone is in the meeting is to call the vgetattendees.php
// of the meeting hosting server to get the attendee count. We want to avoid that because
// the meeting server may not be up anymore or may take a long time to respond.
if ($cmd=='SET_SESSION') {
	if (!isset($meetingInfo['access_id'])) {
		return API_EXIT(API_ERR, "meeting is not set or not found.");	
	}
	if (VMeeting::IsMeetingStarted($meetingInfo)) {
		$session=new VSession($meetingInfo['session_id']);
		$info=array();
		if (GetArg('attendee_count', $attCount)) {
			if ($session->GetValue('max_concur_att', $maxAtt)==ERR_NONE) {
				if ((integer)$attCount>(integer)$maxAtt)
					$info['max_concur_att']=$attCount;
			}
		}	
		$info['mod_time']='#NOW()';
		if ($session->Update($info)!=ERR_NONE) {
			return API_EXIT(API_ERR, $session->GetErrorMsg());
		}
	}
}

?>