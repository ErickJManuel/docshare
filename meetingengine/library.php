<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

include_once("includes/common.php");
require_once("includes/brand.php");

if (GetSessionValue('member_brand')!=$gBrandInfo['id']) {
	require_once("includes/go_signin.php");
}
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

/*
GetArg('fitwindow', $fitWindow);
GetArg('hidetabs', $hideTabs);
*/

$target=$GLOBALS['TARGET'];
$popIcon="themes/popup_icon.gif";
/*
if ($fitWindow=='1' || $hideTabs=='1') {
	
	$pageUrl=$GLOBALS['BRAND_URL']."?page=LIBRARY";
	if (SID!='')
		$pageUrl.="&".SID;	
}
*/
$theme=$GLOBALS['THEME'];

$GLOBALS['PAGE_TITLE']=$gText['M_MY_LIBRARY'];

$GLOBALS['TAB']=PG_LIBRARY;

$canPoll=GetSessionValue("can_poll");

if ($canPoll=='Y') {
	
	$GLOBALS['SUB_MENUS']=array(
		PG_LIBRARY_MANAGER => _Text("Library Manager"),
		PG_LIBRARY_POLLING => _Text("Polling"),
		);
} else {

	$GLOBALS['SUB_MENUS']=array();
}
	
if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';

 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

if (GetArg('hidetabs', $arg)) {
	$GLOBALS['HIDE_NAV']='on';
	$GLOBALS['HIDE_TABS']='on';
}

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/'; }

if (count($GLOBALS['SUB_MENUS'])>0) {
	if ($GLOBALS['SUB_PAGE']==PG_LIBRARY)
		$GLOBALS['SUB_PAGE']=PG_LIBRARY_MANAGER;	
}

?>

<?php
include_once('includes/header.php');

include_once('includes/content-top.php'); 
?>
<?php
	if ($GLOBALS['SUB_PAGE']==PG_LIBRARY || $GLOBALS['SUB_PAGE']==PG_LIBRARY_MANAGER || $GLOBALS['SUB_PAGE']=='')
		require_once("includes/library_manager.php");
	else if ($GLOBALS['SUB_PAGE']==PG_LIBRARY_POLLING)
		require_once("includes/library_polling.php");
	else if ($GLOBALS['SUB_PAGE']==PG_LIBRARY_QUESTION)
		require_once("includes/library_question.php");

include_once('includes/content-bottom.php'); 

include_once('includes/footer.php');

?>