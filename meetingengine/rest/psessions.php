<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vsession.php");
require_once("dbobjects/vuser.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PSessions extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PSessions()
	{
		$this->PRestAPI("sessions");
		$this->mBeginTag="<!--BEGIN_SESSIONS-->";
		$this->mEndTag="<!--END_SESSIONS-->";
		$this->mTableName=TB_SESSION;		
		$this->mSynopsis=
"Sessions is a collection of all meeting sessions of the site. A session is an instance of a meeting and is created everytime a meeting is started. A meeting can only have one live session at a time.";
		$this->mMethods="GET";
		$this->mRequired=array();
		$this->mOptional=array(
			"session_id" => "Return only the session that matches the session id.",
			"start" => "Index for the starting session (0 for the first session.) Default is 0.",
			"count" => "The number of sessions to return. Default (and maximum) is ".$this->mMaxItems.".",
			"host_login" => "Login name of a host. Returns only sessions hosted by the host.",
			"meeting_id" => "Meeting id. Returns only sessions of the meeting.",
			"member_id" => "Member id. Returns only sessions hosted by the member.",
			"from_date" => "'YYYY-MM-DD' or 'YYYY-MM-DD hh:mm:ss'. Returns only sessions started on or after from_date. If 'hh:mm:ss' is not provided, '00:00:00' is used. All time information is in the UTC time zone.",
			"to_date" => "'YYYY-MM-DD' or 'YYYY-MM-DD hh:mm:ss'. Returns only sessions started on or before to_date. If 'hh:mm:ss' is not provided, '23:59:59' is used.",
			'in_progress' => "Y or N. Returns only in-progress sessions (Y) or not in-progress sessions (N).",
			'client_data' => "Meeting client_data. Returns only sessions that match the client data.",
		);
		$this->mReturned=array(
			'[START]' => "The starting index for the records returned.",
			'[NEXT]' => "The starting index for requesting the next set of records. '-1' is returned if no more records are available.",
			"[IN_PROGRESS]" => "Y or N.",
			"[HOST_LOGIN]" => "Login name of the meeting host.",
			"[MEETING_ID]" => "Meeting id of the originating meeting.",
			"[MEETING_TITLE]" => "Meeting title of the originating meeting.",
			"[START_TIME]" => "The session start time in YYYY-MM-DD hh:mm:ss. All time information is in the UTC time zone.",
			"[END_TIME]" => "The session end time. If the session is still in progress, END_TIME is the time when the session time is last updated.",
//			"[MAX_CONCURRENT]" => "Max. number of concurrent participants reached during the session.",
			"[ACCOUNT_TYPE]" => "The account type of the meeting host when the session is started.",
			"[CLIENT_DATA]" => "Meeting client data.",
		);
	}
	function VerifyInput()
	{
		$userId='';
		if (isset($_GET['member_id']))
			$userId=$_GET['member_id'];
				
		if ($userId!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			return("Access is not authorized.");
			return '';			
		}
		return '';
	}	
	
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$xml=str_replace("[SESSION_ID]", $objInfo['id'], $sourceXml);
		$xml=str_replace("[MEETING_TITLE]", htmlspecialchars($objInfo['meeting_title']), $xml);
		$xml=str_replace("[MEETING_ID]", $objInfo['meeting_aid'], $xml);
		$xml=str_replace("[HOST_LOGIN]", htmlspecialchars($objInfo['host_login']), $xml);
		$xml=str_replace("[START_TIME]", $objInfo['start_time'], $xml);
		$xml=str_replace("[END_TIME]", $objInfo['mod_time'], $xml);
		$xml=str_replace("[ACCOUNT_TYPE]", htmlspecialchars($objInfo['license_code']), $xml);
		$xml=str_replace("[CLIENT_DATA]", htmlspecialchars($objInfo['client_data']), $xml);
/*		
		$maxUsers=$objInfo['max_concur_att'];
		if ($maxUsers=='0')
			$maxUsers='1';
		$xml=str_replace("[MAX_CONCURRENT]", $maxUsers, $xml);			
*/
		$session=new VSession($objInfo['id']);
		if ($session->IsInProgress())
			$inProgress='Y';
		else
			$inProgress='N';
		
		$xml=str_replace("[IN_PROGRESS]", $inProgress, $xml);

		return $xml;
	}
	
	function GetSelectQuery()
	{
		$query="brand_id='".$this->mBrandId."'";
		if (isset($_GET['session_id']) && $_GET['session_id']!='') {
			$query.=" AND (id='".$_GET['session_id']."')";
		}
		if (isset($_GET['meeting_id']) && $_GET['meeting_id']!='') {
			$query.=" AND (meeting_aid='".$_GET['meeting_id']."')";
		}	

		if (isset($_GET['in_progress'])) {
			if ($_GET['in_progress']=='Y')			
				$query.=" AND (".VSession::GetInProgressQuery().")";
			else if ($_GET['in_progress']=='N')
				$query.=" AND (".VSession::GetNotInProgressQuery().")";
		}
		if (isset($_GET['client_data'])) {			
			$query.=" AND (client_data='".$_GET['client_data']."')";
		}
		if (isset($_GET['host_login'])) {			
			$query.=" AND (LOWER(host_login)='".addslashes(strtolower($_GET['host_login']))."')";
		}

		if (isset($_GET['member_id']) && $_GET['member_id']!='') {
			VObject::Find(TB_USER, 'access_id', $_GET['member_id'], $userInfo);
			if (isset($userInfo['login'])) {
				$query.=" AND (LOWER(host_login)='".addslashes(strtolower($userInfo['login']))."')";
			} else {
				$query.=" AND (host_login='')";
			}
		}
		if (isset($_GET['from_date']) && $_GET['from_date']!='') {
			if (strlen($_GET['from_date'])<=11)
				$fromDate=$_GET['from_date']." 00:00:00";
			else
				$fromDate=$_GET['from_date'];
			$query.=" AND (start_time>='".$fromDate."')";
		}
		if (isset($_GET['to_date']) && $_GET['to_date']!='') {
			if (strlen($_GET['to_date'])<=11)
				$toDate=$_GET['to_date']." 23:59:59";
			else
				$toDate=$_GET['to_date'];
			$query.=" AND (start_time<='".$toDate."')";
		}

		return $query;
	}	
/*
	function GetSelectQuery()
	{
		$query="brand_id='".$this->mBrandId."'";
		if (isset($_GET['group_id'])) {
			$query.=" AND (group_id='".$_GET['group_id']."')";
		}
		if (isset($_GET['from_date'])) {
			$query.=" AND (create_date>='".$_GET['from_date']."')";
		}
		if (isset($_GET['to_date'])) {
			$query.=" AND (create_date<='".$_GET['to_date']."')";
		}
		if (isset($_GET['license_code'])) {			
			$errMsg=VObject::Find(TB_LICENSE, 'code', $_GET['license_code'], $licenseInfo);
			if (isset($licenseInfo['id']))
				$query.=" AND (license_id='".$licenseInfo['id']."')";
		}
		return $query;
	}
*/

}


?>