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
require_once("dbobjects/vregform.php");
require_once("includes/meetings_common.php");


$thisPage=$_SERVER['PHP_SELF'];;

$memberId=GetSessionValue('member_id');
$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE) {
	ShowError($member->GetErrorMsg());
	return;
}

if (!isset($memberInfo['id'])) {
	ShowError("Member record is not found.");
	return;
}	

GetArg('meeting_id', $meetingId);

$meeting=new VMeeting($meetingId);
$meeting->Get($meetingInfo);
if ($meeting->Get($meetingInfo)!=ERR_NONE) {
	ShowError($meeting->GetErrorMsg());
	return;
}

if (!isset($meetingInfo['id'])) {
	ShowError("Meeting record is not found.");
	return;
}

if ($meetingInfo['host_id']!=$memberId) {
	ShowError("Not authorized.");
	return;
}

$formId=$meetingInfo['regform_id'];

$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_DETAIL."&meeting=".$meetingInfo['access_id'];
if (SID!='')
	$retPage.="&".SID;
	
$cancelUrl=$retPage;

$retPage=VWebServer::EncodeDelimiter1($retPage);


// add a form
if ($formId=='0') {

	$regForm=new VRegForm();
	if (($errMsg=VRegForm::GetDefault($regInfo))!='') {
		ShowError($errMsg);
		return;
	}
	$regInfo['author_id']=$memberId;
		
	$postUrl=VM_API."?cmd=ADD_REGFORM&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$postUrl.="&".SID;
	
// edit an existing form
} else {

	$postUrl=VM_API."?cmd=SET_REGFORM&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$postUrl.="&".SID;
	
	$regForm=new VRegForm($formId);
	if ($regForm->Get($regInfo)!=ERR_NONE) {
		ShowError($regForm->GetErrorMsg());
		return;
	}
	
	if (!isset($regInfo['id'])) {
		ShowError("Registration form not found in our records.");
		return;		
	}
}

$previewIcon="themes/preview.gif";
$viewUrl=$GLOBALS['BRAND_URL']."?page=REGISTER&meeting=".$meetingInfo['access_id'];
if (SID!='')
	$viewUrl.="&".SID;

$customReply=$gText['MT_REGISTER_INFO'];
if (isset($regInfo['custom_reply']) && $regInfo['custom_reply']!='')
	$customReply=$regInfo['custom_reply'];
	
$checkReplyY='';
$checkReplyN='';
if ($regInfo['auto_reply']=='N')
	$checkReplyN='checked';
if ($regInfo['auto_reply']=='Y')
	$checkReplyY='checked';	
	
$reminderOpts="<select name='auto_reminder'>\n";
$opts=array(
	"0" => "No reminder",
	"1" => "1 hour",
	"4" => "4 hours",
	"24" => "24 hours",
);

$reminder=0;
if (isset($regInfo['auto_reminder']))
	$reminder=(int)$regInfo['auto_reminder'];
	
foreach ($opts as $key => $val) {
	if ($key==$reminder)
		$reminderOpts.="<option selected value='$key'>$val</option>\n";
	else
		$reminderOpts.="<option value='$key'>$val</option>\n";
}
$reminderOpts.="</select>\n";

?>

<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.name.value=='')
	{
		alert("Please enter a value for the \"Name\" field.");
		theForm.name.focus();
		return (false);
	}

	return (true);
}

function ChangeFormOption(optElem, textElemId)
{
	if (optElem.options[optElem.selectedIndex].value=='[CUSTOM]') {
		document.getElementById(textElemId).style.display='inline';	
	} else {
		document.getElementById(textElemId).style.display='none';
	}

	return true;
}

//-->
</script>

<div class="heading1"><?php echo $meetingInfo['title']?></div>

<!--
<div class="list_tools">
<a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $viewUrl?>">
<img src="<?php echo $previewIcon?>"><?php echo _Text("View Registration Page")?></a>
</div>
-->

<?php

$reqItems=explode(",", $regInfo['required_fields']);
$fieldText=_Text("Field");	//_Comment: As in a text field
$opts=VRegForm::$allFields;

?>

<table class="meeting_detail">

<form target="<?php echo $GLOBALS['TARGET']?>" onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="edit_reg_form">

<input type="hidden" name="form_id" value="<?php echo $formId?>">
<input type="hidden" name="meeting_id" value="<?php echo $meetingInfo['id']?>">


<tr>
	<td class="m_key m_key_m"><?php echo _Text("Auto Reply")?>:</td>
	<td colspan="3" class="m_val">
	<div>Automatically send reply email to users when they register:
	<input <?php echo $checkReplyY?> name="auto_reply" value="Y" type="radio"><?php echo _Text("Yes")?>	
	<input <?php echo $checkReplyN?> name="auto_reply" value="N" type="radio"><?php echo _Text("No")?>	
	</div>
	</td>
</tr>
<tr>
	<td class="m_key m_key_m"><?php echo _Text("Auto Reminder")?>:</td>
	<td colspan="3" class="m_val">
	<div>Automatically send reminder email to registered users:
	<?php echo $reminderOpts?><br>
	before a meeting's scheduled start time.
	</div>
	</td>
</tr>
<tr>
	<td class="m_key m_key_m"><?php echo _Text("Email Message")?>:</td>
	<td colspan="3" class="m_val"><textarea name='custom_reply' rows="2" cols="60"><?php echo $customReply?></textarea>
	<div class="m_caption">Customize the message in the notification email.</div>
	</td>
</tr>


<tr >
	<td class="m_key m_key_m"><?php echo _Text("Registration Form")?>:</td>
	<td colspan="3" class="m_val">

<table class="meeting_detail">

<?php
$max=VRegForm::$maxFields;
for ($i=1; $i<=$max; $i++) {
	$key='key_'.$i;
	
	$val='';
	if (!isset($regInfo[$key]))
		break;
		
	$val=$regInfo[$key];
	
	$customId="custom_".$i;
	$customLabelId="custom_label_".$i;
	$customFieldId="custom_field_".$i;
	
	$optsText="<select name=\"$key\" onchange=\"return ChangeFormOption(this, '$customId')\">\n";

	$customLabel='';
	$customField='';
	foreach ($opts as $item) {
		$itemText=FormKeyToText($item);
		if ($val==$item && $val!='')
			$optsText.="<option selected value='$item'>$itemText</option>\n";
		else if ($item=='[CUSTOM]' && $val!='' && $val[0]!='[' && $val[strlen($val)-1]!=']') {
			$customItems=explode("=", $val);
			$optsText.="<option selected value='$item'>$itemText</option>\n";
			$customLabel=$customItems[0];
			if (isset($customItems[1]))
				$customField=$customItems[1];
		} else
			$optsText.="<option value='$item'>$itemText</option>\n";
	}
	
	$optsText.="</select>\n";
	
	$reqKey="required_".$i;
	
	$reqChecked='';
	if (in_array($key, $reqItems))
		$reqChecked='checked';
	
	$display='none';
	if ($customLabel!='')
		$display='inline';
	
	$labelText=_Text("Label");
	$choiceText=_Text("Choices (optional)");
	$captionText=_Text("Separate choices by '|'. e.g. Choice 1|Choice 2|Choice 3. Leave blank for type-in fields.");
print <<<END
<tr>
	<td class="m_val">
	$optsText
	<input $reqChecked name="$reqKey" type="checkbox" value='Y'>Required &nbsp;
	<div id="$customId" style="display:$display"><b>$labelText:</b><input name="$customLabelId" type="text" size='30' value='$customLabel'>
	<div><b>$choiceText:</b> <input name='$customFieldId' type='text' size='60' value='$customField'></div>
	<div class='m_caption'>$captionText</div>
	</div>
</tr>	

END;
}
?>

<tr>
	<td class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>"> 
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')"></td>
</tr>
</form>

</table>
<div class="m_caption"><?php echo _Text("Email must be included as a required field in a registration form.")?></div>
<div class="m_caption"><?php echo _Text("Customize registration page logo in the Meeting Room page.")?></div>


	</td>
</tr>



</table>


