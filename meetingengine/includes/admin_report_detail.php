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

// $userEmail, $userReport and $meetingId should be set in the embedding file


$exportIcon="themes/export.gif";

$brandId=$GLOBALS['BRAND_ID'];
//GetArg('year', $year);
//GetArg('month', $month);
GetArg('date', $date);

// $userReport should be defined in the embedding file
// to show reports of a user
if (!isset($userReport)) {
	GetArg('user', $userEmail);
	GetArg('meeting', $meetingId);
} 

// CONVERT_TZ MySQL function crashes on MySQL 4.1.9 when used in WHREE
// it works on MySQL 5.0.37
// not sure what version is needed so set it to 5.0.0 for now
if (phpversion()<'5.0.0')
	$convertTz=false;
else
	$convertTz=true;	

// don't convert to local time because the export report doesn't do it.
//$convertTz==false;

GetSessionTimeZone($tzName, $tz);

$systemDt=date("Y-m-d H:i:s");
//$tz=date('T');
VObject::ConvertTZ($systemDt, 'SYSTEM', $tz, $localDt);
if ($localDt!='')
	list($today, $timeStr)=explode(" ", $localDt);
/*
if (!isset($userReport))
	$postPage=$_SERVER['PHP_SELF']."?page=".PG_ADMIN_REPORT;
else
	$postPage=$_SERVER['PHP_SELF']."?page=".PG_MEETINGS_REPORT;

$postPage.="&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$postPage.="&".SID;
*/
$postPage=$GLOBALS['BRAND_URL'];

//$exportUrl=SERVER_URL.VM_API."?cmd=GET_SESSION_INFO&brand=".$GLOBALS['BRAND_NAME'];
$exportUrl=VM_API."?cmd=GET_SESSION_INFO&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];

if ($date=='TODAY')
	$exportUrl.="&date=$today";
else if ($date!='')
	$exportUrl.="&date=$date";

//if (isset($userReport) && $userReport==true) {
	if ($userEmail!='')
		$exportUrl.="&user=".$userEmail;
	if ($meetingId!='')
		$exportUrl.="&meeting=".$meetingId;
//}

GetArg('group_id', $groupId);

if ($groupId!='')
	$exportUrl.="&group_id=".$groupId;

$exportUrl.="&file_name=report";
$exportUrl.="&time_zone=$tz";

$query="`brand_id` = '$brandId'";
if (isset($userReport) && $userReport)
	$groups='';
else {
	$prepend="<option value=\"\">".$gText['M_ALL_GROUPS']."</option>\n";
	$groups=VObject::GetFormOptions(TB_GROUP, $query, "group_id", "name", $groupId, $prepend);
}

//$selection=($month=='')?$date:$month;
$selection=$date;
$monthOpts=Get12Months($selection);

if ($userEmail!='' && !isset($userReport)) {
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vlicense.php");

	if (!isset($userInfo)) {
		$query="brand_id='".$gBrandInfo['id']."' AND LOWER(login)='".addslashes(strtolower($userEmail))."'";		
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);
		if ($errMsg!='') {
			ShowError($errMsg);
			return;
		}
		
		if (!isset($userInfo['id'])) {
			ShowError("User '$userEmail' not found");
			return;
		}
	}
	
	$licenseId=$userInfo['license_id'];

	$license=new VLicense($licenseId);
	$licInfo=array();
	if ($license->Get($licInfo)!=ERR_NONE) {
		ShowError("License not found");
		return;
	}	

	$licName=$licInfo['name'];
	if ($licInfo['expiration']=='0')
		$expStr=$gText['M_NONE'];
	else
		$expStr=$licInfo['expiration']." days";
		
	if ($licInfo['max_att']=='0')
		$attStr=$gText['M_NO_LIMIT'];
	else
		$attStr=$licInfo['max_att'];

	if ($licInfo['video_conf']=='N')
		$video=$gText['M_NO'];
	else
		$video=$gText['M_YES'];
		
	if ($licInfo['meeting_length']=='0')
		$length=$gText['M_NO_LIMIT'];
	else
		$length=$licInfo['meeting_length'].' minutes';

		
	if ($licInfo['disk_quota']=='0')
		$disk=$gText['M_NO_LIMIT'];
	else {
		$disk=$licInfo['disk_quota']." MB";
	}
	
	// get disk usage on the web server
	$serverUrl=$gBrandInfo['site_url'];
	$errMsg=VUser::GetDiskUsage($gBrandInfo['id'], $userInfo, $serverUrl, $meetingsSize, $libSize);
	if ($errMsg!='')
		ShowError($errMsg);

	$totalSize=$meetingsSize+$libSize;
	$meetingsSizeStr=(string)BytesToMB($meetingsSize)." MB";
	$libSizeStr=(string)BytesToMB($libSize)." MB";
	$totalSizeStr=(string)BytesToMB($totalSize)." MB";

	if ($licInfo['disk_quota']=='0')
		$availSize=$gText['M_NO_LIMIT'];
	else {
		$quota=(int)$licInfo['disk_quota']*1024*1024;
		$availSize=$quota-$totalSize;
		if ($availSize<=0) {
			$availSizeStr="<span class='alert'>".(string)BytesToMB($availSize)."</span> MB";
		} else {
			$availSizeStr=(string)BytesToMB($availSize)." MB";		
		}

	}
	
print <<<END

<div>${gText['M_MEMBER']}: <b>$userEmail</b></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">${gText['MEETINGS_TAB']}</th>
    <th class="pipe">${gText['M_MY_LIBRARY']}</th>
    <th class="pipe">${gText['M_TOTAL']}</th>
    <th class="tr">${gText['M_AVAILABLE']}</th>
</tr>

<tr>
	<td class="u_item_c">$meetingsSizeStr</td>
	<td class="u_item_c">$libSizeStr</td>
	<td class="u_item_c">$totalSizeStr</td>
	<td class="u_item_c">$availSizeStr</td>
</tr>

</table>
END;

} else if ($meetingId!='') {

	if (!isset($meetingInfo)) {
		$meetingInfo=array();
		$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
		
		if ($errMsg!='') {
			ShowError($errMsg);
			return;
		}
		if (!isset($meetingInfo['id'])) {
			ShowError("Couldn't find a meeting record that matches id ".$meetingId);
			return;
		}
	}
	
$title=htmlspecialchars($meetingInfo['title']);
echo "<div><b>$title</b></div>\n";
	
}

GetArg("summary", $summary);
$checkY=$checkN='';
if ($summary=='Y')
	$checkY='checked';
else
	$checkN='checked';

echo "<form target=${GLOBALS['TARGET']} method=\"GET\" action=\"$postPage\" name=\"selectmeeting_form\">\n";
if (!isset($userReport))
	echo "<input type='hidden' name='page' value='ADMIN_REPORT'>\n";
else
	echo "<input type='hidden' name='page' value='MEETINGS_REPORT'>\n";

if ($meetingId!='')
	echo "<input type='hidden' name='meeting' value='$meetingId'>\n";
if ($userEmail!='' && (!isset($userReport) || !$userReport))
	echo "<input type='hidden' name='user' value='$userEmail'>\n";

if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
	
	print <<<END
<table class='report_bar'>
<tr>
<td class='report_left'>${gText['M_SHOW_SESSIONS']}: 
$monthOpts $groups&nbsp;
<input type="submit" name="submit_month" value="${gText['M_GO']}"></td>
<td class='report_right'><a href='$exportUrl'><img src="$exportIcon">${gText['M_DOWNLOAD']}</a></td>
</tr>
</table>
</form>
END;

//<input $checkN type="radio" name="summary" value="N"><b>${gText['M_SHOW_DETAILS']}</b>&nbsp;
//<input $checkY type="radio" name="summary" value="Y"><b>${gText['M_SHOW_SUMMARY']}</b>&nbsp;



$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar


	
$hostUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_USER;
if (SID!='')
	$hostUrl.="&".SID;

$reportIcon="themes/preview.gif";
$reportBtn="<img src=\"$reportIcon\">";

if (!isset($userReport))
	$attUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ATTENDEE;
else
	$attUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_ATTENDEE;	

if (SID!='')
	$attUrl.="&".SID;
	
$transUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_TRANSCRIPT;	
if (SID!='')
	$transUrl.="&".SID;
	
$canPoll=GetSessionValue("can_poll");

$pollUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_POLL;	
if (SID!='')
	$pollUrl.="&".SID;

$timezoneText=_Text("Time zone");
echo "<div>$timezoneText: $tzName</div>\n";

$transText=_Text("Transcripts");

$enableTranscript=false;
if (defined('ENABLE_TRANSCRIPTS') && constant('ENABLE_TRANSCRIPTS')=='1')
	$enableTranscript=true;
	
?>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_SESSION']?></th>
<?php
if ($meetingId=='') {
	$text=$gText['M_ID'];
	$pollText=_Text("Polls");
	
	print <<<END
    <th class="pipe">$text</th>
END;
}
?>
    <th class="pipe"><?php echo $gText['MD_DATE']?></th>
    <th class="pipe"><?php echo $gText['MD_TIME']?></th>
    <th class="pipe"><?php echo $gText['MD_DURATION']?></th>
    <th class="pipe"><?php echo $gText['M_TITLE']?></th>
<?php if (!isset($userReport))
echo "<th class=\"pipe\">${gText['MD_HOSTED_BY']}</th>";
?>
    <th class="pipe"><?php echo $gText['M_ATTENDEES']?></th>
<?php 
if (isset($userReport) && $enableTranscript)
	echo "<th class=\"pipe\">$transText</th>";
	
if (isset($userReport) && $canPoll=='Y')
	echo "<th class=\"pipe\">$pollText</th>";

?>
</tr>


<?php

$query="(brand_id = '$brandId')";
$query.=" AND (host_login <> '')";	// exclude replay sessions (host_login='')
if ($userEmail!='') {
	$query.=" AND (LOWER(host_login)='".addslashes(strtolower($userEmail))."')";		
} 
if ($meetingId!='') {
	$query.=" AND (meeting_aid='$meetingId')";
} 
/*
if ($month!='') {
	list($year, $mn)=explode("-", $month);
	if ($convertTz)		
		$query.=" AND (YEAR(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$year' AND MONTH(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$mn')";
	else
		$query.=" AND (YEAR(start_time)='$year' AND MONTH(start_time)='$mn')";
*/

if ($groupId!='') {
//	$query.=" AND (group_id='$groupId')";

	// find all users of the group
	$groupQuery="group_id='$groupId'";
	$errMsg=VObject::SelectAll(TB_USER, $groupQuery, $groupResult);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$num_rows = mysql_num_rows($groupResult);
	
	$userQuery='';
	while ($auser = mysql_fetch_array($groupResult, MYSQL_ASSOC)) {
		if ($userQuery!='')
			$userQuery.=" OR ";
		// for each user, add a query
		$userQuery.="LOWER(host_login)='".addslashes(strtolower($auser['login']))."'";
	}
	if ($userQuery!='') {
		$query.=" AND ($userQuery)";
	} else {
		$query.=" AND (0)"; // no users in the group
	}

}

if ($date=='ALL' || $date=='') {

} elseif ($date=='NOW') {
	$query.=" AND ".VSession::GetInProgressQuery();
} elseif ($date=='TODAY') {
	if ($convertTz)
		$query.=" AND (DATE(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$today')";	
	else
		$query.=" AND (DATE(start_time)='$today')";	
} else if ($date!='') {
	$dateItems=explode("-", $date);
	$dtCount=count($dateItems);
	
	// month specified
	if ($dtCount==2) {		
		$year=$dateItems[0];
		$mn=$dateItems[1];
		if ($convertTz)		
			$query.=" AND (YEAR(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$year' AND MONTH(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$mn')";
		else
			$query.=" AND (YEAR(start_time)='$year' AND MONTH(start_time)='$mn')";
		
	
	// date specified
	} else if ($dtCount==3) {
		if ($convertTz)
			$query.=" AND (DATE(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$date')";	
		else
			$query.=" AND (DATE(start_time)='$date')";	
	}
	
}
//echo $query;

//VObject::SetTimeZone($tz);
GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_SESSION, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
	
$errMsg=VObject::SelectAll(TB_SESSION, $query, $result, $offset, $count, "*", "start_time", true);
//VObject::SetTimeZone('');
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		echo "<tr><td colspan=8>&nbsp;</td></tr>";
	}	
	$rowCount=0;	
	$lastDate='';
	$lastMeetingId=0;
	$meetingInfo=array();
	$hostInfo=array();
	$lastHostId=0;

	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		
		if ($row['meeting_aid']!=$lastMeetingId) {
			$lastMeetingId=$row['meeting_aid'];
			unset($meetingInfo);
			$errMsg=VObject::Find(TB_MEETING, 'access_id', $row['meeting_aid'], $meetingInfo);
			if ($errMsg!='') {
				ShowError($errMsg);
				break;			
			}
		}
		
		// ignore recording playback sessions
//		if ($meetingInfo['status']=='REC')
//			continue;
		
		if ($rowCount % 2)
			echo "<tr class=\"u_bg\">\n";
		else
			echo "<tr>\n";
		
		echo "<td class=\"u_item\">".$row['id']."</td>\n";

		if ($meetingId=='') {
			$meetingPage=$GLOBALS['BRAND_URL']."?meeting=".$row['meeting_aid'];
			if (SID!='')
				$meetingPage.="&".SID;
			
			// only show the meeting link if the meeting still exists in the database	
			if (isset($meetingInfo['access_id']))
				$idStr="<a target=\"".$GLOBALS['TARGET']."\" href=\"".$meetingPage."\">".$row['meeting_aid']."</a>";
			else
				$idStr=$row['meeting_aid'];
			
			echo "<td class=\"m_id\">".$idStr."</td>\n";
		}
		$startTime=$row['start_time'];
		VObject::ConvertTZ($startTime, 'SYSTEM', $tz, $tzTime);
		if ($tzTime!='') {
			list($sDate, $sTime)=explode(" ", $tzTime);		
			if ($sDate!=$lastDate) 
				$lastDate=$sDate;
			else
				$sDate='&nbsp;';
		} else {
			$sDate='&nbsp;';
			$sTime='&nbsp;';			
		}

		echo "<td class=\"u_item\">".$sDate."</td>\n";
		echo "<td class=\"u_item\">".$sTime."</td>\n";
		
		if ($meetingInfo['session_id']==$row['id'] && VMeeting::IsMeetingStarted($meetingInfo))
			$liveSession=true;
		else
			$liveSession=false;
		
		$attCount=0;
		if ($liveSession) {
//			VObject::Count(TB_ATTENDEE_LIVE, $attQuery, $attCount);
			$attCount="?";
/* don't get live attendee count as it requires accessing the hosting server, which may not be up or take too long
			// get live attendee count by checking with the meeting server
			if ($lastHostId!=$meetingInfo['host_id']) {
				$lastHostId=$meetingInfo['host_id'];
				unset($hostInfo);
				$host=new VUser($meetingInfo['host_id']);
				$host->Get($hostInfo);	
				if ($host->Get($hostInfo)!=ERR_NONE) {
					$errMsg=$host->GetErrorMsg();
					ShowError($errMsg);
					break;
				}		
			}
			
			if (isset($hostInfo['id'])) {
				$meetingServerUrl=VMeeting::GetMeetingServerUrl($hostInfo, $meetingInfo);
				$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
				
				$ret=VSession::GetLiveAttendees($meetingServerUrl.$meetingDir,
						$row['id'], true, $attCount, $attList);
				if (!$ret)
					$attCount='n/a';
			} else {
				$attCount='n/a';
			}
*/
		} else {
			$attQuery="session_id = '".$row['id']."'";		
			VObject::Count(TB_ATTENDEE, $attQuery, $attCount);
		}

//		if (VMeeting::IsMeetingStarted($meetingInfo) && $meetingInfo['session_id']==$row['id'])
		if ($liveSession)
		{
			$session=new VSession($row['id']);
//			if (is_numeric($attCount) && $attCount>0)
			if ($session->IsInProgress())
				$duration="<span class=\"u_item progress\">${gText['M_IN_PROGRESS']}</span>";
			else
				$duration="<span class=\"u_item progress\">${gText['M_IDLE']}</span>";
		} else {

			list($sDate, $sTime)=explode(" ", $row['start_time']);
			list($sYear, $sMonth, $sDay)=explode("-", $sDate);
			list($hh, $mm, $ss)=explode(":", $sTime);			
			$time1=mktime($hh, $mm, $ss, $sMonth, $sDay, $sYear);
			
			list($sDate, $sTime)=explode(" ", $row['mod_time']);
			list($sYear, $sMonth, $sDay)=explode("-", $sDate);
			list($hh, $mm, $ss)=explode(":", $sTime);
			$time2=mktime($hh, $mm, $ss, $sMonth, $sDay, $sYear);
			
			if ($time2<$time1)
				$time2=$time1+1;
			
			$timeDiff=$time2-$time1;
			$duration=SecToStr($timeDiff);
			
		}
		echo "<td class=\"u_item\">$duration</td>\n";

		$title=$row['meeting_title'];
		$maxChar=20;

		if (strlen($title)>$maxChar) {
			$title=substr($title, 0, $maxChar-2);
			$title.="...";
		}
		$title=htmlspecialchars($title);		
		
//		if (isset($meetingInfo['access_id']))
//			$titleStr="<a target=\"".$GLOBALS['TARGET']."\" href=\"".$meetingPage."\">".$title."</a>";
//		else
			$titleStr=$title;
				
		echo "<td class='u_item'>".$titleStr."</td>";
		
		if (!isset($userReport)) {
			$name=$row['host_login'];
			if (strlen($name)>28) {
				$name=substr($name, 0, 26);
				$name.="...";
			} else if ($name=='')
				$name='&nbsp;';
			
			// if @ is missing, this is a guest user and the name is the ip address or the computer dns name of the user
			// don't try to look up the user in the database
			if (strpos($name, "@")===false)		
				$hostLink=$name;
			else
				$hostLink="<a target=${GLOBALS['TARGET']} href=\"$hostUrl&user=".$row['host_login']."\">".$name."</a>";		

			echo "<td class=\"u_item\">".$hostLink."</td>";
		}
		
/*		
		$attCount = mysql_num_rows($attResult);
		$totalTime=0;
		while ($attRow = mysql_fetch_array($attResult, MYSQL_ASSOC)) {
			$totalTime+=(int)$attRow['duration'];
		}
		$attTimeStr=SecToStr($totalTime);
		
		mysql_free_result($attResult);
		echo "<td class=\"u_item\"><a target=\"".$GLOBALS['TARGET']."\" href=\"$attUrl&session_id=".$row['id']."\">$attCount/$attTimeStr $reportBtn</a>\n";
*/							
		echo "<td class=\"u_item_c\"><a target=\"".$GLOBALS['TARGET']."\" href=\"$attUrl&session_id=".$row['id']."\">$attCount $reportBtn</a></td>\n";
		
		if (isset($userReport) &&  $enableTranscript) {
			if (isset($row['transcripts']) && $row['transcripts']!='') {
				$itemCount=substr_count($row['transcripts'], "<item ");
				echo "<td class=\"u_item_c\"><a target=\"".$GLOBALS['TARGET']."\" href=\"$transUrl&session_id=".$row['id']."\">$itemCount $reportBtn</a></td>\n";
			} else
				echo "<td class=\"u_item_c\">&nbsp;</td>\n";
		}
		
		if (isset($userReport) && $canPoll=='Y') {
			if (isset($row['poll_results']) && $row['poll_results']!='' && isset($row['poll_questions']) && $row['poll_questions']!='') {
				$itemCount=substr_count($row['poll_results'], "<poll>");
				if ($itemCount>0)
					echo "<td class=\"u_item_c\"><a target=\"".$GLOBALS['TARGET']."\" href=\"$pollUrl&session_id=".$row['id']."\">$itemCount $reportBtn</a></td>\n";
				else
					echo "<td class=\"u_item_c\">&nbsp;</td>\n";
			} else
				echo "<td class=\"u_item_c\">&nbsp;</td>\n";
			
		}
		echo "</tr>\n";
		$rowCount++;	

	}
}

echo "</table>";


if (!isset($userReport))
	$listPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
else
	$listPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REPORT;
if (SID!='')
	$listPage.="&".SID;
	
//if ($month!='')
//	$listPage.="&month=".$month;
if ($date!='')
	$listPage.="&date=".$date;
if ($userEmail!='')
	$listPage.="&user=".$userEmail;
if ($meetingId!='')
	$listPage.="&meeting=".$meetingId;
if ($groupId!='')
	$listPage.="&group_id=".$groupId;
	
ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
