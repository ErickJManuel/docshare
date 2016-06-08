<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

if (isset($_REQUEST['help'])) {
	$pobj->Help();
	$pobj->Quit();
}

if ($pobj->Authenticate()==false) {
	$pobj->Respond();
	$pobj->Quit();
}

$method=$pobj->GetRequestMethod();

if ($method=='GET') {
	$retXml=$pobj->Get();
	$pobj->Respond($retXml);
}
$pobj->Quit();

?>