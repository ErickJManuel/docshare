<?php 

// (c)Copyright 2004, Persony, Inc. All rights reserved.

	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	include_once $includeFile;
	include_once $gHostFile;
	
	$speed=40*1024;	// speed limit in bytes per second
	
	$frameFilePrefix="frm";
	$frameFileType=".swf";
		
	$from='';
	if (isset($GET_VARS['from']))
		$from=$GET_VARS['from'];
	
	$frameSeg=0;	
	if (isset($GET_VARS['segment']))
		$frameSeg=(integer)$GET_VARS['segment'];
	
	$lastSeg='';
	if (isset($GET_VARS['last']))
		$lastSeg=$GET_VARS['last'];
	
	$dir=$GET_VARS['dir'];
	$subdir=basename($dir);
	$dirName=$dir."/";
	$subdir.="/";
	
	$dirExists=false;
	for ($i=0; $i<4; $i++) {
		if (!file_exists($dirName))
			sleep(1);
		else {
			$dirExists=true;
			break;
		}
	}

	// most likely the meeting has ended and the folder has been deleted.
	// just quit and don't report an error.
	if (!$dirExists) {
		// the error has to contain "sharing stopped" for vpresent.exe to pick up
		// otherwise, it will be reported as a reqular script error to the user
		die("ERROR sharing stopped");
	}
	
	if (!IsHost($from, $evtDir)) {
		$evtDir=str_replace($subdir, "", $dirName);
		if (!IsPresenter($from, $evtDir)) {
			// the error has to contain "not authorized" for vpresent.exe to pick up
			// otherwise, it will be reported as a reqular script error to the user
			die( "ERROR not authorized");
		}
	}	

	if ($subdir!=$gFramesDir)
		die("ERROR Invlaid upload directory ".$dir);

	// uploading screen sharing frames
	$fileName="";
	
	// indicate if we want to delete old frames
	$deleteOld='';
	if (isset($GET_VARS['deleteOld']))
		$deleteOld=$GET_VARS['deleteOld'];
		

	$tempFrm="tempfrm.";
	$sessionFile=$dirName.$gSessionFile;
//	$sessionTemp=$dirName."vsession.tmp";
	$keyFrame=0;
	$fileCounter=-1;
	$counter=0;
	$pictCount=0;
	$isKeyframe=false;

	$stopFile=$dirName.$gStopFile;
	if (file_exists($stopFile)) {
		// the error has to contain "sharing stopped" for vpresent.exe to pick up
		// otherwise, it will be reported as a reqular script error to the user
		// The Java sharing app doesn't respond to any error except for this.
		die( "ERROR sharing stopped");			
	}

	$sfp=@fopen($sessionFile, "rb");
	if ($sfp && flock($sfp, LOCK_SH)) {
		$line = fgets($sfp, 64); 
		list($keyFrame, $fileCounter) = sscanf($line, "KeyFrame=%d&LastFrame=%d"); 
		flock($sfp, LOCK_UN);
	}
	if ($sfp)
		@fclose($sfp);
	
	if ($frameSeg<=1) {
		$fileCounter++;
	}
	
	$fileName=$frameFilePrefix.$fileCounter.$frameFileType;
	
	// This really shouldn't happen but in case it does, we want to find the last frame file counter
	while (file_exists($dirName.$fileName)) {
		$fileCounter++;
		$fileName=$frameFilePrefix.$fileCounter.$frameFileType;
	}
		
	if (isset($GET_VARS['isKeyframe']) && $GET_VARS['isKeyframe']=='1') {
		$keyFrame=$fileCounter;
		$isKeyframe=true;
	}
	
	if ($frameSeg>0) {
		$fileName.=".".$frameSeg;
	}
	
	$newFile=$dirName.$fileName;
	
	$tempFile=$newFile.".tmp";
	$fp=@fopen($tempFile, "wb");

	$fSize=0;
	if ($fp) {

		$ifp=@fopen("php://input", "rb");

		while ($data=fread($ifp, 8192)) {
			$dataSize=strlen($data);
			if ($dataSize==0)
				break;
				
			fwrite($fp, $data);
		}

		fclose($ifp);
		
		fclose($fp);
		if (file_exists($newFile))
			@unlink($newFile);
		if (!@rename($tempFile, $newFile)) {
			// ignore error in case the new file exists
//			die("ERROR can't move file from ".$tempFile." to ".$newFile);
		}
		@chmod($newFile, 0777);
		$fSize=filesize($newFile);
	} else {
		die("ERROR can't write file ".$tempFile);
	}
	
	if ($lastSeg=='1') {
		$newFile=$dirName.$frameFilePrefix.$fileCounter.$frameFileType;
		$tempFile=$newFile.".tmp";
		$fp=@fopen($tempFile, "wb");
		if ($fp) {
			for ($i=1; ;$i++) {
				$segFile=$newFile.".".$i;
				
				if (file_exists($segFile)) {
					$data=@file_get_contents($segFile);
					fwrite($fp, $data);				
				} else {
					break;
				}
			}
			fclose($fp);
			@chmod($tempFile, 0777);
			// on Linux rename will overwrite an existing file, which's what we want.
			// on Win32, it doesn't so need to unlink it first
			if (file_exists($newFile))
				@unlink($newFile);

			if (!@rename($tempFile, $newFile)) {
				// ignore error in case the new file exists
//				die("ERROR can't move file from ".$tempFile." to ".$newFile);
			}
			
		} else {		
			die("ERROR can't write file ".$tempFile);
		}
	}
		
	if ($frameSeg<=1) {		
//		$fp=@fopen($sessionTemp, "wb");
		$sfp=@fopen($sessionFile, "wb");
		if (flock($sfp, LOCK_EX)) {		
			$line="KeyFrame=".$keyFrame."&LastFrame=".$fileCounter;
			fwrite ($sfp, $line);
			flock($sfp, LOCK_UN);
//			if (file_exists($sessionFile))
//				@unlink($sessionFile);
//			@rename($sessionTemp, $sessionFile);
		} else {
			die("ERROR can't write file ".$sessionFile);
		}
		@fclose ($sfp);
		@chmod($sessionFile, 0777);
	
		$timeFile="vframes.xml";
		$fp=@fopen($dirName.$timeFile, "a");

		if (flock($fp, LOCK_EX)) {
			$theTime=time();
			if ($isKeyframe)
				$line="<f t=\"$theTime\" k=\"1\" i=\"$fileCounter\" />\r\n";
			else
				$line="<f t=\"$theTime\" i=\"$fileCounter\" />\r\n";
			
			fwrite($fp, $line);
			flock($fp, LOCK_UN);
		} else {
			die("ERROR can't write file ".$timeFile);
		}	
		@fclose($fp);
		@chmod($dirName.$timeFile, 0777);
	
		// delete old files
		if ($deleteOld=='1') {
			$oldFile='';
			$oldFileOffset=1000;
			$oldIndex=$fileCounter-$oldFileOffset;
			if ($oldIndex>=0) {		
				$oldFile=$dirName.$frameFilePrefix.$oldIndex.$frameFileType;
				if (file_exists($oldFile))
					@unlink($oldFile);			

				for ($i=1; ;$i++) {
					$oldFile=$dirName;
					//$oldFile.=sprintf("%s%d_%04d%s", $frameFilePrefix, $oldIndex, $i, $frameFileType);
					$oldFile.=$frameFilePrefix.$oldIndex.$frameFileType.".".$i;
					if (file_exists($oldFile))
						@unlink($oldFile);
					else
						break;
				}		
			
			}	
		}
	}
	
	// iphone conversion
/* The conversion is now done on the requesting side (vistream.php)
	if ($frameSeg<=1) {	
		include_once("iswfcommon.php");
		include_once("iswfprocessor.php");
		if( SwfProcessor::isConverting($dirName)) {
			$swf_filepth = $newFile;
			$proc = new SwfProcessor();
			if ($isKeyframe)
				$isKey=1;
			else
				$isKey=0;
	//		$proc->processSwfUpload($gScriptDir, $swf_filepth, $dirName, $frameSeg, $isKey, $lastSeg,-1);
			$proc->processSwfUpload($swf_filepth, $dirName, $isKey, $fileCounter);
		}
	}
*/
	// $gSwfServer should be defined in gHostFile if it is set
	// the server is used to convert swf to jpeg for iphone
	// this code is not used anymore because we don't need the Java server for conversion

	if (isset($gSwfServer)) {
		// notify the Java Server a new file has been uploaded	
//		$aurl="http://localhost:8080/swfserver/receiver";
		$aurl=$gSwfServer."receiver";
		$aurl.='?'.$_SERVER["QUERY_STRING"];
		$cwdPath=getcwd();
		$cwdPath=str_replace("\\", "/", $cwdPath);
		$cwdPath.="/";
		$aurl.="&filePath=".$cwdPath.$newFile;
		@file_get_contents($aurl);
	}

/* write out the upload stats for debugging */
	$speedFile=$dirName."upload.data";
	$speedFp=@fopen($speedFile, "rb");
	$speedData=array();
	if ($speedFp) {
		$strData=fread($speedFp, filesize($speedFile));
		$items=explode("\r\n", $strData);
		foreach ($items as $keyPair) {
			list($key, $val)=explode("=", $keyPair);
			$speedData[$key]=$val;
		}
		fclose($speedFp);
	}
	
	// write out upload stats to a file and also add a delay if the upload speed exceeds the limit

	$speedFp=@fopen($speedFile, "wb");
	$currentTime=microtime(true);
	$elapsedTime=0;
	$lastTime=0;
	if (isset($speedData['lastTime'])) {
		$lastTime=floatval($speedData['lastTime']);
	}
	if ($lastTime>0)
		$elapsedTime=$currentTime-$lastTime;
	$uploadSpeed=0;
	$waitTime=0;
	if ($elapsedTime>0) {
		if ($speed>0) {
			$expectedTime=(float)$fSize/$speed;
			$waitTime=round(($expectedTime-$elapsedTime)*1000000);
			if ($waitTime>0) {
				if ($waitTime>3000000)	// shouldn't need to do this but just in case something is really wrong
					$waitTime=3000000;
					
				usleep($waitTime);
				$currentTime=microtime(true);
				if ($lastTime>0)
					$elapsedTime=$currentTime-$lastTime;
			}
		}
		$uploadSpeed=round($fSize*8/($elapsedTime*1024));
	}

	$newData="lastFile=$fileName\r\n";
	$newData.="lastData=$fSize\r\n";
	$newData.="lastTime=$currentTime\r\n";
	$newData.="elapsedTime=$elapsedTime\r\n";
	$newData.="waitTime=$waitTime\r\n";
	$newData.="speed=$uploadSpeed\r\n";
	
	$topSpeed=$uploadSpeed;
	$topSpeedFile=$fileName;
	$topSpeedSize=$fSize;
	if (isset($speedData['topSpeed'])) {
		$lastTopSpeed=floatval($speedData['topSpeed']);
		if ($lastTopSpeed>$topSpeed) {
			$topSpeed=$lastTopSpeed;
			$topSpeedFile=$speedData['topSpeedFile'];
			$topSpeedSize=$speedData['topSpeedSize'];
		}
	}
	
	$newData.="topSpeed=$topSpeed\r\n";
	$newData.="topSpeedFile=$topSpeedFile\r\n";
	$newData.="topSpeedSize=$topSpeedSize\r\n";
	$totalSize=$fSize;
	$totalTime=$elapsedTime;
	if (isset($speedData['totalSize']))
		$totalSize+=(integer)($speedData['totalSize']);
	if (isset($speedData['totalTime']))
		$totalTime+=floatval($speedData['totalTime']);

if ($totalTime>0)	
	$avgSpeed=round($totalSize*8/($totalTime*1024));
else
	$avgSpeed=0;

$newData.="totalSize=$totalSize\r\n";
$newData.="totalTime=$totalTime\r\n";
$newData.="avgSpeed=$avgSpeed\r\n";
fwrite($speedFp, $newData);
fclose($speedFp);

?>
