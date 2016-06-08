<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

		
	$xml="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<response xmlns=\"http://schemas.persony.com/wc2/rest/1.0\">
	<code>[CODE]</code>
	<message>[MESSAGE]</message>
	<link>[LINK]</link>
</response>
";
	
	$code="400";
	$message="Abort.";
	$link='';
	
	$xml=str_replace("[CODE]", $code, $xml);
	$xml=str_replace("[LINK]", $link, $xml);
	$xml=str_replace("[MESSAGE]", $message, $xml);
	
	header("Content-Type: text/xml");

	echo $xml;
?>