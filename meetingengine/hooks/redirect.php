<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

	// these paramters may be passed in
	if (isset($_GET['meeting_id']))
		$meetingId=$_GET['meeting_id'];
	else
		$meetingId='';
	if (isset($_GET['member_id']))
		$memberId=$_GET['member_id'];
	else
		$memberId='';	
	if (isset($_GET['session_id']))
		$sessionId=$_GET['session_id'];
	else
		$sessionId='';
		
	$xml="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<response xmlns=\"http://schemas.persony.com/wc2/rest/1.0\">
	<code>[CODE]</code>
	<message>[MESSAGE]</message>
	<link>[LINK]</link>
</response>
";
	
	$code="300";
	$message="";
	$url="http://www.google.com/"; // put your rediret url here. append meeting_id and other parameters if necessary.
	$link=htmlspecialchars($url);	// make sure to encode special characters such as & and quotes.
	
	$xml=str_replace("[CODE]", $code, $xml);
	$xml=str_replace("[LINK]", $link, $xml);
	$xml=str_replace("[MESSAGE]", $message, $xml);
	
	header("Content-Type: text/xml");

	echo $xml;
?>