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
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vteleserver.php");


$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_USERS;
if (SID!='')
	$cancelUrl.="&".SID;

if (GetArg('user_id', $userId)) {
	$user=new VUser($userId);
	$userInfo=array();
	$user->Get($userInfo);
	if (!isset($userInfo['id'])) {
		ShowError("User not found.");
		return;
	}
	
	$groupdId=$userInfo['group_id'];
	
} elseif (GetArg('user', $accessId)) {
	VObject::Find(TB_USER, 'access_id', $accessId, $userInfo);
	if (!isset($userInfo['id'])) {
		ShowError("User not found.");
		return;
	}

	$groupdId=$userInfo['group_id'];
	
} else {
	// add a new user
	
	$groupdId='';

}

$brandId=$GLOBALS['BRAND_ID'];
if (isset($userInfo['id']) && $userInfo['brand_id']!=$brandId) {
	ShowError("The user is not a member of this site.");
	return;
}

if (!isset($userInfo['id']))
	$onGroupChanged="return OnChangeGroup();";

$query="`brand_id` = '$brandId'";
//$groups=VObject::GetFormOptions(TB_GROUP, $query, "group_id", "name", $groupdId, '', '', $onGroupChanged);


$errMsg=VObject::SelectAll(TB_GROUP, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

// if adding a new user, allow getting a free conf number
if (!isset($userInfo['id']))
	$groups= "<select onchange=\"$onGroupChanged\" name=\"group_id\">";
else
	$groups= "<select name=\"group_id\">";

$num_rows = mysql_num_rows($result);
$freeConfOpts=array();
$teleServerOpts=array();
$selectedGroupId=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	$teleId=$row['teleserver_id'];
	$canGetConf='N';
	$hasTele='N';
	if ($teleId>0) {
		$teleServer=new VTeleServer($teleId);
		$teleServer->GetValue('can_getconf', $canGetConf);
		$hasTele='Y';
	}
	$freeConfOpts[]=$canGetConf;
	$teleServerOpts[]=$hasTele;
	
	if ($groupdId!='' && $groupdId==$row['id']) {
		$groups.="<option value=\"".$row['id']."\" selected>".$row['name']."</option>";
		$selectedGroupIndex=count($freeConfOpts)-1;
	} else
		$groups.="<option value=\"".$row['id']."\">".$row['name']."</option>";
}
$groups.="</select>";


//$backPage=$_SERVER['PHP_SELF']."?brand=".$GLOBALS['BRAND_NAME']."&page=".PG_ADMIN_USERS;
$backPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_USERS;
if (SID!='')
	$backPage.="&".SID;
$backPage=VWebServer::EncodeDelimiter2($backPage);

$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$backPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
if (isset($userInfo['id'])) 
{
	$postUrl=VM_API."?cmd=SET_USER&return=$retPage";
} else {
	$postUrl=VM_API."?cmd=ADD_USER&return=$retPage";
}

$postUrl.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;	
/*
$addGroupUrl="index.php?page=".PG_ADMIN_ADD_GROUP."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$addGroupUrl.="&".SID;	

$addGroupBtn="<a href=\"$addGroupUrl\">${gText['M_ADD']}</a>";
*/
$licensePage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ACCOUNTS;
if (SID!='')
	$licensePage.="&".SID;	

$licenseBtn="<a target=\"".$GLOBALS['TARGET']."\" href=\"$licensePage\">${gText['M_ACCOUNTS']}</a>";

$format=$gText['M_ENTER_VAL'];

?>


<script type="text/javascript">
<!--

<?php
echo "var freeconfs=new Array();\n";
for ($i=0; $i<count($freeConfOpts); $i++) {
	echo "freeconfs[$i]='".$freeConfOpts[$i]."';\n";
}
echo "var hasteles=new Array();\n";
for ($i=0; $i<count($teleServerOpts); $i++) {
	echo "hasteles[$i]='".$teleServerOpts[$i]."';\n";
}
?>

function OnChangeGroup() {
	var index=document.profile_form.group_id.selectedIndex;
	if (index>=0) {
		if (freeconfs[index]=='Y') {
			document.getElementById('check_free_id').style.display='inline';
			document.profile_form.free_conf.checked=true;
		} else {
			document.profile_form.free_conf.checked=false;
			document.getElementById('check_free_id').style.display='none';		
		}
/*
		if (hasteles[index]=='Y') {
			document.getElementById('has_tele_id').style.display='inline';
		} else {
			document.getElementById('has_tele_id').style.display='none';		
		}
*/
	}
}

function CheckUserForm (theForm) {

	if (theForm.first_name.value=='' && theForm.last_name.value=='')
	{
		alert("<?php echo sprintf($format, 'Name')?>");
		if (theForm.first_name.value=='')
			theForm.first_name.focus();
		else if (theForm.last_name.value=='')
			theForm.last_name.focus();
		return (false);
	}
	if (theForm.login.value=='')
	{
		alert("<?php echo sprintf($format, 'Login')?>");
		theForm.login.focus();
		return (false);
	}
	if (theForm.password1 && theForm.password1.value!='' && theForm.password1.value!=theForm.password2.value)
	{
		alert("Password not matched.");
		theForm.password1.focus();
		return (false);
	}
<?php
if (isset($userInfo['permission']) && $userInfo['permission']!='ADMIN') {
	$msg=_Text('Are you sure you want to give ADMIN permission to this user?');
print <<<END
	if (theForm.permission.value=='ADMIN') {
		var ok=confirm("$msg");
		if (ok)
			return true;
		else
			return false;
	}
END;
}
?>
	return true;
}

//-->

</script>

<?php
if (isset($userInfo['id'])) {	
}

?>

<?php
if (isset($userInfo['id'])) 
{
	$login=$userInfo['login'];
	$id=$userInfo['id'];
	$fullName=htmlspecialchars(VUser::GetFullName($userInfo));
	$firstName=htmlspecialchars($userInfo['first_name']);
	$lastName=htmlspecialchars($userInfo['last_name']);
	$email=$userInfo['email'];
	$login=$userInfo['login'];
//	$group=$userInfo['group_id'];
	$perm=$userInfo['permission'];
	$title=htmlspecialchars($userInfo['title']);
	$org=htmlspecialchars($userInfo['org']);
	$street=htmlspecialchars($userInfo['street']);
	$city=htmlspecialchars($userInfo['city']);
	$state=htmlspecialchars($userInfo['state']);
	$zip=htmlspecialchars($userInfo['zip']);
	$country=htmlspecialchars($userInfo['country']);
	$phone=htmlspecialchars($userInfo['phone']);
	$mobile=htmlspecialchars($userInfo['mobile']);
	$fax=htmlspecialchars($userInfo['fax']);
	$licenseId=$userInfo['license_id'];
	$previewIcon="themes/preview.gif";
	$viewUrl=$GLOBALS['BRAND_URL']."?user=".$userInfo['access_id'];
	$active=$userInfo['active'];
	
	$checkSendMail='';
	$target=$GLOBALS['TARGET'];
	
print <<<END
<div class="heading1">$fullName</div>
<div class="list_tools"><a target=$target href="$viewUrl"><img src="$previewIcon">View Member Profile</a></div>

END;
} else {
	// new user
	$fullName='';
	$firstName='';
	$lastName='';
	$email='';
	$login='';
//	$group='';
	$perm='HOST';
	$title='';
	$org='';
	$street='';
	$city='';
	$state='';
	$zip='';
	$country='';
	$phone='';
	$mobile='';
	$fax='';
	$licenseId='';
	$active='Y';
	
	$checkSendMail='checked';
	
print <<<END
<div class="heading1">${gText['M_ADD_MEMBER']}</div>

END;
}

$offerings=explode(',', $gBrandInfo['offerings']);
/*
$query="";
foreach ($offerings as $v) {
	$items=explode(':', $v);
	if ($query!='')
		$query.=" OR ";
	$query.="`code` = '${items[0]}'";
}
if ($query=='')
	$query='0';
*/
//$prepend="";
//$licenses=VObject::GetFormOptions(TB_LICENSE, $query, "license_id", "name", $licenseId, $prepend);

// If a license key is assigned to this user, don't allow the change of the account type
if ($licenseId!='' && isset($userInfo['licensekey_id']) && $userInfo['licensekey_id']!='0') {
	$myLic=new VLicense($licenseId);
	$myLic->GetValue("name", $licName);
	$licenses=$licName;
} else {
	// check if the provider account has enough licenses for this type	
	$provider_id=$gBrandInfo['provider_id'];
	$licenseCounts=array();
	$providerInfo=array();

	$licenseType='';
	if ($provider_id>0) {
		$provider=new VProvider($provider_id);
		
		if ($provider->Get($providerInfo)!=ERR_NONE) {
			API_EXIT(API_ERR, $provider->GetErrorMsg());	
		}
		
		VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
	}	
	$errMsg=VObject::SelectAll(TB_LICENSE, "1", $result);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	$licenses="<select name=\"license_id\">\n";
		
	$num_rows = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$licCode=$row['code'];
		if (!in_array($licCode, $offerings))
			continue;
		
		$total=0;
	//	if ($licenseType=='N' || $licenseType=='P') {
			if (isset($licenseCounts[$licCode]))
				$total=$licenseCounts[$licCode];
	//		else if ($row['trial']=='Y')
	//			$total=-1;
	//		else
	//			$total=0;
	/*		
		} else {
			if ($row['trial']=='Y')
				$total=-1;
			
		}
	*/
	
		if ($total==0)
			continue;

		if ($total==-1)
			$availText="&nbsp;&nbsp;&nbsp;(".$gText['M_NO_LIMIT'].")";
		else {
			$licUsed=VProvider::GetLicenseUsed($provider_id, $brandId, $licCode);
			$avail=(int)$total-(int)$licUsed;
			if ($avail<0)
				$avail=0;

			$availText="&nbsp;&nbsp;&nbsp;($avail available)";
		}
		if ($licenseId!='' && $licenseId==$row['id'])
			$licenses.="<option value=\"".$row['id']."\" selected>".$row['name']." $availText</option>\n";
		else
			$licenses.="<option value=\"".$row['id']."\">".$row['name']."  $availText</option>\n";
	}
	$licenses.="</select>\n";
}

/*
$confNum=$confMcode=$confPcode='';

$freeNum=$freeMcode=$freePcode='';


if (!isset($userInfo['free_conf'])) {
	if ($gBrandInfo['free_audio_conf']=='Y') {
		$userInfo['free_conf']='Y';
		$checkFree='checked';
	}
} elseif ($userInfo['free_conf']=='Y') {
	$checkFree='checked';
	$freeNum=$userInfo['tele_num'];
	$freeMcode=$userInfo['tele_mcode'];
	$freePcode=$userInfo['tele_pcode'];

} else {
	$confNum=$userInfo['tele_num'];
	$confMcode=$userInfo['tele_mcode'];
	$confPcode=$userInfo['tele_pcode'];
	$checkConf='checked';
}
*/


?>
<form onSubmit="return CheckUserForm(this)" method="POST" action="<?php echo $postUrl?>" name="profile_form">
<?php
if (isset($userInfo['id'])) 
	echo "<input type=\"hidden\" name=\"user_id\" value=\"${userInfo['id']}\">";
else {
	echo "<input type=\"hidden\" name=\"brand\" value=\"${gBrandInfo['name']}\">";
	echo "<input type=\"hidden\" name=\"add_meeting\" value=\"1\">";
}

?>

<table class="meeting_detail">

<tr>
	<td class="m_key">*<?php echo $gText['MT_LOGIN']?>:</td>
	<td colspan="3" class="m_val">
<?php
if ($login==VUSER_GUEST) {
	echo $login."<span class='m_caption'>Default host for all unregistered users</span>";
	
} else {
	if ($login!='')
		$roText='readonly';
	else
		$roText='';
		
print <<<END
	<input $roText type="text" name="login" size="30" value="$login" autocorrect="off" autocapitalize="off">
	<span class='m_caption'>*${gText['M_REQUIRED']}</span>
END;
}
?>
	</td>
</tr>
<tr>
	<td class="m_key">*<?php echo $gText['MD_NAME']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_FIRSTNAME']?>: <input type="text" name="first_name" size="20" value="<?php echo $firstName?>">&nbsp;
	<?php echo $gText['M_LASTNAME']?>: <input type="text" name="last_name" size="20" value="<?php echo $lastName?>">
	</td>
</tr>
<?php
$passwordText=$gText['MT_PASSWORD'];

if (isset($userInfo['id'])) {
	$selectY='';
	$selectN='';
	if ($active=='Y')
		$selectY='selected';
	else
		$selectN='selected';
	
	print <<<END
<tr>
	<td class="m_key">$passwordText:</td>
	<td colspan="3" class="m_val">
	New password: <input type="text" name="password1" size="8" maxlength='8' autocorrect="off" autocapitalize="off" value="">&nbsp;
	Retype password: <input type="text" name="password2" size="8" maxlength='8' autocorrect="off" autocapitalize="off" value="">
	<span class='m_caption'>Up to 8 characters.</span>
	</td>
</tr>
END;
	if (GetSessionValue('member_id')!=$userInfo['id']) {
		$statusText=_Text("Status");
print <<<END
<tr>
	<td class="m_key">$statusText:</td>
	<td colspan="3" class="m_val">
	<select name='active'>
	<option $selectY value='Y'>Active</option>
	<option $selectN value='N'>Terminated</option>
	</select>
	</td>
</tr>
END;
	}

} else  {
	$password=strtolower(RandomPassword());
	$password2=$password;
	
	$retypeText=_Text("Retype password");
	$upto8Text=_Text("Up to 8 characters");
	
print <<<END
<tr>
	<td class="m_key">*$passwordText:</td>
	<td colspan="3" class="m_val">
	$passwordText: <input type="text" name="password" size="8" maxlength='8' autocorrect="off" autocapitalize="off" value="$password">&nbsp;
	$retypeText: <input type="text" name="password2" size="8" maxlength='8' autocorrect="off" autocapitalize="off" value="$password2">
	<div class='m_caption'>$upto8Text</div>
	</td>
</tr>

END;
	
}
$groupText=$gText['M_GROUP'];

?>

<tr>
	<td class="m_key"> <?php echo $groupText?>:</td>
	<td colspan="3" class="m_val"> <?php echo $groups?>
	</td>
</tr>

<?php
if ($login!=VUSER_GUEST) {
	
	$selectHost='';
	$selectAdmin='';
	if ($perm=='HOST')
		$selectHost='selected';
	else
		$selectAdmin='selected';
		
	$accText=$gText['M_ACCOUNT_TYPE'];
	$permText=$gText['M_PERMISSION'];
	print <<<END
<tr>
	<td class="m_key">$accText:</td>
	<td colspan="3" class="m_val">$licenses
	<span class="m_button_s">$licenseBtn</span>
	</td>
</tr>
<tr>
	<td class="m_key">$permText:</td>
	<td colspan="3" class="m_val">
		<select name='permission'>
			<option $selectHost value='HOST'>HOST</option>
			<option $selectAdmin value='ADMIN'>ADMIN</option>
		</select>
		<span class='m_caption'>HOST: Meeting host</span>
		<span class='m_caption'>ADMIN: Site administrator</span>
	</td>
</tr>
END;
}

$affiText=_Text("Affiliation");
$addressText=_Text("Address");
$teleconfText=$gText['M_AUDIO_CONF'];
$titleText=$gText['M_TITLE'];
$orgText=$gText['M_ORG'];
$streetText=$gText['M_STREET'];
$cityText=$gText['M_CITY'];
$stateText=$gText['M_STATE'];
$zipText=$gText['M_ZIP'];
$countryText=$gText['M_COUNTRY'];
$skipEmailText=_Text("Skip if the email address is the same as Login.");

print <<<END
<tr>
	<td class="m_key">$affiText:</td>
	<td colspan="3" class="user_val">
	$titleText: <input type="text" name="title" size="20" value="$title">&nbsp;
	$orgText: <input type="text" name="org" size="20" value="$org">
	</td>
</tr>
<tr>
	<td class="m_key">$addressText:</td>
	<td colspan="3" class="m_val">
	<div>$streetText: <input type="text" name="street" size="60" value="$street"></div>
	<div><span>$cityText: <input type="text" name="city" size="20" value="$city"></span>&nbsp;
	<span>$stateText: <input type="text" name="state" size="20" value="$state"></span>&nbsp;
	</div>
	<div><span>$zipText: <input type="number" name="zip" size="10" value="$zip"></span>&nbsp;
	<span>$countryText: <input type="text" name="country" size="20" value="$country"></span>&nbsp;
	</div>
	</td>
</tr>

<tr>
	<td class="m_key">${gText['M_PHONE']}:</td>
	<td colspan="3" class="user_val">
	<input type="number" name="phone" size="20" value="$phone">
	</td>
</tr>
<tr>
	<td class="m_key">${gText['M_MOBILE']}:</td>
	<td colspan="3" class="user_val">
	<input type="number" name="mobile" size="20" value="$mobile">
	</td>
</tr>
<tr>
	<td class="m_key">${gText['M_FAX']}:</td>
	<td colspan="3" class="user_val">
	<input type="number" name="fax" size="20" value="$fax">
	</td>
</tr>
<tr>
	<td class="m_key">${gText['M_EMAIL']}*:</td>
	<td colspan="3" class="user_val">
	<input type="email" name="email" size="30" value="$email" autocorrect="off" autocapitalize="off">
	<span class="m_caption">$skipEmailText</span>
	</td>
</tr>
END;

?>
<tr>
	<td class="m_key"> <?php echo $teleconfText?>:</td>
	<td colspan="3" class="m_val">
	<div id='check_free_id' class='sub_val1'><input type="checkbox" name="free_conf" value="1">
	<b> <?php echo _Text("Assign a free conference number* to the member.")?></b>
	<div class="m_caption"> *<?php echo _Text("An audio conference number will be automatically assigned to the member if Free Audio Conferencing is enabled for the group. US numbers only.")?></div>
	</div>

	<div class='sub_val1'><b> <?php echo _Text("Assign the following number to the member")?>:</b></div>
	
	<?php echo $gText['M_PHONE']?>: <input type="number" name="conf_num" value="<?php if (isset($userInfo['conf_num'])) echo $userInfo['conf_num'];?>" size='15'>&nbsp;
	<?php echo _Text("Alternate (Toll-free) Phone")?>: <input type="number" name="conf_num2" value="<?php if (isset($userInfo['conf_num2'])) echo $userInfo['conf_num2'];?>" size='15'></div>
	<div class='sub_val1'>	
	<?php echo $gText['MD_PHONE_MCODE']?>: <input type="number" name="conf_mcode" value="<?php if (isset($userInfo['conf_mcode'])) echo $userInfo['conf_mcode'];?>" size='10'>&nbsp;
	<?php echo $gText['MD_PHONE_PCODE']?>: <input type="number" name="conf_pcode" value="<?php if (isset($userInfo['conf_pcode'])) echo $userInfo['conf_pcode'];?>" size='10'>
	</div>
	<div id='has_tele_id' class='sub_val1'>
	<input type="hidden" name="use_teleserver_checkbox" value="1">
	<input type="checkbox" <?php if (isset($userInfo['use_teleserver']) && $userInfo['use_teleserver']=='Y') echo 'checked';?> name="use_teleserver_checked" value="1">
	<?php echo _Text("This number belongs to the teleconference server assigned to the group.")?>
	<div class='m_caption'><?php echo _Text("Check this box if the phone number is controllable by the teleconference server.")?></div>
	</div>
	
<?php
//	<div class='sub_val1'><input $checkConf type="radio" name="free_conf" value="N"><b>Assign the following nubmer:</b></div>

/*
if ($gBrandInfo['free_audio_conf']=='Y') {
print <<<END
	<div class='sub_val1'><input $checkFree type="radio" name="free_conf" value="Y"><b>Assign a FREE* conference number to the member.</b></div>
END;
	if ($freeNum!='') {
print <<<END
	<div class='sub_val1'>
	Phone: <input readOnly type="text" name="free_num" value="$freeNum" size='11'>
	Moderator code: <input readOnly type="text" name="free_mcode" value="$freeMcode" size='7'>
	Attendee code: <input readOnly type="text" name="free_pcode" value="$freePcode" size='7'>
	</div>
END;
	}
print <<<END
	<div class='m_caption'>*US numbers only. Long distance charges may apply.</div>
END;
}
*/
?>

	</td>
</tr>
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input <?php echo $checkSendMail?> type="checkbox" name="notify" value="1"><?php echo _Text("Send email notification to the member.")?></td>
</tr>
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')"></td>
</tr>

</table>
</form>


<script type="text/javascript">
<!--

<?php
	if (isset($userInfo['id']) || $freeConfOpts[$selectedGroupId]!='Y') {
		echo "document.getElementById('check_free_id').style.display='none';\n";
	} else {
		echo "document.profile_form.free_conf.checked=true;\n";
	}
	
	if ($teleServerOpts[$selectedGroupId]!='Y') {
	//	echo "document.getElementById('has_tele_id').style.display='none';\n";
	}
?>
//-->
</script>