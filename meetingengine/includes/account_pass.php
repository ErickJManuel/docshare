<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
/*
$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_USERS;
if (SID!='')
	$cancelUrl.="&".SID;
*/
$memberId=GetSessionValue('member_id');

$member=new VUser($memberId);
$member->GetValue('login', $login);

$thisPage=$_SERVER['PHP_SELF'];
$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$thisPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

$postUrl=VM_API."?cmd=SET_USER&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;	
	
$pwdText=_Text("Password missing or not matched.");
$loginText=_Text("Login");
$currPwdText=_Text("Current Password");
$newPwdText=_Text("New Password");
$retypeText=_Text("Retype password");
$uptoText=_Text("Up to 8 characters.");

?>


<script type="text/javascript">
<!--

function CheckUserForm(theForm) {

	if (theForm.password1.value=='' || theForm.password1.value!=theForm.password2.value)
	{
		alert( "<?php echo $pwdText?>");
		theForm.password1.focus();
		return (false);
	}

	return (true);
}

//-->
</script>


<form onSubmit="return CheckUserForm(this)" method="POST" action="<?php echo $postUrl?>" name="profile_form">
<input type="hidden" name="user_id" value="<?php echo $memberId?>">

<table class="meeting_detail">
<tr>
	<td class="m_key m_key_w"><?php echo $loginText?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $login?>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"><?php echo $currPwdText?>:</td>
	<td colspan="3" class="m_val">
	<input type="password" name="old_password" size="10" value="">
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"><?php echo $newPwdText?>:</td>
	<td colspan="3" class="m_val">
	<input type="password" name="password1" size="8" maxlength='8' value="">&nbsp;
	<?php echo $retypeText?>: <input type="password" name="password2" maxlength='8' size="8" value="">
	<span class='m_caption'><?php echo $uptoText?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
</tr>

</table>
</form>
