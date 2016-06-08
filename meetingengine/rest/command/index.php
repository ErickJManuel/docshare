<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

chdir("../../");
require_once('includes/common.php');
require_once("api_includes/common.php");
require_once("rest/pcommand.php");

$pcommand=new PCommand();

if (isset($_REQUEST['help'])) {
	$pcommand->Help();
	$pcommand->Quit();
}
if ($pcommand->Authenticate()==false) {
	$pcommand->Respond();
	$pcommand->Quit();
}

$method=$pcommand->GetRequestMethod();

if ($method=='POST') {
	$retXml=$pcommand->Insert();	
} else {
	$pcommand->SetStatusCode(PCODE_BAD_REQUEST);
	$pcommand->SetErrorMessage("Invalid method $method");
	$retXml=null;
}
	
$pcommand->Respond($retXml);

$pcommand->Quit();
?>