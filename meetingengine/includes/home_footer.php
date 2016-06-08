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

require_once("dbobjects/vbrand.php");

$page=$GLOBALS['SUB_PAGE'];
$num=substr($page, strlen($page)-1, 1);

$brand=new VBrand($GLOBALS['BRAND_ID']);
$brandInfo=array();
$brand->Get($brandInfo);

$key="footer".$num."_label";
if (isset($brandInfo[$key])) {
	$label=$brandInfo[$key];
} else {
	ShowError("Page not found");
	return;
}

$key="footer".$num."_text";
$text=$brandInfo[$key];

?>

<div class=heading1><?php echo $label?></div>
<?php echo $text?>
