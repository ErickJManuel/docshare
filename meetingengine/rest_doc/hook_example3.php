<?php
	// get input paramters if they are available
	if (isset($_GET['meeting_id']))
		$meetingId=$_GET['meeting_id'];
	else
		$meetingId='';
	if (isset($_GET['session_id']))
		$sessionId=$_GET['session_id'];
	else
		$sessionId='';
			
	// read the xml response template from a file	
	$xml=file_get_contents("hook_response.xml");
	
	// redirect the user to a client page
	$code="300";
	$message="";
	$link="http://www.mysite.com/mypage";
	// append input parameters if necessary
	$link.="?meeting_id=$meetingId&session_id=$sessionId";
	// make sure to encode special characters such as & and quotes.
	$link=htmlspecialchars($link);
	
	// replace the xml template tags with the actual values
	$xml=str_replace("[CODE]", $code, $xml);
	$xml=str_replace("[MESSAGE]", $message, $xml);
	$xml=str_replace("[LINK]", $link, $xml);
	
	// send response
	header("Content-Type: text/xml");
	echo $xml;
?>