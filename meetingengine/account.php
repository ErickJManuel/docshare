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

//if (GetSessionValue('member_id')=='') {
if (GetSessionValue('member_brand')!=$gBrandInfo['id'] || GetSessionValue('member_login')==VUSER_GUEST) {
	require_once("includes/go_signin.php");
}

require_once("dbobjects/vuser.php");
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

$memberId=GetSessionValue('member_id');

$GLOBALS['PAGE_TITLE']=$gText['ACCOUNT_TAB'];
$GLOBALS['TAB']=PG_ACCOUNT;


$GLOBALS['SUB_MENUS']=array(
		PG_ACCOUNT_PROFILE => $gText['ACCOUNT_PROFILE'],
		PG_ACCOUNT_PASSWORD => $gText['ACCOUNT_PASSWORD'],
		PG_ACCOUNT_AUDIO_CONF => $gText['M_AUDIO_CONF'],
		PG_ACCOUNT_INFO => $gText['M_INFORMATION'],
		PG_ACCOUNT_TEST => $gText['M_SPEED_TEST'],
		);
	
	
if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']=PG_ACCOUNT_PROFILE;

if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT)
	$GLOBALS['SUB_PAGE']=PG_ACCOUNT_PROFILE;


 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

if (GetArg('hidetabs', $arg) && $arg==1) {
	$GLOBALS['HIDE_NAV']='on';
	$GLOBALS['HIDE_TABS']='on';
}
 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/'; }

?>

<?php include_once('includes/header.php'); ?>

<?php include_once('includes/content-top.php'); ?>

<div class="heading1">
<?php 
/*
$arr=$GLOBALS['SUB_MENUS'];
foreach ($arr as  $pageId => $pageTitle) {
	if ($pageId==$GLOBALS['SUB_PAGE']) {
		echo $pageTitle;
		break;
	}
}
*/
?>
</div>
<?php

$userInfo=array();
$errMsg=VObject::Find(TB_USER, 'id', $memberId, $userInfo);
if ($errMsg!='')
	ShowError($errMsg);
else if (!isset($userInfo['id']))
	ShowError("user id not found.");
else
	$user=new VUser($userInfo['id']);

if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT || $GLOBALS['SUB_PAGE']==PG_ACCOUNT_PROFILE) 
	include_once("includes/account_profile.php");
else if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT_PASSWORD)
	include_once("includes/account_pass.php");
//else if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT_HOSTING)
//	include_once("includes/account_hosting.php");
else if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT_AUDIO_CONF)
	include_once("includes/account_aconf.php");
else if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT_INFO)
	include_once("includes/account_info.php");
else if ($GLOBALS['SUB_PAGE']==PG_ACCOUNT_TEST)
	include_once("includes/account_test.php");
else
	include_once("includes/not_found.php");	

include_once('includes/content-bottom.php'); 

?>

<?php include_once('includes/footer.php'); ?>
