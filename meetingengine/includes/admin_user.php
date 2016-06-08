<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vlicense.php");

$exportIcon="themes/export.gif";

GetSessionTimeZone($tzName, $tz);

// CONVERT_TZ MySQL function crashes on MySQL 4.1.9 when used in WHREE
// it works on MySQL 5.0.37
// not sure what version is needed so set it to 5.0.0 for now
if (phpversion()<'5.0.0')
	$convertTz=false;
else
	$convertTz=true;
	
$systemDt=date("Y-m-d H:i:s");
//$tz=date('T');
VObject::ConvertTZ($systemDt, 'SYSTEM', $tz, $localDt);
$today=$timeStr='';
if ($localDt!='')
	list($today, $timeStr)=explode(" ", $localDt);

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar
$maxListChars=80; // max. number of chars to display in the meeting description box

//$thisPage='admin.php';
$thisPage=$_SERVER['PHP_SELF'];

$emailIcon="themes/invite.gif";
$emailBtn="<img src=\"$emailIcon\">&nbsp;".$gText['M_EMAIL'];

$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$addUserIcon="themes/add.gif";
//$addUserUrl=$thisPage."?page=".PG_ADMIN_ADD_USER."&brand=".$GLOBALS['BRAND_NAME'];
$addUserUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_USER;
if (SID!='') $addUserUrl.="&".SID;
$addUserBtn="<a target=\"${GLOBALS['TARGET']}\" href=\"$addUserUrl\"><img src=\"$addUserIcon\"> ${gText['M_ADD_MEMBER']}</a>";
//$editUserUrl=$thisPage."?page=".PG_ADMIN_EDIT_USER."&brand=".$GLOBALS['BRAND_NAME'];
$editUserUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_USER;
if (SID!='') $editUserUrl.="&".SID;

//$editGroupUrl=$thisPage."?page=".PG_ADMIN_EDIT_GROUP."&brand=".$GLOBALS['BRAND_NAME'];
$editGroupUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_GROUP;
if (SID!='') $editGroupUrl.="&".SID;

$reportIcon="themes/preview.gif";
$reportBtn="<img src=\"$reportIcon\">".$gText['M_REPORTS'];
$reportUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
if (SID!='') $reportUrl.="&".SID;

$backPage=$thisPage."?brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL']."&page=".PG_ADMIN_USERS;
if (SID!='')
	$backPage.="&".SID;
$backPage=VWebServer::EncodeDelimiter2($backPage);

$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$backPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
/*
$retPage=$thisPage."?brand=".$GLOBALS['BRAND_NAME']."&page=".PG_ADMIN_USERS;
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);
*/

$deleteUserUrl=VM_API."?cmd=DELETE_USER&return=".$retPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUserUrl.="&".SID;

//$usersPage=$thisPage."?page=".PG_ADMIN_USERS."&brand=".$GLOBALS['BRAND_NAME'];
$usersPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_USERS;
if (SID!='')
	$usersPage.="&".SID;
	
$emailPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_SEND;
if (SID!='')
	$emailPage.="&".SID;
	
$showText=$gText['M_SHOW'];
//$postPage=$thisPage."?page=".PG_ADMIN_USERS."&brand=".$GLOBALS['BRAND_NAME'];
//if (SID!='')
//	$postPage.="&".SID;
$postPage=$GLOBALS['BRAND_URL'];

GetArg('member_type', $memberType);
GetArg('group_id', $groupId);
GetArg('license_id', $licenseId);
GetArg('search', $search);
GetArg("sortby", $sortBy);
if ($sortBy=='')
	$sortBy="create_date";

$brandId=$GLOBALS['BRAND_ID'];
$query="`brand_id` = '$brandId'";
$prepend="<option value=\"\">".$gText['M_ALL_GROUPS']."</option>\n";
$groups=VObject::GetFormOptions(TB_GROUP, $query, "group_id", "name", $groupId, $prepend);

$offerings=explode(',', $gBrandInfo['offerings']);
$query="";
foreach ($offerings as $v) {
	$items=explode(':', $v);
	if ($query!='')
		$query.=" OR ";
	$query.="`code` = '${items[0]}'";
}
if ($query=='')
	$query='0';

$prepend="<option value=\"\">".$gText['M_ALL_ACCOUNTS']."</option>\n";
if ($licenseId=='non_trial')
	$selected1='selected';
else
	$selected1='';
$prepend.="<option $selected1 value=\"non_trial\">".$gText['M_NON_TRIALS']."</option>\n";
/*
$trialLicId=$gBrandInfo['trial_license_id'];
if ($trialLicId>0) {
	$trialLic=new VLicense($trialLicId);
	$trialLic->GetValue('name', $trialLicName);
	if ($licenseId==$trialLicId)
		$selected='selected';
	else
		$selected='';
	$prepend.="\n<option $selected value=\"$trialLicId\">".$trialLicName."</option>\n";
}
*/
$trialLicId=1; // assume the first license is the trial license
$licenses=VObject::GetFormOptions(TB_LICENSE, $query, "license_id", "name", $licenseId, $prepend);

GetArg('month', $month);
GetArg('date', $date);

$selectMonth= ($month=='')?$date:$month;
$monthOpts=Get12Months($selectMonth);

$checkActive='';
$checkInactive='';
$checkAdmin='';
if ($memberType=='ACTIVE')
	$checkActive='selected';
elseif ($memberType=='INACTIVE')
	$checkInactive='selected';
elseif ($memberType=='ADMIN')
	$checkAdmin='selected';
$memberOpts='<select name="member_type">\n';
$memberOpts.="<option value=\"\">".$gText['M_ALL_MEMBERS']."</option>\n";
$memberOpts.="<option $checkActive value=\"ACTIVE\">".$gText['M_ACTIVE_MEMBERS']."</option>\n";
$memberOpts.="<option $checkInactive value=\"INACTIVE\">".$gText['M_INACTIVE_MEMBERS']."</option>\n";
$memberOpts.="<option $checkAdmin value=\"ADMIN\">".$gText['M_ADMIN_MEMBERS']."</option>\n";
$memberOpts.="</select>\n";

$query='1';
if ($search!='') {
	$dbsearch=VObject::MyAddSlashes($search);
	$query="(".
		"(first_name LIKE '%$dbsearch%') OR (last_name LIKE '%$dbsearch%')".
		" OR (CONCAT(first_name, ' ', last_name) LIKE '%$dbsearch%')".
		" OR (title LIKE '%$dbsearch%') OR (org LIKE '%$dbsearch%')".
		" OR (street LIKE '%$dbsearch%') OR (city LIKE '%$dbsearch%')".
		" OR (country LIKE '%$dbsearch%') OR (zip LIKE '%$dbsearch%')".
		" OR (phone LIKE '%$dbsearch%') OR (email LIKE '%$dbsearch%')".
		" OR (login LIKE '%$dbsearch%')".
		")";
} else {
	
	if ($memberType=='ACTIVE')
		$query.=" AND (active='Y')";
	elseif ($memberType=='INACTIVE')
		$query.=" AND (active='N')";
	elseif ($memberType=='ADMIN')
		$query.=" AND (permission='ADMIN')";
	
	if ($groupId!='')
		$query.=" AND (group_id='".VObject::MyAddSlashes($groupId)."')";
	
	if ($licenseId=='non_trial')
		$query.=" AND (license_id<>'".VObject::MyAddSlashes($trialLicId)."')";
	elseif  ($licenseId!='')
		$query.=" AND (license_id='".VObject::MyAddSlashes($licenseId)."')";
	
	if ($date=='TODAY') {
		
		if ($convertTz)
			$query.=" AND (DATE(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='".VObject::MyAddSlashes($today)."')";		
		else
			$query.=" AND (DATE(create_date)='".VObject::MyAddSlashes($today)."')";	
	
	} elseif ($date!='') {
		
		$dateItems=explode("-", $date);
		$dtCount=count($dateItems);
		
		// month specified
		if ($dtCount==2) {
			$year=$dateItems[0];
			$mn=$dateItems[1];
			if ($convertTz)		
				$query.=" AND (YEAR(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$year' AND MONTH(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='$mn')";
			else
				$query.=" AND (YEAR(create_date)='$year' AND MONTH(create_date)='$mn')";
						
			// date specified
		} else if ($dtCount==3) {
			if ($convertTz)
				$query.=" AND (DATE(CONVERT_TZ(create_date, 'SYSTEM', '$tz'))='".VObject::MyAddSlashes($date)."')";	
			else
				$query.=" AND (DATE(create_date)='".VObject::MyAddSlashes($date)."')";	
		}
	}
}

$query.=" AND (brand_id='".$GLOBALS['BRAND_ID']."')";

SetSessionValue("member_query", $query);
$exportUrl=VM_API."?cmd=GET_USER_LIST&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if ($sortBy!='')
	$exportUrl.="&sortby=$sortBy";

?>


<form target="<?php echo $GLOBALS['TARGET']?>" method="GET" action="<?php echo $postPage?>" name="selectmeeting_form">
<input type="hidden" name="page" value="ADMIN_USERS">
<?php
if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
?>
<div class='meetings_select'>
<div id='select_show'><?php echo $gText['M_SHOW']?>:
<?php echo $memberOpts?>&nbsp;
<?php echo $groups?>&nbsp;
<?php echo $licenses?>&nbsp;
<?php echo $monthOpts?>&nbsp;
<input type="submit" name="submit" value="<?php echo $gText['M_GO']?>">
</div>
</form>
<form target=<?php echo $GLOBALS['TARGET']?> method="GET" action="<?php echo $postPage?>" name="selectmeeting_form">
<input type="hidden" name="page" value="ADMIN_USERS">
<?php
if (SID!='') {
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
?>
<div id='select_search2'>
<input type="text" name="search" size="25" value="<?php echo $search?>">
<input type="submit" name="submit" value="<?php echo $gText['M_SEARCH']?>">
</div>
</div>
</form>

<table class='report_bar'>
<tr>
<td class="list_tools"><?php echo $addUserBtn?></td>
<td class='report_right'><a href='<?php echo $exportUrl?>'><img src="<?php echo $exportIcon?>">Export Members</a></td>
</tr>
</table>

<?php


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

$reverseSort=false;
if ($sortBy=='create_date')
	$reverseSort=true;

$errMsg=VObject::SelectAll(TB_USER, $query, $result, $offset, $count, "*", $sortBy, $reverseSort);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
} 
?>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe"><?php echo $gText['M_FULL_NAME']?></th>
    <th class="pipe"><?php echo $gText['M_GROUP']?></th>
    <th class="pipe"><?php echo $gText['M_ACCOUNT']?></th>
    <th class="pipe"><?php echo $gText['M_DATE']?></th>
<!--    <th class="pipe"><?php echo _Text("Active")?></th> -->
    <th class="tr">&nbsp;</th>
</tr>
<?php

$num_rows = mysql_num_rows($result);
if ($num_rows==0) {
	echo "<tr>";
	echo "<td colspan=7>&nbsp;</td>";
	echo "</tr>";
}
$rowCount=0;
$target=$GLOBALS['TARGET'];

$gid=0;
$lid=0;
$groupName='';
$licenseName='';
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if ($row['login']==ROOT_USER)
		continue;
		
	if ($rowCount % 2)
		echo "<tr class=\"u_bg\">\n";
	else
		echo "<tr>\n";
	
	$userUrl=$editUserUrl."&user=".$row['access_id'];
	echo "<td class=\"u_item\"><a target='$target' href=\"$userUrl\">".$row['access_id']."</a></td>\n";
	$name=$row['first_name']." ".$row['last_name'];
	if (strlen($name)>17) {
		$name=substr($name, 0, 17-2);
		$name.="...";
	}

	$name=htmlspecialchars($name);

	echo "<td class=\"u_item\"><a target='$target' href=\"$userUrl\">".$name."</a></td>\n";
	
	if ($row['group_id']!=$gid) {
		$group=new VGroup($row['group_id']);
		$group->GetValue('name', $groupName);
		
		if (!isset($groupName))
			$groupName='&nbsp;';
		if (strlen($groupName)>11) {
			$groupName=substr($groupName, 0, 11-2);
			$groupName.="...";
		}
		
		$groupName=htmlspecialchars($groupName);
		if ($groupName=='')
			$groupName='&nbsp;';
			
		$gid=$row['group_id'];
	}
	
	if ($row['license_id']!=$lid) {
		$license=new VLicense($row['license_id']);
		$license->GetValue('name', $licenseName);
		
		if (strlen($licenseName)>12)
			$licenseName=substr($licenseName, 0, 10);
		$licenseName=htmlspecialchars($licenseName);
		if ($licenseName=='')
			$licenseName='&nbsp;';
			
		$lid=$row['license_id'];
	}

	$groupUrl=$editGroupUrl."&id=".$row['group_id'];
		
	$dtStr=$row['create_date'];
/* FIXME: don't know why the returned localDtStr is null
		$errMsg=VObject::ConvertTZ($dtStr, 'SYSTEM', $tz, $localDtStr);
		if ($errMsg!='')
			ShowError($errMsg);
	list($dateStr, $timeStr)=explode(' ', $localDtStr);
*/	
	list($dateStr, $timeStr)=explode(' ', $dtStr);


	$deleteUrl=$deleteUserUrl."&user_id=".$row['id'];
	if (!isset($GLOBALS['LIMITED_ADMIN']))
		echo "<td class=\"u_item_c\"><a target='$target' href=\"$groupUrl\">".$groupName."</a></td>\n";
	else
		echo "<td class=\"u_item_c\">".$groupName."</td>\n";
	
	echo "<td class=\"u_item_c\">".$licenseName."</td>\n";
	echo "<td class=\"u_item_c\">".$dateStr."</td>\n";
//	echo "<td class=\"u_item_c\">".$row['active']."</td>\n";
	echo "<td class=\"m_tool\">\n";
	
	$format=$gText['M_CONFIRM_DELETE'];
	$msg=sprintf($format, "\'".addslashes($name)."\'");
	
//	$msg="delete '$name' and all this user's meetings";
//	$msg=addslashes($msg);
	echo "<a target=${GLOBALS['TARGET']} href=\"$reportUrl&user=".rawurlencode($row['login'])."\">".$reportBtn."</a>\n";
	echo "<a target=${GLOBALS['TARGET']} href=\"$emailPage&user_id=".$row['id']."\">".$emailBtn."</a>\n";
	if (GetSessionValue('member_id')!=$row['id'] && $row['login']!=VUSER_GUEST)
		echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteUrl\">".$deleteBtn."</a>\n";
	
	echo "</td></tr>\n";
	
	$rowCount++;
}

echo "</table>";

$listPage=$usersPage;

if ($memberType!='')
	$listPage.="&member_type=".$memberType;
if ($groupId!='')
	$listPage.="&group_id=".$groupId;
if ($licenseId!='')
	$listPage.="&license_id=".$licenseId;
if ($search!='')
	$listPage.="&search=".$search;
if ($date!='')
	$listPage.="&date=".$date;
if ($month!='')
	$listPage.="&month=".$month;

ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
