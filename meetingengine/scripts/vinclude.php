<?php

// (c)Copyright 2004, Persony, Inc. All rights reserved.

	$parser_version = phpversion();

	if ($parser_version < "4.1.0") {
		$GET_VARS		= &$HTTP_GET_VARS;
		$GET_POST		= &$HTTP_POST_VARS;
		$GET_POST_FILES = &$HTTP_POST_FILES;
		$GET_SERVER		= &$HTTP_SERVER_VARS;
	} else {

		$GET_VARS		= &$_GET;
		$GET_POST		= &$_POST;
		$GET_POST_FILES	= &$_FILES;
		$GET_SERVER		= &$_SERVER;
	}

//	$debugOn=true;
// should be defined in the parent script
	if ($debugOn) {
		define ("FATAL", E_USER_ERROR);
		define ("ERROR", E_USER_WARNING);
		define ("WARNING", E_USER_NOTICE);

		// error handler function
		function myErrorHandler ($errno, $errstr, $errfile, $errline)
		{
			$debugInfo=debug_backtrace();
//			print_r($debugInfo);

			$count=count($debugInfo);
			for ($i=0; $i<$count; $i++) {
				if (!isset($debugInfo[$i]["file"])) {
					continue;
				}
				$debugLine=$debugInfo[$i]["line"];
				$debugFile=$debugInfo[$i]["file"];
				$debugFunc=$debugInfo[$i]["function"];
				echo "'$debugFile' line $debugLine $debugFunc():<br>\r\n";
				break;
			}


			switch ($errno) {
				case FATAL:
					echo "<b>FATAL</b> [$errno] $errstr<br />\n";
					echo "  Fatal error in line $errline of file $errfile";
					echo ", PHP ".PHP_VERSION." (".PHP_OS.")<br />\n";
					echo "Aborting...<br />\n";
					exit(1);
					break;
				case ERROR:
					echo "<b>ERROR</b> [$errno] $errstr<br />\n";
					break;
				case WARNING:
					echo "<b>WARNING</b> [$errno] $errstr<br />\n";
					break;
				default:
					echo "Unkown error type: [$errno] $errstr<br />\n";
					break;
			}
		}

		// set the error reporting level for this script
		error_reporting (FATAL | ERROR | WARNING);
		// set to the user defined error handler
		$old_error_handler = set_error_handler("myErrorHandler");
	}

	$gVMeetingDir="vmeeting/";
	$gVLibgDir="vlibrary/";
	$gVRoomDir="vroom/";
	$gHostFile="vmhost.php";
	$gEvtPrefix="evt";
	$gTempDir="temp/";
	$gCacheDir="cache/";
	$gEvtDir="vevents/";
	$gAttDir="vattendees/";
	$gPollsDir="vpolls/";
	$gDocDir="vdocuments/";
	$gFramesDir="vframes/";
	$gMediaDir="vmedia/";
	$gSlidesDir="vslides/";
	$gLogsDir="vlogs/";
	$gMeetingsDir="vmeetings/";
	$gRegisDir="vregistrations/";
	$gRegPrefix="reg";
	$gXMLHeader="<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
	$gMaxAttendees=10;
	$gMaxAttDelay=35; // seconds an attendee deemed gone if no update within the duration
	$gMaxWaitTime=15;  //max wait time in seconds for getfile or getframe to return. this number needs to smaller than php's max_execution_time (<30)
	$gStopFile="stop.inf";
	$gPauseFile="pause.inf";
	$gStartFile="start.inf";
	$gCheckFileDelay=300; // in msec <1000 and >10
	$gAttFile="vattendees.xml";
	$gInfoFile="meeting_info.php";
	$gAttFilePrefix="att_";
	$gCallFilePrefix="cal_";
	$gHostFileExt="host";
	$gDrawFileExt="draw";
	$gPresFileExt="pres";
	$gGetAttendeeScript="vgetattendees.php";
	$gSessionFile="vsession.inf";

	error_reporting(0); // turn off error reporting

	if (phpversion()<"4.3.0") {
		if (ini_get('always_populate_raw_post_data')!="1")
		  ini_set('always_populate_raw_post_data', "1");

//		echo "ini_get ".ini_get('always_populate_raw_post_data');
	}


	function XmlToStr($xml)
	{
		return html_entity_decode($xml);
/*
		$str=str_replace("&amp;", "&", $xml);
		$str=str_replace("&apos;", "'", $str);
		$str=str_replace("&quot;", "\"", $str);
		$str=str_replace("&lt;", "<", $str);
		$str=str_replace("&gt;", ">", $str);
		return $str;
*/
	}

	function StrToXml($str)
	{
		return htmlspecialchars($str);
	/*
			$xml=str_replace("&", "&amp;", $str);
			$xml=str_replace("'", "&apos;", $xml);
			$xml=str_replace("\"", "&quot;", $xml);
			$xml=str_replace("<", "&lt;", $xml);
			$xml=str_replace(">", "&gt;", $xml);

			return $xml;
	*/
	}

	function GetIP()
	{
		$ip='unknown';
		if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
			$ip = getenv("REMOTE_ADDR");
		else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
			$ip = $_SERVER['REMOTE_ADDR'];
		return($ip);
	}

	function GetPostData() {

		if (phpversion()<"4.3.0") {
            return $GLOBALS["HTTP_RAW_POST_DATA"];
		} else {
			return file_get_contents("php://input");
		}
	}

	// needs to match VUtils::EncryptNumber
//	function EncryptNumber($numStr) {
/*
		sscanf($numStr, "%d", $numInt);
		// sum the digits
		$sum=0;
		for ($i=0; $i<strlen($numStr); $i++) {
			$ch=substr($numStr, $i, 1);
			sscanf($ch, "%d", $dig);
			$sum+= $dig;
		}
		$code=($numInt+1)*$sum;
		return sprintf("%d", $code);
*/
//		return $numStr."13"; // keep it simple
//	}
	function GetAttendeeFile($attId)
	{
		global $gAttFilePrefix;
//		return $attId.".xml";
		return $gAttFilePrefix.md5($attId).".php";
	}
/*
	function GetCallFile($callId)
	{
		global $gCallFilePrefix;
		return $gCallFilePrefix.md5($callId).".php";
	}
*/
	function IsAttendeeFile($file)
	{
		global $gAttFilePrefix;
/*
		if (!is_file($filePath))
			return false;
		$fileInfo = pathinfo($filePath);
		if (!isset($fileInfo["extentsion"]) || !isset($fileInfo["basename"]))
			return false;
		if ($fileInfo["extentsion"]!="php")
			return false;
*/
		if (strpos($file, $gAttFilePrefix)===false)
			return false;
		return true;
	}
/*
	function IsHostCode($code) {
		global $hostKey; // defined in vmhost.php
		$enc=EncryptNumber($hostKey);
		return ($enc==$code);
	}

	function IsPresenterCode($code) {
		global $presenterKey;  // defined in vmhost.php
		$enc=EncryptNumber($presenterKey);
		return ($enc==$code);
	}
*/
	function IsHost($userid, $evtDir) {
		global $gHostID;  // defined in vmhost.php
		global $gAttDir, $gHostFileExt;
		if (isset($gHostID) && $userid==$gHostID) {
			return true;
		}
		$theFile=$evtDir.$gAttDir.$userid.".".$gHostFileExt;
		return file_exists($theFile);
	}

	function IsPanelMeeting()
	{
		global $gMeetingType;  // defined in vmhost.php
		$isPanel = false;

		if(isset($gMeetingType) && $gMeetingType == "PANEL")
		{
			$isPanel = true;
		}

		return $isPanel;
	}

	function CanDraw($userid, $evtDir) {
		global $gAttDir, $gDrawFileExt, $gPresFileExt;
		$theFile=$evtDir.$gAttDir.$userid.".".$gDrawFileExt;
		if (file_exists($theFile)) {
			return true;
		} else {
			$theFile=$evtDir.$gAttDir.$userid.".".$gPresFileExt;
			return file_exists($theFile);
		}
	}

	function IsPresenter($userid, $evtDir) {
		global $gAttDir, $gPresFileExt;
		$theFile=$evtDir.$gAttDir.$userid.".".$gPresFileExt;
		return file_exists($theFile);

/*
//		global $presenterIDs;  // defined in vmhost.php
//		return in_array($userid, $presenterIDs);

		$attFile=GetAttendeeFile($userid);
		$afile=$evtDir.$gAttDir.$attFile;
		$fp=@fopen($afile, "rb");
		if ($fp) {
			$content = fread($fp, filesize($afile));
			fclose($fp);
			$pos=strpos($content, "isPresenter=\"true\"");
			if ($pos===false) {
				return false;
			} else {
				return true;
			}
		}
		return false;
*/
	}

	// not working on Linux. don't use this
/*
	function MicroDelay($delay) {
		$UNUSED_PORT=31238; //make sure this port isn't being used on your server
		@fsockopen("tcp://localhost",$UNUSED_PORT,$errno,$errstr,$delay);
	}
*/

	// return current time in seconds (floating point)
	function GetMicroTime(){
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	// sleep for msec (10<msec<1000) milli-seconds
	// set canusleep to -1 to test if usleep is supported
	// return canusleep as 1 if usleep is supported and sleep for msec
	// otherwise, return canusleep as 0 and sleep for 1 sec.
	function MSleep($msec, &$canusleep) {
		// test if usleep works
		if ($canusleep==-1) {
			$start=GetMicroTime();
			usleep($msec*1000);
			$delay=GetMicroTime()-$start;
			if ($delay<0.01) { // less than 10 msec
				// usleep not working (Windows)
				$canusleep=0;
				sleep(1);
			} else {
				$canusleep=1;
			}
		} else if ($canusleep==1) {
			usleep($msec*1000);
		} else {
			sleep(1);
		}
	}

	function IsAuthorized($id, $theCode) {

//		@include "site_config.php";
		@include "vh".$id.".php";

		// make sure the request is from the host defined in $serverUrl of site_config.php
/*
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$urlItems=parse_url($serverUrl);
			$hostName=$urlItems['host'];
			$serverIp=gethostbyname($hostName);
			$serverIps=explode(".", $serverIp);
			$remoteAddrs=explode(".", $_SERVER['REMOTE_ADDR']);

			// match only the first 3 sub ip addresses
			if ($serverIps[0]!=$remoteAddrs[0] ||
				$serverIps[1]!=$remoteAddrs[1] ||
				$serverIps[2]!=$remoteAddrs[2])

				return false;

		}
*/
		if ($theCode=='')
			return false;

		// $code or $mcode should be defined in hostfile
		// $mcode is md5 encrypted code
		// $theCode may or may not be encrypted so check both
		if (isset($code) && ($theCode==$code || md5($theCode)==$code)) {
			return true;
		} else if (isset($mcode) && ($theCode==$mcode || md5($theCode)==$mcode)) {
			return true;
		}
		return false;

	}
	function CopyDir($fromdir, $todir) {
		if (!file_exists($todir)) {
			umask(0);
			//@mkdir($todir, 0777);
			mkdirs($todir, 0777);
		}
		
		$separator=$fromdir[strlen($fromdir)-1];
		if ($separator!='/' && $separator!='\\') {
			$fromdir.="/";
		}
		$separator=$todir[strlen($todir)-1];
		if ($separator!='/' && $separator!='\\') {
			$todir.="/";
		}

		if ($dir = @opendir($fromdir)) {
			while($file = readdir($dir)) {
				if($file == "." || $file == "..")
					continue;
				
				$fromFile=$fromdir.$file;
				$toFile=$todir.$file;
				
				if (is_file($fromFile)) {
					if (file_exists($toFile))
						@unlink($toFile);
					@copy($fromFile, $toFile);
				} else if (is_dir($fromFile)) {
					CopyDir($fromFile, $toFile);
				}
			}
			closedir($dir);
		} else {
			return false;
		}
		return true;
	}
	
	// recursive mkdir to create all parent folders if they don't exist
	function mkdirs($dirPath, $mode)
	{
		if ($dirPath=='.' || $dirPath=='' || $dirPath=='/')
			return true;
		
		if (is_dir($dirPath)) {
			return true;
		}
		
		$parentPath = dirname($dirPath);
		if (!mkdirs($parentPath, $mode)) 
			return false;
		
		return @mkdir($dirPath, $mode);
	}

	function MyMkDir($dir, $mode, $indexFile)
	{
		$ret=true;
		if (!is_dir($dir)) {
			umask(0);
//			if (@mkdir($dir, $mode)) {
			if (mkdirs($dir, $mode)) {
				@chmod($dir, $mode);
				if ($indexFile && $indexFile!='') {
					$indexFilePath=$dir."/".$indexFile;
					$ofp=@fopen($indexFilePath, "w");
					if ($ofp) {
						fwrite($ofp, "<html></html>");
						fclose($ofp);
						@chmod($indexFilePath, $mode);
					}
				}
				$ret=true;
			} else
				$ret=false;
		} else {
			umask(0);
			@chmod($dir, $mode);
			$ret=true;
		}
		return $ret;
	}

	function MyRmDir($dir, &$errMsg)
	{
		if (is_dir($dir)) {
			if ($dh = @opendir($dir)) {
				$fileList = array();
				while (($file = @readdir($dh)) !== false) {
					if ($file!="." && $file!="..") {
						$fileList[] = $dir."/".$file;
					}
				}
				closedir($dh);
				$ok=true;
				$errMsg='';
				foreach ($fileList as $fileItem) {
					$count++;
					if (is_file($fileItem)) {
						$ok=@unlink($fileItem);
						if (!$ok) {
							$errMsg="couldn't delete file ${fileItem}";
							return false;
						}
					}
				}
				if ($dir!=".") {
					$ok=@rmdir($dir);
					if (!$ok) {
						$errMsg="Couldn't delete directory $dir";
						return false;
					}
				}

			} else {
				$errMsg="Couldn't open directory $dir";
				return false;
			}
		}
		return true;
	}

	function ErrorExit($msg) {
		$errMsg="ERROR";
		if ($msg!='')
			$errMsg.="\n".$msg;
		die ($errMsg);
	}

	function EchoFile($theFile) {
		$fp=fopen($theFile, "rb");
		if ($fp) {
			while (1) {
				$data=fread($fp, 8192);
				if (strlen($data)==0)
					break;
				echo $data;
			}
			fclose($fp);
		}
	}

	function CreateFile($outFile, $content, $mode)
	{
		$ofp=@fopen($outFile, "w");
		if (!$ofp)
			return false;
		fwrite($ofp, $content);
		fclose($ofp);
		@chmod($outFile, $mode);
		return true;
	}
	function SpecialChars($str) {
		$ret=str_replace("\\", "\\\\", $str);
		$ret=str_replace("\"", "\\\"", $ret);
		$ret=str_replace("\$", "\\\$", $ret);
		return $ret;
	}
	function WriteAttendeeFile($afile, $useragent, $userip, $userid, $username, $useremail, $usertype,
				$startTime, $modTime, $breakTime, $drawing, $isHost, $isPresenter, $emoticon, $webcam, $callerId,
				$lastCallerId, $callId, $camStartTime, $camTime, $inMeeting, $serverId, $mustExist=false)
	{
		// don't write the file unless the file name matches the attendee id
		if (basename($afile)!=GetAttendeeFile($userid))
			return false;

		$username=SpecialChars($username);
		$useremail=SpecialChars($useremail);
		$useragent=SpecialChars($useragent);
		$usertype=SpecialChars($usertype);
//		$attData=str_replace("\"", "\\\"", $attData);
		$content="<?php\n";
		$content.=" \$_useragent=\"$useragent\";";
		$content.=" \$_userip=\"$userip\";";
		$content.=" \$_userid=\"$userid\";";
		$content.=" \$_username=\"$username\";";
		$content.=" \$_useremail=\"$useremail\";";
		$content.=" \$_usertype=\"$usertype\";";
		$content.=" \$_startTime=$startTime;";
		$content.=" \$_modTime=$modTime;";
		$content.=" \$_breakTime=$breakTime;";
		$content.=" \$_isHost=\"$isHost\";";
		$content.=" \$_isPresenter=\"$isPresenter\";";
		$content.=" \$_drawing=\"$drawing\";";
		$content.=" \$_emoticon=\"$emoticon\";";
		$content.=" \$_webcam=\"$webcam\";";
		$content.=" \$_callerId=\"$callerId\";";
		if ($callerId!='')
			$content.=" \$_lastCallerId=\"$callerId\";";
		else
			$content.=" \$_lastCallerId=\"$lastCallerId\";";
		$content.=" \$_callId=\"$callId\";";
		$content.=" \$_camStartTime=$camStartTime;";
		$content.=" \$_camTime=$camTime;";
		$content.=" \$_inMeeting=\"$inMeeting\";";
		$content.=" \$_serverId=\"$serverId\";";
		$content.="\n?>";

		if ($mustExist && !file_exists($afile)) {
			return false;
		}
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

?>
