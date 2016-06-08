<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("provider/common.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vbrand.php");

if (($providerId=GetSessionValue('provider_id'))=='') {
	$signinPage="provider_signin.php";
	if (SID!='')
		$signinPage.="?".SID;
	header("Location: $signinPage");
	DoExit();
}
//@include_once($GLOBALS['LOCALE_FILE']);
require_once("includes/common_text.php");

require_once("dbobjects/vbrand.php");

$providerId=GetSessionValue('provider_id');
//$provider=new VProvider($providerId);
//$provider->GetValue('admin_email', $email);


GetArg('active', $active);
GetArg('delete', $delete);

if ($active!='' || $delete=='1') {
	GetArg('brand_id', $brandId);
	if ($brandId=='') {
		ShowError("Brand id not set");
		DoExit();
	}
	
	$brand=new VBrand($brandId);
	$brand->GetValue('provider_id', $brandPid);
	if ($brandPid!=$providerId) {
		ShowError("Not authorized");
		DoExit();
	}
	
	$brandInfo=array();
	if ($active=='0') {
		$brandInfo['status']='INACTIVE';		
	} else if ($active=='1') {
		$brandInfo['status']='ACTIVE';		
	}
	
	if ($delete) {
		$brand->Drop();
		
		// delete all hosting accounts associate with this brand				
				
		
	} else
		$brand->Update($brandInfo);
	
	$mgPage=$_SERVER['PHP_SELF']."?page=SITE_MANAGE";
	if (SID!='')
		$mgPage.="&".SID;
	
	header("Location: $mgPage");
	DoExit();
}


$provider=new VProvider($providerId);
$providerInfo=array();

if ($provider->Get($providerInfo)!=ERR_NONE) {
	ShowError($provider->GetErrorMsg());
	return;
}

$maxSites=(integer)$providerInfo['max_sites'];

$canCreate=true;
// if there is a limit of how many brands can be created (0 means no limit)
if ($maxSites>0) {
	$query="provider_id='$providerId'";

	$errMsg=VObject::Count(TB_BRAND, $query, $numSites);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	// limit is already reached
	if ($numSites>=$maxSites) {
		$canCreate=false;
		
	}
	
}

$GLOBALS['TAB']="Web Sites";
// SMB provider with single site limit
if (!$canCreate) {
	$GLOBALS['SUB_MENUS']=array(
		"Manage Sites" => "SITE_MANAGE",
			);	
} else {
	$GLOBALS['SUB_MENUS']=array(
		"Manage Sites" => "SITE_MANAGE",
		"Create Site" => "SITE_CREATE",
			);
		
}

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


function ConfirmDeactivate() {

var confirmText ="Do you want to deactivate this site?\nNo one will be able to sign in to this site.";

	var ok=confirm(confirmText);
	if (ok)
		return true;
	else
		return false;
}
function ConfirmDelete() {

var confirmText ="Do you want to delete this site?\nThe deletion will not remove server files for this site.";

	var ok=confirm(confirmText);
	if (ok)
		return true;
	else
		return false;
}
//-->
</script>

<?php 
include_once('provider/header.php'); 
if ($GLOBALS['SUB_PAGE']=="SITE" || $GLOBALS['SUB_PAGE']=='') {
	$query="provider_id='$providerId'";
	$errMsg=VObject::Count(TB_BRAND, $query, $num);
	if ($num>0) {
		include_once("provider/site_manage.php");
	} else
		include_once("provider/site_create.php");

} elseif  ($GLOBALS['SUB_PAGE']=="SITE_MANAGE")
	include_once("provider/site_manage.php");
else if ($GLOBALS['SUB_PAGE']=="SITE_CREATE")
	include_once("provider/site_create.php");
else if ($GLOBALS['SUB_PAGE']=="SITE_EDIT")
	include_once("provider/site_edit.php");


include_once('provider/footer.php'); 
?>
