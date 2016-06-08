<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vsession.php");
require_once("dbobjects/vattendee.php");
//require_once("dbobjects/vattendeelive.php");

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar

// this page is embedded in an email.
GetArg("in_email", $inEmail);

if (GetArg("show_all", $showAll)) {
	$itemsPerPage=10000;
	$maxPages=10000;
	$pageNavInc=10000;
}

//GetLocalTimeZone($tzName, $tz);
GetSessionTimeZone($tzName, $tz);

//$userPage=$GLOBALS['BRAND_URL']."?".SID;
//if (SID!='')
//	$userPage.="&".SID;
	
GetArg("session_id", $sessionId);
if ($sessionId=='') {
	ShowError("Missing session_id");
	return;
}

$session=new VSession($sessionId);
$sessInfo=array();
if ($session->Get($sessInfo)!=ERR_NONE) {
	ShowError($session->GetErrorMsg());
	return;
}

if (!isset($sessInfo['id'])) {
	ShowError("Couldn't find session for id ".$sessionId);
	return;
}

$memberLogin=GetSessionValue('member_login');

// only show the session info if I am the host of the session or admin of the site
if ($sessInfo['host_login']!=$memberLogin && !(GetSessionValue('member_perm')=='ADMIN' && GetSessionValue('member_brand')==$sessInfo['brand_id'])) {
	ShowError("You are not a host of the meeting.");
	return;	
}

$meetingInfo=array();
$errMsg=VObject::Find(TB_MEETING, 'access_id', $sessInfo['meeting_aid'], $meetingInfo);

$meetingTitle=htmlspecialchars($sessInfo['meeting_title']);

$startTime=$sessInfo['start_time'];
VObject::ConvertTZ($startTime, 'SYSTEM', $tz, $tzTime);	


?>
<div class=heading1><?php echo $gText['M_ATTENDEES']?></div>

<?php
// don't show this line when the report is embedded in email
if ($inEmail=='' && isset($sessInfo['id'])) {
?>
<div><?php echo $gText['M_SESSION'].": ". $sessInfo['id']." "._Text("Meeting ID")." ".$sessInfo['meeting_aid']." <b>'".
$meetingTitle."'</b><br>"._Text("Hosted by").": <b>".$sessInfo['host_login']."</b> "._Text("Time").": <b>".$tzTime." ".$tzName."</b>";?>
</div>
<?php
}
?>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="pipe">&nbsp;</th>
    <th class="pipe"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe"><?php echo $gText['MD_TIME']?></th>
    <th class="pipe"><?php echo $gText['MD_DURATION']?></th>
    <th class="pipe"><?php echo $gText['MD_PHONE_NUM']?></th>
    <th class="pipe"><?php echo "IP"?></th>
<?php
if (!isset($userReport) || !$userReport) {
	$serverText=_Text("Server");
	echo "<th class=\"pipe\">$serverText</th>\n";
}
?>
    <th class="pipe"><?php echo _Text("Webcam")?></th>
</tr>
<?php

$query="session_id ='".$sessInfo['id']."'";

if ($meetingInfo['session_id']==$sessionId && VMeeting::IsMeetingStarted($meetingInfo))
	$liveSession=true;
else
	$liveSession=false;


GetArg('offset', $offset);
if ($offset=='') {

	$total=0;
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}

$attList=array();
$attCount=0;
$errMsg='';

if ($liveSession) {

	// get live attendees from the hosting server
	$host=new VUser($meetingInfo['host_id']);
	$hostInfo=array();
	if ($host->Get($hostInfo)!=ERR_NONE) {
		$errMsg=$host->GetErrorMsg();
	}
	
	if (isset($hostInfo['id'])) {
		$meetingServerUrl=VMeeting::GetMeetingServerUrl($hostInfo, $meetingInfo);
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		
		// get only the current attendees
		$ret=VSession::GetAttendees($meetingServerUrl.$meetingDir,
				$sessInfo['id'], "", false, false, $meetingInfo['access_id'], $meetingInfo['tele_num'], $meetingInfo['tele_mcode'], $attCount, $attList);
//				$sessInfo['id'], "", false, true, $meetingInfo['access_id'], $meetingInfo['tele_num'], $meetingInfo['tele_mcode'], $attCount, $attList);
				
		if ($total==0)
			$total=$attCount;
	}	

} else {
	if ($total==0)
		VObject::Count(TB_ATTENDEE, $query, $total);
		
	$select="id, user_id, user_name, start_time, (TIME_TO_SEC(TIMEDIFF(mod_time, start_time))-break_time) as duration, break_time, caller_id, user_ip, cam_time, server_id";

	$errMsg=VObject::SelectAll(TB_ATTENDEE, $query, $result, $offset, $count, $select, "start_time");
	$num_rows = mysql_num_rows($result);
	
}


if ($errMsg!='') {
	ShowError($errMsg);
} else {

	$totalTime=0;
	$rowCount=0;
	$lastDate='';
	while (1) {
		
		if ($liveSession) {
			if ($rowCount>=$attCount)
				break;
			$row=array();
			
			$att=$attList[$rowCount];
			$row['user_name']=GetArrayValue($att, "username");
			$row['user_id']=GetArrayValue($att, "userid");
			$row['user_ip']=GetArrayValue($att, "userip");
			$row['caller_id']=GetArrayValue($att, "callerId");
			$row['call_id']=GetArrayValue($att, "callId");
			$row['cam_time']=GetArrayValue($att, "camTime");
			$row['server_id']=GetArrayValue($att, "serverId");
			$startTime=(integer)GetArrayValue($att, "startTime", 0);
			$endTime=(integer)GetArrayValue($att, "modTime", 0);
			$breakTime=(integer)GetArrayValue($att, "breakTime", 0);
			
			if ($startTime>0 && $endTime>0) {
				$row['start_time']=date("Y-m-d H:i:s", $startTime);
				$row['duration']=$endTime-$startTime-$breakTime;
			} else {
				$row['start_time']='n/a';
				$row['duration']='n/a';
			}			

		} else {			
			$row = mysql_fetch_array($result, MYSQL_ASSOC);
			if (!$row)
				break;
		}
		if ($rowCount % 2)
			echo "<tr class=\"u_bg\">\n";
		else
			echo "<tr>\n";
		
		echo "<td class=\"u_item\">".($offset+$rowCount+1)."</td>\n";

		$name=$row['user_name'];
		if ($name=='' && $row['call_id']!='')
			$name="(call ".$row['call_id'].")";
			
		if (strlen($name)>30) {
			$name=substr($name, 0, 30);
			$name.="...";
		}
		$name=htmlspecialchars($name);
		
		// the 'user_id' may not be a valid member id so don't create a link for it.
//		if ($row['user_id']>0)
//			$nameStr="<a target=${GLOBALS['TARGET']} href=\"".$userPage."&user=".$row['user_id']."\">".$name."</a>";
//		else
			$nameStr=$name;
				
		echo "<td class='u_item'><b>".$nameStr."</b></td>\n";
		
		$startTime=$row['start_time'];
		VObject::ConvertTZ($startTime, 'SYSTEM', $tz, $tzTime);
		if ($tzTime!='') {
			list($sDate, $sTime)=explode(" ", $tzTime);
			if ($sDate!=$lastDate) 
				$lastDate=$sDate;
			else
				$sDate='';
		} else {
			$sDate='';
			$sTime='&nbsp;';
		}
		if ($sDate!='')
			$timeStr=$sDate."<br>".$sTime;
		else
			$timeStr=$sTime;
//		echo "<td class=\"u_item_c\">".$sDate."</td>\n";
		echo "<td class=\"u_item_c\">$timeStr</td>\n";

/*
		list($sDate, $sTime)=explode(" ", $row['start_time']);
		list($sYear, $sMonth, $sDay)=explode("-", $sDate);
		list($hh, $mm, $ss)=explode(":", $sTime);			
		$time1=mktime((int)$hh, (int)$mm, (int)$ss, (int)$sMonth, (int)$sDay, (int)$sYear);
		
		if ($row['mod_time']!=null) {
			list($sDate, $sTime)=explode(" ", $row['mod_time']);
			list($sYear, $sMonth, $sDay)=explode("-", $sDate);
			list($hh, $mm, $ss)=explode(":", $sTime);
			$time2=mktime((int)$hh, (int)$mm, (int)$ss, (int)$sMonth, (int)$sDay, (int)$sYear);
			if ($time2<$time1)
				$time2=$time1+1;
		} else {
			$time2=$time1;
		}
		
		$timeDiff=$time2-$time1;
		$duration=SecToStr($timeDiff);
		$totalTime+=$timeDiff;
*/		
		$timeDiff=(int)$row['duration'];
		$totalTime+=$timeDiff;
		$duration=SecToStr($timeDiff);

		echo "<td class=\"u_item_c\">$duration</td>\n";
/*
		// if modification time is less than 20 seconds old, assume the attendee is in the meeting.
		if ((time()-$time2)<20)
			$in='Y';
		else
			$in='N';
		
		$breakTime=$row['break_time'];
		echo "<td class='u_item_c'>$breakTime</td>";
*/
		
		$phone=$row['caller_id'];
		if ($phone=='')
			$phone='&nbsp;';
		echo "<td class=\"u_item\">$phone</td>\n";
		
		$ip=$row['user_ip'];
		if ($ip=='')
			$ip="&nbsp;";
		echo "<td class=\"u_item\">$ip</td>\n";
		
		if (!isset($userReport) || !$userReport) {
			$serverId=$row['server_id'];
			if ($serverId!='' && $serverId!='0') {
				$target=$GLOBALS['TARGET'];
				$link=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_WEB."&id=".$serverId;
				if (SID!='')
					$link.="&".SID;
				echo "<td class=\"u_item_c\"><a target='$target' href='$link'>$serverId</a></td>\n";
			} else {
				echo "<td class=\"u_item_c\">&nbsp;</td>\n";
			}

		}
		
		$camTime=$row['cam_time'];
		$camTimeStr=SecToStr($camTime);
		echo "<td class=\"u_item_c\">$camTimeStr</td>\n";

		echo "</tr>\n";
		$rowCount++;	

	}
	

}

echo "</table>\n";
if ($showAll=='1')
	return;
	
$duration=SecToStr($totalTime);

// no pagination is needed because live session attendees are only shown at once
if (!$liveSession) {
	echo "<div id='meeting_stat'>Sub-total: <b>$duration</b></div>\n";

	if (isset($userReport))
		$listPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_ATTENDEE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	else
		$listPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ATTENDEE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];

	$listPage.="&session_id=".$sessionId;
	if (SID!='')
		$listPage.="&".SID;
	ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);
}

?>
