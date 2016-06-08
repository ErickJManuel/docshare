<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vgroup.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vteleserver.php");
require_once("dbobjects/vremoteserver.php");
require_once("dbobjects/vvideoserver.php");
require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vconversionserver.php");

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_GROUPS;
if (SID!='')
	$cancelUrl.="&".SID;

if (GetArg('id', $groupId)) {
	$group=new VGroup($groupId);
	$groupInfo=array();
	$group->Get($groupInfo);
	if (!isset($groupInfo['id'])) {
		ShowError("Group not found");
		return;
	}
	if ($groupInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The group does not belong to this site.");
		return;
	}
}

$thisPage=$_SERVER['PHP_SELF'];

//$backPage=$thisPage."?page=".PG_ADMIN_GROUPS."&brand=".$GLOBALS['BRAND_NAME'];
$backPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_GROUPS;
if (SID!='')
	$backPage.="&".SID;
$backPage=VWebServer::EncodeDelimiter2($backPage);

$retPage="$thisPage?page=".PG_HOME_INFORM."&ret=".$backPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);

if (isset($groupInfo['id'])) 
{
	$postUrl=VM_API."?cmd=SET_GROUP&return=$retPage";
} else {
	$postUrl=VM_API."?cmd=ADD_GROUP&return=$retPage";
}
$postUrl.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;	

$webUrl=$thisPage."?page=".PG_ADMIN_ADD_WEB."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$webUrl.="&".SID;	

$webBtn="<a href=\"$webUrl\">${gText['M_ADD']}</a>";

$videoUrl=$thisPage."?page=".PG_ADMIN_ADD_VIDEO."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$videoUrl.="&".SID;	

$videoBtn="<a href=\"$videoUrl\">${gText['M_ADD']}</a>";

$teleUrl=$thisPage."?page=".PG_ADMIN_ADD_TELE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$teleUrl.="&".SID;	

$teleBtn="<a href=\"$teleUrl\">${gText['M_ADD']}</a>";

$remoteUrl=$thisPage."?page=".PG_ADMIN_ADD_REMOTE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$remoteUrl.="&".SID;	

$remoteBtn="<a href=\"$remoteUrl\">${gText['M_ADD']}</a>";

$storageUrl=$thisPage."?page=".PG_ADMIN_ADD_STORAGE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$storageUrl.="&".SID;	

$storageBtn="<a href=\"$storageUrl\">${gText['M_ADD']}</a>";

$conversionUrl=$thisPage."?page=".PG_ADMIN_ADD_CONVERSION."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$conversionUrl.="&".SID;	

$conversionBtn="<a href=\"$conversionUrl\">${gText['M_ADD']}</a>";

/*
$checkFreeAudioYes='checked';
$checkFreeAudioNo='';
if (isset($groupInfo['free_audio_conf'])) {
	if ($groupInfo['free_audio_conf']=='Y')
		$checkFreeAudioYes='checked';
	else
		$checkFreeAudioNo='checked';
}
*/

$format=$gText['M_ENTER_VAL'];

?>


<script type="text/javascript">
<!--


function CheckUserForm(theForm) {
	if (theForm.name.value=='')
	{
		alert("<?php echo sprintf($format, 'Name')?>");
		theForm.name.focus();
		return (false);
	}
	if (theForm.webserver_id.value=='')
	{
		alert("<?php echo sprintf($format, 'Web conference')?>");
		theForm.webserver_id.focus();
		return (false);
	}

	return (true);
}


//-->
</script>


<form onSubmit="return CheckUserForm(this)" method="POST" action="<?php echo $postUrl?>" name="profile_form">
<?php
if (isset($groupInfo['id'])) 
{
	$groupId=$groupInfo['id'];
	$name=htmlspecialchars($groupInfo['name']);
	$description=htmlspecialchars($groupInfo['description']);
	$webServerId=$groupInfo['webserver_id'];
	$videoserver_id=$groupInfo['videoserver_id'];
	$remoteserver_id=$groupInfo['remoteserver_id'];
	$selectedWeb=$groupInfo['webserver_id'];
	$selectedWeb2=$groupInfo['webserver2_id'];
	$selectedVideo=$groupInfo['videoserver_id'];
//	$selectedVideo2=$groupInfo['videoserver2_id'];
	$selectedRemote=$groupInfo['remoteserver_id'];
	$selectedTele=$groupInfo['teleserver_id'];
	$selectedStorage=$groupInfo['storageserver_id'];
	$selectedConversion=$groupInfo['conversionserver_id'];
		
print <<<END
<input type="hidden" name="id" value="$groupId">
<div class="heading1">$name</div>

END;
} else {
	// new group
	$name='';
	$description='';
	$webServerId='';
	$videoserver_id='';
	$remoteserver_id='';
	$selectedWeb='';
	$selectedWeb2='';
	$selectedVideo='';
//	$selectedVideo2='';
	$selectedRemote='';
	$selectedTele='';
	$selectedStorage='';
	$selectedConversion='';

print <<<END
<div class="heading1">${gText['M_ADD_GROUP']}</div>

END;
}

$brandId=$GLOBALS['BRAND_ID'];
$query="`brand_id` = '$brandId' OR `brand_id` = '0'";

$prepend="<option value=\"0\">None</option>";

$webServers=VObject::GetFormOptions(TB_WEBSERVER, $query." OR id='$selectedWeb'", "webserver_id", "name", $selectedWeb);
$webServers2=VObject::GetFormOptions(TB_WEBSERVER, $query." OR id='$selectedWeb2'", "webserver2_id", "name", $selectedWeb2, $prepend);
$videoServers=VObject::GetFormOptions(TB_VIDEOSERVER, $query." OR id='$selectedVideo'", "videoserver_id", "name", $selectedVideo, $prepend);
//$videoServers2=VObject::GetFormOptions(TB_VIDEOSERVER, $query, "videoserver2_id", "name", $selectedVideo2, $prepend);
$remoteServers=VObject::GetFormOptions(TB_REMOTESERVER, $query." OR id='$selectedRemote'", "remoteserver_id", "name", $selectedRemote, $prepend);
$teleServers=VObject::GetFormOptions(TB_TELESERVER, $query." OR id='$selectedTele'", "teleserver_id", "name", $selectedTele, $prepend);
$storageServers=VObject::GetFormOptions(TB_STORAGESERVER, $query." OR id='$selectedStorage'", "storageserver_id", "name", $selectedStorage, $prepend);
$prepend="<option value=\"0\">Default</option>";
$conversionServers=VObject::GetFormOptions(TB_CONVERSIONSERVER, $query." OR id='$selectedConversion'", "conversionserver_id", "name", $selectedConversion, $prepend);

?>

<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w"> *<?php echo _Text("Group Name")?>:</td>
	<td colspan="3" class="m_val">
	<input type="text" name="name" size="20" value="<?php echo $name?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"> <?php echo $gText['MD_DESCRIPTION']?>:</td>
	<td colspan="3" class="m_val"><textarea name="description" rows="2" cols="50"><?php echo $description?></textarea></td>
</tr>

<tr>
	<td class="m_key m_key_w">&nbsp;</td>
	<td colspan="3" class="m_caption"><?php echo _Text("Select hosting profiles for all members of the group. Secondary hosting will be used if the primary hosting is not available.")?></td>
</tr>
<tr>
	<td class="m_key m_key_w"> *<?php echo _Text("Primary Web Conference")?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $webServers?>
	<span class="m_button_s"><?php echo $webBtn?></span>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Secondary Web Conference")?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $webServers2?>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"> <?php echo $gText['ADMIN_VIDEO']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $videoServers?>
	<span class="m_button_s"><?php echo $videoBtn?></span>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"> <?php echo $gText['M_AUDIO_CONF']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $teleServers?>
	<span class="m_button_s"><?php echo $teleBtn?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w" > <?php echo $gText['ADMIN_REMOTE']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $remoteServers?>
	<span class="m_button_s"><?php echo $remoteBtn?></span>
	</td>
</tr>

<?php
if (defined('USE_CONVERSION_SERVER') && constant('USE_CONVERSION_SERVER')!='0') {
?>
<tr>
	<td class="m_key m_key_w" ><?php echo _Text("Conversion Server")?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $conversionServers?>
	<span class="m_button_s"><?php echo $conversionBtn?></span>
	</td>
</tr>
<?php
}
?>

<?php
if (defined('USE_STORAGE_SERVER') && constant("USE_STORAGE_SERVER")=='1') {
?>
<tr>
	<td class="m_key m_key_w" ><?php echo $gText['ADMIN_STORAGE']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $gText['M_SELECT_HOST']?> <?php echo $storageServers?>
	<span class="m_button_s"><?php echo $storageBtn?></span>
	</td>
</tr>
<?php
}
?>

<!--
<tr>
	<td class="m_key m_key_w">Free Teleconference:</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input <?php //echo $checkFreeAudioYes?> type="radio" name="free_audio_conf" value="Y">Allow members to request a free* teleconference number.</div>
	<div class='sub_val1'><input <?php //echo $checkFreeAudioNo?> type="radio" name="free_audio_conf" value="N">Disable free audio conferencing.</div>
	<div class='m_caption'>*Long distance charges may apply.</div>
	</td>
</tr>
-->

<tr>
	<td class="m_key m_key_w">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')"></td>
</tr>

</table>
</form>
