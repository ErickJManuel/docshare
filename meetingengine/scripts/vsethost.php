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

		
	$dir='';
	if (isset($GET_VARS['dir'])) {
		$dir=$GET_VARS['dir'];
		if ($dir[strlen($dir)-1]!='/')
			$dir.="/";
	} else {
		ErrorExit("missing input");
	}
	$hostId='';
	if (isset($GET_VARS['hostId']))
		$hostId=$GET_VARS['hostId'];
	else
		ErrorExit("missing input");
		
	$keyCode='';
	if (isset($GET_VARS['keyCode']))
		$keyCode=$GET_VARS['keyCode'];
	else
		ErrorExit("missing input");
		
	$registration='';
	if (isset($GET_VARS['registration']))
		$registration=$GET_VARS['registration'];
/*		
	$meetingId='';
	if (isset($GET_VARS['meetingId']))
		$meetingId=$GET_VARS['meetingId'];
	else
		ErrorExit("missing input meetingId");
*/		
	$server='';
	if (isset($GET_VARS['server']))
		$server=$GET_VARS['server'];
	else
		ErrorExit("missing input server");

//	$sessionDir='evt';
	if (isset($GET_VARS['sessionDir']))
		$sessionDir=$GET_VARS['sessionDir'];
//	else
//		ErrorExit("missing input");
	
	$swfServer='';
	if (isset($GET_VARS['swfServer']))
		$swfServer=$GET_VARS['swfServer'];

/*
	$ext=".php";
	if (isset($GET_VARS['ext']))
		$ext=".".$GET_VARS['ext'];
*/			
	$scriptFile='./';
	if (isset($GET_SERVER['SCRIPT_FILENAME']))
		$scriptFile=$GET_SERVER['SCRIPT_FILENAME'];
	$mode=fileperms($scriptFile);
	
	$meetingType="";
	if (isset($GET_VARS['meetingType']))
		$meetingType=$GET_VARS['meetingType'];

	if (isset($GET_VARS['mode'])) {
		if ($GET_VARS['mode']=='755')
			$mode=0755;
		else if ($GET_VARS['mode']=='777')
			$mode=0777;
	}

	$outFile=$dir.$gHostFile;
	umask(0);
	$content="<?php \$gHostID=\"".$hostId."\"; \$gKeyCode=\"".$keyCode."\"; ";
//	$content.="\$gMeetingID=\"$meetingId\"; ";
	$content.="\$gServerUrl=\"$server\"; ";
	
	// shouldn't need this anymore
//	if (isset($sessionDir))
//		$content.="\$gSessionDir=\"$sessionDir\"; ";

	$content.="\$gMeetingType=\"$meetingType\"; ";
	if ($swfServer!='')
		$content.="\$gSwfServer=\"$swfServer\"; ";
	
	if ($registration=='1')
		$content.="\$gVerifyRegistration=true; ";
		
	$content.="?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't open file ".$outFile);
		
	echo ("OK");
   
?>
