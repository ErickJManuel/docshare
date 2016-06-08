<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vuser.php");

$providerId=GetSessionValue('provider_id');
if ($providerId=='') {
	ShowError("Your session has expired.");
	return;
}


$provider=new VProvider($providerId);
$providerInfo=array();
$provider->Get($providerInfo);

$serverUrl=SERVER_URL;
$sslUrl=SSL_SERVER_URL;

$installer="vinstall.php";
$downloadUrl='download/vinstall.zip';

$adminName=$providerInfo['first_name'].' '.$providerInfo['last_name'];
$adminEmail=$providerInfo['admin_email'];
//$fromEmail=$providerInfo['admin_email'];
//$fromName=$providerInfo['company_name'];
$fromEmail=SERVER_EMAIL;
$fromName='';
$productName=$providerInfo['company_name'];
$brandName=$providerInfo['company_name'];


?>
<link href="provider/provider.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="provider/provider.js"></script>


<div id='page_box' class='page' style='visibility:hidden'>
<div class='box_header'><a onclick="hidePageBox(); return false;" href='javascript:void(0)'>
Close <img src="themes/close.gif"></a></div>
<iframe id='page_content' src=''></iframe>
</div>

<div class='heading1'>Add a Web Conferencing Site</div>

<table class="meeting_list">

<form name="install_form">
<input type='hidden' name='server' value='<?php echo $serverUrl?>'>
<input type='hidden' name='ssl_server' value='<?php echo $sslUrl?>'>
<input type='hidden' name='no_update' value='1'>
<input type='hidden' name='provider_id' value='<?php echo $providerInfo['id']?>'>
<input type='hidden' name='provider_login' value='<?php echo $providerInfo['login']?>'>
<input type='hidden' name='provider_mpass' value='<?php echo md5($providerInfo['password'])?>'>
<input type="hidden" name="admin_name" value="<?php echo htmlspecialchars($adminName)?>">
<input type="hidden" name="admin_email" value="<?php echo htmlspecialchars($adminEmail)?>">
<input type="hidden" name="admin_password" value="<?php echo htmlspecialchars($adminPassword)?>">
<input type="hidden" name="from_name" value="<?php echo htmlspecialchars($fromName)?>">
<input type="hidden" name="from_email" value="<?php echo htmlspecialchars($fromEmail)?>">
<input type="hidden" name="win_title" value="<?php echo htmlspecialchars($productName)?>">

<tr>
	<td class="m_key" style="width: 180px; text-align: left">1. Download Installer:</td>
	<td class="m_val">
	Download the file and put the file <b>"vinstall.php"</b> in your Web conferencing site directory.
	<input onclick="parent.location='<?php echo $downloadUrl?>'; return true;" type="button" value="Download">
	<div class="m_caption">- The site needs to support PHP 4.3 or above. See <a target=_blank href='http://persony.com/support_requirements.php'>System Requirements</a> for more details.</div>
	<div class="m_caption">- If you have previously installed a Persony site in the same directory, you should stop here and go to "Manage Sites" to "Reinstall".
	Do not run the installer again here.</div>
	</td>
</tr>
<!--
<tr>
	<td class="m_key" style="text-align: left">2. Enter Site Information:</td>
	<td class="m_val">
	<div class='sub_val1'><b>Site Name</b>: <input type="text" name="win_title" size="30" value="<?php echo htmlspecialchars($productName)?>"></div>
	<div class=m_caption>Name of my Web conferencing site or service. The name will appear in your Web conferencing site window title bar and email.
	You can change this later.</div>
	<hr size=1>

	<div class='sub_val1'><b>Admin Name</b>: <input type="text" name="admin_name" size="30" value="<?php echo htmlspecialchars($adminName)?>"></div>
	<div class='sub_val1'><b>Admin Email</b>: <input type="text" name="admin_email" size="30" value="<?php echo htmlspecialchars($adminEmail)?>"></div>
	<div>
	<span class='sub_val1'><b>Password</b>: <input type="password" name="admin_password" size="8" maxlength='8' value=''></span>
	<span class='sub_val1'>Retype Password: <input type="password" name="admin_password2" size="8" maxlength='8' value=''></span>
	<span class='m_caption'>Up to 8 characters</span>
	<div class="m_caption">Administrator of the site. You wil use the email address and password to sign in to the site. 
	You can change the password later after signing in.</div>
	</div>
	<hr size=1>

	<div class='sub_val1'><b>Site Email Name</b>: <input type="text" name="from_name" size="30" value="<?php echo htmlspecialchars($fromName)?>"></div>
	<div class='sub_val1'><b>Site Email Address</b>: <input type="text" name="from_email" size="30" value="<?php echo htmlspecialchars($fromEmail)?>"></div>
	<div class=m_caption>Email name and address for outgoing email from the site. The email address does not need to exist. You can change this later.</div>
	
	</td>
</tr>
-->
<tr>
	<td class="m_key" style="text-align: left">2. Launch Installer:</td>
	<td class="m_val">
	<div>Enter the installer URL below and click <b>Launch Installer</b>. </div>
	<input type="text" name="site_url" value="http://" size=50>/<?php echo $installer?> <br>
	<input type='submit' name='submit' value='Launch Installer' onclick='launchInstaller(document.install_form); return false'>
	
	<div class="m_caption">- Your web site URL must be accessible by a Persony server to complete the installation. Do not use a private domain name or IP addresses that does not work outside of your network.</div>
	
	</td>
</tr>
</form>

<tr>
	<td class="m_key" style="text-align: left">3. Log In:</td>
	<td class="m_val">
	Log in to the web conferencing site:
	<input type='button' name='login' value='Log In' onclick='gotoSite(document.install_form);'>

	</td>
</tr>

</table>


