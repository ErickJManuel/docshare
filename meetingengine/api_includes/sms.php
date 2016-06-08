<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("dbobjects/vmailtemplate.php");
require_once("dbobjects/vbrand.php");

GetArg("phones", $phones);
GetArg("subject", $subject);
GetArg("message", $message);
GetArg("from", $form);

if ($phones=='')
	return API_EXIT(API_ERR, "'phones' is not set.");

$phoneList=explode(",", $phones);
$toEmail='';
foreach ($phoneList as $aPhone) {
	$aPhone=RemoveSpacesFromPhone($aPhone);
	if ($toEmail!='')
		$toEmail.=" ";
	$toEmail.=$aPhone."@txt.att.net";	// for AT&T texting
}

if (($errMsg=VMailTemplate::Send($form, '', '', $toEmail, $subject, $message))!='')
	return API_EXIT(API_ERR, $errMsg);	

?>