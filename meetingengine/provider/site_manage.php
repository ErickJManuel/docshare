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

require_once("dbobjects/vwebserver.php");

?>
<link href="provider/provider.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="provider/provider.js"></script>
<div id='page_box' class='page' style='visibility:hidden'>
<div class='box_header'><a onclick="hidePageBox(); return false;" href='javascript:void(0)'>
Close <img src="themes/close.gif"></a></div>
<iframe id='page_content' src=''></iframe>
</div>

<div class='heading1'>Manage Sites</div>

Web conferencing sites created under this provider account:

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="pipe">Site URL</th>
    <th class="pipe" style="width: 50px">Active</th>
    <th class="tr" style="width: 200px">Actions</th>
</tr>


<?php

$providerId=GetSessionValue('provider_id');
$provider=new VProvider($providerId);
$provider->GetValue('admin_email', $email);

$serverUrl=SERVER_URL;
$sslUrl=SSL_SERVER_URL;

$query="provider_id='$providerId'";

$errMsg=VObject::SelectAll(TB_BRAND, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}
$mgPage=$_SERVER['PHP_SELF']."?page=SITE_MANAGE";
if (SID!='')
	$mgPage.="&".SID;
	
$editPage=$_SERVER['PHP_SELF']."?page=SITE_EDIT";
if (SID!='')
	$editPage.="&".SID;	

$num_rows = mysql_num_rows($result);
$i=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	$brandName=$row['name'];
	$editUrl=$editPage."&brand=".$brandName;
	$url=$row['site_url'];
	$urlStr="<a target=_blank href='$url'>$url</a>";
	$winTitle=htmlspecialchars($row['product_name']);
	
	$siteQuery="brand_id ='".$row['id']."' AND url='".$row['site_url']."'";
	
	$webInfo=array();	
	$errMsg=VObject::Select(TB_WEBSERVER, $siteQuery, $webInfo);
	$siteLogin=$sitePassword='';
	if (isset($webInfo['login'])) {
		$siteLogin=$webInfo['login'];
		$sitePassword=$webInfo['password'];
	} else {
		$urlStr.='(Site record not found)';
	}
	
	$status=$row['status'];
	$active='Y';
	if ($status=='INACTIVE')
		$active='N';
	
	$installUrl='';
	$formName="reinstall_form_$i";
	$actions="<a href='$editUrl'>Edit</a> | ";
	$actions.="<a href='javascript:void(0);' onclick='launchInstaller(document.$formName); return false;'>Reinstall</a> | ";
/*		
	if ($active=='Y') {
		$loginUrl=$url."?page=SIGNIN&login_id=$email";
		$actions.="<a href='$loginUrl'>Login</a> | ";
	}
*/	
	if ($active=='Y') {

		$deactUrl=$mgPage."&active=0&brand_id=".$row['id'];
		if (SID!='')
			$deactUrl.="&".SID;
		$actions.="<a onclick='return ConfirmDeactivate();' href='$deactUrl'>Deactivate</a>";
	} else {
		$actUrl=$mgPage."&active=1&brand_id=".$row['id'];
		if (SID!='')
			$actUrl.="&".SID;
		$actions.="<a href='$actUrl'>Activate</a>";
	}
/*	
	$deleteUrl=$mgPage."&delete=1&brand_id=".$row['id'];
	if (SID!='')
		$deleteUrl.="&".SID;
	$actions.=" | <a onclick='return ConfirmDelete();' href='$deleteUrl'>Delete</a>";
*/	


	print <<<END

<tr>
	<td class="u_item">$urlStr
	<form name="$formName">
	<input type="hidden" name="site_url" value="$url">
	<input type='hidden' name='server' value="$serverUrl">
	<input type='hidden' name='ssl_server' value="$sslUrl">	
	<input type='hidden' name='login' value="$siteLogin">
	<input type='hidden' name='password' value="$sitePassword">
	<input type='hidden' name='brand' value="$brandName">
	<input type='hidden' name='win_title' value="$winTitle">	
	</form>	
	</td>
	<td class="u_item_c">$active</td>
	<td class="u_item_c">$actions</td>
</tr>
END;
	$i++;
}

?>

</table>
