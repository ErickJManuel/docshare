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
require_once("rest/pgroup.php");

$pgroup=new PGroup();

if (isset($_REQUEST['help'])) {
	$pgroup->Help();
	$pgroup->Quit();
}
if ($pgroup->Authenticate()==false) {
	$pgroup->Respond();
	$pgroup->Quit();
}

$method=$pgroup->GetRequestMethod();

if ($method=='GET') {
	$retXml=$pgroup->Get();
} elseif ($method=='DELETE') {
	$retXml=$pgroup->Delete();	
} elseif ($method=='POST') {
	$retXml=$pgroup->Insert();	
} elseif ($method=='PUT') {
	$retXml=$pgroup->Update();
} else {
	$pgroup->SetStatusCode(PCODE_BAD_REQUEST);
	$pgroup->SetErrorMessage("Invalid method $method");
	$retXml=null;
}
	
$pgroup->Respond($retXml);
$pgroup->Quit();


?>