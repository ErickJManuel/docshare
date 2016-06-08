<?php
/**
 * Database constant definitions
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */

define("MULTI_PROVIDERS", "0");
define("DEV_VERSION", "0");

/**
* MySQL server name or ip address. Replace it with your server name.
*/
define("DB_SERVER", "localhost");
/**
* database name
*/
define('DB_NAME', "meetingengine");
/**
* MySQL server login name. Replace it with your login name.
*/
define("DB_LOGIN", "persony");
/**
* MySQL server password. Replace it with your password.
*/
define("DB_PASS", "xpttannslmxn");
/**
* URL of the management server's installed folder. The URL must end with a '/'.
*/
$HTTP_SERVER_NAME=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:"localhost";	// The server host name. Modify only if necessary.
$HTTP_INSTALL_PATH='';	// Enter the intalled folder path, e.g. wc2_deploy
define("SERVER_URL", "http://$HTTP_SERVER_NAME/$HTTP_INSTALL_PATH");
/**
* https URL of the management server's installed folder (optional). Required if you want to support https sites. The URL must end with a '/'.
*/
$HTTPS_SERVER_NAME=isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:"localhost";	// The server host name. Modify only if necessary.
$HTTPS_INSTALL_PATH=''; // Enter the intalled folder path, e.g. wc2_deploy
define("SSL_SERVER_URL", "https://$HTTPS_SERVER_NAME/$HTTPS_INSTALL_PATH");

/*
* Email from address for sending email from the site
*/
define("SERVER_EMAIL", "noreply@ganconference.com");
/*
* SMTP email server for sending email (e.g. smtp.example.com or smtp.example.com:25) (optional)
* If this is not set, PHP default mail() function will be used
*/
define("SMTP_SERVER", "localhost");
/*
* SMTP email server authentication [true|false] (optional)
*/
define("SMTP_AUTH", "false");
/*
* SMTP email server user name (if SMTP_AUTH is true) (optional)
*/
define("SMTP_USER", "");
/*
* SMTP email server user password (if SMTP_AUTH is true) (optional)
*/
define("SMTP_PASS", "");
/*
* Email address of the site admin
*/
define("ADMIN_EMAIL", "noreply@ganconference.com");
/*
* Password of the site admin
*/
define("ADMIN_PASS", "698d51a19d8a121ce581499d7b701668");
/*
* Free audio conference request URL (optional)
*/
define("FREEAC_URL", "");
/*
* Free audio conference request login (optional)
*/
define("FREEAC_LOGIN", "");
/*
* Free audio conference request password (optional)
*/
define("FREEAC_PASS", "");
/*
* Server log files directory path (optional)
*/
define("LOG_DIR", "/tmp/logs");
/*
* Directory path for temporary files.
* The patch can be absolute or relative to the installed directory.
* (make sure this directory is writable by PHP scripts)
*/
define("TEMP_DIR", "/tmp/");
/*
* Cron job authorized requester IP address (optional)
*/
define("CRON_REQUEST_IP", "");
/**
* Local path of the directory to store database files, such as images. The default is the installed directory if this value is empty.
* You should set a value if multiple installions will be sharing the same database.
* The path can be absolute or relative to the installed directory.
*/
define("DB_DIR_PATH", "/opt/meetingengine_files/");
/**
* URL of DB_DIR_PATH relative to SERVER_URL
* A file in DB_DIR_PATH should have the URL of SERVERURL+DBDIR_URL+file_name
*/
define("DB_DIR_URL", "/me_files/");
/*
* Database table prefix (Optional)
* e.g. persony
*/
define("TB_PREFIX", "wc2");
/*
* Set HOST_MULTI_MEETINGS to 1 to allow a moderator to host multiple meetings concurrently (optional)
*/
define("HOST_MULTI_MEETINGS", "0");
/*
* Set USE_STORAGE_SERVER to 1 to enable the storage server option for storing library contents (optional)
*/
define("USE_STORAGE_SERVER", "0");
/*
* Set ALLOW_PANEL_MEETING to 1 to enable the PANEL meeting type, where every participant of a meeting is a presenter. (optional)
*/
define("ALLOW_PANEL_MEETING", "0");
/*
* Set USE_CONVERSION_SERVER to 1 to enable the document conversion server option (optional)
*/
define("USE_CONVERSION_SERVER", "0");
/*
* (optional)
* Set ENABLE_TRANSCRIPTS to "1" to enable the creation of meeting transcripts
* Set ENABLE_TRANSCRIPTS to "0" to disable
*/
define("ENABLE_TRANSCRIPTS", "1");
/*
* (optional)
* Set ENABLE_WINDOWS_CLIENT to "1" to enable the use of Windows Presenter Client
* Set ENABLE_WINDOWS_CLIENT to "0" to disable
*/
define("ENABLE_WINDOWS_CLIENT", "1");
/*
* (optional)
* Set ENABLE_CACHING_SERVERS to "1" to enable the use of multiple servers to host a single meeting
* Set ENABLE_CACHING_SERVERS to "0" to disable
*/
define("ENABLE_CACHING_SERVERS", "0");
/*
* Server statistic files directory path. Required when caching servers are used.
* The patch can be absolute or relative to the installed directory.
* The default is "temp/".	
* (make sure this directory is writable by PHP scripts)
*/
define("STATS_DIR", "/opt/meetingengine_data/stats/");
/*
* (optional)
* Set REQUIRE_CROSSDOMAIN to "1" to require the installation of the "crossdomain.xml" file on each hosting site's root directory.
* The file allows the Flash player to access contents reside on different domain servers.
* Set REQUIRE_CROSSDOMAIN to "0" to make the crossdomain.xml file optional.
* If the value is set to "0", certain meeting functions may not work if you have more than one hosting servers.
* Set the value to 1 unless you have no way to install the crossdomain.xml file.
*/
define("REQUIRE_CROSSDOMAIN", "1");
/*
* (optional)
* Set ENABLE_RECORDING_DOWNLOAD to "1" to allow the option to download a recording
* Set ENABLE_RECORDING_DOWNLOAD to "0" to disable
*/
define("ENABLE_DOWNLOAD_RECORDING", "1");
/*
* (optional)
* Set ENABLE_RECORDING_DOWNLOAD to "1" to allow the option to download the audio (MP3) portion of a recording
* Set ENABLE_RECORDING_DOWNLOAD to "0" to disable
*/
define("ENABLE_DOWNLOAD_AUDIO", "0");
/*
* (optional)
* Set ENABLE_LICENSE_KEY to "1" to allow users to apply a license key to their account for upgrading. A licene key field will shown up in the user's My Account/Information page.
* Set ENABLE_LICENSE_KEY to "0" to disable
*/
define("ENABLE_LICENSE_KEY", "0");
/*
* (optional)
* Set a purchase page that will be shown to the site admin of a trial site upon signing in. The site must have "license_key" enabled so the admin can install a key file under Accounts page.
*/
define("PURCHASE_PAGE", "");
/*
* (optional)
* Default iPhone app protocol to launch the app from a web page. The protocol should match that set by the app.
*/
define("IPHONE_PROTO", "");
/*
* (optional)
* Default iPhone app name.
*/
define("IPHONE_APP", "");
/*
* (optional)
* Set the iPhone app download url
*/
define("IPHONE_URL", "");
/*
* (optional)
* Default iPad app protocol to launch the app from a web page. The protocol should match that set by the app.
*/
define("IPAD_PROTO", "");
/*
* (optional)
* Default iPad app name.
*/
define("IPAD_APP", "");
/*
* (optional)
* Set the iPhone app download url
*/
define("IPAD_URL", "");
/*
* (optional)
* Enable polling feature
*/
define("ENABLE_POLLING", "1");
?>
