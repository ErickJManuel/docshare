<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("event_inc.php");

// This class should work without accessing the database

/**
 * @package     PRestAPI
 * @access      public
 */
class PLiveEvent extends PRestAPI 
{
	var $m_hostEvents=array(
		"LockMeeting" => "Lock the meeting and not allow more attendees to join.",
		"UnlockMeeting" => "Un-lock the meeting.",
		"RedirectPage" => "Redirect all participants of the meeting to another page immediately.",
		"AllowPresenting" => "Grant presenter permissions to the attendee specified in 'target_id'.",
		"DenyPresenting" => "Remove presenter permissions from the attendee specified in 'target_id'.",
		"AllowDrawing" => "Grant drawing permissions to the attendee specified in 'target_id'.",
		"DenyDrawing" => "Remove drawing permissions from the attendee specified in 'target_id'.",
		"SendAlert" => "Send an alert message. An alert window will show up.",
		);
	var $m_presenterEvents=array(
		"SendSlide" => "Send a slide or a picture and share it in the meeting viewer. The 'slideurl' field in 'event_data' should point to eitehr a swf or a jpeg file. The slide or picture should have been uploaded to the library. Use the 'libraries' API object to obtain a list of slides in the user's library or the site's public library. The 'presentation_id' field in 'event_data' should be the 'content_id' field from in the 'libraries' object XML response data. The 'slideurl' filed in 'event_data' should be the 'content_url' from 'libraries' XML data.",
		"EndPresentation" => "End a slide show presentation.",
		"StartMedia" => "Start a streaming video or mp3 audio in the library.",
		"StartWhiteboard" => "Start a whiteboard. The 'index' filed in event_data indicates which whiteboard to start. The index goes from 0 to n-1, where n is the number of whiteboards.",
		"EndWhiteboard" => "End the whiteboard session.",
		"AddWhiteboard" => "Add a new whiteboard.",
		"DeleteWhiteboard" => "Delete a whiteboard. The 'index' field in event_data indicates which whiteboard to delete. The index goes from 0 to n-1, where n is the number of whiteboards.",
		"SetView" => "Set the zoom scale and full screen mode of the displayed area. The 'scale' field in 'event_data' is a floating number with 1.0 means 100%. If 'scale' is 0, the display will be 'auto-sized' to fit the window. If 'fullscreen' is 'true', the window will be displayed in the full screen mode (Flash 9 required.)",
		);
	var $m_attendeeEvents=array(
		"AddAttendee" => "Add a web or audio conference participant (or both) to the meeting. The 'attendeeinfo' XML tag in the 'event_data' is for a web attendee. The 'callerinfo' XML tag in the 'event_data' is for an audio conference participant.<p>The event is used to immediately trigger the display of the participant in the meeting viewer. A web attendee must send heartbeats (see 'live_attendees') subsequently or it will disappear from the attendee list eventually.<p>If 'sender_id' is not provided, this event will add a new attendee and the 'sender_attendee_id' field in the xml response contains the attendee_id of the new attendee. If 'sender_id' is provided and matches an existing attendee's id, the event will not create a new attendee and is used to resume a meeting..<p>'CallId' in 'callerinfo' XML data should be a unique conference call identifier from the conference bridge. 'ParticipantNumber' is the phone number of the caller. 'active_talker' and 'muted' values are 'true' or 'false'.",
		"SetAttendee" => "If 'sender_id' is provided, the event will set the phone number and call status for the attendee identified in 'sender_id'. If 'sender_id' is not provided, the event will set the call status of the audio conference participant identified in the 'CallId' field of the 'event_data'. The phone call identified in 'CallId' must be in progress. You can include multiple '<callinfo></callerinfo>' in 'event_data' to set the call status of multiple callers in one POST.",
		"RemoveAttendee" => "Remove the attendee identified in 'sender_id' from the web conference immediately. An attendee will be removed automatically if the attendee does not send a heartbeat. However, it can take from 15 to 30 seconds for the status to change. Use the event to trigger an immediate update.",
		"RefreshAttendees" => "Cause the attendee list to be immediately refreshed in the meeting viewer. This is useful if some attendee status has changed and you want the change to be displayed immediately.",
		"SendMessage" => "Send a text message from 'sender_id' to 'target_id'. If 'target_id' is not provided, the message will be sent to all attendees.",
		"SetEmoticon" => "Set the emoticon for the attendee defined in 'sender_id'. Valid emoticon name values are 'NoEmoticon', 'RaiseHand', 'Applaud', 'Laugh', 'Smile', 'LoveIt', 'Sad'.",
		);
	var $m_eventData=array(
		"RedirectPage" => "<page>[PAGE_URL]</page>",
		"SendMessage" => "<message>[MESSAGE]</message>",
		"SendSlide" => "<slide slideurl='[SLIDE_URL]'\npresentationid='[CONTENT_ID]' title='[TITLE]'/>",
		"StartMedia" => "<media title='[TITLE]' url='[URL]'/>",
		"StartWhiteboard" => "<whiteboard index='[INDEX]'/>",
		"DeleteWhiteboard" => "<whiteboard index='[INDEX]'/>",
		"SetView" => "<view scale='[SCALE]' fullscreen='[FULLSCREEN]'/>",
		"AddAttendee" => "<attendeeinfo fullname='[FULL_NAME]'/>\n<callerinfo CallId='[CALL_ID]' ParticipantNumber='[CALL_NUMBER]'\nActiveTalker='[ACTIVE_TALKER]' Muted='[MUTED]'/>",
		"SetAttendee" => "<callerinfo CallId='[CALL_ID]' ParticipantNumber='[NUMBER]'\nActiveTalker='[ACTIVE_TALKER]' Muted='[MUTED]'/>",
		"SetEmoticon" => "<emoticon name='[EMOTICON_NAME]'/>",
		"SendAlert" => "<alert title='[WINDOW_TITLE]'/>[ALERT_MESSAGE]</alert>",
		);		
		
			
	/**
	 * Constructor
	 */	
	function PLiveEvent()
	{

		$typeText="<h4>event_type</h4>\n";
		$typeText.="The following events require 'moderator' permissions:\n<ul>\n";
		foreach ($this->m_hostEvents as $key => $val) {
			if (isset($this->m_eventData[$key])) {
				$data=$this->m_eventData[$key];
				$typeText.="<li><b>$key</b>: $val<pre>event_data: \"".htmlspecialchars($data)."\"</pre></li>\n";
			} else {
				$typeText.="<li><b>$key</b>: $val</li>\n";
			}
		}
		$typeText.="</ul>\n";		

		$typeText.="The following events require 'moderator' or 'presenter' permissions:\n<ul>\n";
		foreach ($this->m_presenterEvents as $key => $val) {
			if (isset($this->m_eventData[$key])) {
				$data=$this->m_eventData[$key];
				$typeText.="<li><b>$key</b>: $val<pre>event_data: \"".htmlspecialchars($data)."\"</pre></li>\n";
			} else {
				$typeText.="<li><b>$key</b>: $val</li>\n";
			}
		}
		$typeText.="</ul>\n";		
		$typeText.="The following events are for all attendees:\n<ul>\n";
		foreach ($this->m_attendeeEvents as $key => $val) {
			if (isset($this->m_eventData[$key])) {
				$data=$this->m_eventData[$key];
				$typeText.="<li><b>$key</b>: $val<pre>event_data: \"".htmlspecialchars($data)."\"</pre></li>\n";
			} else {
				$typeText.="<li><b>$key</b>: $val</li>\n";
			}
		}
		$typeText.="</ul>\n";	
						
		$this->PRestAPI("live_event");
		$this->mSynopsis="A live_event is used to control live meeting activities.";
		$this->mMethods="POST";
		$this->mRequired=array(
			"meeting_id" => "Meeting id. The meeting must be in progress.",
			"event_type" => "Event type. Allowable types depend on the sender's permssions in the meeting. See 'Additional information'.",
			);
		$this->mOptional=array(
			"sender_id" => "Sender attendee id.",
			"sender_name" => "Sender user name.",
			"target_id" => "Target attendee id. If the parameter is not provided, the event will be sent to all attendees.",
			"event_data" => "Event XML data specific to the event type. Only some event types have event_data. See 'Additional information'.",
			);
		$this->mReturned=array(
			'[EVENT_ID]' => "A unique id for the event.",
			'[EVENT_TIME]' => "Time in seconds from Unix epoch when the event was created.",
			'[SENDER_ATTENDEE_ID]' => "The attendee_id of the sender of the event.",
			'[TARGET_ATTENDEE_ID]' => "The attendee_id of the receipient of the event. If the field is omitted, the event is sent to all attendees of the meeting.",
			'[SENDER_USERNANE]' => "The user name of the sender of the event.",
		);
		$this->mAddtional=htmlspecialchars($typeText);
		
	}
	
	function Insert()
	{
		$respXml=$this->LoadResponseXml();
		$meetingId='';
		$this->GetPostArg('meeting_id', $meetingId);
		$eventType='';
		$this->GetPostArg('event_type', $eventType);

		if ($meetingId=='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'meeting_id'");
			return '';
		}
		if ($eventType=='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'event_type'");
			return '';
		}
		
		if ($meetingId!=GetSessionValue('meeting_access_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}		

		$senderId='';
		$this->GetPostArg('sender_id', $senderId);
		
		$senderName='';
		$this->GetPostArg('sender_name', $senderName);

		$targetId='';
		$this->GetPostArg('target_id', $targetId);
			
		$eventData='';
		$this->GetPostArg('event_data', $eventData);
			
		$ret=SendEvent($meetingId, $senderId, $targetId, $senderName, $eventType, $eventData, $response);
		if (!$ret) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($response);
			return '';
		}
		
		// AddAttendee may return <status failed="some message"/> 
		if (($pos1=strpos($response, "failed=\""))!==false) {
			$pos1+=strlen("failed=\"");
			$substr=substr($response, $pos1);
			$pos2=strpos($substr, "\"");
			$message=substr($substr, 0, $pos2);
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($message);
			return '';
		}
		
		$items=explode("\n", $response);
		$eventOffset=$eventId='';
		$itemCount=count($items);
		if ($itemCount>1 && $items[0]=='OK') {
			for ($i=1; $i<$itemCount; $i++) {
				list($key, $val)=explode("=", $items[$i]);
				if ($key=="event_index")
					$eventOffset=$val;
				elseif ($key=="event_id")
					$eventId=$val;
				elseif ($key=="sender_id")
					$senderId=$val;
				elseif ($key=="target_id")
					$targetId=$val;
			}
		} else {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($response);
			return '';			
		}


/*		
		$meetingFile=VMeeting::GetSessionCachePath($meetingId);

		if (VMeeting::IsSessionCacheValid($meetingFile)) {
			@include_once($meetingFile);
			if ($_meetingStatus!='START' && $_meetingStatus!='START_REC') {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting is not in progress.");
				return '';
			}				

			$meetingDirUrl=$_hostingServerUrl.$_meetingDir;
			$sessionId=$_sessionId;
			
		} else {
			
			// access the database only if the cache is not valid
			$meeting=new VMeeting($meetingId);
			$meetingInfo=array();
			if ($meeting->Get($meetingInfo)!=ERR_NONE) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage($meeting->GetErrorMsg());
				return '';
			}
			
			if (!VMeeting::IsMeetingStarted($meetingInfo)) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting is not in progress.");
				return '';
			}
			
			if ($meetingInfo['session_id']=="0") {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting session is not found.");
				return '';
			}
			
			$sessionId=$meetingInfo['session_id'];
			
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
			
		$response = HTTP_Request($url, $eventData, "POST", 30);
		if ($response) {
			$items=explode("\n", $response, 3);
			if (count($items)>=3 && $items[0]=='OK') {
				$eventId=$items[2];				
			} else {
				$this->SetStatusCode(PCODE_ERROR);
				//$this->SetErrorMessage("Invalid response returned from ".$url);
				$this->SetErrorMessage($response);
				return '';			
			}
			
		} else {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Count't get response from ".$url);
			return '';			
		}
*/		
		$xml=str_replace("[EVENT_ID]", $eventId, $respXml);
		$username=htmlspecialchars($senderName);
		$xml=str_replace("[SENDER_USERNAME]", $username, $xml);
		$xml=str_replace("[SENDER_ATTENDEE_ID]", $senderId, $xml);
		$xml=str_replace("[TARGET_ATTENDEE_ID]", $targetId, $xml);
		$xml=str_replace("[EVENT_TYPE]", $eventType, $xml);
		$xml=str_replace("[EVENT_DATA]", htmlspecialchars($eventData), $xml);
		$xml=str_replace("[OFFSET]", $eventOffset, $xml);

		$this->SetStatusCode(PCODE_OK);
		return $xml;
	}

}


?>