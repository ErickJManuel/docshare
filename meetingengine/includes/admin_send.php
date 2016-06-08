<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vmailtemplate.php");

if (GetArg('user_id', $userId)) {
	$user=new VUser($userId);
	$userInfo=array();
	$user->Get($userInfo);

	
} elseif (GetArg('user', $accessId)) {
	VObject::Find(TB_USER, 'access_id', $accessId, $userInfo);
	$userId=$userInfo['id'];
}
/*
GetArg('user_id', $userId);

$user=new VUser($userId);
$userInfo=array();
$user->Get($userInfo);
*/
if (!isset($userInfo['id'])) {
	ShowError("User not found.");
	return;
}

$toName=htmlspecialchars(VUser::GetFullName($userInfo));

$fromName=htmlspecialchars($gBrandInfo['from_name']);
$fromEmail=htmlspecialchars($gBrandInfo['from_email']);
$toEmail=htmlspecialchars($userInfo['email']);
if ($toEmail=='')
	$toEmail=htmlspecialchars($userInfo['login']);

GetArg('template_id', $templateId);

$body='';
$subject='';
if ($templateId>0) {
	$mail=new VMailTemplate($templateId);
	$mailInfo=array();
	if ($mail->Get($mailInfo)!=ERR_NONE) {
		ShowError($mail->GetErrorMsg());
		return;
	}
	if (!isset($mailInfo['id'])) {
		ShowError("Email template not found");
		return;
	}
	$subject=$gText[$mailInfo['subject']];
	$subject.=" [".$userInfo['access_id']."]";
	$subject=htmlspecialchars($subject);
	$body=VMailTemplate::GetBody($mailInfo, $userInfo, $gBrandInfo, $gText);
	$body=htmlspecialchars($body);
}	

$query="(brand_id='0' OR brand_id = '".$gBrandInfo['id']."') AND (type='ADD' || type='PASSWORD' || type='EDIT')";
$templates=VObject::GetFormOptions(TB_MAILTEMPLATE, $query, "template_id", "name", $templateId, '', 'template_id');
$templates=str_replace('MT_ADD_MEMBER', $gText['MT_ADD_MEMBER'], $templates);
$templates=str_replace('MT_SEND_PWD', $gText['MT_SEND_PWD'], $templates);
//$templates=str_replace('MT_REGISTER', $gText['MT_REGISTER'], $templates);
$templates=str_replace('MT_EDIT_MEMBER', $gText['MT_EDIT_MEMBER'], $templates);

$thisPage=$_SERVER['PHP_SELF'];
$backPage=$thisPage."?brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$backPage=VWebServer::EncodeDelimiter2($backPage);
$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$backPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

$sendPage=$thisPage."?page=".PG_ADMIN_SEND."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$sendPage.="&user_id=".$userId;
if (SID!='')
	$sendPage.="&".SID;

$postUrl=VM_API."?cmd=SEND_USER&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;

$format=$gText['M_ENTER_VAL'];

?>


<script type="text/javascript">
<!--


function CheckUserForm(theForm) {
	if (theForm.to_email.value=='')
	{
		alert("<?php echo sprintf($format, 'to_email')?>");
		theForm.to_email.focus();
		return (false);
	}
	if (theForm.subject.value=='')
	{
		alert("<?php echo sprintf($format, 'subject')?>");
		theForm.subject.focus();
		return (false);
	}
	if (theForm.body.value=='')
	{
		alert("<?php echo sprintf($format, 'body')?>");
		theForm.body.focus();
		return (false);
	}


	return (true);
}

function SelectTemplate() {
	var pageUrl = '<?php echo $sendPage?>';
	var elem=document.getElementById('template_id');
	
	pageUrl+="&template_id="+elem.value;
	
	window.location = pageUrl;
	return true;

}

//-->
</script>


<form onSubmit="return CheckUserForm(this)" method="POST" action="<?php echo $postUrl?>" name="email_form">


<div class='heading1'><?php echo _Text("Send Email To Member")?></div>


<table class="meeting_detail">

<tr>
	<td class="m_key"><?php echo _Text("Member Name")?>:</td>
	<td class="m_val"><input readonly type="text" name="to_name" size="30" value="<?php echo $toName?>"></td>
	<td class="m_key1"><?php echo $gText['M_EMAIL']?>:</td>
	<td class="m_val1"><input readonly type="text" name="to_email" size="30" value="<?php echo $toEmail?>"></td>
</tr>
<?php
/*
<tr>
	<td class="m_key"><?php echo _Text("From Name")?>:</td>
	<td class="m_val"><input type="text" name="from_name" size="30" value="<?php echo $fromName?>"></td>
	<td class="m_key1">Email:</td>
	<td class="m_val1"><input type="text" name="from_email" size="30" value="<?php echo $fromEmail?>"></td>
</tr>
*/
?>
<tr>
	<td class="m_key"><?php echo _Text("Email Template")?>:</td>
	<td colspan=3 class="m_val1">
	<?php echo $templates?>
	<span class='m_val1'><input onclick="return SelectTemplate(this);" type="button" name="select_temp" value="Create Email"></span>
	<span class='m_caption'><?php echo _Text("Create email from the selected template.")?></span>
	</td>
</tr>
<tr>
	<td class="m_key"><?php echo _Text("Subject")?>:</td>
	<td colspan=3 class="m_val1"><input type="text" name="subject" size="30" value="<?php echo $subject?>"></td>
</tr>

<tr>
	<td class="m_key"><?php echo _Text("Message")?>:</td>
	<td colspan=3 class="m_val">
	<textarea rows="10" name="body" cols="65"><?php echo $body?></textarea>
	</td>
</tr>
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan=3 class="m_val"><input type="submit" name="send" value="<?php echo $gText['M_SEND_EMAIL']?>"></td>
</tr>

</table>
</form>
