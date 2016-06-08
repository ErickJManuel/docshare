<?php 

// (c)Copyright 2004, Persony, Inc. All rights reserved.
// redefine the user error constants - PHP 4 only 

	function CreatePermissionFile($theDir, $attId, $type)
	{
		global $gAttDir, $gDrawFileExt, $gPresFileExt;
		$theFile=$theDir.$gAttDir.$attId;
		if ($type=="AllowDrawing") {
			$theFile.=".".$gDrawFileExt;
		} elseif ($type=='AllowPresenting') {
			$theFile.=".".$gPresFileExt;
		}		
		
		if (!file_exists($theFile)) {
			$fp=@fopen($theFile, "w");
			if ($fp) {
				fwrite($fp, "1");
				fclose($fp);
				@chmod($theFile, 0777);
			} else {
				ErrorExit("Can't create ".$theFile);
			}
		}
		
	}
	
	function DeletePermissionFile($theDir, $attId, $type)
	{
		global $gAttDir, $gDrawFileExt, $gPresFileExt;
		$theFile=$theDir.$gAttDir.$attId;
		if ($type=="DenyDrawing") {
			$theFile.=".".$gDrawFileExt;
		} elseif ($type=='DenyPresenting') {
			$theFile.=".".$gPresFileExt;
		}		
		
		if (file_exists($theFile)) {
			unlink($theFile);
		}
	}



	function SearchFiles($theDir, $str)
	{
		$foundFile="";
		if ($dh = opendir($theDir)) { 		
			while (($file = readdir($dh)) !== false) { 
				if (is_file($theDir.$file)) {
					$filepath=$theDir.$file;
					$ifp= fopen($filepath, "r");
					$content=fread($ifp, filesize($filepath));
					fclose($ifp);
					$pos=strpos($content, $str);
					if ($pos!==false) {
						$foundFile=$file;
						break;
					}
				}
			} 
			closedir($dh); 
		}
		return $foundFile;
	}	
	
	function errorXmlHandler($parser, $name, $attrs) { 
		global $xmlError;
		
		if ($name=="error") { 
			while (list($k, $v) = each($attrs)) { 
				if ($k=="message") {
					$xmlError=$v;
					break;
				}
			}		
		}
	}
	function recordXmlHandler($parser, $name, $attrs) { 
		global $recAudio, $recPhone, $recCode;
		
		if ($name=="recording") { 
			while (list($k, $v) = each($attrs)) { 
				if ($k=="recordAudio") {
					$recAudio=$v;
				} else if ($k=="phone") {
					$recPhone=$v;
				} else if ($k=="modCode") {
					$recCode=$v;
				}
			}		
		}
	}

	function attendeeXmlHandler($parser, $name, $attrs) { 
		global $attendeeName, $attendeeEmail, $attendeeType;
		global $theCallerId, $theCallerMuted, $theActiveTalker, $theCallId;
		
		if ($name=="attendeeinfo") { 
			while (list($k, $v) = each($attrs)) { 
				if ($k=="fullname") {
					$attendeeName=$v;
				} else if ($k=="email") {
					$attendeeEmail=$v;
				} else if ($k=='type') {
					$attendeeType=$v;
				}
			}
		// use callerinfo going forward
		} elseif ($name=="caller" || $name=="callerinfo") { 
			while (list($k, $v) = each($attrs)) {
				// use ParticipantNumber going forward
				if ($k=="callerId" || $k=="ParticipantNumber") {
					$theCallerId=$v;
				} elseif ($k=="Muted") {
					$theCallerMuted=$v;
				} elseif ($k=="ActiveTalker") {
					$theActiveTalker=$v;
				} elseif ($k=="CallId") {
					$theCallId=$v;
				}
			}	
		}
	}

	function econXmlHandler($parser, $name, $attrs) { 
		global $econName;
		
		if ($name=="emoticon") { 
			while (list($k, $v) = each($attrs)) { 
				if ($k=="name") {
					$econName=$v;
					break;
				}
			}		
		}
	}
/*
	function RmDirFiles($theDir)
	{
		if (file_exists($theDir)) {
			if ($dh = opendir($theDir)) { 		
				while (($file = readdir($dh)) !== false) { 
					if ($file!="." && $file!="..") {
						$filePath= $theDir.$file;
						if (is_file($filePath))
							unlink($filePath); 				
					}
				} 
				closedir($dh); 
			} 
		}
	}
*/	
	function RmDirFiles($dir){
		if (!is_dir($dir))
			return true;
		if ($dh = @opendir($dir)) { 
			$fileList = Array();
			while (($file = readdir($dh)) !== false) { 
				if ($file!="." && $file!="..") {
					$filePath= $dir.$file;
					if (is_file($filePath))
						$fileList[] = $filePath;
				}
			} 
			closedir($dh);
			foreach ($fileList as $fileItem) {
				$ok=@unlink($fileItem);
				if (!$ok) {
					echo "couldn't delete ${fileItem}";
					break;
				}
			} 
		}
	}
		
	function GetFile($theDir, $fileMatch)
	{
		if (!is_dir($theDir))
			return true;
		$theFile="";
		if ($dh = opendir($theDir)) { 		
			while (($file = readdir($dh)) !== false) { 
				if ($file!="." && $file!="..") {
					$pos=strpos($file, $fileMatch);
					if ($pos!==false) {
						$theFile=$file;
						break;
					}
				}
			} 
			closedir($dh); 
		} else {
			
		}

		return $theFile;
	}

//	function FormatEvent($type, $agent, $from, $fromid, $to, $toid, $time, $data) 
	function FormatEvent($type, $agent, $from, $to, $time, $data, $id, $fromName) 
	{
		$ag=StrToXml($agent);

		$line="<event id=\"".$id."\" time=\"".time()."\" type=\"$type\" useragent=\"$ag\"";
		
		if ($from!='') {
			$fr=StrToXml($from);
		
//			$line.=" from=\"$fr\" userid=\"$fromid\"";		
			$line.=" from=\"$fr\"";		
		}
		if ($fromName!='') {
			$frn=StrToXml($fromName);		
			$line.=" fromname=\"$frn\"";				
		}
		if ($to!='') {
			$to1=StrToXml($to);
			$line.=" to=\"$to1\"";
		}
				
		if ($data=='')
			$line.="/>";
		else
			$line.=">\r\n".$data."\r\n</event>\r\n";	
		
		return $line;
	}
/*	
	function LogEvent($logsDir, $type, $data, $theTime, $attDir="", $attFileExt="", $pollsDir="", $pollFileExt="")
	{
		global $gXMLHeader;
		
		if (file_exists($logsDir)) {
			$today=date("Y_m_d");
			
			$logFilename=GetFile($logsDir, $today);
			$ofp=NULL;
			
			$logFile="";
			if ($logFilename!="") {		
				$logFile="${logsDir}${logFilename}";
				$ofp=fopen($logFile, "r+b");				
				$offset=strlen("</logs>\r\n");
				fseek($ofp, -$offset, SEEK_END);			
			} else {		
				$logFile="${logsDir}${today}_".mt_rand().".xml";
				$ofp=fopen($logFile, "wb");
				fwrite($ofp, "$gXMLHeader\r\n<logs>\r\n");
			}
			
			if ($ofp) {
				fwrite($ofp, "<log time=\"$theTime\" type=\"$type\" >\r\n");

				if ($data!='')
					fwrite($ofp, $data."\r\n");	
				
				if ($type=="Meeting") {
					CatDir($ofp, $attDir, $attFileExt);
					CatDir($ofp, $pollsDir, $pollFileExt);
				}
				
				fwrite($ofp, "</log>\r\n</logs>\r\n");
				fclose($ofp);
				@chmod($logFile, 0777);
			}
		}
	}
*/
	function IsAttendeeEvent($type) {

		if ($type=="SendMessage" || $type=="AddAttendee" ||
			$type=="RemoveAttendee" || $type=="SendAnswer" ||
			$type=="RequestControl" || $type=="EndControl" ||
			$type=="SetEmoticon" || 
			$type=="SetCaller" || $type=="SetAttendee" || // SetCaller is deprecated. Use SetAttendee instead.
			$type=="StartStream" || $type=="EndStream" || $type=="SetStream" || $type=="SendCommand" ||
			$type=="StartMeeting"		// allow an attendee to start a meeting
//			$type=="RaiseHand" || $type=="UnraiseHand" || 
//			$type=="SendSlide" || 
//			$type=="MoveSelection" || $type=="DeleteSelection" ||  $type=="ClearDrawing" ||
//			$type=="DrawLines" || $type=="DrawShape" || // DrawLines is replaced with DrawShape
//			$type=="DrawCircle" || $type=="DrawText" 
			)
			return true;
		else
			return false;

//		return true;
	}
	function IsDrawingEvent($type) {

		if 	($type=="MoveSelection" || $type=="DeleteSelection" ||  $type=="ClearDrawing" ||
			$type=="DrawLines" || $type=="DrawShape" || // DrawLines is replaced with DrawShape
			$type=="DrawCircle" || $type=="DrawText" 
			)
			return true;
		else
			return false;	
	
	}

	function IsPresenterEvent($type) {

		if ($type=="StartScreenSharing" || $type=="PauseScreenSharing" || 
			$type=="EndScreenSharing" || $type=="SendDocument" ||
			$type=="SendURL" ||
			$type=="StartAudio" || $type=="SendQuestion" ||
			$type=="EndQuestion" || $type=="SendResults" ||
			$type=="GrantControl" || $type=="AllowDrawing" ||
			$type=="DenyDrawing" || $type=="StartWhiteboard" ||
			$type=="StartMedia" || $type=="SeekMedia" || 
			$type=="EndMedia" || $type=="SetMedia" ||
			$type=="AddWhiteboard" || $type=="DeleteWhiteboard" ||
			$type=="EndWhiteboard" || $type=="SetView" ||
			$type=="EndPresentation"  ||
			$type=="IdentifyPhone" || $type=="RefreshAttendees" ||
			$type=="SendSlide"
			)
			return true;
		else
			return false;	
	
	}

/*	
	function IsKeyEvent($type) {
	
		if ($type=="StartScreenSharing" || 
			$type=="EndScreenSharing" ||
			$type=="SendSlide" ||
			$type=="StartWhiteboard" ||
			$type=="EndWhiteboard" ||
			$type=="StartMeeting" ||
			$type=="EndMeeting")
			return true;
		else
			return false;	
	
	}
*/	
	function IsHostEvent($type) {
	
//		if ($type=="StartMeeting" || $type=="EndMeeting" || 
		if ($type=="EndMeeting" ||		// moved "StartMeeting" to IsAttEvent
			$type=="LockMeeting" || $type=="UnlockMeeting" ||
			$type=="AllowPresenting" || $type=="DenyPresenting" ||
			$type=="RefreshMeeting" || $type=="RestartMeeting" ||
			$type=="AddLabel" ||
			$type=="StartRecording" || $type=="EndRecording" ||
//			$type=="RequestStream" || $type=="EndStream" || $type=="SetStream" ||
			$type=="RequestStream" ||
			$type=="SendAlert" ||
			$type=="RedirectPage"
			)
			return true;
		else
			return false;	
	}
		
	function CatDir($ofp, $dir, $match) {
		if ($dh = @opendir($dir)) { 			
			while (($file = readdir($dh)) !== false) { 
				if (strpos($file, $match)===false)
					continue;						
					
				$filepath=$dir.$file;
				$ifp = fopen ($filepath, "rb");
				$content = fread($ifp, filesize($filepath));
				fclose($ifp);
				
				fwrite($ofp, "$content\r\n");	

			}
			closedir($dh); 
		}
	}
	
	
/*	
	function MyRmDir($dir, &$errMsg)
	{
		$ret=true;
		if (is_dir($dir) && ($dh = @opendir($dir))) {
			$fileList = Array();
			while (($file = @readdir($dh)) !== false) { 
				if ($file!="." && $file!="..") {
					$fileList[] = $dir."/".$file;
				}
			} 
			closedir($dh);
			$count=0;
			$errMsg='';
			foreach ($fileList as $fileItem) {
				$count++;
				if (is_file($fileItem)) {
					$ret=@unlink($fileItem);
					if (!$ret) {
						$errMsg="couldn't delete file ${fileItem}";
						break;
					}
				}
			} 
			if ($ret && $dir!=".") {
				$ret=@rmdir($dir);
				if (!$ret)
					$errMsg="Couldn't delete directory $dir";
			}

			$errMsg="Couldn't open directory $dir";
			$ret=false;
		}
		return $ret;
	}
*/	
/*	
	function WriteCallFile($afile, $callId, $callerId, $muted, $activeTalker)
	{
		$content="<?php\n";
		$content.=" \$_callId=\"$callId\";";
		$content.=" \$_callerId=\"$callerId\";";
		$content.=" \$_muted=\"$muted\";";
		$content.=" \$_activeTalker=\"$activeTalker\";";
		$content.="\n?>";

		$fp=@fopen($afile, "a");		
		if ($fp && flock($fp, LOCK_EX)) {
			ftruncate($fp, 0);
			fwrite($fp, $content);				
			flock($fp, LOCK_UN);
		}
		if ($fp) {
			fclose($fp);
			@chmod($afile, 0777);
			return true;
		}		
		return false;
	}
*/

	// gScriptDir is defined in vscript.php	
	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	require_once($includeFile);
	include_once($gHostFile); //defined in vinclude.php

	$agent=$GET_SERVER['HTTP_USER_AGENT'];
/*
	$code='';
	if (isset($GET_VARS['code']))
		$code=$GET_VARS['code'];
*/	

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
	$theDir=$GET_VARS['evtdir'];
	$theDir.="/";
*/
	if (!isset($_GET['type']))
		ErrorExit("Missing input parameter");
		
	$type=$GET_VARS['type'];

/* don't get fromName from the input because it doesn't work for Unicode characters
* get it from the attendee file instead if the file exists
*/	
	$fromName="";
	if (isset($GET_VARS['fromName']))
		$fromName=$GET_VARS['fromName'];
	
	$meetingId="";
	if (isset($GET_VARS['meetingId']))
		$meetingId=$GET_VARS['meetingId'];
		
	$serverId="";
	if (isset($GET_VARS['serverId']))
		$serverId=$GET_VARS['serverId'];

	$from="";
	if (isset($GET_VARS['from']))
		$from=$GET_VARS['from'];
	else if ($type=="AddAttendee") {
		// create a unique attendee id of 6 digits
		// only for AddAttendee
		for ($tt=0; $tt<10; $tt++) {
			$from=(string)mt_rand(100000, 999999);
			// need to check if it is unique in the meeting
			$afile=GetAttendeeFile($from);
			if (!file_exists($theDir.$gAttDir.$afile))
				break;
		}
	}

	$evtId="";
	if (isset($GET_VARS['id']))
		$evtId=$GET_VARS['id'];
	else
		$evtId=uniqid('');	
		
	$format='';
	if (isset($GET_VARS['format']))
		$format=$GET_VARS['format'];
	
	// set this to 0 to not clear meeting directories when "StartMeeting" is received.
	// set it to 0 when an attendee is allowed to start a meeting so in case mutliple "StartMeeting" are sent, we don't have the directories cleared multiple times.
	$clearDir='1';
	if (isset($GET_VARS['clear_dir']))
		$clearDir=$GET_VARS['clear_dir'];
	
/*		
	$password="";
	if (isset($GET_VARS['password']))
		$password=$GET_VARS['password'];
		
	$email="";
	if (isset($GET_VARS['email']))
		$email=$GET_VARS['email'];
*/	

	$attFile=GetAttendeeFile($from);

	$isHost=IsHost($from, $theDir);
	
	// Get fromName from the attendee file instead if it exists
	// The fromName passed in through the URL paramater doesn't work if it has Unicode characters.
	if ($fromName!='' && file_exists($theDir.$gAttDir.$attFile)) {
		@include_once($theDir.$gAttDir.$attFile);
		if (isset($_username) && isset($_userid) && $_userid==$from)
			$fromName=$_username;
	}

	
	if ($type!="StartReplay") {
//		include($gHostFile); //defined in vinclude.php

		if (IsHostEvent($type)) {
			if (!$isHost)
				exit( "ERROR not an authorized host");
		} else if (IsPresenterEvent($type)) {
			if (!$isHost) {
				$isPresenter=IsPresenter($from, $theDir);
				if (!$isPresenter)
					exit( "ERROR not an authorized presenter");	
			}
		} else if (IsDrawingEvent($type)) {
			if (!$isHost) {
				$canDraw=CanDraw($from, $theDir);
				if (!$canDraw)
					exit( "ERROR not authorized to draw");	
			}
		} else if (!IsAttendeeEvent($type)) {
			exit( "ERROR not a valid event '$type'");
		}
	}
	
	$to='';
	if (isset($GET_VARS['to'])) {
		$to=$GET_VARS['to'];
	}

	$data=GetPostData();
	$theTime=time();
	$sessionFile=$theDir.$gEvtDir.$gSessionFile;
	
	$isKeyEvent=false;
	if (isset($GET_VARS['isKey'])) {
		if ($GET_VARS['isKey']==1 || $GET_VARS['isKey']=="true")
			$isKeyEvent=true;
	}
	
	if ($type!='StartMeeting') {
		if (!file_exists($theDir.$gEvtDir)) {
			if ($type=='EndMeeting') {
				echo 'OK';
				exit();
			} else {
				ErrorExit("Directory '".$theDir.$gEvtDir."' does not exist");
			}
		}
	}
		
	if ($type=="StartMeeting") {
	
		$indexFile="index.html";
		$mode=fileperms("./");
		if (is_dir($theDir) && $clearDir=='1')
			MyRmDir($theDir, $dirErrMsg);
			
		MyMkDir($theDir, $mode, $indexFile);
		sleep(1);
		if (is_dir($theDir.$gFramesDir) && $clearDir=='1')
			MyRmDir($theDir.$gFramesDir, $dirErrMsg);
		MyMkDir($theDir.$gFramesDir, $mode, $indexFile);
		
		if (is_dir($theDir.$gDocDir) && $clearDir=='1')
			MyRmDir($theDir.$gDocDir, $dirErrMsg);		
		MyMkDir($theDir.$gDocDir, $mode, $indexFile);
		
		if (is_dir($theDir.$gEvtDir) && $clearDir=='1')
			MyRmDir($theDir.$gEvtDir, $dirErrMsg);				
		MyMkDir($theDir.$gEvtDir, $mode, $indexFile);
		
		if (is_dir($theDir.$gAttDir) && $clearDir=='1')
			MyRmDir($theDir.$gAttDir, $dirErrMsg);						
		MyMkDir($theDir.$gAttDir, $mode, $indexFile);

		if (is_dir($theDir.$gPollsDir) && $clearDir=='1')
			MyRmDir($theDir.$gPollsDir, $dirErrMsg);						
		MyMkDir($theDir.$gPollsDir, $mode, $indexFile);
	
	
	} else if ($type=="LockMeeting" || $type=="UnlockMeeting") {
		
		$status= ($type=="LockMeeting")?'Y':'N';
		$theUrl=$gServerUrl."?cmd=SET_MEETING&meeting="
			.$meetingId."&user=".$from."&locked=".$status;
					
		if ($response = @file_get_contents($theUrl)) {
			
			$xmlError='';
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, "errorXmlHandler", false); 
			
			if (!xml_parse($xml_parser, $response, true)) {
				
			} else if ($xmlError!='') {
				ErrorExit($xmlError);
			}

		} else {
			ErrorExit("Can't get response from ".$theUrl);
		}
					
	} else if ($type=="SendAnswer") {

//		$line=FormatEvent($type, $agent, $from, $id, "", "", $theTime, $data);
		$line=FormatEvent($type, $agent, $from, "", $theTime, $data, $evtId, $fromName);
		// get poll id from the xml data. look for the first pollid="xxx", where xxx is the id
		$quote="\"";
		$key=" pollid=$quote";
		$pos1=strpos($data, $key);
		if ($pos1===false) {
			$quote="'";
			$key=" pollid=$quote";
			$pos1=strpos($data, $key);
		}
		if ($pos1===false)
			ErrorExit("Illegal answer");
	
		$str=substr($data, $pos1+strlen($key));
		$pos2=strpos($str, "$quote");
		$qid=substr($str, 0, $pos2);
		
		if ($qid==null || $qid=='')
			ErrorExit("Illegal answer 2");
	
		$outFile=$theDir.$gPollsDir.$qid.".poll";
		$fp = @fopen ($outFile, "a");
		if (!$fp)
			ErrorExit("can't open ".$outFile);
		if (!flock($fp, LOCK_EX)) 
			ErrorExit("can't lock ".$outFile);

		fwrite($fp, $line);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($outFile, 0777);

		return;
/*		
		$outFile=$theDir.$gPollsDir.$attFile;
		$fp = fopen ($outFile, "wb");
		if ($fp) {
			fwrite($fp, $line);
			fclose($fp);
			@chmod($outFile, 0777);
		} else {
			ErrorExit("can't open ".$outFile);
		}
		return;	
*/
/*
	} else if ($type=="StartReplay") {
		// if replayer fullname is empty, fill it with the hostname
		if (strpos($data, "fullname=\"\"")!==false) {
			$hostname=@gethostbyaddr($_SERVER['REMOTE_ADDR']);
			$data=str_replace("fullname=\"\"", "fullname=\"".$hostname."\"", $data);
		}
		
		// add referrer
		if (isset($GET_SERVER['HTTP_REFERER'])) {
			$data=str_replace("/>", "referrer=\"".$GET_SERVER['HTTP_REFERER']."\" />", $data);
		}
		
//		LogEvent("../../".$gLogsDir, "Replay", $data, $theTime);
		return;	
*/

	} else if ($type=="AddAttendee") {

		$userip=GetIP();
		
		// get attendee name from post data
		// don't use $fromName because it doesn't work for unicode char
		$attendeeName='';
		$attendeeEmail='';
		$attendeeType='';
		$theCallerId=$theCallId=$theCallerMuted=$theActiveTalker='';
		if ($data && $data!='') {
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, "attendeeXmlHandler", false); 
			
			xml_parse($xml_parser, $data, true);
		}
		
/*				
		$addAttUrl=$gServerUrl."?cmd=ADD_ATTENDEE&meeting="
			.$meetingId."&attendee_id=".$from."&user_name="
			.rawurlencode($attendeeName)."&user_ip=".$userip;
			
		if ($password!='')
			$addAttUrl.="&password=".$password;
		if ($email!='')
			$addAttUrl.="&email=".rawurlencode($email);

		if ($response = file_get_contents($addAttUrl)) {

		} else {
			ErrorExit("Can't get response from ".$addAttUrl);
		}
		
		$xmlError='';
		if (strpos($response, "<error")!==false) {
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, "errorXmlHandler", false); 
			
			if (!xml_parse($xml_parser, $response, true)) {
				
			} else {
				echo "<status failed=\"$xmlError\"/>";
				exit();
			}	
		}
		
		echo $response;

*/
		// web attendee
		if ($attendeeName!='') {
			$fromName=$attendeeName;
			$attMax=$gMaxAttendees; // default max
			
			if (isset($gKeyCode)) {		// defined in $gHostFile
				$offset=intval(substr($gKeyCode, 0, 1)); // get int value of the first char, this is the offset
				// extract the att number limit from offset+1
				$attMax=intval(substr($gKeyCode, $offset+1));
			}
			
			// count the number of attendees (attendee files in the folder) in the meeting		
			$attCount=0;
			
			if ($dh = @opendir($theDir.$gAttDir)) { 
				while (($file = readdir($dh)) !== false) { 
					if (IsAttendeeFile($theDir.$gAttDir.$file)) {
						$modTime=filemtime($theDir.$gAttDir.$file);
						// don't count outdated file (attendee is gone)
						if (($theTime-$modTime)<=$gMaxAttDelay) {
							$attCount++;
						}
					}
				}				
			} else {
				ErrorExit("Couldn't access files on the server.");			
			}
			
			// check if the attendee is resuming a meeting (within the timeout)
			$resumeMeeting=false;		
			if (file_exists($theDir.$gAttDir.$attFile)) {
				$modTime=filemtime($theDir.$gAttDir.$attFile);
				if (($theTime-$modTime)<=$gMaxAttDelay) {
					$resumeMeeting=true;
				}
			}
			
			// if attMax is 0, there is no limit
			// if attendee count has reached max and the attendee is not resuming the meeting
			if ($attMax!=0 && $attCount>=$attMax && !$resumeMeeting) {
				echo "<status failed=\"meeting full\"/>";
				exit;
			}
			
			/*		
				// verify there is no duplicate login of a registered user
				$checkRegis=true; 
			//		if (isset($gVerifyRegistration)) // defined in $gHostFile
			//			$checkRegis=true;
				
				if ($checkRegis && $attendeeEmail!='') {
					$foundFile=SearchFiles($theDir.$gAttDir, "\$useremail=\"".htmlspecialchars($attendeeEmail)."\"");
					
					if ($foundFile=="" || $foundFile==$attFile) {
					
					} else {
						echo "<status failed=\"duplicate login\"/>";			
						exit;			
					}			
				}
			*/
		}

	} else if ($type=="PauseScreenSharing") {
		
		$pauseFile=$theDir.$gFramesDir.$gPauseFile;		
		$ffp=@fopen($pauseFile, "wb");
		if ($ffp) {
			fclose($ffp);
			@chmod($pauseFile, 0777);
		}
		
	} else if ($type=="EndScreenSharing" || $type=="EndMeeting") {
		
		$stopFile=$theDir.$gFramesDir.$gStopFile;		
		$ffp=@fopen($stopFile, "wb");
		if ($ffp) {
			fclose($ffp);
			@chmod($stopFile, 0777);
		}
		$pauseFile=$theDir.$gFramesDir.$gPauseFile;
		if (file_exists($pauseFile))
			@unlink($pauseFile);
		$startFile=$theDir.$gFramesDir.$gStartFile;
		if (file_exists($startFile))
			@unlink($startFile);
	
	} else if ($type=="StartScreenSharing") {
		$startFile=$theDir.$gFramesDir.$gStartFile;		
		$ffp=@fopen($startFile, "wb");
		if ($ffp) {
			fclose($ffp);
			@chmod($startFile, 0777);
		}
		$stopFile=$theDir.$gFramesDir.$gStopFile;		
		$pauseFile=$theDir.$gFramesDir.$gPauseFile;		
		if (file_exists($stopFile))
			@unlink($stopFile);
		if (file_exists($pauseFile))
			@unlink($pauseFile);
			
		$fSessionFile=$theDir.$gFramesDir.$gSessionFile;
		$keyframeID=0;
		$lastframeID=-1;
		if (file_exists($fSessionFile)) {
			$fp=fopen($fSessionFile, "r+b");
		} else {
			$fp=fopen($fSessionFile, "w+b");
		}
		if (!$fp)
			ErrorExit("Can't open frame session file");

		if (!flock($fp, LOCK_EX)) {
			if ($fp)
				fclose($fp);
			ErrorExit("Can't lock frame session file");
		}

		$fsize=filesize ($fSessionFile);
		if ($fsize>0) {
			rewind($fp);
			$line = fread ($fp, filesize ($fSessionFile)); 
			list($keyframeID, $lastframeID) = sscanf($line, "KeyFrame=%d&LastFrame=%d"); 
			rewind($fp);		
		}
		
		$keyframeID=$lastframeID+1;
		$line="KeyFrame=".$keyframeID."&LastFrame=".$lastframeID;
		rewind($fp);
		ftruncate($fp, 0);
		fwrite($fp, $line);
//		fflush($fp);
//		ftruncate($fp, ftell($fp));
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($fSessionFile, 0777);

	} else if ($type=="SendSlide" || $type=="StartWhiteboard" || $type=="AddWhiteboard" || $type=="DeleteWhiteboard" || $type=='EndPresentation' || $type=='EndWhiteboard') {
		$startFile=$theDir.$gFramesDir.$gStartFile;
		if (file_exists($startFile)) {
			ErrorExit("You cannot send this event when screen sharing is in progress.");			
		}
	}

	$eventData=FormatEvent($type, $agent, $from, $to, time(), $data, $evtId, $fromName);
//		$line=FormatEvent($type, $agent, $from, $id, $to, $toid, time(), $data);

//	$sessionLock=$theDir.$gEvtDir."vsession.lock";	
//	$sessionUnlock=$theDir.$gEvtDir."vsession.unlock";
	
//	$timeOut=15;
//	$lockOk=false;
	$sessionLine='';
	$lastEventId=0;

	if ($type=="StartMeeting" || !file_exists($sessionFile)) {

		$fp=@fopen($sessionFile, "w+b");
		if (!$fp)
			ErrorExit("Can't create session file");
		if (!flock($fp, LOCK_EX)) {
			if ($fp)
				fclose($fp);
			ErrorExit("Can't lock file");
		}
				
		$sessionLine="LastEvent=0&LastKeyEvent=0";

	} else {

		
		$fp = @fopen ($sessionFile, "r+b");
		if (!$fp)
			ErrorExit("Can't open session file");
		
		if (!flock($fp, LOCK_EX)) {
			if ($fp)
				fclose($fp);
			ErrorExit("Can't lock file");
		}
		
		rewind($fp);
//		$line = fgets($fp, 256);
		$line=fread($fp, filesize($sessionFile));
		
		list($lastEventId, $keyEvtID) = sscanf($line, "LastEvent=%d&LastKeyEvent=%d"); 
		$lastEventId++;
		if ($isKeyEvent)
			$keyEvtID=$lastEventId;
		$sessionLine="LastEvent=".$lastEventId."&LastKeyEvent=".$keyEvtID;

	}

	$evtFile=$theDir.$gEvtDir.$gEvtPrefix.$lastEventId.".xml";
	$evtTmpFile=$theDir.$gEvtDir.$gEvtPrefix.$lastEventId.".tmp";
	$evtFp = fopen ($evtTmpFile, "wb");
	if ($evtFp) {
		fwrite ($evtFp, $eventData);
		fclose ($evtFp);
		@chmod($evtTmpFile, 0777);
	} else {
		flock($fp, LOCK_UN);
		fclose($fp);
		ErrorExit("Can't write to event file");
	}

//	$nextFile=$theDir.$gEvtDir.$gEvtPrefix.($evtID+1).".xml";
	if (file_exists($evtFile))
		@unlink($evtFile);
	@rename($evtTmpFile, $evtFile);
	
	rewind($fp);
	ftruncate($fp, 0);
	fwrite($fp, $sessionLine);
//	fflush($fp);
//	ftruncate($fp, ftell($fp));
	flock($fp, LOCK_UN);
	fclose($fp);
	@chmod($sessionFile, 0777);

/*
	$sessionFileTmp=$sessionFile.".tmp";
	$fp = fopen ($sessionFileTmp, "wb");
	if ($fp) {
		fwrite($fp, $sessionLine);
		fclose($fp);
		@chmod($sessionFileTmp, 0777);
	}
	if (file_exists($sessionFile))
		unlink($sessionFile);
	rename($sessionFileTmp, $sessionFile);

	if (file_exists($sessionUnlock))
		unlink($sessionUnlock);
	rename($sessionLock, $sessionUnlock);		
*/

	$pollFileExt=".pol.xml";

/*	
	// change the attFile mod time so we know this attendee is still in the meeting
	// don't create a new file
	if (file_exists($theDir.$gAttDir.$attFile))
		touch($theDir.$gAttDir.$attFile);
*/	
	if ($type=="AddAttendee") {
		// web attendee
		if ($attendeeName!='') {
			$afile=$theDir.$gAttDir.$attFile;
			$ag=StrToXml($agent);
			
			if (file_exists($afile)) {
				@include_once($afile);
				//$modTime=filemtime($afile);
				$awayTime=$theTime-$_modTime;
				
				// if the attendee is resuming after being away from the meeting beyond the timeout period
				// add the away time to 'break_time'			
				if ($awayTime>$gMaxAttDelay) {
					if ($_breakTime>0)
						$_breakTime+=$awayTime;
					else
						$_breakTime=$awayTime;
				}
				$_modTime=$theTime;
				$_inMeeting="true";
				if ($webcam=='true') {
					$_camStartTime=$theTime;
				}
				WriteAttendeeFile($afile, $_useragent, $_userip, $_userid, $_username, $_useremail, $_usertype,
						$_startTime, $_modTime, $_breakTime, $_drawing, $_isHost, $_isPresenter, $_emoticon, $_webcam, $_callerId, 
						$_lastCallerId, $_callId, $_camStartTime, $_camTime, "true", $serverId, false);
				
			} else {
				$isHostStr=$isHost?"true":"false";
				$can_draw = "false";
				$can_present = "false";
				
				if(IsPanelMeeting())
				{
					$can_draw = "true";
					$can_present = "true";
					CreatePermissionFile($theDir, $from, "AllowDrawing");
					CreatePermissionFile($theDir, $from, "AllowPresenting");
				}
					
				if (!WriteAttendeeFile($afile, $ag, 
							$userip, $from, $attendeeName, $attendeeEmail, $attendeeType,
							$theTime, $theTime, 0, $can_draw, $isHostStr, $can_present, "", "false", $theCallerId, 
							$theCallerId, $theCallId, 0, 0,  "true", $serverId, false))
					ErrorExit("can't open ".$afile);
			}
		} else if ($theCallId!='') {
			// add an audio caller
/*
			$afile=$theDir.$gAttDir.GetCallFile($theCallId);
			if (!WriteCallFile($afile, $theCallId, $theCallerId, $theCallerMuted, $theActiveTalker))
				ErrorExit("can't open ".$afile);
*/
		}
	} else if ($type=="RemoveAttendee") {
/* don't remove the file because we need it for the log		
		if (file_exists($theDir.$gAttDir.$attFile))
			@unlink($theDir.$gAttDir.$attFile);
*/	
		if ($to!='')
			$attToRemove=$to;
		else 
			$attToRemove=$from;
		
		$afile=$theDir.$gAttDir.GetAttendeeFile($attToRemove);			
//		$afile=$theDir.$gAttDir.$attFile;			
		if (file_exists($afile)) {
			@include_once($afile);
			$_modTime=$theTime;
			if ($_webcam=='true' && $_camStartTime>0) {
				$_camTime+=($theTime-$_camStartTime);
				$_camStartTime=$theTime;
			}
			WriteAttendeeFile($afile, $_useragent, $_userip, $_userid, $_username, $_useremail, $_usertype,
					$_startTime, $_modTime, $_breakTime, $_drawing, $_isHost, $_isPresenter, $_emoticon, $_webcam, $_callerId, 
					$_lastCallerId, $_callId, $_camStartTime, $_camTime, "false", $_serverId, true);
					
		}
		
	} else if (	$type=="AllowDrawing" || $type=="DenyDrawing" || 
				$type=="AllowPresenting" || $type=="DenyPresenting" ||
//				$type=="RaiseHand" || $type=="UnraiseHand" ||
				$type=="StartStream" || $type=="EndStream" ||
				$type=="SetEmoticon" || $type=="SetCaller" || $type=="SetAttendee"
				) {
/*		
		if ($type=='RaiseHand' || $type=='UnraiseHand' || $type=='SetEmoticon') {
			$setAttUrl=$gServerUrl."?cmd=SET_ATTENDEE&meeting="
				.$meetingId."&attendee_id=".$from;
		} else {			
			$setAttUrl=$gServerUrl."?cmd=SET_ATTENDEE&meeting="
				.$meetingId."&host_id=".$from."&attendee_id=".$to;
		}
		if ($password!='')
			$setAttUrl.="&password=".$password;
			
		if ($type=="AllowDrawing")			
			$setAttUrl.="&can_draw=Y";
		else if ($type=="DenyDrawing")
			$setAttUrl.="&can_draw=N";
		else if ($type=="AllowPresenting")
			$setAttUrl.="&can_present=Y";
		else if ($type=="DenyPresenting")
			$setAttUrl.="&can_present=N";
		else if ($type=="RaiseHand")
			$setAttUrl.="&raise_hand=Y";
		else if ($type=="UnraiseHand")
			$setAttUrl.="&raise_hand=N";
		else if ($type=="SetEmoticon") {
			
			$econName=false;
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, "econXmlHandler", false); 
			
			xml_parse($xml_parser, $data, true);
			if ($econName!=false) {
				$setAttUrl.="&emoticon=$econName";
			}
		}
			
		if ($response = file_get_contents($setAttUrl)) {
		} else {
			ErrorExit("Can't get response from ".$setAttUrl);
		}
		
		$xmlError='';
		if (strpos($response, "<error")!==false) {
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, "errorXmlHandler", false); 
			
			if (!xml_parse($xml_parser, $response, true)) {
				
			} else {
				ErrorExit($xmlError);
			}	
		}
*/	

		if ($type=="AllowDrawing" || $type=="AllowPresenting")
			CreatePermissionFile($theDir, $to, $type);
		elseif ($type=="DenyDrawing" || $type=="DenyPresenting")
			DeletePermissionFile($theDir, $to, $type);		
		
		$filename='';	
		if ($type=='SetCaller' || $type=='SetAttendee') 
		{
			$theCallerId=$theCallId=$theCallerMuted=$theActiveTalker=false;
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
			xml_set_element_handler($xml_parser, "attendeeXmlHandler", false); 
			
			xml_parse($xml_parser, $data, true);
				
			if ($from!='')
				$filename=GetAttendeeFile($from);
//			else if ($theCallId!=false)
//				$filename=GetCallFile($theCallId);
//			else if ($theCallerId!=false)
//				$filename=GetCallFile($theCallerId);
			
		} elseif ($type=='SetEmoticon' || $type=='StartStream' || $type=='EndStream')
		{
			$filename=GetAttendeeFile($from);
		} else {			
			$filename=GetAttendeeFile($to);
		}	
		
		$theFile=$theDir.$gAttDir.$filename;
		if ($filename!='' && file_exists($theFile)) {
			@include_once($theFile);
			
			// All $_xxx variables are defined in $theFile
			
			if ($type=="AllowDrawing")			
				$_drawing="true";
			else if ($type=="DenyDrawing")
				$_drawing="false";
			else if ($type=="AllowPresenting")
				$_isPresenter="true";
			else if ($type=="DenyPresenting")
				$_isPresenter="false";
			else if ($type=="StartStream") {
				$_webcam="true";
				$_camStartTime=time();
			} else if ($type=="EndStream") {
				if ($_webcam=='true' && $_camStartTime>0) {
					$nowTime=time();
					$_camTime+=($nowTime-$_camStartTime);
					$_camStartTime=$nowTime;
				}
				$_webcam="false";
			} else if ($type=="SetEmoticon") {
				$econName=false;
				$xml_parser = xml_parser_create("UTF-8"); 
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
				xml_set_element_handler($xml_parser, "econXmlHandler", false); 
				
				xml_parse($xml_parser, $data, true);
				if ($econName!==false) {
					$_emoticon=$econName;
				}
			} else if ($type=="SetCaller" || $type=="SetAttendee") {
				if ($theCallerId!==false) {
					$_callerId=$theCallerId;
				}
				if ($theCallId!==false) {
					$_callId=$theCallId;
				}
/*
				if ($theCallerMuted!==false) {
					$_muted=$theCallerMuted;
				}			
				if ($theActiveTalker!==false) {
					$_activeTalker=$theActiveTalker;
				}
*/			
			}
			
			if ($from!='') {
				WriteAttendeeFile($theFile, $_useragent, $_userip, $_userid, $_username, $_useremail, $_usertype,
					$_startTime, $_modTime, $_breakTime, $_drawing, $_isHost, $_isPresenter, $_emoticon, $_webcam, $_callerId, 
					$_lastCallerId, $_callId, $_camStartTime, $_camTime, $_inMeeting, $_serverId, true);
			} else {
//				WriteCallFile($theFile, $_callId, $_callerId, $_muted, $_activeTalker);
			}
				
		} else {
			// ignore error because it is OK for the attendee file to not exist (for SetCaller)
//			ErrorExit("missing attendee file ".$theFile);			
		}
/*
		if (file_exists($theFile)) {
			$fp = fopen ($theFile, "r+b");
			if ($fp) {
				$content = fread($fp, filesize($theFile));
				ftruncate($fp, 0);
				fseek($fp, 0);
				if ($type=="AllowDrawing")			
					$line=str_replace("drawing=\"false\"", "drawing=\"true\"", $content);
				else if ($type=="DenyDrawing")
					$line=str_replace("drawing=\"true\"", "drawing=\"false\"", $content);
				else if ($type=="AllowPresenting")
					$line=str_replace("isPresenter=\"false\"", "isPresenter=\"true\"", $content);
				else if ($type=="DenyPresenting")
					$line=str_replace("isPresenter=\"true\"", "isPresenter=\"false\"", $content);
				
				fwrite($fp, $line);
				fclose($fp);
			} else {
				ErrorExit("can't open ".$theFile);
			}
		} else {
			ErrorExit("missing file ".$theFile);
		}
*/
/*		
	} else if ($type=="RaiseHand" || $type=="UnraiseHand") {
		
		$theFile=$theDir.$gAttDir.$attFile;
		if (file_exists($theFile)) {
			$fp = fopen ($theFile, "r+b");
			if ($fp) {
				$content = fread($fp, filesize($theFile));
				ftruncate($fp, 0);
				fseek($fp, 0);
				if ($type=="RaiseHand")			
					$line=str_replace("raiseHand=\"false\"", "raiseHand=\"true\"", $content);
				else
					$line=str_replace("raiseHand=\"true\"", "raiseHand=\"false\"", $content);
				
				fwrite($fp, $line);
				fclose($fp);
			} else {
				ErrorExit("can't open ".$theFile);				
			}
		} else {
			ErrorExit("missing file ".$theFile);
		}
*/
	} else if ($type=="SendQuestion" || $type=="EndQuestion") {
/* we don't need do this because the poll history is in the event list
		$currentPoll="current".$pollFileExt;
		$pollXmlLabel="poll";
		
		if ($type=="SendQuestion") {
			if ($dh = @opendir($theDir.$gPollsDir)) { 		
				while (($file = readdir($dh)) !== false) { 
					if ($file=="." || $file==".." || strpos($file, $pollFileExt)>0 || $file=="index.htm")
						continue;
						
					$filePath= $theDir.$gPollsDir.$file;
					unlink($filePath); 				
				} 
				closedir($dh); 
			} else {
				ErrorExit("can't open dir ".$theDir.$gPollsDir);
			}
			
			$pfile=$theDir.$gPollsDir.$currentPoll;
			$ofp=fopen($pfile, "wb");
			if ($ofp) {
				fwrite($ofp, "<$pollXmlLabel>\r\n");
				fwrite($ofp, $line);
				fclose($ofp);
				@chmod($pfile, 0777);
			} else {
				ErrorExit("can't open ".$pfile);
			}
*/
		} else if ($type=="EndQuestion") {
/*
			if ($dh = @opendir($theDir.$gPollsDir)) { 
				
				$pfile=$theDir.$gPollsDir.$currentPoll;
				$ofp=fopen($pfile, "ab");
				if ($ofp) {
					while (($file = readdir($dh)) !== false) { 
						if ($file=="." || $file==".." || strpos($file, $pollFileExt)>0 || $file=="index.htm")
							continue;						
							
						$filepath=$theDir.$gPollsDir.$file;
						$ifp = fopen ($filepath, "rb");
						$content = fread($ifp, filesize($filepath));
						fclose($ifp);
						
						fwrite($ofp, "$content\r\n");	

					}
					closedir($dh); 
					fwrite($ofp, "</$pollXmlLabel>\r\n");	
					fclose($ofp);
					@chmod($pfile, 0777);
					
					$plfile=$theDir.$gPollsDir.$theTime.$pollFileExt;
					if (file_exists($plfile))
						@unlink($plfile);
					@rename($pfile, $plfile);
				} else {
					ErrorExit("can't open ".$pfile);
				}
			} else {
				ErrorExit("can't open dir ".$theDir.$gPollsDir);
			} 			
		}
*/	
	} else if ($type=="EndMeeting") {
//		LogEvent("../../".$gLogsDir, "Meeting", $data, $theTime, $theDir.$gAttDir, ".xml", $theDir.$gPollsDir, $pollFileExt);			
	}

	if ($format=='xml') {
		echo "<event event_index='$lastEventId' event_id='$evtId' event_type='$type' sender_id='$from' target_id='$to' time='$theTime'/>";
	} else {
		echo "OK\nevent_index=$lastEventId\nevent_id=$evtId\nsender_id=$from\ntarget_id=$to\ntime=$theTime";
	}

	// $gSwfServer should be defined in gHostFile if it is set
	// the server is used to convert swf to jpeg for iphone
	// forward the event to the Java server	
	if ($type=='StartScreenSharing' || $type=='PauseScreenSharing' || $type=='EndScreenSharing') {
		if (isset($gSwfServer)) {
			//$aurl='http://localhost:8080/swfserver/event';
			$aurl=$gSwfServer."event";
			$aurl.='?'.$_SERVER["QUERY_STRING"];
			@file_get_contents($aurl);	
		}

	}

/*
	if ($type=='EndScreenSharing') {
		include_once("iswfcommon.php");
		include_once("iswfprocessor.php");
		SwfProcessor::endConversion();
	}
*/
?>