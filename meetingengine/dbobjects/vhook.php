<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");

$hookResponse=null;
$current_tag='';
function start_hook_tag($parser, $name, $attribs) {
	global $hookResponse, $current_tag;
	$current_tag=$name;
}

function end_hook_tag($parser, $name) {
	global $hookResponse, $current_tag;
	$current_tag='';
}
function parse_hook($parser, $data) {
	global $hookResponse, $current_tag;

	switch ($current_tag) {
        case "code":
			$hookResponse['code']=$data;
            break;
        case "link":
			$hookResponse['link'].=$data; // this is called multiple times if there is a & in the link so we need to concatenate them
            break;
        case "message":
			$hookResponse['message'].=$data;
            break;
    }
}

$s_hook_info=array(
	'add_meeting' => "Called when a meeting is about to be added.",
	'meeting_added' => "Called after a meeting has been added.",
	'delete_meeting' => "Called when a meeting is about to be deleted.",
	'meeting_deleted' => "Called after a meeting has been deleted.",
	'set_meeting' => "Called when a meeting is about to be modified.",
	'meeting_set' => "Called after a meeting has been modified.",
	'start_meeting' => "Called when a meeting is about to be started by a member. The hook is called only once for each meeting session. Resuming the same session will not trigger the hook again. The hook is only applicable to a moderator. Attendees joining a meeting will invoke the 'join_meeting' hook.",
	'meeting_started' => "Called when a meeting is already started by a member and is about to go to the meeting page. The hook can redirect the member to a client page to implement a custom meeting viewer. The client page should embed the 'host_url' page returned from the <a target='_parent' href='http://test1.persony.net/test2/?page=HELP_REST&topic=Objects&sub1=meeting'>'meeting'</a> API object. The hook is called every time the member starts or resumes a meeting and may be called multiple times per session. The hook is only applicable to a moderator.",
	'end_meeting' => "Called when a meeting is about to be ended by a member. The hook is only applicable to a moderator.",
	'meeting_ended' => "Called when a meeting is already ended by a member. The hook is called for both the moderator and attendees, and can be used to redirect the user to an exit page.",
	'join_meeting' => "Called when an attendee (not including the moderator) is about to go to the meeting page. This hook is called for every attendee joining a meeting or playing a recording. The hook can redirect the user to a client page to implement a custom meeting viewer. The client page should embed the 'attendee_url' page returned from the <a target='_parent' href='http://test1.persony.net/test2/?page=HELP_REST&topic=Objects&sub1=meeting'>'meeting'</a> API object.",
	'login_meeting' => "Called when an attendee is about to log in to a meeting or play back a recording. This hook is called for every attendee (except for the moderator) and can be used to provide access authentication.",
	'start_recording' => "Called when a recording session is about to be started by a member. The hook can initiate an adjunct recording session or abort the recording session. The hook is only applicable to a moderator.",
	'recording_started' => "Called when a recording session is already started by a member. The hook is only applicable to a moderator.",
	'end_recording' => "Called when a recording session is about to be ended by a member. The hook can end an adjunct audio recording session. The hook is only applicable to a moderator.",
	'recording_ended' => "Called when a recording session is already ended by a member. The hook is only applicable to a moderator.",
	'lock_meeting' => "Called when a meeting is about to be locked by a moderator.",
	'unlock_meeting' => "Called when a meeting is about to be unlocked by a moderator.",
/*
	'show_invitation_page' => "Called when a meeting invitation page is about to be shown. You can redirect to your own invitation page.",
	'show_member_page' => "Called when a member's profile page that contains the member's vCard is about to be shown. You can redirect to your own profile page.",
	'show_meeting_page' => "Called when a meeting information page is about to be shown. You can redirect to your own meeting page.",
*/
		);

$s_hook_params=array(
	'add_meeting' => array(
//		"meeting_id" => "id of the meeting to be added.",
		"member_id" => "id of the member adding the meeting.",
		),
	'meeting_added' => array(
		"meeting_id" => "id of the meeting added.",
		"member_id" => "id of the member adding the meeting.",
		),
	'delete_meeting' => array(
		"meeting_id" => "id of the meeting to be deleted.",
		"member_id" => "id of the member deleting the meeting.",
		),
	'meeting_deleted' => array(
		"meeting_id" => "id of the meeting deleted.",
		"member_id" => "id of the member deleted the meeting.",
		),
	'set_meeting' => array(
		"meeting_id" => "id of the meeting to be modified.",
		"member_id" => "id of the member modifying the meeting.",
		),
	'meeting_set' => array(
		"meeting_id" => "id of the meeting modified.",
		"member_id" => "id of the member modified the meeting.",
		),
	'start_meeting' => array(
		"meeting_id" => "id of the meeting to be started.",
		"member_id" => "id of the member starting the meeting.",
		),
	'meeting_started' => array(
		"member_id" => "id of the member started the meeting.",
		"meeting_id" => "id of the meeting just started.",
		"session_id" => "id of the meeting session just started.",
		),
	'end_meeting' => array(
		"meeting_id" => "id of the meeting just ended.",
		"session_id" => "id of the meeting session just ended.",
		),
	'meeting_ended' => array(
		"meeting_id" => "id of the meeting just ended.",
		"session_id" => "id of the meeting session just ended.",
		),
	'join_meeting' => array(
		"meeting_id" => "id of the meeting to join.",
		"session_id" => "id of the meeting session to join.",
		),
	'login_meeting' => array(
		"meeting_id" => "id of the meeting to log in to.",
		"client_id" => "optional parameter passed in by the client system via the meeting url.",
		"client_code" => "optional parameter passed in by the client system via the meeting url.",
		),
	'start_recording' => array(
		"meeting_id" => "id of the meeting to be recorded.",
		"session_id" => "Recording session id. A unique id is generated for each session.",
		"member_id" => "id of the member.",
		"tele_number" => "Tele-conference call number.",
		"tele_code" => "Tele-conference call access code.",
		),
	'recording_started' => array(
		"meeting_id" => "id of the meeting.",
		"session_id" => "Recording session id. A unique id is generated for each session.",
		"member_id" => "id of the member.",
		"tele_number" => "Tele-conference call number.",
		"tele_code" => "Tele-conference call access code.",
		),
	'end_recording' => array(
		"meeting_id" => "id of the meeting.",
		"session_id" => "Recording session id. A unique id is generated for each session.",
		"member_id" => "id of the member.",
		"tele_number" => "Tele-conference call number.",
		"tele_code" => "Tele-conference call access code.",
		),
	'recording_ended' => array(
		"meeting_id" => "id of the meeting recorded.",
		"session_id" => "Recording session id. A unique id is generated for each session.",
		"member_id" => "id of the member.",
		"tele_number" => "Tele-conference call number.",
		"tele_code" => "Tele-conference call access code.",
		),
	'lock_meeting' => array(
		"meeting_id" => "id of the meeting.",
		"session_id" => "id of the meeting session.",
		"member_id" => "id of the member locking the meeting.",
		),
	'unlock_meeting' => array(
		"meeting_id" => "id of the meeting.",
		"session_id" => "id of the meeting session.",
		"member_id" => "id of the member unlocking the meeting.",
		),
/*
	'show_invitation_page' => array(
		"meeting_id" => "id of the meeting.",
		"member_id" => "id of the member showing the meeting (available only if the member has signed in.)",
		),
	'show_member_page' => array(
		"member_id" => "id of the member whose profile page is to be shown.",
		),
	'show_meeting_page' => array(
		"meeting_id" => "id of the meeting.",
		),	
*/		
	);

$s_hook_codes=array(
	'add_meeting' => array(
		"200" => "OK",
		"400" => "Abort. The meeting will not be added.",
		),
	'meeting_added' => array(
		"200" => "OK",
		),
	'delete_meeting' => array(
		"200" => "OK",
		"400" => "Abort. The meeting will not be deleted.",
		),
	'meeting_deleted' => array(
		"200" => "OK",
		),
	'set_meeting' => array(
		"200" => "OK",
		"400" => "Abort. The meeting will not be modified.",
		),
	'meeting_set' => array(
		"200" => "OK",
		),
	'start_meeting' => array(
		"200" => "OK",
		"400" => "Abort. The meeting will not be started.",
		),
	'meeting_started' => array(
		"200" => "OK",
		"300" => "Redirect. The moderator will be redirected to a client page.",
		),
	'end_meeting' => array(
		"200" => "OK",
		"400" => "Abort. The meeting will not be ended.",
		),
	'meeting_ended' => array(
		"200" => "OK",
		"300" => "Redirect. The attendee will be redirected to a client page.",
		),
	'join_meeting' => array(
		"200" => "OK",
		"300" => "Redirect. The attendee will be redirected to a client page.",
		"400" => "Abort. The attendee will not join the meeting.",
		),
	'login_meeting' => array(
		"200" => "OK",
		"400" => "Abort. The attendee will not be allowed to log in to the meeting. If the 'link' field is returned in the XML response, the attendee will be redicted to the url given in the link parameter. Otherwise, the attendee will be directed to the default exit page.",
		),
	'start_recording' => array(
		"200" => "OK",
		"400" => "Abort. The meeting will not be recorded.",
		),
	'recording_started' => array(
		"200" => "OK",
		),
	'end_recording' => array(
		"200" => "OK",
		),
	'recording_ended' => array(
		"200" => "OK",
		),
	'lock_meeting' => array(
		"200" => "OK",
		),
	'unlock_meeting' => array(
		"200" => "OK",
		),
/*
	'show_invitation_page' => array(
		"300" => "Redirect. The user will be redirected to a custom page.",
		),
	'show_member_page' => array(
		"300" => "Redirect. The user will be redirected to a custom page.",
		),
	'show_meeting_page' => array(
		"300" => "Redirect. The user will be redirected to a custom page.",
		),	
*/		
	);

	
/**
 * @package     VShow
 * @access      public
 */
class VHook extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VHook($id=0)
	{
		$this->VObject(TB_HOOK);
		$this->SetRowId($id);
	}
	/**
	 * @access static
	 * @param string $hookUrl
	 */
	function CallHook($hookUrl, $args, &$responses)
	{
require_once("includes/common_lib.php");

		global $hookResponse;
		
		$data='';
		foreach($args as $key => $value)
		{
			if ($data!='')
				$data.="&";
			$data.= "$key=".$value;
		}
		$result=HTTP_Request($hookUrl, $data, "GET", 15);
/*
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');

		header("Content-type: text/xml");
		echo($result);
		exit();
*/		
		if ($result==false)
			return false;
			
		$hookResponse=array();	
		$hookResponse['code']='';
		$hookResponse['link']='';
		$hookResponse['message']='';
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_hook_tag", "end_hook_tag");
		xml_set_character_data_handler($xml_parser, "parse_hook");
		xml_parse($xml_parser, $result, true);
		xml_parser_free($xml_parser);
/*
		echo ("<pre>");
		print_r($hookResponse);
		echo ("</pre>");
		exit();
*/
			
		$responses=array();
		foreach ($hookResponse as $key => $val)
			$responses[$key]=$val;
					
		return true;
	}

}


?>