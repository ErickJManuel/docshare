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
require_once("rest/pmeeting.php");

$pmeeting=new PMeeting();

if (isset($_REQUEST['help'])) {
	$pmeeting->Help();
	$pmeeting->Quit();
}
if ($pmeeting->Authenticate()==false) {
	$pmeeting->Respond();
	$pmeeting->Quit();
}

$method=$pmeeting->GetRequestMethod();

if ($method=='GET') {
	$retXml=$pmeeting->Get();
} elseif ($method=='DELETE') {
	$retXml=$pmeeting->Delete();	
} elseif ($method=='POST') {
	$retXml=$pmeeting->Insert();	
} elseif ($method=='PUT') {
	$retXml=$pmeeting->Update();
} else {
	$pmeeting->SetStatusCode(PCODE_BAD_REQUEST);
	$pmeeting->SetErrorMessage("Invalid method $method");
	$retXml=null;
}
	
$pmeeting->Respond($retXml);

$pmeeting->Quit();
?>