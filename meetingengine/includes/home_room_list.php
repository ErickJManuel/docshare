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

require_once("includes/meetings_common.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");

$tz=GetSessionValue("time_zone");

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar
$maxListChars=80; // max. number of chars to display in the meeting description box

$thisPage=$_SERVER['PHP_SELF'];	
/*	
$roomPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_ROOM;
if (SID!='')
	$roomPage.="&".SID;
	
$hostUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_USER;
if (SID!='')
	$hostUrl.="&".SID;
*/
GetArg('sort', $sort);
	
$sortOpts=array(
	"DATE" => $gText['M_DATE'],
	"HOST" => $gText['M_HOST_NAME'],
	"ROOM" => $gText['M_ROOM_NAME']
);

$showText=$gText['M_SORT_BY'];
//$postPage=$thisPage."?page=".PG_HOME_ROOMS."&brand=".$GLOBALS['BRAND_NAME'];
//if (SID!='')
//	$postPage.="&".SID;

$postPage=$GLOBALS['BRAND_URL'];

$selections="<select name=\"sort\">";
foreach ($sortOpts as $key => $value) {
	if ($sort==$key)
		$selections.="<option value=\"$key\" selected>".$value."</option>";
	else
		$selections.="<option value=\"$key\">".$value."</option>";
}

$selections.="</select>";

?>

<div class='meetings_select'>
<form target="<?php echo $GLOBALS['TARGET']?>" method="GET" action="<?php echo $postPage?>" name="selectmeeting_form">
<input type="hidden" name="page" value="HOME_ROOMS">
<?php
if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
?>
<?php echo $gText['M_SORT_BY']?>:&nbsp;<?php echo $selections?>
<input type="submit" name="submit" value="<?php echo $gText['M_GO']?>">
</form></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe" ><?php echo $gText['M_ROOM_NAME']?></th>
    <th class="pipe" ><?php echo $gText['M_DATE']?></th>
    <th class="tr" ><?php echo $gText['MD_HOSTED_BY']?></th>
</tr>
<?php

$sortExp='';
$reverse=false;
if ($sort=='' ||  $sort=='DATE') {
	$sortExp="create_date";
	$reverse=true;
} else if  ($sort=='HOST') {
	$sortExp="last_name";
} else if ($sort=='ROOM') {
	$sortExp="room_name";		
}
$query="public = 'Y' AND active='Y' AND brand_id ='".$GLOBALS['BRAND_ID']."'";

GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_USER, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
	
$errMsg=VObject::SelectAll(TB_USER, $query, $result, $offset, $count, "*", $sortExp, $reverse);
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
//		echo "<tr><td class=\"m_id\">&nbsp;</td>";
		echo "<tr>";
		echo "<td class=\"m_title\">&nbsp;</td>";
		echo "<td class=\"m_date3\">&nbsp;</td>";
		echo "<td class=\"m_but\">&nbsp;</td></tr>";
	}		
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr>\n";
		
		$desc=$row['room_description'];
		if (strlen($desc)>$maxListChars) {
			$desc=substr($desc, 0, $maxListChars-3);
			$desc.="...";
		}
		$desc=htmlspecialchars($desc);
		$title=htmlspecialchars($row['room_name']);
		$roomPage=$roomPage=$GLOBALS['BRAND_URL']."?room=".$row['access_id'];
		$titleStr="<a target=${GLOBALS['TARGET']} href=\"".$roomPage."\">".$title."</a>";			
		echo "<td class='m_info'><ul>";
		echo "<li class=\"m_title\">".$titleStr."</li>";				
		echo "<li class=\"m_desc\">".$desc."</li>";
		echo "</ul></td>\n";
		
		$dtStr=$row['create_date'];
/* FIXME: don't know why the returned localDtStr is null
		$errMsg=VObject::ConvertTZ($dtStr, 'SYSTEM', $tz, $localDtStr);
		if ($errMsg!='')
			ShowError($errMsg);
*/
		list($dayStr, $timeStr)=explode(' ', $dtStr);
		echo "<td class=\"m_date3\">".$dayStr."</td>\n";
		
		$host=new VUser($row['id']);
		$hostName=$host->GetFullName($row);
		$hostName=htmlspecialchars($hostName);
		$hostUrl=$GLOBALS['BRAND_URL']."?user=".$row['access_id'];
		
		$hostLink="<a target=${GLOBALS['TARGET']} href=\"".$hostUrl."\">".$hostName."</a>";
		
		echo "<td class=\"m_but m_but2\">$hostLink</td>\n";
		echo "</tr>\n";
	}
}

echo "</table>";

$listPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_ROOMS;
if ($sort!='')
	$listPage.="&sort=".$sort;
	
if (SID!='')
	$listPage.="&".SID;
	
ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
