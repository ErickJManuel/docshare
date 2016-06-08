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
require_once("dbobjects/vviewer.php");

$customBgText="[Custom]";

$previewIcon="themes/preview.gif";
$greyPict="images/greybox.gif";

$memberId=GetSessionValue('member_id');
$userId=$memberId;

$thisPage=$_SERVER['PHP_SELF'];

// $editBrand is defined in admin_viewer.php
if (isset($editBrand))
	$cancelUrl=$thisPage."?page=".PG_ADMIN;
else
	$cancelUrl=$thisPage."?page=".PG_MEETINGS;
$cancelUrl.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$cancelUrl.="&".SID;

$user=new VUser($userId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

if (isset($editBrand)) {
	$viewerId=$gBrandInfo['viewer_id'];
	if ($viewerId==0) {
		ShowError("Meeting viewer is not set for this brand");
		return;
	}		
} else
	$viewerId=$userInfo['viewer_id'];

$viewerInfo=array();

$addViewer=false;

// no custom viewer is set for this brand. create a new viewer record in the database
if ($viewerId==0) {

//	$brand=new VBrand($userInfo['brand_id']);
	$brandViewer=new VViewer($gBrandInfo['viewer_id']);
	
	$brandViewerInfo=array();
	if ($brandViewer->Get($brandViewerInfo)!=ERR_NONE) {
		ShowError($brandViewer->GetErrorMsg());
		return;
	}
	
	$viewerInfo['send_all']=$brandViewerInfo['send_all'];
	$viewerInfo['see_all']=$brandViewerInfo['see_all'];
	$viewerInfo['att_snd']=$brandViewerInfo['att_snd'];
	$viewerInfo['msg_snd']=$brandViewerInfo['msg_snd'];
	$viewerInfo['hand_snd']=$brandViewerInfo['hand_snd'];
	$viewerInfo['back_id']=$brandViewerInfo['back_id'];
//	$viewerInfo['waitmusic_id']=$brandViewerInfo['waitmusic_id'];
	$viewerInfo['waitmusic_url']=$brandViewerInfo['waitmusic_url'];
	
	// leave this filed null so we can use the updated brand viewer logo
//	$viewerInfo['logo_id']='0';
	$viewerInfo['end_url']='';
		
	$addViewer=true;
} else {
// there is already a custom viewer defined for this brand
	$viewer=new VViewer($viewerId);
	$viewer->Get($viewerInfo);
	if ($viewer->Get($viewerInfo)!=ERR_NONE) {
		ShowError($viewer->GetErrorMsg());
		return;
	}	
}

if (isset($editBrand))
	$backPage=$GLOBALS['BRAND_URL']."?page=ADMIN_VIEWER";	
//	$backPage=$thisPage."?page=ADMIN_VIEWER";
else
	$backPage=$GLOBALS['BRAND_URL']."?page=MEETINGS_VIEWER";	
//	$backPage=$thisPage."?page=MEETINGS_VIEWER";	

if (SID!='')
	$backPage.="&".SID;
$backPage=VWebServer::EncodeDelimiter2($backPage);

$retPage="index.php?page=".PG_HOME_INFORM."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&ret=".$backPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
	
//$retPage=VWebServer::EncodeDelimiter1($retPage);

//$retPage=VWebServer::EncodeDelimiter1($retPage);
//$postUrl=VM_API."?cmd=SET_VIEWER&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME'];
$postUrl=VM_API."?cmd=SET_VIEWER";
if (SID!='')
	$postUrl.="&".SID;
	
if ($viewerInfo['send_all']=='Y') {
	$sendAll_Y='checked';
	$sendAll_N='';
} else {
	$sendAll_Y='';
	$sendAll_N='checked';
}

$checkAttSndY='';
$checkAttSndN='';
if ($viewerInfo['att_snd']=='Y')
	$checkAttSndY='checked';
else
	$checkAttSndN='checked';
/*	
$checkMsgSndY='';
$checkMsgSndN='';
if ($viewerInfo['msg_snd']=='Y')
	$checkMsgSndY='checked';
else
	$checkMsgSndN='checked';

$checkHandSndY='';
$checkHandSndN='';
if ($viewerInfo['hand_snd']=='Y')
	$checkHandSndY='checked';
else
	$checkHandSndN='checked';
*/

$brandId=$gBrandInfo['id'];
$query="(brand_id='$brandId' OR brand_id='0') AND (`public`='Y' OR `author_id`='$userId')";

$errMsg=VObject::SelectAll(TB_BACKGROUND, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}
$backgrounds= "<select id='select_bg' name=\"back_id\" onchange=\"return SetBackgroundImg()\">\n";
$backgrounds.="<option value=\"0\">None</option>\n";

//$imageDir="./".DIR_IMAGE;

$picts=array();
$canDelList=array();
$num_rows = mysql_num_rows($result);
$i=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	// this is a public background authored by someone else
	if ($row['public']=='Y' && $row['author_id']!=$userId && $row['author_id']!=0) {
		// make sure the author is from the same brand
		$author=new VUser($row['author_id']);
		$author->GetValue('brand_id', $authBrandId);
		// the author is from a different brand. skip this background
		if ($authBrandId!=$gBrandInfo['id'])
			continue;
	}
	$pictId=$row['onpict_id'];
	$pict=new VImage($pictId);
	$pict->GetValue('file_name', $pictFile);
//	$picts[$row['id']]=$imageDir.$pictFile;
	$pictUrl=VImage::GetFileUrl($pictFile);
	$picts[$row['id']]=$pictUrl;
	if ($row['id']==$viewerInfo['back_id']) {
		$selected='selected';
		$backPict="<img alt=\"$pictFile\" id=\"back_img\" src=\"$pictUrl\">";
	} else
		$selected='';
	if ($row['author_id']==$userId || ($row['brand_id']==$userInfo['brand_id'] && $userInfo['permission']=='ADMIN')) {
		$name=$customBgText." ".$row['name'];
		$canDelList[$row['id']]='Y';
	} else {
		$name=$row['name'];
		$canDelList[$row['id']]='N';
	}
	
	$backgrounds.="<option value=\"".$row['id']."\" $selected >".htmlspecialchars($name)."</option>\n";
	$i++;
}
$backgrounds.="</select>\n";

if (!isset($backPict) || $backPict=='')
	$backPict="<img alt=\"$greyPict\" id=\"back_img\" src=\"$greyPict\">";

$deleteMsg=_Text("Do you want to delete this background?");

?>


<script type="text/javascript">
<!--

function CheckForm(theForm) {
	return (true);
}

var imgList=new Array();
var greyImg= "<?php echo $greyPict?>";

var delList=new Array();

<?php
	foreach($picts as $key=>$value) {
		echo "imgList[$key]=\"$value\";\n";
	}
	foreach($canDelList as $key=>$value) {
		echo "delList[$key]=\"$value\";\n";
	}
?>
function SetBackgroundImg()
{
	var selectElm=document.getElementById('select_bg');
	var imgElm=document.getElementById('back_img');

	imgElm.alt=selectElm.value;
	if (selectElm.value!='0')
		imgElm.src=imgList[selectElm.value];
	else
		imgElm.src=greyImg;
	
	if (selectElm.value!='0' && delList[selectElm.value]=='Y') {
		document.getElementById('del_btn').style.display='inline';
	} else {
		document.getElementById('del_btn').style.display='none';
	}

	return true;
}


//-->
</script>

<?php
if (isset($editBrand))
	echo "Set default meeting viewer properties.\n";
?>
 
<form onsubmit="return CheckForm(this);" enctype="multipart/form-data" method="POST" action="<?php echo $postUrl?>" name="profile_form">
<input type="hidden" name="id" value="<?php echo $viewerId?>">
<input type="hidden" name="return" value="<?php echo $retPage?>">
<input type="hidden" name="brand" value="<?php echo $GLOBALS['BRAND_NAME']?>">
<input type="hidden" name="brandUrl" value="<?php echo $GLOBALS['BRAND_URL']?>">

<?php
if (isset($editBrand)) {
	echo "<input type='hidden' name='public_background' value='1'>\n";
}

/*
if ($addViewer) {
//<input type="hidden" name="logo_id" value="${viewerInfo['logo_id']}">
//<input type="hidden" name="waitmusic_url" value="${viewerInfo['waitmusic_url']}">
print <<<END
<input type="hidden" name="att_snd" value="${viewerInfo['att_snd']}">
<input type="hidden" name="msg_snd" value="${viewerInfo['msg_snd']}">
<input type="hidden" name="hand_snd" value="${viewerInfo['hand_snd']}">
END;

}
*/

$imageFormat=_Text("Image will be resized to %s pixels.");
?>

<table class="meeting_detail">

<tr>
	<td class="m_key">Logo:</td>
	<td colspan=3 class="m_val">
	<input type="file" name="logo_file" size="45">
	<input type="checkbox" name="reset_logo" value='1'><?php echo _Text("Reset to default")?>
	<div class='m_caption'><?php echo $gText['M_UPLOAD_FILE']?> (jpeg, gif, png) <?php echo sprintf($imageFormat, "116x30")?></div>
	</td>
</tr>

<tr>
	<td class="m_key"><?php echo $gText['M_BACKGROUND']?>:</td>
	<td colspan="3" class="m_val">
	<?php echo $backgrounds;?>
	<span id='back_pict'><?php echo $backPict;?></span>&nbsp;&nbsp;
	<input id='del_btn' onclick="return MyConfirm('<?php echo $deleteMsg?>')" type='submit' name='delete_background' value='Delete'><p>
	<div><b><?php echo _Text("Add background")?>:</b> <input type="file" name="background_file" size="45"></div>
	<div style="margin-left:105px;" class='m_caption'><?php echo $gText['M_UPLOAD_FILE']?> (jpeg, gif, png) <?php echo sprintf($imageFormat, "800x600")?></div>
	</td>
</tr>

<?php

if (!isset($editBrand)) {
	$seeAll_Y='';
	if ($viewerInfo['see_all']=='Y')
		$seeAll_Y='checked';
	$seeAll_N='';
	if ($viewerInfo['see_all']=='N')
		$seeAll_N='checked';

	print <<<END
<tr>
	<td class="m_key">${gText['M_ATTENDEE']}:</td>
	<td colspan="3" class="m_val">
	<input $seeAll_Y type="radio" name="see_all" value="Y" onclick="return SetElemHtml('send_all', 'send_all')">${gText['M_SEE_ALL']}<br>
	<div id="send_all">
	<input $sendAll_Y type="radio" name="send_all" value="Y">${gText['M_SEND_ALL']}<br>
	<input $sendAll_N type="radio" name="send_all" value="N">${gText['M_SEND_HOST']}
	</div>
	<input $seeAll_N type="radio" name="see_all" value="N" onclick="return SetElemHtml('send_all', '')">${gText['M_SEE_HOST']}
	</td>
</tr>
END;
}
?>


<tr>
	<td class="m_key"><?php echo _Text("Alert Sound")?>:</td>
	<td colspan="3" class="m_val">
	<div><?php echo _Text("Play sound when an attendee joins or a message arrives")?>:
	<input <?php echo $checkAttSndY?> type="radio" name="att_snd" value="Y"><?php echo $gText['M_YES']?>
	<input <?php echo $checkAttSndN?> type="radio" name="att_snd" value="N"><?php echo $gText['M_NO']?>
	</div>
	</td>
</tr>
<!--
<tr>
	<td class="m_key">Music:</td>
	<td colspan="3" class="m_val">MP3 URL:
	<input type="text" name="waitmusic_url" size="60" value="<?php echo $viewerInfo['waitmusic_url']?>">
	<div class='m_caption'>Play the music while waiting for a meeting to start</div>
	</td>
</tr>
-->

<?php
	
	
if (isset($editBrand)) {
	
	if (!defined('ENABLE_WINDOWS_CLIENT') || constant('ENABLE_WINDOWS_CLIENT')=='1') {
	
	$checkAll=$checkJava='';
	if (isset($viewerInfo['presenter_client']) && $viewerInfo['presenter_client']=='JAVA')
		$checkJava='checked';
	else
		$checkAll='checked';

	$presText=_Text("Presenter Client");
	$text1=_Text("Enable both Windows and Java Presenter Client");
	$text2=_Text("Enable only Java Presenter Client");
	$capText=_Text("See details");
	$capLink=$GLOBALS['BRAND_URL']."?page=".PG_HOME_DOWNLOAD;
	$target=$GLOBALS['TARGET'];
	print <<<END
<tr>
	<td class="m_key">$presText:
	<div class='m_caption'><a target=$target href='$capLink'>$capText</a></div>
	</td>
	<td colspan="3" class="m_val">
	<input $checkAll type="radio" name="presenter_client" value="">$text1<br>
	<input $checkJava type="radio" name="presenter_client" value="JAVA">$text2
	</td>
</tr>

END;
	}
}

?>

<tr>
	<td class="m_key"><?php echo _Text("Exit Page")?>:</td>
	<td colspan="3" class="m_val"><?php echo _Text("Redirect attendees to this page when a meeting ends")?>:<br>
	<input type="text" name="end_url" size="70" autocorrect="off" autocapitalize="off" value="<?php echo $viewerInfo['end_url']?>">
	<div class='m_caption'><?php echo _Text("The default exit page is the meeting page, which allows attendees to post comments.")?></div>
	</td>
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
<!--	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')"> -->
	</td>
</tr>

</table>
</form>

<script type="text/javascript">
<!--	
var htmlText=new Array();
htmlText['send_all']= GetElemHtml('send_all');

function SetElemHtml(elemId, textId)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		if (textId!='')
			elem.innerHTML=htmlText[textId];
		else
			elem.innerHTML='';
		
	}
	return true;
}

<?php 
if ($viewerInfo['see_all']=='Y')
	echo "SetElemHtml('send_all', 'send_all');";
else
	echo "SetElemHtml('send_all', '');";

if ($viewerInfo['back_id']>0 && $canDelList[$viewerInfo['back_id']]=='Y')
	echo "SetElemDisplay('del_btn', 'inline');";
else
	echo "SetElemDisplay('del_btn', 'none');";

?>


//-->
</script>
