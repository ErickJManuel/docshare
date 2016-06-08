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

require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vsession.php");

//$memberId=GetSessionValue('member_id');

GetSessionTimeZone($tzName, $tz);

$userPage=$GLOBALS['BRAND_URL']."?".SID;
if (SID!='')
	$userPage.="&".SID;
	
GetArg("session_id", $sessionId);
if ($sessionId=='') {
	ShowError("Missing session_id");
	return;
}

$session=new VSession($sessionId);
$sessInfo=array();
if ($session->Get($sessInfo)!=ERR_NONE) {
	ShowError($session->GetErrorMsg());
	return;
}
if (!isset($sessInfo['id'])) {
	ShowError("Couldn't find session for id ".$sessionId);
	return;
}

$memberLogin=GetSessionValue('member_login');

// only show the session info if I am the host of the session or admin of the site
if ($sessInfo['host_login']!=$memberLogin && !(GetSessionValue('member_perm')=='ADMIN' && GetSessionValue('member_brand')==$sessInfo['brand_id'])) {
	ShowError("You are not a host of the meeting.");
	return;	
}

//$meetingInfo=array();
//$errMsg=VObject::Find(TB_MEETING, 'access_id', $sessInfo['meeting_aid'], $meetingInfo);

$meetingTitle=htmlspecialchars($sessInfo['meeting_title']);

$startTime=$sessInfo['start_time'];
VObject::ConvertTZ($startTime, 'SYSTEM', $tz, $tzTime);	

?>

<div class=heading1><?php echo _Text("Transcripts")?></div>

<div><?php echo $gText['M_SESSION'].": ". $sessInfo['id']." "._Text("Meeting ID")." ".$sessInfo['meeting_aid']." <b>'".
$meetingTitle."'</b><br>"._Text("Hosted by").": <b>".$sessInfo['host_login']."</b> "._Text("Time").": <b>".$tzTime." ".$tzName."</b>";?>
</div>

<?php

echo VSession::XmlToHtmlTranscript($sessInfo['transcripts']);

?>
