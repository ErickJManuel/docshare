<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */

$minPhpVersion='4.3.0';
$minMySQLVersion='4.0';
$silent=true;
$configFile="server_config.php";
$configTmpFile="server_config-dist.php";
$hostFile="vhost.php";
$sqlFile="persony.sql";
include_once("vinstall/vversion.php");

if (!file_exists($sqlFile))
	$sqlFile='';

$step=1;
if (GetArg('step', $arg))
	$step=$arg;

//defined in vversion.php
if (!isset($version))
	$version='Unknown';

$dbServer='localhost';
$dbName='';

$dirUrl=GetDirUrl();
$serverUrl="http://$dirUrl";
$ssl_serverUrl="https://$dirUrl";

$smtpServer='';
$smtpAuth='false';
$smtpAuthChecked='';
$smtpUser='';
$smtpPass='';
$smtpMessage='';
$freeacUrl='';
$freeacLogin='';
$freeacPass='';
$dbDir='';
$dbDirUrl='';
$cronIp='';

// defined in config.php
/*
if (file_exists($configFile)) {
@include_once($configFile);
	$dbServer=DB_SERVER;
	$dbName=DB_NAME;
	$serverUrl=SITE_URL;
}
*/
$dbLogin='';
$dbPass='';
/*
if ($serverUrl=='') {
	$installedText='No';
} else {
	$installedText='Yes';
}	
*/

//$logDir="_logs";
//$logDir="logs";
$logFile="setup.log";
$phpMessage='';
$setupMessage='';
$postUrl=$_SERVER['PHP_SELF'];
$directory=getcwd();
umask(0);
$mode=fileperms("./");
$octperms=sprintf("%o", $mode);
$dirPerm=substr($octperms, -3);

if (isset($_POST['php_info'])) {
	phpinfo();
	exit();	
}

GetArg('smtp_server', $smtpServer);
GetArg('smtp_auth', $smtpAuth);
GetArg('smtp_user', $smtpUser);
GetArg('smtp_pass', $smtpPass);
GetArg('email_from', $emailFrom);
GetArg('email_to', $emailTo);
GetArg('admin_email', $adminEmail);
GetArg('admin_password', $adminPassword);
GetArg('cron_ip', $cronIp);

if ($emailFrom=='')
	$emailFrom=	"test@".$_SERVER['SERVER_NAME'];

GetArg('server_email', $serverEmail);

GetArg('db_server', $dbServer);
GetArg('db_name', $dbName);
GetArg('db_login', $dbLogin);
GetArg('db_pass', $dbPass);
GetArg('server_url', $serverUrl);
GetArg('ssl_server_url', $ssl_serverUrl);

GetArg('db_dir', $dbDir);
GetArg('db_dir_url', $dbDirUrl);
GetArg('sql_file', $sqlFile);
GetArg('tb_prefix', $tbPrefix);

GetArg('freeac_url', $freeacUrl);
GetArg('freeac_login', $freeacLogin);
GetArg('freeac_pass', $freeacPass);

GetArg('log_dir', $logDir);
$serverUrl=AddSlashToPath($serverUrl);
$ssl_serverUrl=AddSlashToPath($ssl_serverUrl);
$logDir=AddSlashToPath($logDir);
$dbDir=AddSlashToPath($dbDir);
$dbDirUrl=AddSlashToPath($dbDirUrl);

function AddSlashToPath($path)
{
	$len=strlen($path);
	if ($len>0) {
		if ($path[$len-1]!='/' && $path[$len-1]!='\\')
			$path.='/';	
	}
	return $path;
}

function GetDirUrl()
{	
	$dirUrl=$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
	$scriptFile=basename($_SERVER['PHP_SELF']);
	if ($scriptFile!='')
		$dirUrl=str_replace($scriptFile, '', $dirUrl);
		
	return $dirUrl;
}

function ShowError($errMsg)
{
	echo "<div class='error'>".$errMsg."<br></div>\n";
}

function ShowMsg($msg)
{
	// fill the buffer with extra data so flush will work on Win
	echo "<div class='message'>".$msg."<br></div>\n".str_pad(" ", 512);
	flush();
}

function GetArg($key, &$arg)
{
	$keyVal='';
	if (array_key_exists($key, $_GET))
		$keyVal=$_GET[$key];
	else if (array_key_exists($key, $_POST))
		$keyVal=$_POST[$key];
	else
		return false;
	
	$arg=$keyVal;	
	return true;
}

function ErrorExit($msg, $logFp)
{
	if (isset($logFp)) {
		fwrite($logFp, $msg."\r\n");
		fclose($logFp);
	}
	die($msg);
}

function CheckSMTP()
{
require("phpmailer/class.phpmailer.php");
	global $smtpServer, $smtpAuth, $smtpUser, $smtpPass, $emailFrom, $emailTo;
	
	if ($emailTo=='')
		return "Missing email to address";
		
	set_time_limit(60);

	$mail = new PHPMailer();

	if ($smtpServer!='') {
		$mail->IsSMTP();            // set mailer to use SMTP
		$mail->Host = $smtpServer;  // specify main and backup server
		if ($smtpAuth=='true') {
			$mail->SMTPAuth = true;     // turn on SMTP authentication
			$mail->Username = $smtpUser;  // SMTP username
			$mail->Password = $smtpPass; // SMTP password
		}
	}

	$mail->From = $emailFrom;
//	$mail->FromName = "Test";
	$mail->AddAddress($emailTo);

	$mail->WordWrap = 50;                                 // set word wrap to 50 characters
	$mail->IsHTML(true);                                  // set email format to HTML

	$mail->Subject = "Test Mailer";
	$mail->Body    = "This test email is sent from <b>".$_SERVER['SERVER_NAME']."</b>";
	$mail->AltBody = "This test email is sent from ".$_SERVER['SERVER_NAME'];

	if(!$mail->Send())
	{
		return "Mailer Error: " . $mail->ErrorInfo;
	}

	return "Message has been sent successfully";
}

function Setup()
{
	global $configTmpFile, $configFile, $hostFile, $logFile, $sqlFile;
	global $dbServer, $dbName, $dbLogin, $dbPass, $dbDir, $dbDirUrl, $cronIp;
	global $serverUrl, $ssl_serverUrl, $directory, $adminEmail, $adminPassword, $serverEmail;
	global $smtpServer, $smtpAuth, $smtpUser, $smtpPass;
	global $freeacUrl, $freeacLogin, $freeacPass, $logDir;
	global $tbPrefix, $version;

	$ip='';
	if (isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	
	$logFp=@fopen($logFile, "a");
	
	if ($logFp)
		fwrite($logFp, date('Y-m-d H:i:s')." ".$_SERVER['PHP_SELF']." ".$ip."\r\n");
	
	ShowMsg("Connecting to database ".$dbName);
	
	// open db
	$db = mysql_connect($dbServer, $dbLogin, $dbPass);
	if ($db==FALSE)
		ErrorExit("Unable to connect to MySQL server", $logFp);
	
	if (!@mysql_select_db($dbName, $db))
		ErrorExit("Unable to access database ".$dbName, $logFp);
/*	
	$len=strlen($serverUrl);
	if ($len>0 && $serverUrl[$len-1]!='/')
		$serverUrl.='/';
	$len=strlen($ssl_serverUrl);
	if ($len>0 && $ssl_serverUrl[$len-1]!='/')
		$ssl_serverUrl.='/';
*/	
	// read config template file
	$fp=fopen($configTmpFile, "r");
	
	if ($fp) {
		$content=fread($fp, filesize($configTmpFile));
		fclose($fp);

		$content=str_replace('[ADMIN_EMAIL]', $adminEmail, $content);
		$content=str_replace('[ADMIN_PASS]', md5($adminPassword), $content);
		$content=str_replace('[DB_SERVER]', $dbServer, $content);
		$content=str_replace('[DB_NAME]', $dbName, $content);
		$content=str_replace('[DB_LOGIN]', $dbLogin, $content);
		$content=str_replace('[DB_PASS]', $dbPass, $content);
		$content=str_replace('[SERVER_URL]', $serverUrl, $content);
		$content=str_replace('[SSL_SERVER_URL]', $ssl_serverUrl, $content);
		$content=str_replace('[SMTP_SERVER]', $smtpServer, $content);
		$content=str_replace('[SMTP_AUTH]', $smtpAuth, $content);
		$content=str_replace('[SMTP_USER]', $smtpUser, $content);
		$content=str_replace('[SMTP_PASS]', $smtpPass, $content);
		$content=str_replace('[FREEAC_URL]', $freeacUrl, $content);
		$content=str_replace('[FREEAC_LOGIN]', $freeacLogin, $content);
		$content=str_replace('[FREEAC_PASS]', $freeacPass, $content);
		$content=str_replace('[SERVER_EMAIL]', $serverEmail, $content);
		$content=str_replace('[LOG_DIR]', $logDir, $content);
		$content=str_replace('[DB_DIR_PATH]', $dbDir, $content);
		$content=str_replace('[DB_DIR_URL]', $dbDirUrl, $content);
		$content=str_replace('[TB_PREFIX]', $tbPrefix, $content);
		$content=str_replace('[CRON_REQUEST_IP]', $cronIp, $content);

	} else {
		ErrorExit("Missing ".$configTmpFile, $logFp);
	}	
	
	ShowMsg("Writing ".$configFile);

	// write out config file	
	$fp=fopen($configFile, "w");
	if ($fp) {
		fwrite($fp, $content);
		fclose($fp);
		umask(0);
		chmod($configFile, 0777);
	} else {
		ErrorExit("Couldn't write to ".$configFile, $logFp);		
	}
	
	$siteName=$_SERVER['HTTP_HOST'];
	$domainCode="<?xml version=\"1.0\"?>
<cross-domain-policy>
  <allow-access-from domain=\"*\" />
</cross-domain-policy>
";
	// check if crossdomain.xml exists in the home directory
	$homeDir=$_SERVER["DOCUMENT_ROOT"];
	$domainfile=$homeDir."/crossdomain.xml";
	// if not, write it out
	if (!file_exists($domainfile)) {
		$fp=@fopen($domainfile, "w");
		if ($fp) {
			$out="Installing ".$domainfile."<br>\n";
			// fill the buffer with extra data so flush will work on Win
			echo $out.str_pad(" ", 512);
			flush();

			fwrite($fp, $domainCode, strlen($domainCode));
			fclose($fp);
			umask(0);	
			chmod($domainfile, 0777);
		} else {
			// if can't write it out, inform the user to do it manually
print <<<END
<div>
<p>Copy and paste the following code to a file <b>'crossdomain.xml'</b> and put it in your Web site's <b>HOME directory</b>.
This file is required for the Flash Player to access files on this site.
Verify the following url is correct after you have installed the file:
</p>
<table class="tbl">
	<tr>
		<td class="key">crossdomain.xml:</td>
		<td class="val">
		<textarea readonly rows="5" cols="55">$domainCode</textarea>
		</td>
	</tr>
	<tr>
		<td class="key">Crossdomain URL:</td>
		<td class="val"><a href='http://$siteName/crossdomain.xml'>http://$siteName/crossdomain.xml</a></td>
	</tr>
</table>
</div>
<br>
END;
		}
		
	}

	if ($sqlFile!='') {
		if (!file_exists($sqlFile))
			ErrorExit("Couldn't find file ".$sqlFile, $logFp);
		
		ShowMsg("Installing database ".$dbName);
				
		// read sql file
		$fp=fopen($sqlFile, "rb");
		
		if (!$fp)
			ErrorExit("Couldn't open ".$sqlFile, $logFp);
		
		$query='';
		// list all queries that contain a table name here
//		$createTbSql="CREATE TABLE IF NOT EXISTS `";
//		$insertTbSql="INSERT INTO `";

		// needs to match the table prefix in persony.sql
		$defTbPrefix="wc2_";
		// add the table prefix if the line contains a table name
		if ($tbPrefix!='')
			$tbPrefix.="_";

		while (!feof($fp)) {
			// remove any whitespace from both ends
			$buffer=trim(fgets($fp, 4096));
			$len=strlen($buffer);
			if ($len<2 || $buffer[0]=='-') {
				$query='';
				continue;
			}
			
			// add the table prefix if the line contains a table name
/*			if ($tbPrefix!='') {				
				if (strpos($buffer, $createTbSql)!==false)
					$buffer=str_replace($createTbSql, $createTbSql.$tbPrefix."_", $buffer);
				elseif (strpos($buffer, $insertTbSql)!==false)
					$buffer=str_replace($insertTbSql, $insertTbSql.$tbPrefix."_", $buffer);
			}
*/
			$buffer=str_replace("`".$defTbPrefix, "`".$tbPrefix, $buffer);

			$query.=$buffer;
			if ($buffer[$len-1]==';') {
				$sqlResults = mysql_query($query, $db);
				
				if (!$sqlResults)
					ErrorExit('SQL error: ' . mysql_error(), $logFp);

				$query='';
			}
		}		
		fclose($fp);
		
		@require_once("dbobjects/vversion.php");
		
		// create an entry for the version
		$verInfo=array();
		$verInfo['number']=$version;
		$verInfo['rollback_number']=$version;
		$verInfo['source_url']=$serverUrl;
		$verInfo['ssl_source_url']=$ssl_serverUrl;
		$verInfo['date']=date('Y-m-d H:i:s');
		$verInfo['type']='final';
		
		$ver=new VVersion();
		if ($ver->Insert($verInfo)!=ERR_NONE)
			ErrorExit($ver->GetErrorMsg(), $logFp);

	}

/*
	ShowMsg("Setting up database ".$dbName);

require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vsite.php");
	
	$serverInfo=array();
	
	VObject::Find(TB_WEBSERVER, "url", $serverUrl, $serverInfo);

	if (!isset($serverInfo['id'])) {
		// create a vwebserver entry for the site
		$server=new VWebServer();
		$serverInfo['url']=$serverUrl;
		$serverInfo['login']='host'; // need to match $hostFile name
		$serverInfo['password']=(string)mt_rand(100000, 999999);
		$serverInfo['name']='server';
		
		$fp=fopen($hostFile, "w");
		$fileData='<?php \$mcode=\"'.md5($serverInfo['password']).'\"; ?>';
		fwrite($fp, $fileData);
		fclose($fp);

		if ($server->Insert($serverInfo)!=ERR_NONE) {
			ErrorExit($server->GetErrorMsg(), $logFp);
		}
			
		$server->GetValue("id", $serverId);
	} else {
		$serverId=$serverInfo['id'];
		$server=new VWebServer($serverId);
		$serverInfo['path']=$directory;
		if ($server->Update($serverInfo)!=ERR_NONE)
			ErrorExit($server->GetErrorMsg(), $logFp);		
	}
	
	$siteInfo=array();
	VObject::Find(TB_SITE, "webserver_id", $serverId, $siteInfo);
	
	if (!isset($siteInfo['id'])) {		
		// create an entry for the site
		$siteInfo['webserver_id']=$serverId;
		$siteInfo['name']=$serverUrl;
		$site=new VSite();
		if ($site->Insert($siteInfo)!=ERR_NONE)
			ErrorExit($site->GetErrorMsg(), $logFp);
			
	}
*/
	if ($logFp)
		fclose($logFp);

	return '';
}

function CheckPHP()
{
	global $minPhpVersion;
	global $server;
	
	$reqParams=array(
		"allow_url_fopen" => "1",
		"file_uploads" => "1",
		"upload_max_filesize" => "2M",
		"post_max_size" => "2M",
		"safe_mode" => "Off",
		"open_basedir" => "no value or include user directory",
		"max_execution_time" => "30",
		"max_input_time" => "30",
		);
		
	$recmdParams=array(
		"allow_url_fopen" => "1",
		"file_uploads" => "1",
		"upload_max_filesize" => "10M or more",
		"post_max_size" => "10M or more",
		"safe_mode" => "Off",
		"open_basedir" => "no value or include user directory",
		"max_execution_time" => "300 or more",
		"max_input_time" => "300 or more",
		);
		
	
	$parser_version = phpversion();
	if ($parser_version<$minPhpVersion)
		return("ERROR PHP version ".$parser_version." is not supported.\n ($minPhpVersion or above is required)");

/*
	$fileUpload=ini_get('file_uploads');
	if ($fileUpload!="1" && $fileUpload!='On') {
		return("ERROR PHP 'file_uploads' is $fileUpload.\n Set 'file_uploads' to 'On' or '1' in php.ini");
	}
	$urlFopen=ini_get('allow_url_fopen');
	if ($urlFopen!="1" && $urlFopen!='On') {
		return("ERROR PHP 'allow_url_fopen' is $urlFopen.\n Set 'allow_url_fopen' to 'On' or '1' in php.ini");
	}
	$uploadMax=ini_get('upload_max_filesize');
	if ($uploadMax=="") {
		return("ERROR PHP 'upload_max_filesize' is undefined.\n Set 'upload_max_filesize' to 2M or more in php.ini");
	}
	$postMax=ini_get('post_max_size');
	if ($postMax=="") {
		return("ERROR PHP 'post_max_size' is undefined.\n Set 'post_max_size' to 2M or more in php.ini");
	}
*/
	
	$fileErrMsg="\n Make sure PHP user is permitted to create files.";
	$dir=rand();
	$file="./".$dir."/".rand();
	
	umask(0);
	$mode=fileperms("./");
	
	if (!@mkdir($dir, $mode)) {
		
		// only check for safe_mode if we can't create a directory
		// safe_mod==1 is ok if  the script runs with the owner's UID
		if (ini_get("safe_mode")=="1")
			return("ERROR PHP 'safe_mode' is 1.\n Set 'safe_mode' to 0 or Off in php.ini");
		
		return("ERROR can't make directory.".$fileErrMsg);
	}
	
	if (!is_dir($dir))
		return("ERROR directory not found.".$fileErrMsg);
	
	$fp=@fopen($file, 'wb');
	if (!$fp)
		return("ERROR can't open file.".$fileErrMsg);
	
	if (!fwrite($fp, '123', 3))
		return("ERROR can't write to file.".$fileErrMsg);
	
	fclose($fp);
	@chmod($file, $mode);
	
	if (!file_exists($file))
		return("ERROR can't create file.".$fileErrMsg);
	
	if (!@unlink($file))
		return("ERROR can't delete file.".$fileErrMsg);
	
	if (!@rmdir($dir))
		return("ERROR can't remove directory.".$fileErrMsg);
		

$msg="
<table class='phptbl'>
<tr>
<th>php.ini parameter</th><th>Current value</th><th>Required value</th><th>Recommended value</th>
</tr>
";
	foreach ($reqParams as $key => $val) {
		$recmd=$recmdParams[$key];
		$curVal=ini_get($key);
$msg.="
<tr>
<td class='pt_1'>$key</td>
<td class='pt_2'>$curVal</td>
<td class='pt_2'>$val</td>
<td class='pt_3'>$recmd</td>
</tr>
";		
	}
$msg.="
</table>
";

	$msg.="
<br>
<ul>Make sure the following extensions are enabled in php.ini:
<li>php_curl</li>
<li>php_gd2</li>
<li>php_mbstring</li>
</ul>
";

	return $msg;
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Persony Web Conferencing Server Installer</title>
<script type="text/javascript">
<!--
var theStep= <?php echo $step?>;

function CheckSetupForm() {
	var theForm=document.getElementById('db_form_id');

	var ok=confirm("Do you want to set up the server?");
	if (!ok)
		return false;

	if (theForm.db_server.value=='')
	{
		alert("Please enter a value for the \"Server Name\" field.");
		theForm.db_server.focus();
		return (false);
	}
	if (theForm.db_name.value=='')
	{
		alert("Please enter a value for the \"Dababase Name\" field.");
		theForm.db_name.focus();
		return (false);
	}
	if (theForm.db_login.value=='')
	{
		alert("Please enter a value for the \"Database Login\" field.");
		theForm.db_login.focus();
		return (false);
	}
	if (theForm.server_url.value=='')
	{
		alert("Please enter a value for the \"Server URL\" field.");
		theForm.server_url.focus();
		return (false);
	}
	if (theForm.admin_email.value=='')
	{
		alert("Please enter a value for the \"Admin Email\" field.");
		theForm.admin_email.focus();
		return (false);
	}
	if (theForm.admin_password.value=='')
	{
		alert("Please enter a value for the \"Admin Password\" field.");
		theForm.admin_password.focus();
		return (false);
	}
	if (theForm.server_email.value=='')
	{
		alert("Please enter a value for the \"Server Email\" field.");
		theForm.server_email.focus();
		return (false);
	}

	return (true);
}

function MyConfirm(msg) {

	return confirm(msg);

}

function GoToNextStep()
{
	ShowStep(theStep+1);
	return true;
}
function GoToPrevStep(currentStep)
{
	ShowStep(theStep-1);
	return true;
}	
function GetElemHtml(elemId)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		return elem.innerHTML;
	}
	return '';
}

function SetElemDisplay(elemId, display)
{
	var elem=document.getElementById(elemId);

	if (elem)
		elem.style.display = display;		
	return true;
}

function SetElemVisibility(elemId, val)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		elem.style.visibility=val;
	}
	return true
}

function SetElemHtml(elemId, text)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		elem.innerHTML=text;
	}
	return true;
}

function ShowStep(step)
{
	if (step==1) {
		SetElemDisplay('step1', 'inline');
		SetElemDisplay('step2', 'none');
		SetElemDisplay('step3', 'none');
		SetElemDisplay('prev_button', 'none');
		SetElemDisplay('next_button', 'inline');
		
	} else if (step==2) {
		SetElemDisplay('step1', 'none');
		SetElemDisplay('step2', 'inline');
		SetElemDisplay('step3', 'none');
		SetElemDisplay('prev_button', 'inline');
		SetElemDisplay('next_button', 'inline');
	} else if (step==3) {
		SetElemDisplay('step1', 'none');
		SetElemDisplay('step2', 'none');
		SetElemDisplay('step3', 'inline');
		SetElemDisplay('prev_button', 'inline');
		SetElemDisplay('next_button', 'none');
	}
	theStep=step;

//	document.writeln('step='+step+);
	
}

//-->
</script>

<style type="text/css">

body, th, td {
	font-family: Verdana, sans-serif;
    font-size: 0.9em;
}
body { background-color: #E0E0E0; }
.title { font-size: 1.8em; color: #707070; width: 700px; font-weight: 800; padding-bottom: 10px; }
input, textarea {
    font-family: Verdana, sans-serif;
    font-size: 1em;
}
.phptbl { padding: 0; background-color: #F0F0F0; }
.phptbl th { font-size: 75%; }
.phptbl td { padding: 5px; background-color: #E0E0E0; }
.pt_1 { width: 100px; }
.pt_2 { width: 100px; }
.pt_3 { width: 300px; }

.caption { padding: 0px 0 5px 0; font-size: 80%; color: #555;}
.tbl {
	padding: 0;
	width:700px; 
	background-color: #F0F0F0;
}
.tbl td {
    padding: 5px;
    padding-left:  15px;
    background-color: #E0E0E0;
}
.tbl th {
    padding: 10px;
    padding-right: 15px;
    text-align: right;
    background-color: #C0B9C0;
    font-weight: normal;
}
.doit {
	padding-top: 10px;
	width: 700px;
    text-align: left;
}
.key { width:180px; text-align: right; padding: 5px 5px 5px 0; vertical-align:top; font-size: 80%; font-weight: bold; }
.inst { width: 700px; text-align: left; }
.val { padding: 5px 0 5px 0px; vertical-align:top; font-size: 80%; }
.subval { padding: 3px 0 3px 10px; font-size: 90%;}
.inst ol {  vertical-align:top; }
.message { padding: 0 0 0 30px; color: #33f; }
.error { color: #f00; font-weight:bold; }
.install-step {padding: 0; margin: 15px 0 0 0; }
.step-title { font-size: 100%; font-weight: bold; padding: 5px 0 5px 0;}
.buttons { padding: 10px 0 0px 0px;}
.do_btn { padding-top: 10px; text-align: center; }

</style>


</head>


<body><center>

<div class='title'>Persony WC2 Management Server Installer</div>

<?php
if (file_exists($configFile)) {
	ShowError("File '".$configFile."' already exist in the directory. Please remove it first.");
	echo "</center></body></html>";
	exit();
}

if (isset($_POST['setup'])) {
	
	$loginPage="console_signin.php";
	Setup();
	
	ShowMsg("<br><b>Installation completed successfully.</b>");
	ShowMsg("To reinstall, you must remove the file 'server_config.php' from the installation folder.");
	ShowMsg("<br>Go to the <a href='$loginPage'>Login page</a>");
	
	echo "</center></body></html>";

	exit();
}

if (isset($_POST['php_check'])) {
	$phpMessage=CheckPHP();
} else
	if (isset($_POST['smtp_check'])) {
		
		if ($smtpAuth=='true')
			$smtpAuthChecked='checked';
		
		$smtpMessage=CheckSMTP();
	} 
?>

<div class="inst">

<div id='step1' class='install-step'>
Make sure the following conditions are met before you install:
<ul>
	<li>PHP <?php echo $minPhpVersion?> and MySQL <?php echo $minMySQLVersion?> or above are required. Run PHP Check to verify <a href="http://www.persony.com/support_php_config.php">PHP server configurations</a> are compatible.</i>
	<li>For Linux or Unix servers, the Install Directory permissions should be set to 777(read/write/exec by everyone) to allow
	PHP scripts to create/read/write/delete files in the directory. Some PHP servers may not allow scripts to run with 777 permissions.
	You may need to set the directory permssions to 755 instead.</li>
	<li>For Windows IIS servers, make sure to follow instructions here to <a href="http://www.persony.com/support_iis.php">configure IIS</a>. Make sure PHP scripts can write to the meeting directory and "index.php" is a default page for a folder.</li>
	</li>
</ul>

<form method="POST" action="<?php echo $postUrl?>" name="check_form">
<div>Check PHP Server Configuration: 
<span class='subval'><input type="submit" name="php_check" value="PHP Check"></span>
<span class='subval'><input type="submit" name="php_info" value="PHP Info"></span>
</div>
</form>
<div class="message"><?php echo $phpMessage?></div>

</div>

<div id='step2' class='install-step'>
<div class='step-title'>Step 1 of 2: Set Up MySQL</div>
<ul>
	<li>Create a database on the MySQL server (name it 'persony' if you are unsure what to call it.) 
	If the database already exists, you can proceed to the next step.</li>
</ul>
</div>

<div id='step3' class='install-step'>
<div class='step-title'>Step 2 of 2: Set Up Persony Server</div>
<div class="message"><?php echo $setupMessage?></div>
<form id='db_form_id' method="POST" action="<?php echo $postUrl?>" name="db_form">
<input type='hidden' name='step' value='3'>

<table class="tbl">
	<tr>
		<td class="key">Version:</td>
		<td class="val"><?php echo $version?></td>
	</tr>
	<tr>
		<td class="key">*Server URL:</td>
		<td class="val">
		<input type="text" name="server_url" size="50" value="<?php echo $serverUrl?>">
		<div class=caption>The URL of this directory. (e.g. http://www.mysite.com/dir/) </div>
		</td>
	</tr>
	<tr>
		<td class="key">SSL Server URL:</td>
		<td class="val">
		<input type="text" name="ssl_server_url" size="50" value="<?php echo $ssl_serverUrl?>">
		<div class=caption>The SSL URL of this directory. (e.g. https://www.mysite.com/dir/) Leave blank to disable SSL access.</div>
		</td>
	</tr>
	<tr>
		<td class="key">*Admin Email:</td>
		<td class="val">
		<input type="text" name="admin_email" value='<?php echo $adminEmail?>' size="30">
		<div class=caption>Email address of the server administrator</div>
		</td>
	</tr>
	<tr>
		<td class="key">*Admin Password:</td>
		<td class="val">
		<input type="password" name="admin_password" value='' size="8">
		<div class=caption>Password for logging in the server management console page</div>
		</td>
	</tr>
	<tr>
		<td class="key">*MySQL Server Name:</td>
		<td class="val">
		<input type="text" name="db_server" size="30" value="<?php echo $dbServer?>">
		<div class=caption>MySQL server name or IP address.</div>
		</td>
	</tr>
	<tr>
		<td class="key">*MySQL Login:</td>
		<td class="val">
		<input type="text" name="db_login" size="10" value="<?php echo $dbLogin?>">
		<div class=caption>Login name for the MySQL server</div>
		</td>
	</tr>
	<tr>
		<td class="key">MySQL Password:</td>
		<td class="val">
		<input type="password" name="db_pass" size="10" value="<?php echo $dbPass?>">
		<div class=caption>Password for the MySQL server</div>
		</td>
	</tr>
	<tr>
		<td class="key">*Persony Database Name:</td>
		<td class="val">
		<input type="text" name="db_name" size="15" value="<?php echo $dbName?>">
		<div class=caption>The Persony database name. The database must already exist.</div>
		</td>
	</tr>
	<tr>
		<td class="key">Persony Database Initialization File: (Optional)</td>
		<td class="val">
		<input type="text" name="sql_file" size="30" value="<?php echo $sqlFile?>">
		<div class=caption>Initialize the database from the sql file. Leave blank if you do not want to initialize the database.
		For security reasons, you should remove this file from the server after the setup.</div>
		
		Database table prefix: <input type="text" name="tb_prefix" size="20" value="<?php echo $tbPrefix?>">
		<span class=caption>e.g. persony</span>
		<div class=caption>The prefix must be set prior to the creation of the tables.</div>
		</td>
	</tr>
	<tr>
		<td class="key">*Server Email:</td>
		<td class="val">
		Email Address: <input type="text" name="server_email" size="30" value="<?php echo $serverEmail?>">
		<div class=caption>Set the email 'from' address for outbound email.</div>
		</td>
	</tr>
<!--
	<tr>
		<td class="key">Install Directory:</td>
		<td class="val">
		<span><input readonly type="text" name="server_dir" size="50" value="<?php echo $directory?>"></span>
		<span class='subval'>Permissions: <input readonly type="text" name="dir_perm" size="3" value="<?php echo $dirPerm?>"></span>
		<div class=caption>The path of the this directory (read-only)</div>
		</td>
	</tr>
-->
	<tr>
		<td class="key">SMTP Server (optional):</td>
		<td class="val">
		<input type="text" name="smtp_server" size="50" value="<?php echo $smtpServer?>">
		<div class=caption>Set up STMP server for outgoing email. Default is to use PHP mail.</div>
		<div class='subval'><input type="checkbox" name="smtp_auth" value="true" <?php echo $smtpAuthChecked?>>The SMTP server requires authentication</div>
		<div>
		<span class='subval'>User name: <input type="text" name="smtp_user" size="30" value="<?php echo $smtpUser?>"></span>
		<span class='subval'>Password: <input type="password" name="smtp_pass" size="10" value="<?php echo $smtpPass?>"></span>
		</div>
		<div class='subval'>Send test email from: <input type="text" name="email_from" value='<?php echo $emailFrom?>' size="25"></div>		
		<div class='subval'>Send test email to: <input type="text" name="email_to" value='<?php echo $emailTo?>' size="25">	
		<span class='subval'><input onclick="SetElemHtml('smtp_message', 'Sending email... Please wait.'); return true;" type="submit" name="smtp_check" value="Send Email"></span>
		</div>
		<div id='smtp_message' class="message"><?php echo $smtpMessage?></div>
		</td>
	</tr>
	<tr>
		<td class="key">Log Directory (optional):</td>
		<td class="val">
		<input type="text" name="log_dir" size="40" value="<?php echo $logDir?>"> <span class="m_caption">e.g. ../logs/</span>
		<div class=caption>Absolute or relative path of the directory for storing log files. The direcory must exist and writable by PHP scripts.
		If the field is not set log files will not be created.</div>
		<div>
		</div>
		</td>
	</tr>
	<tr>
		<td class="key">Upload Files Directory (optional):</td>
		<td class="val">
		<div class='subval'>Directory path: <input type="text" name="db_dir" size="30" value="<?php echo $dbDir?>"> e.g. ../db_files/</div>
		<div class=caption>Local path of the directory to store uploaded files, such as images. The direcory must exist and writable by PHP scripts.
		The default is 'vimage/' in the installed directory if the field is not set.
		The path should be accessible by the web server (see Directory URL below.)
		It's recommended the directory is NOT under the installed directory so multiple 
		installations can share the same uploaded files.</div>
		<div class='subval'>Directory URL: <input type="text" name="db_dir_url" size="30" value="<?php echo $dbDirUrl?>"> e.g. ../db_files/</div>		
		<div class=caption>URL of the directory path. If the URL starts with a '/', it is an absolute path from the web site home directory.
		Otherwise, it is relative to the installed direcory.
		You do not need to include the server host name in the URL.
		</div>
		</td>
	</tr>
	<tr>
		<td class="key">Authorized IP for cron jobs (optional):</td>
		<td class="val">
		<input type="text" name="cron_ip" size="16" value="<?php echo $cronIp?>">
		<div class=caption>For security reasons, you can limit system cron jobs to be invoked only from the above IP address. 
		If the IP value is not set, the cron job URL may be invoked by anyone.</div>
		<div>
		</div>
		</td>
	</tr>
	<tr>
		<td class="key">Free Teleconference (optional):</td>
		<td class="val">
		<input type="text" name="freeac_url" size="50" value="<?php echo $freeacUrl?>">
		<div class=caption>The default URL for requesting free teleconference numbers from the GoConference server.
		You must have a reseller account with GoConference.</div>
		<div>
		<span class='subval'>Login name: <input type="text" name="freeac_login" size="15" value="<?php echo $freeacLogin?>"></span>
		<span class='subval'>Password: <input type="password" name="freeac_pass" size="15" value="<?php echo $freeacPass?>"></span>
		</div>
		</td>
	</tr>


</table>

<div class='doit'>
<div class=caption>*Required fields.</div>
<div class=caption>When you click "Set Up", a file named <b>"<?php echo $configFile?>"</b> will be created.
You must delete <b>"<?php echo $configFile?>"</b> first if you want to 
run this installer again to modify the setup information.
</div>
</div>
<div class='do_btn'><input onClick="return CheckSetupForm()" type="submit" name="setup" value="Set Up"></div>
</form>
</div>


<div class='buttons'>
<span id='prev_button' class='subval'><input onclick='return GoToPrevStep();' type='button' value='<< Previous Step'></span>
<span id='next_button' class='subval'><input onclick='return GoToNextStep();' type='button' value='Next Step >>'></span>
</div>

</div>
<script type="text/javascript">

<?php
	echo "ShowStep($step);\n";	
?>


</script>

</center></body>
</html>



