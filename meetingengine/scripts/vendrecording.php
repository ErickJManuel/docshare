<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


	$frameFilePrefix="frm";

	// this function should not exit the script
	function startEvtElement($parser, $name, $attrs) { 
		global $endRecTime, $startRecTime, $eventType, $eventContent, $eventDir, $eventTime;
		global $startSharingTime;
		global $gDocDir, $keyEvent, $currentIndex, $startIndex, $keyEventTime;
		global $viewEvent, $viewEventTime;
		if ($name=="event" && isset($attrs['type'])) { 
			$eventType=$attrs['type'];
			$eventTime=(integer)($attrs['time']);
		
			if ($currentIndex>=$startIndex) {
				if ($attrs['type']=='StartRecording') {
					$startRecTime=$eventTime;
					if ($startSharingTime==0)
						$startSharingTime=$startRecTime;

					$eventContent=str_replace("StartRecording", "StartMeeting", $eventContent);
					// set the current view 
					if ($viewEvent!='') {
						// replace the event time with StartRecording time +1
						$evtTime=$eventTime+1;
						$theEvent=str_replace("time=\"$viewEventTime\"", "time=\"$evtTime\"", $viewEvent);
						$eventContent.="\r\n".$theEvent;
					}
					// add the screen display event saved
					if ($keyEvent!='') {
						// replace the event time with StartRecording time +1
						$evtTime=$eventTime+1;
						$theKeyEvent=str_replace("time=\"$keyEventTime\"", "time=\"$evtTime\"", $keyEvent);
						$eventContent.="\r\n".$theKeyEvent;
					}
				} elseif ($attrs['type']=='EndRecording' || $attrs['type']=='EndMeeting') {
					$endRecTime=(integer)($attrs['time']);
					$eventContent=str_replace("EndRecording", "EndMeeting", $eventContent);
				} 
			}
		} else if ($name=='documentinfo' && isset($attrs['fileurl'])) {
			// if this is a relative url, remove the event dir path name
			$docurl=$attrs['fileurl'];
			$pos1=strpos($docurl, "/vdocuments/");
			if ($pos1>0) {
				$evtPath=substr($docurl, 0, $pos1+1);
				$eventContent=str_replace($evtPath, "", $eventContent);
			}

		} else if ($name=='slide' && isset($attrs['slideurl'])) {
			$slideUrl=$attrs['slideurl'];
			if (($pos1=strpos($slideUrl, "/vlibrary/"))>0) {
				$pos2=$pos1+strlen('/vlibrary/');
				$liburl=substr($slideUrl, 0, $pos2);
				$filepath=substr($slideUrl, $pos2);
				$eventContent=str_replace($liburl, $gDocDir, $eventContent);
				$slideFile=$eventDir.$gDocDir.$filepath;
			
				// copy the slide from the library to the meeting document folder
				if (!file_exists($slideFile) || filesize($slideFile)==0) {
					$response = @file_get_contents($slideUrl);
					
					/* the user can reprocess so no need to retry
					if (!$response) {
						sleep(1);
						$response=@file_get_contents($slideUrl);
					}
					*/
					
					// the slide may have been deleted from the library
					if ($response) {					
						$filedir=str_replace(basename($slideFile), '', $slideFile);
						//ErrorExit("dir ".$filedir);
						if ($filedir!='' && !is_dir($filedir)) {
							umask(0);
							@mkdir($filedir, 0777);
						}
						
						$fp=@fopen($slideFile, "w");
						if ($fp) {
							fwrite($fp, $response);
							fclose($fp);
							@chmod($slideFile, 0777);
						}
					}
				}
			} elseif (($pos1=strpos($slideUrl, "/vdocuments/"))>0) {
				$evtPath=substr($slideUrl, 0, $pos1+1);
				$eventContent=str_replace($evtPath, "", $eventContent);
			}
		}
	} 
	function endEvtElement($parser, $name) { 
	} 

	// start from the EndRecording event and works backward to the StartRecording event
	function catEvents($dir, $todir) {
		global $endRecTime, $startRecTime, $eventType, $eventContent, $eventTime;
		global $startSharingTime;
		global $gXMLHeader, $keyEvent, $keyEventTime, $startIndex, $currentIndex;
		global $viewEvent, $viewEventTime;
		
		$outFile=$todir."vevents.xml";
		$filePrefix="evt";
		$fileExt=".xml";
		
		// allow reprocessing so don't check for the output file's existance
//		if (file_exists($outFile))
//			return true;
				
		$ofp=@fopen($outFile, "w");
		if (!$ofp) {
			return false;
		}
		fwrite($ofp, "$gXMLHeader\r\n<eventlist>\r\n");
	    
		$startRecTime=$endRecTime=0;
		$startSharingTime=0;
		$i=0;
		$keyEvent='';
		$viewEvent='';
		$viewEventTime=0;
		while(1) {
			$filename=$dir.$filePrefix.$i.$fileExt;
			$ifp= @fopen($filename, "r");
			if ($ifp) {				
				$content=fread($ifp, filesize($filename));
				$eventContent=$content;
				$eventType='';
				$currentIndex=$i;
				$eventTime=0;
				
				$xml_parser = xml_parser_create("UTF-8"); 
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
				xml_set_element_handler($xml_parser, "startEvtElement", "endEvtElement"); 
				
				// the parser may modify eventContent
				if (!xml_parse($xml_parser, $content, feof($ifp))) { 
					//					echo "can't parse $filename\n";
				} else {
					
					if ($currentIndex>=$startIndex) {
						fwrite($ofp, $eventContent);
						fwrite($ofp, "\r\n");						
					} else {
						
						// save any event that shows a display so we can append it after StartRecording to preserve the state
						if ($eventType=='SendSlide' || 
								$eventType=='StartWhiteboard' ||
								$eventType=='AddWhiteboard' ||
								$eventType=='StartScreenSharing') {
							$keyEvent=$eventContent; // save the event
							$keyEventTime=$eventTime;
							if ($eventType=='StartScreenSharing')
								$startSharingTime=$eventTime;
						} elseif ($eventType=='EndWhiteboard' ||
								$eventType=='DelWhiteboard' ||
								$eventType=='PauseScreenSharing' ||
								$eventType=='EndScreenSharing' ||
								$eventType=='EndPresentation') {
							$keyEvent='';	// delete the saved event
							$keyEventTime=0;
							if ($eventType=='PauseScreenSharing' || $eventType=='EndScreenSharing')
								$startSharingTime=0;
							
						} elseif ($eventType=='SetView') {
							$viewEvent=$eventContent; // save the event
							$viewEventTime=$eventTime;
						}						
					}
				}	
				fclose($ifp);
				xml_parser_free($xml_parser); 
				if ($endRecTime!=0) {
					break;
				}
	
				$i++;
			} else
				break;
		}
		fwrite($ofp, "</eventlist>\r\n");
		fclose($ofp);
		@chmod($outFile, 0777);
		
		return true;
	}
	

	
	function catFrames($dir, $todir) {
		global $endRecTime, $startRecTime;
		global $startSharingTime;
		global $gFramesDir, $gXMLHeader, $frameFilePrefix;
			
		$inFile=$dir."vframes.xml";

		$outFile=$todir."vframes.xml";
//		if (file_exists($outFile))
//			return true;
		$ofp=@fopen($outFile, "w");
		if (!$ofp)
			return false;

		fwrite($ofp, "$gXMLHeader\r\n<frames>\r\n");
		$ifp = @fopen ($inFile, "r");
		if ($ifp) {
/*
		if ($ifp) {		
			$content=fread($ifp, filesize($inFile));
			fwrite($ofp, $content);
			fclose ($ifp);
		}
*/		
			$i=0;
			$toIndex=0;
			$priorFrames=array();
			$lastKey=-1;
			while (!feof($ifp)) {
				$buffer = fgets($ifp, 512); 
				if (strpos($buffer, "<f t=")!==false) {
					list($frameTime) = sscanf($buffer, "<f t=\"%d\"");
					if ($frameTime>=$startSharingTime && $frameTime<=$endRecTime) {	
						
						// screen sharing started before the recording started
						// we need to skip all the frames until we reach the most recent key frame right before recording start
						if ($frameTime<=$startRecTime) {
							// save the record to a list
							array_push($priorFrames, array($i, $buffer));
							// save the last key frame position
							if (strpos($buffer, "k=")!==false) {
								$lastKey=count($priorFrames)-1;
							}
							//$buffer=str_replace($frameTime, ($startRecTime+1), $buffer);
							
						} else {
							
							// we have gone past the recording start
							if ($lastKey>=0) {
								$len=count($priorFrames);
								// write out all the frames from the last key frame to the recording start
								for ($k=$lastKey; $k<$len; $k++) {
									$index=$priorFrames[$k][0];
									$buf=$priorFrames[$k][1];
									// need to reset the frame time to the recording start time+1 so they will be included in the playback
									list($aTime) = sscanf($buf, "<f t=\"%d\"");
									$buf=str_replace($aTime, ($startRecTime+1), $buf);
									
									// write out the record
									$frameFile=$frameFilePrefix.$index.".swf";
									$fromFile=$dir.$frameFile;
									//$toFile=$todir.$gFramesDir.$frameFile;
									$toFile=$todir.$gFramesDir.$frameFilePrefix.$toIndex.".swf";
									
									if (file_exists($fromFile) && @copy($fromFile, $toFile)) {
										fwrite($ofp, $buf);
										$toIndex++;
									}									
								}
								// important to set it so we only do this once
								$lastKey=-1;
							}
							
							
							$frameFile=$frameFilePrefix.$i.".swf";
							$fromFile=$dir.$frameFile;
							//$toFile=$todir.$gFramesDir.$frameFile;
							$toFile=$todir.$gFramesDir.$frameFilePrefix.$toIndex.".swf";
							
							if (file_exists($fromFile) && @copy($fromFile, $toFile)) {
								fwrite($ofp, $buffer);
								$toIndex++;
							}
						}
					}			
				}
				$i++;
			}

			fclose ($ifp);
		}

		fwrite($ofp, "</frames>\r\n");
		fclose($ofp);
		@chmod($outFile, 0777);
		
		return true;
	}

	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	include_once $includeFile;
	include_once($gHostFile); //defined in vinclude.php
/*	
	$id='';
	if (isset($GET_VARS['id']))
		$id=$GET_VARS['id'];
	$code='';
	if (isset($GET_VARS['code']))
		$code=$GET_VARS['code'];

	if (!IsAuthorized($id, $code))
		ErrorExit("Not authorized $id $code mcode=$mcode");
*/
/*
	$evtDir="evt/";
	if (isset($GET_VARS['evtdir'])) {
		$evtDir=$GET_VARS['evtdir'];
		$evtDir.="/";
	}
*/
	$evtDir="evt/";
	if (isset($_GET['evtdir']))
		$evtDir=$_GET['evtdir'];
	elseif (isset($gSessionDir))
		$evtDir=$gSessionDir."/";
		
	if ($evtDir=='')
		ErrorExit("missing parameter 'evtdir'");
	if ($evtDir[strlen($evtDir)-1]!='/')
		$evtDir.="/";

	$meeting_id='';
	if (isset($GET_VARS['meeting_id']))
		$meeting_id=basename($GET_VARS['meeting_id']);
	if ($meeting_id=='')
		ErrorExit("missing parameter 'meeting_id'");
/*		
	$server='';
	if (isset($GET_VARS['server']))
		$server=$GET_VARS['server'];
	if ($server=='')
		ErrorExit("missing parameter 'server'");
*/
	$title='';
	if (isset($GET_VARS['title']))
		$title=$GET_VARS['title'];
/*
	$userId='';
	if (isset($GET_VARS['user_id']))
		$userId=$GET_VARS['user_id'];
*/		
	$startIndex=0;
	if (isset($GET_VARS['start_index']))
		$startIndex=(integer)$GET_VARS['start_index'];
		
//	if ($startIndex==0)
//		exit("start_index not set.");
			
	
	$recEvtDir="evt";
	if (isset($GET_VARS['session_dir']))
		$recEvtDir=$GET_VARS['session_dir'];
/*		
	$isHost=IsHost($userId, $recEvtDir);
	if (!$isHost)
		ErrorExit( "Not authorized.");	
*/	
	$recDir="../".$meeting_id."/";
	$todir=$recDir.$recEvtDir."/";
	
	// this may happen if EndRecording is called multiple times
//	if (file_exists($todir."vevents.xml")) {
//		ErrorExit ("file ".$todir."vevents.xml already exists");
//	}
	
	$indexFile="index.html";
	$mode=fileperms("./");
	if (!MyMkDir($recDir, $mode, $indexFile))
		ErrorExit ("can't create ".$todir);
	sleep(1);
	if (!MyMkDir($todir, $mode, $indexFile))
		ErrorExit ("can't create ".$todir.$evtDir);
	sleep(1);
	if (!MyMkDir($todir.$gDocDir, $mode, $indexFile))
		ErrorExit ("can't create ".$todir.$gDocDir);	
	if (!MyMkDir($todir.$gFramesDir, $mode, $indexFile))
		ErrorExit ("can't create ".$todir.$gFramesDir);
	sleep(1);
	
	$ext='.php';
	$scriptDir="../../../scripts/";
	$phpFile="vscript".$ext;
	$outFile=$recDir."vscript".$ext;
	umask(0);
	$content="<?php \$gScriptDir='".$scriptDir."'; require '".$scriptDir."vscript.php'; ?>";
	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't open file ".$outFile);
		
	$swfFile="../../../viewer.swf";
//	$arg="MeetingServer=".$server;
//	$arg.="&MeetingID=".$meeting_id;

	$phpFile="viewer".$ext;
	$outFile=$recDir.$phpFile;
/*
	$content="<?php \$winTitle=\"$title\";";
	$content.=" \$swfFile=\"$swfFile\";";
	$content.=" \$arg=\"$arg\";";
	$content.=" include(\"$scriptDir$phpFile\");\n";
	$content.="?>";
*/	
	$content="<?php\n";
	$content.=" include(\"../../../site_config.php\");";
	$content.=" \$winTitle=\"$title\";";
	$content.=" \$swfFile=\"$swfFile\";";
	$content.=" \$apiUrl=\$serverUrl.\"api.php\";";
	$content.=" \$arg=\"MeetingServer=\$apiUrl&MeetingID=$meeting_id\";";
	$content.=" include(\"$scriptDir$phpFile\");\n";
	$content.="?>";


	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("Can't create file ".$outFile);
		
	$phpFile="vmhost".$ext;
	$outFile=$recDir.$phpFile;
	$content="<?php \$gSessionDir=\"$recEvtDir\"; ?>";

	if (!CreateFile($outFile, $content, $mode))
		ErrorExit("can't create file ".$outFile);

	set_time_limit(300);
	
	$sessionFile=$evtDir.$gEvtDir."vsession.inf";
	$fp = @fopen ($sessionFile, "r");
	if ($fp) {
		$line = fread ($fp, filesize ($sessionFile));
		fclose($fp);
	} else {
		ErrorExit("Can't open file ".$sessionFile);
	}
	
	
	list($lastEventId, $keyEvtID) = sscanf($line, "LastEvent=%d&LastKeyEvent=%d");
	$startRecTime=$endRecTime=0;
	$startSharingTime=0;
	$eventType=$eventContent=$keyEvent=$viewEvent='';
	$eventDir=$todir;
	$currentIndex=0;
	$keyEventTime=0;
	$viewEventTime=0;
	$eventTime=0;

	if (!catEvents($evtDir.$gEvtDir, $todir))
		ErrorExit ("Can't process events.");
	if (!catFrames($evtDir.$gFramesDir, $todir))
		ErrorExit ("Can't process frames.");
	
	if (file_exists($evtDir.$gAttFile)) {
		if (file_exists($todir.$gAttFile))
			@unlink($todir.$gAttFile);
		@rename($evtDir.$gAttFile, $todir.$gAttFile);
	}
		
	if (!CopyDir($evtDir.$gDocDir, $todir.$gDocDir))
		ErrorExit("Can't copy dir ".$evtDir.$gDocDir);
	
//	if (!CopyDir($evtDir.$gFramesDir, $todir.$gFramesDir))
//		ErrorExit("Can't copy dir ".$evtDir.$gFramesDir);

	$duration=$endRecTime-$startRecTime;
	if ($duration<0)
		$duration=0; // shouldn't happen unless EndRecording is not found in the events
	
	echo ("OK\nduration=$duration");
   
?>
