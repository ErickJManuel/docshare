<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	include_once $includeFile;
	
	$id='';
	if (isset($GET_VARS['id']))
		$id=$GET_VARS['id'];
	$code='';
	if (isset($GET_VARS['code']))
		$code=$GET_VARS['code'];

	if (!IsAuthorized($id, $code))
		ErrorExit("Not authorized.");

	$dir="";
	if (isset($GET_VARS['dir'])) {
		$dir=$GET_VARS['dir'];
		if ($dir[strlen($dir)-1]!='/')
			$dir.="/";
	}
	
	if ($dir=='')
		ErrorExit("missing input");
				
	$theDir=$dir.$gMeetingsDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
		
	$theDir=$dir.$gVLibgDir;			
	// delete sub-folders in the library folder
	if ($dh = @opendir($theDir)) {
		$dirList = array();
		while (($file = @readdir($dh)) !== false) {
			if ($file!="." && $file!="..") {
				$theFile=$theDir.$file;
				if (is_dir($theFile))		
					$dirList[] = $theFile;
			}
		}
		closedir($dh);
		
		foreach($dirList as $adir) {
			if (!MyRmDir($adir, $errMsg))
				break;
		}		
	}
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

/*
	$theDir=$dir.$gSlidesDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
		
	$theDir=$dir.$gLogsDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
*/
	$theDir=$dir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

	echo ("OK");
   
?>
