<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


// This file is not used currently.

require_once("dbobjects/vmailtemplate.php");

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN;
if (SID!='')
	$cancelUrl.="&".SID;

$brandId=$GLOBALS['BRAND_ID'];

$brand=new VBrand($brandId);

$brandInfo=array();
$brand->Get($brandInfo);

GetArg('template_id', $templateId);

$body='';
$subject='';
$templateName='';
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
	$subject=htmlspecialchars($mailInfo['subject']);
	$body=htmlspecialchars($mailInfo['body_text']);
	$templateName=$mailInfo['name'];
}	

$thisPage=$_SERVER['PHP_SELF'];

$retPage=$thisPage."?page=".PG_ADMIN."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL']."&page=".PG_ADMIN_USERS;
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
$postUrl=VM_API."?cmd=SET_BRAND&return=$retPage";
if (SID!='')
	$postUrl.="&".SID;	
	
$query="brand_id='0' OR brand_id = '".$gBrandInfo['id']."'";
$templates=VObject::GetFormOptions(TB_MAILTEMPLATE, $query, "template_id", "name", $templateId, null, null, 'template_id');

$tempPage=$thisPage."?page=".PG_ADMIN_EMAIL."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$tempPage.="&".SID;	

$mailKeys='';
foreach ($gMailKeys as $key => $value) {
	$mailKeys.=$value." ";
}

?>

<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.company_name.value=='')
	{
		alert("Please enter a value for the \"Company Name\" field.");
		theForm.company_name.focus();
		return (false);
	}
	if (theForm.company_url.value=='')
	{
		alert("Please enter a value for the \"Company URL\" field.");
		theForm.company_url.focus();
		return (false);
	}
	if (theForm.from_email.value=='')
	{
		alert("Please enter a value for the \"From Email\" field.");
		theForm.from_email.focus();
		return (false);
	}
	if (theForm.product_name.value=='')
	{
		alert("Please enter a value for the \"Product Name\" field.");
		theForm.product_name.focus();
		return (false);
	}

	return (true);
}

function SelectTemplate() {
	var pageUrl = '<?php echo $tempPage?>';
	var elem=document.getElementById('template_id');
	
	pageUrl+="&template_id="+elem.value;
	
	window.location = pageUrl;
	return true;

}

//-->
</script>

Set properties for email templates.
<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<input type="hidden" name="brand" value="<?php echo $GLOBALS['BRAND_NAME']?>">
<input type="hidden" name="brandUrl" value="<?php echo $GLOBALS['BRAND_URL']?>">

<table class="meeting_detail">

<tr>
	<td class="m_key">*Email From Name:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="from_name" size="50" value="<?php echo $brandInfo['from_name']?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>
<tr>
	<td class="m_key">*Email From Address:</td>
	<td colspan=3 class="m_val">
	<input type="email" name="from_email" size="50" value="<?php echo $brandInfo['from_email']?>" autocorrect="off" autocapitalize="off">
	</td>
</tr>

<tr>
	<td class="m_key">*Company/Org. Web Site:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="company_url" size="50" value="<?php echo $brandInfo['company_url']?>">
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*Product/Service Name:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="product_name" size="50" value="<?php echo $brandInfo['product_name']?>">
	</td>
</tr>

<tr>
	<td class="m_key">Email Template:</td>
	<td colspan=3 class="m_val1">
	<?php echo $templates?>
	<span class='m_val1'><input onclick="return SelectTemplate(this);" type="button" name="select_temp" value="<?php echo $gText['M_SELECT']?>"></span>
	<span class='m_caption'>Select a template to edit</span>
	</td>
</tr>

<tr>
	<td class="m_key">Template Name:</td>
	<td colspan=3 class="m_val1"><input type="text" name="template_name" size="30" value="<?php echo $templateName?>"></td>
</tr>

<tr>
	<td class="m_key">Email Subject:</td>
	<td colspan=3 class="m_val1"><input type="text" name="subject" size="30" value="<?php echo $subject?>"></td>
</tr>

<tr>
	<td class="m_key">Email Body:</td>
	<td colspan=3 class="m_val">
	<textarea rows="8" name="email_body" cols="65"><?php echo $body?></textarea>
	</td>
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	</td>
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val">
	The following words in the Email Body will be replaced with the actual text.
	<div>
	<?php echo $mailKeys?>
	</div>
	</td>
</tr>
</table>
</form>
