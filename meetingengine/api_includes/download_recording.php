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
require_once('dbobjects/vuser.php');
require_once('dbobjects/vmeeting.php');
require_once('dbobjects/vbrand.php');

GetArg("meeting_id", $meetingId);
if ($meetingId=='') 
	API_EXIT(API_ERR, "meeting id not set");	

VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
if (!isset($meetingInfo['id']))
	API_EXIT(API_ERR, "Meeting id cannot be found");	

$memberId=GetSessionValue('member_id');

// see if the recording can be downloaded or the requester is the meeting host
if ($meetingInfo['can_download_rec'] || $meetingInfo['can_download'] || $memberId==$meetingInfo['host_id']) {
	
	$user=new VUser($meetingInfo['host_id']);
	if ($user->Get($userInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $user->GetErrorMsg());
	if (!isset($userInfo['id']))
		API_EXIT(API_ERR, "User not found.");
		
	$brand=new VBrand($meetingInfo['brand_id']);
	if ($brand->Get($brandInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $brand->GetErrorMsg());
	if (!isset($brandInfo['id']))
		API_EXIT(API_ERR, "Brand not found.");
	
	GetArg("audio", $getAudio);
	
	$getrecUrl='';
	if ($getAudio=='1' && ($meetingInfo['can_download'] || $memberId==$meetingInfo['host_id'])) {
		// download audio only
		$getrecUrl=VMeeting::GetExportRecUrl($userInfo, $meetingInfo, true, false);
		$getrecUrl.="&download=recording_".$meetingInfo['access_id'];					
		
		
	} else if ($getAudio=='' && ($meetingInfo['can_download_rec']|| $memberId==$meetingInfo['host_id'])) {
		// download the entire recording
		// check if the download is available	
		$getrecUrl=VMeeting::GetExportRecUrl($userInfo, $meetingInfo, false, false);
		$getrecUrl.="&meeting_id=".$meetingInfo['access_id'];

		$checkUrl=$getrecUrl."&check=1";
		$resp=@file_get_contents($checkUrl);
		if (!$resp)
			API_EXIT(API_ERR, "Couldn't get a response from the server.");	

		
		// create the download if it is not available yet
		if (strpos($resp, "OK")===false) {		
			$makeRecUrl=$getrecUrl."&server=".SITE_URL;
			$makeRecUrl.="&brand=".$brandInfo['name'];
			$makeRecUrl.="&create=1";

			if ($meetingInfo['login_type']=='PWD' && $meetingInfo['password']!='') {
				if ($memberId==$meetingInfo['host_id'])
					$makeRecUrl.="&meeting_pass=".rawurlencode($meetingInfo['password']);
				else
					API_EXIT(API_ERR, "The recording requires a password to download.");	
			}
			$resp=@file_get_contents($makeRecUrl);
			if ($resp===false || strpos($resp, "ERROR")===0) {
				$msg="An error occurred when trying to create a downloadable file.";
				if ($resp)
					$msg.=" ".$resp;
				API_EXIT(API_ERR, $msg);	
			}
		}
	}
	// redirect to the download url
	$downloadUrl=$getrecUrl."&download=recording_".$meetingInfo['access_id'];				
	header("Location: $getrecUrl");
	
} else if ($meetingInfo['can_download'] || $memberId==$meetingInfo['host_id']) {
	
	$loadUrl=VMeeting::GetExportRecUrl($userInfo, $row, true, false);
	$downloadUrl=$loadUrl."&download=".$row['access_id'];					

	
} else {
	API_EXIT(API_ERR, "Download of this recording is not allowed.");	
	
}


?>
