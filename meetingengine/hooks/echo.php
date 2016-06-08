<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


	if (isset($_GET['meeting_id']))
		$meetingId=$_GET['meeting_id'];
	else
		$meetingId='';
	if (isset($_GET['member_id']))
		$memberId=$_GET['member_id'];
	else
		$memeberId='';
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
	
	$code="400";
//	$message=rawurlencode($_SERVER['QUERY_STRING']);
	$message="meeting_id=".$meetingId." member_id=".$memberId." session_id=".$sessionId;
	$link='';
	$message=htmlspecialchars('http://04-w1.yourcall.com/CA_WETestingV2/TurnInKey.aspx?action=personyWithAOC&host_url=http%3a%2f%2fweplus1.conferenceamerica.com%2fstaging%2f6702921%2fvmeetings%2f6462404%2fviewer.php%3fhost_id%3d11%26meeting_id%3d6462404');
	$message=htmlspecialchars('http://04-w1.yourcall.com/CA_WETestingV2/TurnInKey.aspx?host_url=http%3a%2f%2fweplus1.conferenceamerica.com%2fstaging%2f6702921%2fvmeetings%2f6462404%2fviewer.php%3fhost_id%3d11%26meeting_id%3d6462404&action=personyWithAOC');
	
	$xml=str_replace("[CODE]", $code, $xml);
	$xml=str_replace("[LINK]", $link, $xml);
	$xml=str_replace("[MESSAGE]", $message, $xml);
	
	header("Content-Type: text/xml");

	echo $xml;

	
?>