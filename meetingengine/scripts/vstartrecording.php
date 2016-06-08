<?php 

// (c)Copyright 2007, Persony, Inc. All rights reserved.

	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	@include_once($includeFile);
	@include_once($gHostFile); //defined in vinclude.php

	$evtDir="evt/";
	if (isset($GET_VARS['evtdir']))
		$evtDir=$GET_VARS['evtdir'];
	elseif (isset($gSessionDir))
		$evtDir=$gSessionDir."/";
		
	if ($evtDir=='')
		ErrorExit("missing parameter 'evtdir'");
	if ($evtDir[strlen($evtDir)-1]!='/')
		$evtDir.="/";

	$userid="";
/*
	if (isset($GET_VARS['user_id']))
		$userid=$GET_VARS['user_id'];
	if ($userid=='')
		ErrorExit("Missing user_id");
*/	
	$meetingId='';
	if (isset($GET_VARS['meeting_id']))
		$meetingId=$GET_VARS['meeting_id'];
	if ($meetingId=='')
		ErrorExit("Missing meeting_id");
/*		
	$getAttUrl=$gServerUrl."?cmd=GET_ATTENDEE_LIST&meeting="
		.$meetingId."&attendee_id=".$userid;


	if ($output = @file_get_contents($getAttUrl)) {
		// ignore the error as it is not critical and we don't want to stop the audio recording which maybe in progress already
//		if (strpos($output, "<error")!==false)
//			ErrorExit($output);
	} else {
//		ErrorExit("Can't get response from ".$getAttUrl);
	}
*/

	$output='';
	$VARS=array();
	$VARS['meeting_id']=$meetingId;
	$VARS['evtdir']=$evtDir;
	
	$t1=$t2='';

	if (isset($GET_VARS['tele_num']))
		$t1=$GET_VARS['tele_num'];
	if (isset($GET_VARS['tele_mcode']))
		$t2=$GET_VARS['tele_mcode'];
			
	if ($t1!='')
		$VARS['tele_num']=$t1;
	if ($t2!='')
		$VARS['tele_mcode']=$t2;

	@include_once($gScriptDir.$gGetAttendeeScript);

	// save the attendee list to a file for replay
	if ($output) {
		$attFile=$evtDir.$gAttFile;
		$fp=@fopen($attFile, "wb");
		if ($fp) {
			fwrite($fp, $output);
			fclose($fp);
			@chmod($attFile, 0777);
		}
	}
	echo "OK";
?>