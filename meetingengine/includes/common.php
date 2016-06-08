<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// these pages will hide navigation tabs
define("PG_REGISTER", "REGISTER");
define("PG_INVITE", "INVITE");
define("PG_DOWNLOAD", "DOWNLOAD");

// pages under the Home tab
define("PG_HOME", "HOME");
define("PG_HOME_MEETINGS", "HOME_MEETINGS");
define("PG_HOME_RECORDINGS", "HOME_RECORDINGS");
define("PG_HOME_MEETING", "HOME_MEETING");
define("PG_HOME_REGISTER", "HOME_REGISTER");
define("PG_HOME_USER", "HOME_USER");
define("PG_HOME_ROOM", "HOME_ROOM");
define("PG_HOME_ROOMS", "HOME_ROOMS");
define("PG_HOME_DOWNLOAD", "HOME_DOWNLOAD");
define("PG_HOME_INVITE", "HOME_INVITE");
define("PG_HOME_JOIN", "HOME_JOIN");
define("PG_HOME_VINE", "HOME_VINE");
define("PG_HOME_ARD", "HOME_ARD");
define("PG_HOME_TVNC", "HOME_TVNC");
define("PG_HOME_FSP", "HOME_FSP");
define("PG_HOME_VERSIONS", "HOME_VERSIONS");
define("PG_HOME_COOKIES", "HOME_COOKIES");
define("PG_HOME_VIDEO", "HOME_VIDEO");
define("PG_HOME_JAVA", "HOME_JAVA");
define("PG_HOME_TEST", "HOME_TEST");

define("PG_MEETINGS", "MEETINGS");
define("PG_MEETINGS_LIST", "MEETINGS_LIST");
define("PG_MEETINGS_ADD", "MEETINGS_ADD");
define("PG_MEETINGS_ROOM", "MEETINGS_ROOM");
define("PG_MEETINGS_REGIST", "MEETINGS_REGIST");
define("PG_MEETINGS_DETAIL", "MEETINGS_DETAIL");
define("PG_MEETINGS_DELETE", "MEETINGS_DELETE");
define("PG_MEETINGS_START", "MEETINGS_START");
define("PG_MEETINGS_END", "MEETINGS_END");
define("PG_MEETINGS_VIEWER", "MEETINGS_VIEWER");
define("PG_MEETINGS_ATTENDEE", "MEETINGS_ATTENDEE");
define("PG_MEETINGS_REGFORM", "MEETINGS_REGFORM");
define("PG_MEETINGS_REPORT", "MEETINGS_REPORT");
define("PG_MEETINGS_COMMENT", "MEETINGS_COMMENT");
define("PG_MEETINGS_RECORDINGS", "MEETINGS_RECORDINGS");
define("PG_MEETINGS_AUDIO", "MEETINGS_AUDIO");
define("PG_MEETINGS_TRANSCRIPT", "MEETINGS_TRANSCRIPT");
define("PG_MEETINGS_POLL", "MEETINGS_POLL");

define("PG_LIBRARY", "LIBRARY");
define("PG_LIBRARY_MANAGER", "LIBRARY_MANAGER");
define("PG_LIBRARY_POLLING", "LIBRARY_POLLING");
define("PG_LIBRARY_QUESTION", "LIBRARY_QUESTION");
/*
define("PG_LIBRARY_PRES", "LIBRARY_PRES");
define("PG_LIBRARY_PICT", "LIBRARY_PICT");
define("PG_LIBRARY_QUIZ", "LIBRARY_QUIZ");
*/

define("PG_ACCOUNT", "ACCOUNT");
define("PG_ACCOUNT_PROFILE", "ACCOUNT_PROFILE");
define("PG_ACCOUNT_PASSWORD", "ACCOUNT_PASSWORD");
define("PG_ACCOUNT_HOSTING", "ACCOUNT_HOSTING");
define("PG_ACCOUNT_AUDIO_CONF", "ACCOUNT_AUDIO_CONF");
define("PG_ACCOUNT_INFO", "ACCOUNT_INFO");
define("PG_ACCOUNT_TEST", "ACCOUNT_TEST");

define("PG_ADMIN_USERS", "ADMIN_USERS");
define("PG_ADMIN_GROUPS", "ADMIN_GROUPS");
define("PG_ADMIN_MEETINGS", "ADMIN_MEETINGS");
define("PG_ADMIN_SITE", "ADMIN_SITE");
define("PG_ADMIN_WEB", "ADMIN_WEB");
define("PG_ADMIN_VIDEO", "ADMIN_VIDEO");
define("PG_ADMIN_REMOTE", "ADMIN_REMOTE");
define("PG_ADMIN_REPORT", "ADMIN_REPORT");
define("PG_ADMIN_ADD_USER", "ADMIN_ADD_USER");
define("PG_ADMIN_EDIT_USER", "ADMIN_EDIT_USER");
define("PG_ADMIN_HOSTING", "ADMIN_HOSTING");
define("PG_ADMIN_ADD_GROUP", "ADMIN_ADD_GROUP");
define("PG_ADMIN_EDIT_GROUP", "ADMIN_EDIT_GROUP");
define("PG_ADMIN_ADD_WEB", "ADMIN_ADD_WEB");
define("PG_ADMIN_EDIT_WEB", "ADMIN_EDIT_WEB");
define("PG_ADMIN_DELETE_WEB", "ADMIN_DELETE_WEB");
define("PG_ADMIN_ADD_VIDEO", "ADMIN_ADD_VIDEO");
define("PG_ADMIN_EDIT_VIDEO", "ADMIN_EDIT_VIDEO");
define("PG_ADMIN_DELETE_VIDEO", "ADMIN_DELETE_VIDEO");
define("PG_ADMIN_ADD_REMOTE", "ADMIN_ADD_REMOTE");
define("PG_ADMIN_EDIT_REMOTE", "ADMIN_EDIT_REMOTE");
define("PG_ADMIN_DELETE_REMOTE", "ADMIN_DELETE_REMOTE");
define("PG_ADMIN_STORAGE", "ADMIN_STORAGE");
define("PG_ADMIN_ADD_STORAGE", "ADMIN_ADD_STORAGE");
define("PG_ADMIN_EDIT_STORAGE", "ADMIN_EDIT_STORAGE");
define("PG_ADMIN_DELETE_STORAGE", "ADMIN_DELETE_STORAGE");
define("PG_ADMIN_INSTALL", "ADMIN_INSTALL");
define("PG_ADMIN_STORAGE_INSTALL", "ADMIN_STORAGE_INSTALL");
define("PG_ADMIN_ATTENDEE", "ADMIN_ATTENDEE");
define("PG_ADMIN_PAGE", "ADMIN_PAGE");
define("PG_ADMIN_VIEWER", "ADMIN_VIEWER");
define("PG_ADMIN_EMAIL", "ADMIN_EMAIL");
define("PG_ADMIN_ACCOUNTS", "ADMIN_ACCOUNTS");
define("PG_ADMIN_SEND", "ADMIN_SEND");
define("PG_ADMIN_AWS", "ADMIN_AWS");
define("PG_ADMIN_ADD_AWS", "ADMIN_ADD_AWS");
define("PG_ADMIN_EDIT_AWS", "ADMIN_EDIT_AWS");
define("PG_ADMIN_API", "ADMIN_API");
define("PG_ADMIN_API_HOOKS", "ADMIN_API_HOOKS");
define("PG_ADMIN_ADD_TELE", "ADMIN_ADD_TELE");
define("PG_ADMIN_EDIT_TELE", "ADMIN_EDIT_TELE");
define("PG_ADMIN_DELETE_TELE", "ADMIN_DELETE_TELE");
define("PG_ADMIN_ADD_CONVERSION", "ADMIN_ADD_CONVERSION");
define("PG_ADMIN_EDIT_CONVERSION", "ADMIN_EDIT_CONVERSION");
define("PG_ADMIN_DELETE_CONVERSION", "ADMIN_DELETE_CONVERSION");
define("PG_ADMIN_SITE_ATTENDEES", "ADMIN_SITE_ATTENDEES");


define("PG_ADMIN", "ADMIN");
define("PG_SIGNIN", "SIGNIN");
define("PG_SIGNOUT", "SIGNOUT");
define("PG_SIGNUP", "SIGNUP");
define("PG_CUSTOM", "CUSTOM");

define("PG_VIEWER", "VIEWER");
define("PG_HELP", "HELP");
define("PG_HELP_REST", "HELP_REST");
define("PG_CONTACT", "CONTACT");
define("PG_FEEDBACK", "FEEDBACK");
define("PG_TERMS", "TERMS");
define("PG_PRIVACY", "PRIVACY");
define("PG_TEST", "TEST");

define("PG_HOME_ERROR", "HOME_ERROR");
define("PG_HOME_INFORM", "HOME_INFORM");

define("PG_HOME_FOOTER_1", "HOME_FOOTER_1");
define("PG_HOME_FOOTER_2", "HOME_FOOTER_2");
define("PG_HOME_FOOTER_3", "HOME_FOOTER_3");
define("PG_HOME_FOOTER_4", "HOME_FOOTER_4");

define("PG_PROCESS_AUDIO", "PROCESS_AUDIO");

//define('ROOT_USER', '_root');

require_once("header.php");
require_once("includes/common_lib.php");

// Allow an embedding site to control the display of the tabs with an url parameter (hide_nav)
// hide the navigation tabs and also set a cookie so all the sub pages will inherit this properties
// the cookie will expire when the browser closes
if (isset($_GET['hide_nav'])) {
	setcookie("hide_nav", $_GET['hide_nav'], 0);	// good till the browser closes
	// update $_COOKIE for the current page too
	$_COOKIE['hide_nav']=$_GET['hide_nav'];
}
StartSession();



?>