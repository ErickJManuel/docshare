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
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vwebserver.php");
require_once('api_includes/common.php');

$logoWidth=MAX_BRAND_LOGO_WIDTH;
$logoHeight=MAX_BRAND_LOGO_HEIGHT;
$imageFormat=_Text("Image will be resized if it is larger than %s pixels.");

$homeIcon="themes/home.gif";
$previewIcon="themes/preview.gif";
$commentIcon="themes/comment.gif";

$memberId=GetSessionValue('member_id');
$userId=$memberId;

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS;
if (SID!='')
	$cancelUrl.="&".SID;

$user=new VUser($userId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

$viewUrl=$GLOBALS['BRAND_URL']."?room=".$userInfo['access_id'];
if (SID!='')
	$viewUrl.="&".SID;

//$backPage=$_SERVER['PHP_SELF']."?brand=".$GLOBALS['BRAND_NAME'];
$backPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_ROOM;
if (SID!='')
	$backPage.="&".SID;
$backPage=VWebServer::EncodeDelimiter2($backPage);
$retPage="index.php?page=".PG_HOME_INFORM."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&ret=".$backPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$retPage=VWebServer::EncodeDelimiter1($retPage);
$postUrl=VM_API."?cmd=SET_ROOM&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];

if (SID!='')
	$postUrl.="&".SID;

$userName=htmlspecialchars($user->GetFullName($userInfo));
$userName=$user->GetFullName($userInfo);
$roomName=htmlspecialchars($userInfo['room_name']);
if ($roomName=='')
	$roomName=htmlspecialchars($gText['M_MY_ROOM']);
$roomDesc=htmlspecialchars($userInfo['room_description']);
$roomDesc=str_replace("\n", "<br>", $roomDesc);

$public=$userInfo['public'];
$userId=$userInfo['id'];
$publicComment=$userInfo['public_comment'];
if ($publicComment=='')
	$publicComment='Y';

?>


<script type="text/javascript">
<!--

function CheckRoomForm(theForm) {
	return (true);
}

//-->
</script>


<div class="list_tools"><a target=<?php echo $GLOBALS['TARGET']?> href="<?php echo $viewUrl?>"><img src="<?php echo $previewIcon?>"><?php echo $gText['M_VIEW_ROOM']?></a></div>

<form enctype="multipart/form-data" onsubmit="return CheckRoomForm(this)" method="POST" action="<?php echo $postUrl?>" name="profile_form">
<?php

print <<<END
<input type="hidden" name="user_id" value="$userId">
END;

?>

<table class="meeting_detail">

<tr>
	<td class="m_key">*Room Name:</td>
	<td colspan="3" class="m_val">
	<input type="text" name="room_name" size="40" value="<?php echo $roomName?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key"><?php echo $gText['MD_DESCRIPTION']?></td>
	<td colspan="3" class="m_val"><textarea id="meet_desc" name="room_description" rows="4" cols="50"><?php echo $roomDesc?></textarea></td>
</tr>

<tr>
	<td class="m_key"> <?php echo _Text("Page Logo")?>:</td>
	<td colspan=3 class="m_val">
	<input type="file" name="logo_file" size="35">
	<input type="checkbox" name="reset_logo" value='1'> <?php echo _Text("Reset to default")?>
	<div class='m_caption'><?php echo $gText['M_UPLOAD_FILE']?> (jpeg, gif or png) <br><?php echo sprintf($imageFormat, $logoWidth."x".$logoHeight);?> </div>
	</div>
	</td>
</tr>

<?php
if (strpos($GLOBALS['MAIN_TABS'], PG_HOME)!==false) {
	$checkY=$checkN='';
	if ($public=='Y')
		$checkY='checked';
	else
		$checkN='checked';
	print <<<END
<tr>
	<td class="m_key"><img src="$homeIcon"> ${gText['MD_PUBLISH']}</td>
	<td colspan="3" class="m_val">
	<input $checkN type="radio" name="public" value="N"><b>${gText['MD_PRIVATE']}:</b> ${gText['MD_PRIVATE_TEXT']}<br>
	<input $checkY type="radio" name="public" value="Y"><b>${gText['MD_PUBLIC']}:</b> ${gText['MD_PUBLIC_TEXT']}<br>
	</td>
</tr>
END;
}

$text1=_Text("Allow public comments");
$text2=_Text("Visitors can post a public comment of this meeting.");
$text3=_Text("Private comments only");
$text4=_Text("Vistors can only send a private comment to me.");

?>
<tr>
	<td class="m_key"><img src="<?php echo $commentIcon?>"> <?php echo $gText['M_COMMENTS']?></td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input <?php if ($publicComment=='Y') echo 'checked';?> type="radio" name="public_comment" value="Y">
	<b><?php echo $text1?>:</b>  <?php echo $text2?></div>
	<div class='sub_val1'><input <?php if ($publicComment=='N') echo 'checked';?> type="radio" name="public_comment" value="N">
	<b><?php echo $text3?>:</b> <?php echo $text4?></div>
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
