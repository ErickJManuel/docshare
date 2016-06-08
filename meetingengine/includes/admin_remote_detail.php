<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vremoteserver.php");

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
if (SID!='')
	$cancelUrl.="&".SID;
	
$remoteInfo=array();
if (GetArg('id', $remoteId)) {
	$remoteServer=new VRemoteServer($remoteId);
	$remoteServer->Get($remoteInfo);
	if (!isset($remoteInfo['id'])) {
		ShowError("Account not found");
		return;
	}
	if ($remoteInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The account does not belong to this site.");
		return;
	}
} else {
	
	$remoteInfo['name']='';
	$remoteInfo['server_url']='';
	$remoteInfo['client_url']='';
	$remoteInfo['password']='';
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

if (isset($remoteInfo['id'])) 
{
/*
	$retPage="$thisPage?page=".PG_ADMIN_EDIT_REMOTE."&id=".$remoteInfo['id'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
*/
	$postUrl=VM_API."?cmd=SET_REMOTE&return=$retPage";
} else {
	$postUrl=VM_API."?cmd=ADD_REMOTE&return=$retPage";
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
	if (theForm.client_url.value=='')
	{
		alert("Please enter a value for the \"client_url\" field.");
		theForm.client_url.focus();
		return (false);
	}
	
	return (true);
}

//-->
</script>


<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<?php
if (isset($remoteInfo['id'])) 
{
	$id=$remoteInfo['id'];
	$name=htmlspecialchars($remoteInfo['name']);
	
print <<<END
<div class="heading1">$name</div>
<input type="hidden" name="id" value="$id">

END;
} else {
	$name='';

	$downloadUrl='download/prc.zip';
	$awsPage=$thisPage."?page=".PG_ADMIN_AWS;
	if (SID!='')
		$awsPage.="&".SID;
	
print <<<END
<div class="heading1">${gText['M_ADD_REMOTE']}</div>

Remote control requires a remote control server. 
After you have installed the remote control server, create a profile here that links to the server.
<ul>
<li><a target=_blank href="$downloadUrl">Download Remote Control Server</a> (for Windows and Linux)</li>
</ul>

<p>
Alternatively, you can launch a pre-configured server on  
<a target=${GLOBALS['TARGET']} href='$awsPage'>Amazon Web Services (AWS)</a>.

END;
}

?>


<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w">*Profile Name:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="name" size="40" value="<?php echo $name?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*Server URL:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="server_url" size="65" value="<?php echo $remoteInfo['server_url']?>">
	<div class='m_caption'>URL for the Remote Control server connections. </div>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*Client URL:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="client_url" size="65" value="<?php echo $remoteInfo['client_url']?>">
	<div class='m_caption'>URL for the Remote Control client connections. </div>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*Password:</td>
	<td colspan=3 class="m_val">
	<input type="type" name="password" size="8" value="<?php echo $remoteInfo['password']?>">
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
