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
require_once("rest/pmember.php");

$puser=new PMember();

if (isset($_REQUEST['help'])) {
	$puser->Help();
	$puser->Quit();
}
if ($puser->Authenticate()==false) {
	$puser->Respond();
	$puser->Quit();
}


$method=$puser->GetRequestMethod();

if ($method=='GET') {
	$retXml=$puser->Get();
} elseif ($method=='DELETE') {
	$retXml=$puser->Delete();	
} elseif ($method=='POST') {
	$retXml=$puser->Insert();	
} elseif ($method=='PUT') {
	$retXml=$puser->Update();
}
$puser->Respond($retXml);
$puser->Quit();


?>