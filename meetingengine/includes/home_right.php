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

require_once("dbobjects/vwebserver.php");


$memberId=GetSessionValue('member_id');
//echo ("member=".$memberId);
//$signinPage="signin.php?".SID;
//$signupPage="signup.php?".SID;

//$joinPage="viewer.php";
//if (SID!='')
//	$joinPage.="&".SID;

$joinPage=$GLOBALS['BRAND_URL'];
//$meeting->GetViewerUrl(false, $attendUrl);

$trialLicId=$gBrandInfo['trial_license_id'];
$trialGroupId=$gBrandInfo['trial_group_id'];
$brandName=$gBrandInfo['name'];

$offerings=$gBrandInfo['offerings'];

// if the offering starts with T, it's a trial license
//if ($offerings{0}=='T' && $gBrandInfo['trial_signup']=='Y')
if ($trialLicId!='0' && $gBrandInfo['trial_signup']=='Y')
	$hasTrial=true;
else
	$hasTrial=false;


$joinmeetingText=_Text("Meeting participants");
$enteridText=_Text("Enter meeting ID:");
$enteridText=_Text("Meeting ID:");
$notMemText=_Text("Not a member?");
//$trialText=_Text("Sign up for a free trial");
$trialText=_Text("Free Trial");
$signupText=_Text("Sign Up");
$notreadyText=_Text("Not ready to sign up?");
$startText=_Text("Start a meeting without signup");
$startmeetingText=_Text("Start Meeting");
$joinText=_Text("Join");

if (strpos($GLOBALS['THEME'], 'broadsoft')!==false) {
	$joinmeetingText=_Text("Join a Meeting");
}

if (isset($GLOBALS['JOIN_BOX']) && $GLOBALS['JOIN_BOX']=='off')
	$showJoinBox=false;
else
	$showJoinBox=true;

?>

<script type="text/javascript">
<!--
function ShowViewer(theForm)
{
	if (theForm.meeting.value=='')
	{
		alert("Please enter a value for the \"meeting ID\" field.");
		theForm.meeting.focus();
		return (false);
	}
	return true;
}

function CheckSignupForm(theForm) {
	if (theForm.full_name.value=='')
	{
		alert("Please enter a value for the \"Name\" field.");
		theForm.full_name.focus();
		return (false);
	}
	if (theForm.login.value=='')
	{
		alert("Please enter a value for the \"email\" field.");
		theForm.login.focus();
		return (false);
	}
	var ok=confirm("Please confirm the email address is correct:\n'"+theForm.login.value+"'");
	if (ok)
		return true;
	else
		return false;

	return true;
}


//-->
</script>

<div id='r-text'>

<?php

if ($showJoinBox) {
	print <<<END
<div class='box1_top'>&nbsp;</div>
<div class='box1_mid'>
<form target=${GLOBALS['TARGET']} onSubmit="return ShowViewer(this);" class="right_form" method="GET" action="$joinPage" name="join_form">
<div class='box2_top'></div>
<div class='box2_mid'>
<div class='box-text'>
<div class='right_hd1'>$joinmeetingText</div>
$enteridText<br>
<input type="hidden" name="page" value="HOME_JOIN">
<input type="hidden" name="redirect" value="1">
<input type="number" name="meeting" size="10">
<input type="submit" value="$joinText">
</div>
</div>
<div class='box2_bottom'></div>
</form>
</div>
<div class='box1_bottom'>&nbsp;</div>
END;
}


if ($hasTrial && ($memberId==0 || $memberId=='')) {
require_once("dbobjects/vuser.php");
require_once("dbobjects/vlicense.php");
	$fromEmail=$gBrandInfo['from_email'];

	$text1=_Text("Your member information has been sent to the email address you entered.");
	$format=_Text("Please make sure to accept email from '%s'");
	$text2=sprintf($format, $fromEmail);
	
	$meetingsPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS;
	$msg=$text1."<br>";
	$msg.=$text2;
	$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$meetingsPage."&message=".rawurlencode($msg);
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
	$signupUrl=VM_API."?cmd=ADD_USER&return=$retPage";
	if (SID!='')
		$signupUrl.="&".SID;
		

	$startUrl='start.php';
	if (SID!='')
		$startUrl.="?".SID;
		
	$meetingId=GetSessionValue('meeting_id');
	$userId=GetSessionValue('user_id');
//	echo ("meeting=".$meetingId);
	
	// no trial license is defined for the brand.
	// find the default trial license
	if ($trialLicId=='0') {
		$errMsg=VObject::Find(TB_LICENSE, 'code', 'TPV1', $licenseInfo);
		$numUsers=$licenseInfo['max_att'];
		$trialLicId=$licenseInfo['id'];
	} else {
		$license=new VLicense($trialLicId);
		$license->GetValue('max_att', $numUsers);
		if (!isset($numUsers))
			$numUsers=2;		
	}
	
	$onStartClick="onclick=\"alert('Your trial account allows you to have $numUsers participants per meeting.'); return true;\"";
/*
	if ($gBrandInfo['free_audio_conf']=='Y') {
		$freeConf='Y';
	} else {
		$freeConf='N';
	}
*/		
//<input type="hidden" value="$freeConf" name="free_conf">
//<div class='right_hd1'>$notMemText</div>
	
	print <<<END
<div class='box1_top'>&nbsp;</div>
<div class='box1_mid'>
<form id='signup_form' onSubmit='return CheckSignupForm(this)' class="right_form" method="POST" action="$signupUrl" name="signup_form">
<input type="hidden" value="$brandName" name="brand">
<input type="hidden" value="$joinPage" name="brandUrl">
<input type="hidden" value="$trialLicId" name="license_id">
<input type="hidden" value="$trialGroupId" name="group_id">
<input type="hidden" value="1" name="notify">
<input type="hidden" value="1" name="add_meeting">
<input type="hidden" value="1" name="validate_login">
<input value="1" name="free_conf" type="hidden">
<div class='box2_top'></div>
<div class='box2_mid'>
<div class='box-text'>
<div class='right_hd1'>$trialText</div>
${gText['M_EMAIL']}:<br>
<input type="text" name="login" size="22" autocorrect="off" autocapitalize="off"><br>
${gText['M_YOUR_NAME']}:<br>
<input type="text" name="full_name" size="22" autocorrect="off"><br>
<div class='signup_button'><input type="submit" value="$signupText" name="signup"></div>
</div>
</div>
<div class='box2_bottom'></div>
</form>

END;

	if (strpos($GLOBALS['THEME'], 'broadsoft')===false && !IsIPhoneUser() && !IsIPadUser()) {
		print <<<END
<form target=${GLOBALS['TARGET']} id='start_form' class="right_form" method="POST" action="$startUrl" name="start_form">
<input type="hidden" value="$brandName" name="brand">
<input type="hidden" value="$joinPage" name="brandUrl">
<input type="hidden" value="$meetingId" name="meeting">
<input type="hidden" value="$trialLicId" name="license_id">
<input type="hidden" value="$trialGroupId" name="group_id">
<div class='box2_top'></div>
<div class='box2_mid'>
<div class='box-text'>
<div class='right_hd1'>$notreadyText</div>
<div class='right_hd2'>$startText</div>
<div class='signup_button'><input $onStartClick type="submit" value="$startmeetingText" name="start"></div>
</div>
</div>
<div class='box2_bottom'></div>
</form>
</div>
<div class='box1_bottom'>&nbsp;</div>

END;
	}
}
?>

</div>