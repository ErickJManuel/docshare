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
	require_once($includeFile);

	$file="";
	if (isset($GET_VARS['file'])) {
		$file=$GET_VARS['file'];
	}
	if ($file=='')
		exit();
		
	$timeOut=0;
	if (isset($GET_VARS['timeout']))
		$timeOut=(int)$GET_VARS['timeout'];
	
	if ($timeOut>$gMaxWaitTime)
		$timeOut=$gMaxWaitTime;

	if ($timeOut>0) {
		$startTime=time();
		$canusleep=-1;

		while (1) {
			if ($fp=@fopen($file, "r"))
				break;
			MSleep($gCheckFileDelay, $canusleep);
			$currTime=time();
			if (($currTime-$startTime)>$timeOut)
				break;
		}
	} else {
		$fp=@fopen($file, "r");
	}
	if (flock($fp, LOCK_SH)){
		$fize=filesize($file);
		$content=fread($fp, $fize);
		flock($fp, LOCK_UN);
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: " . $fize);
		header("Content-Type: text/plain");
		echo $content;
	}
	if ($fp)
		fclose($fp);

?>