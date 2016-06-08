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
require_once("rest/pwebserver.php");

$obj=new PWebServer();

if (isset($_REQUEST['help'])) {
	$obj->Help();
	$obj->Quit();
}
if ($obj->Authenticate()==false) {
	$obj->Respond();
	$obj->Quit();
}

$method=$obj->GetRequestMethod();

if ($method=='GET') {
	$retXml=$obj->Get();
} elseif ($method=='DELETE') {
	$retXml=$obj->Delete();	
} elseif ($method=='POST') {
	$retXml=$obj->Insert();	
} elseif ($method=='PUT') {
	$retXml=$obj->Update();
} else {
	$obj->SetStatusCode(PCODE_BAD_REQUEST);
	$obj->SetErrorMessage("Invalid method $method");
	$retXml=null;
}
	
$obj->Respond($retXml);
$obj->Quit();


?>