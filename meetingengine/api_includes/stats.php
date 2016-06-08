<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;


if ($cmd=='SET_STATS') {
	$prefix=FILE_PREFIX;
//	$remoteIp=$_SERVER['REMOTE_ADDR'];
	
	GetArg("attendees", $attCount);
	GetArg("report_id", $reportId);
	GetArg("server_name", $serverName);
	
	if ($reportId=='' || $attCount=='' || $serverName=='')
		API_EXIT(API_ERR, 'Missing a required input parameter.');

	if ($serverName!=basename($serverName) || $reportId!=basename($reportId))
		API_EXIT(API_ERR, 'Invalid input parameter.');

	$theDir=GetServerDir($serverName);
	if (!MakeDir($theDir))
		API_EXIT(API_ERR, 'Could not create a directory.');
	
	$file=$theDir."/".$prefix.$reportId;
	$fp=@fopen($file, "w");
	if ($fp && flock($fp, LOCK_EX)) {
		fwrite($fp, "attendees=$attCount");
		flock($fp, LOCK_UN);
	}

	if ($fp) {
		fclose($fp);
		@chmod($file, 0777);
	} else {
		API_EXIT(API_ERR, 'Could not write to a file.');
	}
	
	API_EXIT(API_NOERR, 'OK');

} else if ($cmd=='GET_STATS') {
	
	GetArg("server_name", $serverName);
	if ($serverId=='') {
		API_EXIT(API_ERR, 'Missing an input parameter.');
	}

	$attCount=GetAttendees($serverName);
	
	echo ("OK\nattendees=$attCount");
	exit();
}

function GetServerDir($serverName) {
	$statsDir=GetStatsDir();
	// use the ip address as the directory key so it can account for all attendees on the server
	// in case the server is hosting mutliple sites with different host names
	$ip = gethostbyname($serverName); 
	$dir=$statsDir.md5($ip)."/";
	return $dir;
}	

// find out the number of connected attendees to the server of the given ip address
function GetAttendees($serverName) {
	$prefix=FILE_PREFIX;
	$expTime=30;

	$attCount=0;
	$dir=GetServerDir($serverName);
	if ($dh = @opendir($dir)) {
		clearstatcache();	// need this to make sure file info is not gotten from a cache
		while (($file = @readdir($dh)) !== false) {
			if (strpos($file, $prefix)!==false) {
				$theFile=$dir.$file;
				//echo ("file=$theFile time=".time()." mtime=".filemtime($theFile)."\r\n");
				// check if the file is still good
				if ((time()-filemtime($theFile)<=$expTime)) {
					// file has not expired
					$fp=fopen($theFile, "r");
					if ($fp && flock($fp, LOCK_SH)) {
						// read the file content
						$data=fread($fp, filesize($theFile));
						flock($fp, LOCK_UN);
						
						// parse the file content (attendees=xxx)
						$dataItems=explode("&", $data);
						$params=array();
						foreach ($dataItems as $ditem) {
							list($key, $val)=explode("=", $ditem);
							$params[$key]=$val;
						}
						
						// add the attendee count
						if (isset($params['attendees'])) {
							$attCount+=intval($params['attendees']);
						}
					}
					if ($fp)
						fclose($fp);
					
				} else {
					// delete outdated files
					@unlink($theFile);
				}
			}
		}
		closedir($dh);		
	}
	
	return $attCount;
}


function MakeDir($dir) {
	if (!is_dir($dir)) {		
		umask(0);
		if (mkdir($dir, 0777)) {
			$fp=@fopen("index.htm", "w");
			if ($fp) {
				fwrite($fp, "<html></html>");
				fclose($fp);
			}
		} else {
			return false;
		}
	}
	return true;
}


?>