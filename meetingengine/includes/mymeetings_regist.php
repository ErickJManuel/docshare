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

require_once("dbobjects/vregistration.php");
require_once("dbobjects/vregform.php");
require_once("dbobjects/vmeeting.php");
require_once("includes/meetings_common.php");

$memberId=GetSessionValue('member_id');

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar

//GetLocalTimeZone($tzName, $tz);
GetSessionTimeZone($tzName, $tz);

GetArg('meeting', $aMeetingId);	// show registration for this meeting (access_id) only

GetArg('id', $id);	// show registrations for this meeting (id) but also allows the user to select other meetings

if ($aMeetingId!='')
	$query="access_id='".$aMeetingId."'";
else
	// find all of my meetings with registration on
	$query="`host_id`='$memberId' AND `login_type`='REGIS'";
$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

$meetingIds=array();
$num_rows = mysql_num_rows($result);
$selections='<select name="page_id" onchange="gotoPage()">';
if ($aMeetingId=='')
	$selections.="<option value=\"\">[".$gText['M_ALL_MEETINGS']."]</option>";		

while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	$title=$row['title'];
	if (strlen($title)>30)
		$title=substr($title, 0, 30);
	$title=htmlspecialchars($title);
	
	$meetingIds[]=$row['id'];

	if ($row['id']==$id) {
		$selected='selected';
	} else {
		$selected='';
	}
	$selections.="<option $selected value='".$row['id']."'>".$row['access_id'].":".$title."</option>\n";		

}
$selections.="</select>";

$query='';

if ($aMeetingId!='') {
	$query="meeting_id='".$meetingIds[0]."'";	
} elseif ($id!='') {
	$query="meeting_id='$id'";	
} else {
	// select all meetings of mine
	$select='';
	foreach ($meetingIds as $value) {
		if ($select!='')
			$select.=" OR ";
		$select.="meeting_id='$value'";	
	}
	
	if ($select!='')
		$query=$select;
	else
		$query='0';
	
}

SetSessionValue("member_query", $query);
$exportUrl=VM_API."?cmd=GET_REGISTRATIONS";
$exportUrl.="&time_zone=$tz";
//$exportUrl.="&query=".rawurlencode($query);
$exportIcon="themes/export.gif";

$thisPage=$_SERVER['PHP_SELF']."?page=".PG_MEETINGS_REGIST;
if (SID!='')
	$thisPage.="&".SID;

$retPage=$thisPage;
$retPage=VWebServer::EncodeDelimiter1($retPage);

$deleteUrl=VM_API."?cmd=DELETE_REGISTRATION&return=$retPage";
if (SID!='')
	$deleteUrl.="&".SID;

$emailIcon="themes/invite.gif";
/*
$customizeUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REGNOTIFY;
if (SID!='')
	$customizeUrl.="&".SID;
	
$text=_Text("Customize Notification");	
$customizeBtn="<a target=${GLOBALS['TARGET']} href=\"$customizeUrl\"><img src=\"$emailIcon\"> $text</a>";
*/

?>

<script type="text/javascript">
<!--
	function gotoPage() {
		var pageId=document.selectmeeting_form.page_id.value;
		window.location="<?php echo $thisPage?>"+"&id="+pageId;
	}
	function confirmSend() {
		var pageId=document.selectmeeting_form.page_id.value;
		if (pageId==0 || !pageId) {
			alert("Please select a meeting first.");
			return false;
		} else {
			var index=document.selectmeeting_form.page_id.selectedIndex;
			var label=document.selectmeeting_form.page_id.options[index].text;
			var ok=confirm("Do you want to send meeting information to all registered users of '"+label+"'?");
			if (ok)
				return true;

			return false;
			
		}
	
	}
//-->
</script>

<table class='report_bar'>
<tr>
<td class="meetings_select">
<form name="selectmeeting_form">
<?php
/*
if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
*/

?>
<span id="select_show"><?php echo $gText['M_MEETING']?>:
<?php echo $selections?></span>
</form>
</td>
<td class='report_right'>
<!--
<a href='' onclick='return confirmSend()'><img src="<?php echo $inviteIcon?>"> <?php echo _Text("Email")?></a> &nbsp; 
-->
<a href='<?php echo $exportUrl?>'><img src="<?php echo $exportIcon?>"><?php echo _Text("Export")?></a>
</td>
</tr>
</table>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">&nbsp;</th>
<?php
if ($aMeetingId=='' && $id=='') {
	$text=_Text("Meeting");
	print <<<END
    <th class="pipe">$text</th>
END;
}
?>
    <th class="pipe"><?php echo _Text("Date")?></th>
    <th class="pipe"><?php echo $gText['M_EMAIL']?></th>
    <th class="pipe"><?php echo $gText['M_FULL_NAME']?></th>
    <th class="pipe">&nbsp;</th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

$max=VRegForm::$maxFields;

GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_REGISTRATION, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
	
$errMsg=VObject::SelectAll(TB_REGISTRATION, $query, $result, $offset, $count, "*", "date_time", true);
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);	
	$rowCount=0;
	$lastDate='';
	$formInfo=null;
	$lastMeetingId=0;
	$meetingAccessId='';
	$meetingLink='';
	$meetingPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_MEETING;

	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($rowCount % 2)
			echo "<tr class=\"u_bg\">\n";
		else
			echo "<tr>\n";
		
		$index=$rowCount+$offset+1;
		echo "<td class=\"u_item\">".$index."</td>\n";
		// only show the meeting id when listing registrations for all meetings
		if ($aMeetingId=='' && $id=='') {
			if ($row['meeting_id']!=$lastMeetingId) {
				$meeting=new VMeeting($row['meeting_id']);
				$meeting->Get($meetingInfo);
				$meetingAccessId=$meetingInfo['access_id'];
				$meetingLink=$meetingPage."&meeting=".$meetingAccessId;
				$lastMeetingId=$row['meeting_id'];
			}
				
			$idStr="<a target=${GLOBALS['TARGET']} href=\"".$meetingLink."\">".$meetingAccessId."</a>";			
			echo "<td class=\"u_item\">".$idStr."</td>\n";
		}
		if (!isset($row['regform_id']) || !isset($formInfo['id']) || $formInfo['id']!=$row['regform_id']) {
			$formInfo=array();
			if (!isset($row['regform_id']) || $row['regform_id']=='0') 
				VRegForm::GetDefault($formInfo);
			else {
				$regForm=new VRegForm($row['regform_id']);
				$regForm->Get($formInfo);
			}
		}	
			
		$dtime=$row['date_time'];
		VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $tzTime);
		if ($tzTime!='') {
			list($dateStr, $time)=explode(" ", $tzTime);
			
			if ($dateStr!=$lastDate) {
				$lastDate=$dateStr;
			} else {
				$dateStr='&nbsp;';
			}
		} else {
			$dateStr='&nbsp;';
			$timeStr='&nbsp;';			
		}
				
		echo "<td class=\"u_item\">".$dateStr."</td>\n";
		$email=$row['email'];

		$email=htmlspecialchars($email);
		echo "<td class='u_item'>".$email."</td>";
		$name=$row['name'];
		$name=htmlspecialchars($name);
		echo "<td class='u_item'>".$name."</td>";
		
		$fieldText='';
		for ($i=1; $i<=$max; $i++) {
			$key="key_".$i;
			$field="field_".$i;
			if (isset($formInfo[$key])) {
				$keyVal=$formInfo[$key];
				$valItems=explode("=", $keyVal);
				$keyVal=$valItems[0];
			} else {
				$keyVal=$field;
			}
			
			
			if ($keyVal!='[EMAIL]' && $keyVal!='[FULLNAME]' && $keyVal!='[FIRSTNAME]' && $keyVal!='[LASTNAME]' && $row[$field]!='') {
				$fieldText.=$keyVal."=";
				$fieldText.=htmlspecialchars($row[$field]);
				$fieldText.='<br>';
			}
		}
		if ($fieldText=='')
			$fieldText='&nbsp;';

		echo "<td class='u_item'>$fieldText</td>\n";
		
	
		$format=$gText['M_CONFIRM_DELETE'];
		$msg=sprintf($format, "\'".addslashes($row['email'])."\'");		

		$actionIcons="<a onclick=\"return MyConfirm('".$msg."')\" target=${GLOBALS['TARGET']} href=\"$deleteUrl&id=".$row['id']."\">$deleteBtn</a>\n";

		echo "<td class=\"m_tool\">".$actionIcons."</td>\n";			


		echo "</tr>\n";
		$rowCount++;	

	}
}

echo "</table>";

$listPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REGIST;
if ($id!='')
	$listPage.="&id=".$id;
//if ($search!='')
//	$listPage.="&search=".$search;
if (SID!='')
	$listPage.="&".SID;
ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
