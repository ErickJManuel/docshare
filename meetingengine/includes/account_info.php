<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vwebserver.php");

$memberId=GetSessionValue('member_id');
$user=new VUser($memberId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

$licenseId=$userInfo['license_id'];

$license=new VLicense($licenseId);
$licInfo=array();
if ($license->Get($licInfo)!=ERR_NONE) {
	ShowError("License not found");
	return;
}	

$licName=$licInfo['name'];
if ($licInfo['expiration']=='0')
	$expStr=$gText['M_NONE'];
else
	$expStr=$licInfo['expiration']." days";
	
if ($licInfo['max_att']=='0')
	$attStr=$gText['M_NO_LIMIT'];
else
	$attStr=$licInfo['max_att'];

if ($licInfo['video_conf']=='N')
	$video=$gText['M_NO'];
else
	$video=$gText['M_YES'];
	
if ($licInfo['meeting_length']=='0')
	$length=$gText['M_NO_LIMIT'];
else
	$length=$licInfo['meeting_length'].' '._Text("minutes");	//_Comment: as in time

	
if ($licInfo['disk_quota']=='0')
	$disk=$gText['M_NO_LIMIT'];
else {
	$disk=$licInfo['disk_quota']." MB";
}
	
// get disk usage on the web server
/* disable it for now because it can take a while
//$serverUrl=$gBrandInfo['site_url'];

$errMsg=VUser::GetDiskUsage($userInfo['brand_id'], $userInfo, $storageUrl, $meetingsSize, $libSize);
if ($errMsg!='')
	ShowError($errMsg);

*/
/*
// replaced by VUser::GetDiskUsage

VUser::GetStorageUrl($userInfo['brand_id'], $userInfo, $storageUrl, $storageId, $storageCode, $storageServerId);

$query="brand_id='".$userInfo['brand_id']."' AND url = '$storageUrl'";
$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
if ($errMsg) {
	ShowError($errMsg);
	return;
}
if (!isset($serverInfo['id'])) {
	ShowError("Web server '$storageUrl' not found");
}

$libSize=0;
$meetingsSize=0;
$libSizeStr='n/a';
$meetingsSizeStr='n/a';

$scriptUrl=VWebServer::GetScriptUrl($serverInfo['url'], $serverInfo['php_ext']);		
$getSizeUrl=$scriptUrl."?s=".SC_VFTP."&cmd=getsize&arg1=".$userInfo['access_id']."/vlibrary";
$getSizeUrl.="&id=".$storageId."&code=".$storageCode;
//echo "server url=".$getSizeUrl."\n";
if (VWebServer::GetUrl($getSizeUrl, $response)) {
	$items=explode("\n", $response, 2);
	if (count($items)>1 && $items[0]=='OK') {
		$libSize=(int)$items[1];
	}			
}
$getSizeUrl=$scriptUrl."?s=".SC_VFTP."&cmd=getsize&arg1=".$userInfo['access_id']."/vmeetings";
$getSizeUrl.="&id=".$storageId."&code=".$storageCode;
//echo "server url=".$getSizeUrl."\n";

if (VWebServer::GetUrl($getSizeUrl, $response)) {
	$items=explode("\n", $response, 2);
	if (count($items)>1 && $items[0]=='OK') {
		$meetingsSize=(int)$items[1];
	}			
}		

*/
/*
$totalSize=$meetingsSize+$libSize;
$meetingsSizeStr=(string)BytesToMB($meetingsSize)." MB";
$libSizeStr=(string)BytesToMB($libSize)." MB";
$totalSizeStr=(string)BytesToMB($totalSize)." MB";
//echo ("size=$totalSize $meetingsSizeStr $libSizeStr $totalSizeStr");

if ($licInfo['disk_quota']=='0')
	$availSize=$gText['M_NO_LIMIT'];
else {
	$quota=(int)$licInfo['disk_quota']*1024*1024;
	$availSize=$quota-$totalSize;
	if ($availSize<=0) {
		$availSizeStr="<span class='alert'>".(string)BytesToMB($availSize)."</span> MB";
	} else {
		$availSizeStr=(string)BytesToMB($availSize)." MB";		
	}

}
*/
$record=$gText['M_YES'];
if ($licInfo['btn_disabled']!='') {
	$btns=explode(",", $licInfo['btn_disabled']);
	foreach ($btns as $abtn) {
		if ($abtn=='record') {
			$record=$gText['M_NO'];
		}
/*
		elseif ($abtn=='whiteboard')
			$params.="&CanWhiteboard=0";
		elseif ($abtn=='library')
			$params.="&CanLibrary=0";
		elseif ($abtn=='snapshot')
			$params.="&CanSnapshot=0";
		elseif ($abtn=='file')
			$params.="&CanSendFile=0";
		elseif ($abtn=='screen')
			$params.="&CanShareScreen=0";				
		elseif ($abtn=='poll')
			$params.="&CanPoll=0";
*/			
	}			
}


$groupId=$userInfo['group_id'];
$group=new VGroup($groupId);
$group->GetValue('name', $groupName);

?>

<div class='heading2'><?php echo $gText['M_ACCOUNT_TYPE']?>:</div>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ACCOUNT_TYPE']?></th>
    <th class="pipe"><?php echo $gText['M_NUM_ATTENDEES']?></th>
    <th class="pipe"><?php echo $gText['M_LENGTH']?></th>
    <th class="pipe"><?php echo $gText['M_VIDEO_CONF']?></th>
    <th class="pipe"><?php echo $gText['M_RECORD']?></th>
<!--    <th class="tr"><?php echo $gText['M_DISK_QUOTA']?></th>	-->
</tr>

<tr>
	<td class="u_item u_item_b"><?php echo $licName?></td>
	<td class="u_item_c"><?php echo $attStr?></td>
	<td class="u_item_c"><?php echo $length?></td>
	<td class="u_item_c"><?php echo $video?></td>
	<td class="u_item_c"><?php echo $record?></td>
<!--	<td class="u_item_c"><?php echo $disk?></td>	-->
</tr>

</table>

<?php
if ((defined('ENABLE_LICENSE_KEY') && constant('ENABLE_LICENSE_KEY')=='1')) {
	
	if ($userInfo['licensekey_id']!='0') {
		
		require_once("dbobjects/vlicensekey.php");
		
		$licKey=new VLicenseKey($userInfo['licensekey_id']);
		$licKey->Get($keyInfo);
		$licXml=simplexml_load_string($keyInfo['license_text']);
		$licInfo=array();
		foreach ($licXml->children() as $child) {
			$licInfo[$child->getName()]=(string)$child;
		}		
		
		if ($licInfo['expiry_date']>"1961-01-01") {
			$title=_Text("Subscription period");
			$startText=_Text("Start date");
			$expText=_Text("Renewal date");
			$expiredText=_Text("Expired");
			$startDate=$licInfo['start_date'];
			$expDate=$licInfo['expiry_date'];
			print <<<END
<div class='heading2'>$title:</div>
<table cellspacing="0" class="meeting_list" >
<tr>
    <th class="tl pipe">$startText</th>
    <th class="pipe">$expText</th>
</tr>
<tr>
    <td class="u_item_c">$startDate</td>
    <td class="u_item_c">$expDate</td>
</tr>
</table>
END;
		}
	}
}
?>

<?php
/* disable for now
$hasLibrary=GetSessionValue('has_library');
if ($hasLibrary=='Y') {
	print <<<END
	
<div class='heading2'>${gText['M_STORAGE_SPACE']}:</div>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">${gText['MEETINGS_TAB']}</th>
    <th class="pipe">${gText['M_MY_LIBRARY']}</th>
    <th class="pipe">${gText['M_TOTAL']}</th>
    <th class="tr">${gText['M_AVAILABLE']}</th>
</tr>

<tr>
	<td class="u_item_c">$meetingsSizeStr</td>
	<td class="u_item_c">$libSizeStr</td>
	<td class="u_item_c">$totalSizeStr</td>
	<td class="u_item_c">$availSizeStr</td>
</tr>

</table>
END;
}
*/
?>
<br>
<div><span class='heading2'><?php echo _Text("Account ID")?>:</span> <?php echo $userInfo['access_id']?>
<span class='heading2' style="padding-left: 40px"><?php echo $gText['M_GROUP']?>:</span> <?php echo $groupName?></div>


<?php

if ($gBrandInfo['enable_licensekey']=='USER' || $gBrandInfo['enable_licensekey']=='ALL') {
	$text1=_Text("License key file");
	
	$backPage=$GLOBALS['BRAND_URL']."?page=".PG_ACCOUNT_INFO;
	if (SID!='')
		$backPage.="&".SID;
	$backPage=VWebServer::EncodeDelimiter2($backPage);
	
	$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$backPage."&message=".rawurlencode($gText['M_SUBMIT_OK'])."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;
	
	$retPage=VWebServer::EncodeDelimiter1($retPage);
	
	$postUrl=VM_API."?cmd=INSTALL_LICENSE";
	$uploadText=_Text("Upload");
	print <<<END
<br>
Install a user license key file (*.userkey):
<form enctype="multipart/form-data" method="POST" action="$postUrl" name="license_form">
<div><span class='heading2'>$text1:</span>
<input type='hidden' name='user_id' value='$memberId'> 
<input type='hidden' name='return' value='$retPage'> 
<input type='file' name='license_file' size='40'> &nbsp;
<input type='submit' name='submit' value='$uploadText'></div>
</form>

END;
}

?>