<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


$target=$GLOBALS['TARGET'];
$thisPage=$_SERVER['PHP_SELF'];
//$webUrl=$thisPage."?page=".PG_ADMIN_WEB."&brand=".$GLOBALS['BRAND_NAME'];
$webUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_WEB;
if (SID!='')
	$webUrl.="&".SID;
//$videoUrl=$thisPage."?page=".PG_ADMIN_VIDEO."&brand=".$GLOBALS['BRAND_NAME'];
$videoUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_VIDEO;
if (SID!='')
	$videoUrl.="&".SID;
//$remoteUrl=$thisPage."?page=".PG_ADMIN_REMOTE."&brand=".$GLOBALS['BRAND_NAME'];
$remoteUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REMOTE;
if (SID!='')
	$remoteUrl.="&".SID;

if ($GLOBALS['SUB_PAGE']==PG_ADMIN_HOSTING || $GLOBALS['SUB_PAGE']==PG_ADMIN_WEB) {

	$webClass="class='sublist_on'";
	$videoClass="";
	$remoteClass="";
	$includeFile="includes/admin_web.php";

} else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_VIDEO) {

	$webClass="";
	$videoClass="class='sublist_on'";
	$remoteClass="";
	$includeFile="includes/admin_video.php";
	
} else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_REMOTE) {
	
	$webClass="";
	$videoClass="";
	$remoteClass="class='sublist_on'";
	$includeFile="includes/admin_remote.php";
	
} else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_STORAGE) {
		
	$webClass="";
	$videoClass="";
	$remoteClass="class='sublist_on'";
	$includeFile="includes/admin_storage.php";
			
}

$awsUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_AWS;
if (SID!='')
	$awsUrl.="&".SID;

?>

<ul><li><a target='<?php echo $GLOBALS['TARGET']?>' href='<?php echo $awsUrl?>'>Manage Amazon Web Services (AWS)</a></li></ul>

<div class="heading2"><?php echo _Text("Web Conference")?></div>

<?php
include_once("includes/admin_web.php");
?>

<div class="heading2"><?php echo _Text("Video Conference")?></div>
<?php
include_once("includes/admin_video.php");
?>

<div class="heading2"><?php echo _Text("Teleconference")?></div>
<?php
include_once("includes/admin_teleconf.php");
?>

<div class="heading2"><?php echo _Text("Remote Control")?></div>

<?php
include_once("includes/admin_remote.php");

if (defined('USE_CONVERSION_SERVER') && constant('USE_CONVERSION_SERVER')!='0') {
	$text=_Text("Conversion Server");
	echo "<div class=\"heading2\">$text</div>\n";
	include_once("includes/admin_conversion.php");
}

if (defined('USE_STORAGE_SERVER') && constant("USE_STORAGE_SERVER")=='1') {
	$text=_Text("Storage Server");
	echo "<div class=\"heading2\">$text</div>\n";
	include_once("includes/admin_storage.php");
}
?>




