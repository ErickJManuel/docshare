<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

/* This file shouldn't be used anymore because live attendees are no longer stored in the database */

require_once("dbobjects/vattendee.php");
require_once("dbobjects/vattendeelive.php");
require_once('api_includes/meeting_common.php');

define("CALLID_PREFIX", "i");

class TeleParticipant {
	var $CallId='';
	var $ParticipantNumber='';
	var $CallerName='';
	var $CreationTime='';
	var $Muted='';
	var $ParticipantType='';
	var $CallType='';
	var $CallState='';
	var $ActiveTalker='';
	var $inWeb=false;
};

$participants=array();
$current_tag='';
$itemIndex=0;
$inItem=false;
$current_part=null;

function start_participant_tag($parser, $name, $attribs) {
	global $current_tag, $itemIndex, $inItem, $participants, $current_part;
	$current_tag = $name;
	if ($name=='Participant') {
		$inItem=true;
		//		$itemIndex=count($participants);
		$current_part=new TeleParticipant();
	}
}

function end_participant_tag($parser, $name) {
	global $current_tag, $itemIndex, $inItem, $participants, $current_part;
	$current_tag = '';
	if ($name=='Participant') {
		$inItem=false;
		$num=$current_part->ParticipantNumber;
		if ($num!='Unavailable' && $num!='')
			$participants[$num]=$current_part;
		else
			$participants["i".$current_part->CallId]=$current_part;
	}
}
function parse_participant($parser, $data) {
	global $current_tag, $itemIndex, $inItem, $participants, $current_part;
	
	switch ($current_tag) {
		case "CallId":
			if ($inItem)
				$current_part->CallId=$data;
			break;
		case "CallerName":
			if ($inItem)
				$current_part->CallerName=$data;
			break;
		case "ParticipantNumber":
			if ($inItem) {
				$current_part->ParticipantNumber=$data;
			}
			break;
		case "CreationTime":
			if ($inItem)
				$current_part->CreationTime=$data;
			break;
		case "Muted":
			if ($inItem)
				$current_part->Muted=$data;
			break;
		case "CallType":
			if ($inItem)
				$current_part->CallType=$data;
			break;
		case "CallState":
			if ($inItem)
				$current_part->CallState=$data;
			break;
		case "ActiveTalker":
			if ($inItem)
				$current_part->ActiveTalker=$data;
			break;
		case "ParticipantType":
			if ($inItem)
				$current_part->ParticipantType=$data;
			break;
	}
}

function GetParticipantXml($part)
{
	$xml="<ThinkEngine\n";
	$xml.="ParticipantNumber=\"".$part->ParticipantNumber."\"\n";
	$xml.="Muted=\"".$part->Muted."\"\n";
	$xml.="CallId=\"".$part->CallId."\"\n";
	$xml.="CallState=\"".$part->CallState."\"\n";
	$xml.="CallType=\"".$part->CallType."\"\n";
	$xml.="/>\n";
	return $xml;
}

if ($errMsg!='')
	return API_EXIT(API_ERR, $errMsg);
	
if (!isset($meetingInfo['id']))
	return API_EXIT(API_ERR, "Meeting id not set.");
/*
$memberId=GetSessionValue('member_id');
$memberName=GetSessionValue('member_name');
echo("id=".$memberId." name=".$memberName);
*/

$sessionId=$meetingInfo['session_id'];
//$host=new VUser($meetingInfo['host_id']);
//$host->GetValue('access_id', $hostId);

/*
if ($cmd=='ADD_ATTENDEE') {

	if ($meetingInfo['status']=='STOP' || $meetingInfo['status']=='REC')
		return API_EXIT(API_ERR, "The meeting is not in progress");
		
	$attInfo=array();
	if (GetArg('attendee_id', $arg) && $arg!='') {
		$attId=$arg;
		$query="attendee_id = '$attId' AND session_id = '$sessionId'";
		$errMsg=VObject::Select(TB_ATTENDEE_LIVE, $query, $attInfo);
		if ($errMsg!='') {
			return API_EXIT(API_ERR, $errMsg);
		}
	} else {
		return API_EXIT(API_ERR, "Missing attendee_id");		
	}
	
	if (isset($attInfo['id'])) {
			
		$attendee=new VAttendeeLive($attInfo['id']);
//		$attendee->Touch();
		if ($attendee->Resume()!=ERR_NONE)
			return API_EXIT(API_ERR, $attendee->GetErrorMsg());
		
	} else {
require_once("dbobjects/vuser.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vsession.php");
//require_once("dbobjects/vbrand.php");
//require_once("dbobjects/vprovider.php");
		
		// if the meeting is locked, only a returning attendee can join
		if ($meetingInfo['locked']=='Y')
//			return API_EXIT(API_ERR, "The meeting is not accepting more attendees");
			// the message has to be exactly "meeting full" for the Flash viewer to pick up
			return API_EXIT(API_ERR, "meeting full");
				
		// find out how many attendees are allowed to the meeting				
		$hostId=$meetingInfo['host_id'];
		$host=new VUser($hostId);
		$host->GetValue('license_id', $licenseId);
		$license=new VLicense($licenseId);
		$licInfo=array();
		if ($license->Get($licInfo)!=ERR_NONE) {
			return API_EXIT(API_ERR, $license->GetErrorMsg());
		}	
		
		$maxAtt=$licInfo['max_att'];
		
		if ($maxAtt>0) {			
			// check if we are exceeding the limit
			
			$session=new VSession($sessionId);
			if ($session->GetAttendeeCount($attCount)!=ERR_NONE) {
				return API_EXIT(API_ERR, $session->GetErrorMsg(), "VSession::GetAttendeeCount $attCount");
			}
			
			if ($attCount>=$maxAtt)
				return API_EXIT(API_ERR, "meeting full"); 
				// the message has to be exactly "meeting full" for the Flash viewer to pick up
			
		}
		
		$attendee=new VAttendeeLive();		

		$attInfo['brand_id']=$meetingInfo['brand_id'];
		$attInfo['session_id']=$sessionId;
		$attInfo['start_time']='#NOW()';
		$attInfo['mod_time']='#NOW()';
		if (GetArg('attendee_id', $arg) && $arg!='')
			$attInfo['attendee_id']=$arg;
		else
			return API_EXIT(API_ERR, "Missing attendee_id."); 
		
		// see if the attendee is a member	
		VObject::Find(TB_USER, "access_id", $attInfo['attendee_id'], $userInfo);
		if (isset($userInfo['id']))
			$attInfo['user_id']=$attInfo['attendee_id'];
		
//		if (GetArg('user_id', $arg))
//			$attInfo['user_id']=$arg;
		if (GetArg('user_name', $arg))
			$attInfo['user_name']=$arg;
		if (GetArg('user_ip', $arg))
			$attInfo['user_ip']=$arg;
					
		if ($attendee->Insert($attInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $attendee->GetErrorMsg());
		if ($attendee->Get($attInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $attendee->GetErrorMsg());
			
	}
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	
	header("Content-Type: text/xml");
	$xml=XML_HEADER."\n";
	$xml.=$attendee->GetXML($attInfo);
	echo $xml;

	API_EXIT(API_NOMSG);
	
} else */
if ($cmd=='GET_ATTENDEE_LIST') {
	
	$liveMeeting=true;
	if ($meetingInfo['status']=='STOP' || $meetingInfo['status']=='REC')
		$liveMeeting=false;
	
	
	if ($liveMeeting) {
		require_once('dbobjects/vteleserver.php');
		GetArg('tele_num', $phone);
		GetArg('tele_mcode', $mcode);
		GetArg('nocache', $nocache);
		
		if (GetArg('teleserver_id', $teleServerId)) {
			$telServ=new VTeleServer($teleServerId);
			if ($telServ->Get($teleInfo)!=ERR_NONE)
				API_EXIT(API_ERR, $telServ->GetErrorMsg());
			if (!isset($teleInfo['id']))
				API_EXIT(API_ERR, "Teleserver_id $teleServerId does not exist.");
			$teleServer=$teleInfo['server_url'];
			$accessKey=$teleInfo['access_key'];
			
		} else {
			// deprecated. don't use this
			GetArg('teleserver', $teleServer);
			$accessKey='';
			
		}
		
		$phone=RemoveSpacesFromPhone($phone);
		$mcode=RemoveNonNumbers($mcode);
		
		if ($teleServer!='' && $phone!='' && $mcode!='') {
//			$cacheFile=DIR_TEMP.md5($teleServer.$phone.$mcode);
			$cacheFile=GetTempDir().md5($teleServer.$phone.$mcode);
			$hasCache=false;
			if ($nocache=='1') {
				$hasCache=false;
			} elseif (file_exists($cacheFile)) {
				$curTime=time();
				$modTime=filemtime($cacheFile);
				if (($curTime-$modTime)<=3) {
					$hasCache=true;			
				}
			}
			
			$resp='';
			if ($hasCache) {
				$fp=@fopen($cacheFile, "r");
				if ($fp && flock($fp, LOCK_SH)) {
					$resp=fread($fp, filesize($cacheFile));
					flock($fp, LOCK_UN);				
				}
				if ($fp)
					fclose($fp);
				
			} else {
				$args="phone=".$phone."&id=".$mcode;
				if ($accessKey!='')
					$sig="signature=".md5($args.$accessKey);
				else
					$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
					
				$args.="&".$sig;
				$teleUrl=$teleServer."participant/?".$args;	
				$resp=HTTP_Request($teleUrl, '', 'GET', 5);
				if ($resp) {				
					$fp=@fopen($cacheFile, "a");
					
					if (flock($fp, LOCK_EX)) {
						ftruncate($fp, 0);
						fwrite($fp, $resp);				
						flock($fp, LOCK_UN);				
					}
					if ($fp)
						fclose($fp);				
				}
			}
			
			// remove the first line which has duplicated xml header that prevents the parser from working
			//			$pos1=strpos($resp, "\n");
			//			$resp=substr($resp, $pos1+1);
			//			echo $resp."\n";
			
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($xml_parser, "start_participant_tag", "end_participant_tag");
			xml_set_character_data_handler($xml_parser, "parse_participant");
			xml_parse($xml_parser, $resp, true);
			xml_parser_free($xml_parser);
		}
		
	}
	GetArg('attendee_id', $attId);	
	if (GetArg("attendee_only", $arg) && $arg=='1') {
		// get only this attendee
		$query="attendee_id = '$attId' AND session_id = '$sessionId'";
	} else {				
		// Shouldn't this be only for when the meeting status is START?
		// request only attendees whoes records are updated within the timeout period
		$timeoutSec=VSESSION_TIMEOUT;
		$query="session_id = '$sessionId' AND mod_time>SUBTIME(NOW(), '0 0:0:$timeoutSec')";
		//	$query="session_id = '$sessionId' AND TIME_TO_SEC(TIMEDIFF(NOW(), mod_time))<'$timeoutSec'";
	}
	
	if ($liveMeeting)
		$errMsg=VObject::SelectAll(TB_ATTENDEE_LIVE, $query, $result);
	else
		$errMsg=VObject::SelectAll(TB_ATTENDEE, $query, $result);
	if ($errMsg!='') {
		return API_EXIT(API_ERR, $errMsg);
	}
	
	$num_rows = mysql_num_rows($result);
	
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	
	header("Content-Type: text/xml");
	$xml=XML_HEADER."\n";
	echo $xml;
	if (GetArg('count', $arg) && $arg=='1') {
		echo ("<attendeelist count=\"$num_rows\" />");		
	} else {
		
		$callerCount=count($participants);
		echo "<attendeelist>\n";
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$partXml='';
			if ($callerCount>0) {
				$callerId=$row['caller_id'];
				if ($callerId!='') {
					// find the tele participant
					if (isset($participants[$callerId])) {
						$part=$participants[$callerId];
						$part->inWeb=true;
						$partXml=GetParticipantXml($part);
					}			
				}
			}
			if ($liveMeeting)
				$xml=VAttendeeLive::GetXML($row, $partXml);
			else
				$xml=VAttendee::GetXML($row, $partXml);
			echo $xml."\n";
		}
		
		// write out all tele-only participants
		if ($callerCount>0) {
			foreach ($participants as $part) {
				if ($part->inWeb==false) {
					//					$uid=CALLID_PREFIX.$part->CallId;
					$uid='';
					$xml="<attendee userid=\"$uid\" ";
					$xml.="startTime=\"".$part->CreationTime."\" >\n";
					$xml.=GetParticipantXml($part);
					$xml.="</attendee>\n";								
					echo $xml."\n";
				}			
			}
		}
		echo "</attendeelist>";
	}
	
	if ($liveMeeting) {
		
		// update the attendee mod_time
		if ($attId!='') {
			$attInfo=array();
			$query="attendee_id = '$attId' AND session_id = '$sessionId'";
			$errMsg=VObject::Select(TB_ATTENDEE_LIVE, $query, $attInfo);
			if ($errMsg!='') {
				return API_EXIT(API_ERR, $errMsg);
			}
			if (isset($attInfo['id'])) {
				$attendee=new VAttendeeLive($attInfo['id']);
				$attendee->Touch();
			} else {
				//				return API_EXIT(API_ERR, "Attendee not found ".$sessionId." ".$attId);				
			}
			
			// update the session mod_time
			// do this for all attendees				
			$session=new VSession($meetingInfo['session_id']);
			$session->GetValue('max_concur_att', $concurAtt);
			$info=array();
			$info['mod_time']='#NOW()';
			if ($num_rows>$concurAtt) {
				$info['max_concur_att']=$num_rows;
			}
			$session->Update($info);
			
		}
	}
	
	API_EXIT(API_NOMSG);
}
/*
else if ($cmd=='SET_ATTENDEE') {
	
	if (!GetArg('attendee_id', $attId) || $attId=='')
		return API_EXIT(API_ERR, "Missing attendee_id");
		
	$attInfo=array();		
	$query="attendee_id = '$attId' AND session_id = '$sessionId'";
	$errMsg=VObject::Select(TB_ATTENDEE_LIVE, $query, $attInfo);
	if ($errMsg!='') {
		return API_EXIT(API_ERR, $errMsg);
	}
	
	if (isset($attInfo['id'])) {
		$attendee=new VAttendeeLive($attInfo['id']);
	} else {
		return API_EXIT(API_ERR, "Couldn't find attendee");
	}
	
	$updateInfo=array();
	if (GetArg('can_draw', $canDraw))
		$updateInfo['can_draw']=$canDraw;
	if (GetArg('can_present', $canPresent))
		$updateInfo['can_present']=$canPresent;
	if (GetArg('raise_hand', $raiseHand))
		$updateInfo['raise_hand']=$raiseHand;
	if (GetArg('emoticon', $emoticon))
		$updateInfo['emoticon']=$emoticon;
	if (GetArg('caller_id', $callerId)) {
		$updateInfo['caller_id']=$callerId;
		// check to see if the caller_id is on the list?
	}
		
	if (GetArg('start_video', $arg)) {
//		if (isset($attInfo['show_webcam']))
//			$updateInfo['show_webcam']='Y';

	} elseif (GetArg('end_video', $arg)) {
//		if (isset($attInfo['show_webcam']))
//			$updateInfo['show_webcam']='N';

	}

	if ($canDraw!='' || $canPresent!='') {
		if (!GetArg('host_id', $host_id) || $host_id=='')
			return API_EXIT(API_ERR, "Missing host_id");
	}
	$updateInfo['mod_time']='#NOW()';
	
//	if (($canDraw!='' || $canPresent!='') && $host_id!=$hostId)
//		return API_EXIT(API_ERR, "Not a host of the meeting");			
		
	if ($attendee->Update($updateInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $attendee->GetErrorMsg());


}

*/

?>