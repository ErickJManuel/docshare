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

if (isset($_GET['export_csv'])) {
	require_once("provider/export_cdr.php");
}

$GLOBALS['TAB']="Reports";
$GLOBALS['SUB_MENUS']=array(
	"User licenses" => "REPORT_USER",
	"Meeting sessions" => "REPORT_SESSION",
	"Concurrent ports" => "REPORT_PORT",
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

if ($GLOBALS['SUB_PAGE']=="REPORT" || $GLOBALS['SUB_PAGE']=="REPORT_USER" || $GLOBALS['SUB_PAGE']=='')
	include_once("provider/report_user.php");
else if ($GLOBALS['SUB_PAGE']=="REPORT_PORT")
	include_once("provider/report_port.php");
else if ($GLOBALS['SUB_PAGE']=="REPORT_SESSION")
	include_once("provider/report_session.php");

include_once('provider/footer.php'); 

?>
