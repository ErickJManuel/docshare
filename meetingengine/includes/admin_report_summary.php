<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


// This file is not used currently

require_once("dbobjects/vsession.php");

GetSessionTimeZone($tzName, $tz);

// CONVERT_TZ MySQL function crashes on MySQL 4.1.9 when used in WHREE
// it works on MySQL 5.0.37
// not sure what version is needed so set it to 5.0.0 for now
if (phpversion()<'5.0.0')
	$convertTz=false;
else
	$convertTz=true;	

$brandQuery="(brand_id ='".$GLOBALS['BRAND_ID']."')";

//$thisYear=(int)date('Y');
//$thisMonth=(int)date('n');
$systemDt=date("Y-m-d H:i:s");
//$tz=date('T');
VObject::ConvertTZ($systemDt, 'SYSTEM', $tz, $localDt);
list($today, $timeStr)=explode(" ", $localDt);
list($thisYear, $thisMonth, $thisDate)=explode("-", $today);

//if ($thisMonth<10)
//	$thisMonthStr="0".$thisMonth;
//else
	$thisMonthStr=(string)$thisMonth;

$lastMonth=(int)$thisMonth-1;
if ($lastMonth==0)
	$lastMonth=12;
	
if ($lastMonth<10)
	$lastMonthStr="0".$lastMonth;
else
	$lastMonthStr=(string)$lastMonth;

$trialLicId=1; // assume the first license is the trial license
$trialQuery=" AND (license_id='$trialLicId')";
if ($convertTz) {
	$todayQuery=" AND (DATE(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$today')";
	$thisMonthQuery=" AND (YEAR(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$thisYear' AND MONTH(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$thisMonth')";
	$lastMonthQuery=" AND (YEAR(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$thisYear' AND MONTH(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$lastMonth')";
} else {
	$todayQuery=" AND (DATE(create_date)='$today')";
	$thisMonthQuery=" AND (YEAR(create_date)='$thisYear' AND MONTH(create_date)='$thisMonth')";
	$lastMonthQuery=" AND (YEAR(create_date)='$thisYear' AND MONTH(create_date)='$lastMonth')";
}
$memberQuery=" AND (license_id<>'$trialLicId')";
$memberPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_USERS;

// trial users summary
$query=$brandQuery.$trialQuery.$todayQuery;

$link=$memberPage."&license_id=".$trialLicId."&date=TODAY";
$todayTrialsStr=CreateItemStr(TB_USER, $query, $link);

$query=$brandQuery.$trialQuery.$thisMonthQuery;
$link=$memberPage."&license_id=".$trialLicId."&month=".$thisYear."-".$thisMonthStr;
$thisMonthTrialsStr=CreateItemStr(TB_USER, $query, $link);

$query=$brandQuery.$trialQuery.$lastMonthQuery;
$link=$memberPage."&license_id=".$trialLicId."&month=".$thisYear."-".$lastMonthStr;
$lastMonthTrialsStr=CreateItemStr(TB_USER, $query, $link);

$query=$brandQuery.$trialQuery;
$link=$memberPage."&license_id=".$trialLicId;
$totalTrialsStr=CreateItemStr(TB_USER, $query, $link);

// members summary
$query=$brandQuery.$memberQuery.$todayQuery;
$link=$memberPage."&license_id=non_trial&date=TODAY";
$todayMemberStr=CreateItemStr(TB_USER, $query, $link);

$query=$brandQuery.$memberQuery.$thisMonthQuery;
$link=$memberPage."&license_id=non_trial&month=".$thisYear."-".$thisMonthStr;
$thisMonthMemberStr=CreateItemStr(TB_USER, $query, $link);

$query=$brandQuery.$memberQuery.$lastMonthQuery;
$link=$memberPage."&license_id=non_trial&month=".$thisYear."-".$lastMonthStr;
$lastMonthMemberStr=CreateItemStr(TB_USER, $query, $link);

$query=$brandQuery.$memberQuery;
$link=$memberPage."&license_id=non_trial";
$totalMemberStr=CreateItemStr(TB_USER, $query, $link);

// # of sessions
$reportPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
if ($convertTz) {
	$todayQuery=" AND (DATE(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$today')";
	$thisMonthQuery=" AND (YEAR(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$thisYear' AND MONTH(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$thisMonth')";
	$lastMonthQuery=" AND (YEAR(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$thisYear' AND MONTH(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$lastMonth')";
} else {
	$todayQuery=" AND (DATE(start_time)='$today')";
	$thisMonthQuery=" AND (YEAR(start_time)='$thisYear' AND MONTH(start_time)='$thisMonth')";
	$lastMonthQuery=" AND (YEAR(start_time)='$thisYear' AND MONTH(start_time)='$lastMonth')";
}
$nowQuery=" AND ".VSession::GetInProgressQuery();


$query=$brandQuery.$nowQuery;
$link=$reportPage."&date=NOW";
$nowSessionStr=CreateItemStr(TB_SESSION, $query, $link);

$query=$brandQuery.$todayQuery;
$link=$reportPage."&date=TODAY";
$todaySessionStr=CreateItemStr(TB_SESSION, $query, $link);

$query=$brandQuery.$thisMonthQuery;
$link=$reportPage."&month=".$thisYear."-".$thisMonthStr;
$thisMonthSessionStr=CreateItemStr(TB_SESSION, $query, $link);

$query=$brandQuery.$lastMonthQuery;
$link=$reportPage."&month=".$thisYear."-".$lastMonthStr;
$lastMonthSessionStr=CreateItemStr(TB_SESSION, $query, $link);

$query=$brandQuery;
$link=$reportPage;
$totalSessionStr=CreateItemStr(TB_SESSION, $query, $link);

// # of attendees
$query=$brandQuery.$nowQuery;
$nowAttStr=GetAttCount($query);

$query=$brandQuery.$todayQuery;
$todayAttStr=GetAttCount($query);

$query=$brandQuery.$thisMonthQuery;
$thisMonthAttStr=GetAttCount($query);

$query=$brandQuery.$lastMonthQuery;
$lastMonthAttStr=GetAttCount($query);

$query=$brandQuery;
$totalAttStr=GetAttCount($query);


// concurrent attendees
/* this is not correct. don't use it
$query=$brandQuery.$todayQuery;
$todayConcurStr=GetConcurAttCount($query);

$query=$brandQuery.$thisMonthQuery;
$thisMonthConcurStr=GetConcurAttCount($query);

$query=$brandQuery.$lastMonthQuery;
$lastMonthConcurStr=GetConcurAttCount($query);

$query=$brandQuery;
$totalConcurStr=GetConcurAttCount($query);
*/

// attendee time
$query=$brandQuery.$todayQuery;
$todayTimeStr=GetAttTime($query);

$query=$brandQuery.$thisMonthQuery;
$thisMonthTimeStr=GetAttTime($query);

$query=$brandQuery.$lastMonthQuery;
$lastMonthTimeStr=GetAttTime($query);

$query=$brandQuery;
$totalTimeStr=GetAttTime($query);

function CreateItemStr($tbName, $query, $link) {
	global $tz;
	VObject::SetTimeZone($tz);
	$msg=VObject::Count($tbName, $query, $count);
	VObject::SetTimeZone('');
	if ($msg!='') {
		ShowError($msg);
		return;
	}
	if ($count>0) {
		if (SID!='')
			$link.="&".SID;
		$str="<a target=\"".$GLOBALS['TARGET']."\" href='$link'>$count</a>";
	} else
		$str=$count;
	
	return $str;
}

function GetAttCount($query)
{	
	global $tz;
	VObject::SetTimeZone($tz);
	$errMsg=VObject::SelectAll(TB_SESSION, $query, $result);
	VObject::SetTimeZone('');
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$total=0;	
	$num_rows = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$where="session_id='".$row['id']."'";		
		VObject::Count(TB_ATTENDEE, $where, $attCount);
		$total+=(int)$attCount;
	}
	return $total;
}

function GetAttTime($query)
{	
	global $tz;
	VObject::SetTimeZone($tz);
	$errMsg=VObject::SelectAll(TB_SESSION, $query, $result);
	VObject::SetTimeZone('');
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$total=0;	
	$num_rows = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
//		$where="session_id='".$row['id']."' AND mod_time<>'0000-00-00 00:00:00' AND mod_time<>'0000-01-01 00:00:00'";
		// change the query to allow the default epoch time
		$where="session_id='".$row['id']."' AND mod_time>'1961-01-01 00:00:00'";
		$attTb=TB_ATTENDEE;
		// total attendee time (sec)=sum(mod_time-start_time-break_time)
		// mod_time is the last time the attendee sends a hearbeat to the server
		// start_time is the time the attendee starts
		// break_is the time (in sec) the attendee is away from the meeting (if none zero) during the session
		$sql="SELECT SUM(TIME_TO_SEC(TIMEDIFF(mod_time, start_time))-break_time) FROM `$attTb` WHERE $where";
		$ret=VObject::SendQuery($sql, $sqlResults);			
		$rowInfo= mysql_fetch_row($sqlResults);
		if (isset($rowInfo[0]))
			$total+=(int)$rowInfo[0];
			
		mysql_free_result($sqlResults);
	}

	$total=round($total/60);
	$timeStr=SecToStr($total);
	return $timeStr;
}
/*
function GetConcurAttCount($query, $link=null)
{
	$errMsg=VObject::SelectAll(TB_SESSION, $query, $result);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$count=0;	
	$num_rows = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$count+=(int)$row['max_concur_att'];

	}

	if ($count>0 && $link) {
		if (SID!='')
			$link.="&".SID;
		$str="<a target=${GLOBALS['TARGET']} href='$link'>$count</a>";
	} else
		$str=$count;

	return $count;
}
*/


?>

<div class=heading2><?php echo $gText['M_MEMBERS']?></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">&nbsp;</th>
    <th class="tl pipe"><?php echo $gText['M_TODAY']?></th>
    <th class="pipe"><?php echo $gText['M_THIS_MONTH']?></th>
    <th class="pipe"><?php echo $gText['M_LAST_MONTH']?></th>
    <th class="tr"><?php echo $gText['M_TOTAL']?></th>
</tr>

<tr>
	<td class='u_item'><?php echo $gText['M_TRIALS']?></td>
	<td class='u_item_c'><?php echo $todayTrialsStr?></td>
	<td class='u_item_c'><?php echo $thisMonthTrialsStr?></td>
	<td class='u_item_c'><?php echo $lastMonthTrialsStr?></td>
	<td class='u_item_c'><?php echo $totalTrialsStr?></td>
</tr>

<tr>
	<td class='u_item'><?php echo $gText['M_MEMBERS']?></td>
	<td class='u_item_c'><?php echo $todayMemberStr?></td>
	<td class='u_item_c'><?php echo $thisMonthMemberStr?></td>
	<td class='u_item_c'><?php echo $lastMonthMemberStr?></td>
	<td class='u_item_c'><?php echo $totalMemberStr?></td>
</tr>


</table>

<div class=heading2><?php echo $gText['M_SESSIONS']?></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">&nbsp;</th>
    <th class="tl pipe"><?php echo $gText['M_IN_PROGRESS']?></th>
    <th class="tl pipe"><?php echo $gText['M_TODAY']?></th>
    <th class="pipe"><?php echo $gText['M_THIS_MONTH']?></th>
    <th class="pipe"><?php echo $gText['M_LAST_MONTH']?></th>
    <th class="tr"><?php echo $gText['M_TOTAL']?></th>
</tr>

<tr>
	<td class='u_item'><?php echo $gText['M_NUM_SESSIONS']?></td>
	<td class='u_item_c'><?php echo $nowSessionStr?></td>
	<td class='u_item_c'><?php echo $todaySessionStr?></td>
	<td class='u_item_c'><?php echo $thisMonthSessionStr?></td>
	<td class='u_item_c'><?php echo $lastMonthSessionStr?></td>
	<td class='u_item_c'><?php echo $totalSessionStr?></td>
</tr>

<tr>
	<td class='u_item'><?php echo $gText['M_NUM_ATTENDEES']?></td>
	<td class='u_item_c'><?php echo $nowAttStr?></td>
	<td class='u_item_c'><?php echo $todayAttStr?></td>
	<td class='u_item_c'><?php echo $thisMonthAttStr?></td>
	<td class='u_item_c'><?php echo $lastMonthAttStr?></td>
	<td class='u_item_c'><?php echo $totalAttStr?></td>
</tr>
<!--
<tr>
	<td class='u_item'><?php echo $gText['M_CONCUR_ATTENDEES']?></td>
	<td class='u_item_c'><?php echo $todayConcurStr?></td>
	<td class='u_item_c'><?php echo $thisMonthConcurStr?></td>
	<td class='u_item_c'><?php echo $lastMonthConcurStr?></td>
	<td class='u_item_c'><?php echo $totalConcurStr?></td>
</tr>
-->

<tr>
	<td class='u_item'><?php echo $gText['M_TOTAL_ATTENDEE_TIME']?></td>
	<td class='u_item_c'>N/A</td>
	<td class='u_item_c'><?php echo $todayTimeStr?></td>
	<td class='u_item_c'><?php echo $thisMonthTimeStr?></td>
	<td class='u_item_c'><?php echo $lastMonthTimeStr?></td>
	<td class='u_item_c'><?php echo $totalTimeStr?></td>
</tr>
</table>
