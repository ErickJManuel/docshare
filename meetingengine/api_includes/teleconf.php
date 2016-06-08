<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("includes/common_lib.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vteleserver.php");
require_once("includes/log_error.php");

$teleServer='';
$accessKey='';
$canDialOut='N';
$tollfreeOnly='N';
$teleMcode='';
if (GetArg('meeting_id', $meetingId)) {

	if ($meetingId=='')
		API_EXIT(API_ERR, "Meeting id is empty.");
			
	$meetingFile=VMeeting::GetSessionCachePath($meetingId);
	$accessDb=true;

	if (VMeeting::IsSessionCacheValid($meetingFile)) {
		@include_once($meetingFile);
		if (isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
		// turn off database access if a meeting is already in progress
			$accessDb=false;
		}
	}
	
	if (!$accessDb) {
		// no database case
		// get teleserver info from the meeting cache file
		if (isset($_teleServerUrl))
			$teleServer=$_teleServerUrl;
		if (isset($_teleServerKey))
			$accessKey=$_teleServerKey;
		if (isset($_teleDialout))
			$canDialOut=$_teleDialout;
		if (isset($_teleTollfreeOnly))
			$tollfreeOnly=$_teleTollfreeOnly;
		if (isset($_teleMCode))
			$teleMcode=$_teleMCode;

	} else {
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");

		$errMsg=VObject::Find(TB_MEETING, "access_id", $meetingId, $meetingInfo);
		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg);
			
		if (!isset($meetingInfo['host_id']))
			API_EXIT(API_ERR, "Meeting not found.");			

		$host=new VUser($meetingInfo['host_id']);
		$host->GetValue('group_id', $groupId);
		$group=new VGroup($groupId);
		$group->GetValue('teleserver_id', $teleServerId);
		$teleMcode=$meetingInfo['tele_mcode'];	
	}
	
} elseif (GetArg('teleserver_id', $teleServerId)) {
	
} else {
	// deprecated. don't use this
	GetArg('teleserver', $teleServer);
	$accessKey='';
	$canDialOut='N';
	$tollfreeOnly='N';
}

if ($teleServer=='' && $teleServerId!='') {

	$telServ=new VTeleServer($teleServerId);
	if ($telServ->Get($teleInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $telServ->GetErrorMsg());
	if (!isset($teleInfo['id']))
		API_EXIT(API_ERR, "Teleserver_id $teleServerId does not exist.");
	$teleServer=$teleInfo['server_url'];
	$accessKey=$teleInfo['access_key'];
	$canDialOut=$teleInfo['can_dialout'];
	$tollfreeOnly=$teleInfo['dial_tollfree_only'];
}

if (GetArg('callnumber', $callnumber)) {
	$callnumber=RemoveNonNumbers($callnumber);	// remove spaces
	if ($canDialOut=='N')
		API_EXIT(API_ERR, "Dial-out is not enabled for this server.");
	if ($tollfreeOnly=='Y') {
		$tollfree=false;
		if (strpos($callnumber, "1800")===0 || strpos($callnumber, "1866")===0 || strpos($callnumber, "1877")===0 || strpos($callnumber, "1888")===0 ||
			strpos($callnumber, "800")===0 || strpos($callnumber, "866")===0 || strpos($callnumber, "877")===0 || strpos($callnumber, "888")===0)
			$tollfree=true;
		if ($tollfree) {
			// ok
		} else {
			API_EXIT(API_ERR, "Only toll-free numbers are allowed.");				
		}			
	}
}

GetArg('phone', $phone);
GetArg('confid', $confId);

// there is a bug in 2.2.20 Flash viewer that it sends in the phone number as the conference id
// this is only needed for 2.2.20. the Flash viewer bug is fixed in latter versions
/*
if ($confId==$phone && $confId!='' && $teleMcode!='') {
	$confId=$teleMcode;
}
*/

$phone=RemoveSpacesFromPhone($phone);
$confId=RemoveNonNumbers($confId);

if ($teleServer=='' || $phone=='' || $confId=='') {
	API_EXIT(API_ERR, "Missing input parameters");
}

$data='';
if ($cmd=='SET_TELE_PARTICIPANT') {		
	$teleUrl=$teleServer."participant/";
	$data="phone=".$phone."&id=".$confId;
	if ($meetingId!='')
		$data.="&meetingid=".$meetingId;
		
	GetArg('callid', $callId);
	$callId=RemoveNonNumbers($callId);
	
	if (GetArg('mute', $mute)) {		
		$data.="&mute=$mute&callid=$callId";
	} else if (GetArg('hangup', $hangUp) && $hangUp=='Y') {
		$data.="&hangup=Y&callid=$callId";
	}

} else if ($cmd=='SET_TELE_CONFERENCE') {
	$teleUrl=$teleServer."conference/";
	$data="phone=".$phone."&id=".$confId;
	if ($meetingId!='')
		$data.="&meetingid=".$meetingId;
	if (GetArg('attendeeid', $attendeeId))
		$data.="&attendeeid=".$attendeeId;

	if (GetArg('mode', $mode))
		$data.="&mode=$mode";
	elseif (GetArg('callnumber', $callnumber)) {
		$callnumber=RemoveNonNumbers($callnumber);	// remove spaces
		$data.="&callnumber=$callnumber";
		if (GetArg('dialmode', $dialMode))
			$data.="&dialmode=$dialMode";
	} elseif (GetArg('hangup', $hangUp) && $hangUp=='Y')
		$data.="&hangup=Y";

} else if ($cmd=='GET_TELE_PARTICIPANTS') {
	
	GetArg('nocache', $nocache);
	
//	$cacheFile=DIR_TEMP.md5($teleServer.$phone.$confId);
	$cacheFile=GetTempDir().md5($teleServer.$phone.$confId);
	$hasCache=false;
	if ($nocache=='1') {

	} elseif (file_exists($cacheFile) && filesize($cacheFile)>0) {
		$curTime=time();
		$modTime=filemtime($cacheFile);
		if (($curTime-$modTime)<=3) {
			$hasCache=true;			
		}
	}
	
	$resp='';
	if ($hasCache) {
		$fp=@fopen($cacheFile, "r");
		if ($fp && flock($fp, LOCK_SH)) {
			$resp=fread($fp, filesize($cacheFile));
			flock($fp, LOCK_UN);				
		}
		if ($fp)
			fclose($fp);
		
	} else {
		$args="phone=".urlencode($phone)."&id=".$confId;
		if ($meetingId!='')
			$args.="&meetingid=".$meetingId;
		if ($accessKey!='')
			$sig="signature=".md5($args.$accessKey);
		else
			$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
			
		$args.="&".$sig;
		$teleUrl=$teleServer."participant/?".$args;	
		
		// change the timeout time to 10 sec.
//		$resp=HTTP_Request($teleUrl, '', 'GET', 5);
		$resp=HTTP_Request($teleUrl, '', 'GET', 10);
		
		if ($resp) {
			$pos1=strpos($resp, "<?xml");
			// not a valid xml response
			if ($pos1===false) {
				LogError("Invalid response '$resp' from '$teleUrl'");
				API_EXIT(API_ERR, "Invalid response '$resp'.");
			}

			$fp=@fopen($cacheFile, "a");
			
			if (flock($fp, LOCK_EX)) {
				ftruncate($fp, 0);
				fwrite($fp, $resp);				
				flock($fp, LOCK_UN);				
			}
			if ($fp) {
				fclose($fp);
				umask(0);
				@chmod($cacheFile, 0777);
			}
		} else {
			LogError("Null response from ".$teleUrl);			
		}
	}
//LogAccess($teleUrl."\n".$resp);
	
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-Type: text/xml");
	echo $resp;	
	exit();
}

if ($accessKey!='')
	$sig="signature=".md5($data.$accessKey);
else
	$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
	
$data.="&".$sig;

$resp=HTTP_Request($teleUrl, $data, 'POST', 15);
LogAccess($teleUrl." POST ".$data);
if ($resp) {

	$pos1=strpos($resp, "<?xml");
	// not a valid xml response
	if ($pos1===false) {
		LogError("Invalid response '$resp' from '$teleUrl' POST '$data' QUERY ".$_SERVER['QUERY_STRING']);
		API_EXIT(API_ERR, "Invalid response received from the server. Feature may not be supported.");
	}
	// remove any extra data before the xml data.
	if ($pos1>0) {
		$resp=substr($resp, $pos1);
	}
	
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-Type: text/xml");
	echo $resp;
	
	// get the current participant status to a cache file
//	$cacheFile=DIR_TEMP.md5($teleServer.$phone.$confId);
	$cacheFile=GetTempDir().md5($teleServer.$phone.$confId);
	$data="phone=".urlencode($phone)."&id=".$confId;
	if ($meetingId!='')
		$data.="&meetingid=".$meetingId;
	
	if ($accessKey!='')
		$sig="signature=".md5($data.$accessKey);
	else
		$sig="nosig=1"; // shouldn't allow this. need to remove this soon.
	
	$data.="&".$sig;
	
	$teleUrl=$teleServer."participant/?".$data;		
//	$resp2=HTTP_Request($teleUrl, '', 'GET', 5);
	$resp2=HTTP_Request($teleUrl, '', 'GET', 10);
	if ($resp2) {
		
		$pos1=strpos($resp2, "<?xml");
		// make sure it is a valid xml response
		if ($pos1!==false) {
			$fp=@fopen($cacheFile, "a");
			
			if (flock($fp, LOCK_EX)) {
				ftruncate($fp, 0);
				fwrite($fp, $resp2);				
				flock($fp, LOCK_UN);				
			}
			if ($fp) {
				fclose($fp);
				umask(0);
				@chmod($cacheFile, 0777);
			}
		} else {
			LogError("Invalid response '$resp2' from '$teleUrl'");
		}
	} else {
		LogError("Null response from ".$teleUrl);
	}
	API_EXIT(API_NOMSG);
	
} else {
	API_EXIT(API_ERR, "Couldn't connect to server $teleUrl");
}


?>