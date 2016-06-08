<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once('api_includes/common.php');
require_once("dbobjects/vlicensekey.php");

GetArg("key", $key);
GetArg("license", $license);
if ($key=='' || $license=='')
	die ("ERROR Missing input");
	
if (!VLicenseKey::VerifyLicenseText($license, $keyInfo, $errMsg))
	die ("ERROR $errMsg");		

VObject::Find(TB_LICENSEKEY, "license_key", $key, $oldKeyInfo);
if (!isset($oldKeyInfo['id']))
	die ("ERROR Not found");

// update the old license key with the new information
$lic=new VLicenseKey($oldKeyInfo['id']);
$licInfo=array();
$licInfo['license_key']=$keyInfo['license_key'];
$licInfo['license_text']=$license;
if ($lic->Update($licInfo)!=ERR_NONE)
	die("ERROR ".$lic->GetErrorMsg());

die("OK");

?>