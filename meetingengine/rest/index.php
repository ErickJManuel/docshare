<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

chdir("../");
require_once("rest/prestapi.php");

$prest=new PRestAPI();

$method=$prest->GetRequestMethod();

if ($method=='GET') {
	$retXml=$prest->Get();
	$prest->Respond($retXml);

}
$prest->Quit();
?>