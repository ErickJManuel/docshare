<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// This script requires PHP 5 (simplexml_load_string, RecursiveIteratorIterator)
// and "zip" extension must be enabled.
// The php_zip.dll that comes with Windows PHP5.2.6 and later has a bug that causes it to create currpted zip files
// (http://pecl.php.net/bugs/bug.php?id=16071&edit=1)
// Replace it with an older (5.2.5) php_zip.dll seems to fix the problem
// Download php_zip.dll from http://kromann.info/download.php?strFolder=php5_2-Release_TS&strIndex=PHP5_2

$includeFile='vinclude.php';
if (isset($gScriptDir))
$includeFile=$gScriptDir.$includeFile;
include_once $includeFile;
include_once($gHostFile); //defined in vinclude.php

if (!isset($_GET['meeting_id']) || $_GET['meeting_id']=='')
	ErrorExit("meeting_id parameter not set.");
	
$evtDir="evt/";
if (isset($_GET['evtdir']))
	$evtDir=$_GET['evtdir'];
elseif (isset($gSessionDir))
	$evtDir=$gSessionDir."/";

if ($evtDir=='')
	ErrorExit("missing parameter 'evtdir'");
if ($evtDir[strlen($evtDir)-1]!='/')
	$evtDir.="/";

	
//set_time_limit(1800);

$meetingId=basename($_GET['meeting_id']);
if (isset($_GET['download']) && $_GET['download']!='')
	$downloadName=$_GET['download'].".zip";
else
	$downloadName="recording_".$meetingId.".zip";

$sessionId=substr($evtDir, 0, strlen($evtDir)-1);
$zipFile="recording_".$sessionId.".zip";

if (isset($_GET['check'])) {
	if (!file_exists($zipFile) || filesize($zipFile)==0)
		ErrorExit("File not available");	// does not exist
	else
		die("OK size=".filesize($zipFile));
	
} else if ($_GET['create']) {
						
	if (!isset($_GET['server']) || $_GET['server']=='')
		ErrorExit("server parameter not set.");
	if (!isset($_GET['brand']) || $_GET['brand']=='')
		ErrorExit("brand parameter not set.");
		
	$serverUrl=$_GET['server'];
	$brand=$_GET['brand'];	
	$meetingPass=isset($_GET['meeting_pass'])?$_GET['meeting_pass']:"";
	$apiUrl=$serverUrl."api.php";

	if (!extension_loaded('zip'))
		ErrorExit("PHP extension 'zip' is not enabled.");
	if (!function_exists('simplexml_load_string'))
		ErrorExit("PHP function 'simplexml_load_string' is not supported.");
	if (!class_exists('RecursiveIteratorIterator'))
		ErrorExit("PHP class 'RecursiveIteratorIterator' is not supported.");

		
	$tempFile=$zipFile.".tmp";
	$lockFile=$zipFile.".lock";
	
	$lfp=fopen($lockFile, "w");
	// lock the file while we are procssing
	if (flock($lfp, LOCK_EX)) {		
		
		if (($err=CreateZipFile($evtDir, $tempFile, $apiUrl, $meetingId, $brand))!='') {

			flock($lfp, LOCK_UN);
			fclose($lfp);
			unlink($lockFile);
			
			ErrorExit($err);
		}
		
		if (file_exists($zipFile))
			unlink($zipFile);	
		if (!rename($tempFile, $zipFile))
			ErrorExit("Couldn't rename $tempFile to $zipFile");
			
		flock($lfp, LOCK_UN);

	}
	fclose($lfp);
	unlink($lockFile);
	
	die("OK size=".filesize($zipFile));
}

if (!file_exists($zipFile) || filesize($zipFile)==0)
	ErrorExit("File does not exist.");

if (ini_get('zlib.output_compression')) {
	ini_set('zlib.output_compression', 'Off');
}

$fp=@fopen($zipFile, "rb");
if (!$fp)
	ErrorExit("Couldn't open $zipFile");

header('Pragma: private');
header('Cache-control: private, must-revalidate');
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . filesize($zipFile));
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=".$downloadName);
//	readfile($outFile);

while (!feof($fp)) {
	$data=fread($fp, 32768);
	echo $data;
}

fclose($fp);

function CreateZipFile($evtDir, $zipFile, $apiUrl, $meetingId, $brand)
{
	$tempDir="rec_temp/";
	$filesDir="files/";
	$contentDir="content/";
	$localeDir="vlocale/";
	$hostPictName="host";
	$logoPictName="logo";
	$initFile="viewerinit.inf";
	$backgroundFile="background.xml";
	$bgStartPict="bg_start";
	$bgWaitPict="bg_wait";
	$viewerFile="viewer.swf";
	$zipTemp="../../../scripts/rec_temp.zip";
	$vieweFilePath="../../../".$viewerFile;
	$meetingFileName="vmeeting.xml";
	
	
	if (file_exists($zipFile))
		unlink($zipFile);
	if (!copy($zipTemp, $zipFile))
		return ("Couldn't copy the template file");

	umask(0);
	chmod($zipFile, 0777);
	
	// create a zip file
	$zip = new ZipArchive();
	
	if ($zip->open($zipFile, ZIPARCHIVE::CREATE )!==TRUE) {
		return("Couldn't open $zipFile");
	}
	
	// rename the directory in the zip file from rec_temp to recording_xxxx, where xxxx is the meeting id
	$outDir="recording_".$meetingId."/";
	for ($i=0; $i<$zip->numFiles;$i++) {
		$oldName=$zip->getNameIndex($i);
		$newName=str_replace($tempDir, $outDir, $oldName);
		$zip->renameName($oldName, $newName );
	}
	
	if ($zip->addFile($vieweFilePath, $outDir.$filesDir.$viewerFile)!==true)
		return("Couldn't add file ".$outDir.$filesDir.$viewerFile);
		
	// load meeting file
	// get access token; only valid for v 2.2.15 or above
	$tokenUrl=str_replace("api.php", "rest/signin/", $apiUrl);
	$data="brand=".$brand;
	$data.="&meeting_id=".$meetingId;
	if ($meetingPass!='')
		$data.="&meeting_password=".$meetingPass;
	$data.="&method=POST";
		
	$resp=@file_get_contents($tokenUrl."?".$data);

	$token='';
	if ($resp) {
		$items=explode("=", $resp);
		$token=$items[1];
	} else {
		return("Couldn't get token");
	}

	$getMeetingUrl=$apiUrl."?cmd=GET_MEETING_INFO&meeting=".$meetingId;
	$getMeetingUrl.="&token=".$token;
	$meetingXml=@file_get_contents($getMeetingUrl);

	if ($meetingXml===false)
		return ("Couldn't get meeting information");

	// remove the trailing slash
	$evtDir1=substr($evtDir, 0, strlen($evtDir)-1);	
	$contentDir1=substr($contentDir, 0, strlen($contentDir)-1);	
	// replace the sessiondir value with the local content dir name
	$meetingXml=str_replace($evtDir1, $contentDir1, $meetingXml);

	if ($zip->addFromString($outDir.$filesDir.$meetingFileName, $meetingXml)!==true)
		return("Couldn't add a file ".$outDir.$filesDir.$meetingFileName);

	
	// initialize an iterator
	// pass it the directory to be processed
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($evtDir));
	if (!$iterator)
		return ("Couldn't create a RecursiveIteratorIterator");

	// iterate over the directory
	// add each file found to the archive
	foreach ($iterator as $key=>$value) {
		
		// if the last char is . skip it
		if ($key[strlen($key)-1]=='.')
			continue;
			
		$file=str_replace("\\", "/", $key);
		$file=str_replace($evtDir, $outDir.$filesDir.$contentDir, $file);

		/** 
		 * addFile keeps the file open.
		 * Use addFromString to work around file descriptor number limitation (to avoid failure
		 * upon adding more than typically 253 or 1024 files to ZIP) */		
		/*
		if ($zip->addFile(realpath($key), $file)!==true)
			return ("Could not add file: $file");
		*/
		$contents=@file_get_contents(realpath($key));
		if ($contents!==false) {
			$zip->addFromString( $file, $contents );
		}

	}


	$viewerUrl=$apiUrl."?cmd=GET_VIEWER_INFO&meeting_id=".$meetingId."&".rand();
	$viewerParams=@file_get_contents($viewerUrl);
	
	if ($viewerParams===false)
		return("Couldn't get $viewerUrl");
					
	$viewerData='';
	$paramPairs=explode("&", $viewerParams);
	foreach($paramPairs as $item) {
		list($key, $val)=explode("=", $item, 2);
			
		if ($key=='BaseDir')
			$viewerData.="$key=";
		else if ($key=='RoomDir' || $key=='MeetingDir')
			$viewerData.="&$key=";
		else if ($key=='LocaleDir')
			$viewerData.="&$key=$localeDir";
		else if ($key=='HostPict') {
			if ($val!='')
				AddUrlToZip($zip, $val, $outDir.$filesDir, $hostPictName);
			$viewerData.="&$key=$localFile";
		} else if ($key=='LogoFile') {
			if ($val!='')
				$localFile=AddUrlToZip($zip, $val, $outDir.$filesDir, $logoPictName);
			$viewerData.="&$key=$localFile";
		} else if ($key=='BrandID' || $key=='BrandName' || $key=='AlertSound' || $key=='CanSeeAll' || $key=='CanSendAll' || $key=='HideWindows')
			$viewerData.="&$key=$val";
		else if ($key=='Background') {
			$background=$val;
			$startFile='';
			$waitFile='';

			$backgroundXml=@file_get_contents(DecodeDelimiter1($background));
			if ($backgroundXml) {

				$xml = simplexml_load_string($backgroundXml);
				$startFile=isset($xml['startFile'])?$xml['startFile']:"";
				$waitFile=isset($xml['waitFile'])?$xml['waitFile']:"";
				
				$err='';
				if ($startFile!='')
					AddBackgroundToZip($zip, $startFile, $outDir.$filesDir, $bgStartPict, $backgroundXml);
	
				if ($waitFile!='')
					AddBackgroundToZip($zip, $waitFile, $outDir.$filesDir, $bgWaitPict, $backgroundXml);
								
				if ($zip->addFromString($outDir.$filesDir.$backgroundFile, $backgroundXml)!==true)
					return("Couldn't add a file ".$outDir.$filesDir.$backgroundFile);
				
				$viewerData.="&$key=$backgroundFile";
			}			
		}


	}
	
	if ($zip->addFromString($outDir.$filesDir.$initFile, $viewerData)!==true)
		return("Couldn't add a file ".$outDir.$filesDir.$initFile);

	if (!$zip->close())
		return("Couldn't create $tempFile");
	
		
}

function DecodeDelimiter1($url)
{
	$url=str_replace("|", "&", $url);
	$url=str_replace(";", "?", $url);
	return $url;
}

function AddUrlToZip($zip, $url, $localDir, $localName)
{
	$localFile='';
	$content=@file_get_contents($url);
	if ($content) {

		$pathItems=pathinfo($url);
		$ext=isset($pathItems['extension'])?$pathItems['extension']:"";
		$localFile=$localName.".".$ext;
		
		if ($localFile==".")
			return '';
		
		$zip->addFromString($localDir.$localFile, $content);
		return $localFile;
	}
	return '';
}

function AddBackgroundToZip($zip, $pictUrl, $localDir, $localPictName, &$backgroundXml)
{
	$localFile=AddUrlToZip($zip, $pictUrl, $localDir, $localPictName);
	$backgroundXml=str_replace($pictUrl, $localFile, $backgroundXml);

}


?>