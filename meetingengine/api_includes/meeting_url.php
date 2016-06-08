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
	API_EXIT(API_ERR, $errMsg);
if (!isset($meetingInfo['id']))
	API_EXIT(API_ERR, "Meeting id not set.");

$isHost=false;
if ($cmd="GET_HOSTURL") {

	if (!IsLogin())
		API_EXIT(API_ERR, "Nog signed in");
		
	$isHost=true;	
}
if ($meeting->GetViewerUrl($isHost, $meetingUrl)!=ERR_NONE)
	API_EXIT(API_ERR, $meeting->GetErrorMsg());
else
	echo $meetingUrl;

API_EXIT(API_NOMSG);

?>