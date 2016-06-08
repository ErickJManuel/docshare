<?php
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * 
 */

$includeFile='vinclude.php';
if (isset($gScriptDir)) {
$includeFile=$gScriptDir.$includeFile;
include_once $includeFile;
require_once $gScriptDir."PEAR.php";
require_once $gScriptDir."File.php";
require_once $gScriptDir."iswfcommon.php";
require_once $gScriptDir."iswfdataextractor.php";
require_once $gScriptDir."iswfprocessor.php";
} else {
require_once "PEAR.php";
require_once "File.php";
require_once "iswfcommon.php";
require_once "iswfdataextractor.php";
require_once "iswfprocessor.php";

}
include_once $gHostFile;

$theDir="evt/";
if (isset($_GET['evtdir']))
$theDir=$_GET['evtdir'];
elseif (isset($gSessionDir))
$theDir=$gSessionDir."/";
if (isset($_GET['zlib']))
$zlib=$_GET['zlib'];
else
$zlib = "0";

if(isset($_GET['start']))
$start = $_GET['start'];
else
$start = 0;


if ($theDir=='')
ErrorExit("missing parameter 'evtdir'");
if ($theDir[strlen($theDir)-1]!='/')
$theDir.="/";

$prefix=$theDir.$gFramesDir;
$param = realpath($prefix);
//$path = getIphoneVframesPath($param.DIRECTORY_SEPARATOR);
$path = $param.DIRECTORY_SEPARATOR;

$swfNumber=0;
if (isset($_GET['frameId']))
$swfNumber = $_GET['frameId'];
//////////////////////
//$sess_fp = $path. __SESSION_DESCR_FILENAME__;
// read the current keyframe and frame file numbers
$swfNumbersArray = SwfProcessor::getSessionNumbers($path);

if (!isset($swfNumbersArray[1]))
ErrorExit("missing session file");

// start from the last keyframe
if($start==1 && $swfNumber!=$swfNumbersArray[1]) {
	$swfNumber=$swfNumbersArray[1];
}

//$ffp=fopen($path."test.inf", "w");
//fwrite($ffp, $_SERVER['QUERY_STRING']." swfNumber=".$swfNumber." key=".$swfNumbersArray[1]." last=".$swfNumbersArray[0]);


// if the iphone user is joining an existing screen sharing session or the last keyframe is not converted yet
//if(!file_exists($path . __IPHONE_FILE_PREFIX__.$swfNumbersArray[1].".xml")) {
//	$infdt = readFromInf($path);
//	if (isset($infdt[0]) && isset($infdt[1])) {
		// create a iphone sharing session file, which will trigger iphone frame conversion in vuploadfile.php
//		$sessionId=substr($theDir, 0, strlen($theDir)-1);
//		SwfProcessor::createSessionFile($path, $sessionId, $infdt[1], $infdt[0]);
		// stream from the last keyframe file to the current frame and convert unconverted files
		$swfNumber=streamWithConversion($swfNumber, $path, $start);
//	}
//}

// turn on the iphone conversion flag so vuploadfile.php will convert uploaded frames
//SwfProcessor::startConversion($path);

// stream from the current frame files
//stream($swfNumber, $path, $start);
//	fclose($ffp);

/////////////////////
/*
function stream($swfNumber, $path, $start) {
	global $ffp;
	
	$canusleep = -1;
	$counter = 0;
	while(true) {
		$swfNumbersArray = SwfProcessor::getSessionNumbers($path);
		if($swfNumber < $swfNumbersArray[1]) {
			//new keyframe is ready
			$swfNumber = $swfNumbersArray[1];
		} else if($start == 1 && $swfNumber!=$swfNumbersArray[1]) {
			//no keyframe but on start we have to return keyframe.
//			$swfNumber=$swfNumbersArray[1];
		}
		//no more start loop.
		$start = 0;
		if($swfNumber <= $swfNumbersArray[0]) {
			//has to be smaller then last available.
			$filename = $path . __IPHONE_FILE_PREFIX__.$swfNumber.".xml";
			if(file_exists($filename)) {				
if ($ffp)
	fwrite($ffp, "\r\n stream=".$swfNumber." key=".$swfNumbersArray[1]." last=".$swfNumbersArray[0]);
				printFrameFromFile($filename);
				$swfNumber++;
				$counter = 0;
			} else {
				MSleep(200, $canusleep);
				$counter++;
			}
		} else {
			MSleep(200, $canusleep);
			$counter++;
		}
		if($counter > 20) {
			break;

		}
//		$filename = $path . __IPHONE_FILE_PREFIX__.$swfNumber.".xml";

	}
}
*/

function streamWithConversion($swfNumber, $frmPath, $start) {
	global $ffp;

	$canusleep = -1;
	$counter = 0;
	
	$currentFr = $swfNumber;
	$hasOutput=false;
//	$iters = 0;
	while (true) {
		if($counter > 50)
			break;
		clearstatcache();	// need this to make sure file info is not gotten from a cache
		$infData = SwfProcessor::getSessionNumbers($frmPath);
		$keyframe=$infData[1];			
		//if there is new keyframe on hdd then skip to the next keyframe
		If ($currentFr < $keyframe) {
			$currentFr=$keyframe;
		}
		
		$srcFile = $frmPath."frm".$currentFr.".swf";
		// swf file is not available yet; wait for it
		if ($currentFr>$infData[0] || !file_exists($srcFile)) {
			if ($hasOutput)
				return;
			MSleep(100, $canusleep);
			$counter++;
			continue;
		}
		// check if the file has been converted
		// only convert the file if it has not been converted
		$xmlFile= $frmPath.__IPHONE_FILE_PREFIX__.$currentFr.".xml";
		$lockFile= $xmlFile.".lock";
		If (file_exists($xmlFile)) {
			printFrameFromFile($xmlFile);
//if ($ffp)
//	fwrite($ffp, "\r\n readFile=".$currentFr);
		} else {
			$xmlData=null;
			$lfp=@fopen($lockFile, "w");

			if (@flock($lfp, LOCK_EX)) {
				
				// check if we no one else has created the file while we are waiting for the lock
				clearstatcache();	// need this to make sure file info is not gotten from a cache
				$createFile=true;
				if (file_exists($xmlFile) && filesize($xmlFile)>0) {
					$createFile=false;
				}
				
				if ($createFile) {
					$isKey = ($currentFr == $keyframe) ? 1 : 0;			
					$swfExtr = new SwfDataExtractor($srcFile, $isKey, $currentFr);
					$xmlData = $swfExtr->returnSwfXml();
					SwfProcessor::saveXmlToFile($xmlData, $xmlFile);
				}
				@flock($lfp, LOCK_UN);								
			}
			@fclose($lfp);
//			@unlink($lockFile);
			
			if ($xmlData!=null) {
//if ($ffp)
//	fwrite($ffp, "\r\n lock&writeFile=".$currentFr);

				printFrameFromString($xmlData->asXML());
			} else {
//if ($ffp)
//	fwrite($ffp, "\r\n lock&readFile=".$currentFr);

				printFrameFromFile($xmlFile);
			}
	
		}
		$counter++;

		$currentFr++;

		$hasOutput=true;

	}
	
	return $currentFr;
}

function printFrameFromFile($filename) {
	$outxml = simplexml_load_file($filename);
	printFrameFromString($outxml->asXML());
}

function printFrameFromString($xmlContent) {
	print "\n==============\n";
	print(gzcompress($xmlContent));
//	print($xmlContent);
	print "\n==============\n";
//	ob_flush();
	flush();
//	usleep(50000);
}




?>
