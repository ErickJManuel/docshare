<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common.php");
require_once("includes/brand.php");
require_once("dbobjects/vuser.php");

if (GetSessionValue('member_brand')!=$gBrandInfo['id'] || GetSessionValue('member_login')==VUSER_GUEST) {
	require_once("includes/go_signin.php");
}

require_once("dbobjects/vuser.php");
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

if (GetArg('create_keyfile', $arg) && $arg!='') {
	$file="my_aws_keys.txt";
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text; name=\"$file\"");	
	header("Content-Disposition: attachment; filename=$file");
	GetArg('access_key', $accessKey);
	GetArg('secret_key', $secretKey);
	echo $accessKey." ".$secretKey;
	DoExit();
}

// If the provider license type is 'S' and the user is not the root admin, set the admin to have more restricted access. 
// Hide the Groups and Hosting pages and don't allow changing of the footnote
if (GetSessionValue('root_user')!='true') {
	$provider_id=$gBrandInfo['provider_id'];
	$query="id= '".$provider_id."'";
	VObject::Select(TB_PROVIDER, $query, $providerInfo);

	if (isset($providerInfo['id'])) {
		require_once("dbobjects/vprovider.php");
		$licenseCounts=array();
		VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
		if ($licenseType=='S')
			$GLOBALS['LIMITED_ADMIN']=true;
	}
}
$GLOBALS['PAGE_TITLE']=$gText['ADMIN_TAB'];

$GLOBALS['TAB']=PG_ADMIN;
if (isset($GLOBALS['LIMITED_ADMIN']) && $GLOBALS['LIMITED_ADMIN']) {
	$GLOBALS['SUB_MENUS']=array(
			PG_ADMIN_USERS => $gText['ADMIN_USERS'],
			PG_ADMIN_MEETINGS => $gText['ADMIN_MEETINGS'],
			PG_ADMIN_REPORT => $gText['ADMIN_REPORT'],
			PG_ADMIN_SITE => $gText['ADMIN_SITE'],
			PG_ADMIN_VIEWER => $gText['MEETINGS_VIEWER'],
			PG_ADMIN_ACCOUNTS => $gText['M_ACCOUNTS'], 
			PG_ADMIN_API => "API", 
			);	
} else {
	$GLOBALS['SUB_MENUS']=array(
			PG_ADMIN_USERS => $gText['ADMIN_USERS'],
			PG_ADMIN_GROUPS => $gText['ADMIN_GROUPS'],
			PG_ADMIN_MEETINGS => $gText['ADMIN_MEETINGS'],
			PG_ADMIN_HOSTING => $gText['M_HOSTING'],
			PG_ADMIN_REPORT => $gText['ADMIN_REPORT'],
			PG_ADMIN_SITE => $gText['ADMIN_SITE'],
			PG_ADMIN_VIEWER => $gText['MEETINGS_VIEWER'],
			PG_ADMIN_ACCOUNTS => $gText['M_ACCOUNTS'], 
			PG_ADMIN_API => "API", 
			);
}

if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';
	
if ($GLOBALS['SUB_PAGE']=='' || $GLOBALS['SUB_PAGE']==PG_ADMIN)
	$GLOBALS['SUB_PAGE']=PG_ADMIN_USERS;

 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/home_right.php'; }


include_once('includes/header.php'); 

include_once('includes/content-top.php'); 

/*
$arr=$GLOBALS['SUB_MENUS'];
foreach ($arr as  $pageId => $pageTitle) {
	if ($pageId==$GLOBALS['SUB_PAGE']) {
		echo "<div class=\"heading1\">$pageTitle</div>";
		break;
	}
}
*/
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

$hasErr=false;
if ($memberPerm!='ADMIN') {
	ShowError("Not an authorized administrator");
	$hasErr=true;
}

if (!$hasErr) {
	if ($memberBrand!=$GLOBALS['BRAND_ID']) {
		ShowError("Not an authorized administrator for this brand");
		$hasErr=true;
	}
}

if (!$hasErr) {
	if ($GLOBALS['SUB_PAGE']==PG_ADMIN || $GLOBALS['SUB_PAGE']=='')
		include_once("includes/admin_user.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_USERS)
		include_once("includes/admin_user.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_USER || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_USER)
		include_once("includes/admin_user_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_GROUPS)
		include_once("includes/admin_group.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_GROUP || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_GROUP)
		include_once("includes/admin_group_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_SITE)
		include_once("includes/admin_site.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_HOSTING)
		include_once("includes/admin_hosting.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_MEETINGS)
		include_once("includes/admin_meetings.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_REPORT)
		include_once("includes/admin_report.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_HOSTING)
		include_once("includes/admin_hosting.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_WEB || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_WEB)
		include_once("includes/admin_web_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_VIDEO || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_VIDEO)
		include_once("includes/admin_video_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_REMOTE || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_REMOTE)
		include_once("includes/admin_remote_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_STORAGE || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_STORAGE)
		include_once("includes/admin_storage_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_TELE || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_TELE)
		include_once("includes/admin_teleconf_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ADD_CONVERSION || $GLOBALS['SUB_PAGE']==PG_ADMIN_EDIT_CONVERSION)
		include_once("includes/admin_conversion_detail.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_INSTALL)
		include_once("includes/admin_install.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_STORAGE_INSTALL)
		include_once("includes/admin_storage_install.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ATTENDEE)
		include_once("includes/admin_attendee.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_PAGE)
		include_once("includes/admin_page.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_VIEWER)
		include_once("includes/admin_viewer.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_SEND)
		include_once("includes/admin_send.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_ACCOUNTS)
		include_once("includes/admin_accounts.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_AWS)
		include_once("includes/admin_aws.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_API)
		include_once("includes/admin_api.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_API_HOOKS)
		include_once("includes/admin_api_hooks.php");
	else if ($GLOBALS['SUB_PAGE']==PG_ADMIN_SITE_ATTENDEES)
		include_once("includes/admin_site_attendees.php");
	else
		include_once("includes/not_found.php");	
}

include_once('includes/content-bottom.php'); 
include_once('includes/footer.php');
?>
