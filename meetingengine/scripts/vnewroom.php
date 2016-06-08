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
		$dir=basename($GET_VARS['dir']);
		if ($dir[strlen($dir)-1]!='/')
			$dir.="/";	
	}
	if ($dir=='')
		ErrorExit("missing input");
/*
	$version='';
	if (isset($GET_VARS['version']))
		$version=$GET_VARS['version'];
	if ($version=='')
		ErrorExit("missing input");
*/
	$ext=".php";
	if (isset($GET_VARS['ext'])&& $GET_VARS['ex']!='')
		$ext=".".basename($GET_VARS['ext']);
	
	$indexFile="index.html";
	if (isset($GET_VARS['index']) && $GET_VARS['index']!='')
		$indexFile=basename($GET_VARS['index']);

	$mode=fileperms("./");
//	$mode=fileperms($dir);
	if (isset($GET_VARS['mode'])) {
		if ($GET_VARS['mode']=='755')
			$mode=0755;
		else if ($GET_VARS['mode']=='777')
			$mode=0777;
	}
	umask(0);

// can't do this unless the parent folder is already set to 777. 
	if (!MyMkDir($dir, $mode, $indexFile))
		ErrorExit ("can't create ".$dir);

//	if (!MyMkDir($dir.$gSlidesDir, $mode, $indexFile))
//		ErrorExit ("can't create ".$dir.$gSlidesDir);

//	if (!MyMkDir($dir.$gLogsDir, $mode, $indexFile))
//		ErrorExit ("can't create ".$dir.$gLogsDir);

	if (!MyMkDir($dir.$gMeetingsDir, $mode, $indexFile))
		ErrorExit ("can't create ".$dir.$gMeetingsDir);

	$scriptDir="../scripts/";
	
	$phpFile="vlogin".$ext;
	$outFile=$dir.$phpFile;
	$content="<?php require_once (\"".$scriptDir."vlogin.php\"); ?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't create file ".$outFile);
	
//	$verName=str_replace(".", "", $version);
	
	$phpFile="vscript".$ext;
	$outFile=$dir.$phpFile;
/* $content="<?php \$ext='".$ext."'; \$gScriptDir='".$scriptDir."'; require '".$scriptDir.$gScriptFile."'; ?>"; */
	$content="<?php \$gScriptDir='".$scriptDir."'; require_once(\"".$scriptDir."vscript.php\"); ?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't create file ".$outFile);
/*	
	$phpFile="vgetversion".$ext;
	$outFile=$dir.$phpFile;
	$content="<?php echo \"".$version."\"; ?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't create file ".$outFile);
*/
	echo ("OK");
   
?>
