<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vstorageserver.php");

$installer="vinstall.php";
$installerZip="download/vinstall.zip";

$siteUrl=SITE_URL;
$server=$siteUrl;

$storageInfo=array();
if (GetArg('id', $storageServerId)) {
	$storageServer=new VStorageServer($storageServerId);
	$storageServer->Get($storageInfo);
	$target=$storageInfo['url'];
	if (!isset($target) || $target=='') {
		ShowError("Storage server url not set");
		return;
	}

	$installUrl=$target.$installer;
	$siteLogin='host';//$webInfo['login'];
	$sitePassword=$storageInfo['access_code'];
	$brandName=$GLOBALS['BRAND_NAME'];
	$serverUrl=$siteUrl;
//	$winTitle=$gBrandInfo['product_name'];
	
} else {
	$target='';
	$installUrl="[Meeting directory URL]/".$installer;
	$siteLogin='';
	$sitePassword='';
	$brandName=$GLOBALS['BRAND_NAME'];
	$serverUrl=$siteUrl;
//	$winTitle=$gBrandInfo['product_name'];
	
}


?>

<div class=heading1>Install Storage Scripts</div>

Follow these steps to install Storage Server PHP scripts in the Storage Directory.

<div class='invite_url'>Storage Directory URL: <a href='<?php echo $target?>'><?php echo $target?></a></div>

<ol>
<li>Make sure PHP 4.3.0 or above is running on the Web server. You can install PHP from the following:
	<ul>
	<li><a href="http://www.easyphp.org/telechargements.php3">Download EasyPHP (Install both Apache and PHP on Windows PC)</a></li>
	<li><a href="http://www.php.net/downloads.php">Download PHP for all platforms</a></li>
	</ul>
</li>

<li>Download Scripts Installer and upload the Installer to the Storage Directory.
	<ul>
	<li><a href="<?php echo $installerZip?>">Download Scripts Installer</a></li>
	<li>Unzip the Installer and upload <b>"<?php echo $installer?>"</b> to the Storage Directory.
	</li>
	</ul>
</li>
<li>Launch the Scripts Installer using the following URL:
<blockquote>
	<form target=_blank method='POST' action="<?php echo $installUrl?>" name="create_form">
	<input type='hidden' name='no_update' value='1'>
	<input type='hidden' name='login' value='<?php echo $siteLogin?>'>
	<input type='hidden' name='password' value='<?php echo $sitePassword?>'>
	<input type='hidden' name='brand' value='<?php echo $brandName?>'>
	<input type='hidden' name='storage' value='1'>
	<input type='hidden' name='server' value='<?php echo $serverUrl?>'>
	<input type='submit' name='submit' value='Launch Installer'>
	</form>
</blockquote>

</li>
</ul>