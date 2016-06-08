<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


// This file is not userd anymore

/*
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vvideoserver.php");
require_once("dbobjects/vremoteserver.php");

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ACCOUNT;
if (SID!='')
	$cancelUrl.="&".SID;

$memberId=GetSessionValue('member_id');
$user=new VUser($memberId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}


$group=new VGroup($userInfo['group_id']);
$groupInfo=array();
$group->Get($groupInfo);
if (!isset($groupInfo['id'])) {
	ShowError("Group not found");
	return;
}

$thisPage=$_SERVER['PHP_SELF'];

$accountPage=$thisPage."?page=".PG_ACCOUNT."&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$accountPage.='&'.SID;
$accountPage=VWebServer::EncodeDelimiter2($accountPage);
$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$accountPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);

$postUrl=VM_API."?cmd=SET_USER&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$postUrl.="&".SID;	
*/
?>

<script type="text/javascript">
<!--


function CheckUserForm(theForm) {
	if (theForm.name.value=='')
	{
		alert("Please enter a value for the \"Name\" field.");
		theForm.name.focus();
		return (false);
	}
	if (theForm.webserver_id.value=='')
	{
		alert("Please enter a value for the \"Web Server\" field.");
		theForm.webserver_id.focus();
		return (false);
	}

	return (true);
}


//-->
</script>


<form onSubmit="return CheckUserForm(this)" method="POST" action="<?php echo $postUrl?>" name="hosting_form">
<input type="hidden" name="user_id" value="<?php echo $memberId?>">
<?php

function GetOptions($tbName, $idName, $id2Name, $name, $select)
{
	global $groupInfo;
	$query='';
	$id1=$groupInfo[$idName];
	if ($id1>0)
		$query="id='$id1'";
	$id2=$groupInfo[$id2Name];
	if ($id2>0 && $query=='')
		$query="id='$id2'";
	else if ($id2>0)
		$query.=" OR id='$id2'";
	
	if ($query=='')
		$query="0";
	$options=VObject::GetFormOptions($tbName, $query, $idName, $name, $select, "Default", "0");

	return $options;
}

$webOptions=GetOptions(TB_WEBSERVER, "webserver_id", "webserver2_id", "name", $userInfo['webserver_id']);
$videoOptions=GetOptions(TB_VIDEOSERVER, "videoserver_id", "videoserver2_id", "name", $userInfo['videoserver_id']);
$remoteOptions=GetOptions(TB_REMOTESERVER, "remoteserver_id", "remoteserver2_id", "name", $userInfo['remoteserver_id']);

?>

<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w">*<?php echo $gText['ADMIN_WEB']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $webOptions?>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"><?php echo $gText['ADMIN_VIDEO']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $videoOptions?>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"><?php echo $gText['ADMIN_REMOTE']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $remoteOptions?>
	</td>
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')"></td>
</tr>

</table>
</form>
