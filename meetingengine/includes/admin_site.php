<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vwebserver.php");
//require_once("dbobjects/vsite.php");
require_once("dbobjects/vuser.php");
require_once('api_includes/common.php');
require_once('dbobjects/vversion.php');

$silent=true;
require_once('vinstall/vversion.php');
$silent=false;


$logoWidth=MAX_BRAND_LOGO_WIDTH;
$logoHeight=MAX_BRAND_LOGO_HEIGHT;

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN;
if (SID!='')
	$cancelUrl.="&".SID;

$brandId=$GLOBALS['BRAND_ID'];
$brand=new VBrand($brandId);

//$brandInfo=array();
//$brand->Get($brandInfo);

if (isset($_POST['set_version'])) {
	
	if (GetArg('auto_update', $arg) && $arg=='Y') {
		$newInfo=array();
		$newInfo['auto_update']='Y';
		$brand->Update($newInfo);
		$gBrandInfo['auto_update']='Y';
	} elseif ($gBrandInfo['auto_update']=='Y') {
		$newInfo=array();
		$newInfo['auto_update']='N';
		$brand->Update($newInfo);
		$gBrandInfo['auto_update']='N';
	}
	
	if (GetArg('beta_site', $arg) && $arg=='Y') {
		$new1Info=array();
		$new1Info['site_level']='beta';
		$brand->Update($new1Info);
		$gBrandInfo['site_level']='beta';
		$GLOBALS['SITE_LEVEL']='beta';
	} elseif ($gBrandInfo['site_level']=='beta') {
		$new1Info=array();
		$new1Info['site_level']='';
		$brand->Update($new1Info);
		$gBrandInfo['site_level']='';
		$GLOBALS['SITE_LEVEL']='';
	}
	
	if (GetArg('version_number', $versionNumber) && $versionNumber!=$version) {
		$endPageFile='admin_site_end.php';
		$procTxt=_Text("Processing...");
print <<<END
<div class='inform' id='message'>$procTxt<br><br></div>

END;
		return;
		
	}
	
}

$linkExCode="<a target='_blank' href='http://mylink.com'><img src='http://mypict.com'></a>";


$thisPage=$_SERVER['PHP_SELF'];
/*
$retPage=$thisPage."?page=".PG_ADMIN_SITE."&=".rand();
if (SID!='')
	$retPage.="&".SID;*/

$backPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_SITE;
if (SID!='')
	$backPage.="&".SID;
$backPage=VWebServer::EncodeDelimiter2($backPage);
$retPage="$thisPage?page=".PG_HOME_INFORM."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&ret=".$backPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
$postUrl=VM_API."?cmd=SET_BRAND&return=$retPage";
if (SID!='')
	$postUrl.="&".SID;	
	
$editUrl=$thisPage."?page=".PG_ADMIN_PAGE;
if (SID!='')
	$editUrl.="&".SID;	

$editIcon=SITE_URL."themes/edit.gif";
$editBtn="<img src=\"$editIcon\">&nbsp;".$gText['M_EDIT_PAGE'];

//echo "Brand: ".$gBrandInfo['name'];

// custom theme name starts with _
$themeName=$gBrandInfo['theme'];
if (strlen($themeName)>0 && $themeName{0}=='_') {
	$customTheme=substr($themeName, 1); // strip '_'
	$themeName='[custom]'; // select the custom field from the option list
	$displayCustomTheme='inline';
} else {
	$customTheme='';
	$displayCustomTheme='none';
}

$themePicts=array();
$themes=GetOptionsFromDir("themes/", 'theme', $themeName, $themePicts);
$locales=GetLocaleOptions('locale', $gBrandInfo['locale']);

$timezones=GetTimeZones($gBrandInfo['time_zone']);

$query="brand_id='$brandId' AND permission='ADMIN' AND login<>'_root'";
$selectId=$gBrandInfo['admin_id'];
$admins=VObject::GetFormOptions(TB_USER, $query, "admin_id", "login", $selectId);

$checkHelpY=$checkHelpN='';
if ($gBrandInfo['custom_help']=='Y')
	$checkHelpN='checked';
else
	$checkHelpY='checked';

$checkNotifyY='';
$checkNotifyN='';
if ($gBrandInfo['notify']=='Y')
	$checkNotifyY='checked';
else
	$checkNotifyN='checked';
	
$checkSigninY='';
$checkSigninN='';
if ($gBrandInfo['hide_signin']=='N')
	$checkSigninY='checked';
else
	$checkSigninN='checked';

$customSigninUrl=$gBrandInfo['custom_signin_url'];
$signinPage=$GLOBALS['BRAND_URL']."?signin";

$checkEmbedY='';
$checkEmbedN='';
if ($gBrandInfo['embed_site']=='Y')
	$checkEmbedY='checked';
else
	$checkEmbedN='checked';
		
$checkHomeY='checked';
$checkHomeN='';
if ($gBrandInfo['custom_tabs']!='' && strpos($gBrandInfo['custom_tabs'], "HOME")===false) {
	$checkHomeY="";
	$checkHomeN="checked";
}

$checkShareY='';
$checkShareN='';
if (!isset($gBrandInfo['share_it']) || $gBrandInfo['share_it']=='Y')
	$checkShareY='checked';
else
	$checkShareN='checked';
		

$hasTrial=false;
$offerings=$gBrandInfo['offerings'];
// if the offering starts with T, trial is allowed

if ($offerings{0}=='T' || $gBrandInfo['trial_signup']=='Y') {
	$hasTrial=true;
}

$docPage=$GLOBALS['BRAND_URL']."?page=HELP";
if (SID!='')
	$docPage.="&".SID;

$unknownImg="themes/unknown_theme.png";

$logoLinkY='';
$logoLinkN='';
if ($gBrandInfo['logo_link']=='')
	$logoLinkN='checked';
else
	$logoLinkY='checked';
	
$historyPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_VERSIONS;
if (SID!='')
	$historyPage.="&".SID;
	
function GetVersionOptions($versionNumber)
{
	VObject::Select(TB_VERSION, "number='$versionNumber'", $verInfo);
	$minNum=$verInfo['rollback_number'];
	$brandId=$GLOBALS['BRAND_ID'];
	$query="((brand_id='$brandId' OR brand_id='0') AND (number>='$minNum')";
	if ($GLOBALS['SITE_LEVEL']=='')
		$query.=" AND (type='final')";
	elseif ($GLOBALS['SITE_LEVEL']=='beta')
		$query.=" AND (type='final' OR type='beta')";

	if ($versionNumber!='') {
		$query.=") OR (number='$versionNumber')";			
	} else
		$query.=")";

	$errMsg=VObject::SelectAll(TB_VERSION, $query, $result, 0, 0, '*', 'number', true);
	if ($errMsg!='') {
		return '';
	}

	$str="<select name=\"version_number\">\n";

	$num_rows = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($row['type']!='final')
			$versionStr=$row['number']." ".$row['type'];
		else
			$versionStr=$row['number'];

		if ($versionNumber==$row['number'])
			$str.="<option value=\"".$row['number']."\" selected>".$versionStr." (Current)</option>\n";
		else if ($versionNumber<$row['number'])
			$str.="<option value=\"".$row['number']."\">".$versionStr." (Upgrade)</option>\n";
		else 
			$str.="<option value=\"".$row['number']."\">".$versionStr." (Rollback)</option>\n";
	}
	$str.="</select>\n";
	return $str;	
}	

function GetOptionsFromDir($theDir, $optName, $selectedOpt, &$themePicts)
{
	global $unknownImg;
	
	$opts="<select id=\"select_style\" name=\"$optName\" onchange=\"return ChangeStyle();\">";

	if ($dh = @opendir($theDir)) { 

		while (($file = readdir($dh)) !== false) { 
			if ($file=="." || $file=="..")
				continue;
			
			// skip custom templates, which start with '_'
			if ($file{0}=='_')
				continue;
				
			if (is_dir($theDir.$file)) {
				
				if (file_exists($theDir.$file."/sample.png"))
					$themePicts[$file]=$theDir.$file."/sample.png";
				else
					$themePicts[$file]=$unknownImg;
					
				if ($file==$selectedOpt)
					$selected='selected';
				else
					$selected='';
				$opts.="<option $selected value='$file'>$file</option>";
			}
		}
	}
	if ($selectedOpt=='[custom]')
		$opts.="<option selected value='[custom]'>[custom]</option>";
	else
		$opts.="<option value='[custom]'>[custom]</option>";
	
	$opts.="</select>";
	return $opts;

}


$text=sprintf(_Text("Do you want to disable the default sign-in page?\\nUse '%s' to sign in again."), $signinPage);
?>

<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.site_url.value=='')
	{
		alert(" <?php echo sprintf($gText['M_ENTER_VAL'], 'URL')?>");
		theForm.site_url.focus();
		return (false);
	}
	
	if (theForm.theme.value=='[custom]') {
		if (theForm.custom_theme.value=='')
		{
			alert(" <?php echo sprintf($gText['M_ENTER_VAL'], 'Theme name')?>");
			theForm.custom_theme.focus();
			return (false);
		}	
	}

	if (theForm.enable_signin[1].checked) {
		var ok=confirm(" <?php echo $text?>");
		if (!ok)
			return false;
	}

	return (true);
}


function ChangeStyle()
{
	var elem=document.getElementById('select_style');
	
	if (elem.value=='[custom]') {
		document.getElementById('custom_style').style.display='inline';
	} else {
		document.getElementById('custom_style').style.display='none';
		SetThemePict();
	}
	return true;
}

function showSmtp(showIt)
{
	var elem=document.getElementById('smtp-settings');
	if (showIt)
		elem.style.display='inline';
	else
		elem.style.display='none';
}

function sendTestMail(form)
{
	if (form.smtp_server.value=='')
	{
		alert("Enter a value for SMTP server name.");
		form.smtp_server.focus();
		return (false);
	}
}

var imgList=new Array();

<?php

foreach($themePicts as $key=>$value) {
	echo "imgList['$key']=\"$value\";\n";
}

?>

function SetThemePict()
{
	var selectElm=document.getElementById('select_style');
	var imgElm=document.getElementById('theme_img');

	if (selectElm.value!='[custom]')
		imgElm.src=imgList[selectElm.value];
	else
		imgElm.src='<?php echo $unknownImg?>';
		

	return true;
}


function CheckVersionForm(theForm) {
	var currVersion='<?php echo $version?>';
	if (theForm.version_number.value!=currVersion) {
		var ret=confirm("Do you want to change the version to '"+theForm.version_number.value+"'?");
		return (ret);
	}
	return true;
}
//-->
</script>


<?php
/*
function PrintMsg($msg) {
	// fill the buffer with extra data so flush will work on Win
	echo "<div class='message'>$msg</div>\n".str_pad(" ", 512);
	flush();

}

if (isset($_POST['set_version'])) {
	
	$configFile='site_config.php';

	GetArg('version_number', $versionNumber);

	// find the management server source url of this version
	$errMsg=VObject::Select(TB_VERSION, "number='$versionNumber'", $verInfo);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
		
	if (!isset($verInfo['id'])) {
		ShowError("Version not found.");
		return;
	}

	$srcUrl=$verInfo['source_url'];

	$query="brand_id='$brandId'";
	$errMsg=VObject::SelectAll(TB_WEBSERVER, $query, $result);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}

	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

		$webserver=new VWebServer($row['id']);
		$serverInfo=array();
		$errMsg=$webserver->Get($serverInfo);
		
		if ($errMsg!='') {
			ShowError($errMsg);
			return;
		}
		
		PrintMsg("Updating ".$serverInfo['url']."...");

		$installUrl=$serverInfo['url']."vinstall.php";
		$data="server=".$srcUrl.
			"&brand=".$brandName."&win_title=".rawurlencode($gBrandInfo['product_name']).
			"&login=".$serverInfo['login']."&password=".$serverInfo['password'].
			"&version=".$versionNumber.
			"&no_update=1&install=1&silence=1";

		$content=HTTP_Request($installUrl, $data, 'POST', 30);
		
		if ($content==false) {
			PrintMsg("Couldn't get respose from ${serverInfo['url']}");
		} elseif ($content!='OK') {
			PrintMsg("$content");
		} else {
			$winfo=array();
			$winfo['installed_version']=$versionNumber;
			$webserver->Update($winfo);	
			PrintMsg("Update completed.");
		}
		
		echo ("<br>");
	}
	
	$retUrl=$GLOBAL['BRAND_URL']."?page=ADMIN_SITE&rand=".rand();
	if (SID!='')
		$retUrl.="&".SID;
	echo ("<a href='$retUrl'>Return</a>");
	
	return;
}
*/

$versionOpts=GetVersionOptions($version);

$query="number>'$version'";
if ($GLOBALS['SITE_LEVEL']=='')
	$query.=" AND (type='final')";
elseif ($GLOBALS['SITE_LEVEL']=='beta')
	$query.=" AND (type='final' || type='beta')";
VObject::Count(TB_VERSION, $query, $numNewVer);
if ($numNewVer>0)
	$newVerMsg=_Text("A new version is available. Select a version and click Submit to upgrade.");
else
	$newVerMsg='';

if ($newVerMsg!='')
	echo "<div class='inform'>$newVerMsg</div>\n";

$imageFormat=_Text("Image will be resized if it is larger than %s pixels.");

$smtpY=$smtpN='';
if (!isset($gBrandInfo['smtp_server']) || $gBrandInfo['smtp_server']=='')
	$smtpN='checked';
else
	$smtpY='checked';
?>


<form onsubmit='return CheckVersionForm(this)' method='POST' action="" name='version_form'>
<table class="meeting_detail">
<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Version")?>:</td>
	<td colspan=3 class="m_val">
	<?php echo $versionOpts?>
	<span><a target='<?php echo $GLOBALS['TARGET']?>' href='<?php echo $historyPage?>'>Release Notes</a></span>
<!--	
	<div><input <?php if ($gBrandInfo['auto_update']=='Y') echo 'checked';?>
	type="checkbox" name="auto_update" value='Y'>Automatically upgrade when a new version is available.</div>
-->
	<div><input <?php if ($gBrandInfo['site_level']=='beta') echo 'checked';?>
	type="checkbox" name="beta_site" value='Y'>Alert me when a beta version is available.</div>
	<div><input type="submit" name="set_version" value="Submit"></div>
	</td>
</tr>
</table>
</form>


<hr>

<form enctype="multipart/form-data" onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<input type="hidden" name="brand" value="<?php echo $GLOBALS['BRAND_NAME']?>">
<input type="hidden" name="brandUrl" value="<?php echo $GLOBALS['BRAND_URL']?>">
<table class="meeting_detail">

<?php
if (!isset($GLOBALS['LIMITED_ADMIN']))
{
	// only allow change if the admin access is not restricted (see admin.php)
?>

<tr>
	<td class="m_key m_key_w">*<?php echo _Text("Home Directory URL")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="site_url" size="50" value="<?php echo $gBrandInfo['site_url']?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	<div class='m_caption'><?php echo _Text("URL of this meeting site's home directory")?></div>
	</td>
</tr>

<?php
}
?>

<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Site Administrator")?>:</td>
	<td colspan=3 class="m_val">
	<?php echo $admins?>
	<div class='sub_val1'><input <?php echo $checkNotifyY?> type="radio" name="notify" value="Y">Send all email notifications to the administrator.</div>
	<div class='sub_val1'><input <?php echo $checkNotifyN?> type="radio" name="notify" value="N">Do not send email notifications.</div>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"> *<?php echo _Text("Site Email")?>:</td>
	<td colspan=3 class="m_val">
	<div>Name: <input type="text" name="from_name" size="30" value="<?php echo $gBrandInfo['from_name']?>"></div>
	<div>Email: <input type="email" name="from_email" size="30" autocorrect="off" autocapitalize="off" value="<?php echo $gBrandInfo['from_email']?>"></div>
	<div class='m_caption'> <?php echo _Text("Use the name and email address when sending email from this site.")?></div>
	<div class='sub_val1'><input <?php echo $smtpN?> type="radio" name="has_smtp" value="N" onclick='showSmtp(false)'><?php echo _Text("Use default email server.")?></div>
	<div class='sub_val1'><input <?php echo $smtpY?> type="radio" name="has_smtp" value="Y" onclick='showSmtp(true)'><?php echo _Text("Use my email server")?>:&nbsp;
	<div id='smtp-settings'>
	<div class='sub_val2'><b>SMTP server name:</b> <input type='text' size='40' name='smtp_server' autocorrect="off" autocapitalize="off" value="<?php echo $gBrandInfo['smtp_server']?>">
	<div class='m_caption'>Example: smtp.example.com, or ss://smtp.example.com:465 </div>
	<div class='m_caption'>The SMTP server must accept connections from '<?php echo $_SERVER['SERVER_NAME']?>'</div>
	</div>
	<div class='sub_val2'><b>User name:</b> <input type='text' size='15' name='smtp_user' autocorrect="off" autocapitalize="off" value="<?php echo $gBrandInfo['smtp_user']?>">	
	<b>Password:</b> <input type='password' size='15' name='smtp_password' value="<?php echo $gBrandInfo['smtp_password']?>">
	<div class='m_caption'>Enter the user name and password for SMTP server authentication.</div></div>
	<div class='sub_val2'><input type='submit' name='test_smtp' value='Send Test Email' onclick='return sendTestMail(document.web_form); return false'>
	<span class='m_caption'>Send test email to the Site Administrator.</span></div>
	</div>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Web Site Logo")?>:</td>
	<td colspan=3 class="m_val">
	<input type="file" name="site_logo_file" size="35">
	<input type="checkbox" name="reset_logo" value='1'> <?php echo _Text("Reset to default")?>
	<div class='m_caption'><?php echo $gText['M_UPLOAD_FILE']?> (jpeg, gif or png) <br><?php echo sprintf($imageFormat, $logoWidth."x".$logoHeight);?> </div>
	<div class='sub_val1'><input <?php echo $logoLinkN?> type="radio" name="has_logo_link" value="N"><?php echo _Text("Link to the Home page.")?></div>
	<div class='sub_val1'><input <?php echo $logoLinkY?> type="radio" name="has_logo_link" value="Y"><?php echo _Text("Custom link")?>:&nbsp;
	<input type='text' size='45' name='logo_link' autocorrect="off" autocapitalize="off" value="<?php echo $gBrandInfo['logo_link']?>">
	<div class='m_caption'>Example: http://www.mysite.com </div>
	</div>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"><?php echo _Text("Window Title")?>:</td>
	<td colspan=3 class="m_val">
	<input type='text' size='45' name='product_name' value="<?php echo htmlspecialchars($gBrandInfo['product_name'])?>">
	</td>
</tr>
<tr>
	<td class="m_key m_key_w"><?php echo _Text("Web Site Style")?>:</td>
	<td colspan=3 class="m_val">
	<div id='theme_pict'><img id='theme_img' src='<?php echo $unknownImg?>'></div>
	<span><?php echo _Text("Select a theme")?>: <?php echo $themes?></span>
	<span style="display:<?php echo $displayCustomTheme?>;" id='custom_style'><?php echo _Text("Theme name")?>: <input type='text' name='custom_theme' value='<?php echo $customTheme?>' size='15'></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"><?php echo _Text("Time Zone")?>:</td>
	<td colspan=3 class="m_val">
	<?php echo $timezones?>
	<span class='m_caption'><?php echo $gText['M_SET_TZ']?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Language")?>:</td>
	<td colspan=3 class="m_val">
	<?php echo $locales?>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Help Page")?>:</td>
	<td colspan=3 class="m_val">
	<div class='sub_val1'><input <?php echo $checkHelpY?> type="radio" name="custom_help" value="N"><?php echo _Text("Show default Help page")?></div>
	<div class='sub_val1'><input <?php echo $checkHelpN?> type="radio" name="custom_help" value="Y"><?php echo _Text("Show my Help page")?> 
	<span class='m_button_s'><a href='<?php echo "$editUrl&index=help_text";?>'><?php echo $editBtn?></a></span></div>
	</td>
</tr>

<?php
for ($i=1; $i<5; $i++) {
	$labelKey="footer".$i."_label";
	$indexKey="footer".$i."_text";
	print <<<END
<tr>
	<td class="m_key m_key_w">Footer Label $i:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="$labelKey" size="25" value="${gBrandInfo[$labelKey]}">
	<span class='m_button_s'><a href="$editUrl&index=$indexKey">$editBtn</a></span>
	</td>
</tr>
END;
}


if (!isset($GLOBALS['LIMITED_ADMIN'])) {
	// only allow change of the footnote if the admin access is not restricted (see admin.php)
?>

<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Footnote")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="footnote" size="65" maxlength="255" autocorrect="off" autocapitalize="off" value="<?php echo htmlspecialchars($gBrandInfo['footnote']);?>">	
	<div class='m_caption'><?php echo _Text("HTML code is allowed.")?> Example:</div>
	<div class='m_caption'><i><?php echo htmlspecialchars($linkExCode);?></i></div>
	<div class='m_caption'>Make sure to set <b>target='_blank'</b> (open a new window) or <b>target='_parent'</b> (open current window.)
	The image 'src' value should start with https if this is an https site.</div>
	</td>
</tr>

<?php
}
?>

<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Home Tab")?>:</td>
	<td colspan=3 class="m_val">
	<div class='sub_val1'><input <?php echo $checkHomeY?> type="radio" name="enable_home" value="Y"><b><?php echo $gText['M_YES']?>:</b> <?php echo _Text("Enable the Home tab.")?></div>
	<div class='sub_val1'><input <?php echo $checkHomeN?> type="radio" name="enable_home" value="N"><b><?php echo $gText['M_NO']?>:</b> <?php echo _Text("Disable the Home tab.")?></div>
	<div class='m_caption'><?php echo _Text("The Home tab allows site visitors to browse public meetings and meeting rooms.")?></div>
	</td>
</tr>

<?php
if ($hasTrial) {
	$checkTrialY=$checkTrialN='';
	if ($gBrandInfo['trial_signup']=='Y' && $gBrandInfo['offerings']{0}=='T')
		$checkTrialY='checked';
	else
		$checkTrialN='checked';
		
	$freeTrialText=_Text("Free Trial");
	$allowText=_Text("Allow anyone to sign up for free trials.");
	$disableText=_Text("Disable free trials.");
	print <<<END
<tr>
	<td class="m_key m_key_w">$freeTrialText:</td>
	<td colspan=3 class="m_val">
	<div class='sub_val1'><input $checkTrialY type="radio" name="enable_trial" value="Y"><b> ${gText['M_YES']}:</b> $allowText</div>
	<div class='sub_val1'><input $checkTrialN type="radio" name="enable_trial" value="N"><b> ${gText['M_NO']}:</b> $disableText</div>
	</td>
</tr>
END;
}

?>
<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Sign In")?>:</td>
	<td colspan=3 class="m_val">
	<div class='sub_val1'><input <?php echo $checkSigninY?> type="radio" name="enable_signin" value="Y" onclick="return SetElemDisplay('custom_signin', 'none')"><b><?php echo $gText['M_YES']?>:</b> <?php echo _Text("Enable the default sign-in page.")?></div>
	<div class='sub_val1'><input <?php echo $checkSigninN?> type="radio" name="enable_signin" value="N" onclick="return SetElemDisplay('custom_signin', 'inline')"><b><?php echo $gText['M_NO']?>:</b> <?php echo _Text("Disable the default sign-in page.")?></div>
	<div id='custom_signin'>
	<div class='sub_val2'><?php echo _Text("Redirect users to the custom sign-in page")?>: </div>
	<div class='sub_val2'><input type='input' name='custom_signin_url' size='60' value='<?php echo $customSigninUrl?>'></div>
	<div class='m_caption'>See <a target='<?php echo $GLOBALS['TARGET']?>' href='<?php echo $docPage?>'>Documentation</a> for implementing your custom sign-in page.<br>
	Use <a href='<?php echo $signinPage?>'><?php echo $signinPage?></a> to go the default sign-in page.</div>
	</div>
	</td>
</tr>

<?php

if (isset($gBrandInfo['send_report']) && defined('ENABLE_TRANSCRIPTS') && constant('ENABLE_TRANSCRIPTS')=='1') {
	$text=_Text("Report");
	$checkY=$checkN='';
	if (isset($gBrandInfo['send_report']) && $gBrandInfo['send_report']=='Y')
		$checkY='checked';
	else if (isset($gBrandInfo['send_report']) && $gBrandInfo['send_report']=='N')
		$checkN='checked';
	$text1=_Text("Enable sending of meeting reports at end of a meeting.");
	$text2=_Text("Disable sending of meeting reports.");

print <<<END
<tr>
	<td class="m_key m_key_w"> $text:</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $checkY type="radio" name="send_report" value="Y"><b>${gText['M_YES']}:</b> $text1
	<div class='sub_val1'><input $checkN type="radio" name="send_report" value="N"><b>${gText['M_NO']}:</b> $text2</div>
	</div>
	</td>
</tr>
END;

}

?>

<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Share It")?>:</td>
	<td colspan=3 class="m_val">
	<div class='sub_val1'><input <?php echo $checkShareY?> type="radio" name="share_it" value="Y"><b><?php echo $gText['M_YES']?>:</b> <?php echo _Text("Allow sharing of meeting information via Facebook and Twitter.")?></div>
	<div class='sub_val1'><input <?php echo $checkShareN?> type="radio" name="share_it" value="N"><b><?php echo $gText['M_NO']?>:</b> <?php echo _Text("Disable sharing via Facebook and Twitter.")?></div>
	</td>
</tr>


<tr>
	<td class="m_key m_key_w"> <?php echo _Text("Embedding")?>:</td>
	<td colspan=3 class="m_val">
	<div class='sub_val1'><input <?php echo $checkEmbedN?> type="radio" name="embed_site" value="N"><b><?php echo $gText['M_NO']?>:</b> <?php echo _Text("This is a stand-alone site.")?></div>
	<div class='sub_val1'><input <?php echo $checkEmbedY?> type="radio" name="embed_site" value="Y"><b><?php echo $gText['M_YES']?>:</b> <?php echo _Text("This site is embedded in a frame of another page.")?></div>
	</td>
</tr>


<tr>
	<td class="m_key m_key_w">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	</td>
</tr>

</table>
</form>


<script type="text/javascript">
<!--
<?php

if ($themeName!='[custom]')
	echo "document.getElementById('theme_img').src=imgList['$themeName'];\n";

if ($checkSigninY=='checked')
	echo "SetElemDisplay('custom_signin', 'none');\n";
	
if ($smtpY=='checked')
	echo "showSmtp(true);\n";
else
	echo "showSmtp(false);\n";

?>

//-->
</script>