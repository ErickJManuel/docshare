<?php
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * 
 */
//define("__SESSION_DESCR_FILENAME__", "iph_session.xml");
//define("__SWF_DESCR_FILENAME__", "descr.xml");
define("__IPHONE_FILE_PREFIX__", "iph_");
define("__IPHONE_START__", "iph.start");

$debug = 0;
/**
 * 
 * @param $str
 * @return unknown_type
 */
function logDebug($str) {
	global $debug;	
	if($debug) {
		echo "$str<br>";
	}
}

/**
 * 
 * @param $vframespath
 * @return unknown_type
 */
/*
function getIphoneVframesPath($vframespath) {
	
	$path=$vframespath;	
	if(!file_exists($path))
	{
		mkdir($path);		
	}	
	return $path;
}
*/

?>