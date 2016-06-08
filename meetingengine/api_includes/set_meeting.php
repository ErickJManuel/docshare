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

// if database id down while trying to set the status of an in-progress, update the meeting cache file only.
/*
if (GetArg('status', $arg) && $cmd=='SET_MEETING') {
	if (!VObject::CanOpenDB()) {
		if (GetArg('meeting_id', $arg))
			$meetingId=$arg;
		else if (GetArg('meeting', $arg))
			$meetingId=$arg;
		$meetingFile=VMeeting::GetSessionCachePath($meetingId);
		if (file_exists($meetingFile)) {
			@include_once($meetingFile);
			if (isset($_meetingStatus) && ($_meetingStatus=='START' || $_meetingStatus=='START_REC' )) {				
				
			}
	}
}
*/
require_once('api_includes/meeting_common.php');
require_once('dbobjects/vuser.php');
require_once('dbobjects/vhook.php');
require_once('dbobjects/vbrand.php');
require_once('dbobjects/vmailtemplate.php');

//require_once('includes/brand.php');

//require_once('api_includes/user_common.php');
/*
if ($userErrMsg!='')
return API_EXIT(API_ERR, $userErrMsg);
*/
if ($errMsg!='')
return API_EXIT(API_ERR, $errMsg);
	
// FIXME: locking/unlocking a meeting may be called during a meeting and the session may have ended
// disable login checking for that

if (GetArg('locked', $arg) && $cmd=='SET_MEETING' && isset($meetingInfo['id'])) {
	$memberId=$meetingInfo['host_id'];
	$memberBrand=$meetingInfo['brand_id'];
	$memberPerm='';

} else {
	$memberId=GetSessionValue('member_id');
	$memberPerm=GetSessionValue('member_perm');
	$memberBrand=GetSessionValue('member_brand');

	if ($memberId=='')
		return API_EXIT(API_ERR, "Not signed in.");
}

$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $member->GetErrorMsg());

$brand=new VBrand($memberBrand);
if ($brand->Get($brandInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $brand->GetErrorMsg());

if ($brandInfo['status']=='INACTIVE')
	return API_EXIT(API_ERR, "The brand is not active.");

$hookId=$brandInfo['hook_id'];
//$brand->GetValue('hook_id', $hookId);
$hookInfo=array();
if ($hookId>0) {
	$hook=new VHook($hookId);
	$hook->Get($hookInfo);
}
		
//if (!isset($meetingInfo['id'])) {
if ($cmd=='ADD_MEETING') {	
	if (isset($hookInfo['add_meeting']) && $hookInfo['add_meeting']!='') {
		$args=array();
		$args['member_id']=$memberInfo['access_id'];
		if ($hook->CallHook($hookInfo['add_meeting'], $args, $resp)) {
			$code='';
			if (isset($resp['code'])) {
				$code=$resp['code'];
			}
			
			if ($code=='400') {
				if (isset($resp['message']))
					$meetingErrMsg=$resp['message'];
				else
					$meetingErrMsg="API Hook 'add_meeting' refused the request.";
				return API_EXIT(API_ERR, $meetingErrMsg);
			}
		} else {
			return API_EXIT(API_ERR, "API Hook 'add_meeting' failed to respond.");
		}
	}
	
	// add a new meeting
	$meeting=new VMeeting();
	$meetingInfo['host_id']=$memberId;
//	$host->GetValue('brand_id', $brandId);
	$meetingInfo['brand_id']=$memberBrand;
	$meetingInfo['description']="";
	$meetingInfo['keyword']="";
	$meetingInfo['title']="My Meeting";	
	
	
//	$webServerId=VUser::GetWebServerId($memberInfo);
//	if ($meetingInfo['webserver_id']!=0)
//		$webServerId=$meetingInfo['webserver_id'];
//	else
		$webServerId=VUser::GetWebServerId($memberInfo);
	
	if ($webServerId<=0) {
		return API_EXIT(API_ERR, "Web server is not set for the user.");
	}


} else {
	
	if (isset($hookInfo['set_meeting']) && $hookInfo['set_meeting']!='') {
		$args=array();
		$args['meeting_id']=$meetingInfo['access_id'];
		$args['member_id']=$memberInfo['access_id'];
		if ($hook->CallHook($hookInfo['set_meeting'], $args, $resp)) {
			$code='';
			if (isset($resp['code'])) {
				$code=$resp['code'];
			}
			
			if ($code=='400') {
				if (isset($resp['message']))
					$meetingErrMsg=$resp['message'];
				else
					$meetingErrMsg="API Hook 'set_meeting' refused the request.";
				return API_EXIT(API_ERR, $meetingErrMsg);
			}
		} else {
			return API_EXIT(API_ERR, "API Hook 'set_meeting' failed to respond.");
		}
	}

	if ($meetingInfo['host_id']!=$memberId) {
			
		// check if the member is an admin of the brand
		if ($memberPerm!='ADMIN' || $memberBrand!=$meetingInfo['brand_id']) 
		{
			return API_EXIT(API_ERR, "Not authorized");
		}	
		
	}	
	
}

if ((GetArg('scheduled', $arg)) && $arg!='') {
	$meetingInfo['scheduled']=$arg;
}

// handle both ways to set date/time. with a single param date_time or separate parameters.
$setTime=false;
if (GetArg('set_date', $setDate)) {
	// set_date is used to set both date_time and scheduled
	// set_time must be provided
	// used by the iPhone app
	if ($setDate=='') {
		$meetingInfo['scheduled']='N';
	} else {
		$meetingInfo['scheduled']='Y';
		
		GetArg('set_time', $theTime);
		$dateTime=$setDate." ".$theTime;
		$setTime=true;
		$meetingInfo['duration']="01:00:00";
	}
	
} else if ((GetArg('date_time', $dateTime)) && $dateTime!='') {
	$setTime=true;
} else {
	if ((GetArg('day', $arg)) && $arg!='') {
		$day=$arg;
		$setTime=true;
	} else {
		$day="01";
	}
	if ((GetArg('month', $arg)) && $arg!='') {
		$month=$arg;
		$setTime=true;
	} else {
		$month="01";
	}
	if ((GetArg('year', $arg)) && $arg!='') {
		$year=$arg;
		$setTime=true;
	} else {
		$year="2009";
	}
	if ((GetArg('hour', $arg)) && $arg!='') {
		if (strlen($arg)<2)
			$arg="0".$arg;
		$hour=$arg;
		$setTime=true;
	} else {
		$hour="00";
	}
	if ((GetArg('minute', $arg)) && $arg!='') {
		if (strlen($arg)<2)
			$arg="0".$arg;
		$min=$arg;
		$setTime=true;
	} else {
		$min="00";
	}
	
	$dateTime=$year."-".$month."-".$day." ".$hour.":".$min.":00";
}	
if ($setTime) {

	if (GetArg('time_zone', $tz) && $tz!='') {
		if ($tz[0]==' ')
			$tz[0]='+';
		
		// adjust for the DST status of the scheduled time for these time zones
		if ($tz=='PT' || $tz=='MT' || $tz=='CT' || $tz=='ET') {
			$val=mktime($hour, $min, 0, $month, $day, $year);
			GetTimeZoneByTime($tz, $val, $tzName, $dtz);
			$tz=$dtz;
		}			
		
		VObject::ConvertTZ($dateTime, $tz, 'SYSTEM', $meetDateTimeStr);
		
		if (!isset($meetDateTimeStr) || $meetDateTimeStr=='')
			return API_EXIT(API_ERR, "Could not convert time to the current time zone: $tz. Try to reset your time zone first.");

		$meetingInfo['date_time']=$meetDateTimeStr;
		
		if (GetArg('set_tz', $setTz) && $setTz=='1') {
			$newInfo=array();
			GetArg('time_zone', $timeZone);
			$newInfo['time_zone']=$timeZone;
			$member->Update($newInfo);
			// need to change the time zone session value
			SetSessionValue('time_zone', $timeZone);	
		}
		

//				$meetingInfo['date_time']="#(SELECT CONVERT_TZ('".$dateTime."', '".$tz."', 'SYSTEM'))";
	} else {
		$meetingInfo['date_time']=$dateTime;

	}
}

if ((GetArg('client_data', $arg))) {
	$meetingInfo['client_data']=$arg;
}

if ((GetArg('duration', $arg))) {
	$meetingInfo['duration']=$arg;
}	
if ((GetArg('password', $arg))) {
	$meetingInfo['password']=$arg;
}
if ((GetArg('can_download', $arg))) {
	$meetingInfo['can_download']=$arg;
}
if ((GetArg('can_download_rec', $arg))) {
	$meetingInfo['can_download_rec']=$arg;
}

if ((GetArg('login_type', $arg)) && $arg!='') {
	$meetingInfo['login_type']=$arg;
	if ($arg!='PWD')
		$meetingInfo['password']='';
}
if ((GetArg('meeting_type', $arg))) {
	$meetingInfo['meeting_type']=$arg;
}
if ((GetArg('regform_id', $arg))) {
	$meetingInfo['regform_id']=$arg;
}
if ((GetArg('close_register', $arg))) {
	$meetingInfo['close_register']=$arg;
}
if ((GetArg('title', $arg))) {
	$arg=str_replace("\"", "'", $arg);
	$meetingInfo['title']=$arg;	
}
if ((GetArg('description', $arg))) {
	$arg=str_replace("\"", "'", $arg);
	$meetingInfo['description']=$arg;
}
if ((GetArg('public', $arg))) {
	$meetingInfo['public']=$arg;
}
if ((GetArg('tele_conf', $arg))) {
	$meetingInfo['tele_conf']=$arg;
}
// same as tele_conf
if ((GetArg('use_tele', $arg))) {
	$meetingInfo['tele_conf']=$arg;
}
if ((GetArg('tele_num', $arg))) {
	$meetingInfo['tele_num']=$arg;
}
if ((GetArg('tele_num2', $arg))) {
	$meetingInfo['tele_num2']=$arg;
}
if ((GetArg('tele_mcode', $arg))) {
	$meetingInfo['tele_mcode']=$arg;
}
if ((GetArg('tele_pcode', $arg))) {
	$meetingInfo['tele_pcode']=$arg;
}

if ((GetArg('tele_conf_choice', $arg))) {
	if ($arg=='NONE') {
		$meetingInfo['tele_conf']='N';
		$meetingInfo['tele_num']='';
		$meetingInfo['tele_mcode']='';
		$meetingInfo['tele_pcode']='';
		
	} elseif ($arg=='CONF') {
		$meetingInfo['tele_conf']='Y';
		GetArg('conf_num', $param);
		$meetingInfo['tele_num']=$param;
		GetArg('conf_mcode', $param);
		$meetingInfo['tele_mcode']=$param;
		GetArg('conf_pcode', $param);
		$meetingInfo['tele_pcode']=$param;
		GetArg('conf_num2', $param);
		$meetingInfo['tele_num2']=$param;
		
	} elseif ($arg=='TELE') {
		$meetingInfo['tele_conf']='Y';
	}
}

if (GetArg('public_comment', $arg))
	$meetingInfo['public_comment']=$arg;

if (GetArg('send_report', $arg)) {
	$meetingInfo['send_report']=$arg;
	if (GetArg('set_all_reports', $setAll) && $setAll=='1') {
		$query="host_id = '$memberId' AND brand_id ='".$brandInfo['id']."'";		
		VObject::SelectAll(TB_MEETING, $query, $result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$ameeting=new VMeeting($row['id']);
			$aminfo=array();
			$aminfo['send_report']=$arg;
			$ameeting->Update($aminfo);			
		}
	}
}

if (GetArg('locked', $arg)) {
	
//	if (!isset($meetingInfo['id']))
//		return API_EXIT(API_ERR, "Meeting not found");

//	if ($meetingInfo['status']!='START' && $meetingInfo['status']!='START_REC')
//		return API_EXIT(API_ERR, "Meeting not in progress");

	if ($arg=='Y' || $arg=='N') {
		$meetingInfo['locked']=$arg;
		
		$hookUrl='';
		if ($arg=='Y' && isset($hookInfo['lock_meeting']))
			$hookUrl=$hookInfo['lock_meeting'];
		else if ($arg=='N' && isset($hookInfo['unlock_meeting']))
			$hookUrl=$hookInfo['unlock_meeting'];		

		if ($hookUrl!='') {
			$args=array();
			$args['member_id']=$memberInfo['access_id'];
			$args['meeting_id']=$meetingInfo['access_id'];
			$args['session_id']=$meetingInfo['session_id'];
			VHook::CallHook($hookUrl, $args, $resp);
		}

	} else
		return API_EXIT(API_ERR, "Invalid value locked=$arg");
}

//if (isset($meetingInfo['id'])) {
if ($cmd=='SET_MEETING') {
	// update an existing meeting
	if ($meeting->Update($meetingInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $meeting->GetErrorMsg());
} else if ($cmd=='ADD_MEETING') {
	// add a new meeting
	if ($meeting->Insert($meetingInfo)!=ERR_NONE)
	return API_EXIT(API_ERR, $meeting->GetErrorMsg());
}

$host=new VUser($meetingInfo['host_id']);

/* don't need to create the meeting folder until someone starts or joins the meeting
if ($host->UpdateServer()!=ERR_NONE) {
return API_EXIT(API_ERR, $host->GetErrorMsg(), 'host:UpdateServer');
}
if ($meeting->UpdateServer()!=ERR_NONE)
return API_EXIT(API_ERR, $meeting->GetErrorMsg(), 'meeting:UpdateServer');
*/
//$meeting->GetValue('access_id', $accessId);

// update the session cache file
$mInfo=array();
$meeting->Get($mInfo);
$hInfo=array();
$host->Get($hInfo);
VMeeting::WriteSessionCache($mInfo, $hInfo);


$accessId=$mInfo['access_id'];

if ($cmd=='ADD_MEETING') {	

	if (isset($hookInfo['meeting_added']) && $hookInfo['meeting_added']!='') {
		$args=array();
		$args['member_id']=$memberInfo['access_id'];
		$args['meeting_id']=$accessId;
		if ($hook->CallHook($hookInfo['meeting_added'], $args, $resp)) {
		} else {
			return API_EXIT(API_ERR, "API Hook 'meeting_added' failed to respond.");
		}
	}
} elseif ($cmd=='SET_MEETING') {
	
	if (isset($hookInfo['meeting_set']) && $hookInfo['meeting_set']!='') {
		$args=array();
		$args['member_id']=$memberInfo['access_id'];
		$args['meeting_id']=$accessId;
		if ($hook->CallHook($hookInfo['meeting_set'], $args, $resp)) {
		} else {
			return API_EXIT(API_ERR, "API Hook 'meeting_set' failed to respond.");
		}
	}	
	
}	

if (GetArg('send_email', $arg) && $arg=='1') {
	
	$meeting->GetMeetingUrl($url);
	$attachFile="meeting_".$meetingInfo['access_id'].".ics";
	$attachData=VMeeting::GetICal($meetingInfo, $url, false);		
	$memberId=GetSessionValue('member_id');
	$host->Get($hostInfo);
/*
	$fromEmail=$hostInfo['email'];
	if ($fromEmail=='')
		$fromEmail=$hostInfo['login'];
	if (!valid_email($fromEmail))
		$fromEmail=$brandInfo['from_email'];
	$fromName=$host->GetFullName($hostInfo);
*/
	$fromEmail=$brandInfo['from_email'];
	$fromName=$brandInfo['from_name'];
	
	$to='';
	if (valid_email($hostInfo['email']))
		$to=$hostInfo['email'];
	else if (valid_email($hostInfo['login']))
		$to=$hostInfo['login'];
		
	$title=$meetingInfo['title'];
	
	$body= "Meeting URL:\n".$url."\n";
	
	$errMsg=VMailTemplate::Send($fromName, $fromEmail, '', $to, $title, $body,
			$attachData, $attachFile, "text/calendar", false, null, $brandInfo);
	
}

?>