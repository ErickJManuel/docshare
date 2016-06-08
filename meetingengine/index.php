<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

$pageList=array("home", "meetings", "help", "library", "admin", "account", "viewer", "signin", "signout", "process", "test");

if (isset($_GET['page']) && $_GET['page']!='') {	
	$page=strtolower($_GET['page']);
	if (($i=strpos($page, "_"))>0)
		$page=substr($page, 0, $i);
	
	$pageFound=false;
	foreach($pageList as $v) {
		if ($page==$v) {
			$pageFound=true;
			break;
		}
	}
	if ($pageFound)
		$page.=".php";
	else
		$page="home.php";
} elseif (isset($_GET['StorageServerUrl'])) {
	$page="opensam.php";
} elseif (isset($_GET['signin'])) {
	$page="signin.php";
} elseif (isset($_GET['signout'])) {
	$page="signout.php";
} else {
require_once("includes/common_lib.php");
	$page="home.php";
}
	
include_once($page);
	
?>