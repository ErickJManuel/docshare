<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PMeeting extends PRestAPI 
{
	/**
	 * Constructor
	 */	
	function PMeeting()
	{
		$this->PRestAPI("meeting");
		$this->mSynopsis="An object that represents a meeting.";
		$this->mMethods="GET,POST,PUT,DELETE";
		$this->mRequired=array(
			'id' => "Meeting id. Required for GET, PUT, DELETE. Ignored for POST.",
			'member_id' => "Member id to which this meeting belongs. Required for POST.",
			);
		$this->mOptional=array(
			'status' => "Meeting status (STOP, START, START_RECORDING, END_RECORDING). Setting the meeting status to START or STOP will start or end the meeting. Setting the status to START_RECORDING or END_RECORDING will start or end the recording of an in-progress meeting. The attendee_url returned in the response is the url an attendee uses to join a meeting. When changing status of a meeting, any other updates to the meeting object are ignored.",
			'login_type' => "Meeting login option (NAME, PWD, REGIS, NONE)",
			'meeting_type' => "Meeting type (NORMAL, OPEN, PANEL)",
			'password' => "Meeting password.",
			'title' => "Meeting title.",
			'description' => "Meeting description.",
			'scheduled' => "Is this a scheduled a meeting? (Y, N)",
			'date_time' => "Meeting date and time. 'scheduled' needs to be 'Y'. (YYYY-MM-DD hh:mm:ss)",
			'duration' => "Meeting duration. 'scheduled' needs to be 'Y'. (hh:mm:ss)",
			'public' => "Is the meeting public? (Y, N)",
			'public_comment' => "Can someone post a public comment of the meeting? (Y, N)",
			'use_tele' => "Is the telephone used for the meeting? (Y, N) 'tele_num' is required if the parameter is Y.",
			'tele_num' => "Tele-conference number for the meeting.",
			'tele_num2' => "Alternative tele-conference number for the meeting.",
			'tele_mcode' => "Tele-conference moderator code.",
			'tele_pcode' => "Tele-conference participant code.",
			'client_data' => "A string up to 63 characters long to store client data.",
			);
		$this->mReturned=array(
			'STATUS' => "STOP, START (meeting in progress), START_REC (recording in progress), REC (recorded meeting)",
			'SESSION_ID' => "Live meeting session id. If the meeting is not in progress, this is the last session id. For recordings, this is the session that created the recording.",
			'LOCKED' => "Y if the meeting is locked, or N otherwise.",
			'PUBLIC' => "Y if the meeting is public, or N otherwise.",
			'PUBLIC_COMMENT' => "Y if someone can post a public comment, or N otherwise.",
			'USE_TELE' => "Y if the telephone is used. or N otherwise.",
			'TELE_NUM' => "Tele-conference number for the meeting.",
			'TELE_NUM2' => "Alternative tele-conference number for the meeting.",
			'TELE_MCODE' => "Tele-conference moderator code.",
			'TELE_PCODE' => "Tele-conference participant code.",
			'HOST_ID' => "The member id of the meeting host.",
			'HOST_URL' => "The URL for for the meeting host to join the meeting. The host must sign in first for the URL to work.  IMPORTANT: The url contains encoded characters. You must remove 'amp;' in the url before redirecting the user to it.",
			'ATTENDEE_URL' => "The URL for a meeting attendee to join a meeting. Append 'user=user_name' and 'pass=meeting_password' to the URL to automatically log in an attendee with the user_name and meeting_password. If the meeting requires registration, append 'email=registered_email_address'.",
			'CLIENT_DATA' => "Client data.",
			'MEETING_TYPE' => "OPEN if anyone can start the meeting. NORMAL or blank if only the moderator can start the meeting.",
			'ROOM_PICT_URL' => "Meeting viewer background picture URL.",
			'LOGO_PICT_URL' => "Meeting viewer logo picture URL.",
			'SHOW_ATTENDEES' => "Y if the meeting viewer will display all participants.",
			'PUBLIC_CHAT' => "Y if the meeting permits attendees to send messages to everyone. N if attendees are only allowed to send messages to the moderator.",
			'EXIT_URL' => "The URL to direct the attendee to when exiting a meeting. If the field is empty, the default exit page will be used.",
			);
		
	}

	function Get($meetingId='')
	{
		$respXml=$this->LoadResponseXml();
		
		$query="brand_id='".$this->mBrandId."'";
		
		// the input can be id (user access_id) or login
		if ($meetingId=='')
			if (isset($_GET['id']))
				$meetingId=$_GET['id'];
		
		if ($meetingId!='')
			$query.=" AND access_id='".$meetingId."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}
				
		$meetingInfo=array();
		$errMsg=VObject::Select(TB_MEETING, $query, $meetingInfo);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		if (!isset($meetingInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Meeting not found.");
			return '';
		}
		$meeting=new VMeeting($meetingInfo['id']);
		
		if ($meetingInfo['access_id']!=GetSessionValue('meeting_access_id') &&
			$meetingInfo['host_id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}
		
		if ($meeting->GetViewerUrl(true, $hostUrl)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($meeting->GetErrorMsg());
			return '';			
		}
		if ($meeting->GetViewerUrl(false, $attendeeUrl)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($meeting->GetErrorMsg());
			return '';			
		}
		
		$respXml=PMeeting::ReplaceObjectTags($meetingInfo, $respXml);
		
		$respXml=str_replace("[HOST_URL]", htmlspecialchars($hostUrl), $respXml);
		$respXml=str_replace("[ATTENDEE_URL]", htmlspecialchars($attendeeUrl), $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $respXml;
	}
	function ReplaceObjectTags($meetingInfo, $respXml)
	{
		$status='';
		if ($meetingInfo['status']=='START' || $meetingInfo['status']=='START_REC')
			$status='START';
		else if ($meetingInfo['status']=='LOCK' || $meetingInfo['locked']=='Y') 
			// $meetingInfo['locked'] is added to DB in version 2.2.14.0
			// We can't use 'status' to set the LOCK, which can be unlocked if status is set to 'START_REC'
			$status='LOCK';
		else if ($meetingInfo['status']=='STOP')
			$status='STOP';
		else if ($meetingInfo['status']=='REC')
			$status='REC';
		$respXml=str_replace("[ID]", $meetingInfo['access_id'], $respXml);
		$respXml=str_replace("[TITLE]", htmlspecialchars($meetingInfo['title']), $respXml);
		$respXml=str_replace("[DESCRIPTION]", htmlspecialchars($meetingInfo['description']), $respXml);
		$respXml=str_replace("[STATUS]", $status, $respXml);
		$respXml=str_replace("[DATE_TIME]", $meetingInfo['date_time'], $respXml);
		$respXml=str_replace("[DURATION]", $meetingInfo['duration'], $respXml);
		$respXml=str_replace("[SCHEDULED]", $meetingInfo['scheduled'], $respXml);
		$respXml=str_replace("[LOGIN_TYPE]", $meetingInfo['login_type'], $respXml);
		$respXml=str_replace("[MEETING_TYPE]", $meetingInfo['meeting_type'], $respXml);
		$respXml=str_replace("[PASSWORD]", htmlspecialchars($meetingInfo['password']), $respXml);
		$respXml=str_replace("[PUBLIC]", $meetingInfo['public'], $respXml);
		$respXml=str_replace("[PUBLIC_COMMENT]", $meetingInfo['public_comment'], $respXml);
		$respXml=str_replace("[USE_TELE]", $meetingInfo['tele_conf'], $respXml);
		$respXml=str_replace("[TELE_NUM]", htmlspecialchars($meetingInfo['tele_num']), $respXml);
		$respXml=str_replace("[TELE_MCODE]", htmlspecialchars($meetingInfo['tele_mcode']), $respXml);
		$respXml=str_replace("[TELE_PCODE]", htmlspecialchars($meetingInfo['tele_pcode']), $respXml);
		$respXml=str_replace("[CLIENT_DATA]", htmlspecialchars($meetingInfo['client_data']), $respXml);
		$respXml=str_replace("[LOCKED]", $meetingInfo['locked'], $respXml);
		
		$sessionId=$meetingInfo['session_id'];
		// Wouldn't hurt to return the session_id regardless it is a live meeting or not
		// For recordings, this allows us to find the original meeting used to do the recording because they share the same session_id.
//		if ($meetingInfo['status']=='STOP' || $meetingInfo['status']=='REC')
//			$sessionId='';

		$respXml=str_replace("[SESSION_ID]", $sessionId, $respXml);

		// get meeting viewer background pict
		require_once("dbobjects/vimage.php");
		require_once("dbobjects/vbrand.php");
		require_once("dbobjects/vviewer.php");
		$pictUrl='';
		$hostId=$meetingInfo['host_id'];
		$hostInfo=array();
		$host=new VUser($hostId);
		$host->Get($hostInfo);
		
		$brand=new VBrand($hostInfo['brand_id']);
		$brand->Get($brandInfo);
		
		// get the user's viewer
		$viewerId=$hostInfo['viewer_id'];
		if ($viewerId==0) {
			// The user has no custom viewer, get the brand's viewer
			$viewerId=$brandInfo['viewer_id'];
		}
		// get the viewer's background	
		$backId=0;
		$logoId=0;
		$viewerInfo=array();
		$seeAll=$sendAll='Y';
		$exitUrl='';
		if ($viewerId>0) {
			$viewer=new VViewer($viewerId);	
			$viewer->Get($viewerInfo);
			if (isset($viewerInfo['back_id'])) {
				$backId=$viewerInfo['back_id'];
				$seeAll=$viewerInfo['see_all'];
				$sendAll=$viewerInfo['send_all'];
				$exitUrl=$viewerInfo['end_url'];
			}
			$logoId=$viewerInfo['logo_id'];
			
			// get the brand's viewer logo
			if ($logoId==0) {
				if ($viewerId!=$brandInfo['viewer_id']) {
					$brandViewer=new VViewer($brandInfo['viewer_id']);	
					$brandViewer->GetValue("logo_id", $logoId);
				} else {
					$logoId=$viewerInfo['logo_id'];
				}
			}
			
			// get the default viewer logo
			if ($logoId==0)
				$logoId=1;	
			
		}
		// get the background's pict	
		$pictId=0;
		$pictName='';
		if ($backId>0) {
			$back=new VBackground($backId);
			$back->Get($backInfo);
			if (isset($backInfo['onpict_id'])) {
				$pictId=$backInfo['onpict_id'];
				$pictName=$backInfo['name'];
			}
		}
		// get the pict's url	
		if ($pictId>0) {
			$image=new VImage($pictId);
			$image->GetUrl(SITE_URL, $pictUrl);
		}
		
		// get the logo url
		$logoUrl='';
		if ($logoId>0) {
			$image1=new VImage($logoId);
			$image1->GetUrl(SITE_URL, $logoUrl);			
		}

		$respXml=str_replace("[ROOM_PICT_NAME]", htmlspecialchars($pictName), $respXml);
		$respXml=str_replace("[ROOM_PICT_URL]", $pictUrl, $respXml);
		$respXml=str_replace("[LOGO_PICT_URL]", $logoUrl, $respXml);
//		$respXml=str_replace("[ROOM_PICT_WIDTH]", BACKGROUND_WIDTH, $respXml);
//		$respXml=str_replace("[ROOM_PICT_HEIGHT]", BACKGROUND_HEIGHT, $respXml);
		$respXml=str_replace("[SHOW_ATTENDEES]", $seeAll, $respXml);
		$respXml=str_replace("[PUBLIC_CHAT]", $sendAll, $respXml);
		$respXml=str_replace("[EXIT_URL]", htmlspecialchars($exitUrl), $respXml);
		
		$hostPictUrl='';
		if ($hostInfo['pict_id']>0) {
			$pict=new VImage($hostInfo['pict_id']);
			$pict->GetUrl(SITE_URL, $hostPictUrl);
		}	
		$respXml=str_replace("[HOST_PICT_URL]", $hostPictUrl, $respXml);
		$hostName=VUser::GetFullName($hostInfo);
		$respXml=str_replace("[HOST_NAME]", htmlspecialchars($hostName), $respXml);	
		$respXml=str_replace("[HOST_ID]", $hostInfo['access_id'], $respXml);	
		return $respXml;
	}
	
	function Update($meetingId='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($meetingId=='')
			if (isset($_POST['id']))
				$meetingId=$_POST['id'];
		
		if ($meetingId!='')
			$query.=" AND access_id='".$meetingId."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}
		$meetingInfo=array();
		$errMsg=VObject::Select(TB_MEETING, $query, $meetingInfo);
		if (!isset($meetingInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Meeting not found.");
			return '';
		}
		if ($meetingInfo['host_id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$meetingInfo['brand_id'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}	
		
		$cmd='SET_MEETING';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;
		
		$userId=$meetingInfo['host_id'];		
		$user=new VUser($userId);
		if ($user->Get($userInfo)!=ERR_NONE){
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($user->GetErrorMsg());
			return '';
		}		
		if (!isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Meeting host not found.");
			return '';
		}
		
		// log in the user
		SetSessionValue("member_id", $userInfo['id']);
		$memberName=VUSer::GetFullName($userInfo);
		SetSessionValue("member_name", $memberName);
		SetSessionValue("member_perm", $userInfo['permission']);					
		SetSessionValue("member_brand", $userInfo['brand_id']);				
		
		// don't allow changing user_id; also user_id in set_meeting.php is the database id, not the access_id as in the input
		if (isset($_POST['user_id'])) {
			unset($_POST['user_id']);
			unset($_REQUEST['user_id']);
		}

		if (isset($_POST['id'])) {
			$VARGS['meeting']=$_POST['id'];
			unset($_POST['id']);
			unset($_REQUEST['id']);
		}
		if (isset($_POST['use_tele'])) {
			$VARGS['tele_conf']=$_POST['use_tele'];
		}
		
		// status change requires a separate processing and can't use set_meeting
		$status='';
		if (isset($_POST['status'])) {
			$status=$_POST['status'];
			unset($_POST['status']);
			unset($_REQUEST['status']);
		} else {
			require_once('api_includes/set_meeting.php');
		}
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
		
		if ($status!='') {
			$api_error_message='';
			$api_exit=false;
			$restApi=true;
			if ($status=='START') {
				require_once('api_includes/start_meeting.php');			
			} elseif ($status=='STOP') {
				$cmd='END_MEETING';
				require_once('api_includes/end_meeting.php');
			} elseif ($status=='START_RECORDING') {
				if ($meetingInfo['status']!='START') {
					$this->SetStatusCode(PCODE_ERROR);
					$this->SetErrorMessage("Meeting is not started.");
					return '';					
				}
				require_once('api_includes/start_recording.php');			
			} elseif ($status=='END_RECORDING') {
				if ($meetingInfo['status']!='START_REC') {
					$this->SetStatusCode(PCODE_ERROR);
					$this->SetErrorMessage("Meeting recording is not in progress.");
					return '';					
				}
				require_once('api_includes/end_recording.php');

			} else {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Invalid status code.");
				return '';
			}
			if ($api_error_message!='') {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage($api_error_message);
				return '';
			}		
		}
		return $this->Get($meetingInfo['access_id']);

	}
	function Insert()
	{
		global $api_error_message, $api_exit, $VARGS;
				
		$cmd='ADD_MEETING';
		$api_error_message='';
		$api_exit=false;
/*
		if (isset($_POST['member_id'])) {
			$VARGS['user']=$_POST['member_id'];
		} else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'member_id'");
			return '';
		}
*/			
		if (!isset($_POST['member_id'])) {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'member_id'");
			return '';
		}
		$VARGS['user']=$_POST['member_id'];
		$errMsg=VObject::Find(TB_USER, "access_id", $_POST['member_id'], $userInfo);
		if (!isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Member not found.");
			return '';
		}
		if ($userInfo['id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}	
			
		// log in the user; set_meeting.php requires the use to log in.
		SetSessionValue("member_id", $userInfo['id']);
		$memberName=VUSer::GetFullName($userInfo);
		SetSessionValue("member_name", $memberName);
		SetSessionValue("member_perm", $userInfo['permission']);					
		SetSessionValue("member_brand", $userInfo['brand_id']);				

		if (isset($_POST['id'])) {
			unset($_POST['id']);
			unset($_REQUEST['id']);
		}
		if (isset($_POST['status'])) {
			unset($_POST['status']);
			unset($_REQUEST['status']);
		}
		if (isset($_POST['use_tele'])) {
			$VARGS['tele_conf']=$_POST['use_tele'];
		}
		require_once('api_includes/set_meeting.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		$meeting->GetValue('access_id', $meetingId);
		return $this->Get($meetingId);

	}
	
	function Delete($meetingId='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($meetingId=='')
			if (isset($_POST['id']))
				$meetingId=$_POST['id'];
		
		if ($meetingId!='')
			$query.=" AND access_id='".$meetingId."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}

		$meetingInfo=array();
		$errMsg=VObject::Select(TB_MEETING, $query, $meetingInfo);
		if (!isset($meetingInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Meeting not found.");
			return '';
		}
		
		if ($meetingInfo['host_id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$meetingInfo['brand_id'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}
		
		$user=new VUser($meetingInfo['host_id']);
		if ($user->Get($userInfo)!=ERR_NONE){
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($user->GetErrorMsg());
			return '';
		}		
		if (!isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Meeting host user not found.");
			return '';
		}
		
		// log in the user; delete_meeting.php requires the use to log in.
		SetSessionValue("member_id", $userInfo['id']);
		$memberName=VUSer::GetFullName($userInfo);
		SetSessionValue("member_name", $memberName);
		SetSessionValue("member_perm", $userInfo['permission']);					
		SetSessionValue("member_brand", $userInfo['brand_id']);				
		
		$theId=$meetingInfo['access_id'];
		
		$cmd='DELETE_MEETING';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;

		if (isset($_POST['id'])) {
			$VARGS['meeting']=$_POST['id'];
			unset($_POST['id']);
			unset($_REQUEST['id']);
		}

		require_once('api_includes/delete_meeting.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->GetMessageXml("Meeting $theId deleted.");

	}
}


?>