<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

	if ($gBrandInfo['hide_signin']=='Y' && $gBrandInfo['custom_signin_url']!='') {
		header("Location: ".$gBrandInfo['custom_signin_url']);
		DoExit();
	}

//	$retPage=$_SERVER['PHP_SELF']."?brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	$retPage=$GLOBALS['BRAND_URL'];
	if (isset($_GET['page'])) {
		$page=$_GET['page'];
		if ($page==PG_MEETINGS_START && isset($_GET['meeting'])) {
			$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&redirect=1&meeting=".$_GET['meeting'];
			if (isset($_GET['start']) && $_GET['start']=='1')
				$retPage.="&start=1";
			else if (isset($_GET['resume']) && $_GET['resume']=='1')
				$retPage.="&resume=1";
			
			if (SID!='')
				$retPage.="&".SID;
			
			
		} else {
			$thePage="MEETINGS";
			if (($pos1=strpos($page, "MEETINGS"))!==false)
				$thePage=PG_MEETINGS;
			else if (($pos1=strpos($page, "LIBRARY"))!==false)
				$thePage="LIBRARY";
			else if (($pos1=strpos($page, "ACCOUNT"))!==false)
				$thePage="ACCOUNT";
			else if (($pos1=strpos($page, "ADMIN"))!==false)
				$thePage="ADMIN";

		
			$retPage.="?page=".$thePage;
		}
	}

	$retPage=rawurlencode($retPage);
//	if (isset($_SERVER['HTTP_REFERER']))
//		$retPage=$_SERVER['HTTP_REFERER'];
	
	if (SID!='')
		$retPage.="&".SID;

	$signinPage="signin.php?ret=$retPage&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (isset($_GET['web']))
		$signinPage.="&web=1";
	if (SID!='')
		$signinPage.="&".SID;
	header("Location: $signinPage");
	DoExit();
	
?>