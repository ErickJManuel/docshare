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
class PCommand extends PRestAPI 
{
	var $m_commands=array(
		"OpenScreenSharing" => "Open the screen sharing dialog box.",
		"OpenScreenshot" => "Open the send screenshot dialog box.",
		"OpenFilePicker" => "Open the send file dialog box.",
		"OpenInvitation" => "Open the send invitation page.",
		"OpenRecording" => "Open the recording dialog box.",
		"OpenWebcam" => "Open the webcam window. The optional value 'command_data' specifies a webcam index (1-12), that controls which of the webcam windows should be assigned to the attendee.",
		"RestartMeeting" => "Restart the meeting.",
		"StopMeeting" => "Stop the meeting.",
		);			
	/**
	 * Constructor
	 */	
	function PCommand()
	{

		$text="<h4>command_name</h4>\n<ul>\n";
		foreach ($this->m_commands as $key => $val) {
			$text.="<li><b>$key</b>: $val</li>\n";
		};
		$text.="</ul>\n";
		
		$this->PRestAPI("command");
		$this->mSynopsis="Command is used to send a control command to a meeting viewer. A meeting viewer must be running on the target attendee's computer. The attende must have the permissions to excute the command.";
		$this->mMethods="POST";
		$this->mRequired=array(
			"meeting_id" => "Meeting id. The meeting must be in progress.",
			"target_id" => "Target attendee id.",
			"command_name" => "Command name. See 'Additional information'.",
			);
		$this->mOptional=array(
			"command_data" => "Command data.  See 'Additional information'.",
			);
		$this->mAddtional=htmlspecialchars($text);
		
	}
	
	function Insert()
	{
		$respXml=$this->LoadResponseXml();
/*
$resp='';
foreach ($_POST as $key => $val)
	$resp.="$key=$val&";

$resp=htmlspecialchars($resp);
$this->SetStatusCode(PCODE_BAD_REQUEST);
$this->SetErrorMessage($resp);
return '';
*/
		if (!isset($_POST['meeting_id'])) {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'meeting_id'");
			return '';
		}
		if (!isset($_POST['target_id'])) {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'target_id'");
			return '';
		}
		if (!isset($_POST['command_name'])) {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter 'command_name'");
			return '';
		}
		$commandName=$_POST['command_name'];
		if (!isset($this->m_commands[$commandName])) {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Invalid command $commandName");
			return '';
		}
		
		if ($_POST['meeting_id']!=GetSessionValue('meeting_access_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}	
		$commandData='';
		$this->GetPostArg('command_data', $commandData);
			
		$eventType="SendCommand";
		$eventData="<command command_name='$commandName' command_data='$commandData'/>";
		
		$ret=SendEvent($_POST['meeting_id'], $_POST['target_id'], $_POST['target_id'], "", $eventType, $eventData, $response);
		if (!$ret) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($response);
			return '';
		}
		
		$items=explode("\n", $response, 3);
		if (count($items)>=3 && $items[0]=='OK') {
			$eventId=$items[2];				
		} else {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($response);
			return '';			
		}
		
		$xml=str_replace("[COMMAND_NAME]", $commandName, $respXml);
		$xml=str_replace("[COMMAND_DATA]", $commandData, $xml);
		$xml=str_replace("[TARGET_ATTENDEE_ID]", $_POST['target_id'], $xml);

		$this->SetStatusCode(PCODE_OK);
		return $xml;
	}

}


?>