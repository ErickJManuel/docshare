<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PAttendees extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PAttendees()
	{
		$this->PRestAPI("attendees");
		$this->mBeginTag="<!--BEGIN_ATTENDEES-->";
		$this->mEndTag="<!--END_ATTENDEES-->";
		$this->mTableName=TB_ATTENDEE;		

		$this->mSynopsis="Attendees is a collection of all users attended a past meeting session. Use 'live_attendees' to get attendees of a session currently in progress.";
		$this->mMethods="GET";
		$this->mRequired=array(
			);
		$this->mOptional=array(
			"session_id" => "Session id. Returns only attendees of the session.",
			"attendee_id" => "Return only the attendee that matches the attendee id.",
			"start_date" => "Return only the attendee that join a session on or after the date. Date should be given in YYYY-MM-DD (e.g. 2010-01-21)",
			"end_date" => "Return only the attendee that join a session on or before the date. Date should be given in  YYYY-MM-DD.",
			"start" => "Index for the starting attendee (0 for the first attendee.) Default is 0.",
			"count" => "The number of attendees to return. Default (and maximum) is ".$this->mMaxItems.".",
		);
		$this->mReturned=array(
			'[START]' => "The starting index for the records returned.",
			'[NEXT]' => "The starting index for requesting the next set of records. '-1' is returned if no more records are available.",
			'[START_TIME]' => "The time when the attendee starts the meeting.",
			'[END_TIME]' => "The last time when the attendee is the meeting.",
			'[BREAK_TIME]' => "The duration the attendee is away from the meeting between the start and the end time.",
			'[WEBCAM_TIME]' => "The attendee's total webcam usage time.",
		);

	}
	function VerifyInput()
	{
//		if (!isset($_GET['session_id']) || $_GET['session_id']=='')
//			return ("Missing input parameter session_id");
			
		if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
		{
			return("Access is not authorized.");		
		}
		return '';
	}
	function GetSelectQuery()
	{
		$sessionId=$_GET['session_id'];
		
// attendee_live table is no longer used		
//		if (VObject::InTable(TB_ATTENDEE, 'session_id', $sessionId))
			$this->mTableName=TB_ATTENDEE;		
//		else
//			$this->mTableName=TB_ATTENDEE_LIVE;		

		$query="brand_id='".$this->mBrandId."'";
		if (isset($_GET['session_id']) && $_GET['session_id']!='') {
			$query.=" AND (session_id='".$_GET['session_id']."')";
		}
		
//		$query="session_id='".$_GET['session_id']."'";
		if (isset($_GET['attendee_id']) && $_GET['attendee_id']!='') {
			$query.=" AND (attendee_id='".$_GET['attendee_id']."')";
		}
		
		if (isset($_GET['start_date']) && $_GET['start_date']!='') {
			$query.=" AND (start_time>='".$_GET['start_date']." 00:00:00')";
		}
		
		if (isset($_GET['end_date']) && $_GET['end_date']!='') {
			$query.=" AND (start_time<='".$_GET['end_date']." 23:59:59')";
		}
		return $query;
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$xml=str_replace("[ATTENDEE_ID]", GetArrayValue($objInfo, 'attendee_id'), $sourceXml);
		$xml=str_replace("[SESSION_ID]", GetArrayValue($objInfo, 'session_id'), $xml);
		if ($objInfo['user_id']=='0')
			$memberId='';
		else
			$memberId=$objInfo['user_id'];
		$xml=str_replace("[MEMBER_ID]", $memberId, $xml);
		$xml=str_replace("[USER_NAME]", htmlspecialchars(GetArrayValue($objInfo, 'user_name')), $xml);
		$xml=str_replace("[START_TIME]", GetArrayValue($objInfo, 'start_time'), $xml);
		$xml=str_replace("[END_TIME]", GetArrayValue($objInfo, 'mod_time'), $xml);
		$xml=str_replace("[BREAK_TIME]", GetArrayValue($objInfo, 'break_time'), $xml);
		$xml=str_replace("[WEBCAM_TIME]", GetArrayValue($objInfo, 'cam_time'), $xml);
		$xml=str_replace("[USER_IP]", htmlspecialchars(GetArrayValue($objInfo, 'user_ip')), $xml);
		return $xml;
	}

}


?>