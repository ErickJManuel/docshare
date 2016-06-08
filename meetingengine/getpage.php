<?php
//require_once("includes/common.php");
	$page=$_GET['page'];
	
	$pageFile="index.php";
	if (($i=strpos($page, "_"))>0) {
		$pageFile=strtolower(substr($page, 0, $i));
		$pageFile.=".php";
	} else if ($page!='') {
		$pageFile=strtolower($page);
		$pageFile.=".php";
	}
	echo $pageFile;
/*	
	
	if (($pos=strpos($page, PG_MEETINGS))!==false && $pos==0) {
		$page='meetings.php';
	} else if (($pos=strpos($page, PG_LIBRARY))!==false && $pos==0) {
		$page='library.php';
	} else if (($pos=strpos($page, PG_ACCOUNT))!==false && $pos==0) {
		$page='account.php';
	} else if (($pos=strpos($page, PG_HELP))!==false && $pos==0) {
		$page='help.php';
	} else if (($pos=strpos($page, PG_ADMIN))!==false && $pos==0) {
		$page='admin.php';
	} else if (($pos=strpos($page, PG_SIGNIN))!==false && $pos==0) {
		$page='signin.php';
	} else if (($pos=strpos($page, PG_SIGNOUT))!==false && $pos==0) {
		$page='signout.php';
	} else if (($pos=strpos($page, PG_SIGNUP))!==false && $pos==0) {
		$page='signup.php';
	} else if (($pos=strpos($page, PG_HOME))!==false && $pos==0) {
		$page='home.php';
	} else if (($pos=strpos($page, PG_VIEWER))!==false && $pos==0) {
		$page='viewer.php';
	} else if (($pos=strpos($page, PG_HOME_ERROR))!==false && $pos==0) {
		$page='error.php';
	} else {
		$page='home.php';
	}
	echo $page;
*/
?>