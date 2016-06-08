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
ob_start();
include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");


//if (GetSessionValue('member_id')=='') {
if (GetSessionValue('member_brand')!=$gBrandInfo['id']) {
	$signinPage="signin.php?ret=".$_SERVER['PHP_SELF']."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$signinPage.="&".SID;
	header("Location: $signinPage");
	DoExit();
}


$GLOBALS['TAB']=PG_CUSTOM;

$GLOBALS['SUB_MENUS']=array(
		);
		
if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';

 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/'; }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo $gText['CUSTOM_TAB']?></title>
<script type="text/javascript">
<!--


//-->
</script>


<?php
include_once('includes/header.php'); 
		

include_once('includes/footer.php');


?>
