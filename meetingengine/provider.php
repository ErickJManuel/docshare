<?php

@include("console_forward.php");
if (defined("CONSOLE_FORWARD") && !isset($_GET['no_forward'])) {
	if (isset($_SERVER['HTTPS']))
		$forwardUrl=SECURE_CONSOLE_FORWARD;
	else
		$forwardUrl=CONSOLE_FORWARD;
	$forwardUrl.=basename($_SERVER['PHP_SELF']);
	$thisPage=$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
	// make sure to not forward to self
	if (strpos($forwardUrl, $thisPage)===false) {
		header("Location: $forwardUrl");
		exit();
	}
}

if (isset($_GET['page']) && $_GET['page']!='') {
	$name=$_GET['page'];
	if (($i=strpos($name, "_"))>0)
		$name=substr($name, 0, $i);
	if ($name=='ACCOUNT')
		$page="provider_account.php";
	elseif ($name=="REPORT")
		$page="provider_report.php";
	elseif ($name=="SIGNIN")
		$page="provider_signin.php";
	elseif ($name=="SIGNOUT")
		$page="provider_signout.php";
	elseif ($name=="SITE")
		$page="provider_site.php";	
	
//	$page="provider_".$page.".php";

} else {
	$page="provider_site.php";
}

require_once($page);
?>