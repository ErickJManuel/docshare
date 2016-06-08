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
		

	if (isset($GET_VARS['evtDir']) && $GET_VARS['evtDir']!='') {
		$evtDir=basename($GET_VARS['evtDir']);
		if ($evtDir[strlen($evtDir)-1]!='/')
			$evtDir.="/";
	} else
		ErrorExit("missing input");
		
	$flashFile='';
	if (isset($GET_VARS['flashFile']))
		$flashFile=$GET_VARS['flashFile'];
	
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

	$theDir=$dir.$evtDir.$gSlidesDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
		
	$theDir=$dir.$evtDir.$gPollsDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);

	$theDir=$dir.$evtDir.$gDocDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
	
	sleep(1);	
	$theDir=$dir.$evtDir;
	if (!MyRmDir($theDir, $errMsg))
		ErrorExit ("couldn't delete ".$theDir." error=".$errMsg);
/*
	$theFile=$dir.$gHostFile;
	if (file_exists($theFile))
		@unlink($theFile);
		
	$theFile=$dir.$flashFile;
	if ($flashFile!='' && file_exists($theFile))
		@unlink($theFile);
*/
	echo ("OK");
	

?>
