<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("dbobjects/vsession.php");
require_once("dbobjects/vattendee.php");
require_once("dbobjects/vmeeting.php");

// Create a replay session in the session table if it doesn't exists
// Create a replay user in the attendee table if it doesn't exists
// Update the replay user mod_time if the the user already exists

GetArg("meeting_id", $meetingId);
if ($meetingId=='') 
	API_EXIT(API_ERR, "meeting_id not set");
	
// name of the playback user
GetArg("player_name", $playerName);
GetArg("player_id", $playerId);

$meetingInfo=array();
$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
if ($errMsg!='')
	API_EXIT(API_ERR, $errMsg);	

if (!isset($meetingInfo['id']))
	API_EXIT(API_ERR, "Meeting with given id is not found.");
		
$meeting=new VMeeting($meetingInfo['id']);	

$sessionInfo=array();
$errMsg=VObject::Find(TB_SESSION, 'meeting_aid', $meetingId, $sessionInfo);
if ($errMsg!='')
	API_EXIT(API_ERR, $errMsg);	

// if the replay session doesn't exist, create it
if (!isset($sessionInfo['id'])) {
	$session=new VSession();
	$sessionInfo['brand_id']=$meetingInfo['brand_id'];
	$sessionInfo['meeting_aid']=$meetingInfo['access_id'];
	$sessionInfo['meeting_title']=$meetingInfo['title'];
	$sessionInfo['client_data']=$meetingInfo['client_data'];
	$sessionInfo['start_time']='#NOW()';
	$sessionInfo['mod_time']='#NOW()';
	if ($session->Insert($sessionInfo)!=ERR_NONE) {
		API_EXIT(API_ERR, $session->GetErrorMsg());	
	}
	$session->Get($sessionInfo);
} else {		
	$session=new VSession($sessionInfo['id']);	
}

$userIp='';
if (isset($_SERVER['REMOTE_ADDR']))
	$userIp=$_SERVER['REMOTE_ADDR'];

// find if the replay user is already in the DB
$playerInfo=array();
$sessionId=$sessionInfo['id'];
$brandId=$meetingInfo['brand_id'];
if ($playerId!='') {
	
	$query="attendee_id = '$playerId' AND session_id = '$sessionId'";
//	if ($userIp!='')
//		$query.=" AND user_ip = '$userIp'";
	
	$errMsg=VObject::Select(TB_ATTENDEE, $query, $playerInfo);
	if ($errMsg!='') {
		return API_EXIT(API_ERR, $errMsg);
	}
}

// the replay user does not exist; create it.
if (!isset($playerInfo['id'])) {

	$player=new VAttendee();
	$playerInfo['user_name']=$playerName;
	$playerInfo['attendee_id']=$playerId;
	$playerInfo['session_id']=$sessionId;
	$playerInfo['start_time']='#NOW()';
	$playerInfo['mod_time']='#NOW()';
	$playerInfo['user_ip']=$userIp;
	$playerInfo['brand_id']=$brandId;

	if ($player->Insert($playerInfo)!=ERR_NONE) {
		API_EXIT($player->GetErrorMsg());
	}

} else {
	
	// the replay user already exists; update the user's mod_time
	$player=new VAttendee($playerInfo['id']);
	$info=array();
	$info['mod_time']='#NOW()';
	if ($player->Update($info)!=ERR_NONE) {
		API_EXIT($player->GetErrorMsg());
	}
}
		
?>