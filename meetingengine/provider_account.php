<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("provider/common.php");
require_once("dbobjects/vprovider.php");

if (GetSessionValue('provider_id')=='') {
	$signinPage="provider_signin.php";
	if (SID!='')
		$signinPage.="?".SID;
	header("Location: $signinPage");
	DoExit();
}
//@include_once($GLOBALS['LOCALE_FILE']);
require_once("includes/common_text.php");

$GLOBALS['TAB']="Account";
$GLOBALS['SUB_MENUS']=array(
	"Licenses" => "ACCOUNT_LICENSE",
	"Account Info" => "ACCOUNT_INFO",
		);

if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';
	
 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'provider/right.inc.php'; }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo $GLOBALS['TAB']?></title>
<script type="text/javascript">
<!--


//-->
</script>

<?php 
include_once('provider/header.php'); 

if ($GLOBALS['SUB_PAGE']=="ACCOUNT" || $GLOBALS['SUB_PAGE']=="ACCOUNT_LICENSE" || $GLOBALS['SUB_PAGE']=='')
	include_once("provider/account_license.php");
else if ($GLOBALS['SUB_PAGE']=="ACCOUNT_INFO")
	include_once("provider/account_info.php");

include_once('provider/footer.php'); 

?>
