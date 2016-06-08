<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common.php");
require_once("dbobjects/vwebserver.php");

GetArg('message', $message);
ShowMessage($message);

if (GetArg('ret', $return)) {
	GetArg('retLabel', $retLabel);
	if ($retLabel=='')
		$retLabel="Return";
	$return=VWebServer::DecodeDelimiter2($return);
//	$return=str_replace("^", "?", $return);
	echo "<div class='ret_link'><a target=${GLOBALS['TARGET']} href=\"".$return."\">$retLabel</a></div>";
}

?>