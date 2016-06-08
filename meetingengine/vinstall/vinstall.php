<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
//require_once("config.php");
//diff test

$installerVersion='2.2.24.1';

$minPhpVersion="4.3.0";
$serverDir="vinstall";
$scriptDir="scripts";
$versionFile="vversion.php";
$scriptFile="vscript.php";
//$scriptFile="api.php";
$logFile="vinstall.log";
$configFile='site_config.php';

GetArg("silence", $silence);
GetArg("version", $version);
GetArg("install", $install);
GetArg("storage", $storageServer);
GetArg("check", $check);
GetArg("php_info", $phpInfo);
GetArg("no_update", $noUpdate);

GetArg("server", $serverArg);
GetArg("ssl_server", $sslServerArg);
GetArg("brand", $brandArg);
GetArg("win_title", $winTitleArg);
GetArg("login", $loginArg);
GetArg("password", $passwordArg);

GetArg("provider_id", $providerId);
GetArg("provider_login", $providerLogin);
GetArg("provider_mpass", $providerPass);
GetArg("admin_name", $adminName);
GetArg("admin_email", $adminEmail);
GetArg("admin_password", $adminPassword);
GetArg("from_name", $fromName);
GetArg("from_email", $fromEmail);
GetArg("site_url", $siteUrl);
GetArg("change_title", $changeTitle);

$len=strlen($siteUrl);
if ($len>0 && $siteUrl[$len-1]!='/') {
	$siteUrl.='/';
}


$len=strlen($serverArg);
if ($len>0 && $serverArg[$len-1]!='/') {
	$serverArg.='/';
}

$len=strlen($sslServerArg);
if ($len>0 && $sslServerArg[$len-1]!='/') {
	$sslServerArg.='/';
}
if ($sslServerArg=='')
	$sslServerArg=$serverArg;
	
$server=$serverArg;
if ($siteUrl!='' && strpos($siteUrl, "https")===0)
	$server=$sslServerArg;
elseif (isset($_SERVER['HTTPS']))
	$server=$sslServerArg;

$login=$loginArg;
$password=$passwordArg;
$brand=$brandArg;
$winTitle=$winTitleArg;
$serverUrl=$server;

// create a new site
$newSite=false;
if ($loginArg=='')
	$newSite=true;

$step=1;

$siteName=$_SERVER['HTTP_HOST'];
$domainCode="<?xml version=\"1.0\"?>
<cross-domain-policy>
  <allow-access-from domain=\"*\" />
</cross-domain-policy>
";

function ShowError($errMsg)
{
	echo "<div class='error'>".$errMsg."<br></div>\n";
}
function GetContents($filename) {
/*
	$parser_version = phpversion();

	if ($parser_version < "4.3.0") { 

		$fd = @fopen($filename, "rb");
		if ($fd) {
			$content = fread($fd, filesize($filename));
			fclose($fd);
			return $content;
		} else {
			return false;
		}
	} else {
*/
		return @file_get_contents($filename);
//	}
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

function ErrorExit($msg)
{
	global $silence;
	
	if ($silence=='') {
		echo ("<div class='error'>$msg</div>");
		echo "</body></html>";
		exit();
	} else {
		die("ERROR ".$msg);
	}
}

function ShowProgress($msg)
{
	global $silence;

	if ($silence=='') {
print <<<END
<script type="text/javascript">
	AddText("$msg");
</script>
END;
		flush();
	}
		
}

function ShowMessage($msg)
{
	global $silence;

	if ($silence=='') {
print <<<END
<script type="text/javascript">
	SetElemHtml("message", "$msg");
</script>
END;
		flush();
	}
		
}

function ShowDomainText()
{
	global $silence;

	if ($silence=='') {
print <<<END
<script type="text/javascript">
	SetElemDisplay("domain_text", 'inline');
</script>
END;
		flush();
	}
		
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
	if (phpversion()<"4.3.0") {
		if (ini_get('always_populate_raw_post_data')!="1")
			ini_set('always_populate_raw_post_data', "1");
		
		if (ini_get('always_populate_raw_post_data')!="1") {
			return("ERROR PHP 'always_populate_raw_post_data' is 0.\n Set 'always_populate_raw_post_data' to 1");
		}
	}  
*/
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
/*
	$safeMode=ini_get('safe_mode');
	if ($safeMode=='')
		$safeMode='Off';
	$openBaseDir=ini_get('open_basedir');
	if ($openBaseDir=='')
		$openBaseDir='no vaule';
	$maxExeTime=ini_get('max_execution_time');
	$maxInputTime=ini_get('max_input_time');
*/	
	$fileErrMsg="\n Make sure PHP user is permitted to create files.";
	$dir=rand();
//	$file="./".$dir."/".rand().".txt";
	$file=$dir."/".rand().".txt";
	
	umask(0);
	$mode=fileperms("./");
	
	if (!@mkdir($dir, $mode)) {
		
		// only check for safe_mode if we can't create a directory
		// safe_mod==1 is ok if  the script runs with the owner's UID
		if (ini_get("safe_mode")=="1")
			return("ERROR PHP 'safe_mode' is 1.\n Set 'safe_mode' to 0");
		
		return("ERROR can't create a test directory.".$fileErrMsg);
	}
	sleep(1);
	
	if (!is_dir($dir))
		return("ERROR directory not found.".$fileErrMsg);	
	
	$fp=@fopen($file, 'wb');
	if (!$fp)
		return("ERROR can't open a test file.".$fileErrMsg);
		
	if (flock($fp, LOCK_EX)) {
		
		if (!fwrite($fp, "123"))
			return("ERROR can't write to a test file.".$fileErrMsg);
			
		flock($fp, LOCK_UN);
		
	} else {
		return("ERROR can't lock a file. Your OS may allow file locking.");		
	}
	fclose($fp);
	@chmod($file, $mode);
	
	if (!file_exists($file))
		return("ERROR can't create a test file.".$fileErrMsg);
		
	$content=@file_get_contents($file);
	if ($content===false)
		return("ERROR can't read a test file.".$fileErrMsg);
			
	if (!@unlink($file))
		return("ERROR can't delete a test file.".$fileErrMsg);
	
	if (!@rmdir($dir))
		return("ERROR can't remove a test directory.".$fileErrMsg);
		
	// test usleep
	$canusleep=false;
	if (function_exists('usleep')) {
		list($usec, $sec) = explode(" ",microtime());
		$startTime=((float)$usec + (float)$sec);
		
		usleep(300*1000);
		
		list($usec, $sec) = explode(" ",microtime());
		$nowTime=((float)$usec + (float)$sec);
		
		$delay=$nowTime-$startTime;
		
		if ($delay>0.2) {
			// usleep working
			$canusleep=true;
		}
	}
/*			
	$octperms=sprintf("%o", $mode);
	$filePerms=substr($octperms, -3)."\n";	
	
	$version_comment='OK';
	$upload_comment='>10M recommended to allow uploading large files.';
	$post_comment='>10M recommended to allow uploading large files.';
	$safe_comment='Off recommended';
	$open_comment='no value or include user directory';
	$file_comment='777 or 755';
	$execution_comment='>300 recommended to allow uploading large files.';
*/
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

	if (!$canusleep) {
		$msg.="<div>PHP function 'usleep' is not supported on your system. The system performance may not be optimial.</div>";		
	}
/*
	if ($server!='') {
		$serverFile=$server."vinstall/vversion.php";
		$content=GetContents($serverFile);
		if ($content===false)
			$msg.="<div>ERROR couldn't get response from '$serverFile'</div>";
	}
*/
	return $msg;
}

function GetVersion($url, &$version)
{
	// get the version
	if ($html = GetContents($url)) {
		
		if (strlen($html)>16)
			return ("Invalid server response");
		
		$version=$html;
		if ($version=='') {
			return ("Couldn't ger server version");
		}
	} else {
		return ("Couldn't get $url");
	}
	return '';
}

// recursive mkdir
function mkdirs($dirPath, $mode)
{
	if ($dirPath=='.' || $dirPath=='' || $dirPath=='/')
		return true;
	
	if (is_dir($dirPath)) {
		return true;
	}
	
	$parentPath = dirname($dirPath);
	if (!mkdirs($parentPath, $mode)) 
		return false;
	
	return @mkdir($dirPath, $mode);
}

function Install($server, $baseDir, $fromDir, $toDir, $mode, $logFp, $files=null)
{
//	global $versionFile, $scriptFile, $silence;
	global $scriptFile, $silence;
	
//	$versionNum=str_replace(".", "", $version);
	$serverScript=$server.$scriptFile;

	if(!$files)
	{	
		$content=GetContents($serverScript."?s=vftp&cmd=listdir&arg1=$fromDir");
	//	$content=GetContents($serverScript."?cmd=GET_INSTALL_DIR&dir=$fromDir");
		if ($content===false)
			return ("Couldn't get ".$serverScript);
		else if ($content=='OK' && $fromDir==$baseDir) {
			return ("Directory '$fromDir' is empty or not found on $server");		
		}	
		$files=explode("\n", $content);
	}
	
	$count=count($files);
	
	if ($files[0]!='OK') {
		return ($content);
	}
	
	@set_time_limit(300);
		
	umask(0);
	$mode=fileperms("./");
	$baseDirS=$baseDir."/";
	
	for ($i=1; $i<$count; $i++) {
		
		$filename=$files[$i];
		$len=strlen($filename);
		if ($len>1 && $filename[$len-1]=='/') {
			// a directory
			if (($errMsg=Install($server, $baseDir, $filename, $toDir, $mode, $logFp))!='')
				return $errMsg;
			
		} else if ($len>1) {
				
			$localFile=str_replace($baseDirS, "", $filename);
			$localFile=$toDir.$localFile;
			
			if ($silence=='') {
				ShowProgress("Installing ".$localFile);;

			}
			
			
			$theDir=dirname($filename);
			$localDir=str_replace($baseDir, "", $theDir);
			if (strlen($localDir)>1 && $localDir[0]=='/')
				$localDir=substr($localDir, 1);
				
			$localDir=$toDir.$localDir;
			if (!mkdirs($localDir, $mode))
				return("Couldn't create ".$localDir);
			
			$getScript=$serverScript."?s=vftp&cmd=get&arg1=$filename";
//			$getScript=$serverScript."?cmd=GET_INSTALL_FILE&file=$filename";
			$content=GetContents($getScript);
			if ($content===false)
				return ("Couldn't get ".$getScript);
			
			if (file_exists($localFile))
				@unlink($localFile);
			
			$fp=@fopen($localFile, "w");
			if (!$fp) {
				echo("Couldn't create ".$localFile."<br>\n");
			} else {
				fwrite($fp, $content, strlen($content));
				fclose($fp);
				@chmod($localFile, $mode);
			}
		}
		
	}
	return '';
	
}

if ($phpInfo!='') {	
	phpinfo();
	exit();
} 

// update the installer first if the no_update arg is not set
if ($noUpdate=='' && $server!='') {
	$hasNewInstaller=false;
	$theFile=$_SERVER['PHP_SELF'];
	$theFilename=basename($theFile);
	$getScript=$server.$scriptFile."?s=vftp&cmd=get&arg1=$serverDir/$theFilename";
//	$getScript=$server.$scriptFile."?cmd=GET_INSTALL_FILE&file=$serverDir/$theFilename";
	$content=GetContents($getScript);
	if ($content!==false) {
		$filePath=$theFilename;
		if (file_exists($filePath))
			@unlink($filePath);
		
		$fp=@fopen($filePath, "w");
		if ($fp) {
			fwrite($fp, $content, strlen($content));
			fclose($fp);
			$mode=fileperms("./");
			umask(0);

			@chmod($filePath, $mode);
			$hasNewInstaller=true;
		}
	}	
	
	if ($hasNewInstaller) {
		if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']!='') {
			$theFile.="?".$_SERVER['QUERY_STRING'];
			$theFile.="&no_update=1";
		} else {
			$theFile.="?no_update=1";
		}
		//build the post string
		$poststring='';
		foreach($_POST AS $key => $val){
			$poststring .= $key . "=" . rawurlencode($val) . "&";
		}
		if ($poststring!='')
			$theFile.="&".$poststring;
		$theFile.="&".rand();
		header("Location: $theFile");
		exit();			
	}
}
	
if ($silence=='') {
		print <<<END

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Persony Server Installer</title>
<script type="text/javascript">
<!--

function CheckMyForm(form) {

	if (form.server.value=='')
	{
		alert("Please enter a value for the \"Server\" field.");
		form.server.focus();
		return (false);
	}
/*
	if (form.version.value=='')
	{
		alert("Please enter a value for the \"Version\" field.");
		form.version.focus();
		return (false);
	}
*/
	if (form.login.value!='') {
		if (form.password.value=='')
		{
			alert("Please enter a value for the \"Code\" field.");
			form.password.focus();
			return (false);
		}
	} else if (form.provider_id.value!='') {
	    if (form.admin_email.value=='')
	    {
		    alert("Please enter a value for the \"amdin_email\" field.");
		    form.admin_email.focus();
		    return (false);
	    }
	    if (form.admin_name.value=='')
	    {
		    alert("Please enter a value for the \"admin_name\" field.");
		    form.admin_name.focus();
		    return (false);
	    }
	    if (form.from_email.value=='')
	    {
		    alert("Please enter a value for the \"site_email_address\" field.");
		    form.from_email.focus();
		    return (false);
	    }
	    if (form.from_name.value=='')
	    {
		    alert("Please enter a value for the \"site_email_name\" field.");
		    form.from_name.focus();
		    return (false);
	    }
	    if (form.admin_password.value=='')
	    {
		    alert("Please enter a value for the \"password\" field.");
		    form.admin_password.focus();
		    return (false);
	    }
	    if (form.win_title.value=='')
	    {
		    alert("Please enter a value for the \"site_name\" field.");
		    form.win_title.focus();
		    return (false);
	    }	
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
	return true;
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
//		SetElemDisplay('step3', 'none');
		SetElemDisplay('prev_button', 'none');
		SetElemDisplay('next_button', 'inline');
		
	} else if (step==2) {
		SetElemDisplay('step1', 'none');
		SetElemDisplay('step2', 'inline');
//		SetElemDisplay('step3', 'none');
		SetElemDisplay('prev_button', 'inline');
//		SetElemDisplay('next_button', 'inline');
		SetElemDisplay('next_button', 'none');
/*
	} else if (step==3) {
		SetElemDisplay('step1', 'none');
		SetElemDisplay('step2', 'none');
		SetElemDisplay('step3', 'inline');
		SetElemDisplay('prev_button', 'inline');
		SetElemDisplay('next_button', 'none');
*/
	}
	theStep=step;
	
}

function AddText(newText) {

	document.prog_form.text_box.value += newText;
	document.prog_form.text_box.value += '\\n';
    document.prog_form.text_box.scrollTop=document.prog_form.text_box.scrollHeight;
	
}

//-->
</script>

<style type="text/css">

body, th, td {
	font-family: Verdana, sans-serif;
    font-size: 0.8em;
}
body { background-color: #E0E0E0; }
input, textarea {
    font-family: Verdana, sans-serif;
    font-size: 1em;
}
.title { font-size: 1.6em; color: #707070; width: 700px; font-weight: 800; padding-bottom: 10px; }
.caption { padding: 0px 0 5px 0; font-size: 0.84em; color: #555;}
.tbl {
	padding: 0;
	width:700px; 
	background-color: #F0F0F0;
}
.tbl td {
    padding: 10px;
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
	width: 700px;
    text-align: left;
}
.phptbl { padding: 0; background-color: #F0F0F0; }
.phptbl td { padding: 5px; background-color: #E0E0E0; }
.pt_1 { width: 100px; }
.pt_2 { width: 100px; }
.pt_3 { width: 300px; }
.caption { font-size: 80%; color: #555;}
.key { width:140px; text-align: right; padding: 8px 10px 8px 0; vertical-align:top; font-size: 80%; font-weight: 900; }
.subkey { width: 140px; font-size: 90%; color: #333; font-weight: 700; }
.inst { width: 700px; text-align: left; }
.install-step { font-size: 1em; font-weight: 700; padding: 5px; }
.val { padding: 8px 0 8px 0px; vertical-align:top;}
.inst ol {  vertical-align:top; }
.message { width: 700px; padding: 0 0 0 30px; color: #33f; text-align:left; font-size: 110%; font-weight: 900; }
.error { color: #f00; }
.buttons { padding: 10px 0 0px 0px;}
.doit {
	padding: 10px;
    text-align: center;
}

#domain_text {
    text-align: left;
}

</style>

</head>
<body><center>

END;
	if($storageServer != '')
	{
		print <<<END
		<div class='title'>Persony Web Conferencing 2.0 Storage Server Installer</div>
END;
	}
	else
	{
		print <<<END
<div class='title'>Persony Web Conferencing 2.0 Site Installer</div>
END;
	}
	echo "Version ".$installerVersion."<br>\n";
}


if ($install !='' || $changeTitle!='') {
	
	// if config file exists, login and password must match
	if (file_exists($configFile)) {
		include($configFile);
		
		if ($newSite)
			ErrorExit("A previous installation already exists in this directory.");
		if ($login!=$loginArg || $passcode!=md5($passwordArg))
			ErrorExit("Login or password doesn't match a previous installation. Delete the file '$configFile' to reset the password.");

		if ($server!='')
			$serverUrl=$server;
		$server=$serverUrl;	// defined in config.php

		if ($brandArg!='')
			$brand=$brandArg;
		
		if ($winTitleArg!='')
			$winTitle=$winTitleArg;
		
	} else {

		if (!$newSite) {
			if ($loginArg=='' || $passwordArg=='')
				ErrorExit('Missing login or password');
		}
		if ($serverArg=='')
			ErrorExit('Missing server');

	}
	$passcode=md5($password);
	
	if ($changeTitle!='') {
		$fp=@fopen($configFile, 'rb');
		if (!$fp)
			ErrorExit("Couldn't open file $configFile");
		$content=fread($fp, filesize($configFile));
		fclose($fp);
		
		// replace old winTitle with the new one
		$key="\$winTitle=";
		$pos1=strpos($content, $key);
		$len=strlen($key);
		$pos2=strpos($content, ";", $pos1+$len);
		$newText=substr($content, 0, $pos1+$len)."'$winTitle'".substr($content, $pos2);	
		
		$fp=@fopen($configFile, "wb");
		if (!$fp)
			ErrorExit("Couldn't open file $configFile");
		fwrite($fp, $newText);
		fclose($fp);
		
		echo "OK";		
		exit();
	}
	
	if ($silence=='') {
print <<<END
	<form name='prog_form' class='prog_form'>
	<textarea name='text_box' rows='12' cols='70'></textarea>
	</form>
	<div id='domain_text' style='display:none'>
	<table class='tbl'>
		<tr><td></td>
		<td class='val'>
		Copy and paste the following code to a file <b>'crossdomain.xml'</b> and put it in your Web site's <b>HOME directory</b>.
		This file is required for the Flash Player to access files on this site.
		Verify the following url is correct after you have installed the file:

		</td>
		</tr>
		<tr>
			<td class='key'>crossdomain.xml:</td>
			<td class='val'>
			<textarea readonly rows='4' cols='55'>$domainCode</textarea>
			</td>
		</tr>
		<tr>
			<td class='key'>Crossdomain URL:</td>
			<td class='val'><a href='http://$siteName/crossdomain.xml'>http://$siteName/crossdomain.xml</a></td>
		</tr>
	</table>
	</div>
	<p>
	<div id='message'></div>

</center></body>
</html>
END;
	}
	
	if ($newSite) {
		
		$names=explode(" ", $adminName);
		$firstName=isset($names[0])?$names[0]:'';
		$lastName=isset($names[1])?$names[1]:'';
		
		$params=array(
			'provider_id' => $providerId,
			'provider_login' => $providerLogin,
			'provider_mpass' => $providerPass,
			'site_url' => $siteUrl,
			'admin_login' => $adminEmail,
			'admin_first_name' => $firstName,
			'admin_last_name' => $lastName,
			'admin_email' => $adminEmail,
			'admin_password' => $adminPassword,
			'site_email_name' => $fromName,
			'site_email_address' => $fromEmail,
			'site_title' => $winTitle
		);
//		print_r($params);

		ShowProgress("Creating site...");
					
		$requestData="cmd=ADD_PROVIDER_BRAND";
		
		foreach ($params as $key => $val)
			$requestData.="&".$key."=".rawurlencode($val);
				
		$resp=@file_get_contents($server."api.php?".$requestData);
		if (($pos1=strpos($resp, "OK"))>0)
			$resp=substr($resp, $pos1);
					
		if ($resp===false) {
			ErrorExit("Error: couldn't get a response from the Persony server");	
					
		} else if (strpos($resp, "OK")===0) {
			// parse the response data
			list($ok, $respData)=explode("\n", $resp);
			if (isset($respData))
				$dataItems=explode("&", $respData);
			foreach ($dataItems as $anItem) {
				list($dataKey, $dataVal)=explode("=", $anItem);
				if ($dataKey=='brand')
					$brand=$dataVal;
				elseif ($dataKey=='login')
					$login=$dataVal;
				elseif ($dataKey=='password')
					$passcode=$dataVal;
			}
			
		} else if (strpos($resp, "ERROR")!==false) {
			list($err, $errMsg)=explode("\n", $resp);
			ErrorExit("Error: $errMsg");		
		} else {
			ErrorExit("Error: invalid response returned from the Persony server: ".htmlspecialchars($resp));
		}
		
		
	}

	
	// write out the config file
	$content="<?php
\$login='$login';
\$passcode='$passcode';
\$serverUrl='$serverUrl';
\$brand='$brand';
\$winTitle='$winTitle';
?>";

	$fp=@fopen($configFile, 'w');
	if (!$fp)
		ErrorExit("Couldn't open file $configFile");
	fwrite($fp, $content);
	fclose($fp);
	
	umask(0);	
	@chmod($configFile, 0777); // so the file can be deleted
	
	if ($version=='') {
		// get the current version number on the server
		if (($errMsg=GetVersion($server.$versionFile, $version))!='') {
			ShowError($errMsg);
		}
	}
	
	$ip='';
	if (isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	
	$logFp=fopen($logFile, "a");
	if ($logFp)
		fwrite($logFp, date('Y-m-d H:i:s')." ".$ip."\r\n");
			
	// write out the host file		
	$filename="vh".$login.".php";
	//$code=md5($password);	
	$code=$passcode;	
	$content="<?php \$mcode='$code'; ?>";
	$fp=@fopen($filename, "w");
	if (!$fp)
		ErrorExit("Couldn't create ".$filename);			
	fwrite($fp, $content, strlen($content));
	fclose($fp);
						
	$mode=fileperms("./");

	if ($silence=='')
		ShowMessage("Installing files from the source server. Please wait...");

	// install web host
	if (($errMsg=Install($server, $serverDir, $serverDir, "", $mode, $logFp))!='') {
		if ($logFp)
			fclose($logFp);
		ErrorExit($errMsg);
	}
	if (($errMsg=Install($server, $scriptDir, $scriptDir, $scriptDir."/", $mode, $logFp))!='') {
		if ($logFp)
			fclose($logFp);
		ErrorExit($errMsg);
	}

	// check if crossdomain.xml exists in the home directory
	$homeDir=$_SERVER["DOCUMENT_ROOT"];
	$domainfile=$homeDir."/crossdomain.xml";
	// if not, write it out

	if (!file_exists($domainfile)) {
		$fp=@fopen($domainfile, "w");
		if ($fp) {
			ShowProgress("Installing ".$domainfile);

			fwrite($fp, $domainCode, strlen($domainCode));
			fclose($fp);
			umask(0);	
			@chmod($domainfile, 0777);
		} else {
			// if can't write it out, inform the user to do it manually
			if ($silence=='') {
				ShowDomainText();

			}
		}		
	}
		
	if ($logFp) {
		fclose($logFp);
	}
	if ($silence=='') {

		ShowMessage("Installation completed successfully.");


	} else {
		echo "OK";
		exit();
	}	
} else {
		
	$phpMsg='';	
	if ($check!='') {		
		$phpMsg=CheckPHP();
		if ($silence!='') {
			echo $phpMsg;
			exit();
		}
	}
	
	if ($silence!='') {
		echo "OK";
		exit();
	}
		
	$postUrl=$_SERVER['PHP_SELF'];

	if ($version=='' && $server!='') {
		// get the current version number on the server
		if (($errMsg=GetVersion($server.$versionFile, $version))!='') {
			ShowError($errMsg);
		}
	}
	
	
	$stepCount=1;
	
	$adminName=htmlspecialchars($adminName);
	$adminEmail=htmlspecialchars($adminEmail);
	$adminPassword=htmlspecialchars($adminPassword);
	$fromName=htmlspecialchars($fromName);
	$fromEmail=htmlspecialchars($fromEmail);
	$providerLogin=htmlspecialchars($providerLogin);
	$providerPass=htmlspecialchars($providerPass);
	$winTitle=htmlspecialchars($winTitle);


	print <<<END
<div class="inst">
<div id='step$stepCount'>

<div class='install-step'>Step $stepCount. Make sure the following conditions are met before you install:</div>
<ul>
	<li>PHP $minPhpVersion or above is required. Run PHP Check to verify <a target=_blank href="http://www.persony.com/support_php_config.php">PHP server configurations</a> are compatible.</li>
	<li>For Linux or Unix servers, the Install Directory permissions should be set to 777(read/write/exec by everyone) to allow
	PHP scripts to create/read/write/delete files in the directory. Some PHP servers may not allow scripts to run with 777 permissions.
	You may need to set the directory permssions to 755 instead.</li>
	<li>For Windows IIS servers, make sure to follow instructions here to <a target=_blank href="http://www.persony.com/support_iis.php">configure IIS</a>.</li>
	<li>Optional Settings: To allow uploading large video or audio files to "My Library", set the following parameters in 'php.ini'. 
	<ul>
		<li>'upload_max_filesize' and 'post_max_size' should be set to the max. file size allowed. (e.g. 10M)</li>
		<li>'max_execution_time' and 'max_input_time' should be set to 300 or more.
		The execution time should be long enough to allow the largest file upload to complete.
		</li>
	</ul>	
	</li>
</ul>

<form method="POST" action="$postUrl" name="check_form">
<p>Check PHP Server Configuration: &nbsp;&nbsp;
<input type="submit" name="check" value="PHP Check">&nbsp;&nbsp;
<input type="submit" name="php_info" value="PHP Info">
<input type="hidden" name="login" value="$login">
<input type="hidden" name="storage" value="$storageServer">
<input type="hidden" name="password" value="$password">
<input type="hidden" name="no_update" value="1">
<input type='hidden' name="brand" value="$brand">
<input type='hidden' name="win_title" value="$winTitle">
<input type="hidden" name="server" value="$server">
<input type="hidden" name="version" value="$version">
<input type="hidden" name="site_url" value="$siteUrl">
<input type="hidden" name="admin_name" value="$adminName">
<input type="hidden" name="admin_email" value="$adminEmail">
<input type="hidden" name="admin_password" value="$adminPassword">
<input type="hidden" name="from_name" value="$fromName">
<input type="hidden" name="from_email" value="$fromEmail">
<input type="hidden" name="provider_id" value="$providerId">
<input type="hidden" name="provider_login" value="$providerLogin">
<input type="hidden" name="provider_mpass" value="$providerPass">
<input type="hidden" name="site_url" value="$siteUrl">

</form>
<div class="message">$phpMsg</div>

</div>
END;

	$stepCount++;
	
	$providerLogin=htmlspecialchars($providerLogin);
	$providerPass=htmlspecialchars($providerPass);
	$winTitle=htmlspecialchars($winTitle);

print <<<END

<div id='step$stepCount'>

<div class='install-step'>Step $stepCount. Install PHP scripts from the Source Server to the current directory:</div>

<form onSubmit="return CheckMyForm(this)" method="POST" action="$postUrl" name="install_form">
<input type="hidden" name="no_update" value="1">
<input type='hidden' name="brand" value="$brand">
<input type='hidden' name="win_title" value="$winTitle">
<input type="hidden" name="login" value="$login">
<input type="hidden" name="storage" value="$storageServer">
<input type="hidden" name="provider_id" value="$providerId">
<input type="hidden" name="provider_login" value="$providerLogin">
<input type="hidden" name="provider_mpass" value="$providerPass">

<table class="tbl">
	<tr>
		<td class="key">Source Server:</td>
		<td class="val">
		<input type="text" name="server" size="60" value="$server">
		<div class=caption>URL of the server to install the PHP scripts from.</div>
		</td>
	</tr>
END;
/*
	<tr>
		<td class="key">Version to Install:</td>
		<td class="val">
		<input readonly type="text" name="version" size="16" value="$version">
		<span class=caption>Current version on the Source Server.</span>


if (file_exists($versionFile)) {
		echo "<div>Current installed version: <b>";
		include($versionFile);
		echo "</b></div>\n";
}
	
print <<<END
		</td>
	</tr>
END;
*/

if (!$newSite) {
print <<<END
	<tr>
		<td class="key">Authentication Code:</td>
		<td class="val">
		<input type="text" name="password" size="8" maxlength="8" value="$password">
		<span class='caption'>The code must match the record for the site.</span>
	</tr>	
END;
} else {
print <<<END
<tr>
	<td class="key">Site Information:</td>
	<td class="val">
	<div><span class='subkey'>Site URL</span>: <input type="text" name="site_url" size="40" value="$siteUrl"></div>
	<div><span class='subkey'>Site Name</span>: <input type="text" name="win_title" size="30" value="$winTitle"></div>
	<div class='caption'>Name of my Web conferencing site or service. The name will appear in your Web conferencing site window title bar and email.
	You can change this later.</div>
	<hr size=1>

	<div><span class='subkey'>Admin Name</span>: <input type="text" name="admin_name" size="30" value="$adminName"></div>
	<div><span class='subkey'>Admin Email</span>: <input type="text" name="admin_email" size="30" value="$adminEmail"></div>
	<div><span class='subkey'>Password</span>: <input type="text" name="admin_password" size="8" maxlength='8' value=''>
	<span class='caption'>Up to 8 characters</span></div>
	<div class="caption">Administrator of the site. You wil use the email address and password to sign in to the site. 
	You can change the password later after signing in.</div>
	<hr size=1>

	<div><span class='subkey'>Site Email Name</span>: <input type="text" name="from_name" size="30" value="$fromName"></div>
	<div><span class='subkey'>Site Email Address</span>: <input type="text" name="from_email" size="30" value="$fromEmail"></div>
	<div class=caption>Email name and address for outgoing email from the site. The email address does not need to exist. You can change this later.</div>
	
	</td>
</tr>	
END;

}

print <<<END

</table>
<div class='doit'>
<input onclick="return MyConfirm('Do you want to start the installation?')" type="submit" name="install" value="Install">
</div>

</form>
</div>

<div class='buttons'>
<span id='prev_button' class='subval'><input onclick='return GoToPrevStep();' type='button' value='<< Previous Step'></span>
<span id='next_button' class='subval'><input onclick='return GoToNextStep();' type='button' value='Next Step >>'></span>
</div>
</div>

END;
	
}


if ($silence=='') {
	
print <<<END
<script type="text/javascript">
	ShowStep($step);
</script>
</center></body>
</html>
END;
} else {
	echo "OK";
}

?>