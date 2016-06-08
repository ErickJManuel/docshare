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
		
	$evtDir="";
	if (isset($GET_VARS['evtDir']) && $GET_VARS['evtDir']!='') {
		$evtDir=basename($GET_VARS['evtDir']);
		if ($evtDir[strlen($evtDir)-1]!='/')
			$evtDir.="/";
	} else
		ErrorExit("missing input");

/*		
	$flashFile='';
	if (isset($GET_VARS['flashFile']))
		$flashFile=$GET_VARS['flashFile'];
*/	
	$theDir=$dir.$evtDir.$gEvtDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
		
	$theDir=$dir.$evtDir.$gMediaDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

	$theDir=$dir.$evtDir.$gFramesDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
		
	$theDir=$dir.$evtDir.$gAttDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
/*
	$theDir=$dir.$evtDir.$gSlidesDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
		
	$theDir=$dir.$evtDir.$gPollsDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
*/
	$theDir=$dir.$evtDir.$gDocDir;
	// delete sub-folders in the document folder
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
		
	$theDir=$dir.$evtDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
/*
	$theDir=$dir.$gRegisDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
*/

	$theDir=$dir.$gTempDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

	$theDir=$dir.$gCacheDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

	$theDir=$dir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

	echo ("OK");
   
?>
