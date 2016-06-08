<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vsession.php");
require_once("dbobjects/vattendee.php");
require_once("dbobjects/vattendeelive.php");

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar

if (GetArg("show_all", $showAll)) {
	$itemsPerPage=10000;
	$maxPages=10000;
	$pageNavInc=10000;
}

GetSessionTimeZone($tzName, $tz);
//GetArg("session_id", $sessionId);
//if ($sessionId=='') {
	if (GetArg("meeting", $meetingId) && $meetingId!='')
		VObject::Find(TB_SESSION, 'meeting_aid', $meetingId, $sessInfo);
//}

if (!isset($meetingInfo) && isset($sessInfo['meeting_aid']) ) {
	$meetingInfo=array();
	VObject::Find(TB_MEETING, 'access_id', $sessInfo['meeting_aid'], $meetingInfo);
}

if (isset($meetingInfo['title']))
	$meetingTitle=htmlspecialchars($meetingInfo['title']);
else
	$meetingTitle='';


if (isset($sessInfo['meeting_aid'])) {
	echo "<div>"._Text("Meeting ID")." ".$sessInfo['meeting_aid']." <b>'".$meetingTitle."'</b> "._Text("Time zone").": <b>".$tzName."</b></div>\n";
}

?>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">&nbsp;</th>
    <th class="pipe"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe"><?php echo $gText['MD_DATE']?></th>
    <th class="pipe"><?php echo $gText['MD_TIME']?></th>
    <th class="tr"><?php echo _Text("Computer address")?></th>
</tr>
<?php

if (isset($sessInfo['id']))
	$query="session_id ='".$sessInfo['id']."'";
else
	$query="session_id ='0'";


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


if ($total==0)
	VObject::Count(TB_ATTENDEE, $query, $total);
	
$select="id, user_id, user_name, start_time, user_ip";

$errMsg=VObject::SelectAll(TB_ATTENDEE, $query, $result, $offset, $count, $select, "start_time", true);
$num_rows = mysql_num_rows($result);
	
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	
//	$num_rows = mysql_num_rows($result);

	$totalTime=0;
	$rowCount=0;
	$lastDate='';
	$lastHost='';
	$lastIp='';
//	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while (1) {
		
		$row = mysql_fetch_array($result, MYSQL_ASSOC);
		if (!$row)
			break;

		if ($rowCount % 2)
			echo "<tr class=\"u_bg\">\n";
		else
			echo "<tr>\n";
		
		echo "<td class=\"u_item\">".($offset+$rowCount+1)."</td>\n";

		$name=$row['user_name'];
		if (strlen($name)>30) {
			$name=substr($name, 0, 30);
			$name.="...";
		}
		$name=htmlspecialchars($name);
		if ($name=='')
			$name=_Text("(Unknown)");	//_Comment: as in an unknown user name
				
		echo "<td class='u_name'>".$name."</td>";
		
		$startTime=$row['start_time'];
		VObject::ConvertTZ($startTime, 'SYSTEM', $tz, $tzTime);
		if ($tzTime!='') {
			list($sDate, $sTime)=explode(" ", $tzTime);
//			if ($sDate!=$lastDate) 
//				$lastDate=$sDate;
//			else
//				$sDate='&nbsp;';
		} else {
			$sDate='&nbsp;';
			$sTime='&nbsp;';
		}
		
		$hostName='&nbsp;';
		if ($row['user_ip']!='') {
			if ($row['user_ip']!=$lastIp) {
				$hostName=@gethostbyaddr($row['user_ip']);
				$lastIp=$row['user_ip'];
				$lastHost=$hostName;
			} else {
				$hostName=$lastHost;
			}
		}

		echo "<td class=\"u_item_c\">".$sDate."</td>\n";
		echo "<td class=\"u_item_c\">".$sTime."</td>\n";
		echo "<td class=\"u_item\">".$hostName."</td>\n";
		
		echo "</tr>\n";
		$rowCount++;	

	}
	

}

echo "</table>";
if ($showAll=='1')
	return;
	
	
if (!isset($userReport))
	$listPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
else
	$listPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REPORT;

/*
if (isset($userReport))
	$listPage=$_SERVER['PHP_SELF']."?page=".PG_MEETINGS_ATTENDEE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
else
	$listPage=$_SERVER['PHP_SELF']."?page=".PG_ADMIN_ATTENDEE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
*/
if (isset($sessInfo['meeting_aid']))
	$listPage.="&meeting=".$sessInfo['meeting_aid'];

if (SID!='')
	$listPage.="&".SID;
ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
