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
	include $includeFile;
	include $gHostFile;
	
	$evtDir="evt/";
	if (isset($_GET['evtdir']))
		$evtDir=$_GET['evtdir'];
	elseif (isset($gSessionDir))
		$evtDir=$gSessionDir."/";

	if ($evtDir=='')
		ErrorExit("missing parameter 'evtdir'");
	if ($evtDir[strlen($evtDir)-1]!='/')
		$evtDir.="/";
/*
	$evtDir="";
	if (isset($GET_VARS['evtdir'])) {
		$evtDir=$GET_VARS['evtdir'];
		$evtDir.="/";
	}
*/
	// get a range of events from event id e0 to e1
	$e0=NULL;
	if (isset($GET_VARS['e0']))
		$e0=(integer)$GET_VARS['e0'];
	
	$e1=NULL;
	if (isset($GET_VARS['e1']))
		$e1=(integer)$GET_VARS['e1'];	
		
	// skip any attendee events
	$skipAtt=false;
	if (isset($GET_VARS['skip_att']))
		$skipAtt=true;	
	
	$timeOut=0;
	if (isset($GET_VARS['timeout'])) {
		$timeOut=$GET_VARS['timeout'];
    	if ($timeOut>$gMaxWaitTime)
    		$timeOut=$gMaxWaitTime;
	}
	
	if (is_null($e0) || is_null($e1) || $e0<0 || $e1<0 ) {
		$sessionFile=$evtDir.$gEvtDir.$gSessionFile;
		
		$fp = @fopen ($sessionFile, "rb");		
		if ($fp && flock($fp, LOCK_SH)) {
			$line=fread($fp, filesize($sessionFile));
			flock($fp, LOCK_UN);				
			fclose($fp);
			list($lastEventId, $keyEvtID) = sscanf($line, "LastEvent=%d&LastKeyEvent=%d"); 
			if (is_null($e1))
				$e1=(integer)$lastEventId;
			elseif ($e1<0)
				$e1=(integer)$lastEventId+1+$e1;	// starting from the end
				
			if (is_null($e0)) {
				$e0=(integer)$lastEventId+1;	// next event
				$e1=$e0;
			} elseif ($e0<0) {
				$e0=(integer)$lastEventId+1+$e0; // starting from the end
			}
			
		} else {
			$e0=$e1=0;
		}
	}
	
	$filePrefix="evt";
	$fileExt=".xml";
	$dir=$evtDir.$gEvtDir;
	
	// request single event. wait for it to be available if timeout is given
	$doExist=false;
//	if ($e1==$e0 && $timeOut>0) {
	if ($timeOut>0) {
		$startTime=time();
		$canusleep=-1;
		$filePath=$dir.$filePrefix.$e0.$fileExt;
		while (1) {
			if (file_exists($filePath)) {
				$doExist=true;
				break;
			}
			MSleep($gCheckFileDelay, $canusleep);
			$currTime=time();
			if (($currTime-$startTime)>$timeOut)
				break;
		}
	} else {
		$doExist=true;
	}
	
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');	
	header("Content-Type: text/xml");			
	echo "$gXMLHeader\r\n<eventlist start=\"$e0\">\r\n";
	
	if ($doExist) {
		for ($i=$e0; $i<=$e1; $i++) {
			$filename=$dir.$filePrefix.$i.$fileExt;
			if (!file_exists($filename))
				break;
			$ifp= @fopen($filename, "r");
			if ($ifp) {
				$content=fread($ifp, filesize($filename));
				
				$skip=false;
				if ($skipAtt) {
					if (strpos($content, "AddAttendee")!==false)
						$skip=true;
					elseif (strpos($content, "RemoveAttendee")!==false)
						$skip=true;
/*					elseif (strpos($content, "RaiseHand")!==false)
						$skip=true;
					elseif (strpos($content, "UnraiseHand")!==false)
						$skip=true; */
					elseif (strpos($content, "AllowPresenting")!==false)
						$skip=true;
					elseif (strpos($content, "DenyPresenting")!==false)
						$skip=true;
					elseif (strpos($content, "GrantControl")!==false)
						$skip=true;
					elseif (strpos($content, "AllowPresenting")!==false)
						$skip=true;
					elseif (strpos($content, "DenyPresenting")!==false)
						$skip=true;
					elseif (strpos($content, "AllowDrawing")!==false)
						$skip=true;
					elseif (strpos($content, "DenyDrawing")!==false)
						$skip=true;
//					elseif (strpos($content, "SetEmoticon")!==false)
//						$skip=true;
					elseif (strpos($content, "SetCaller")!==false)
						$skip=true;
					elseif (strpos($content, "SetAttendee")!==false)
						$skip=true;
					elseif (strpos($content, "SendAlert")!==false)
						$skip=true;
				}				

				if ($skip)
					echo "<event type=\"Null\"/>";
				else
					echo ($content);
					
				echo ("\r\n");
				fclose($ifp);
			} else {
				break;
			}
		}
	}
	echo "</eventlist>\r\n";

   
?>
