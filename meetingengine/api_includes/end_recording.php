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

if (!isset($meetingInfo['id']))
	require_once('api_includes/meeting_common.php');

if (!isset($meeting)) {
	require_once("dbobjects/vmeeting.php");
	$meeting=new VMeeting($meetingInfo['id']);
}

//require_once('api_includes/meeting_common.php');
require_once('dbobjects/vhook.php');
require_once('dbobjects/vbrand.php');

$xmlStatus='';
$xmlInfo='';
$current_tag='';
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
	
	switch ($current_tag) {
		case "cm_status":
			$xmlStatus=$data;
			break;
		case "cm_info":
			$xmlInfo=$data;
			break;
	}
}
function ParseXmlData($xmlData) {
	$xml_parser  =  xml_parser_create("");
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
	xml_set_character_data_handler($xml_parser, "parse_xml");
	xml_parse($xml_parser, $xmlData, true);
	xml_parser_free($xml_parser);
}

if ($errMsg!='')
return API_EXIT(API_ERR, $errMsg);
	
if (!isset($meetingInfo['id']))
return API_EXIT(API_ERR, "Meeting id not set.");

// ignore in case this is called multiple times
if ($meetingInfo['status']!='START_REC') {
	LogRecord("Ignore attempt to stop a recording not in progress");
	return API_EXIT(API_NOERR, "");
}
// the session may have expired when this is called.
// we want to still allow the meeting to end even if the session has expired.
// these values may be undefined

// enable session checking because we use a token to keep the session alive
$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in");

if ($meetingInfo['host_id']!=$memberId) {
		
	// check if the member is an admin of the brand
	if ($memberPerm!='ADMIN' || $memberBrand!=$meetingInfo['brand_id']) 
	{
		return API_EXIT(API_ERR, "Not authorized");
	}		
}


GetArg("number", $number);
GetArg("code", $code);
$number=RemoveSpacesFromPhone($number);
$code=RemoveNonNumbers($code);

$brandId=$meetingInfo['brand_id'];
$brand=new VBrand($brandId);
$brand->Get($brandInfo);		

$hookId=$brandInfo['hook_id'];
$hookInfo=array();
if ($hookId>0) {
	$hook=new VHook($hookId);
	$hook->Get($hookInfo);
}

set_time_limit(300);

$hookName='end_recording';
$recId=$meetingInfo['audio_rec_id'];

// the rec phone number and code may not be passed in
// assuming the rec number is the same as the one assigned to the user, we can fix it here 
if ($recId!='' && ($number=='' || $code=='')) {
	$host=new VUser($meetingInfo['host_id']);
	$host->Get($hostInfo);		
	$number=RemoveSpacesFromPhone($hostInfo['conf_num']);
	$code=RemoveNonNumbers($hostInfo['conf_mcode']);
}

if (isset($hookInfo[$hookName]) && $hookInfo[$hookName]!='') {
	$args=array();
	$args['member_id']=$memberInfo['access_id'];
	$args['meeting_id']=$meetingInfo['access_id'];
	$args['tele_number']=$number;
	$args['tele_code']=$code;
	if ($hook->CallHook($hookInfo[$hookName], $args, $resp)) {
		
	} else {
		// ignore error
//		return API_EXIT(API_ERR, "API Hook '$hookName' failed to respond.");
	}			
}

$audioSyncTime=0;
if ($recId!='' && $number!='') {
	require_once("dbobjects/vgroup.php");
	require_once("dbobjects/vteleserver.php");
	
	$host=new VUser($meetingInfo['host_id']);
	$host->GetValue('group_id', $groupId);
	$group=new VGroup($groupId);
	$group->GetValue('teleserver_id', $teleServerId);
	$teleServer=new VTeleServer($teleServerId);
	$teleInfo=array();
	$teleServer->Get($teleInfo);
	$recUrl=$teleInfo['server_url'];
	$accessKey=$teleInfo['access_key'];
	if ($teleInfo['rec_sync_time']!='')
		$audioSyncTime=(int)$teleInfo['rec_sync_time'];
	
	$recUrl.="conference/";
	$data="phone=$number&id=$code&record=E&file=$recId";
	$data.="&meetingid=".$meetingInfo['access_id'];
	if ($accessKey!='')
		$data.="&signature=".md5($data.$accessKey);
	else
		$data.="&nosig=1";

	$res=HTTP_Request($recUrl, $data, 'POST', 30);
	if (!$res) {
		sleep(2);
		$res=HTTP_Request($recUrl, $data, 'POST', 30);
	}
	LogRecord("$recUrl\n$data\n$res\n");
	
	$xmlStatus='';
	$xmlInfo='';
	if ($res && $res!='') {
		ParseXmlData($res);
		// ignore error in case the call is dropped
					
		if ($xmlStatus!='0') {
			$xmlInfo=str_replace("\n", " ", $xmlInfo);
			$xmlInfo=str_replace("\r", " ", $xmlInfo);
			LogError("Error: $xmlStatus $xmlInfo");
		}

	} else {
		LogError("Couldn't get ".$recUrl);		
	}

}

$recMeetingId='';
$recMeetingTitle='';
if ($meeting->EndRecording($number, $code, $recMeetingId, $recMeetingTitle, $audioSyncTime)!=ERR_NONE) {
	// ignore error because the audio recording has stopped
//	return API_EXIT(API_ERR, $meeting->GetErrorMsg());
}

// not sure we need to sleep here
//sleep(15);
$hookName='recording_ended';

if (isset($hookInfo[$hookName]) && $hookInfo[$hookName]!='') {
	$args=array();
	$args['member_id']=$memberInfo['access_id'];
	$args['meeting_id']=$meetingInfo['access_id'];
//	$args['session_id']=$recId;
	$args['tele_number']=$number;
	$args['tele_code']=$code;
	if ($hook->CallHook($hookInfo[$hookName], $args, $resp)) {

	} else {
		// ignore error
//		return API_EXIT(API_ERR, "API Hook '$hookName' failed to respond.");
	}
}

if ($recId!='' && $number!='') {
	
	if ($xmlStatus=='0') {
		require_once('dbobjects/vmeeting.php');
		// start mp3 encoding
		$data="phone=$number&id=$code&mp3=C&file=$recId";
//		$data.="&meetingid=".$meetingInfo['access_id'];
		$recording=new VMeeting($recMeetingId);
		$recording->GetValue('access_id', $recAccessId);
		$data.="&meetingid=".$recAccessId;
		
		if ($accessKey!='')
			$data.="&signature=".md5($data.$accessKey);
		else
			$data.="&nosig=1";
		
		sleep(3);
		$res=HTTP_Request($recUrl, $data, 'POST', 30);
		LogRecord("$recUrl\n$data\n$res\n");
		
		// ignore any error as we can restart mp3 processing later if there is an error
	}	
	
}

return API_EXIT(API_NOERR, "'$recMeetingTitle' has been created.");



?>