<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vmeeting.php");

// This class should work without accessing the database

/**
 * @package     PRestAPI
 * @access      public
 */
class PLiveEvents extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PLiveEvents()
	{
		$this->PRestAPI("live_events");
		$this->mBeginTag="<!--BEGIN_LIVEEVENTS-->";
		$this->mEndTag="<!--END_LIVEEVENTS-->";

		$this->mSynopsis="Live_events is a collection of live events in an in-progress meeting.";
		$this->mMethods="GET";
		$this->mRequired=array(
			"meeting_id" => "Meeting id. Returns live events of the meeting. The meeting must be in progress.",
			);
		$this->mOptional=array(
			"offset" => "Offset for the events requested (0 for the first event.)  If the value is negative, the offset is relative to the last event. If offset is '+1', it will return the next event when it is available (see 'wait_time'.) Default is 0.",
			"wait_time" => "Time in seconds to wait for the requested event to be available. The request will return when either the next event becomes available or when 'wait_time' is reached, whichever comes first. If 'wait_time' is not set or zero, the request will return immediately. The max. value is limited to 30.",
			"count" => "The number of events to return. If the field is missing, all events will be returned.",
		);
		$this->mReturned=array(
			'[OFFSET]' => "The offset relative to the beginning of the first event returned.",
			'[EVENT_ID]' => "A unique id for the event.",
			'[EVENT_TIME]' => "Time in seconds from Unix epoch when the event was created.",
			'[SENDER_ATTENDEE_ID]' => "The attendee_id of the sender of the event.",
			'[TARGET_ATTENDEE_ID]' => "The attendee_id of the receipient of the event. If the field is omitted, the event is sent to all attendees of the meeting.",
			'[SENDER_USERNANE]' => "The user name of the sender of the event.",
			'[SENDER_AGENT]' => "The name of the application sending the event. This is typically the browser's name.",
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
		global $current_tag, $itemIndex, $inItem, $eventList, $eventStart;
		
		$respXml=$this->LoadResponseXml();
		$subXml=$this->GetSubXml($this->mBeginTag, $this->mEndTag, $respXml);
		
		$start=NULL;
		$count=NULL;
		if (isset($_GET['offset']) && $_GET['offset']!='+1')
			$start=(integer)$_GET['offset'];
		if (isset($_GET['count'])) {
			$count=(integer)$_GET['count'];
		}
		$timeout=0;
		if (isset($_GET['wait_time'])) {
			$timeout=(integer)$_GET['wait_time'];
		}
		if ($timeout>30)
			$timeout=30;
		
		$errMsg=$this->VerifyInput();
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$meetingId=$_GET['meeting_id'];
		$meetingFile=VMeeting::GetSessionCachePath($meetingId);

		if (VMeeting::IsSessionCacheValid($meetingFile)) {
			@include_once($meetingFile);
			// do not check for meeting status because someone may be waiting to get the EndMeeting event
			/*
			if ($_meetingStatus!='START' && $_meetingStatus!='START_REC') {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Meeting is not in progress.");
				return '';
			}
			*/

			$meetingDirUrl=$_hostingServerUrl.$_meetingDir;
			if ($meetingId!=GetSessionValue('meeting_access_id') &&
				$_hostId!=GetSessionValue('member_id') &&
				(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'] )
				)
			{
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Access is not authorized.");
				return '';			
			}
			
			$evtDir=md5($_sessionId);	// should match VMeeting::GetEventDir()
		} else {
			
			$errMsg=VObject::Find(TB_MEETING, "access_id", $meetingId, $meetingInfo);
			if ($errMsg!='') {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage($errMsg);
				return '';
			}
			
			// do not check for meeting status because someone may be waiting to get the EndMeeting event
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
			if ($meetingInfo['access_id']!=GetSessionValue('meeting_access_id') &&
				$meetingInfo['host_id']!=GetSessionValue('member_id') &&
				(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
				)
			{
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Access is not authorized.");
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
			
			$meetingServerUrl=VMeeting::GetMeetingServerUrl($hostInfo, $meetingInfo);
			$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
			$meetingDirUrl=$meetingServerUrl.$meetingDir;

			$evtDir=VMeeting::GetEventDir($meetingInfo);			
		}
		$url=$meetingDirUrl."vscript.php?s=vgetevent";
		$url.="&evtdir=".$evtDir;
		if (!is_null($start)) {
			$url.="&e0=".$start;
			if ($count>0) {
				$last=$start+$count-1;
				if ($start<0 && $last>=0)
					$last=-1;
				$url.="&e1=".$last;
			}
		}
		if ($timeout>0)
			$url.="&timeout=".$timeout;
		
		$maxWaitTime=30;
		if ($timeout>0 && $timeout>15)
			$maxWaitTime=$timeout+15;
			
		if ($response = HTTP_Request($url, "", "GET", $maxWaitTime)) {
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($xml_parser, "start_event_tag", "end_event_tag");
			xml_set_character_data_handler($xml_parser, "parse_event_data");
			xml_parse($xml_parser, $response, true);
			xml_parser_free($xml_parser);
			
		} else {
			$this->SetErrorMessage("Couldn't get response from the hosting server.");
			return '';
		}

		$newXml='';
		for ($i=0; $i<=$itemIndex; $i++) {
			//print_r($eventList[$i]);
			$newXml.=$this->ReplaceObjectTags($eventList[$i], $subXml);
		}
				
		$respXml=str_replace("[OFFSET]", $eventStart, $respXml);
		$retXml=str_replace($subXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$xml=str_replace("[EVENT_ID]", GetArrayValue($objInfo, 'id'), $sourceXml);
		$username=htmlspecialchars(GetArrayValue($objInfo, 'fromname'));
		$xml=str_replace("[SENDER_USERNAME]", $username, $xml);
		$xml=str_replace("[SENDER_AGENT]", htmlspecialchars(GetArrayValue($objInfo, 'useragent')), $xml);
		$xml=str_replace("[EVENT_TIME]", GetArrayValue($objInfo, 'time'), $xml);
		$xml=str_replace("[SENDER_ATTENDEE_ID]", GetArrayValue($objInfo, 'from'), $xml);
		$xml=str_replace("[TARGET_ATTENDEE_ID]", GetArrayValue($objInfo, 'to'), $xml);
		$xml=str_replace("[EVENT_TYPE]", GetArrayValue($objInfo, 'type'), $xml);
		$xml=str_replace("[EVENT_DATA]", GetArrayValue($objInfo, 'event_data'), $xml);
		return $xml;
	}

}

$current_tag='';
$itemIndex=-1;
$eventList=array();
$inItem=false;
$eventStart=0;

function start_event_tag($parser, $name, $attribs) {
	global $current_tag, $itemIndex, $inItem, $eventList, $eventStart;
	$current_tag = $name;
	if ($name=='event') {
		$inItem=true;
		$eventList[]=array();
		$itemIndex=count($eventList)-1;
		foreach ($attribs as $key => $val) {
			$eventList[$itemIndex][$key]=$val;			
		}
		$eventList[$itemIndex]['event_data']='';
	} elseif ($name=='eventlist') {
		foreach ($attribs as $key => $val) {
			if ($key=='start') {
				$eventStart=(integer)$val;
				break;
			}
		}
	} elseif ($inItem) {
		$eventList[$itemIndex]['event_data'].="<$name";
		foreach ($attribs as $key => $val) {
			$eventList[$itemIndex]['event_data'].=" $key=\"$val\"";			
		}
		$eventList[$itemIndex]['event_data'].=">";

	}
}

function end_event_tag($parser, $name) {
	global $current_tag, $itemIndex, $inItem, $eventList;
	$current_tag = '';
	if ($name=='event') {
		$inItem=false;
	} elseif ($inItem) {
		$eventList[$itemIndex]['event_data'].="</$name>";		
	}
}
function parse_event_data($parser, $data) {
	global $current_tag, $itemIndex, $inItem, $eventList;
	
	if ($inItem) {
		$eventList[$itemIndex]['event_data'].=$data;
	}	
}

?>