<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("dbobjects/vuser.php");
require_once("dbobjects/vbackground.php");
require_once("dbobjects/vmeeting.php");

GetArg('brand_url', $brandUrl);

$meetingId='';
if (GetArg('meeting_id', $arg))
	$meetingId=$arg;
elseif (GetArg('meeting', $arg))
	$meetingId=$arg;

if ($meetingId=='')
	API_EXIT(API_ERR, "Meeting id not set.");

$meetingFile=VMeeting::GetSessionCachePath($meetingId);
$accessDb=true;

if (VMeeting::IsSessionCacheValid($meetingFile)) {
	// override $_brandUrl defined in the cache file
	if ($brandUrl!='')
		$_brandUrl=$brandUrl;
	@include_once($meetingFile);
	if (isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
// turn off database access for getting meeting info if a meeting is already in progress
// and the meeting cache file is available
		$accessDb=false;
	}
}
if ($cmd=='GET_MEETING_INFO') {	
	
	$meetingXml='';
	if (!$accessDb) {
		// $_meetingXml is defined in $meetingFile
		if (isset($_meetingXml))
			$meetingXml=$_meetingXml;
		else
			API_EXIT(API_ERR, "Invalid meeting cache file.");
			
		if ($meetingId!=GetSessionValue('meeting_access_id') &&
			$_hostId!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN')
			)
		{
			API_EXIT(API_ERR, "Access is not authorized (GET_MEETING_INFO)");
		}
		
	} else {

		// cache file doesn't exist, get it from the database
		require_once('api_includes/meeting_common.php');

		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg);
			
		if (!isset($meetingInfo['id']))
			API_EXIT(API_ERR, "Meeting id not set.");
			
		if ($meetingInfo['access_id']!=GetSessionValue('meeting_access_id') &&
			$meetingInfo['host_id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$meetingInfo['brand_id'])
			)
		{
			API_EXIT(API_ERR, "Access is not authorized (GET_MEETING_INFO)");
		}

		$hostId=$meetingInfo['host_id'];
		$hostInfo=array();
		$host=new VUser($hostId);
		if ($host->Get($hostInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $host->GetErrorMsg());
		
		$meetingXml=VMeeting::GetXML($hostInfo, $meetingInfo);
		
	}

	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/xml");
	echo XML_HEADER."\n";
	echo $meetingXml;


} elseif ($cmd=='GET_ICAL') {

	// this is not critical for running a meeting so we will require database access
	require_once('api_includes/meeting_common.php');
	$isHost=false;
	if (GetArg('host_id', $arg)) {
		$isHost=true;
		if ($meetingInfo['host_id']!=$arg) {
			API_EXIT(API_ERR, "Not a host of the meeting");
		}
	}
	
	$meeting->GetMeetingUrl($meetingUrl);
	$fileName="meeting_".$meetingInfo['access_id'].".ics";
	$vcf=VMeeting::GetICal($meetingInfo, $meetingUrl, $isHost);
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/calendar; charset=us-ascii; name=\"$fileName\"");	
	header("Content-Disposition: attachment; filename=$fileName");	
	echo $vcf;
	API_EXIT(API_NOMSG);
	
} else if ($cmd=='GET_VIEWER_INFO') {
		
	$viewParams='';
	if (!$accessDb) {
		// $hostVars and $attVars are defined in $meetingFile
		if (GetArg('host_id', $arg)) {
			if ($arg==$_hostId) {
				$memberId=GetSessionValue('member_id');
				if ($memberId!=$arg)
					API_EXIT(API_ERR, "You are not logged in as the host of the meeting.");

				$viewParams=$_hostVars;
			} else
				API_EXIT(API_ERR, "You are not a host of the meeting.");
			
		} else {
			$viewParams=$_attVars;
		}
	} else {		
		// cache file doesn't exist, get it from the database
		require_once('api_includes/meeting_common.php');
		require_once("dbobjects/vstorageserver.php");

		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg);
			
		if (!isset($meetingInfo['id']))
			API_EXIT(API_ERR, "Meeting id not set.");

		$hostId=$meetingInfo['host_id'];
		$hostInfo=array();
		$host=new VUser($hostId);
		if ($host->Get($hostInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $host->GetErrorMsg());
		
		if ($meetingInfo['status']=='REC' && $meetingInfo['storageserver_id']!='0') {
			$storageServer=new VStorageServer($meetingInfo['storageserver_id']);
			$serverInfo=array();
			if ($storageServer->Get($serverInfo)!=ERR_NONE) {
				API_EXIT(API_ERR, $storageServer->GetErrorMsg());
			}
			if ($serverInfo['url']=='') {
				API_EXIT(API_ERR, "Url not set");
			}
			
			$php_ext='php';
		} else {				
			if ($meetingInfo['webserver_id']!=0)
				$webServerId=$meetingInfo['webserver_id'];
			else
				$webServerId=VUser::GetWebServerId($hostInfo);
				
			$webServer=new VWebServer($webServerId);

			$serverInfo=array();
			if ($webServer->Get($serverInfo)!=ERR_NONE) {
				API_EXIT(API_ERR, $webServer->GetErrorMsg());
			}
			if ($serverInfo['url']=='') {
				API_EXIT(API_ERR, "Url not set");
			}
			
			$php_ext=$serverInfo['php_ext'];
		}
		
		$groupId=$hostInfo['group_id'];
		$groupInfo=array();
		$group=new VGroup($groupId);
		if ($group->Get($groupInfo)!=ERR_NONE) {
			API_EXIT(API_ERR, $group->GetErrorMsg());
		}
		
		$licId=$hostInfo['license_id'];
		$license=new VLicense($licId);
		$license->GetValue('meeting_length', $meetingLength);
		$meetingLength*=60;
		
		$brand=new VBrand($hostInfo['brand_id']);

		// use the user's locale if defined.
		//$locale=$hostInfo['locale'];
		// otherwise, uset the brand's locale
		//if ($locale=='') {
			//$locale=$groupInfo['locale'];
			if ($brand->GetValue('locale', $locale)!=ERR_NONE) {
				API_EXIT(API_ERR, $brand->GetErrorMsg());
			}
		//}
		
		// get the user's viewer
		$viewerId=$hostInfo['viewer_id'];
		if ($viewerId==0) {
			if ($brand->GetValue('viewer_id', $viewerId)!=ERR_NONE) {
				API_EXIT(API_ERR, $brand->GetErrorMsg());
			}

		}
		$viewer=new VViewer($viewerId);
		
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);	

		$siteUrl=SITE_URL;
		$isHost=false;
		if ($meetingInfo['status']!='REC' && GetArg('host_id', $arg)) {
			if ($hostId!=$arg) 
				API_EXIT(API_ERR, "You are not the host of the meeting.");

//			GetArg('debug', $debug);
			if ($hostInfo['login']!=VUSER_GUEST /*&& $debug!='1'*/) {
				$memberId=GetSessionValue('member_id');
				if ($memberId!=$hostId)
					API_EXIT(API_ERR, "You are not logged in as the host of the meeting.");
			}
			$isHost=true;

		}
		
		if ($brandUrl=='')
			$brand->GetValue('site_url', $brandUrl);
		if (($errMsg=$viewer->GetFlashVars($siteUrl, $brandUrl, $serverInfo['url'], $meetingInfo, $hostInfo, $groupInfo, $locale, $meetingDir, $php_ext, $isHost, $meetingLength, $viewParams))!='')
		{
			API_EXIT(API_ERR, $errMsg);
		}
	}
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/plain");
	echo $viewParams;	
} else if ($cmd=='GET_VIEWER_BACKGROUND') {
		
	$backgroundXml='';
	if (!$accessDb) {
		// $_backgroundXml is defined in $meetingFile
		if (isset($_backgroundXml))
			$backgroundXml=$_backgroundXml;
		else
			API_EXIT(API_ERR, "Invalid meeting cache file.");
		
	} else {
		// cache file doesn't exist, get it from the database
		require_once('api_includes/meeting_common.php');
		
		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg);
		
		if (!isset($meetingInfo['id']))
			API_EXIT(API_ERR, "Meeting id not set.");
		
		$hostId=$meetingInfo['host_id'];
		$hostInfo=array();
		$host=new VUser($hostId);
		if ($host->Get($hostInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $host->GetErrorMsg());
		
		// get the user's viewer
		$viewerId=$hostInfo['viewer_id'];
		if ($viewerId==0) {
			$brand=new VBrand($hostInfo['brand_id']);
			if ($brand->GetValue('viewer_id', $viewerId)!=ERR_NONE) {
				API_EXIT(API_ERR, $brand->GetErrorMsg());
			}
		}
		$viewer=new VViewer($viewerId);	
		if ($viewer->GetValue('back_id', $backID)!=ERR_NONE) {
			API_EXIT(API_ERR, $viewer->GetErrorMsg());
		}
		
		$back=new VBackground($backID);
		$backInfo=array();
		if ($back->Get($backInfo)!=ERR_NONE) {
			API_EXIT(API_ERR, $back->GetErrorMsg());
		}
		
		$backgroundXml=VBackground::GetXML($backInfo);
		
	}
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	
	header("Content-Type: text/xml");
	echo XML_HEADER."\n";
	echo $backgroundXml;
		
} else if ($cmd=='GET_SHARING_INFO') {
	$sharingXml='';
	if (!$accessDb) {
		
		GetArg("user", $userId);
		if ($userId=='')
			API_EXIT(API_ERR, "Missing user parameter.");

		// get data from cache; no database access
//		@include_once($meetingFile);
		// $_sharingXml is defined in $meetingFile
		if (isset($_sharingXml) && $_sharingXml!='') {
			// check if userId is a presenter? skip the check for now.			
			$sharingXml=$_sharingXml;
		} else
			API_EXIT(API_ERR, "Invalid meeting cache file.");
			
		if ($meetingId!=GetSessionValue('meeting_access_id') &&
			$_hostId!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' /*|| GetSessionValue('member_brand')!=$meetingInfo['brand_id']*/)
			)
		{
			API_EXIT(API_ERR, "Access is not authorized (GET_SHARING_INFO)");
		}
		
	} else {
		
		@include_once("download/vpresent_version.php");
		require_once('api_includes/meeting_common.php');

		$version='';
		if (isset($vpresent_version))
			$version=$vpresent_version;
		
		$minVersion='';
		if (isset($required_version))
			$minVersion=$required_version;		
		
		GetArg("user", $userId);
		if ($userId=='')
			API_EXIT(API_ERR, "Missing user parameter.");
			
		$hostId=$meetingInfo['host_id'];
		$hostInfo=array();
		$host=new VUser($hostId);
		if ($host->Get($hostInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $host->GetErrorMsg());
			
		if ($meetingInfo['access_id']!=GetSessionValue('meeting_access_id') &&
			$meetingInfo['host_id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$meetingInfo['brand_id'])
			)
		{
			API_EXIT(API_ERR, "Access is not authorized (GET_SHARING_INFO)");
		}
		
		// make sure the user is a presenter
/*
		if ($userId!=$hostInfo['access_id']) {
			$query="attendee_id = '".$userId."' AND session_id = '".$meetingInfo['session_id']."'";
			$errMsg=VObject::Select(TB_ATTENDEE_LIVE, $query, $attInfo);
			if ($errMsg!='') {
				API_EXIT(API_ERR, $errMsg);
			}
			
			if (!isset($attInfo['id']))
				API_EXIT(API_ERR, "Attendee not found.");
			if ($attInfo['can_present']!='Y')
				API_EXIT(API_ERR, "You are not a presenter.");
			
		}
*/		
		$siteUrl=SITE_URL;
		$downloadUrl=VWebServer::AddPaths($siteUrl, "download/download.php");
		$sharingXml=VMeeting::GetSharingXML($hostInfo, $meetingInfo, $version, $minVersion, $downloadUrl);
	}	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');	
	header("Content-Type: text/xml");
	echo XML_HEADER."\n";
	echo $sharingXml;
	
/* VSA file type is not used anymore because we now use the vpresent:// protocol to launch the desktop sharing app	
} else if ($cmd=='GET_VSA') {
	
	GetArg("user", $userId);
	if ($userId=='')
		API_EXIT(API_ERR, "Missing user parameter.");
	
	// make sure the user is a presenter
	if ($userId!=$hostInfo['access_id']) {
		$query="attendee_id = '".$userId."' AND session_id = '".$meetingInfo['session_id']."'";
		$errMsg=VObject::Select(TB_ATTENDEE_LIVE, $query, $attInfo);
		if ($errMsg!='') {
			API_EXIT(API_ERR, $errMsg);
		}

		if (!isset($attInfo['id']))
			API_EXIT(API_ERR, "Attendee not found.");
		if ($attInfo['can_present']!='Y')
			API_EXIT(API_ERR, "You are not a presenter.");
		
	}

	$siteUrl=SITE_URL;
	$url=$siteUrl.VM_API."?cmd=GET_SHARING_INFO&amp;meeting=".$meetingInfo['access_id']."&amp;user=".$userId;
	$vsaXml=XML_HEADER."\n";
	
	$vsaXml.="
<vnc2flash>
  <session>
    <userId>$userId</userId>
    <sessionInfoUrl>$url</sessionInfoUrl>
  </session>
</vnc2flash>
";

	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: application/vnd.persony-vsa");
	header("Content-Disposition: attachment; filename=session.vsa"); 
	echo $vsaXml;	
*/
}


API_EXIT(API_NOMSG);


?>