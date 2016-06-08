<?php
/**
 * @package     Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 *
 * Get reports of past meeting session attendees
 * 
 */

// Replace with your api url, key and brand id with those from the Administration/API page
$apiUrl="http://license.persony.net/wc2/rest/";
$apiKey='aafc799f2fbbfadxxxxxxxxxxx';
$brand='555xxxx';

$obj="sessions";	// api object
$apiObjUrl=$apiUrl.$obj."/";

// api pramaters to get all meeting sessions for the brand
// from_date and to_date should be in GMT (UTC) time
$args=array(
	"brand" => $brand,
	"from_date" => "2009-02-01 08:00:00",	// get reports for Jan. 2009 UTC-08:00 (PST)
	"to_date" => "2009-03-01 07:59:59",
	"host_login" => "name@company.com",	// get reports for this member
	);

$data='';
foreach($args as $key => $value)
{
	if ($data!='')
		$data.="&";
	
	$value=urlencode($value);	// encode the request data
	$data.= "$key=$value";
}

// compute and append signature	
// for GET requests, the signature should be computed from the url encoded values	
$query=$data."&signature=".md5($data.$apiKey);

// since each api request can only return up to 100 sessions, we need to use a loop to get all sessions
$sessions=array();
$index=-1;
while (1) {
	
	$resp=file_get_contents($apiObjUrl."?".$query);	
	if (!$resp) {
		die("Couldn't get a response from the API object.");
	}
	
	// parse the xml response to get a list of session ids and the start/next value		
	$start=$next=$currentName='';
	$inSession=false;
	$xml_parser  =  xml_parser_create("");
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
	xml_set_character_data_handler($xml_parser, "parse_xml");
	xml_parse($xml_parser, $resp, true);
	xml_parser_free($xml_parser);	
			
	if ($start=='' || $next=='') {
		die("Invalid xml response encountered.");
	}
	
	// no more records are found
	if ($next=="-1") {
		break;			
	}
			
	// get the next set of session records
	$nextData=$data."&start=".$next;
	$query=$nextData."&signature=".md5($nextData.$apiKey);
	
}

// Go through each session to get the session's attendees
$obj="attendees";
$apiObjUrl=$apiUrl.$obj."/";
foreach ($sessions as $aSession) {
	
	$sessId=$aSession['session_id'];
	$data="brand=".$brand."&session_id=".$sessId;
	$query=$data."&signature=".md5($data.$apiKey);
	
	// since each api request can only return up to 100 attendees, we need to use a loop to get all attendees
	$attendees=array();
	$index=-1;
	while (1) {		
		
		$resp=file_get_contents($apiObjUrl."?".$query);	
		if (!$resp) {
			die("Couldn't get a response from the API object.");
		}
		// parse the xml response to get a list of attendees and the start/next value		
		$start=$next=$currentName='';
		$inAttendee=false;
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
		xml_set_character_data_handler($xml_parser, "parse_xml");
		xml_parse($xml_parser, $resp, true);
		xml_parser_free($xml_parser);
		
		if ($start=='' || $next=='') {
			die("Invalid xml response encountered.");
		}
		
		// no more records are found
		if ($next=="-1") {
			break;			
		}
				
		// get the next set of session records
		$data="brand=".$brand."&session_id=".$sessId."&start=".$next;
		$query=$data."&signature=".md5($data.$apiKey);
		
	}
	
	
	// print out the session's attendees data
	// each attendee record is an associative array with key/value pairs
	echo "<b>session:</b> ".$sessId;
	echo " <b>meeting:</b> ".$aSession['meeting_id']." ".$aSession['meeting_title']." <b>host:</b> ".$aSession['host_login'];
	echo "<br>Attendees: ".count($attendees)."<br>\n";
	echo "<pre>\n";
	foreach ($attendees as $anAttendee) {
		print_r($anAttendee);
		echo "\n";	
	}
	echo "</pre>\n";
			
}

// xml parser functdions
// all global varibles should be set in the caller
function start_xml_tag($parser, $name, $attribs) {
	global $start, $next, $currentName, $index;
	global $sessions, $attendees, $inAttendee, $inSession;
	$currentName=$name;
	if ($name=="sessions" || $name=="attendees") {
		foreach ($attribs as $key => $value) {
			if ($key=='start')
				$start=$value;
			elseif ($key=='next')
				$next=$value;
		}

	} else if ($name=="attendee") {
		$inAttendee=true;
		$attendees[]=array();
		$index++;
	} else if ($name=="session") {
		$inSession=true;
		$sessions[]=array();
		$index++;
	}
}

function end_xml_tag($parser, $name) {
	global $currentName, $index, $inAttendee, $inSession;
	$currentName = '';
	if ($name=="attendee")
		$inAttendee=false;
	elseif ($name=="session")
		$inSession=false;
}
function parse_xml($parser, $data) {
	global $currentName, $sessions, $index;
	global $attendees, $inAttendee, $inSession;
	
	if ($inSession && (
		$currentName=='session_id' ||
		$currentName=='host_login' ||
		$currentName=='meeting_title' ||
		$currentName=='meeting_id')) 
	{
		$sessions[$index][$currentName].=$data;
	} else if ($inAttendee && (
		$currentName=="attendee_id" ||
		$currentName=="session_id" ||
		$currentName=="start_time" ||
		$currentName=="end_time" ||
		$currentName=="break_time" ||
		$currentName=="webcam_time" ||
		$currentName=="member_id"))
	{
		$attendees[$index][$currentName].=$data;
	}
}

?>