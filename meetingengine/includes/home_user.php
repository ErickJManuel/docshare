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
require_once("dbobjects/vimage.php");

$brandUrl=$gBrandInfo['site_url'];

// $edit should be set in the file including this page
if (isset($edit) && $edit) {
	
	//$user and $userInfo should be set in the file including this page
	if (!isset($userInfo['access_id'])) {
		ShowError("User not set");
		return;
	}
	
	$previewIcon="themes/preview.gif";
	$editIcon="themes/edit.gif";
	if ($edit)
		$editBtn="<img src=\"$editIcon\">";
	else
		$editBtn='';

	$viewUrl=$brandUrl."?user=".$userInfo['access_id'];
	if (SID)
		$viewUrl.="&".SID;
	


} else {
	
	// view the profile of the user
	$edit=false;
	if (!GetArg('user', $userLogin) || $userLogin=='' || $userLogin=='0') {
		ShowError("User id or login not set");
		return;
	}
	
	// 'user' can be either the login or access_id. match both
	$query="brand_id='".$GLOBALS['BRAND_ID']."'";
	$query.=" AND (access_id = '".addslashes($userLogin)."' OR LOWER(login)= '".addslashes(strtolower($userLogin))."')";

	$userInfo=array();
//	$errMsg=VObject::Find(TB_USER, 'access_id', $userId, $userInfo);
	$errMsg=VObject::Select(TB_USER, $query, $userInfo);

	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	if (!isset($userInfo['id'])) {
		ShowError("User id not found.");
		return;
	}

	$user=new VUser($userInfo['id']);
	if ($user->Get($userInfo)!=ERR_NONE) {
		ShowError($user->GetErrorMsg());
	}
	
	$editBtn='';
	$postUrl='';
	$userNameHtm='';
	$userTitleHtm='';
	$addressHtm='';
	$userPhoneHtm='';
	$userEmailHtm='';
	$userPictHtm="";

}

$login=$userInfo['login'];
$id=$userInfo['id'];
$fullName=htmlspecialchars($user->GetFullName($userInfo));
$firstName=htmlspecialchars($userInfo['first_name']);
$lastName=htmlspecialchars($userInfo['last_name']);
$email=$userInfo['email'];
$login=$userInfo['login'];
$group=$userInfo['group_id'];
//$perm=$userInfo['permission'];
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

if ($edit) {
	$retPage="account.php"."?".rand();
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);

	$postUrl=VM_API."?cmd=SET_USER&user_id=".$userInfo['id']."&return=$retPage";
	if (SID!='')
		$postUrl.="&".SID;

	$userNameHtm="<div class=\"edit_user\">${gText['M_FIRSTNAME']}: <input type=\"text\" name=\"first_name\" size=\"24\" value=\"$firstName\"><br>";
	$userNameHtm.="${gText['M_LASTNAME']}: <input type=\"text\" name=\"last_name\" size=\"24\" value=\"$lastName\"></div>";
	$userNameHtm=str_replace("'", "\'", $userNameHtm);
	
	$userTitleHtm="<div class=\"edit_user\">${gText['M_TITLE']}: <input type=\"text\" name=\"title\" size=\"24\" value=\"$title\"><br>";
	$userTitleHtm.="${gText['M_ORG']}: <input type=\"text\" name=\"org\" size=\"24\" value=\"$org\"></div>";
	$userTitleHtm=str_replace("'", "\'", $userTitleHtm);
		
	$addressHtm="<div class=\"edit_user\">${gText['M_STREET']}: <input type=\"text\" name=\"street\" size=\"30\" value=\"$street\"></div>";
	$addressHtm.="<div class=\"edit_user\">${gText['M_CITY']}: <input type=\"text\" name=\"city\" size=\"12\" value=\"$city\">&nbsp;";
	$addressHtm.="${gText['M_STATE']}: <input type=\"text\" name=\"state\" size=\"10\" value=\"$state\"></div>";
	$addressHtm.="<div class=\"edit_user\">${gText['M_ZIP']}: <input type=\"text\" name=\"zip\" size=\"5\" value=\"$zip\">&nbsp;";
	$addressHtm.="${gText['M_COUNTRY']}: <input type=\"text\" name=\"country\" size=\"10\" value=\"$country\"></div>";
	$addressHtm=str_replace("'", "\'", $addressHtm);

	$userPhoneHtm="<div class=\"edit_user\">${gText['M_PHONE']}: <input type=\"text\" name=\"phone\" size=\"15\" value=\"$phone\"><br>";
	$userPhoneHtm.="${gText['M_MOBILE']}: <input type=\"text\" name=\"mobile\" size=\"15\" value=\"$mobile\"><br>";
	$userPhoneHtm.="${gText['M_FAX']}: <input type=\"text\" name=\"fax\" size=\"15\" value=\"$fax\"></div>";
	$userPhoneHtm=str_replace("'", "\'", $userPhoneHtm);
	
	$userEmailHtm="<div class=\"edit_user\">${gText['M_EMAIL']}: <input type=\"email\" name=\"email\" size=\"32\" value=\"$email\"  autocorrect=\"off\" autocapitalize=\"off\">&nbsp;";
	$userEmailHtm=str_replace("'", "\'", $userEmailHtm);
	
	$theFormat=_Text("Max. size: %s");
	$imageFormat=_Text("Image will be resized to %s pixels");
	$theText=$gText['M_UPLOAD_FILE']." (jpg, gif, png.) ".sprintf($theFormat, "1280x1024")." ".sprintf($imageFormat, PICT_SIZE."x".PICT_SIZE);
	
	$userPictHtm="<div><input type=\"file\" name=\"pict_file\" size=\"12\"></div>";
	$userPictHtm.="<div class=\"m_caption\">".htmlspecialchars($theText)."</div>";

	if ($firstName=='') {
		$firstName="[${gText['M_FIRSTNAME']}]";
	}
	if ($lastName=='') {
		$lastName="[${gText['M_LASTNAME']}]";
	}
	if ($title=='') {
		$title="[${gText['M_TITLE']}]";
	}
	if ($org=='') {
		$org="[${gText['M_ORG']}]";
	}
	if ($street=='') {
		$street="[${gText['M_STREET']}]";
	}
	if ($city=='') {
		$city="[${gText['M_CITY']}]";
	}
	if ($state=='') {
		$state="[${gText['M_STATE']}]";
	}
	if ($country=='') {
		$country="[${gText['M_COUNTRY']}]";
	}
	if ($zip=='') {
		$zip="[${gText['M_ZIP']}]";
	}
	if ($email=='') {
		$email="[${gText['M_EMAIL']}]";
	}
	if ($phone=='') {
		$phone="[${gText['M_PHONE']}]";
	}
	if ($fax=='') {
		$fax="[${gText['M_FAX']}]";
	}
	if ($mobile=='') {
		$mobile="[${gText['M_MOBILE']}]";
	}
}

$userPict='';
if ($userInfo['pict_id']>0) {
	$pict=new VImage($userInfo['pict_id']);
	if ($pict->GetValue('file_name', $pictFile)!=ERR_NONE) {
		ShowError ($pict->GetErrorMsg());
	} else {
//		$userPict=DIR_IMAGE.$pictFile;
		$userPict=VImage::GetFileUrl($pictFile);
	}
} else {
	$userPict="themes/person.jpg";	
}


$cszStr=$city;
if ($cszStr!='')
	$cszStr.=', ';
$cszStr.=$state;
if ($cszStr!='')
	$cszStr.=' ';
$cszStr.=$zip;


$roomUrl=$brandUrl."?room=".$userInfo['access_id'];
$roomUrlStr=BreakText($roomUrl, 50);
$vcardIcon="themes/vcard.gif";

$vcardUrl=VM_API."?cmd=GET_VCARD&user_id=".$userInfo['id'];
$vcardBtn="<img src=\"$vcardIcon\">&nbsp;".$gText['M_DOWNLOAD_VCARD'];

$logoFile='';
if ($userInfo['logo_id']!='0' && !GetArg('page', $arg)) {
	$logo=new VImage($userInfo['logo_id']);
	if ($logo->GetValue('file_name', $logoFile)!=ERR_NONE) {
		ShowError ($logo->GetErrorMsg());
	} else {
		$logoFile=VImage::GetFileUrl($logoFile);
	}
}

if ($logoFile!='') {
print <<<END
<script type="text/javascript">
<!--
	document.getElementById('logo_pict').setAttribute("src", "$logoFile");
	document.getElementById('logo_link').setAttribute("href", "javascript:void(0)");
//-->
</script>
END;
}
?>
<!--
<link href="themes/meetings.php?theme=<?php echo $GLOBALS['THEME']?>" rel="stylesheet" type="text/css">
-->
<script type="text/javascript">
<!--

var htmlText=new Array();
htmlText['user_name']= "<?php echo addslashes($userNameHtm)?>";
htmlText['user_title']= "<?php echo addslashes($userTitleHtm)?>";
htmlText['user_address']= "<?php echo addslashes($addressHtm)?>";
htmlText['user_phone']= "<?php echo addslashes($userPhoneHtm)?>";
htmlText['user_email']= "<?php echo addslashes($userEmailHtm)?>";
htmlText['user_pict']= "<?php echo addslashes($userPictHtm)?>";

function SetElemHtml(elemId, textId)
{
	var elem=document.getElementById(elemId);

	if (elem) {
	
		if (elem.innerHTML=='' && textId!='')
			elem.innerHTML=htmlText[textId];
		else
			elem.innerHTML='';
		
	}
	return false;

}

//-->
</script>

<?php
if ($edit) {
	$target=$GLOBALS['TARGET'];
	echo "<div class=\"list_tools\"><a target='$target' href=\"$viewUrl\"><img src=\"$previewIcon\">${gText['M_VIEW_PROFILE']}</a></div>";
}
?>

<center>
<div class="meeting_frame_top">
<div class="meeting_frame_bot">
<?php
if ($edit) 
	echo "<form enctype=\"multipart/form-data\" method=\"POST\" action=\"$postUrl\" name=\"updateuser_form\">";
	echo "<input type=\"hidden\" name=\"user_id\" value=\"${userInfo['id']}\">";
?>
<table width='100%'>

<tr>
<td id='user_info_l'><img width='<?php echo PICT_SIZE?>' height='<?php echo PICT_SIZE?>' alt="My Picture" src="<?php echo $userPict?>">
<?php 
if ($edit) {
	$resetText=_Text("Reset picture");
	echo $userPictHtm;
	echo "<div><input type='checkbox' name='reset_pict' value='1'>$resetText</div>\n";
}
?>

</td>

<td id='user_info_r'>

<div id="user_name"><?php echo $firstName?> <?php echo $lastName?>&nbsp;
<?php
if ($edit) 
	echo "<a href=\"javascript:void\" onclick=\"return SetElemHtml('edit_user_name', 'user_name')\">$editBtn</a>";
?>
</div>

<div id="edit_user_name"></div>

<div id="user_title"><?php echo $title?><br><?php echo $org?>&nbsp;
<?php if ($edit) 
	echo "<a href=\"javascript:void\" onclick=\"return SetElemHtml('edit_user_title', 'user_title')\">$editBtn</a>";
?>
</div>
<div id="edit_user_title"></div>

<div id="user_address"><?php echo $street?>&nbsp;
<?php if ($edit) 
	echo "<a href=\"javascript:void\" onclick=\"return SetElemHtml('edit_user_address', 'user_address')\">$editBtn</a>";
?>
</div>
<div id="user_city"><?php echo $cszStr?></div>
<div id="user_country"><?php echo $country?></div>
<div id="edit_user_address"></div>

<div id="user_phone">
<?php
$phoneItems='';
if ($phone!='')
	echo "<span class='phone_key'>${gText['M_PHONE']}</span>: $phone";
if ($edit) {
	echo "&nbsp;";
	echo "<a href=\"javascript:void\" onclick=\"return SetElemHtml('edit_user_phone', 'user_phone')\">$editBtn</a>";
}

if ($mobile!='') {
	if ($phone!='')
		echo "<br>\n";
	echo "<span class='phone_key'>${gText['M_MOBILE']}</span>: $mobile";
}

if ($fax!='') {
	if ($phone!='' || $mobile!='')
		echo "<br>\n";
	echo "<span class='phone_key'>${gText['M_FAX']}</span>: $fax";
}

?>
</div>
<div id="edit_user_phone"></div>


<?php 
if ($userInfo['email']!='')
	echo "<div id=\"user_email\"><a href=\"mailto:$email\"?>$email</a>&nbsp;";
else
	echo "<div id=\"user_email\">$email&nbsp;";
	
if ($edit) 
	echo "<a href=\"javascript:void\" onclick=\"return SetElemHtml('edit_user_email', 'user_email')\">$editBtn</a>";
?>
</div>
<div id="edit_user_email"></div>

<div id="user_room"><?php echo $gText['M_ROOM_ADDRESS']?><br><a target=<?php echo $GLOBALS['TARGET']?> href="<?php echo $roomUrl?>"?><?php echo $roomUrlStr?></a></div>

<?php

if (!IsIPadUser() && !IsIPhoneUser()) {
	print <<<END
<div id="user_vcard"><a href="$vcardUrl"?>$vcardBtn</a></div>

END;
}

if ($edit) {

	echo "<div>\n";
	echo "<input type=\"submit\" name=\"submit\" value=\"${gText['M_SAVE']}\">&nbsp;\n";
	echo "<input type=\"reset\" name=\"reset\" value=\"${gText['M_RESET']}\" ></td>\n";
	echo "</div>\n";
}
?>
</td>
</tr>
</table>
<?php if ($edit) {
	echo "</form>";
}
?>
</div>
</div>

</center>


<?php 
if ($edit) {
	$format=_Text("Click %s to make changes."); //_Comment: as in click a button to make changes
	$ftext=sprintf($format, $editBtn);
	echo "<div id='edit_prof_msg'>$ftext</div>";
}
?>