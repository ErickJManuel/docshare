<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vconversionserver.php");

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
if (SID!='')
	$cancelUrl.="&".SID;
	
$conversionInfo=array();
$installStr='';
$message='';
$code='';
if (GetArg('id', $conversionId)) {
	$conversionServer=new VConversionServer($conversionId);
	$conversionServer->Get($conversionInfo);
	if (!isset($conversionInfo['id'])) {
		ShowError("Account is not found");
		return;
	}
	if ($conversionInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The account does not belong to this site.");
		return;
	}

}

$thisPage=$_SERVER['PHP_SELF'];

$webPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
$webPage=VWebServer::EncodeDelimiter2($webPage);
$okMsg=$gText['M_SUBMIT_OK'];
$okMsg.=" ".$gText['M_SELECT_PROFILE_FOR_GROUP'];

if (isset($conversionInfo['id'])) 
{

	$retPage="$thisPage?page=".PG_HOME_INFORM."&ret=".$webPage."&message=".rawurlencode($okMsg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
/*
	$retPage="$thisPage?page=".PG_ADMIN_EDIT_CONVERSION."&id=".$conversionInfo['id'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
*/	
	$postUrl=VM_API."?cmd=SET_CONVERSION&return=$retPage";
} else {
	$retPage="$thisPage?page=".PG_HOME_INFORM."&ret=".$webPage."&message=".rawurlencode($okMsg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;
	
	$retPage=VWebServer::EncodeDelimiter1($retPage);
	
	$postUrl=VM_API."?cmd=ADD_CONVERSION&return=$retPage";
}
$postUrl.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;	


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

	if (theForm.server_url.value=='')
	{
		alert("Please enter a value for the \"server_url\" field.");
		theForm.server_url.focus();
		return (false);
	}
	
	return (true);
}

//-->
</script>



<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<?php
if (isset($conversionInfo['id'])) 
{
	$id=$conversionInfo['id'];
	$name=htmlspecialchars($conversionInfo['name']);
	$url=$conversionInfo['url'];
	$code=$conversionInfo['access_key'];
	
print <<<END
<div class="heading1">$name</div>

<input type="hidden" name="id" value="$id">
END;
} else {
	$name=$url='';

	$title=_Text("Add Conversion Server");
	
print <<<END
<div class="heading1">$title</div>

The conversion server allows a user to upload certain types of documents to the server for conversion to a file type supported by Flash.
The conversion server must have been installed.
END;
}

?>


<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w">*<?php echo _Text("Profile Name:")?></td>
	<td colspan=3 class="m_val">
	<input type="text" name="name" size="40" value="<?php echo $name?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*<?php echo _Text("Server Directory URL:")?></td>
	<td colspan=3 class="m_val">
	<input type="text" name="server_url" size="65" value="<?php echo $url?>">
	<div class='m_caption'><?php echo _Text("URL for the Conversion Server folder. e.g. http://myserver.com/convert/")?> </div>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"><?php echo _Text("Authentication Code")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="access_key" size="8" value="<?php echo $code; ?>">
	</td>	
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	<div class='m_caption'><?php echo $gText['M_SELECT_PROFILE_FOR_GROUP']?></div>
	</td>
</tr>

</table>
</form>
