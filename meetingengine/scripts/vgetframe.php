<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

/*
	function getFrameCount($c) {
		return ord($c{19}) + (ord($c{20}) << 8); 
	}
*/	
	// debugging only
	function WriteDbgFile($filePath, $data) {
		$data.="\r\n";
		$dfp=@fopen($filePath, "a");
		if ($dfp) {
			fwrite($dfp, $data, strlen($data));
			fclose($dfp);
		}	
	}

	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	include $includeFile;
	include $gHostFile;
	
	// turn on output throttling in bytes per second
	// set it to 0 to disable throttling
	// the screen sharing upload must have throttling too to prevent uploading faster than downloading
//	$speed=50*1024;
	$speed=60*1024;
	if (isset($GET_VAR['speed'])) {
		$speed=(integer)$GET_VAR['speed'];
	}
		
//	$theDir=$GET_VARS['evtdir'];
	$theDir="evt/";
	if (isset($_GET['evtdir']))
		$theDir=$_GET['evtdir'];
	elseif (isset($gSessionDir))
		$theDir=$gSessionDir."/";

	if ($theDir=='')
		ErrorExit("missing parameter 'evtdir'");
	if ($theDir[strlen($theDir)-1]!='/')
		$theDir.="/";
/*			
	$evtFile='';
	if (isset($GET_VARS['evtid'])) {
		$evtFile=$theDir.$gEvtDir.$gEvtPrefix.$GET_VARS['evtid'].".xml";
	}
*/
	$prefix=$theDir.$gFramesDir."frm";
	
	$print='';
	if (isset($GET_VARS['print']))
		$print=(int)$GET_VARS['print'];
	
	$maxFrames=1;

	if (isset($GET_VARS['mf']))
		$maxFrames=(int)$GET_VARS['mf'];
	
	// comment out for testing without file concatenation
	if (isset($GET_VARS['maxframes']))
		$maxFrames=(int)$GET_VARS['maxframes'];

	$frameNumber=0;
	if (isset($GET_VARS['frame']))
		$frameNumber=(integer)$GET_VARS['frame'];
		
	$frameFile=$prefix.$frameNumber.".swf";
//	$nextFrame=$frameNumber+1;
//	$nextFrameFile=$prefix.$nextFrame.".swf";
	$stopFile=$theDir.$gFramesDir.$gStopFile;
	$pauseFile=$theDir.$gFramesDir.$gPauseFile;
	
	$frameSegFile=$frameFile.".1";
/*	
$frameFile=$theDir."../frame.swf";
$fsize=filesize($frameFile);
header('Pragma: private');
header('Cache-control: private, must-revalidate');
header("Content-Length: ".$fsize);
header("Content-Type: application/x-shockwave-flash");	
@readfile($frameFile);
exit();		
*/
	
	// if the request frame is less than the current keyframe, return an empty swf file (24 bytes)
	// so the client can make another request to start loading from the keyframe instead.
	$sessFile=$theDir.$gFramesDir.$gSessionFile;
	$fp=@fopen($sessFile, "rb");
	if ($fp && flock($fp, LOCK_SH)) {
		$buffer=fread($fp, filesize($sessFile));
		flock($fp, LOCK_UN);
		fclose($fp);
		list($keyNum, $lastNum)=sscanf($buffer, "KeyFrame=%d&LastFrame=%d");
		
		if (isset($keyNum) && $keyNum>$frameNumber) {
			// has to be exactly 24 bytes swf file (5th byte is the size \x18)
			$emptySwf="FWS\x03\x18\x00\x00\x00".str_pad("", 16, "\x00");
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			
			header("Content-Length: 24");
			header("Content-Type: application/x-shockwave-flash");
			echo $emptySwf;
			exit();	
		}
	}
	
	$timeOut=1;
	if (isset($GET_VARS['timeout']))
		$timeOut=(int)$GET_VARS['timeout'];
		
	if ($timeOut>$gMaxWaitTime)
		$timeOut=$gMaxWaitTime;
	else if ($timeOut<1)
		$timeOut=1;
		
	$frameExists=false;
//	$nextFrameExists=false;
	$frameSegExists=false;
	$stopped=false;
	
	$startTime=time();
	$canusleep=-1;
	
	while (1) {
		if ((file_exists($stopFile) || file_exists($pauseFile)) && !$print) {		
			// sharing is either stopped or paused
			$stopped=true;
			break;
		}
		if (file_exists($frameFile)) {		
			$frameExists=true;
			break;
		}
		if (file_exists($frameSegFile)) {
			$frameSegExists=true;
			break;
		}

		MSleep($gCheckFileDelay, $canusleep);
		$currTime=time();
		if (($currTime-$startTime)>$timeOut)
			break;
	}
	
	$content='';

//	ob_start();
//	$dbgFile=$theDir."dbg.log";

	if ($frameSegExists) {
		// must add this for IE7 to work on SSL download
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');		
		header("Content-Type: application/x-shockwave-flash");
	
		$done=false;
		for ($i=1;;$i++) {
			$segFile=$frameFile.".".$i;
			
			$startTime=microtime(true);
			while (!file_exists($segFile)) {
				// $frameFile may not exist if the presenter upload is interrupted
				if (file_exists($frameFile)) {
					$done=true;
					break;
				}
				MSleep($gCheckFileDelay, $canusleep);
				$elapsedTime=microtime(true)-$startTime;
				// don't wait longer than 10 seconds
				if ($elapsedTime>10) {
					$done=true;
					break;
				}
			}
						
			if (file_exists($segFile)) {
				OutputFile($segFile, $speed, $canusleep);
				//@readfile($segFile);
//				flush();
			}
			if ($done)
				break;
		}
//		WriteDbgFile($dbgFile, "Segment: ".$segFile);
		exit();
		
	} else if ($frameExists) {
	
		$start=$frameNumber;
		$nextFrame=$frameNumber+1;
		$nextFrameFile=$prefix.$nextFrame.".swf";
		
		// only one file to send
		if ($maxFrames==1 || !file_exists($nextFrameFile)) {
			$frameFile=$prefix.$start.".swf";
			$fsize=filesize($frameFile);
			// must add this for IE7 to work on SSL download
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');

			header("Content-Length: ".$fsize);
			header("Content-Type: application/x-shockwave-flash");

			OutputFile($frameFile, $speed, $canusleep);
//			@readfile($frameFile);
//			WriteDbgFile($dbgFile, "Single: ".$frameFile);
			exit();			
		}
		
			
		$content='';
		// concatenate multiple swf files into a single output
		$totalSize=0;
		for ($i=0; $i<$maxFrames; $i++) {
			// limit the download site
			if ($totalSize>300000)
				break;
			$fnum=$start+$i;
			$frameFile=$prefix.$fnum.".swf";
			if (!file_exists($frameFile))
				break;
			$fp=@fopen($frameFile, "rb");
			if ($fp) {
				$theSize=filesize($frameFile);
				$totalSize+=$theSize;
				if ($i==0) {
					$content=fread($fp, $theSize);
					$contentLen=strlen($content);
					if ($contentLen<24) {
						fclose($fp);
						break;
					}
					
				} else {
					
					// read this file
					$theCont=fread($fp, $theSize);
					$theContLen=strlen($theCont);
					if ($theContLen<24) {
						fclose($fp);
						break;						
					}
										
					
					$contentLen = strlen($content);
					
					// detect which style of swf file this is. swf files created by the old VShow client
					// has a StopAction tag (0x07) in the third byte from the end
					// otherwise, treat it as a swf created by the Mac client
					// Must use double quotes for hex number string \x..
					if ($content{$contentLen-3}=="\x07") {
						// remove the last 3 bytes from the last file (StopAction and EndTag swf tag)
						$content=substr($content, 0, $contentLen-3);
//						WriteDbgFile($dbgFile, "Concatenate1: ".$frameFile);
						
					} else if ($content{$contentLen-4}=="\x40") { // ShowFrame tag: 0x40
						// remove 4 bytes at position n-8 from the last file (StopAction)
						// and last 2 bytes (End tag) but keep the 2-byte ShowFrame tag at position n-4
						$content = substr($content, 0, $contentLen - 8).substr($content, $contentLen - 4, 2);
//						WriteDbgFile($dbgFile, "Concatenate2: ".$frameFile);

					} else {
						// skip the concatenation if the format doesn't match
						fclose($fp);
//						WriteDbgFile($dbgFile, "Can't concatenate: ".$frameFile);
						break;
					}
					
					// determine the header size--either 21 or 22, depending on the Frame rect n-bits size
					if ($theCont{8}=="\x88") // n-bits=17 (0x88)
						$headerSize=22;
					else	// n-bits=15 (0x78)
						$headerSize=21;
						
					// get the number of frame in the file (last two bytes of the header)
					//$numFrames = getFrameCount($theCont);
					$numFrames=ord($theCont{$headerSize-2}) + (ord($theCont{$headerSize-1}) << 8);	
					
					// remove the header of this file and
					// append this file to the last file
					$content.=substr($theCont, $headerSize);
					
					// even though we discard the frames before the keyframe, we still need to add those frames to frameCount
					// because the swf player is using the frameCount to determin how many files have been loaded
					// the discarded files should be counted as well
					if ($content{8}=="\x88")
						$headerSize=22;
					else
						$headerSize=21;
					
					$frameCount=ord($content{$headerSize-2}) + (ord($content{$headerSize-1}) << 8);					
					$frameCount+=$numFrames;
										
					// set the frame number to be the sum of the two files
					$content{$headerSize-2}=chr(($frameCount<<24)>>24);
					$content{$headerSize-1}=chr(($frameCount<<16)>>24);
					
					// write the new file size to the swf header
					// write the length in reverse byte order
					$total=strlen($content);
					$content{4}=chr(($total<<24)>>24); // rightmost byte
					$content{5}=chr(($total<<16)>>24);
					$content{6}=chr(($total<<8)>>24);
					$content{7}=chr($total>>24); //leftmost byte

				}
				fclose($fp);
			} else {
				break;
			}	
		}

	}	

	if (!$print) {
		if ($content!='') {
			$fsize=strlen($content);
			// must add this for IE7 to work on SSL download
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');

			header("Content-Length: ".$fsize);
			header("Content-Type: application/x-shockwave-flash");
			flush();
			
//			WriteDbgFile($prefix.$start."_load".$i.".swf", $content);
//			WriteDbgFile($dbgFile, "Output: from $start to $i size=$fsize");
			

			OutputData($content, $fsize, $speed, $canusleep);
			exit();
		} else {
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');

			header("Content-Length: 0");
			header("Content-Type: application/x-shockwave-flash");

			exit();	
		}
	} else {
		echo "total inputSize=".$totalSize." ouputSize=".strlen($content)." frameCount=".$fcount."<br>\n";
		
	}
//	ob_flush();


	function OutputData($content, $contentSize, $bytesPerSec, $canusleep) {
				
		// if output speed throttling is on
		if ($bytesPerSec>0) {

			if ($canusleep)
				$bufSize=8192;
			else
				$bufSize=$bytesPerSec;

			$start=0;
			while ($start<$contentSize) {
				$length=$bufSize;
				if ($start+$length>$contentSize)
					$length=$contentSize-$start;

				echo substr($content, $start, $length);
				flush();
				$start+=$length;
				
				if ($canusleep) {
					$bufTime=round(1000000*$length/$bytesPerSec);	// in micro second;
					if ($bufTime>1000000)
						$bufTime=1000000;
					usleep($bufTime);
				} else if ($length==$bufSize) {	// more data to read
					sleep(1);
				}
			}

			
		} else {
			echo($content);
			
		}
	}
	
	function OutputFile($dataFile, $bytesPerSec, $canusleep) {

		// if output speed throttling is on
		if ($bytesPerSec>0) {
			if ($canusleep)
				$bufSize=8192;
			else
				$bufSize=$bytesPerSec;
			
			$ifp=@fopen($dataFile, "rb");

			while ($buffer=fread($ifp, $bufSize)) {
				$length=strlen($buffer);
				if ($length<=0)
					break;
				print($buffer);					
				flush();
				
				if ($canusleep) {
					$bufTime=round(1000000*$length/$bytesPerSec);	// in micro second;
					if ($bufTime>1000000)
						$bufTime=1000000;
					usleep($bufTime);
				} else if ($length==$bufSize) {	// more data to read
					sleep(1);
				}
			}

			fclose($ifp);			
			
			
		} else {	
			@readfile($dataFile);
		}
		
	}
?>
