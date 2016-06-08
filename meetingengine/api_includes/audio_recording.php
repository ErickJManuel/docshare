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

// meetingInfo may have been set in the embedding script
if (!isset($meetingInfo))
	require_once('api_includes/meeting_common.php');
require_once('dbobjects/vbrand.php');
require_once('dbobjects/vuser.php');
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vteleserver.php");

$xmlStatus='';
$xmlInfo='';
$current_tag='';
$mp3FileSize='';
function start_xml_tag($parser, $name, $attribs) {
	global $xmlStatus, $xmlInfo, $current_tag;
	$current_tag=$name;
}

function end_xml_tag($parser, $name) {
	global $xmlStatus, $xmlInfo, $current_tag;
	$current_tag='';
	
}
function parse_xml($parser, $data) {
	global $xmlStatus, $xmlInfo, $current_tag, $mp3FileSize;
	
	switch ($current_tag) {
		case "cm_status":
			$xmlStatus=$data;
			break;
		case "cm_info":
			$xmlInfo=$data;
			break;
		case "FileSize":
			$mp3FileSize=$data;
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
	return API_EXIT(API_ERR, "Meeting not set.");
	
GetArg('number', $number);
GetArg('code', $code);
$number=RemoveSpacesFromPhone($number);
$code=RemoveNonNumbers($code);

if ($number=='')
	return API_EXIT(API_ERR, "Missing input parameter 'number'");		
if ($code=='')
	return API_EXIT(API_ERR, "Missing input parameter 'code'");		
	
$hostId=$meetingInfo['host_id'];
$hostInfo=array();
$host=new VUser($hostId);
if ($host->Get($hostInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $host->GetErrorMsg());

if (GetArg('user', $userId) && $userId!='') {
	if ($userId!=$hostInfo['access_id']) {
		return API_EXIT(API_ERR, "Not authorized.");
	}
} else {
	return API_EXIT(API_ERR, "Missing input parameter 'user'");		
}

$brand=new VBrand($hostInfo['brand_id']);
if ($brand->Get($brandInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $brand->GetErrorMsg());
	
$group=new VGroup($hostInfo['group_id']);
$group->GetValue('teleserver_id', $teleServerId);
$teleServer=new VTeleServer($teleServerId);
$teleInfo=array();
$teleServer->Get($teleInfo);
$recUrl=$teleInfo['server_url'];
$accessKey=$teleInfo['access_key'];

//$recUrl=$brandInfo['aconf_rec_url'];
if ($recUrl=='')
	return API_EXIT(API_ERR, "Teleconference server url is not set.");
	
$recId=$meetingInfo['audio_rec_id'];
if ($recId=='')
	return API_EXIT(API_ERR, "Recording id is not set.");
/*
if ($teleInfo['rec_outbound']=='Y') {
	
	$recUrl.="?cmd=record&key=".md5('record'.$teleInfo['access_key']);
	$recUrl.="&number=1$number&mod=$code&name=$recId";
	
	if ($cmd=='CHECK_RECORDING_STATUS') {
		$recUrl.="&flag=C";		
	} else if ($cmd=='CHECK_RECORDING_FILE') {
		$recUrl.="&flag=F";
	} else if ($cmd=='CREATE_RECORDING_FILE') {
		$recUrl.="&flag=M";	
	} else if ($cmd=='GET_RECORDING_FILE') {
		$recUrl.="&flag=G";
	}
	
	if ($cmd=="GET_RECORDING_FILE") {
		// get file
		header("Location: $recUrl");
		API_EXIT(API_NOMSG);
		
	} else {
		
		//$res=HTTP_Request($recUrl, '', 'GET', 60);
		$res=@file_get_contents($recUrl);
		if ($res==false) {
			return API_EXIT(API_ERR, "Couldn't get response from server.");		
		}
	}
	
	if ($cmd=="CHECK_RECORDING_STATUS") {
		if (strpos($res, "OK Y")===0) {
			return API_EXIT(API_NOERR, "OK");		
		}
		if (strpos($res, "OK N")===0) {
			return API_EXIT(API_ERR, "Audio recording is not in progress.");		
		}
	}
	
	if (strpos($res, "OK")===0) {
		return API_EXIT(API_NOERR, $res);		
	}
	
	if (strpos($res, "ERROR")===0) {
		return API_EXIT(API_ERR, $res);		
	}
	return API_EXIT(API_ERR, "Audio recording server returns ".substr($res, 0, 64));		
	
} else {
*/	
	$args="phone=$number&id=$code&file=$recId";
	if ($accessKey!='')
		$sig="signature=".md5($args.$accessKey);
	else
		$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
	
	$args.="&".$sig;
		
	$recUrl.="conference/?".$args;
	
	if ($cmd=='CHECK_RECORDING_FILE') {
		$recUrl.="&mp3=S";		
	} elseif ($cmd=='GET_RECORDING_FILE') {		
		$recUrl.="&mp3=F";		
	} else {
		return API_EXIT(API_ERR, "cmd $cmd not supported.");
	}

	if ($cmd=='GET_RECORDING_FILE') {
		header("Location: $recUrl");
		API_EXIT(API_NOMSG);
		
	} else {
		$res=HTTP_Request($recUrl, '', 'GET', 60);
		
		if ($res && $res!='') {	
			ParseXmlData($res);
			if ($xmlStatus=='0')
				return API_EXIT(API_NOERR, "OK");
			else	
				return API_EXIT(API_ERR, "Teleconference server returns ".$xmlInfo);		
			
		} else {
			return API_EXIT(API_ERR, "Couldn't get a response from the teleconference server.");		
		}
	}
	
//}



?>