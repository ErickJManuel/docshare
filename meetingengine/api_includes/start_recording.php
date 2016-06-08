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

include_once("includes/log_error.php");

// if called from the API, the meetingInfo may be set already. Don't set it again.
if (!isset($meetingInfo['id']))
	require_once('api_includes/meeting_common.php');
	
if (!isset($meeting)) {
	require_once("dbobjects/vmeeting.php");
	$meeting=new VMeeting($meetingInfo['id']);
}

require_once("dbobjects/vhook.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vuser.php");

$xmlStatus='';
$xmlInfo='';
$current_tag='';
$recordStatus='';
$startTime='';
function start_xml_tag($parser, $name, $attribs) {
	global $xmlStatus, $xmlInfo, $current_tag;
	$current_tag=$name;
}

function end_xml_tag($parser, $name) {
	global $xmlStatus, $xmlInfo, $current_tag;
	$current_tag='';
}
function parse_xml($parser, $data) {
	global $xmlStatus, $xmlInfo, $current_tag;
	global $recordStatus, $startTime;
	
	switch ($current_tag) {
		case "cm_status":
			$xmlStatus=$data;
			break;
		case "cm_info":
			$xmlInfo=$data;
			break;
		case "Record":
			$recordStatus=$data;
			break;
		case "StartTime":
			$startTime=$data;
			break;
		default:
			break;
	}
}

if ($errMsg!='')
	return API_EXIT(API_ERR, $errMsg);
	
if (!isset($meetingInfo['id']))
	return API_EXIT(API_ERR, "Meeting id not set");

if ($meetingInfo['status']=='STOP') {
	LogRecord("Ignore attempt to start recording on a stopped meeting.");
	return API_EXIT(API_ERR, "Meeting is not in progress");
}
	
if ($meetingInfo['status']=='START_REC') {
	LogRecord("Ignore attempt to re-start a recording already in progress.");
	return API_EXIT(API_ERR, "Recording is already in progress");
}
/* disable login checking for now because the session may have expired during a meeting */

// enable login checking now because we use a token to keep the session alive
$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');
/*
if (isset($userInfo['login']) && $userInfo['login']==VUSER_GUEST) {
	// this is a guest user, no need to sign in
	$memberId=$userInfo['access_id'];
} else {
*/	
	if ($memberId=='')
		return API_EXIT(API_ERR, "Not signed in");

	if ($meetingInfo['host_id']!=$memberId) {
			
		// check if the member is an admin of the brand
		if ($memberPerm!='ADMIN' || $memberBrand!=$meetingInfo['brand_id']) 
		{
			return API_EXIT(API_ERR, "Not authorized");
		}		
	}
//}


$memberId=$meetingInfo['host_id'];
$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, "Member not found.");

$brand=new VBrand($meetingInfo['brand_id']);
if ($brand->Get($brandInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $brand->GetErrorMsg());		

$hookId=$brandInfo['hook_id'];
$hookInfo=array();
if ($hookId>0) {
	$hook=new VHook($hookId);
	$hook->Get($hookInfo);
}
set_time_limit(300);

GetArg("recordAudio", $recordAudio);
GetArg("number", $number);
GetArg("code", $code);
$number=RemoveSpacesFromPhone($number);
$code=RemoveNonNumbers($code);

if ($recordAudio=='1' && $number=='') {
	return API_EXIT(API_ERR, "Missing phone number.");
}

$hookName='start_recording';
if (isset($hookInfo[$hookName]) && $hookInfo[$hookName]!='') {
	$args=array();
	$args['meeting_id']=$meetingInfo['access_id'];
	$args['member_id']=$memberInfo['access_id'];
	$args['tele_number']=$number;
	$args['tele_code']=$code;
	if ($hook->CallHook($hookInfo[$hookName], $args, $resp)) {
		$rcode='';
		if (isset($resp['code'])) {
			$rcode=$resp['code'];
		}
		
		if ($rcode=='400') {
			if (isset($resp['message']))
				$meetingErrMsg=$resp['message'];
			else
				$meetingErrMsg="API Hook '$hookName' refused the request.";
			return API_EXIT(API_ERR, $meetingErrMsg);
		}
	} else {
		return API_EXIT(API_ERR, "API Hook '$hookName' failed to respond.");
	}
}


$hasError=false;
$errorMsg='';
$audioRecId='';
if ($recordAudio=='1') {
	require_once("dbobjects/vgroup.php");
	require_once("dbobjects/vteleserver.php");
	
	if ($number=='')
		return API_EXIT(API_ERR, "Missing phone number.");	
		
	//$recUrl=$brandInfo['aconf_rec_url'];

	$group=new VGroup($memberInfo['group_id']);
	$group->GetValue('teleserver_id', $teleServerId);
	if ($teleServerId=='0') {
		return API_EXIT(API_ERR, "No teleconference server is assigned.");			
	}
	$teleServer=new VTeleServer($teleServerId);
	$teleInfo=array();
	$teleServer->Get($teleInfo);
	if (!isset($teleInfo['server_url']) || $teleInfo['server_url']=='') {
		return API_EXIT(API_ERR, "Teleconference server is not found.");			
	}
	if ($teleInfo['can_record']!='Y') {
		return API_EXIT(API_ERR, "Teleconference recording is not enabled.");			
	}	
	
	// check if the audio conference is in progress
	$checkUrl=$teleInfo['server_url'];
	$checkUrl.="conference/";
//	$checkUrl.="participant/";
	$data="phone=$number&id=$code";
	$data.="&meetingid=".$meetingInfo['access_id'];
	$accessKey=$teleInfo['access_key'];
	if ($accessKey!='')
		$sig="signature=".md5($data.$accessKey);
	else
		$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
		
	$data.="&".$sig;
	
	$res=HTTP_Request($checkUrl, $data);
	LogRecord("$checkUrl\n$data\n$res\n");
	
	if (!$res) {
		return API_EXIT(API_ERR, "Couldn's get a response from teleconference server.");			
	}

	$xmlStatus='';
	$xmlInfo='';
	$current_tag='';
	$recordStatus='';
	$startTime='';
	$xml_parser  =  xml_parser_create("");
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
	xml_set_character_data_handler($xml_parser, "parse_xml");
	xml_parse($xml_parser, $res, true);
	xml_parser_free($xml_parser);			

	$errorMsg='';
	if ($xmlStatus=='0') {
		// OK
		if ($startTime=='0') {
			$errorMsg="Audio conference is not in progress.";
		} else 
		// need to check if the recording is already in progress.
		if ($recordStatus=='RECORD') {
			// terminate the recording if it is in progress.
			$stopUrl=$teleInfo['server_url'];
			$stopUrl.="conference/";
			$recId=$meetingInfo['audio_rec_id'];
			$data="phone=$number&id=$code&record=E&file=$recId";
			$data.="&meetingid=".$meetingInfo['access_id'];
			$accessKey=$teleInfo['access_key'];
			if ($accessKey!='')
				$sig="signature=".md5($data.$accessKey);
			else
				$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
				
			$data.="&".$sig;
			
			$res=HTTP_Request($stopUrl, $data, 'POST', 30);
			LogRecord("$stopUrl\n$data\n$res\n");
			sleep(3); // wait a bit to be safe
		}
		
	} elseif ($xmlStatus!='') {
		$errorMsg="Error: $xmlStatus $xmlInfo";

	} else {
		$errorMsg="Error: $res";
	}
	if ($errorMsg!='') {
		return API_EXIT(API_ERR, $errorMsg);			
	}	
}
/* start the web recording later to make sure the audio recording is started OK
if ($meeting->StartRecording($number, $code)!=ERR_NONE) {
	$errorMsg=$meeting->GetErrorMsg();
	LogError($errorMsg);
	return API_EXIT(API_ERR, $errorMsg);
}
*/
$recordingAudio=false;
$audioStartTime=0;
if ($recordAudio=='1') {
	// create a unique 8-digit id for audio recording
	// need to check if this is unique
	for ($i=0; $i<10; $i++) {
		$audioRecId=mt_rand(10000000, 99999999); // 8 digits
		$query="audio_rec_id = '$audioRecId'";
		VObject::Count(TB_MEETING, $query, $count);	
		if ($count==0)
			break;
	}
	
	$recUrl=$teleInfo['server_url'];
	$recUrl.="conference/";
	$data="phone=$number&id=$code&record=B&file=$audioRecId";
	$data.="&meetingid=".$meetingInfo['access_id'];
	$accessKey=$teleInfo['access_key'];
	if ($accessKey!='')
		$sig="signature=".md5($data.$accessKey);
	else
		$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
		
	$data.="&".$sig;

	$audioStartTime=time();	
	$res=HTTP_Request($recUrl, $data, 'POST', 30);
	LogRecord("$recUrl\n$data\n$res\n");
	
	if ($res && $res!='') {

		$pos1=strpos($res, "<?xml");
		// not a valid xml response
//			if ($pos1===false)
//				API_EXIT(API_ERR, $res);
		// remove any extra data before the xml data.
		if ($pos1>0) {
			$res=substr($res, $pos1);
		}	

		$xmlStatus='';
		$xmlInfo='';
		$current_tag='';
		$recordStatus='';
		$startTime='';		
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
		xml_set_character_data_handler($xml_parser, "parse_xml");
		xml_parse($xml_parser, $res, true);
		xml_parser_free($xml_parser);			

		$recordingAudio=true;
		if ($xmlStatus=='0') {
			// OK
		} elseif ($xmlStatus!='') {
			// some error occured, recording is not started
			if ($xmlStatus>=400)	
				$recordingAudio=false;
				
			$hasError=true;
			$errorMsg="Audio Recording Error: $xmlStatus $xmlInfo";
			LogError($errorMsg);

		} else {
			$hasError=true;
			$errorMsg="Audio Recording Error: $res";
			LogError($errorMsg);
		}
	
	} else {
		$hasError=true;
		$errorMsg="Couldn's get a response from the teleconference server.";
		LogError($errorMsg);
	}
	
}

if (!$hasError) {
	
	$audioOffset=0;
	if ($audioStartTime>0)
		$audioOffset=time()-$audioStartTime;
	
	if ($meeting->StartRecording($number, $code)!=ERR_NONE) {
		$errorMsg=$meeting->GetErrorMsg();
		LogError($errorMsg);
		return API_EXIT(API_ERR, $errorMsg);
	}

	$updateInfo=array();
	$updateInfo['audio_rec_id']=$audioRecId;
	$updateInfo['audio_sync_time']=$audioOffset;
	if ($meeting->Update($updateInfo)!=ERR_NONE) {
		$hasError=true;
		$errorMsg=$meeting->GetErrorMsg();
		LogError($errorMsg);

	}
}

if ($hasError) {
	// In case the audio recording is already started, we need to end the recording.
	if ($recordingAudio) {
		sleep(3);
		$data="phone=$number&id=$code&record=E&file=$audioRecId";
		$data.="&meetingid=".$meetingInfo['access_id'];
		if ($accessKey!='')
			$data.="&signature=".md5($data.$accessKey);
		else
			$data.="&nosig=1";
		
		$res=HTTP_Request($recUrl, $data, 'POST', 30);
		LogRecord("$recUrl\n$data\n$res\n");
	}
	// don't change the status or we can's stop the recording
	// allow the web recording to continue but show the audio recording error to the user
//	$udpateInfo=array();
//	$udpateInfo['status']='START';
//	$meeting->Update($udpateInfo);

	return API_EXIT(API_ERR, $errorMsg);
}	

$hookName='recording_started';

if (isset($hookInfo[$hookName]) && $hookInfo[$hookName]!='') {
	$args=array();
	$args['member_id']=$memberInfo['access_id'];
	$args['meeting_id']=$meetingInfo['access_id'];
	$args['tele_number']=$number;
	$args['tele_code']=$code;

	if ($hook->CallHook($hookInfo[$hookName], $args, $resp)) {

	} else {
		// don't return an error because the recording is already started
//		return API_EXIT(API_ERR, "API Hook '$hookName' failed to respond.");
	}
}


?>