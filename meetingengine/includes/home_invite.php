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
require_once("dbobjects/vmeeting.php");

if  (GetArg('meeting', $arg)) {
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $arg, $meetingInfo);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	if (isset($meetingInfo['id']))
		$meeting=new VMeeting($meetingInfo['id']);	
}

if (!isset($meeting)) {
	ShowError("Meeting not set");
	return;
}

GetArg("viewer", $viewer);
GetArg("user", $userName);

//$invitePage=$_SERVER['PHP_SELF']."?page=".PG_HOME_INVITE."&brand=".$GLOBALS['BRAND_NAME'];
//if (SID!='')
//	$invitePage.='&'.SID;

$brandUrl=$gBrandInfo['site_url'];
$invitePage='';	
$message='';
$errMessage='';
$body='';
$title=htmlspecialchars($meetingInfo['title']);

if ($viewer=='1') {
	// not sure who is using this; keep it for now
	$meeting->GetMeetingUrl($url, true, $userName);
} else {
	$meeting->GetMeetingUrl($url);
}
	
// remove http:// and the trailing / so it is easy to give out the url verbally
$verbUrl=str_replace("http://", "", $url);
$pos=strpos($verbUrl, "/?");
if ($pos>0)
	$verbUrl=substr($verbUrl, 0, $pos);

$webMail=true;

// called from clicking on the Send Email button
if (isset($_POST['send_mail'])) {
require_once("dbobjects/vmailtemplate.php");


	GetArg('title', $title);
	// construct the email fields
	GetArg('body', $body);
	// get rid of extra new lines
	$body=str_replace("\r\n", "\n", $body);
	
	GetArg('to', $to);
	if ($to=='') {
		ShowError("Missing email address.");
		return;
	}
	
	$toItems=explode(",", $to);
	foreach ($toItems as $anItem) {
		$anItem=trim($anItem);
		if (!valid_email($anItem)) {
			ShowError("Invalid email address: $anItem");
			return;
		}
	}
/*		
	$host=new VUser($meetingInfo['host_id']);
	$hostInfo=array();
	$host->Get($hostInfo);

	$fromName=$host->GetFullName($hostInfo);
	$fromEmail=$hostInfo['email'];
	if ($fromEmail=='')
		$fromEmail=$hostInfo['login'];
*/

	$attachData='';
	$attachFile='';
	if ($meetingInfo['scheduled']=='Y' && $meetingInfo['status']!='REC') {	
		$attachFile="meeting_".$meetingInfo['access_id'].".ics";
		$attachData=VMeeting::GetICal($meetingInfo, $url, false);		
	}
	
/* don't set the from address with the member's email because it may cause the mail to be blocked
	$memberId=GetSessionValue('member_id');
	if ($memberId!='') {
		require_once("dbobjects/vuser.php");
		$memberInfo=array();
		$member=new VUser($memberId);
		$member->Get($memberInfo);
		$fromEmail=$memberInfo['email'];
		if ($fromEmail=='')
			$fromEmail=$memberInfo['login'];
		$fromName=$member->GetFullName($memberInfo);
	} else {
*/
		$fromEmail=$gBrandInfo['from_email'];
		$fromName=$gBrandInfo['from_name'];
//	}

	if (($errMsg=VMailTemplate::Send($fromName, $fromEmail, '', $to, $title, $body,
			$attachData, $attachFile, "text/calendar", false, null, $gBrandInfo))!='')
		$errMessage=$errMsg;
	else {
		$format=_Text("Email sent to %s successfully.");
		$message=sprintf($format, $to);
	}
	
/*
					
	$headers='';
   	$headers.="From: $hostEmail\r\n";
    $headers.="X-Mailer: PHP/". phpversion()."\r\n";
	
	if ($to=='') {
		$message="Missing mail recipients.";			
	} else if ($body=='') {
		$message="Empty mail body.";			
	} else {
		// send email with PHP	
		if (!mail($to, $title, $body, $headers))
			$message="Couldn't send email.";
		else
			$message="Email sent to $to successfully.";
	}			
*/


// initialize mail body with the input data	
} else {

	$body=VMeeting::GetInvitationText($meetingInfo, $url, false, $gBrandInfo);
	
}

// can only send text email, not html email
//$mailUrl="mailto:?subject=".rawurlencode($meetingInfo['title']);
//$mailUrl.="&body=".rawurlencode($body);

$format=$gText['M_ENTER_VAL'];

$smsSubj=$meetingInfo['title'];
$smsMsg="Click on the URL to connect:\n";
$smsMsg.=$brandUrl."?page=HOME_JOIN&meeting=".$meetingInfo['access_id'];
if ($meetingInfo['login_type']=='PWD')
	$smsMsg.="&pass=".$meetingInfo['password']."\n";

$msg=_Text("Your SMS message has been sent.");
	
$retPage=$_SERVER['PHP_SELF']."?page=".PG_HOME_INFORM."&message=".rawurlencode($msg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);
$smsUrl=SITE_URL."api.php?cmd=SEND_SMS&brand=".$gBrandInfo['name'];
$smsFrom=GetSessionValue('member_name');

?>

<script type="text/javascript">
<!--
	function CheckForm(theForm) {

		if (theForm.to.value=='')
		{
			alert("<?php echo sprintf($format, 'Email')?>");
			theForm.to.focus();
			return (false);
		}
		return (true);
	}

	function openmail(form) {
		var to = escape(form.to.value);
		var title = encodeURI(form.title.value);
		var body = encodeURI(form.body.value);
		var mailurl = "mailto:"+to;
		mailurl += "?subject="+title;
		mailurl += "&body="+body;
		window.location.href=mailurl;
	}


// -->
</script>

<?php
	include_once("dbobjects/vuser.php");
	include_once("includes/social_media.php");
	
	$host=new VUser($meetingInfo['host_id']);
	$hostInfo=array();
	$host->Get($hostInfo);
	$stz='';
	if ($hostInfo['time_zone']!='')
		$stz=$hostInfo['time_zone'];
	elseif  (isset($gBrandInfo['time_zone']))
		$stz=$gBrandInfo['time_zone'];

	$shareText=_Text("Share It");
	// sharing of the meeting url via Facebook and Twitter
	$fbHtml=GetFacebookShareHtml($brandUrl, $meetingInfo, $stz, $gBrandInfo['product_name'], $hostInfo['pict_id']);
	$twHtml=GetTwitterShareHtml($brandUrl, $meetingInfo, $stz);


?>

<div>
</div>

<table class='invite_info'>
<tr>
<td>
<img style="vertical-align:middle" src="<?php echo $viewIcon?>"> <?php echo $gText['MD_MEETING_URL']?>:</td>
<td>
<a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $url?>"><?php echo $url?></a>
</td>
</tr>

<?php
if (!isset($gBrandInfo['share_it']) || $gBrandInfo['share_it']=='Y') {
print <<<END
<tr>
<td>
<img style="vertical-align:middle" src="$socialIcon"> $shareText:</td>
<td>
	$fbHtml &nbsp; 
	$twHtml
</td>
</tr>
END;
}
?>

<!--
<tr><td>
<form method="POST" action="<?php echo $invitePage?>" name="updateUrlForm">
	<input type="hidden" value="<?php echo $meetingInfo['access_id']?>" name="meeting">
	&nbsp;&nbsp;&nbsp;&nbsp;
	<input <?php if ($viewer=='1') echo 'checked';?> type="checkbox" 
		value="1" name="viewer">
		Show URL for the meeting viewer page.&nbsp;
	<input type="submit" value="Update URL" name="updateUrl">
</form>
</td></tr>
-->
</table>

<div class='invite_bar'><?php echo _Text("Invite attendees verbally")?></div>
<p>
<?php echo sprintf(
_Text("Go to <b>'%s'</b> and enter the meeting id <b>'%s'</b> to join."), $verbUrl, $meetingInfo['access_id']); //_Comment: meeting invitation page
?>

<div class='invite_bar'><?php echo $gText['MD_INV_SEND']?></div>

<div class='inform'><?php echo $message?></div>
<div class='error'><?php echo $errMessage?></div>

<form onSubmit="return CheckForm(this)" method="POST" action="<?php echo $invitePage?>" name="sendEmailForm">
	<input type="hidden" value="<?php echo $meetingInfo['access_id']?>" name="meeting">

	<div class='invite_msg'><?php echo $gText['MD_INV_EMAL']?></div>
	<div class='invite_val'><input type="text" name="to" size="80" autocorrect="off" autocapitalize="off"></div>
	<div class='invite_msg'><?php echo $gText['M_TITLE']?></div>
	<div class='invite_val'><input type="text" name="title" value="<?php echo $title?>" size="80"></div>
	<div class='invite_msg'><?php echo $gText['MD_INV_MSG']?></div>
	<div class='invite_val'><textarea rows="6" name="body" cols="80"><?php echo $body?></textarea></div>
	<div class='invite_msg'><input type="submit" value="<?php echo $gText['M_SEND_EMAIL']?>" name="send_mail"></div>


	<div class='invite_bar'><?php echo $gText['MD_INV_OPEN']?></div>

	<div class=invite_msg><input type="button" name="open_email" value="<?php echo $gText['M_OPEN_EMAIL']?>" onclick="openmail(document.sendEmailForm)"></div>
</form>

<?php
/* not ready to deploy this even though it seems to be working
<div class='invite_bar'><?php echo _Text("Send SMS to an iPhone user (iPhone user must have installed the meeting app to join.)")?></div>

<form method="POST" action="<?php echo $smsUrl?>" name="sms_form">
	<input type="hidden" name="return" value="<?php echo $retPage?>">
	<input type="hidden" name="subject" value="<?php echo $smsSubj?>">
	<input type="hidden" name="from" value="<?php echo $smsFrom?>">
	<div class='invite_msg'><?php echo _Text("Message")?></div>
	<div class='invite_val'><input type="text" name="message" value="<?php echo $smsMsg?>" size="120"></div>
	<div class='invite_msg'><?php echo _Text("Enter a phone number to send the message to (AT&T numbers only)")?></div>
	<div class='invite_val'><input type="text" name="phones" value="">
	<input type="submit" name="submit" value="Send">
	</div>
</form>
*/
?>

<script type="text/javascript">
<!--
	
	var elem=document.getElementById('loader');
	if (elem)
		elem.style.display='none';

// -->
</script>



