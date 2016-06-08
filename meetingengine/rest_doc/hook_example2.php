<?php	
	// read the xml response template from a file	
	$xml=file_get_contents("hook_response.xml");
	
	// deny the event and display a message to the user
	$code="400";
	$message="Abort.";
	$link="";
	
	// replace the xml template tags with the actual values
	$xml=str_replace("[CODE]", $code, $xml);
	$xml=str_replace("[MESSAGE]", $message, $xml);
	$xml=str_replace("[LINK]", $link, $xml);
	
	// send response
	header("Content-Type: text/xml");
	echo $xml;
?>