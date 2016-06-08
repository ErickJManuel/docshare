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
		ErrorExit("Missing an input parameter.");
		
	if (strpos($dir, "/vmeetings/")===false)
		ErrorExit("Invalid directory path.");
/*
	$version='';
	if (isset($GET_VARS['version']))
		$version=$GET_VARS['version'];
	if ($version=='')
		ErrorExit("missing input");
*/
	$ext=".php";
	if (isset($GET_VARS['ext']) && $GET_VARS['ext']!='')
		$ext=".".$GET_VARS['ext'];
			
	$indexFile="index.htm";
	if (isset($GET_VARS['index']) && $GET_VARS['index']!='')
		$indexFile=basename($GET_VARS['index']);

	$title="";
	if (isset($GET_VARS['title']) && $GET_VARS['title']!='') {	
		$title=$GET_VARS['title'];
	
//		if (!get_magic_quotes_gpc())
//			$title=addslashes($title);
	}
	$status="stopped";
	if (isset($GET_VARS['status']) && $GET_VARS['status']!='')
		$status=$GET_VARS['status'];
	$registration="0";
	if (isset($GET_VARS['registration']) && $GET_VARS['registration']!='')
		$registration=$GET_VARS['registration'];
/*		
	$hostVars='';
	if (isset($GET_VARS['host_vars']))
		$hostVars=urldecode($GET_VARS['host_vars']);
		
	$attVars='';
	if (isset($GET_VARS['att_vars']))
		$attVars=urldecode($GET_VARS['att_vars']);

	$meetingXml='';
	if (isset($GET_VARS['meeting_xml'])) {
		$meetingXml=urldecode($GET_VARS['meeting_xml']);
		$meetingXml=str_replace("\"", "\\\"", $meetingXml);
	}
*/
//	$hostname="";
//	if (isset($GET_VARS['hostname']) && $GET_VARS['hostname']!='')
//		$hostname=$GET_VARS['hostname'];
/*
	$hostid="";
	if (isset($GET_VARS['hostid']) && $GET_VARS['hostid']!='')
		$hostid=$GET_VARS['hostid'];
	$webcast="false";
	if (isset($GET_VARS['webcast']) && $GET_VARS['webcast']!='')
		$webcast=$GET_VARS['webcast'];	
*/	
	$mode=fileperms("./");
	if (isset($GET_VARS['mode'])) {
		if ($GET_VARS['mode']=='755')
			$mode=0755;
		else if ($GET_VARS['mode']=='777')
			$mode=0777;
	}

	umask(0);
	
	if (!MyMkDir($dir, $mode, $indexFile))
		ErrorExit ("can't create ".$dir);

	sleep(1);

//ErrorExit("dir=".$dir." cwd=".getcwd());
	
//	if (!MyMkDir($dir.$gRegisDir, $mode, $indexFile))
//		ErrorExit ("can't create ".$dir.$gRegisDir);

	$scriptDir="../../../scripts/";
//	CopyDir($scriptDir.$gVMeetingDir, $dir);

//	$phpFile="vscript".$ext;
//	$scriptVer=str_replace(".", "", $version);
	$phpFile="vscript".$ext;
	$outFile=$dir."vscript".$ext;
	umask(0);
/* $content="<?php \$ext='".$ext."'; \$gScriptDir='".$scriptDir."'; require '".$scriptDir.$phpFile."'; ?>"; */
	$content="<?php \$gScriptDir='".$scriptDir."'; require '".$scriptDir."vscript.php'; ?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't open file ".$outFile);
/*
	$phpFile="vhost".$ext;
	$outFile=$dir.$phpFile;
	$content="<?php \$version='".$version."'; require '".$scriptDir.$phpFile."'; ?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't open file ".$outFile);
*/	
	$serverPath=$GET_VARS['server'];
	$fileName=basename($serverPath);
	$serverPath=str_replace($fileName, '', $serverPath);
//	$swfFile=$serverPath."viewer.swf";
	$swfFile="../../../viewer.swf";
	
/*	$arg='';
	if (isset($GET_VARS['server'])) {
		$arg="MeetingServer=".$GET_VARS['server'];
	} */
	
	$meetingId='';
	if (isset($GET_VARS['meeting_id']))
		$meetingId=$GET_VARS['meeting_id'];
//		$arg.="&MeetingID=".$GET_VARS['meeting_id'];

/*	
	$phpFile="viewer".$ext;
	$outFile=$dir.$phpFile;
	$content="<?php \$version='".$version."'; require '".$scriptDir.$phpFile."'; ?>";
*/
	$phpFile="viewer".$ext;
	$outFile=$dir.$phpFile;
	$content="<?php\n";
	$content.=" include(\"../../../site_config.php\");";
	$content.=" \$winTitle=\"$title\";";
	$content.=" \$swfFile=\"$swfFile\";";
	$content.=" \$apiUrl=\$serverUrl.\"api.php\";";
	$content.=" \$arg=\"MeetingServer=\$apiUrl&MeetingID=$meetingId\";";
	$content.=" include(\"$scriptDir$phpFile\");\n";
	$content.="?>";

	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't open file ".$outFile);
/*		
	$phpFile="meeting_info".$ext;
	$outFile=$dir.$phpFile;
	$content="<?php\n";
	$content.=" \$hostVars=\"$hostVars\";\n";
	$content.=" \$attVars=\"$attVars\";\n";
	$content.=" \$meetingXml=\"$meetingXml\";\n";
	$content.="?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't open file ".$outFile);
*/
	echo ("OK");
   
?>
