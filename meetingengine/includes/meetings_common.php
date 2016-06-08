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


$themeDir=SITE_URL."themes/";
$homeIcon=$themeDir."home.gif";
$inviteIcon=$themeDir."invite.gif";
$deleteIcon=$themeDir."delete.gif";
$viewIcon=$themeDir."view_page.gif";
$startIcon=$themeDir."start_meeting.gif";
$joinIcon=$themeDir."join_meeting.gif";
$resumeIcon=$themeDir."resume.gif";
$endIcon=$themeDir."end_meeting.gif";
$playIcon=$themeDir."playback.gif";
$schedIcon=$themeDir."schedule.gif";
$pwdIcon=$themeDir."password.gif";
$phoneIcon=$themeDir."phone.gif";
$speakerIcon=$themeDir."speaker.gif";
$regIcon=$themeDir."register.gif";
$editIcon=$themeDir."edit.gif";
$commentIcon=$themeDir."comment.gif";
$endRecIcon=$themeDir."end_rec.gif";
$icalIcon=$themeDir."vcalendar.gif";
$twitterIcon=$themeDir."twitter_icon.png";
$facebookIcon=$themeDir."facebook_icon.png";
$socialIcon=$themeDir."people_icon.gif";

$startBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_START_MEETING'];
$endBtn="<img src=\"$endIcon\">&nbsp;".$gText['M_END_MEETING'];
$resumeBtn="<img src=\"$resumeIcon\">&nbsp;".$gText['M_RESUME'];
$playBtn="<img src=\"$playIcon\">&nbsp;".$gText['M_PLAYBACK'];
$registerBtn="<img src=\"$regIcon\">&nbsp;".$gText['M_REGISTER'];
$inviteBtn="<img src=\"$inviteIcon\">".$gText['M_INVITE'];
$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$editBtn="<img src=\"$editIcon\">&nbsp;".$gText['M_EDIT'];
$regBtn="<img src=\"$regIcon\">&nbsp;".$gText['MD_REGISTRATION'];
$endRecBtn="<img src=\"$endRecIcon\">&nbsp;".$gText['M_END_RECORDING'];


function GetMeetingIcons($meetingInfo, $showPublic=true)
{
	global $gText;
	global $homeIcon, $schedIcon, $pwdIcon, $phoneIcon, $speakerIcon, $regIcon;
	$icons='';
	$id=$meetingInfo['id'];
	
	if ($showPublic) {
		if ($meetingInfo['public']=='Y')
			$icons.=
			"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('publish_tip$id', 1, 'style.visibility','visible')\">".
			"<img src=\"$homeIcon\">".
			"</span><span class=\"tool_tip\" id=\"publish_tip$id\">".$gText['MD_PUBLIC_TIP']."</span>&nbsp;";
	}
/*	
	if ($meetingInfo['scheduled']=='Y')
		$icons.=
		"<div class='s_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('schedule_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$schedIcon\">".
		"</div><div class=\"tool_tip\" id=\"schedule_tip$id\">".$gText['MD_SCHEDULED_TIP']."</div>&nbsp;";
*/	

	if ($meetingInfo['login_type']=='PWD')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('pwd_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$pwdIcon\">".
		"</span><span class=\"tool_tip\" id=\"pwd_tip$id\">".$gText['MD_PASSWORD_TIP']."</span>";

	if ($meetingInfo['login_type']=='REGIS')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('register_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$regIcon\">".
		"</span><span class=\"tool_tip\" id=\"register_tip$id\">".$gText['MD_REGISTER_TIP']."</span>";

	if ($meetingInfo['tele_conf']=='Y' && $meetingInfo['status']!='REC')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('tele_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$phoneIcon\">".
		"</span><span class=\"tool_tip\" id=\"tele_tip$id\">".$gText['MD_TELE_TIP']."</span>";
	if ($meetingInfo['audio']=='Y')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('audio_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$speakerIcon\">".
		"</span><span class=\"tool_tip\" id=\"audio_tip$id\">".$gText['MD_AUDIO_TIP']."</span>";
	
	return $icons;
}



//$meetingPage=$GLOBALS['BRAND_URL']."?".SID;
//$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_REGISTER."&".SID;
/*
function IsMeetingInProgress($meetingInfo) {

	$session=new VSession($meetingInfo['session_id']);
	$sessionInfo=array();
	$session->Get($sessionInfo);
	
	if (isset($sessionInfo['mod_time'])) {
		$lastTime=$sessionInfo['mod_time'];
		if ($lastTime=='0000-00-00 00:00:00')
			$lastTime=$sessionInfo['start_time'];
		
		list($sDate, $sTime)=explode(" ", $lastTime);
		list($sYear, $sMonth, $sDay)=explode("-", $sDate);
		list($hh, $mm, $ss)=explode(":", $sTime);
		$time2=mktime($hh, $mm, $ss, $sMonth, $sDay, $sYear);
	} else {
		$time2=0;
	}
	
	return IsInProgress($time2);
}
*/
?>

