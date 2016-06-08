<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
require_once("server_config.php");
require_once("includes/common.php");
require_once("includes/brand.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");

//$redirectPage=$gBrandInfo['site_url'];
GetArg('ret', $redirectPage);
if ($redirectPage=='') {
	$redirectPage=$GLOBALS['BRAND_URL'];
	if (SID!='')
		$redirectPage.='?'.SID;
}

/* don't automatically end meetings in progress when signing out because the host may have signed out from a different computer
// end all started meetings
// if HOST_MULTI_MEETING is not defined or set to 0
if (!defined("HOST_MULTI_MEETINGS") || constant("HOST_MULTI_MEETINGS")=="0") {
	$memberId=GetSessionValue('member_id');
	if ($memberId!='') {
	//	$query="(status='START' OR status='START_REC') AND host_id='$memberId'";
		$query="(status='START') AND host_id='$memberId'";
		$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
		if ($errMsg!='') {
			// ignore
		} else {
			$num_rows = mysql_num_rows($result);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			
				$aMeeting=new VMeeting($row['id']);
				if ($aMeeting->EndMeeting()!=ERR_NONE) {
					// ignore
				}
			}
		}
		
		$newInfo['login_session_id']="";
		$newInfo['login_start_time']='';
		$user=new VUser($memberId);
		$user->Update($newInfo);
	}
}
*/
//include_once("includes/house_keeping.php");

// make these values persistent even after signing out
$tz=GetSessionValue('time_zone');
$meetingId=GetSessionValue('meeting_id');
$ilogin=GetSessionValue('iphone_login');
$iuser=GetSessionValue('iphone_username');

EndSession();
StartSession();
if ($tz!='')
	SetSessionValue('time_zone', $tz);
if ($meetingId!='')
	SetSessionValue('meeting_id', $meetingId);
if ($ilogin!='')
	SetSessionValue('iphone_login', $ilogin);
if ($iuser!='')
	SetSessionValue('iphone_username', $iuser);

header("Location: $redirectPage");
exit();
?>

