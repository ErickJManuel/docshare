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


function LogToFile($msg, $fileName)
{
	@include_once "server_config.php";

	if (defined("LOG_DIR") && LOG_DIR!='') {
		//$errFile="_logs/errors.log";
		$today=date("y_m_d");
		$errFile=LOG_DIR.$fileName."_$today.log";
		
		$fp=@fopen($errFile, "a");
		if ($fp) {
			$datestr=date("Y F d H:i:s");
			if (isset($_SERVER['REMOTE_ADDR']))
				$userip=$_SERVER['REMOTE_ADDR'];
			else
				$userip='';
				
			if (isset($_SERVER['HTTP_REFERER']))
				$url=$_SERVER['HTTP_REFERER'];
			else
				$url='';
				
			if (isset($_SERVER['QUERY_STRING']))
				$query=$_SERVER['QUERY_STRING'];
			else
				$query='';
			
			$logstr="${datestr} ${url} ${userip} ${query} \"$msg\"";
			
			// find the file and line number of where the error occurs
			if ($fileName=='error') {
				$debugInfo=debug_backtrace();
				$count=count($debugInfo);
				for ($i=0; $i<$count; $i++) {
					$func=$debugInfo[$i]["function"];
					if ($func=="API_EXIT" || $func=="LogError" || $func=="LogToFile") {
						continue;
					}
					$debugLine=$debugInfo[$i]["line"];
					$debugFile=$debugInfo[$i]["file"];
					$debugFunc=$debugInfo[$i]["function"];
					$logstr.=" in line $debugLine $debugFunc() '$debugFile'\r\n";
				}
			}
			$logstr.="\r\n";
			fwrite($fp, $logstr);
			fclose($fp);
		}
	}
}

function LogAccess($msg)
{
	LogToFile($msg, 'access');
}

function LogError($msg)
{
	LogToFile($msg, 'error');
}

function LogRecord($msg)
{
	LogToFile($msg, 'record');
}


?>