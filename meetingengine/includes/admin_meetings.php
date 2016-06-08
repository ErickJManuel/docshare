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
require_once("includes/meetings_common.php");

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar

//GetLocalTimeZone($tzName, $tz);
GetSessionTimeZone($tzName, $tz);
$stz=GetSessionValue('time_zone');

$meetingPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_MEETING;
if (SID!='')
	$meetingPage.="&".SID;

$hostUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_USER;
if (SID!='')
	$hostUrl.="&".SID;
	
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$thisPage=$_SERVER['PHP_SELF'];
$retPage=$thisPage."?page=".PG_ADMIN_MEETINGS."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);
$deleteMeetingUrl=VM_API."?cmd=DELETE_MEETING&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteMeetingUrl.="&".SID;

$reportIcon="themes/preview.gif";
$reportBtn="<img src=\"$reportIcon\">".$gText['M_REPORTS'];
$reportUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
if (SID!='')
	$reportUrl.="&".SID;
	
GetArg('select', $select);
GetArg('search', $search);
	
$selectOpts=array(
//	"CURRENT" => $gText['M_SELECT_CURRENT'],
//	"TODAY" => $gText['M_SELECT_TODAY'],
//	"PAST" => $gText['M_SELECT_PAST'],
	"ALL_MEETINGS" => $gText['M_ALL_MEETINGS'],
	"INPROGRESS" => $gText['M_SELECT_INPROGRESS'],
	"RECORDINGS" => $gText['M_RECORDINGS']
);

$showText=$gText['M_SHOW'];
//$postPage=$_SERVER['PHP_SELF']."?page=".PG_ADMIN_MEETINGS."&brand=".$GLOBALS['BRAND_NAME'];
//if (SID!='')
//	$postPage.="&".SID;
//$postPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_MEETINGS;
$postPage=$GLOBALS['BRAND_URL'];

$selections='<select name="select">';

foreach ($selectOpts as $key => $value) {
	if ($select==$key)
		$selections.="<option value=\"$key\" selected>".$value."</option>";
	else
		$selections.="<option value=\"$key\">".$value."</option>";
}

$selections.="</select>";

?>

<div class='meetings_select'>
<form target="<?php echo $GLOBALS['TARGET'];?>" method="GET" action="<?php echo $postPage?>" name="selectmeeting_form">

<input type="hidden" name="page" value="ADMIN_MEETINGS">
<?php
if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
?>
<span id="select_show"><?php echo $gText['M_SHOW']?>:
<?php echo $selections?>
<input type="submit" name="submit_select" value="<?php echo $gText['M_GO']?>">
</span>
<span id="select_search">Meeting id or title:
<input type="text" name="search" size="10" value="<?php echo $search?>">
<input type="submit" name="submit_search" value="<?php echo $gText['M_SEARCH']?>">
</span>
</form></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe"><?php echo $gText['M_TITLE']?></th>
    <th class="pipe"><?php echo $gText['M_HOST_NAME']?></th>
    <th class="pipe"><?php echo $gText['M_DATE']?></th>
    <th class="pipe"><?php echo $gText['M_TIME']?></th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

$query='1';
if ($search!='') {
	$dbsearch=VObject::MyAddSlashes($search);
	$query.=" AND ((access_id LIKE '%$dbsearch%') OR  (title LIKE '%$dbsearch%') OR (description LIKE '%$dbsearch%'))";
} else if ($select=='' ||  $select=='CURRENT') {
	$query.=" AND (status<>'REC' AND (scheduled='N' OR CURDATE()<=DATE(date_time)))";
} else if ($select=='ALL_MEETINGS') {
	$query.=" AND (status<>'REC')";		
} else if ($select=='PAST') {
	$query.=" AND (status<>'REC' AND (scheduled='Y' AND CURDATE()>DATE(date_time)))";
} else if ($select=='INPROGRESS') {
	$query.=" AND (status<>'REC' AND status<>'STOP')";
} else if ($select=='RECORDINGS') {
	$query.=" AND (status='REC')";
}
$query.=" AND brand_id ='".$GLOBALS['BRAND_ID']."'";

GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_MEETING, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
	
$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, $offset, $count, "*", "id", true);
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		echo "<tr><td colspan=6>&nbsp;</td></tr>";
	}	
	$rowCount=0;	
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($rowCount % 2)
			echo "<tr class=\"u_bg\">\n";
		else
			echo "<tr>\n";
		
		$meetingLink=$meetingPage."&meeting=".$row['access_id'];
		echo "<td class=\"m_id\"><a target=${GLOBALS['TARGET']} href=\"".$meetingLink."\">".$row['access_id']."</a></td>\n";
		$title=$row['title'];
		if (strlen($title)>23) {
			$title=substr($title, 0, 23-3);
			$title.="...";
		}
		$title=htmlspecialchars($title);
		if ($title=='')
			$title='&nbsp;';
		echo "<td class='u_name'>".$title."</td>\n";
	
		$host=new VUser($row['host_id']);
		$hostInfo=array();
		$host->Get($hostInfo);
		$hostName=$host->GetFullName($hostInfo);
		$hostName=htmlspecialchars($hostName);
		if ($hostName=='')
			$hostName='&nbsp;';
		$hostLink="<a target=${GLOBALS['TARGET']} href=\"$hostUrl&user=".$hostInfo['access_id']."\">".$hostName."</a>";
		
		echo "<td class=\"u_item\">".$hostLink."</td>\n";
		
		$dateStr='&nbsp;';
		$timeStr='&nbsp;';					
		if ($row['scheduled']=='Y') {
			$dtime=$row['date_time'];
			GetTimeZoneByDate($stz, $dtime, $tzName, $dtz);
			VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
			if ($tzTime!='') {
				list($dateStr, $time)=explode(" ", $tzTime);
				list($hh, $mm, $ss)=explode(":", $time);
				$timeStr=" ".H24ToH12($hh, $mm);
			}
		}

		echo "<td class=\"u_item_c\">$dateStr</td>\n";
		if (VMeeting::IsMeetingStarted($row)) {
			
			if (VMeeting::IsMeetingInProgress($row))																	
				echo "<td class=\"u_item_c progress\">${gText['M_IN_PROGRESS']}</td>\n";
			else
				echo "<td class=\"u_item_c progress\">${gText['M_IDLE']}</td>\n";
				
		} else {
			echo "<td class=\"u_item_c\">$timeStr</td>\n";		
		}
		
		echo "<td class=\"m_tool\">\n";
		echo "<a target=${GLOBALS['TARGET']} href=\"$reportUrl&meeting=".$row['access_id']."\">".$reportBtn."</a>\n";
//		$msg="delete \'".addslashes($row['title'])."\'";
//		$deleteUrl=$deleteMeetingUrl."&id=".$row['id'];
//		echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteUrl\">".$deleteBtn."</a>\n";

		echo "</td></tr>\n";
		$rowCount++;	

	}
}

echo "</table>";

$listPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_MEETINGS."&".SID;
//$listPage=$postPage;
if ($select!='')
	$listPage.="&select=".$select;
if ($search!='')
	$listPage.="&search=".$search;

ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
