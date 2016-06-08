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
require_once("dbobjects/vobject.php");
require_once("dbobjects/vmeeting.php");


$memberId=GetSessionValue('member_id');
$userId=$memberId;
$user=new VUSer($userId);
	
$maxListChars=100;

$tz=GetSessionValue("time_zone");

GetArg('set_tz', $setTz);
if ($setTz==1) {
	$user->GetValue('time_zone', $tz);
	if ($tz!='')
		SetSessionValue('time_zone', $tz);	
}


/*
GetArg('time_zone', $tz);

if ($tz!='') {
	SetSessionValue('time_zone', $tz);
} else {
	$tz=GetSessionValue("time_zone");
}
*/
GetSessionTimeZone($tzName, $dtz);

$thisPage=$_SERVER['PHP_SELF'];
$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_LIST;
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

$deleteUrl=VM_API."?cmd=DELETE_MEETING&return=$retPage";
if (SID!='')
	$deleteUrl.="&".SID;

$editPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_DETAIL;
if (SID!='')
	$editPage.="&".SID;

$regUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REGIST;
if (SID!='')
	$regUrl.="&".SID;
	
$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_LIST;
//$retPage=$thisPage."?page=".PG_MEETINGS;
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);

$endUrl=VM_API."?cmd=END_MEETING&return=$retPage";
if (SID!='')
	$endUrl.="&".SID;
	
$endRecUrl=VM_API."?cmd=END_RECORDING&return=$retPage";
if (SID!='')
	$endRecUrl.="&".SID;

$resumeUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&resume=1&redirect=1";
//$resumeUrl="viewer.php?resume=1";
if (SID!='')
	$resumeUrl.="&".SID;

$startUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&start=1&redirect=1";
//$startUrl=SITE_URL."?page=".PG_MEETINGS_START."&start=1&brand=".$GLOBALS['BRAND_NAME'];
//$startUrl="viewer.php?start=1";
if (SID!='')
	$startUrl.="&".SID;

$addMeetingIcon="themes/add.gif";
$addMeetingUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_ADD;
if (SID!='')
	$addMeetingUrl.="&".SID;

$addMeetingBtn="<a target=${GLOBALS['TARGET']} href=\"$addMeetingUrl\"><img src=\"$addMeetingIcon\"> ${gText['M_ADD_MEETING']}</a>";

$invitePage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_INVITE;
if (SID!='')
	$invitePage.="&".SID;
	
$downloadIcon="themes/download.gif";
$downloadUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_DOWNLOAD;
if (SID!='')
	$downloadUrl.="&".SID;

$downloadBtn="<a target=${GLOBALS['TARGET']} href=\"$downloadUrl\"><img src=\"$downloadIcon\"> ${gText['M_DOWNLOAD_PRESENTER']}</a>";

$reportIcon="themes/preview.gif";
$reportBtn="<img src=\"$reportIcon\">".$gText['M_REPORTS'];
$reportUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REPORT;
if (SID!='')
	$reportUrl.="&".SID;
	
$timezones=GetTimeZones($tz);

$retPage=$thisPage."?page=".PG_MEETINGS_LIST."&set_tz=1";
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
$postPage=VM_API."?cmd=SET_USER&user_id=".$memberId."&return=$retPage";
if (SID!='')
	$postPage.="&".SID;
	
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

$licenseId=$userInfo['license_id'];

$license=new VLicense($licenseId);
$licInfo=array();
if ($license->Get($licInfo)!=ERR_NONE) {
	ShowError("License not found");
	return;
}

$onStartClick='';
if ($licInfo['trial']=='Y') {
	$num=$licInfo['max_att'];
	$format=_Text("Your account allows you to have %s participants.");
	$startMessage=sprintf($format, $num);

	$onStartClick="onclick=\"alert('$startMessage'); return true;\"";
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

?>


<script type="text/javascript">
<!--
function ChangeTimeZone(url)
{
	var tz=document.getElementById('time_zone');

	window.location=url+"&time_zone="+tz.value;		

	return true;
}

//-->
</script>

<?php
if (SID!='') {
	$url=$GLOBALS['BRAND_URL']."?page=".PG_HOME_COOKIES;
	if (SID!='')
		$url.="&".SID;
	print <<<END
    <div class='error'>Make sure you have cookies enabled in your browser before starting a meeting.<br>
	<a target="${GLOBALS['TARGET']}" href="$url">- Show me how to enable cookies.</a></div>
END;
}
?>

<div class="list_tools">
<span class="list_item"><?php echo $addMeetingBtn?></span>

<?php
/*
$hasWindowsClient=true;

if ((defined('ENABLE_WINDOWS_CLIENT') && constant('ENABLE_WINDOWS_CLIENT')=='0'))
{
	$hasWindowsClient=false;
} else {	
	require_once("dbobjects/vviewer.php");
	
	$brandViewer=new VViewer($gBrandInfo['viewer_id']);
	$brandViewerInfo=array();
	$brandViewer->Get($brandViewerInfo);
	
	if (isset($brandViewerInfo['presenter_client']) && $brandViewerInfo['presenter_client']=='JAVA')
		$hasWindowsClient=false;

}
*/
//if ($hasWindowsClient) {

//<span class="list_item">$downloadBtn</span>

//}
?>
</div>

<?php

$dtStr=date('Y-m-d H:i:s');
VObject::ConvertTZ($dtStr, 'SYSTEM', $dtz, $localDtStr);
$changeText=_Text("Change");

for ($i=0; $i<2; $i++) {
	if ($i==0) {
		$tbHeading=$gText['M_TODAY_MEETING'];
	} else if ($i==1) {
		$tbHeading=$gText['M_MEETINGS'];		
	}

	if ($i==0) {

print <<<END
	<form method="POST" action="$postPage" name="tz_form">
	<span class=heading2>$tbHeading</span>&nbsp; $localDtStr &nbsp;
	<span class='meetings_tz1'>$timezones</span>
	<input type="submit" name="submit_tz" value="$changeText">
	<span class='m_caption'>${gText['M_SET_TZ']}</span>
	</form>	
END;
	} else {
		echo "<div class=heading2>$tbHeading</div>\n";	
	}
	
	$idStr=$gText['M_ID'];
	$titleStr=$gText['M_TITLE'];
	$dtStr=$gText['M_DATE']."/".$gText['M_TIME'];

print <<<END
	<table cellspacing="0" class="meeting_list" >
	<tr>
		<th class="tl pipe" style="width:30px">${gText['M_ID']}</th>
		<th class="pipe" >${gText['M_TITLE']}</th>
		<th class="pipe" style="width:80px">${gText['M_DATE']}</th>
		<th class="pipe" style="width:70px">&nbsp;</th>
		<th class="tr" style="width:130px">&nbsp;</th>
	</tr>
END;

	if ($i==0) {
		$query="host_id = '$userId' AND status<>'REC' AND (scheduled='N' OR CURDATE()=DATE(date_time)) AND brand_id ='".$GLOBALS['BRAND_ID']."'";
	} else if ($i==1) {
		$query="host_id = '$userId' AND status<>'REC' AND scheduled='Y' AND CURDATE()<>DATE(date_time) AND brand_id ='".$GLOBALS['BRAND_ID']."'";	
	} else {
		$query="host_id = '$userId' AND status = 'REC' AND brand_id ='".$GLOBALS['BRAND_ID']."'";
	}
	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, 0, 100, "*", "date_time", true);
	if ($errMsg!='') {
		ShowError($errMsg);
	} else {
		$num_rows = mysql_num_rows($result);
		if ($num_rows==0) {
			echo "<tr><td class=\"m_id\">&nbsp;</td>\n";
			echo "<td class=\"m_info\">&nbsp;</td>\n";
			echo "<td class=\"m_date\">&nbsp;</td>\n";
			echo "<td class=\"m_tool\">&nbsp;</td>\n";
			echo "<td class=\"m_but\">&nbsp;</td></tr>\n";
		}		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "<tr>\n";
			
			echo "<td class=\"m_id\">".$row['access_id']."</td>\n";
			$desc=$row['description'];
			if (strlen($desc)>45) {
				$desc=substr($desc, 0, 45-3);
				$desc.="...";
			}
			$desc=htmlspecialchars($desc);
			
			$title=$row['title'];
			if (strlen($title)>35) {
				$title=substr($title, 0, 35-3);
				$title.="...";
			}
			$title=htmlspecialchars($title);
			
			if ($title=='')
				$title='[empty]';

//			$titleStr="<a target=${GLOBALS['TARGET']} href=\"".$meetingPage."&meeting=".$row['access_id']."\">".$row['title']."</a>";			
			$titleStr="<a target=${GLOBALS['TARGET']} href=\"".$editPage."&meeting=".$row['access_id']."\">".$title."</a>";			
			echo "<td class=\"m_info\"><ul>\n";
			echo "<li class=\"m_title\">".$titleStr."</li>\n";
			
			if ($desc=='')
				$desc='&nbsp;';
			echo "<li class=\"m_desc\">".$desc."</li>\n";
			
			$icons=GetMeetingIcons($row);
			if ($icons=='')
				$icons='&nbsp;';

			if ($icons!='')
				echo "<li class=\"m_icon\">".$icons."</li>\n";

			echo "</ul></td>\n";
			
			$timeStr='&nbsp;';
			if ($row['scheduled']=='Y' || $row['status']=='REC') {
				$dtime=$row['date_time'];
				GetTimeZoneByDate($tz, $dtime, $tzName, $dtz);
				VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
				if ($tzTime!='') {
					list($date, $time)=explode(" ", $tzTime);
					if ($i!=0) {
						$timeStr="<li><b>".$date."</b></li>";
					} else
						$timeStr='';
					list($hh, $mm, $ss)=explode(":", $time);

					$timeStr.="<li>".H24ToH12($hh, $mm)."</li>";

					$timeStr.="<li><i>".$row['duration']."</i></li>";
				}
			}
			
			$btn='';
			$progStr='';
			if (VMeeting::IsMeetingStarted($row)) {				
require_once("dbobjects/vsession.php");
				if (VMeeting::IsMeetingInProgress($row))
					$progStr="<li class=\"progress\">${gText['M_IN_PROGRESS']}</li>";
				else
					$progStr="<li class=\"progress\">${gText['M_IDLE']}</li>";
					
//				$resumeMeetUrl=$resumeUrl."&meeting=".$row['access_id']."&title=".rawurlencode($row['title']);
				$resumeMeetUrl=$resumeUrl."&meeting=".$row['access_id'];
				$btn="<li><a target=${GLOBALS['TARGET']} href=\"$resumeMeetUrl\">$resumeBtn</a></li>";
				if ($row['status']=='START_REC')
					$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$endRecUrl&meeting=".$row['access_id']."\">$endRecBtn</a></li>";
				else
					$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$endUrl&meeting=".$row['access_id']."\">$endBtn</a></li>";
			} else if ($row['status']=='STOP' || $row['status']=='') {
//				$startMeetingUrl=$startUrl."&meeting=".$row['access_id']."&title=".rawurlencode($row['title']);
				$startMeetingUrl=$startUrl."&meeting=".$row['access_id'];
				$btn="<li><a $onStartClick target=${GLOBALS['TARGET']} href=\"$startMeetingUrl\">$startBtn</a></li>";
			}
			echo "<td class=\"m_date\"><ul>".$timeStr.$progStr."</ul></td>\n";
			

			$actionIcons='';
			$actionIcons.="<a target=${GLOBALS['TARGET']} href=\"$invitePage&meeting=".$row['access_id']."\">$inviteBtn</a>\n";
//			if ($row['login_type']=='REGIS')
//				$actionIcons.="<a target=${GLOBALS['TARGET']} href=\"$regUrl&id=".$row['id']."\">$regBtn</a>\n";
			$actionIcons.="<br><a target=${GLOBALS['TARGET']} href=\"$reportUrl&meeting=".$row['access_id']."\">".$reportBtn."</a>\n";
			
			$format=$gText['M_CONFIRM_DELETE'];
			$msg=sprintf($format, "\'".addslashes($row['title'])."\'");
			
			// disable delete when the meeting is in progress
			if ($progStr=='')
				$actionIcons.="<br><a onclick=\"return MyConfirm('".$msg."')\" target=${GLOBALS['TARGET']} href=\"$deleteUrl&id=".$row['id']."\">$deleteBtn</a>\n";

			echo "<td class=\"m_tool\">".$actionIcons."</td>\n";			
			echo "<td class=\"m_but\"><ul>$btn</ul></td>\n";
			echo "</tr>\n";
		}
	}

	echo "</table>\n";

}


?>
<div class="m_caption"><b>Firefox only:</b> Do not open multiple meeting viewers on the same computer. The performance will degrade dramatically.</div>
