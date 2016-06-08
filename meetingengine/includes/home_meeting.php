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


require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vsession.php");
require_once("includes/meetings_common.php");

GetArg('post_comment', $postComment);

if (GetArg('meeting_id', $meetingId)) {
	
	$meetingInfo=array();
	$meeting=new VMeeting($meetingId);
	if ($meeting->Get($meetingInfo)!=ERR_NONE) {
		ShowError($meeting->GetErrorMsg());
		return;
	}

} else if (GetArg('meeting', $accessId)) {

	$meetingInfo=array();
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $accessId, $meetingInfo);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	if (!isset($meetingInfo['id'])) {
		ShowError("Meeting id not found.");
		return;
	}

	$meeting=new VMeeting($meetingInfo['id']);
	if ($meeting->Get($meetingInfo)!=ERR_NONE) {
		ShowError($meeting->GetErrorMsg());
		return;
	}
} else {
	ShowError("The 'meeting' parameter is not set");
	return;	
}
/*
GetArg('time_zone', $tz);

if ($tz!='') {
	SetSessionValue('time_zone', $tz);

} else {
	$tz=GetSessionValue("time_zone");
}

GetSessionTimeZone($tzName, $dtz);
*/

/*
if ($meeting->GetMeetingUrl($meetingUrl)!=ERR_NONE)
	ShowError($meeting->GetErrorMsg());

$meetingUrlStr=BreakText($meetingUrl,  55);
*/
$host=new VUser($meetingInfo['host_id']);
$hostInfo=array();
$host->Get($hostInfo);
$hostName=htmlspecialchars($host->GetFullName($hostInfo));

$thisPage=$_SERVER['PHP_SELF'];
$memberId=GetSessionValue('member_id');
$memberName='';
if ($memberId!='') {
	$member=new VUser($memberId);
	$memberInfo=array();
	$member->Get($memberInfo);
	$memberName=htmlspecialchars($member->GetFullName($memberInfo));
}

/*
if (GetArg('page', $arg)) {
	$hostUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_USER."&user=".$hostInfo['access_id'];
	$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_REGISTER;
} else {
*/
	$hostUrl=$GLOBALS['BRAND_URL']."?user=".$hostInfo['access_id'];
	$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_REGISTER;
//}
if (SID!='') {
	$hostUrl.="&".SID;
	$registerPage.="&".SID;
}	
//$attendUrl="viewer.php?meeting=".$meetingInfo['access_id'];
$attendUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_JOIN."&redirect=1&meeting=".$meetingInfo['access_id'];
if (isset($_GET['pass']))
	$attendUrl.="&pass=".$_GET['pass'];

//$meeting->GetViewerUrl(false, $attendUrl);
if (SID!='')
	$attendUrl.="&".SID;

$startUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&start=1&redirect=1&meeting=".$meetingInfo['access_id'];
//$startUrl=SITE_URL."?page=".PG_MEETINGS_START."&start=1&redirect=1&meeting=".$meetingInfo['access_id']."&brand=".$GLOBALS['BRAND_NAME'];
//$startUrl="viewer.php?start=1&meeting=".$meetingInfo['access_id'];
if (SID!='')
	$startUrl.="&".SID;

$resumeUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&resume=1&redirect=1&meeting=".$meetingInfo['access_id'];
if (SID!='')
	$resumeUrl.="&".SID;
	
$downloadIcon="themes/download.gif";
$startBtn="<img src=\"$joinIcon\">&nbsp;".$gText['M_START_MEETING'];

$downloadBtn='';
$viewBtn='';
$downloadAudioUrl='';
$downloadAudioBtn='';
if ($meetingInfo['status']=='REC') {
	if ($meetingInfo['rec_ready']=='Y')
		$viewBtn="<img src=\"$joinIcon\">&nbsp;".$gText['M_CLICK_PLAY'];
		
	//if (defined('ENABLE_DOWNLOAD_RECORDING') && constant('ENABLE_DOWNLOAD_RECORDING')=="1") {
	if (defined('ENABLE_DOWNLOAD_RECORDING') && constant('ENABLE_DOWNLOAD_RECORDING')=="1" &&
		(!isset($gBrandInfo['rec_download']) || $gBrandInfo['rec_download']=='Y')) 
	{
		if ($meetingInfo['can_download_rec']=='Y' && ($meetingInfo['login_type']=='NAME' || $meetingInfo['login_type']=='NONE') ) {
			$text=_Text("Download Recording");
			$downloadBtn="<img src=\"$downloadIcon\"> $text";
			$downloadUrl=SITE_URL."api.php?cmd=DOWNLOAD_RECORDING&meeting_id=".$meetingInfo['access_id'];
		}
	}

	if (defined('ENABLE_DOWNLOAD_AUDIO') && constant('ENABLE_DOWNLOAD_AUDIO')=="1" &&
		(!isset($gBrandInfo['rec_download']) || $gBrandInfo['rec_download']=='Y')) 
	{

		if ($meetingInfo['audio']=='Y' && $meetingInfo['can_download']=='Y' && ($meetingInfo['login_type']=='NAME' || $meetingInfo['login_type']=='NONE') ) {
			$text=_Text("Download Audio");
			$downloadAudioBtn="<img src=\"$downloadIcon\"> $text";
			//$loadUrl=VMeeting::GetExportRecUrl($hostInfo, $meetingInfo, true, false);
			//$downloadAudioUrl=$loadUrl."&download=".$meetingInfo['access_id'];
			$downloadAudioUrl=SITE_URL."api.php?cmd=DOWNLOAD_RECORDING&audio=1&meeting_id=".$meetingInfo['access_id'];
		}
	}
} else if ($meetingInfo['locked']=='Y') {
	$viewBtn="";
} else {
	$viewBtn="<img src=\"$joinIcon\">&nbsp;".$gText['M_CLICK_JOIN'];
}

$title=htmlspecialchars($meetingInfo['title']);
$description=htmlspecialchars($meetingInfo['description']);
$description=str_replace("\n", "<br>", $description);

$icalUrl=VM_API."?cmd=GET_ICAL&meeting=".$meetingInfo['access_id'];
if (isset($memberId) && $memberId==$meetingInfo['host_id'])
	$icalUrl.="&host_id=".$meetingInfo['host_id'];
$icalBtn="<img src=\"$icalIcon\">&nbsp;".$gText['M_DOWNLOAD_ICAL'];

/*
$playUrl="viewer.php?meeting=".$meetingInfo['access_id']."&brand=".$GLOBALS['BRAND_NAME'];
//$startUrl="viewer.php?start=1&meeting=".$meetingInfo['access_id'];
if (SID!='')
	$playUrl.="&".SID;
	
*/

$logoFile='';
if (isset($hostInfo['logo_id']) && $hostInfo['logo_id']!='0' && !GetArg('page', $arg)) {
	$logo=new VImage($hostInfo['logo_id']);
	if ($logo->GetValue('file_name', $logoFile)!=ERR_NONE) {
		ShowError ($logo->GetErrorMsg());
	} else {
		$logoFile=VImage::GetFileUrl($logoFile);
	}
}

if ($logoFile!='') {
print <<<END
<script type="text/javascript">
<!--
	document.getElementById('logo_pict').setAttribute("src", "$logoFile");
	document.getElementById('logo_link').setAttribute("href", "javascript:void(0)");
//-->
</script>
END;
}

?>



<?php

// if this page is shown without the site tabs
if ($GLOBALS['SIDE_NAV']=='off') {
	print <<<END
<div style="float: left; width: 500px;">
END;
	
}
?>

<div class="heading1"><?php echo $title?></div>

<div class='meeting_host'>
<?php echo $gText['MD_HOSTED_BY']?>: <a target=<?php echo $GLOBALS['TARGET']?> href='<?php echo $hostUrl?>'><?php echo $hostName?></a>
</div>

<div class='meeting_desc'>
<?php echo $description?>
</div>

<?php
if ($GLOBALS['SIDE_NAV']=='off') {
	$userPict='';
	if ($hostInfo['pict_id']>0) {
		$pict=new VImage($hostInfo['pict_id']);
		if ($pict->GetValue('file_name', $pictFile)!=ERR_NONE) {
			ShowError ($pict->GetErrorMsg());
		} else {
			$userPict=VImage::GetFileUrl($pictFile);
		}
	}
	
	if ($userPict!='') {
		
		$sizeX=PICT_SIZE;
		$sizeY=PICT_SIZE;
		print <<<END
</div>
<div style="float: right; padding-top: 20px;">
<img  width:'$sizeX' height='$sizeY' src="$userPict">	
</div>
END;
	} else {
		print <<<END

</div>
END;
	}
	
}
?>

<div class="meeting_frame_top" style="clear:both">
<div class="meeting_frame_bot">
<table width='100%'>
<tr><td class="meeting_detail">
<table width='100%'>

<tr>
	<td class="m_key"><img src="<?php echo $viewIcon?>"> <?php echo $gText['MD_MEETING_ID']?>:</td>
	<td colspan="3" class="m_val" ><?php echo $meetingInfo['access_id']?>&nbsp;
<?php	
	$started=false;	
	if (VMeeting::IsMeetingStarted($meetingInfo)) {
		$started=true;
		if (VMeeting::IsMeetingInProgress($meetingInfo))								
			echo "<span class=\"progress\">${gText['M_IN_PROGRESS']}</span>\n";
		else
			echo "<span class=\"progress\">${gText['M_IDLE']}</span>\n";

		if ($meetingInfo['host_id']==$memberId) {
			echo "<span class=\"m_button\"><a target=${GLOBALS['TARGET']} href=\"$resumeUrl\">$resumeBtn</a></li>";
		}

/*		
	} else if ($meetingInfo['host_id']==$memberId && $meetingInfo['status']=='STOP') {
		echo "<span class=\"m_button_l\"><a target=${GLOBALS['TARGET']} href=\"$startUrl\">$startBtn</a></span>\n";
*/
	}
	
	// if the meeting is not in progress, the user is not the moderator of the meeting or this is a recording
	if (!$started || $memberId=='' || $meetingInfo['host_id']!=$memberId || $meetingInfo['status']=='REC') {

		$audioIcon='';
		if ($meetingInfo['status']=='REC' && $meetingInfo['audio']=='Y') {
			$audioIcon="<img style='vertical-align: text-bottom;' src=\"$speakerIcon\">";
		}
		
		if ($meetingInfo['login_type']=='REGIS') {
			$regUrl=$registerPage."&meeting=".$meetingInfo['access_id'];
			echo "<span class=\"m_button_l\"><a target=${GLOBALS['TARGET']} href=\"$regUrl\">$registerBtn</a></span>\n";
			if ($meetingInfo['status']=='REC')
				$joinText=$gText['M_CLICK_PLAY'];
			else
				$joinText=$gText['M_CLICK_JOIN'];
			
			$joinLink="<img src=\"$startIcon\">&nbsp;".$joinText;
			$regText=_Text("Registered users only");
			if ($meetingInfo['host_id']!=$memberId || $meetingInfo['status']=='REC')
				echo "<div style='margin-top:10px'><span class=\"m_button_m\"><a target=${GLOBALS['TARGET']} href=\"$attendUrl\">$joinLink</a></span> <span>($regText)</span> $audioIcon</div>\n";
		
		} else if ($meetingInfo['host_id']!=$memberId  || $meetingInfo['status']=='REC') {
			echo "<span class=\"m_button_l\"><a target=${GLOBALS['TARGET']} href=\"$attendUrl\">$viewBtn</a></span> $audioIcon\n";
		}
		
		// if the user is not logged in, give the user to option to start the meeting by logging in as the moderator
//		if ($memberId=='' && $meetingInfo['status']!='REC') {
		if ($meetingInfo['status']=='STOP') {
			$startLink="<img src=\"$startIcon\">&nbsp;".$gText['M_START_MEETING'];
			$loginText=_Text("Moderator only. Login required.");
			echo "<div style='margin-top:10px'><span class=\"m_button_m\"><a target=${GLOBALS['TARGET']} href=\"$startUrl\">$startLink</a></span> <span>($loginText)</span></div>\n";			
		}
		
		if ($meetingInfo['login_type']=='REGIS' && $meetingInfo['close_register']=='Y')
			echo "<div class=\"inform\">"._Text("The registration is closed.")."</div>\n";
		
		if ($meetingInfo['locked']=='Y')
			echo "<div class=\"inform\">"._Text("This meeting is closed.")."</div>\n";
		
		if (isset($downloadUrl) && $downloadUrl!='')
			echo "<div class=\"m_button\" style=\"padding-top: 10px;\"><a target=${GLOBALS['TARGET']} href=\"$downloadUrl\" onclick='return checkDownload()'>$downloadBtn</a></div>\n";
		
		if (isset($downloadAudioUrl) && $downloadAudioUrl!='')
			echo "<div class=\"m_button\" style=\"padding-top: 10px;\"><a target=${GLOBALS['TARGET']} href=\"$downloadAudioUrl\" onclick='return checkDownload()'>$downloadAudioBtn</a></div>\n";
	
	}
?>
	
	</td>
</tr>

<?php
$dateText=$gText['MD_DATE_TIME'].":";
$durText=$gText['MD_DURATION'].":";
$dateStr='';
$meetingTimeStr='';
$stz='';

if ($meetingInfo['scheduled']=='Y' || $meetingInfo['status']=='REC') {
	GetArg('time_zone', $tz);
	if ($tz[0]==' ')
		$tz[0]='+';
		
	$dtime=$meetingInfo['date_time'];
	//GetLocalTimeZone($tzName, $tz);
	
	$stz='';
	if ($tz!='')
		$stz=$tz;
	elseif ($hostInfo['time_zone']!='')
		$stz=$hostInfo['time_zone'];
	elseif  (isset($gBrandInfo['time_zone']))
		$stz=$gBrandInfo['time_zone'];

//	GetTimeZoneName($stz, $tzName, $dtz);	
	GetTimeZoneByDate($stz, $dtime, $tzName, $dtz);
	
	$thisPage=$_SERVER['PHP_SELF'];
	$tzPage=$thisPage."?page=".$GLOBALS['SUB_PAGE']."&meeting=".$meetingInfo['access_id'];
	$timezones=GetTimeZones($stz, "return ChangeTimeZone('time_zone', '$tzPage');");

	VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
	if ($tzTime!='') {
		list($meetingDateStr, $time24Str)=explode(" ", $tzTime);
		list($year, $mon, $day)=explode("-", $meetingDateStr);
		list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
		$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
		$meetingTimeStr=H24ToH12($meetHour, $meetMin);
		$dateStr=date('l F d, Y', $theDate);
	}
}

if ($meetingInfo['status']=='REC') {
	// recording
//	$recText=_Text("Recorded on");
	$durStr=$meetingInfo['duration'];
/*
	print <<<END
<tr>
	<td class="m_key"><img src="$schedIcon"> $dateText</td>
	<td colspan="3" class="m_val">
	<div>$recText:<br><b>$dateStr $meetingTimeStr</b><div>
	<div>$durText <b>$durStr</b> $tzName</div>
	</td>
</tr>
END;
*/
	print <<<END
<tr>
	<td class="m_key"><img src="$schedIcon"> $dateText</td>
	<td colspan="3" class="m_val">
	<div><b>$dateStr $meetingTimeStr</b><div>
	<div>$tzName <b>$durText</b> $durStr</div>
	</td>
</tr>
END;
} else if ($meetingInfo['scheduled']=='Y') {
	list($hh, $mm, $ss)=explode(":", $meetingInfo['duration']);
	$durStr="$hh:$mm";

	print <<<END
<tr>
	<td class="m_key"><img src="$schedIcon"> $dateText</td>
	<td colspan="3" class="m_val">
	<div><b>$dateStr $meetingTimeStr</b></div>
	<div>$timezones <b>$durText</b> $durStr</div>
	<div><a href="$icalUrl"?>$icalBtn</a></div>
	</td>
</tr>
END;
}

$loginType=$meetingInfo['login_type'];

if ($loginType!='NONE') {
print <<<END
<tr>
	<td class="m_key"><img src="$pwdIcon"> ${gText['MD_LOGIN']}:</td>
	<td colspan="3" class="m_val">
END;
	if ($loginType=='NAME') echo $gText['MD_NAME_TEXT'];
	else if ($loginType=='PWD') echo $gText['MD_NAMEPWD_TEXT'];
	else if ($loginType=='REGIS') {
		echo $gText['MD_REGISTRATION_TEXT'];
	
	}
print <<<END
</tr>
END;
}

if ($meetingInfo['status']!='REC' && $meetingInfo['tele_conf']=='Y') {

	$phoneText=$gText['MD_TELEPHONE'].":";
	$phoneNum=$meetingInfo['tele_num'];
	$phonePCode=$meetingInfo['tele_pcode'];
	$phoneStr=" <b>$phoneNum</b> ";
	if ($meetingInfo['tele_num2']!='') {
		$num2=$meetingInfo['tele_num2'];
		$phoneStr.=" Or <b>$num2</b><br>";
	}
	if ($phonePCode!='')
		$phoneStr.=$gText['MD_PHONE_PCODE'].": <b>".$phonePCode."</b>";
	print <<<END
<tr>
	<td class="m_key"><img src="$phoneIcon"> $phoneText</td>
	<td colspan="3" class="m_val">$phoneStr</td>
</tr>
END;
}
?>

<?php

// check if share_it is turned off; default is on
if (!isset($gBrandInfo['share_it']) || $gBrandInfo['share_it']=='Y') {

	include_once("includes/social_media.php");
	
	$shareTxt=_Text("Share It");
	// sharing of the meeting url via Facebook and Twitter
	$fbHtml=GetFacebookShareHtml($GLOBALS['BRAND_URL'], $meetingInfo, $stz, $gBrandInfo['product_name'], $hostInfo['pict_id']);
	$twHtml=GetTwitterShareHtml($GLOBALS['BRAND_URL'], $meetingInfo, $stz);
print <<<END
<tr>
	<td class="m_key"><img src="$socialIcon"> $shareTxt:</td>
	<td colspan="3" class="m_val">
	$fbHtml &nbsp;
	$twHtml
	</td>
</tr>
END;
}
?>


</table>
</td></tr>
</table>
</div>
</div>


<?php
$query="meeting_id ='".$meetingInfo['id']."' AND public='Y'";
$meetingId=$meetingInfo['id'];
$authorId=$memberId;
$hostId=$meetingInfo['host_id'];
$publicComment=$meetingInfo['public_comment'];
$meetingPage=$GLOBALS['BRAND_URL']."?meeting=".$meetingInfo['access_id'];
if (SID!='')
	$meetingPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($meetingPage);

require_once("includes/comments.php");
?>


