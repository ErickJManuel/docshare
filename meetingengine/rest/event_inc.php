<?php
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");


function SendEvent($meetingId, $senderId, $targetId, $senderName, $eventType, $eventData, &$response)
{
	// these are the events where the session can start playing from
	$keyEvents=array(
	"SendSlide","EndPresentation",
	"StartWhiteboard","EndWhiteboard","AddWhiteboard","DeleteWhiteboard",
	"StartScreenSharing","EndScreenSharing"
	);
			
	$meetingFile=VMeeting::GetSessionCachePath($meetingId);

	if (VMeeting::IsSessionCacheValid($meetingFile)) {
		@include_once($meetingFile);
		if ($_meetingStatus=='REC'|| $_meetingStatus=='STOP') {
			$response="Meeting is not in progress.";
			return false;
		}				

		$meetingDirUrl=$_hostingServerUrl.$_meetingDir;
		$sessionId=$_sessionId;
		
	} else {
		
		// access the database only if the cache is not valid
		$meetingInfo=array();
/*		$meeting=new VMeeting($meetingId);
		if ($meeting->Get($meetingInfo)!=ERR_NONE) {
			$response=$meeting->GetErrorMsg();
			return false;
		}
*/
		$response=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
		if ($response!='') {		
			return false;
		}
		if (!isset($meetingInfo['id'])) {
			$response="Meeting with the given id is not found.";
			return false;
		}
		
		if (!VMeeting::IsMeetingStarted($meetingInfo)) {
			$response="Meeting is not in progress.";
			return false;
		}
		
		if ($meetingInfo['session_id']=="0") {
			$response="Meeting session is not found.";
			return false;
		}
		
		$sessionId=$meetingInfo['session_id'];
		
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$response=$host->GetErrorMsg();
			return false;
		}
		
		if (!isset($hostInfo['id'])) {
			$response="Meeting host user not found.";
			return false;
		}
		
		$meetingServerUrl=VMeeting::GetMeetingServerUrl($hostInfo, $meetingInfo);
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$meetingDirUrl=$meetingServerUrl.$meetingDir;
		
	}
			
	$sessionDir=md5($sessionId);
	$url=$meetingDirUrl."vscript.php?s=vpostevent";
	$url.="&evtdir=".$sessionDir;
	$url.="&type=".$eventType;
	$url.="&meetingId=".$meetingId;
	if ($senderId!='')
		$url.="&from=".$senderId;
	if ($targetId!='')
		$url.="&to=".$targetId;
	if ($senderName!='')
		$url.="&fromName=".rawurlencode($senderName);	
	if (in_array($eventType, $keyEvents))
		$url.="&isKey=1";

	$response = HTTP_Request($url, $eventData, "POST", 30);

	if ($response) {
		return true;		
	} else {
		$response="Couldn't get a response";
		return false;			
	}
}

?>