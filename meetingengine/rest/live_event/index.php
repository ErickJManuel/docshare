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
require_once("rest/pliveevent.php");

$plevent=new PLiveEvent();

if (isset($_REQUEST['help'])) {
	$plevent->Help();
	$plevent->Quit();
}
if ($plevent->Authenticate()==false) {
	$plevent->Respond();
	$plevent->Quit();
}

$method=$plevent->GetRequestMethod();

if ($method=='POST') {
	$retXml=$plevent->Insert();	
} else {
	$plevent->SetStatusCode(PCODE_BAD_REQUEST);
	$plevent->SetErrorMessage("Invalid method $method");
	$retXml=null;
}
	
$plevent->Respond($retXml);

$plevent->Quit();
?>