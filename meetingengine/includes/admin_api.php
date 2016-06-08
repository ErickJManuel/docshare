<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("rest/prestapi.php");

/*
$apiUrl=SERVER_URL.PR_API_DIR."/";
if (defined('SSL_SERVER_URL') && SSL_SERVER_URL!='')
	$apiUrl.="<br>".SSL_SERVER_URL.PR_API_DIR."/";
*/

$apiUrl=SITE_URL.PR_API_DIR."/";

$getApiUrl=$gBrandInfo['site_url']."get_api_url.php";
	
$apiKey=$gBrandInfo['api_key'];
$signatureHtm='';
$query='brand='.$GLOBALS['BRAND_NAME'];

$msg=_Text("Do you want to request a new API access key? The old key will no longer be valid.");

$postUrl=$_SERVER['PHP_SELF']."?page=".PG_ADMIN_API;
if (SID!='')
	$postUrl.="&".SID;
	
$docUrl=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST;
if (SID!='')
	$docUrl.="&".SID;
	
$hookUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_API_HOOKS;
if (SID!='')
	$hookUrl.="&".SID;
	
$ssoDocUrl=$GLOBALS['BRAND_URL']."?page=".PG_HELP."&doc=sso";
if (SID!='')
	$ssoDocUrl.="&".SID;
	
$ssoHost=$gBrandInfo['sso_host'];

if (isset($_POST['request_key']) && $_POST['request_key']=='Request') {
	$brand=new VBrand($GLOBALS['BRAND_ID']);
	$brandInfo=array();
	$brandInfo['api_key']=md5(mt_rand()+1000);
	if ($brand->Update($brandInfo)!=ERR_NONE) {
		ShowError($brand->GetErrorMsg());
	} else {
		$apiKey=$brandInfo['api_key'];
	}
	
} elseif (isset($_POST['get_signature'])) {
	
	$query=$_POST['query'];
	$signature=md5($query.$apiKey);
	$signatureHtm="<div class='m_val'><b>Signature</b>: <input readonly type='text' size='60' value='$signature'></div>";

} elseif (isset($_POST['submit_sso'])) {
	$brand=new VBrand($GLOBALS['BRAND_ID']);
	$brandInfo=array();
	$brandInfo['sso_host']=$_POST['sso_host'];
	if ($brand->Update($brandInfo)!=ERR_NONE) {
		ShowError($brand->GetErrorMsg());
	} else {
		$ssoHost=$brandInfo['sso_host'];
	}
}

?>
<ul>
<li><a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $docUrl?>"><?php echo _Text("API Documentation")?></a></li>
<li><a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $hookUrl?>"><?php echo _Text("API Hooks")?></a></li>
<li><a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $ssoDocUrl?>"><?php echo _Text("Single Sign On (SSO) Documentation")?></a></li>
</ul>

<table class="meeting_detail">

<tr>
	<td class="m_key"><?php echo _Text("API URL")?>:</td>
	<td colspan=3 class="m_val">
	<input readonly type="text" size="70" value="<?php echo $apiUrl?>">
	<div class="m_caption"><?php echo _Text("The API url may change when you change the site version. Use the following URL to get the current API url for the site.")?></div>
	<?php echo _Text("Get API URL")?>: <a href="<?php echo $getApiUrl?>"><?php echo $getApiUrl?></a>
	</td>
</tr>
<tr>
	<td class="m_key"><?php echo _Text("Brand ID")?>:</td>
	<td colspan=3 class="m_val">
	<input readonly type='text' size='20' value="<?php echo $GLOBALS['BRAND_NAME']?>">
	<div class='m_caption'><?php echo _Text("The brand id is a required parameter for every API request.")?></div>
	</td>
</tr>
<form method="POST" action="<?php echo $postUrl?>" name="profile_form">
<tr>
	<td class="m_key"><?php echo _Text("API Access Key")?>:</td>
	<td colspan=3 class="m_val">
	<input readonly type="text" size="40" value="<?php echo $apiKey?>">&nbsp;
	<input onclick='return confirm("<?php echo $msg?>");' type="submit" name="request_key" value="Request">
	<div class='m_caption'><?php echo _Text("You must request an API access key first before using the API. The key is required to compute a query signature for each API call. Do not share this key with unauthorized personnel.")?></div>
	</td>
</tr>

<tr>
	<td class="m_key"><?php echo _Text("SSO Host Name")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" size="60" name='sso_host' value="<?php echo $ssoHost?>">&nbsp;
	<input type="submit" name="submit_sso" value="Submit">
	<div class='m_caption'><?php echo _Text("Authorized server host names for SSO StorageServerUrl. Use \",\" to separate multiple host names. The host name must match completely or partially the host name part of StorageServerUrl. Example: \"www.host1.com,host2.com\" will allow requests from \"www.host1.com\" and \"app1.host2.com\".")?>
	<b><?php echo _Text("If this field is blank, NO SSO requests will be accepted.")?></b></div>
	</td>
</tr>
<tr>
	<td class="m_key"><?php echo _Text("Query Signature")?>:</td>
	<td colspan=3 class="m_val">
	<div class='m_caption'><?php echo _Text("Enter a query string to compute its signature. See API documentation for the details of computing the signature.")?></div>
	<div class='m_val'><b><?php echo _Text("Query String")?>:</b> <input name='query' type="text" size="60" value="<?php echo $query?>"></div>
	<?php echo $signatureHtm?>
	<div class='m_val'><input type="submit" name="get_signature" value="Compute Signature"></div>
	</td>
</tr>
</form>


</table>
