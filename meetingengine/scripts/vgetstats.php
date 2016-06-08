<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


	$includeFile='vinclude.php';
	require_once $includeFile;
	
	chdir("../");
	
	$id='';
	if (isset($GET_VARS['id']))
		$id=$GET_VARS['id'];
	$code='';
	if (isset($GET_VARS['code']))
		$code=$GET_VARS['code'];

	if (!IsAuthorized($id, $code))
		ErrorExit("Not authorized.");
		
	$dir="attendees/";
	
	$prefix="f_";
	$expTime=30;

	$attCount=0;
	$attList=array();
	if ($dh = @opendir($dir)) {
		while (($file = @readdir($dh)) !== false) {
			if (strpos($file, $prefix)!==false) {
				$theFile=$dir.$file;
				// check if the file is still good
				if ((time()-filemtime($theFile)<=$expTime)) {
					// file has not expired
					$fp=fopen($theFile, "r");
					if ($fp && flock($fp, LOCK_SH)) {
						// read the file content
						$attList[$attCount]=fread($fp, filesize($theFile));
						flock($fp, LOCK_UN);
						
						// add the attendee count
						$attCount++;
					}
					if ($fp)
						fclose($fp);
					
				}
			}
		}
		closedir($dh);		
	}	
	
	echo "OK\n";
	foreach ($attList as $att) {
		echo $att."\n";
	}

?>