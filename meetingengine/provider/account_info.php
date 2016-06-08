<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

$message='';
if (isset($_POST['submit'])) {
	$providerId='';
	if (GetArg('provider_id', $arg))
		$providerId=$arg;
	$provider=new VProvider($providerId);
	
	$info=array();
	if (GetArg('login', $arg))
		$info['login']=$arg;
	if (GetArg('company_name', $arg))
		$info['company_name']=$arg;
	if (GetArg('password1', $arg) && $arg!='')
		$info['password']=$arg;
	if (GetArg('first_name', $arg))
		$info['first_name']=$arg;
	if (GetArg('last_name', $arg))
		$info['last_name']=$arg;
	if (GetArg('admin_email', $arg))
		$info['admin_email']=$arg;
	if (GetArg('street', $arg))
		$info['street']=$arg;
	if (GetArg('city', $arg))
		$info['city']=$arg;
	if (GetArg('state', $arg))
		$info['state']=$arg;
	if (GetArg('zip', $arg))
		$info['zip']=$arg;
	if (GetArg('country', $arg))
		$info['country']=$arg;
	if (GetArg('phone', $arg))
		$info['phone']=$arg;

	if ($provider->Update($info)!=ERR_NONE)
		$message=$provider->GetErrorMsg();
	else
		$message="Account information has been updated.";

}

$providerId=GetSessionValue('provider_id');

$provider=new VProvider($providerId);
$providerInfo=array();
$provider->Get($providerInfo);
if (!isset($providerInfo['id'])) {
	ShowError("Provider not found");
	return;
}

$postUrl=$_SERVER['PHP_SELF']."?page=ACCOUNT_INFO";
if (SID!='')
	$postUrl.="&".SID;	

?>


<script type="text/javascript">
<!--

function CheckUserForm (theForm) {

	if (theForm.first_name.value=='' && theForm.last_name.value=='')
	{
		alert("Please enter a value for the \"Name\" field.");
		if (theForm.first_name.value=='')
			theForm.first_name.focus();
		else if (theForm.last_name.value=='')
			theForm.last_name.focus();
		return (false);
	}

	if (theForm.password1.value!='' && theForm.password1.value!=theForm.password2.value)
	{
		alert("Password not matched.");
		theForm.password1.focus();
		return (false);
	}

	if (theForm.admin_email.value=='')
	{
		alert("Please enter a value for the \"Admin Email\" field.");
		theForm.admin_email.focus();
		return (false);
	}

	return true;
}

//-->

</script>

<div class="heading1">Account Information</div>

<?php

if ($message!='')
	echo "<div class='inform'>$message</div>";

$firstName=htmlspecialchars($providerInfo['first_name']);
$lastName=htmlspecialchars($providerInfo['last_name']);
$email=$providerInfo['admin_email'];
$org=htmlspecialchars($providerInfo['company_name']);
$street=htmlspecialchars($providerInfo['street']);
$city=htmlspecialchars($providerInfo['city']);
$state=htmlspecialchars($providerInfo['state']);
$zip=htmlspecialchars($providerInfo['zip']);
$country=htmlspecialchars($providerInfo['country']);
$phone=htmlspecialchars($providerInfo['phone']);

?>

<form onSubmit="return CheckUserForm(this)" method="POST" action="<?php echo $postUrl?>" name="profile_form">
	<input type="hidden" name="provider_id" value="<?php echo $providerInfo['id']?>">

<table class="meeting_detail">

<tr>
	<td class="m_key  m_key_m">Login Name:</td>
	<td colspan="3" class="m_val"><?php echo $providerInfo['login']?></td>
	</td>
</tr>
<tr>
	<td class="m_key  m_key_m">Company Name:</td>
	<td colspan="3" class="user_val">
	<input type="text" name="company_name" size="30" value="<?php echo $org?>">
	</td>
</tr>
<tr>
	<td class="m_key m_key_m">Provider Account ID:</td>
	<td colspan="3" class="m_val"><?php echo $providerInfo['account_id']?></td>
	</td>
</tr>
<tr>
	<td class="m_key  m_key_m">Password:</td>
	<td colspan="3" class="m_val">
	New password: <input type="password" name="password1" size="8" value="">&nbsp;
	Retype password: <input type="password" name="password2" size="8" maxlength='8' value="">
	<div class="m_caption">Up to 8 characters</div>
	</td>
</tr>
<tr>
	<td class="m_key  m_key_m">*Admin Name:</td>
	<td colspan="3" class="m_val">
	First name: <input type="text" name="first_name" size="20" value="<?php echo $firstName?>">&nbsp;
	Last name: <input type="text" name="last_name" size="20" value="<?php echo $lastName?>">
	</td>
</tr>
<tr>
	<td class="m_key  m_key_m">*Admin Email:</td>
	<td colspan="3" class="user_val">
	<input type="text" name="admin_email" size="30" value="<?php echo $email?>">
	</td>
</tr>

<tr>
	<td class="m_key  m_key_m">Address:</td>
	<td colspan="3" class="m_val">
	<div>Street: <input type="text" name="street" size="60" value="<?php echo $street?>"></div>
	<div><span>City: <input type="text" name="city" size="20" value="<?php echo $city?>"></span>&nbsp;
	<span>State: <input type="text" name="state" size="20" value="<?php echo $state?>"></span>&nbsp;
	</div>
	<div><span>Zip code: <input type="text" name="zip" size="10" value="<?php echo $zip?>"></span>&nbsp;
	<span>Country: <input type="text" name="country" size="20" value="<?php echo $country?>"></span>&nbsp;
	</div>
	</td>
</tr>

<tr>
	<td class="m_key  m_key_m"><?php echo $gText['M_PHONE']?>:</td>
	<td colspan="3" class="user_val">
	<input type="text" name="phone" size="20" value="<?php echo $phone?>">
	</td>
</tr>


<tr>
	<td class="m_key  m_key_m">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">
	<span class='m_caption'>*Required fields</span>
</tr>

</table>
</form>
