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
	include_once($gHostFile); //defined in vinclude.php
	
	$id='';
	if (isset($GET_VARS['id']))
		$id=$GET_VARS['id'];
	$code='';
	if (isset($GET_VARS['code']))
		$code=$GET_VARS['code'];

	$cwd=getcwd();
	chdir("../../../");
	if (!isset($_GET['download'])) {
		if (!IsAuthorized($id, $code))
			die("ERROR Not authorized.");		
	}
	chdir($cwd);
	
	$evtDir="evt/";
	if (isset($_GET['evtdir']))
		$evtDir=$_GET['evtdir'];
	elseif (isset($gSessionDir))
		$evtDir=$gSessionDir."/";
		
	if ($evtDir=='')
		ErrorExit("missing parameter 'evtdir'");
	if ($evtDir[strlen($evtDir)-1]!='/')
		$evtDir.="/";
						
	$progress=false;
	if (isset($_GET['progress']))
		$progress=true;
	
	$audioFile="vaudio.";
	set_time_limit(1200);
	
	if (isset($_GET['index'])) {
		if (!isset($_GET['url']))
			die("ERROR missing url.");

		$index=$_GET['index'];
		$getUrl=$_GET['url'];
		
		$outFile=$evtDir.$audioFile.$index;
		
		if (!file_exists($outFile)) {
		
			$tempFile=$outFile.".tmp";
			$ofp=@fopen($tempFile, "wb");
			if (!$ofp)
				die("ERROR Can't create file '$tempFile'.");
				
			$ifp=@fopen($getUrl, "rb");
			if (!$ifp)
				die("ERROR Can't get response from '$getUrl'");
			
			$size=0;
			while ($data=fread($ifp, 32768)) {
				$dataSize=strlen($data);
				if ($dataSize==0)
					break;
				fwrite($ofp, $data, $dataSize);
				if ($progress) {
					$size+=$dataSize;
					echo("OK $size\n");
					flush();
				}
			}
			fclose($ifp);
			
			fclose($ofp);
			@chmod($tempFile, 0777);
			if (file_exists($outFile))
				@unlink($outFile);
			if (!@rename($tempFile, $outFile))
				die("ERROR can't rename file $tempFile to $outFile");
		}
		
		echo ("OK ".filesize($outFile));
				
		
	} else if (isset($_GET['merge'])) {
	
		// concatenate all files		
		$outFile=$evtDir.$audioFile."mp3";
	
		$tempFile=$evtDir.$audioFile."tmp";
		$ofp=@fopen($tempFile, "wb");
		if (!$ofp)
			die("ERROR Can't create file '$tempFile'");
		
		for ($i=0; ; $i++) {
			$filename=$evtDir.$audioFile.$i;
			if (file_exists($filename)) {
				$content=@file_get_contents($filename);
				if ($content==false) {
					die("ERROR can't read file $filename");
				} else {
					fwrite($ofp, $content);
				}
				@unlink($filename);
			} else {
				break;
			}
		}
		fclose($ofp);
		@chmod($tempFile, 0777);
		
		$fsize=filesize($tempFile);
		if ($fsize>0) {
			if (file_exists($outFile))
				@unlink($outFile);
			if (!@rename($tempFile, $outFile))
				die("ERROR can't rename file $tempFile to $outFile");
			echo ("OK ".$fsize);
		} else {
			die("ERROR audio files not found.");		
		}
			
	} else if (isset($_GET['download']) && $_GET['download']!='') {
		$outFile=$evtDir.$audioFile."mp3";
		if (file_exists($outFile)) {
			$outFilename=$_GET['download'].".mp3";
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			header("Content-Length: " . filesize($outFile));
			header("Content-Type: audio/mpeg");
			header("Content-Disposition: attachment; filename=${outFilename}");
//			readfile($outFile);
			$fp=@fopen($outFile, "rb");
			if (!$fp)
				die("ERROR Couldn't open $outFile");
			while (!feof($fp)) {
				$data=fread($fp, 32768);
				echo $data;
			}
			fclose($fp);
			exit();
		} else {
			die("ERROR file '$outFile' not found.");
		}
	}

?>