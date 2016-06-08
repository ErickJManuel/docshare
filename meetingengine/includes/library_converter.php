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
require_once("dbobjects/vconversionserver.php");
require_once("dbobjects/vtoken.php");

// set which library to upload to
GetArg("lib", $lib);
if ($lib=='')
	$lib='mine';	// upload to mine by default

$pageTitle=$GLOBALS['SUB_MENUS'][PG_LIBRARY_CONVERTER];


$memberId=GetSessionValue('member_id');
$user=new VUser($memberId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}
$groupId=$userInfo['group_id'];
$group=new VGroup($groupId);
$groupInfo=array();
$group->Get($groupInfo);

$convId=$groupInfo['conversionserver_id'];
// use the default conversion server. Assume the default server is defined in row 1 of the DB conversionserver table
if ($convId=='0')
	$convId='1';

$naText=_Text("This feature is not enabled.");

$hideHeader=false;
if ((isset($fitWindow) && $fitWindow=='1') || (isset($hideTabs) && $hideTabs=='1')) {
	// show only the conveter UI
	$hideHeader=true;
} else {
	
?>	

<div class="heading1"><?php echo $pageTitle?> <span style="font-size:60%">(BETA)</span></div>
<p>
Document converter lets you upload a document to the server for conversion and insertion into My Library.
You can also add a presentation in Library Manager with a desktop converter.

<?php
}

if ($convId=='0' || $convId==null) {
print <<<END
<div class='inform'>$naText</div>
END;
	return;
}
VUser::GetStorageUrl($userInfo['brand_id'], $userInfo, $storageUrl, $storageId, $storageCode, $storageServerId);
VUser::GetLibraryPath($userInfo['access_id'], $pubLibPath, $myLibPath);

if ($lib=='public'&& $userInfo['permission']=='ADMIN')
	$libPath=$pubLibPath;
else
	$libPath=$myLibPath;

$convServer=new VConversionServer($convId);
$convServer->Get($convInfo);
if (!isset($convInfo['id'])) {
	$text=_Text("The conversion server is not found.");
print <<<END
<div class='inform'>$text</div>
END;
	return;	
}
$convUrl=$convInfo['url'];
// If this is an ssl site, use the ssl doc conversion server url if it is available.
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
	if (isset($convInfo['ssl_url']) && $convInfo['ssl_url']!='')
		$convUrl=$convInfo['ssl_url'];
}
$args="user_id=".$userInfo['access_id'];
$args.="&server_url=".SITE_URL;
$args.="&lib_url=".$storageUrl;
$args.="&lib_path=".$libPath;
$args.="&user_pass=".md5($userInfo['password']);
$args.="&brand=".$GLOBALS['BRAND_NAME'];
$args.="&locale=".$gBrandInfo['locale'];
$args.="&css_url=".SITE_URL."themes/".$GLOBALS['THEME'];

$sig=md5(SITE_URL.$userInfo['access_id'].$convInfo['access_key']);
$convUrl.="?".$args."&signature=".$sig;

?>
<p>

<?php

if ($pubLibPath!='' && !$hideHeader && $userInfo['permission']=='ADMIN') {
	$thisPage=$_SERVER['PHP_SELF'];	
	$thisPage.="?page=".$GLOBALS['SUB_PAGE'];
	$pubLibName=_Text("Public Library");
	$myLibName=_Text("My Library");
	$selectPublic=($lib=="public")?"selected":"";
	$selectMine=($lib=="mine")?"selected":"";
	
print <<<END
Select a library to upload to: 
<select id="select_lib" onchange="SelectLibrary('$thisPage', this)">
<option $selectPublic value="public">$pubLibName</option>
<option $selectMine value="mine">$myLibName</option>
</select>
END;
}

if (!$hideHeader) {
	
?>
<hr size='1'>
<iframe src ="<?php echo $convUrl?>" width="100%" height="400px" frameborder=0>
  <p>Your browser does not support iframes.</p>
</iframe>


<script type="text/javascript">
<!--

function SelectLibrary(url, elem)
{
	window.location=url+"&lib="+elem.value;		
	return true;
}

//-->
</script>

<?php
} else {
?>
<iframe src ="<?php echo $convUrl?>" width="98%" height="98%" frameborder=0 >
  <p>Your browser does not support iframes.</p>
</iframe>

<?php
}
?>
