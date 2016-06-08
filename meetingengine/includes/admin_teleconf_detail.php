<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vteleserver.php");

$installer="vinstall.php";
$getVersion="vversion.php";
$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
if (SID!='')
	$cancelUrl.="&".SID;

$teleInfo=array();
$message='';
if (GetArg('id', $teleServerId)) {
	$teleServer=new VTeleServer($teleServerId);
	$teleServer->Get($teleInfo);
	if (!isset($teleInfo['id'])) {
		ShowError("Account not found");
		return;
	}
	if ($teleInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The account does not belong to this site.");
		return;
	}
	
	// hide the last 8 digits of the key for security reason
	$accessKey=$teleInfo['access_key'];
	$len=strlen($accessKey);
	if ($len>8) {
		$accessKey=substr($accessKey, 0, $len-8)."********";
	}
} else {
	
	$teleInfo['name']='';
	$teleInfo['getconf_url']='';
	$teleInfo['getconf_login']='';
	$teleInfo['getconf_password']='';
	$teleInfo['can_getconf']='Y';
	$teleInfo['can_record']='N';
	$teleInfo['server_url']='';
	$teleInfo['access_key']='';
	$teleInfo['can_dialout']='';	
	$teleInfo['can_control']='N';	
	$teleInfo['can_modify']='Y';	
	$accessKey='';
}

$thisPage=$_SERVER['PHP_SELF'];

//$webPage=$thisPage."?page=".PG_ADMIN_HOSTING."&brand=".$GLOBALS['BRAND_NAME'];
$webPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
$webPage=VWebServer::EncodeDelimiter2($webPage);
$message=$gText['M_SUBMIT_OK'];
$message.=" ".$gText['M_SELECT_PROFILE_FOR_GROUP'];
$retPage="$thisPage?page=".PG_HOME_INFORM."&ret=".$webPage."&message=".rawurlencode($message)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

if (isset($teleInfo['id'])) 
{
/*
	$retPage="$thisPage?page=".PG_ADMIN_EDIT_TELE."&id=".$teleInfo['id'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
*/
	$postUrl=VM_API."?cmd=SET_TELE&return=$retPage";
} else {
	$postUrl=VM_API."?cmd=ADD_TELE&return=$retPage";
}
if (SID!='')
	$postUrl.="&".SID;

$aMessage="Audio recording and control cannot be enabled for the default server unless you also choose the default free teleconference server.";

	
$format=$gText['M_ENTER_VAL'];
?>

<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.name.value=='')
	{
		alert(" <?php echo sprintf($format, 'Name')?>");
		theForm.name.focus();
		return (false);
	}

	if (theForm.server_choice[1].checked==true && 
		theForm.server_url.value=='')
	{
		alert(" <?php echo sprintf($format, 'Server URL')?>");
		theForm.server_url.focus();
		return (false);
	}


	return (true);
}

function DialOutClicked() {
	var elem=document.getElementById('dial_tollfree_id');
	if (document.teleconf_form.can_dialout_checked.checked) {
		elem.style.visibility='visible';
	} else {
		document.teleconf_form.dial_tollfree_only_checked.checked=false;
		elem.style.visibility='hidden';
	}
	return true;
}

//-->
</script>


<?php
if (isset($teleInfo['id'])) 
{
	$name=htmlspecialchars($teleInfo['name']);

	
print <<<END
<div class="heading1">$name</div>

END;


} else {

print <<<END
<div class="heading1">${gText['M_ADD_TELE']}</div>

END;

}
$descText=_Text("Set properties for recording and controlling a teleconference.");

echo $descText;
?>


<div id='web_info'>
<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="teleconf_form">
<?php
if (isset($teleInfo['id'])) {
	$teleId=$teleInfo['id'];
print <<<END
<input type="hidden" name="id" value="$teleId">
END;
} else {

print <<<END
<input type="hidden" name="" value="">
END;
}

$readOnly='';
$disabled='';
if ($teleInfo['can_modify']=='N') {
	$readOnly='readonly';
	$disabled='disabled';
}

?>

<table class="meeting_detail">

<tr>
	<td class="m_key">*<?php echo _Text("Profile Name")?>:</td>
	<td colspan=3 class="m_val">
	<input <?php echo $readOnly?> type="text" name="name" size="30" value="<?php echo $teleInfo['name']?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>


<tr>
	<td class="m_key"><?php echo _Text("Teleconference Server")?>:</td>
	<td colspan=3 class="m_val">

	<div class='sub_val1'>Server URL: <input <?php echo $readOnly?> type="text" name="server_url" size="60" value="<?php echo $teleInfo['server_url']?>"><br>
	Access Key: <input <?php echo $readOnly?> type="text" name="access_key" size="60" value="<?php echo $accessKey?>"></div>
	<input type="hidden" name="can_record_checkbox" value="1">
	<div class='sub_val1'><input <?php echo $disabled?> type="checkbox" name="can_rec_checked" <?php if (isset($teleInfo['can_record']) && $teleInfo['can_record']=='Y') echo 'checked';?> 
	value="1"><?php echo _Text("Enable audio recording.")?></div>
	<input type="hidden" name="can_control_checkbox" value="1">
	<div class='sub_val1'><input <?php echo $disabled?> type="checkbox" name="can_control_checked" <?php if (isset($teleInfo['can_control']) && $teleInfo['can_control']=='Y') echo 'checked';?> 
	value="1"><?php echo _Text("Enable audio controls.")?></div>
	<div class='sub_val1'><input <?php echo $disabled?> type="checkbox" name="can_hangup_all_checked" <?php if (isset($teleInfo['can_hangup_all']) && $teleInfo['can_hangup_all']=='Y') echo 'checked';?> 
	value="1"><?php echo _Text("Allow the server to disconnect all callers.")?></div>	
	<input type="hidden" name="can_hangup_all_checkbox" value="1">
	<input type="hidden" name="can_dialout_checkbox" value="1">
	<input type="hidden" name="dial_tollfree_only_checkbox" value="1">
	<div class='sub_val1'><input <?php echo $disabled?> onclick='return DialOutClicked();' type="checkbox" name="can_dialout_checked" <?php if (isset($teleInfo['can_dialout']) && $teleInfo['can_dialout']=='Y') echo 'checked';?> 
	value="1"><?php echo _Text("Allow the server to dial out.")?>
		<div class='sub_val2' id="dial_tollfree_id"><input <?php echo $disabled?> type="checkbox" name="dial_tollfree_only_checked" <?php if (isset($teleInfo['dial_tollfree_only']) && $teleInfo['dial_tollfree_only']=='Y') echo 'checked';?> 
		value="1"><?php echo _Text("Limit dial-out to toll-free numbers only.")?></div>
	</div>
	</td>
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input <?php echo $disabled?> type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input <?php echo $disabled?> type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input <?php echo $disabled?> type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	<div class='m_caption'><?php echo $gText['M_SELECT_PROFILE_FOR_GROUP']?></div>
	</td>
</tr>
</table>
</form>
</div>


<script type="text/javascript">
<!--
	DialOutClicked();

//-->
</script>
