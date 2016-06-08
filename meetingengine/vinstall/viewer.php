<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("site_config.php"); 

if (isset($_GET['response'])) {
	// process the testing results and select a server to connect to
	include_once("scripts/vselectserver.php");
	
	//die($_SERVER['QUERY_STRING']);
} else if (isset($_GET['check'])) {
	// If caching servers are used for the meeting, 
	// run a speed test on the client viewer to check the connection latency to each server
	// Send the test results back to this script at end of the test.
	include_once("scripts/vcheckservers.php");
	exit();
}

$swfFile="viewer.swf"; 
$baseDir="";
$apiUrl=$serverUrl."api.php"; 
$arg="MeetingServer=$apiUrl&MeetingID=".$_GET['meeting_id']; 

if (isset($_GET['server_id']))
	$arg.="&ServerID=".$_GET['server_id'];

if (isset($_GET['server'])) {
	// this server is being used as a caching server. Set the caching directory
	$arg.="&CacheDir=".$_GET['server'];
} else {
	// this server is being used as a master server.	
}
include("scripts/viewer.php");


?>