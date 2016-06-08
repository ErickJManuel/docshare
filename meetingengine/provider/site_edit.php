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
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vbrand.php");

$providerId=GetSessionValue('provider_id');

if ($providerId=='') {
	ShowError("Your session has expired.");
	return;
}

$brandInfo=array();
GetArg('brand', $brandName);
if ($brandName!='')
	VObject::Find(TB_BRAND, "name", $brandName, $brandInfo);

if (!isset($brandInfo['site_url'])) {
	ShowError("The site is not found in our records.");
	return;
}
			
$postPage=$_SERVER['PHP_SELF'];
$message='';

if (isset($_POST['change_url'])) {
	
	if (!isset($_POST['site_url']) || $_POST['site_url']=='') {
		ShowError("The site url is not set.");
		return;
	}
	
	$siteUrl=$_POST['site_url'];
	$urlItems=@parse_url($siteUrl);
	if (!isset($urlItems['scheme']) || 
		($urlItems['scheme']!='http' && $urlItems['scheme']!='https') ||
		!isset($urlItems['host'])) 
	{
		ShowError("The site url is not a valid url.");
		return;
	}
	
	if ($siteUrl[strlen($siteUrl)-1]!='/')
		$siteUrl.="/";
	
	$provider=new VProvider($providerId);
	$providerInfo=array();
	$provider->Get($providerInfo);
	
	// find the web server record and change it
	$query="brand_id='".$brandInfo['id']."' AND url='".$brandInfo['site_url']."'";

	$webInfo=array();	
	$errMsg=VObject::Select(TB_WEBSERVER, $query, $webInfo);
	if (!isset($webInfo['id'])) {
		ShowError("The web site is not found in our records.");
		return;
	}
	
	$web=new VWebServer($webInfo['id']);	
	$newWebInfo=array();
	$newWebInfo['url']=$siteUrl;
	if ($web->Update($newWebInfo)!=ERR_NONE) {
		ShowError($web->GetErrorMsg());
		return;
	}	
	
	// find the brand record and change it
	$brand=new VBrand($brandInfo['id']);
	$newBrandInfo=array();
	$newBrandInfo['site_url']=$siteUrl;
	if ($brand->Update($newBrandInfo)!=ERR_NONE) {
		ShowError($brand->GetErrorMsg());
		return;
	}
	
	// update completed
	
	$message="Update completed.";
	$brandInfo['site_url']=$siteUrl;
}

?>

<div class='heading1'>Edit a Web Conferencing Site</div>
<div class="inform"><?php echo $message?></div>
<br>

<div class="sub_val2">
<form method="POST" action="<?php $postPage?>">
<input type="hidden" name="brand" value="<?php echo $brandName?>">
<span class="m_subkey">Site URL:</span>
<input type="text" name='site_url' value="<?php echo $brandInfo['site_url']?>" size="60">
<input type="submit" name='change_url' value="Change">
<div class="m_caption">- The new URL should not link to an existing Persony installation for a different site.</div>
<div class="m_caption">- You can use 'Reinstall' in the 'Manage Sites' page to re-install files on the site.</div>
</form>
</div>


