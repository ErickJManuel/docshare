<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
	chdir(dirname(__FILE__));

//	require_once("console/common.php");
	require_once("includes/common_lib.php");
	require_once("includes/common_text.php");
	require_once("dbobjects/vmeeting.php");
	require_once("dbobjects/vsession.php");
	require_once("dbobjects/vtoken.php");
	require_once("dbobjects/vregform.php");
	require_once("dbobjects/vbrand.php");
	require_once("dbobjects/vmailtemplate.php");
	require_once("dbobjects/vuser.php");
	require_once("server_config.php");
/*	
	// check if an authentication signature is passed in
	if (isset($_GET['signature'])) {
		// ip address is required
		if (!isset($_GET['ip'])) {
			die("ERROR missing a parameter.");
		} else {
			if ($_GET['ip']!=$_SERVER['REMOTE_ADDR'])
				die("ERROR IP address does not match.");
		}
		
		$sig=md5($_GET['ip']);
		if ($sig!=$_GET['signature']) {
			die("ERROR Invalid signature.");
		}
	} else {
	
		// no signature, login required
		if (GetSessionValue('root_login')=='') {
			$signinPage="console_signin.php";
			if (SID!='')
				$signinPage.="?".SID;
			header("Location: $signinPage");
			exit();
		}
	}
*/	
	
	// cron jobs intervals in seconds
	$gCronInterval=3600;
	
	StartSession();
	$verbose=false;
	if (isset($_GET['verbose']))
		$verbose=true;

	$today=date("y_m_d");
	if (defined("LOG_DIR") && constant("LOG_DIR")!='[LOG_DIR]' && constant("LOG_DIR")!='')	
		$logFile=LOG_DIR."cron_$today.log";
	else
		$logFile='';
		
	if ($logFile!='') {
		$logFp=@fopen($logFile, "a");
		@chmod($logFile, 0666);
		if (!$logFp)
			ShowMyMessage("Couldn't open log file '$logFile'. Logging disabled.");
	} else
		$logFp=null;

	if (isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	else
		$ip='';	// run from the same host
		
	$serverAddr='';
	if (isset($_SERVER['SERVER_ADDR']))
		$serverAddr=$_SERVER['SERVER_ADDR'];

	LogCronMessage("\n".date('Y-m-d H:i:s')." ".$_SERVER['PHP_SELF']." ".$ip);

	// running remotely and not logged in to the console; check if IP authentication is required.
	if ($ip!='' && $ip!='127.0.0.1' && $ip!=$serverAddr && GetSessionValue('root_login')=='') {
		if (defined("CRON_REQUEST_IP") && constant("CRON_REQUEST_IP")!='[CRON_REQUEST_IP]' && constant("CRON_REQUEST_IP")!='') {
			// requester ip needs to match the authorized ip
			if (strpos($ip, constant("CRON_REQUEST_IP"))!==0) {
				LogCronError("Request is not made from an authorized IP address $ip.");
				if ($logFp) {
					fclose($logFp);
				}
				DoExit();
			}		
		}
	}
	
		
	set_time_limit(15*60);
	
			

// end all idle meetings

	LogCronMessage("Stopping all idle meetings...");

//	$query="login='".VUSER_GUEST."'";
//	$query.=" AND (status='START' OR status='START_REC')";
	$query="status='START'";
	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
	if ($errMsg!='') {
		ExitOnError($errMsg);
	} else {
		$num_rows = mysql_num_rows($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			
			// only end the meeting if it is not in progress
			if (!VMeeting::IsMeetingInProgress($row)) {
				
				// see if the session has been idle for more than 6 hours
				$maxDur=60*60*1;
				$sq="(id='".$row['session_id']."') AND (TIME_TO_SEC(TIMEDIFF(NOW(), mod_time))>'".$maxDur."')";
				VObject::Count(TB_SESSION, $sq, $scount);
				
				if ($scount>=1) {					
					LogCronMessage("EndMeeting meeting=".$row['access_id']);	
					$meeting=new VMeeting($row['id']);
					if ($meeting->EndMeeting(false)!=ERR_NONE)
						LogCronError("EndMeeting ".$meeting->GetErrorMsg());
				}
			}
		}
	}
	
// delete all guest_user meetings not in progress
	LogCronMessage("Deleting all guest meetings not in progress...");

	$query="login='".VUSER_GUEST."'";
	$errMsg=VObject::SelectAll(TB_USER, $query, $result);
	if ($errMsg!='') {
		ExitOnError($errMsg);
	} else {
		$num_rows = mysql_num_rows($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			
			// find all meetings for the user
			$query1="host_id='".$row['id']."'";
			$errMsg=VObject::SelectAll(TB_MEETING, $query1, $result1);			
			$num_rows1 = mysql_num_rows($result1);
			while ($row1 = mysql_fetch_array($result1, MYSQL_ASSOC)) {
				// only delete the meeting if it is not in progress (no current attendees)
				if (!VMeeting::IsMeetingInProgress($row1)) {
					LogCronMessage("DeleteMeeting meeting=".$row1['access_id']);
					$meeting=new VMeeting($row1['id']);
					if ($meeting->DeleteMeeting()!=ERR_NONE) {
						LogCronError("DeleteMeeting ".$meeting->GetErrorMsg());
					}
				}				
			}
	
		}
	}
	
	// Send auto reminder email to registered meeting participants
	LogCronMessage("Sending auto reminder email to registered meeting participants...");
	
	$query="auto_reminder<>0";
	$errMsg=VObject::SelectAll(TB_REGFORM, $query, $result);
	if ($errMsg!='')
		ExitOnError($errMsg);

	$nowTime=time();
	while ($formInfo = mysql_fetch_array($result, MYSQL_ASSOC)) {
		// find the meeting that the registration form belongs to
		$autoTime=(int)$formInfo['auto_reminder']*3600;
		$query2="regform_id='".$formInfo['id']."'";
		$errMsg=VObject::Select(TB_MEETING, $query2, $meetingInfo);
		if ($errMsg!='')
			ExitOnError($errMsg);
			
		// check if this is scheduled meeting and registration is needed
		if ($meetingInfo['scheduled']=='Y' && $meetingInfo['login_type']=='REGIS') {

			list($meetDateStr, $meetTimeStr)=explode(" ", $meetingInfo['date_time']);
			list($meetYear, $meetMonth, $meetDay)=explode("-", $meetDateStr, 3);
			list($meetHour, $meetMin, $meetSec)=explode(":", $meetTimeStr, 3);
			
			$meetingTime=mktime($meetHour, $meetMin, 0, $meetMonth, $meetDay, $meetYear);
			$reminderTime=$meetingTime-$autoTime;
			// since the cron jobs are run on the hour and most meetings will be on the hour too
			// add a few minutes of slack so we can send the reminder on the nearest hour
			$reminderTime+=180;	
			$diffTime=$reminderTime-$nowTime;
//ShowMessage("meetingTime=".$meetingInfo['date_time']." auto=".$formInfo['auto_reminder']." reminder=".$reminderTime." now=".$nowTime." diff=".$diffTime);
			if ($diffTime>=0 && $diffTime<$gCronInterval) {
				// time to send email
				$err=VMeeting::NotifyRegisteredUsers($meetingInfo, $formInfo['custom_reply'], $errMsg, $sent);
				if ($sent>0) {
					LogCronMessage("Sent auto reminder email for meeting id='".
						$meetingInfo['access_id']."' to ".$sent." registered users.");					
				}
				if ($err=='-1')
					ExitOnError($errMsg);
				else if ($err!=0 && $errMsg!='')
					LogCronError($errMsg);
			
			}
		}
	}
	
	// clean up old authorization tokens
	// This is called every time a new token is requested so no need to call it here
//	VToken::DeleteOldTokens();

/*	
	LogCronMessage("Getting audio recording data...");
	
	$query="status='REC' AND audio_rec_id<>'' AND audio='N'";
	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
	if ($errMsg!='') {
		LogCronError($errMsg);
	} else {

		$num_rows = mysql_num_rows($result);
		while ($meetingInfo = mysql_fetch_array($result, MYSQL_ASSOC)) {

			LogCronMessage("Checking audio data for meeting ".$meetingInfo['access_id']."...");
			
			$host=new VUser($meetingInfo['host_id']);
			$host->Get($hostInfo);
			
			$brand=new VBrand($hostInfo['brand_id']);
			$brand->Get($brandInfo);
			
			$recUrl=$brandInfo['aconf_rec_url'];
			if ($recUrl=='')
				continue;

			$number=$meetingInfo['tele_num'];
			$code=$meetingInfo['tele_pcode'];
			$recId=$meetingInfo['audio_rec_id'];
			
			$recUrl.="?cmd=record&key=".md5('record'.$brandInfo['aconf_rec_password']);
			$recUrl.="&number=1$number&mod=$code&name=$recId";
			
			// check if recording is available
			$checkUrl=$recUrl."&flag=F";
			$res=HTTP_Request($checkUrl, '', 'GET', 10);
			if ($res && strpos($res, "OK")===0) {
				// recording available
				LogCronMessage($res);
				LogCronMessage("Copying audio data for meeting ".$meetingInfo['access_id']."...");
				
				$loadUrl=VMeeting::GetExportRecUrl($hostInfo, $meetingInfo, true, true);
				if ($loadUrl=='') {
//					LoadError($errMsg);
				} else {
					$getUrl=$recUrl."&flag=G";
					$getUrl=str_replace("&", "%26", $getUrl);
					$url=$loadUrl."&url=".$getUrl;
					// copy entire file at once
					$url.="&index=0";
					$res2=@file_get_contents($url);
					if ($res2 && strpos($res2, "OK")===0) {
						// merge audio files
						$res3=@file_get_contents($loadUrl."&merge=1");
						if ($res3 && strpos($res3, "OK")===0) {
							LogCronMessage($res3);
							$updateInfo=array();
							$updateInfo['audio']='Y';
							$meeting=new VMeeting($meetingInfo['id']);
							if ($meeting->Update($updateInfo)!=ERR_NONE) {
								LogCronError($meeting->GetErrorMsg());
							}							
							
						} elseif ($res3) {
							LogCronError($res3);
						} else {
							LogCronError("Couldn't get response from ".$loadUrl);						
						}
						
					} elseif ($res2) {
						LogCronError($res2);						
					} else {
						LogCronError("Couldn't get response from ".$url);						
					}				
				}				
			} elseif ($res) {
				LogCronError($res);				
			} else {
				LogCronError("Couldn't get response from ".$checkUrl);						
			}			
		}
	}
*/	
	LogCronMessage("Completed ".date('Y-m-d H:i:s'));	

	if ($logFp)
		fclose($logFp);
	
	function LogCronError($errMsg) {
		global $verbose;
		global $logFp;
		if ($logFp) {
			fwrite($logFp, "ERROR ".$errMsg."\r\n");
		}
		if ($verbose)
			ShowMyMessage($errMsg);
	}

	function LogCronMessage($msg) {
		global $verbose;
		global $logFp;
		if ($logFp) {
			fwrite($logFp, $msg."\r\n");
		}
		if ($verbose)
			ShowMyMessage($msg);
	}
	
	function ShowMyMessage($msg) {
		// fill the buffer with extra data so flush will work on Win
//		echo $msg."<br>\n".str_pad(" ", 512);
		echo $msg."<br>\n";
		flush();
	}
	
	function ExitOnError($msg) {
		LogCronError($msg);
		LogCronMessage("Exit on error ".date('Y-m-d H:i:s'));	
		if ($logFp)
			fclose($logFp);
		exit();
	}	
		

?>