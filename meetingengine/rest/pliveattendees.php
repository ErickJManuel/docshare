<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vsession.php");

// This class should work without accessing the database

/**
 * @package     PRestAPI
 * @access      public
 */
class PLiveAttendees extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PLiveAttendees()
	{
		$this->PRestAPI("live_attendees");
		$this->mBeginTag="<!--BEGIN_LIVEATTENDEES-->";
		$this->mEndTag="<!--END_LIVEATTENDEES-->";

		$this->mSynopsis="Live_attendees is a collection of all attendees, including teleconference participants, currently attending a live meeting.";
		$this->mMethods="GET";
		$this->mRequired=array(
			"meeting_id" => "Meeting id. Returns all live attendees of the meeting. The meeting must be in progress.",
			);
		$this->mOptional=array(
			"attendee_id" => "Attendee id of the user making the request. This is useful to send a 'heartbeat' to indicate the attendee is still in the meeting. The heartbeat should be sent at least once every 15 seconds.",
		);
		$this->mReturned=array(
			"attendee_id" => "Web attendee id. If the field is empty, this is an audio-only participant.",
			"member_id" => "Member account id (for signed-in members only.) This is the same as 'attendee_id' for a moderator.",
			"user_name" => "The name the participant types in to join the meeting.",
			"permission" => "Attendee permission. Valid values are 'Moderator', 'Presenter', 'Annotator', or empty (if no permission is given.)",
			"emoticon" => "The name of the emoticon currently set by the participant.",
			"show_webcam" => "Webcam started status (true or false.)",
			"call_id" => "Teleconference call id. If the field is empty, this is a web-only participant.",
			"caller_number" => "Teleconference caller number.",
			"call_muted" => "Call muted status (true or false.)",
			"active_talker" => "Active talker status (true or false.)",
		);

	}
	function VerifyInput()
	{
		if (!isset($_GET['meeting_id']) || $_GET['meeting_id']=='')
			return ("Missing input parameter meeting_id");
		return '';
	}
	function Get()
	{
		$respXml=$this->LoadResponseXml();
		$subXml=$this->GetSubXml($this->mBeginTag, $this->mEndTag, $respXml);
				
		$errMsg=$this->VerifyInput();
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$this->GetArg('meeting_id', $meetingId);
		
		$attendeeId='';
		$this->GetArg('attendee_id', $attendeeId);

		$meetingFile=VMeeting::GetSessionCachePath($meetingId);
		$phoneNumber='';
		$modCode='';

		if (VMeeting::IsSessionCacheValid($meetingFile)) {
			@include_once($meetingFile);
			// disable meeting status checking
			/*
			if ($_meetingStatus=='REC'|| $_meetingStatus!='STOP') {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting is not in progress.");
				return '';
			}
			*/			

			$meetingDirUrl=$_hostingServerUrl.$_meetingDir;
			$sessionId=$_sessionId;
			
			if (isset($_teleServerUrl) && $_teleServerUrl!='') {
				$phoneNumber=$_teleNum;
				$modCode=$_teleMCode;
			}
			
			if ($meetingId!=GetSessionValue('meeting_access_id') &&
				$_hostId!=GetSessionValue('member_id') &&
				(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_GET['brand'])
				)
			{
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Access is not authorized.");
				return '';			
			}
		} else {
			
			$errMsg=VObject::Find(TB_MEETING, "access_id", $meetingId, $meetingInfo);
			if ($errMsg!='') {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage($errMsg);
				return '';
			}
			/*
			if (!VMeeting::IsMeetingStarted($meetingInfo)) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting is not in progress.");
				return '';
			}
			*/
			if ($meetingInfo['session_id']=="0") {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting session is not found.");
				return '';
			}
			
			$host=new VUser($meetingInfo['host_id']);
			$hostInfo=array();
			if ($host->Get($hostInfo)!=ERR_NONE) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage($host->GetErrorMsg());
				return '';
			}
			
			if (!isset($hostInfo['id'])) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting host user not found.");
				return '';
			}
			
			if ($meetingInfo['access_id']!=GetSessionValue('meeting_access_id') &&
				$meetingInfo['host_id']!=GetSessionValue('member_id') &&
				(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
				)
			{
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Access is not authorized.");
				return '';			
			}
			
			$meetingServerUrl=VMeeting::GetMeetingServerUrl($hostInfo, $meetingInfo);
			$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
			$meetingDirUrl=$meetingServerUrl.$meetingDir;
			
			$sessionId=$meetingInfo['session_id'];
			
			$teleServerUrl='';
			$group=new VGroup($hostInfo['group_id']);
			$group->GetValue('teleserver_id', $teleServerId);
			if ($teleServerId && $teleServerId!='0') {
				$teleServer=new VTeleServer($teleServerId);
				$teleServer->GetValue('server_url', $teleServerUrl);
			}
			if ($teleServerUrl!='') {
				$phoneNumber=$meetingInfo['tele_num'];
				$modCode=$meetingInfo['tele_mcode'];
			}
		}
				
		// get live attendees from the hosting server
		$ret=VSession::GetAttendees($meetingDirUrl,
				$sessionId, $attendeeId, false, false, $meetingId, $phoneNumber, $modCode, $attCount, $attList);
				
		if (!$ret) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Couldn't get response from the hosting server.");
			return '';
		}

		$newXml='';
		for ($i=0; $i<$attCount; $i++) {
			//print_r($attList[$i]);
			$newXml.=$this->ReplaceObjectTags($attList[$i], $subXml);
		}
				
		$retXml=str_replace($subXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$xml=str_replace("[ATTENDEE_ID]", GetArrayValue($objInfo, 'userid'), $sourceXml);
		if (!isset($objInfo['user_id']) || $objInfo['user_id']=='0')
			$memberId='';
		else
			$memberId=$objInfo['user_id'];
			
		$username=htmlspecialchars(GetArrayValue($objInfo, 'username'));
		$xml=str_replace("[MEMBER_ID]", $memberId, $xml);
		$xml=str_replace("[USER_NAME]", htmlspecialchars($username), $xml);
		$xml=str_replace("[START_TIME]", GetArrayValue($objInfo, 'startTime'), $xml);
		
		$perm='';
		if (GetArrayValue($objInfo, 'isHost')=='true')
			$perm='Moderator';
		else if (GetArrayValue($objInfo, 'isPresenter')=='true')
			$perm='Presenter';
		else if (GetArrayValue($objInfo, 'drawing')=='true')
			$perm='Annotator';
		
		$xml=str_replace("[PERMISSION]", $perm, $xml);
		$xml=str_replace("[EMOTICON]", htmlspecialchars(GetArrayValue($objInfo, 'emoticon')), $xml);
		$xml=str_replace("[SHOW_WEBCAM]", htmlspecialchars(GetArrayValue($objInfo, 'webcam')), $xml);
		$xml=str_replace("[CALL_ID]", htmlspecialchars(GetArrayValue($objInfo, 'callId')), $xml);
		$xml=str_replace("[CALLER_NUMBER]", htmlspecialchars(GetArrayValue($objInfo, 'callerId')), $xml);
		$xml=str_replace("[CALL_MUTED]", htmlspecialchars(GetArrayValue($objInfo, 'callMuted')), $xml);
		$xml=str_replace("[ACTIVE_TALKER]", htmlspecialchars(GetArrayValue($objInfo, 'activeTalker')), $xml);
		return $xml;
	}

}


?>