<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common_lib.php");

// define("SESSION_EXP_TIME", 60*20);

StartSession();

$GLOBALS['HIDE_TABS']='off';
$GLOBALS['THEME']='grey1';
$GLOBALS['LOCALE_FILE']="locales/en.php";
$GLOBALS['LOGO_URL']="provider/logo.gif";
$GLOBALS['TARGET']="_parent";

$GLOBALS['MAIN_MENUS']=array(
	"Web Sites" => "SITE",
	"Account" => "ACCOUNT",
	"Reports" => "REPORT"
		);


?>