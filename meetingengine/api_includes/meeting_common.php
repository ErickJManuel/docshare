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

require_once("dbobjects/vmeeting.php");

$meetingInfo=array();
$errMsg='';
if ((GetArg('id', $arg)) && $arg!='') {
		
	$id=$arg;
	$meeting=new VMeeting($id);
	if ($meeting->Get($meetingInfo)!=ERR_NONE)
		$errMsg=$meeting->GetErrorMsg();
		
	elseif (!isset($meetingInfo['id']))
		$errMsg="Meeting not found. id=".$arg;
	
} else if  ((GetArg('meeting', $arg) || GetArg('meeting_id', $arg)) && $arg!='') {
	$meetingId=$arg;
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
	if ($errMsg=='') {
		if (isset($meetingInfo['id']))
			$meeting=new VMeeting($meetingInfo['id']);	
		else
			$errMsg="Meeting not found. meeting_id=".$arg;
	}
}

?>