<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
require_once("vuser.php");
require_once("vwebserver.php");
require_once("vviewer.php");
require_once("vgroup.php");
require_once("vsession.php");
//require_once("vsite.php");
require_once("vbrand.php");
require_once("vlicense.php");
require_once("vattendee.php");
require_once("vattendeelive.php");
require_once("vgroup.php");
require_once("vbackground.php");
require_once("vstorageserver.php");
require_once("vhook.php");

/*
* Meeting Events
*/
define("EVT_START_MEETING", "StartMeeting");
define("EVT_END_MEETING", "EndMeeting");
define("EVT_ADD_ATTENDEE", "AddAttendee");
define("EVT_ALLOW_DRAWING", "AllowDrawing");
define("EVT_ALLOW_PRESENTING", "AllowPresenting");
define("EVT_START_RECORDING", "StartRecording");
define("EVT_END_RECORDING", "EndRecording");
/*
$reportTemplate="
<html><head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
<style>
<!--
body { margin: 5px; padding: 0px; font: 12px arial, helvetica, sans-serif; }
.heading1 { margin-top: 10px; margin-bottom: 5px; font: bold 14px arial, helvetica; padding-bottom:3px; color: #454545; }
.div {font: 14px arial, helvetica; }

ul {
	margin: 0;
	padding: 0;
	list-style:none;
	font-size:14px;
}

ul > li {
	margin: 0;
	padding:5px 0 2px 0px;
}

.meeting_list { margin: 5px 0 0 0; font-size: 100%; width: 100%; padding-top: 0px;}
.meeting_list tr { vertical-align: top; }
.meeting_list th { font-size: 90%; padding: 5px 7px; background-color: #39c ; color: #fff; }

.meeting_list th.tl { background-color: #39c; }
.meeting_list th.tr { background-color: #39c; }
.meeting_list .pipe { border-right: 1px solid #fff; }
.meeting_list td { border-bottom: 1px solid #ccc;}
.meeting_list td.m_id { padding: 5px 5px 0px 0px;}

#meeting_stat { text-align: right; }

.u_item { padding: 5px 5px 5px 5px;}
.u_item_c { text-align: center; padding: 5px 5px 5px 5px;}
.m_caption { line-height: 1.3em; padding: 2px 10px 0 10px; font-size: 80%; }
-->
</style>
</head>
<body>
<ul>
<li><b>Session ID:</b> [SESSION_ID]</li>
<li><b>Meeting:</b> [MEETING_ID] [MEETING_TITLE]</li>
<li><b>Start time:</b> [START_TIME]</li>
</ul>

[ATTENDEES]

<div class='heading1'>Transcripts</div>
[TRANSCRIPTS]
<div class='m_caption'>*Time is measured from the start of the meeting.</div>
</body>
</html>
";
*/
// The templates should be stored in the db so we can customize them
/*
$meetingInviteTemp="
Please join the following meeting.

Meeting URL:
[MEETING_URL]
Telephone: [PHONE_NUMBER]
Access code: [ACCESS_CODE]
Password: [PASSWORD]
Date/Time: [DATE_TIME]
Duration: [DURATION]
";

$iphoneInviteTemp="
---------------------
For iPhone users only
To join the meeting from iPhone:
[IPHONE_MEETING_URL]

You must install the iPhone App first.
Download the app from:
[IPHONE_DOWNLOAD_URL]

";

$recInviteTemp="
Please play the following recording.

Recording URL:
[MEETING_URL]
";

*/

/**
 * @package     VShow
 * @access      public
 */
class VMeeting extends VObject 
{
	/**
	* Constructor
	* @param integer set $id to non-zero to associate the object with an existing row.
	*/	
	function VMeeting($id=0)
	{
		$this->VObject(TB_MEETING);
		$this->SetRowId($id);
	}
	/**
	* Get user dir path from $meetingInfo
	* @static
	* @access public 
	* @param array  $meetingInfo
	* @return string dir path
	*/
	static function GetMeetingDir($hostInfo, $meetingInfo)
	{
		return $hostInfo['access_id']."/vmeetings/".$meetingInfo['access_id']."/";
	}
	
	static function GetMeetingServerUrl($hostInfo, $meetingInfo)
	{
		if ($meetingInfo['webserver_id']!=0)
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
				
		$webServer=new VWebServer($webServerId);
		$webServer->GetValue('url', $serverUrl);
		$serverUrl=VWebServer::AddSlash($serverUrl);
		return $serverUrl;
	}
	
	static function GetSessionCachePath($meetingId)
	{
//		return DIR_TEMP.md5("meeting".$meetingId).".php";
		return GetTempDir().md5("meeting".$meetingId).".php";
	}
	
	static function IsSessionCacheValid($cacheFile)
	{
		// expire the cache after 8 hours in case they are not deleted for whatever reason
		if (file_exists($cacheFile) && (time()-filemtime($cacheFile))<60*60*8)
			return true;
		else
			return false;
	}			
		
	static function GetVPresentVersions(&$version, &$minVersion, &$downloadUrl)
	{
@include_once("download/vpresent_version.php");
		$version='';
		if (isset($vpresent_version))
			$version=$vpresent_version;
			
		$minVersion='';
		if (isset($required_version))
			$minVersion=$required_version;		
/*			
		if (($errMsg=VSite::GetSiteUrl($siteUrl))!='') {
			API_EXIT(API_ERR, $errMsg);
		}
*/
		$siteUrl=SITE_URL;
		$downloadUrl=VWebServer::AddPaths($siteUrl, "download/download.php");
	}
	
	/**
	* @static
	* @return string
	*/		
	static function GetICal($meetingInfo, $meetingUrl, $isHost)
	{
		$title=$meetingInfo['title'];
		
		$currTimeStr=date('Ymd')."T".date('His');
		$startTimeStr='';
		$endTimeStr='';
		if ($meetingInfo['scheduled']=='Y' || $meetingInfo['status']=='REC') {
			$dtime=$meetingInfo['date_time'];
			$tz="+00:00"; // UTC time
			VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $tzTime);
//			echo ("dt=".$dtime." tz=".$tz." tzt=".$tzTime);
			if ($tzTime!='') {
				list($meetingDateStr, $time24Str)=explode(" ", $tzTime);
				list($year, $mon, $day)=explode("-", $meetingDateStr);
				list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
				$startTime=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
				$startTimeStr=date('Ymd', $startTime)."T".date('His', $startTime);
				list($hh, $mm, $ss)=explode(":", $meetingInfo['duration']);
				
				$endTime=$startTime+3600*(int)$hh+60*(int)$mm+(int)$ss;
				$endTimeStr=date('Ymd', $endTime)."T".date('His', $endTime);
			}
		}
		if ($startTimeStr=='')
			$startTimeStr=$currTimeStr;
		if ($endTimeStr=='')
			$endTimeStr=$currTimeStr;
			
		$description="URL: ".$meetingUrl."\\n";
		$login=$meetingInfo['login_type'];
		if ($login=='PWD')
			$description.="Password: ".$meetingInfo['password']."\\n";

		if ($meetingInfo['tele_conf']=='Y') {
			$description.="Phone number: ".$meetingInfo['tele_num'];
			if ($meetingInfo['tele_num2']!='')
				$description.=" Or ".$meetingInfo['tele_num2'];
			$description.="\\n";
			if ($isHost && $meetingInfo['tele_mcode']!='')
				$description.="Moderator code: ".$meetingInfo['tele_mcode']."\\n";
			if ($meetingInfo['tele_pcode']!='')
				$description.="Participant code: ".$meetingInfo['tele_pcode']."\\n";
		}
		
		$uid=md5($meetingUrl)."-".md5($startTimeStr);
		$vcal=		
"BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
BEGIN:VEVENT
DTSTART:${startTimeStr}Z
DTEND:${endTimeStr}Z
LOCATION:Web Meeting
TRANSP:OPAQUE
SEQUENCE:0
UID:$uid
URL;VALUE=URI:${meetingUrl}
DTSTAMP:${currTimeStr}Z
DESCRIPTION:${description}
SUMMARY:${title}
PRIORITY:5
X-MICROSOFT-CDO-IMPORTANCE:1
CLASS:PUBLIC
BEGIN:VALARM
TRIGGER:-PT15M
ACTION:DISPLAY
DESCRIPTION:Reminder
END:VALARM
END:VEVENT
END:VCALENDAR";
		return $vcal;
	}
	
	
	/**
	* @static
	* @param array  column values. 'access_id' and 'dir' will be automatically assigned.
	* @return string
	*/		
	static function GetXML($hostInfo, $meetingInfo)
	{
		$xml="<meeting id=\"".$meetingInfo['access_id']."\" ";
		if ($meetingInfo['public']=='Y')
			$xml.="public=\"true\" ";
		else
			$xml.="public=\"false\" ";
		
		$status="";
		if ($meetingInfo['status']=='STOP')
			$status="stopped";
		else if ($meetingInfo['status']=='REC')
			$status="recorded";
		else if ($meetingInfo['status']=='START')
			$status="started";			
		else if ($meetingInfo['status']=='START_REC')
			$status="recording";			
//		else if ($meetingInfo['status']=='LOCK')
//			$status="locked";
		
		$xml.="status=\"$status\" ";
		
		$login=$meetingInfo['login_type'];
		$password=$meetingInfo['password'];
				
		if ($login=='NAME')
			$loginType=0; // name only
		else if ($login=='PWD')
			$loginType=1; // name and password
		else if ($login=='REGIS')
			$loginType=2; // register
//		else if ($register!=''&&$password!='')
//			$loginType=4; // register and password
		else
			$loginType=3; // not required
		
		$xml.="login=\"".$loginType."\" ";
		$xml.="meeting_type=\"".$meetingInfo['meeting_type']."\" ";
		$xml.="locked=\"".$meetingInfo['locked']."\" ";
		
		if ($meetingInfo['close_register']=='Y')
			$xml.="registration=\"0\" ";
		else
			$xml.="registration=\"1\" ";
	
		$xml.="sessiondir=\"".VMeeting::GetEventDir($meetingInfo)."\" ";
		
		$hostName=VUser::GetFullName($hostInfo);
		$hostName=VObject::StrToXML($hostName);
		$hostId=$hostInfo['access_id'];
		$title=VObject::StrToXML($hostInfo['title']);
		
		$xml.="hostname=\"".$hostName."\" ";
		$xml.="hostid=\"".$hostId."\" ";
		$xml.="title=\"".VObject::StrToXML($meetingInfo['title'])."\" ";
		
		if ($meetingInfo['status']=='REC' && $meetingInfo['audio']=='Y') {
			$offset=0;
			$offset=(integer)$meetingInfo['audio_sync_time'];			

			$xml.="webaudio=\"true\" ";
			$xml.="audio_offset=\"".$offset."\" ";
		}
				
		$xml.=">\n";
		
		$xml.="<time start=\"".$meetingInfo['date_time']."\" duration=\"".$meetingInfo['duration']."\" />\n";
		
		if ($meetingInfo['tele_conf']=='Y') {
			$xml.="<telephone ";
			$xml.="number=\"".$meetingInfo['tele_num']."\" ";
			$xml.="number2=\"".$meetingInfo['tele_num2']."\" ";			
			if ($meetingInfo['tele_mcode']!='')
				$xml.="hostcode=\"".$meetingInfo['tele_mcode']."\" ";
			if ($meetingInfo['tele_pcode']!='')
				$xml.="accesscode=\"".$meetingInfo['tele_pcode']."\" ";
			
			$xml.="/>\n";			
		}
//		$xml.="<description>".VObject::StrToXML($meetingInfo['description'])."</description>\n";
		
		
		$xml.="</meeting>\n";
		
		return $xml;
	}
	/**
	* @param array 
	* @return string
	*/
/* meant for vpresent but not done yet.
	
	function GetScheduleXML($meetingInfo, $tz, $meetingUrl)
	{
		VMeeting::GetVPresentVersions($version, $minVersion, $downloadUrl);		
		$xml="<vshowsc \n";
		
		$xml.="version=\"".$version."\"\n";
		$xml.="minVersion=\"".$minVersion."\"\n";
		$xml.="downloadUrl=\"".$downloadUrl."\">\n";
		
		$xml.="<meeting id=\"".$meetingInfo['access_id']."\"\n";
		
		$login=$meetingInfo['login_type'];
		$password=$meetingInfo['password'];
		
		$xml.="login=\"".$login."\"\n";
		
		if ($login=='PWD')
			$xml.="password=\"".$meetingInfo['password']."\"\n";
		$xml.="title=\"".VObject::StrToXML($meetingInfo['title'])."\"\n";
											
		if ($meetingInfo['scheduled']=='Y' || $meetingInfo['status']=='REC') {
			$dtime=$meetingInfo['date_time'];
			VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $tzTime);
//			echo ("dt=".$dtime." tz=".$tz." tzt=".$tzTime);
			if ($tzTime!='') {
				list($meetingDateStr, $time24Str)=explode(" ", $tzTime);
				list($year, $mon, $day)=explode("-", $meetingDateStr);
				list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
				$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
				$dateStr=date('Y-m-d H:i', $theDate);
				$xml.="datetime=\"".$dateStr."\"\n";
			}
			
			if ($meetingInfo['scheduled']=='Y') {
				list($hh, $mm, $ss)=explode(":", $meetingInfo['duration']);
				$durStr="$hh:$mm";
				$xml.="duration=\"".$durStr."\"\n";
			}			
			
			$xml.="url=\"".$meetingUrl."\"\n";
		}
		$xml.=">\n";
		if ($meetingInfo['description']!='') {
			$xml.="<description>\n";
			$xml.=VObject::StrToXML($meetingInfo['description'])."\n";
			$xml.="</description>\n";
		}
		
		if ($meetingInfo['tele_conf']=='Y') {
			$xml.="<telephone\n";
			$xml.="number=\"".$meetingInfo['tele_num']."\"\n";
			if ($meetingInfo['tele_mcode']!='')
				$xml.="hostcode=\"".$meetingInfo['tele_mcode']."\"\n";
			if ($meetingInfo['tele_pcode']!='')
				$xml.="accesscode=\"".$meetingInfo['tele_pcode']."\"\n";
			$xml.="/>\n";			
		}
		$xml.="</meeting>\n";
		$xml.="</vshowsc>\n";
		
		return $xml;
	}
*/
	/**
	* Insert a row to TB_MEETING
	* @access  public 
	* @param	array  column values. 'access_id' will be automatically assigned.
	* @return integer error code
	*/		
	function Insert($info)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId>0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id already set");
			return $this->mErrorCode;
		}
		$hostId=$info['host_id'];
		if ($hostId<=0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("host_id not set");
			return $this->mErrorCode;
		}
		
		// create a unique access_id
		while (1) {
			// 7 digits
			$accessId=mt_rand(1000000, 9999999);
			if (!$this->InTable(TB_MEETING, 'access_id', $accessId))
				break;
		}
/*
		// create a unique dir name for the meeting			
		while (1) {
			// 5 digits
			$dir=mt_rand(10000, 99999);
			
			$exp=$this->AppendQuery("", "host_id", $hostId);
			$exp=$this->AppendQuery($exp, "dir", $dir);
			$errMsg=$this->Select(TB_MEETING, $exp, $rowInfo);
			if ($errMsg!='') {
				$this->mErrorCode=ERR_ERROR;
				$this->SetErrorMsg($errMsg);
				return $this->mErrorCode;
			}
			// no duplicate dir is found for the user. 
			if ($rowInfo['id']==null)
				break;				
		}
*/		
		$info['access_id']=$accessId;
//		$info['dir']=$dir;	
		
		return parent::Insert($info);
	}
	/**
	* Write data for a live meeting session to a cache file
	*/	
	static function WriteSessionCache($meetingInfo, $hostInfo, $serverUrl='', $serverList=null)
	{
		// write meeting info to a cache file	
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$php_ext="php";
		$groupId=$hostInfo['group_id'];
		$groupInfo=array();
		$group=new VGroup($groupId);
		if ($group->Get($groupInfo)!=ERR_NONE) {
			return $group->GetErrorMsg();
		}
		
		if ($serverUrl=='') {
			$serverUrl=VMeeting::GetMeetingServerUrl($hostInfo, $meetingInfo);
		}
		
		$licId=$hostInfo['license_id'];
		$license=new VLicense($licId);
		$license->GetValue('meeting_length', $meetingLength);
		$meetingLength*=60;
		
		$brand=new VBrand($hostInfo['brand_id']);
		$brandInfo=array();
		if ($brand->Get($brandInfo)!=ERR_NONE) {
			return $brand->GetErrorMsg();
		}
		$locale=$brandInfo['locale'];
		if (GetSessionValue('locale')!='')
			$locale=GetSessionValue('locale');
			
		$brandUrl=$brandInfo['site_url'];
		
		$viewerId=$hostInfo['viewer_id'];
		if ($viewerId==0) {
			if ($brand->GetValue('viewer_id', $viewerId)!=ERR_NONE) {
				return $brand->GetErrorMsg();
			}
		}
		
		$joinMeetingHook='';
		if ($brandInfo['hook_id']!='0') {
			$hook=new VHook($brandInfo['hook_id']);
			if ($hook->GetValue('join_meeting', $joinMeetingHook)!=ERR_NONE)
				return $hook->GetErrorMsg();
		}
		
		$siteUrl=SITE_URL;
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$viewer=new VViewer($viewerId);
		
		//use php varaibles for the server urls so they can't replaced during the query time
		//The variables need to be written to the cache file
		$fSiteUrl="\${_managementServerUrl}";
		$fBrandUrl="\${_brandUrl}";
		$fServerUrl="\${_hostingServerUrl}";
		
		$viewer->GetFlashVars($fSiteUrl, $fBrandUrl, $fServerUrl, $meetingInfo, $hostInfo, $groupInfo, $locale, $meetingDir, $php_ext,
				true, $meetingLength, $hostVars);
		$viewer->GetFlashVars($fSiteUrl, $fBrandUrl, $fServerUrl, $meetingInfo, $hostInfo, $groupInfo, $locale, $meetingDir, $php_ext,
				false, $meetingLength, $attVars);
		
		$meetingXml=VMeeting::GetXML($hostInfo, $meetingInfo);
		$meetingXml=str_replace("\"", "\\\"", $meetingXml);
		
		$meetingPassword=$meetingInfo['password'];
		$meetingPassword=str_replace("\"", "\\\"", $meetingPassword);
		$meetingHostId=$meetingInfo['host_id'];
		
		// get viewer background xml
		$viewer->GetValue('back_id', $backID);			
		$back=new VBackground($backID);
		$backInfo=array();
		$back->Get($backInfo);
		$backXml=VBackground::GetXML($backInfo, $fSiteUrl);
		$backXml=str_replace("\"", "\\\"", $backXml);
		
		// get sharing info xml
		@include_once("download/vpresent_version.php");
		$version='';
		if (isset($vpresent_version))
			$version=$vpresent_version;
			
		$minVersion='';
		if (isset($required_version))
			$minVersion=$required_version;		

		$downloadUrl=VWebServer::AddPaths($fSiteUrl, "download/download.php");		
		$sharingXml=VMeeting::GetSharingXML($hostInfo, $meetingInfo, $version, $minVersion, $downloadUrl, $fServerUrl);
		$sharingXml=str_replace("\"", "\\\"", $sharingXml);
		
		$loginType=$meetingInfo['login_type'];
		$meetingStatus=$meetingInfo['status'];
		
		// get teleconference server info
		$teleServerUrl='';
		$teleServerKey='';
		$teleDialout='';
		$teleTollfreeOnly='';
		$teleCanRecord='';
		$teleServerId=$groupInfo['teleserver_id'];
		if ($teleServerId!='0') {
			$teleServer=new VTeleServer($teleServerId);
			$teleInfo=array();
			$teleServer->Get($teleInfo);
			$teleDialout=GetArrayValue($teleInfo, 'can_dialout');
			$teleCanRecord=GetArrayValue($teleInfo,'can_record');
			$teleServerUrl=GetArrayValue($teleInfo, 'server_url');
			$teleServerKey=GetArrayValue($teleInfo, 'access_key');
			$teleTollfreeOnly=GetArrayValue($teleInfo, 'dial_tollfree_only');
		}
		
		$aMeeting=new VMeeting($meetingInfo['id']);
		$aMeeting->GetViewerUrl(true, $hostUrl, false, $fServerUrl);
		$aMeeting->GetViewerUrl(false, $attUrl, false, $fServerUrl);

//		$libUrl=$fBrandUrl;
		VUser::GetStorageUrl($hostInfo['brand_id'], $hostInfo, $storageUrl, $storageId, $storageCode, $storageServerId);
			
		// get all registered users
		$registList='';
		if ($loginType=='REGIS') {
			
			$query="meeting_id='".$meetingInfo['id']."'";
			$errMsg=VObject::SelectAll(TB_REGISTRATION, $query, $result, 0, 0, "*", "meeting_id", true);
			if ($errMsg!='') {
				// ignore error
			} else {
				$registList=" \$_registration=array(\n";
				while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
					// use the email as the key and name as the value
					if (isset($row['email']) && $row['email']!='') {
						$rowEmail=$row['email'];
						// need to escape " $ \ characters
						$rowEmail=str_replace("\\", "\\\\", $rowEmail);
						$rowEmail=str_replace("\"", "\\\"", $rowEmail);
						$rowEmail=str_replace("\$", "\\\$", $rowEmail);
						$rowEmail=strtolower(trim($rowEmail));		
						if ($rowEmail!='') {
							$registList.="   \"$rowEmail\" => array(";
							foreach($row as $key => $val) {
								if (isset($key) && $key!='') {
									$key=str_replace("\\", "\\\\", $key);
									$key=str_replace("\"", "\\\"", $key);
									$key=str_replace("\$", "\\\$", $key);		
									if ($key!='') {
										if (isset($val)) {
											$val=str_replace("\\", "\\\\", $val);
											$val=str_replace("\"", "\\\"", $val);
											$val=str_replace("\$", "\\\$", $val);
										} else
											$val='';
										$registList.="\"$key\" => \"$val\", ";
									}
								}
							}
							$registList.="),\n";
						}		
					}
				}			
				$registList.="  );\n";
		
			}
		}

		
		$infoContent="<?php\n";
		$infoContent.=" \$_brandUrl=isset(\$_brandUrl)?\$_brandUrl:\"$brandUrl\";\n";		// _brandUrl, _hostingServerUrl, _managementServerUrl must be defined first
		$infoContent.=" \$_hostingServerUrl=isset(\$_hostingServerUrl)?\$_hostingServerUrl:\"$serverUrl\";\n";
		$infoContent.=" \$_managementServerUrl=isset(\$_managementServerUrl)?\$_managementServerUrl:\"$siteUrl\";\n";
		$infoContent.=" \$_meetingStatus=\"$meetingStatus\";\n";
		$infoContent.=" \$_loginType=\"$loginType\";\n";
		$infoContent.=" \$_meetingType=\"".$meetingInfo['meeting_type']."\";\n";
		$infoContent.=" \$_meetingPassword=\"$meetingPassword\";\n";
		$infoContent.=" \$_hostId=\"$meetingHostId\";\n";
		$infoContent.=" \$_sessionId=\"".$meetingInfo['session_id']."\";\n";
		$infoContent.=" \$_meetingDir=\"".$meetingDir."\";\n";
		$infoContent.=" \$_teleNum=\"".$meetingInfo['tele_num']."\";\n";
		$infoContent.=" \$_teleNum2=\"".$meetingInfo['tele_num2']."\";\n";
		$infoContent.=" \$_teleMCode=\"".$meetingInfo['tele_mcode']."\";\n";
		$infoContent.=" \$_telePCode=\"".$meetingInfo['tele_pcode']."\";\n";
		$infoContent.=" \$_teleServerUrl=\"$teleServerUrl\";\n";
		$infoContent.=" \$_teleServerKey=\"$teleServerKey\";\n";
		$infoContent.=" \$_teleDialout=\"$teleDialout\";\n";
		$infoContent.=" \$_teleTollfreeOnly=\"$teleTollfreeOnly\";\n";
		$infoContent.=" \$_teleCanRecord=\"$teleCanRecord\";\n";
//		$infoContent.=" \$_libraryUrl=\"$libUrl\";\n";
		$infoContent.=" \$_storageUrl=\"$storageUrl\";\n";
		$infoContent.=" \$_storageId=\"$storageId\";\n";
		$infoContent.=" \$_storageCode=\"$storageCode\";\n";
		$infoContent.=" \$_hostVars=\"$hostVars\";\n";
		$infoContent.=" \$_attVars=\"$attVars\";\n";
		$infoContent.=" \$_hostUrl=\"$hostUrl\";\n";
		$infoContent.=" \$_attUrl=\"$attUrl\";\n";
		$infoContent.=" \$_meetingXml=\"$meetingXml\";\n";
		$infoContent.=" \$_backgroundXml=\"$backXml\";\n";
		$infoContent.=" \$_sharingXml=\"$sharingXml\";\n";
		$infoContent.=" \$_joinMeetingHook=\"$joinMeetingHook\";\n";
		
		if ($serverList) {
			$infoContent.=" \$_serverList=array(\n";			
			foreach ($serverList as $serv) {
				$url=$serv['url'];
				$name=$serv['name'];
				$max=$serv['max_connections'];
				$id=$serv['id'];
				//$infoContent.=" array(\"url\"=>\"$url\", \"ips\"=>\"$ips\", \"max\"=>\"$max\"),\n";
				$infoContent.=" array(\"id\"=>\"$id\", \"url\"=>\"$url\", \"name\"=>\"$name\", \"max_connections\"=>\"$max\"),\n";
			}
			$infoContent.=" );\n";
		}
		$infoContent.=$registList;
		$infoContent.="?>\n";

		
		$infoFile=VMeeting::GetSessionCachePath($meetingInfo['access_id']);
		$fp=@fopen($infoFile, "ab");
		
		if (flock($fp, LOCK_EX)) {
			ftruncate($fp, 0);
			fwrite($fp, $infoContent);				
			flock($fp, LOCK_UN);
			umask(0);
			@chmod($infoFile, 0777);			
		}
		if ($fp) {
			fclose($fp);
		} else {
			return "Couldn't write meeting data to cache.";
		}
		return '';			
		
	}
	
	static function CreateServerDir($serverInfo, $hostInfo, $meetingInfo)
	{

		$siteUrl=SITE_URL;

		$siteScriptUrl=VWebServer::AddPaths($siteUrl, VM_API);
		$php_ext=$serverInfo['php_ext'];
	
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);		
		$serverUrl=VWebServer::AddSlash($serverInfo['url']);
		$url=VWebServer::GetScriptUrl($serverInfo['url'], $php_ext);
		
		$def_page=$serverInfo['def_page'];
		$file_perm=$serverInfo['file_perm'];
		$login=rawurlencode($serverInfo['login']);
		$password=$serverInfo['password'];
		$meetingTitle=rawurlencode($meetingInfo['title']);
		
		$url.="?s=".SC_NEWMEETING;
		$data="id=$login&code=$password";
		$data.="&dir=$meetingDir";
		$data.="&host_id=".$hostInfo['access_id'];
		$data.="&title=$meetingTitle";
		$data.="&ext=$php_ext&index=$def_page&mode=$file_perm";
		$data.="&server=".$siteScriptUrl;
		$data.="&meeting_id=".$meetingInfo['access_id'];
//		$data.="&host_vars=".urlencode($hostVars);
//		$data.="&att_vars=".urlencode($attVars);
//		$data.="&meeting_xml=".urlencode($meetingXml);
		
		$succeeded=false;
		
		if (!VWebServer::CallScript($url."&".$data, $response)) {
			return ($response." returned from ".$serverUrl);
		}
		
		$licenseId=$hostInfo['license_id'];
		$license=new VLicense($licenseId);
		if ($license->GetValue('max_att', $maxAtt)!=ERR_NONE) {
			return ($license->GetErrorMsg());
		}

		// encode the max attendee limit in the key code
		// the limit is offset by n digits in the code
		// n is the first character in the code
		// return a rand number less than 10 digits
		$keyCode=sprintf("%d", mt_rand(1, 999999999));
		$len=strlen($keyCode);
		$lenStr=sprintf("%d", $len);
		// prepend the len of the rand
		$keyCode=$lenStr.$keyCode;
		$numStr=sprintf("%d", $maxAtt);
		$keyCode.=$numStr;
		
		// Set host file (vmhost.php)
		$url=VWebServer::GetScriptUrl($serverUrl, $php_ext);
		$url.="?s=".SC_SETHOST."&id=$login&code=$password";
		$url.="&dir=$meetingDir";
		$url.="&hostId=".$hostInfo['access_id'];
		//		$url.="&meetingId=".$meetingInfo['access_id'];
		$url.="&server=".rawurlencode($siteScriptUrl);
		$url.="&keyCode=$keyCode";
		if ($file_perm!='')
			$url.="&mode=$file_perm";
		$url.="&meetingType=".$meetingInfo['meeting_type'];
		$url.="&sessionDir=".VMeeting::GetEventDir($meetingInfo);
		if ($meetingInfo['close_register']!='N')
			$url.="&registration=1";

/*			
		// Use the Java server to convert swf to jpeg for the iphone app
		// This is NOT needed anymore with the 1.1 TouchMeeting app and 2.2.20 server because the conversion is done with PHP
		// The Java server is ONLY running on the touchmeeting.net server for backward compatibility with 1.0 TouchMeeing app.
		if (strpos($serverUrl, "www.touchmeeting.net")!==false) {
			$swfServerUrl='http://localhost:8080/swfserver/';
			$url.="&swfServer=".$swfServerUrl;
		}
		$url.="&".rand();
*/		
		if (!VWebServer::CallScript($url, $response)) {
			return($response." returned from ".$serverUrl);
		}


		return '';
	}
	/**
	* Update meeting dir on the web server
	*/		
	function UpdateServer($meetingInfo=null)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		if ($meetingInfo==null) {
			$meetingInfo=array();
			if ($this->Get($meetingInfo)!=ERR_NONE)
				return $this->GetErrorCode();
		}
			
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$this->mErrorCode=$host->GetErrorCode();
			$this->SetErrorMsg($host->GetErrorMsg());
			return $this->GetErrorCode();
		}
		$hostId=$hostInfo['access_id'];

		//$webServerId=VUser::GetWebServerId($hostInfo);
		if ($meetingInfo['webserver_id']!=0)
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
		
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}
		$webServer=new VWebServer($webServerId);

		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			$this->mErrorCode=$webServer->GetErrorCode();
			$this->SetErrorMsg($webServer->GetErrorMsg());
			return $this->GetErrorCode();		
		}
		if ($serverInfo['url']=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Url not set");
			return $this->mErrorCode;
		}
		
		$errMsg=VMeeting::CreateServerDir($serverInfo, $hostInfo, $meetingInfo);
		if ($errMsg!='') {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}

		return $this->mErrorCode;
	}
	/**
	* Create an event ID. The id needs to be unique in a meeting
	* @access private
	* @static
	* @return string event id string
	*/
	static function GetNewEventID()
	{
		// FIXME: needs to be unique
		return rand().rand();
	}
	/**
	* @access private
	* static
	* @return string event id string
	*/
	static function GetEventDir($meetingInfo)
	{
//		return md5($meetingInfo['id'].$meetingInfo['password']);
		if ($meetingInfo['session_id']!='')			
			return md5($meetingInfo['session_id']);
		else
			return 'evt';
		
/*
		$password=$meetingInfo['password'];
		if ($password=='') {
			return 'evt';
		} else {
			$id=0;
			$len=strlen($password);
			for ($i=0; $i<$len; $i++) 
				$id+=ord(substr($password, $i, 1))*($i+1);
			return "evt".$id;			
		}
*/
	}

	/**
	* @access private
	* static
	* @return string
	*/
	static function GetHostParamFile($hostInfo)
	{
		return 'var'.$hostInfo['id'].".inf";
	}
	
	/**
	* Start the meeting on the web server
	* @return integer error code
	*/		
	function StartMeeting()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE)
			return $this->GetErrorCode();
			
		if ($meetingInfo['status']=='REC') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Couldn't start meeting on a recording");
			return $this->GetErrorCode();			
		}			
			
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$this->mErrorCode=$host->GetErrorCode();
			$this->SetErrorMsg($host->GetErrorMsg());
			return $this->GetErrorCode();
		}
		$hostId=$hostInfo['access_id'];
		if ($hostId=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("host access_id not set");
			return $this->GetErrorCode();
		}
				
//		$webServerId=VUser::GetWebServerId($hostInfo);
		if ($meetingInfo['webserver_id']!=0)
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
		
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}
				
		$webServer=new VWebServer($webServerId);
		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			$this->mErrorCode=$webServer->GetErrorCode();
			$this->SetErrorMsg($webServer->GetErrorMsg());
			return $this->GetErrorCode();		
		}
		if ($serverInfo['url']=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Url not set");
			return $this->mErrorCode;
		}
		
		$groupId=$hostInfo['group_id'];
		$group=new VGroup($groupId);
		if ($group->Get($groupInfo)!=ERR_NONE) {
			$this->mErrorCode=$group->GetErrorCode();
			$this->SetErrorMsg($group->GetErrorMsg());
			return $this->GetErrorCode();		
		}
		
		// check if the web server is up
		$resp=@file_get_contents($serverInfo['url']."vversion.php");
		if ($resp==false) {
			// server is not responding
			// check if there is a secondary hosting server assigned to the user's group
			if ($meetingInfo['webserver_id']=='0') {	// means we are using the group's primary server
				
				$webServer2Id=$groupInfo['webserver2_id'];
				if (isset($webServer2Id) && $webServer2Id!='0' && $webServer2Id!=$webServerId) {	// a secondary server is present
					$webServer2=new VWebServer($webServer2Id);
					unset($serverInfo);
					$serverInfo=array();
					$webServer2->Get($serverInfo);
					if (!isset($serverInfo['url']) || $serverInfo['url']=='') {
						$this->mErrorCode=ERR_ERROR;
						$this->SetErrorMsg("Primary sever is not responding. Secondary server is not found or url not set.");
						return $this->mErrorCode;						
					} else {
						// primary server is down and secondary server is available
						// force the meeting to use the secondary server
						// need to reset meeting's webserver_id at EndMeeting so next start will use the primary server again
						$webServerId=$webServer2Id;
						$meetingInfo['webserver_id']=$webServerId;						
					}
				} else {
					// no secondary server is set
					$this->mErrorCode=ERR_ERROR;
					$serverUrl=$serverInfo['url'];
					$this->SetErrorMsg("Hosting server $serverUrl is not responding.");
					return $this->mErrorCode;										
				}
			} else {
				$this->mErrorCode=ERR_ERROR;
				$serverUrl=$serverInfo['url'];
				$this->SetErrorMsg("Hosting server $serverUrl is not responding.");
				return $this->mErrorCode;										
			}			
		}
		
		// save the cascading server list to the meeting cache file
		$serverList=array();
		if (defined("ENABLE_CACHING_SERVERS") && ENABLE_CACHING_SERVERS=='1') {
			// get a list of servers for cascading.
			// the first one should be the master server		
			$serverList[0]=array("id"=>$serverInfo['id'], "url"=>$serverInfo['url'], "name"=>$serverInfo['name'], "max_connections"=>$serverInfo['max_connections']);
			
			if ($serverInfo['slave_ids']!='') {
				$slaveIds=explode(',', $serverInfo['slave_ids']);
				$i=0;
				foreach ($slaveIds as $aid) {
					$i++;
					$aserver=new VWebServer($aid);
					$aserverInfo=array();
					$aserver->Get($aserverInfo);
					$serverList[$i]=array("id"=>$aserverInfo['id'], "url"=>$aserverInfo['url'], "name"=>$aserverInfo['name'], "max_connections"=>$aserverInfo['max_connections']);
				}
			}
		}
		
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$serverUrl=VWebServer::AddSlash($serverInfo['url']);
		$meetingDirUrl=$serverUrl.$meetingDir;
				
		$php_ext=$serverInfo['php_ext'];
		$file_perm=$serverInfo['file_perm'];
		$login=rawurlencode($serverInfo['login']);
		$password=$serverInfo['password'];

		// Flash has trouble loading from https under IE6. Don't know why
		// fixed. added additional header
		$siteUrl=SITE_URL;
//		$siteUrl=SERVER_URL;
		$siteScriptUrl=VWebServer::AddPaths($siteUrl, VM_API);
		
		// create a session
		$session=new VSession();
		$sessionInfo=array();
		$sessionInfo['brand_id']=$meetingInfo['brand_id'];
		$sessionInfo['meeting_aid']=$meetingInfo['access_id'];
//		$sessionInfo['meeting_title']=addslashes($meetingInfo['title']);
		$sessionInfo['meeting_title']=$meetingInfo['title'];
//		$sessionInfo['group_id']=$hostInfo['group_id'];
		
		$license=new VLicense($hostInfo['license_id']);
		$license->GetValue('code', $licenseCode);
		$sessionInfo['license_code']=$licenseCode;
		$sessionInfo['client_data']=$meetingInfo['client_data'];
		if ($hostInfo['login']==VUSER_GUEST) {
			$hostedBy="Guest";
			if (isset($_SERVER['REMOTE_ADDR'])) {
				$hostedBy=@gethostbyaddr($_SERVER['REMOTE_ADDR']);
				if ($hostedBy=='')
					$hostedBy= $_SERVER['REMOTE_ADDR'];
			}
			$sessionInfo['host_login']=$hostedBy;
		} else
			$sessionInfo['host_login']=$hostInfo['login'];
		
		$sessionInfo['start_time']='#NOW()';
		$sessionInfo['mod_time']='#NOW()';
		if ($session->Insert($sessionInfo)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($session->GetErrorMsg());
			return $this->mErrorCode;
		}
		if ($session->GetValue('id', $sessionId)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($session->GetErrorMsg());
			return $this->mErrorCode;
		}
		$meetingInfo['status']='START';
		$meetingInfo['locked']='N';	// unlock the meeting in case it is locked
		$meetingInfo['session_id']=$sessionId;
		VMeeting::WriteSessionCache($meetingInfo, $hostInfo, $serverInfo['url'], $serverList);

		// set the meeting status
		$updateInfo=array();
		$updateInfo['status']='START';
		$updateInfo['locked']='N';	// unlock the meeting in case it is locked
		$updateInfo['session_id']=$sessionId;
		$updateInfo['rec_event_id']='0';

		// force the meeting to use the secondary server if the primary server is down
		// set the meeting's webserver_id to the current web server used so other participants know which one to connect to
		// need to reset meeting's webserver_id at EndMeeting so next start will use the primary server again
		if (isset($webServer2Id) && $webServer2Id!='0')
			$updateInfo['webserver_id']=$webServer2Id;
		else
			$updateInfo['webserver_id']=$webServerId;
			
		if ($this->Update($updateInfo)!=ERR_NONE) {
			return $this->mErrorCode;
		}
			
		
		$errMsg=VMeeting::CreateUrl($serverInfo, $hostInfo, $meetingInfo, true);
		
		if ($errMsg!='') {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}

		$meetingDirUrl=VWebServer::AddSlash($serverInfo['url']).$meetingDir;
		$meetingPwd=$meetingInfo['password'];
		$hostName=rawurlencode(VUser::GetFullName($hostInfo));
		
		// send StartMeeting event to the server
		$evtUrl="?s=".SC_POSTEVENT."&from=$hostId&fromName=$hostName&meetingId="
			.$meetingInfo['access_id']."&userId=".$hostId;
//		if ($meetingInfo['login_type']=='PWD')
//			$evtUrl.="&password=".rawurlencode($meetingPwd);

		if ($meetingInfo['meeting_type']=='OPEN' || $meetingInfo['meeting_type']=='PANEL')
			$evtUrl.="&clearDir=0";
			
		$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
		$url.=$evtUrl."&type=".EVT_START_MEETING;
		$url.="&evtdir=".VMeeting::GetEventDir($meetingInfo);
		$url.="&id=".$this->GetNewEventID();
		$url.="&".rand();
		if (!VWebServer::CallScript($url, $response)) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($response." returned from ".$meetingDirUrl);
			return $this->mErrorCode;
		}
		
		// Tell teleserver to start pusing ActiveTalker events to the meeting
		// Currently only the WydeVoice bridge requires/supports this.	
/* Don't support this anymore. Needs more work to support it again		
		if (isset($groupInfo['teleserver_id']) && $groupInfo['teleserver_id']!='0') {
			$teleServer=new VTeleServer($groupInfo['teleserver_id']);
			$teleInfo=array();
			$teleServer->Get($teleInfo);
			// FIXME: need to check if the auio control is enabled for this meeting
			if (isset($teleInfo['show_active_talker']) && $teleInfo['show_active_talker']=='Y') {
				$teleUrl=VWebServer::AddPaths($teleInfo['server_url'], "conference/");

				$phone=($hostInfo['conf_num']!='')?$hostInfo['conf_num']:$hostInfo['conf_num2'];
				$phone=RemoveSpacesFromPhone($phone);
				$confId=$hostInfo['conf_mcode'];
				$confId=RemoveNonNumbers($confId);

				$data="phone=".$phone."&id=".$confId;
				$data.="&meetingid=".$meetingInfo['access_id'];
				$data.="&activetalker=B";
				$data.="&apiurl=".SITE_URL."rest/";	
				$brand=new VBrand($meetingInfo['brand_id']);
				$brand->Get($brandInfo);
				$data.="&apikey=".$brandInfo['api_key'];
				$data.="&brandid=".$brandInfo['name'];
				
				$accessKey=$teleInfo['access_key'];
				if ($accessKey!='')
					$sig="signature=".md5($data.$accessKey);
				else
					$sig="nosig=1"; // shouldn't allow this. need to remove this soon.

				$data.="&".$sig;
				
				$resp=HTTP_Request($teleUrl, $data, 'POST', 15);
				if (!$resp || strpos($resp, "<cm_status>0")===false) {					
					// error has occurred. 
					// write out the error to the log file but don't stop because it is not critical
					include_once("includes/log_error.php");
					LogError("Invalid response from $teleUrl $data resp=".$resp);
				}
				
			}	
		}
*/
		return $this->mErrorCode;		
	}
	/**
	* Start a recording session on the web server
	* @return integer error code
	*/		
	function StartRecording($recPhoneNumber='', $recPhoneCode='')
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE)
			return $this->GetErrorCode();
			
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$this->mErrorCode=$host->GetErrorCode();
			$this->SetErrorMsg($host->GetErrorMsg());
			return $this->GetErrorCode();
		}
		$hostId=$hostInfo['access_id'];
		if ($hostId=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("host access_id not set");
			return $this->GetErrorCode();
		}
				
		if ($meetingInfo['webserver_id']!=0)
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
		
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}
		$webServer=new VWebServer($webServerId);
		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			$this->mErrorCode=$webServer->GetErrorCode();
			$this->SetErrorMsg($webServer->GetErrorMsg());
			return $this->GetErrorCode();		
		}
		if ($serverInfo['url']=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Url not set");
			return $this->mErrorCode;
		}
		
		
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$serverUrl=VWebServer::AddSlash($serverInfo['url']);
		$meetingDirUrl=$serverUrl.$meetingDir;
		$php_ext=$serverInfo['php_ext'];
								
		$meetingPwd=$meetingInfo['password'];
		$hostName=rawurlencode(VUser::GetFullName($hostInfo));
//		$evtDir=$this->GetEventDir($meetingInfo);
		
		$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
		$url.="?s=".SC_STARTRECORDING;
		$url.="&user_id=$hostId";
		$url.="&evtdir=".VMeeting::GetEventDir($meetingInfo);
		$url.="&meeting_id=".$meetingInfo['access_id'];
		if ($recPhoneNumber!='' && $recPhoneCode!='')
			$url.="&tele_num=$recPhoneNumber&tele_mcode=$recPhoneCode";
				
		if (!VWebServer::CallScript($url, $response)) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($response);
			return $this->mErrorCode;
		}
		
		// send StartRecording event to the server
		$evtUrl="?s=".SC_POSTEVENT."&from=$hostId&fromName=$hostName";
		$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
		$url.=$evtUrl."&type=".EVT_START_RECORDING;
		$url.="&id=".$this->GetNewEventID();			
		$url.="&evtdir=".VMeeting::GetEventDir($meetingInfo);
		$url.="&".rand();

		if (!VWebServer::CallScript($url, $response)) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($response." returned from ".$meetingDirUrl);
			return $this->mErrorCode;
		}
		
		$eventId=0;
		$items=explode("\n", $response);
		if (count($items)>1) {
			list($key, $val)=explode("=", $items[1]);
			$eventId=$val;			
		}
		
		// set the meeting status
		$udpateInfo=array();
		$udpateInfo['status']='START_REC';
		$udpateInfo['rec_event_id']=$eventId;
		if ($this->Update($udpateInfo)!=ERR_NONE) {
			return $this->mErrorCode;
		}
		// update the meeting cache file
		if (isset($hostInfo['id']) && $serverUrl!='') {
			$meetingFile=VMeeting::GetSessionCachePath($meetingInfo['access_id']);
			@include_once($meetingFile);
			$meetingInfo['status']='START_REC';
			$meetingInfo['rec_event_id']=$eventId;
			// $_serverList should be defined in the cache file
			if (isset($_serverList))
				VMeeting::WriteSessionCache($meetingInfo, $hostInfo, $serverUrl, $_serverList);
			else
				VMeeting::WriteSessionCache($meetingInfo, $hostInfo, $serverUrl);
			
		}

		return $this->mErrorCode;		
	}
	/**
	* End a recording session on the web server
	* @return integer error code
	*/		
	function EndRecording($recPhoneNumber, $recPhoneCode, &$recMeetingId, &$recMeetingTitle, $audioSyncTime=0)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE)
			return $this->GetErrorCode();
			
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$this->mErrorCode=$host->GetErrorCode();
			$this->SetErrorMsg($host->GetErrorMsg());
			return $this->GetErrorCode();
		}
		$hostId=$hostInfo['access_id'];
		if ($hostId=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("host access_id not set");
			return $this->GetErrorCode();
		}
				
//		$webServerId=VUser::GetWebServerId($hostInfo);
		if ($meetingInfo['webserver_id']!=0)
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
		
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}
		$webServer=new VWebServer($webServerId);
		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			$this->mErrorCode=$webServer->GetErrorCode();
			$this->SetErrorMsg($webServer->GetErrorMsg());
			return $this->GetErrorCode();		
		}
		if ($serverInfo['url']=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Url not set");
			return $this->mErrorCode;
		}
		
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$serverUrl=VWebServer::AddSlash($serverInfo['url']);
		$meetingDirUrl=$serverUrl.$meetingDir;
		$php_ext=$serverInfo['php_ext'];
								
		$meetingPwd=$meetingInfo['password'];
		$hostName=rawurlencode(VUser::GetFullName($hostInfo));
		
		// send EndRecording event to the server
		$evtUrl="?s=".SC_POSTEVENT."&from=$hostId&fromName=$hostName";
		$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
		$url.=$evtUrl."&type=".EVT_END_RECORDING;
		$url.="&id=".$this->GetNewEventID();
		$url.="&evtdir=".VMeeting::GetEventDir($meetingInfo);
		$url.="&".rand();

		if (!VWebServer::CallScript($url, $response)) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($response);
			return $this->mErrorCode;
		}	
		
		// create a recorded meeting
		$title=$meetingInfo['title']." Recording";
		$recording=new VMeeting();
		$recInfo=array();
		$recInfo['host_id']=$meetingInfo['host_id'];
		$recInfo['brand_id']=$meetingInfo['brand_id'];
		$recInfo['session_id']=$meetingInfo['session_id'];
		$recInfo['login_type']='NONE';
		$recInfo['title']=$title;
		$recInfo['status']='REC';
		$recInfo['scheduled']='Y';
		$recInfo['date_time']="#NOW()";
		$recInfo['audio_rec_id']=$meetingInfo['audio_rec_id'];
		$recInfo['tele_num']=$recPhoneNumber;
		$recInfo['tele_pcode']=$recPhoneCode;		
		$recInfo['webserver_id']=$webServerId;
		$recInfo['rec_event_id']=$meetingInfo['rec_event_id'];
		
		// audio sync time is computed by adding the recording bridge default delay time
		// and the delay between the audio and web recording start times for this meeting
		$syncTime=$audioSyncTime;	// audio bridge default sync time.
		if ($meetingInfo['audio_sync_time']!='')
			$syncTime+=(int)$meetingInfo['audio_sync_time'];	// the delay between audio and web recording start times
		
		$recInfo['audio_sync_time']=$syncTime;
		$recInfo['can_download']='N';
				
		if ($recording->Insert($recInfo)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($recording->GetErrorMsg());
			return $this->mErrorCode;
		}
		
		$recording->Get($recInfo);
		$recMeetingId=$recInfo['id'];
		$recMeetingTitle=$recInfo['title'];

		$errMsg=VMeeting::CopyRecordingData($meetingDirUrl, $recInfo, $duration);
		if ($errMsg=='') {			
			$durInfo=array();
			$durInfo['duration']=VMeeting::SecToStr($duration);
			if ($recording->Update($durInfo)!=ERR_NONE) {
				// ignore error here because we can reprocess the data later
			}
		}

		// set the meeting status
		$udpateInfo=array();
		$udpateInfo['status']='START';
		$udpateInfo['audio_rec_id']='';
		if ($this->Update($udpateInfo)!=ERR_NONE) {
//			return $this->mErrorCode;
		}
		// update the meeting cache file
		if (isset($hostInfo['id']) && $serverUrl!='') {
			$meetingFile=VMeeting::GetSessionCachePath($meetingInfo['access_id']);
			@include_once($meetingFile);
			$meetingInfo['status']='START';
			$meetingInfo['audio_rec_id']='';
			// $_serverList should be defined in the cache file
			if (isset($_serverList))
				VMeeting::WriteSessionCache($meetingInfo, $hostInfo, $serverUrl, $_serverList);
			else
				VMeeting::WriteSessionCache($meetingInfo, $hostInfo, $serverUrl);
		}
		return $this->mErrorCode;		
	}
	
	// copy recorded data from the orginal meeting at $srcMeetingDirUrl to the recording ($recInfo) folder
	// srcMeeting must still have the event data
	function CopyRecordingData($srcMeetingDirUrl, $recInfo, &$duration)
	{
//		$siteUrl=$siteScriptUrl=VWebServer::AddPaths(SITE_URL, VM_API);
		$evtDir=VMeeting::GetEventDir($recInfo);
		$url=VWebServer::GetScriptUrl($srcMeetingDirUrl, "php");
		$url.="?s=".SC_ENDRECORDING;
		$url.="&session_dir=$evtDir&evtdir=$evtDir";
//		$url.="&user_id=$hostId";
		$url.="&meeting_id=".$recInfo['access_id'];
//		$url.="&server=".$siteUrl;
		$url.="&title=".rawurlencode($recInfo['title']);
		$url.="&start_index=".$recInfo['rec_event_id'];
						
//		if ($response = HTTP_Request($url)) {
		if ($response = @file_get_contents($url)) {

			$items=explode("\n", $response);
			if (count($items)>1 && $items[0]=='OK') {
				list($key, $val)=explode("=", $items[1]);
				$duration=$val;
			} else {
				return $response;
			}
		} else {
			return "Couldn't get response from ".$url;
		}
		
		
		return '';
		
	}
	

	static function SecToStr($seconds)
	{
		$hh=floor($seconds/3600);
		$seconds-=$hh*3600;
		$mm=floor($seconds/60);
		$ss=$seconds-$mm*60;
		
		if ($hh<10)
			$hhs="0".strval($hh);
		else
			$hhs=strval($hh);
		if ($mm<10)
			$mms="0".strval($mm);
		else
			$mms=strval($mm);
		if ($ss<10)
			$sss="0".strval($ss);
		else
			$sss=strval($ss);	

		return $hhs.":".$mms.":".$sss;
	}
	/**
	* End the meeting on the web server
	* @return integer error code
	*/		
	function EndMeeting($sendReport=true)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE)
			return $this->GetErrorCode();
			
		// ignore if the meeting is already stopped or is a recording
		if ($meetingInfo['status']=='STOP' || $meetingInfo['status']=='REC') {
			return $this->mErrorCode;		
		}
		
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			// not an error if the host user is not found.
			// this could happen if the meeting has been idle for long and the user has been deleted
//			$this->mErrorCode=$host->GetErrorCode();
//			$this->SetErrorMsg($host->GetErrorMsg());
//			return $this->GetErrorCode();
		}
		
		$hostId='';
		if (isset($hostInfo['access_id'])) {
			$hostId=$hostInfo['access_id'];
/*
			if ($hostId=='') {
				$this->mErrorCode=ERR_ILL;
				$this->SetErrorMsg("host access_id not set");
				return $this->GetErrorCode();
			}
*/
		}				
//		$webServerId=VUser::GetWebServerId($hostInfo);
		$webServerId='0';
		if ($meetingInfo['webserver_id']!='0')
			$webServerId=$meetingInfo['webserver_id'];
		else if (isset($hostInfo['id']))
			$webServerId=VUser::GetWebServerId($hostInfo);
/*		
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}
*/
		if ($webServerId!='0') {
			$webServer=new VWebServer($webServerId);
			$serverInfo=array();
			if ($webServer->Get($serverInfo)!=ERR_NONE) {
				// not an error if the web server is not found.
				// this could happen if the meeting has been idle for long and the web server has been deleted
	//			$this->mErrorCode=$webServer->GetErrorCode();
	//			$this->SetErrorMsg($webServer->GetErrorMsg());
	//			return $this->GetErrorCode();		
			}
		}
		$serverUrl='';
		if (isset($serverInfo['url']) && $serverInfo['url']!='') {
//			$this->mErrorCode=ERR_ILL;
//			$this->SetErrorMsg("Url not set");
//			return $this->mErrorCode;
			$serverUrl=VWebServer::AddSlash($serverInfo['url']);
			$php_ext=$serverInfo['php_ext'];
			$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
			$meetingDirUrl=$serverUrl.$meetingDir;
			$meetingPwd=$meetingInfo['password'];
			$hostName=rawurlencode(VUser::GetFullName($hostInfo));
		}								
				
		$attendeeList=array();
		// Get live attendees
		// for version 2.2.08 or earlier, live attendees are stored in the attendee_live db table
		// for latter versions, live attendees are stored on the hosting server and not in the db
		// Since we don't know which version is used, we need to check the db first
/* Don't need to query the DB anymore for live attendees
		$query="session_id='".$meetingInfo['session_id']."'";
		$errMsg=VObject::SelectAll(TB_ATTENDEE_LIVE, $query, $attResult);
		$num_rows=0;
		if ($attResult)
			$num_rows = mysql_num_rows($attResult);		
		if ($errMsg=='' && $num_rows>0) {
			while ($attRow = mysql_fetch_array($attResult, MYSQL_ASSOC)) {
				// don't need these records
				if (isset($attRow['can_draw']))
					unset($attRow['can_draw']);
				if (isset($attRow['can_present']))
					unset($attRow['can_present']);
				if (isset($attRow['show_webcam']))
					unset($attRow['show_webcam']);
				if (isset($attRow['emoticon']))
					unset($attRow['emoticon']);
				
				$attendee=new VAttendee();			
				
				if ($attendee->Insert($attRow)!=ERR_NONE) {
					$this->mErrorCode=ERR_ERROR;
					$this->SetErrorMsg($attendee->GetErrorMsg());
					return $this->mErrorCode;
				} else {
					$liveAtt=new VAttendeeLive($attRow['id']);
					$liveAtt->Drop();
				}
			}
		} else 
*/
		if ($serverUrl!='') {
			// get attendee data from the hosting server
			$attCount=0;
			$attList=array();
			$ret=VSession::GetAttendees($meetingDirUrl,
					$meetingInfo['session_id'], "", false, true, $meetingInfo['access_id'], $meetingInfo['tele_num'], $meetingInfo['tele_mcode'], $attCount, $attList);
			
			// try again
			if (!$ret) {
				sleep(1);
				$ret=VSession::GetAttendees($meetingDirUrl,
						$meetingInfo['session_id'], "", false, true, $meetingInfo['access_id'], $meetingInfo['tele_num'], $meetingInfo['tele_mcode'], $attCount, $attList);
			}
			if ($ret) {
				foreach ($attList as $attItem) {
					$attendee=new VAttendee();			
					$attInfo=array();
					$attInfo['attendee_id']=GetArrayValue($attItem, 'userid');
					
					// audio only attendee; skip
					if ($attInfo['attendee_id']=='')
						continue;
					
					// see if the attendee id is a site member id (based on the lenghth of the id)
					if (strlen($attInfo['attendee_id'])==VUSER_ID_LENGTH)
						$attInfo['user_id']=$attInfo['attendee_id'];
					$attInfo['session_id']=$meetingInfo['session_id'];
					$startTime=GetArrayValue($attItem, 'startTime');
					if ($startTime!='')
						$attInfo['start_time']=date("Y-m-d H:i:s", (integer)($startTime));
					$modTime=GetArrayValue($attItem, 'modTime');		
					if ($modTime!='')
						$attInfo['mod_time']=date("Y-m-d H:i:s", (integer)($modTime));
					
					$attInfo['user_name']=GetArrayValue($attItem, 'username');
					$attInfo['user_ip']=GetArrayValue($attItem, 'userip');
					$attInfo['brand_id']=$meetingInfo['brand_id'];
					$attInfo['caller_id']=GetArrayValue($attItem, 'callerId');
					if ($attInfo['caller_id']=='')
						$attInfo['caller_id']=GetArrayValue($attItem, 'lastCallerId');
						
					$attInfo['break_time']=GetArrayValue($attItem, 'breakTime');
					$attInfo['server_id']=GetArrayValue($attItem, 'serverId');
					$attInfo['cam_time']=GetArrayValue($attItem, 'camTime');
					
					if ($attendee->Insert($attInfo)!=ERR_NONE) {
						$this->mErrorCode=ERR_ERROR;
						$this->SetErrorMsg($attendee->GetErrorMsg());
						return $this->mErrorCode;
					}
					
					$attendeeList[]=$attInfo;
				}
			} else {
				/* ignore the error because we want to end the meeting even if the hosting server is down */
				/*
				$this->mErrorCode=ERR_ERROR;
				$this->SetErrorMsg("Couldn't get attendee records from the web conferencing server.");
				return $this->mErrorCode;
				*/
			}	
		}
		
	
		// send EndMeeting event to the server

		if ($serverUrl!='') {
			
			$evtUrl="?s=".SC_POSTEVENT."&from=$hostId&fromName=$hostName";
			$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
			$url.=$evtUrl."&type=".EVT_END_MEETING;
			$url.="&id=".$this->GetNewEventID();
			$url.="&evtdir=".VMeeting::GetEventDir($meetingInfo);
			$url.="&".rand();

			if (!VWebServer::CallScript($url, $response)) {
				//ignore the error because the hosting server may be down or the folder may not exist
				//$this->mErrorCode=ERR_ERROR;
				//$this->SetErrorMsg($response." returned from ".$meetingDirUrl);
				//return $this->mErrorCode;
			}
				
		}
		
		$noTranscript=false;
		if ($serverInfo['installed_version']!='' && $serverInfo['installed_version']!='NA' && $serverInfo['installed_version']<'2.2.17.0') {
			$noTranscript=true;
		}
				
		$transcripts='';
		// When EndMeeting is called by cron.php, it may be ending a meeting launched by a version prior to 2.2.17,
		// which does not support transcript. Therefore, the script may return no response
		// only get transcript is the version is unknown or >= 2.2.17
		if (defined('ENABLE_TRANSCRIPTS') && constant('ENABLE_TRANSCRIPTS')=='1' &&
			($serverInfo['installed_version']=='' || $serverInfo['installed_version']=='NA'|| $serverInfo['installed_version']>='2.2.17.0')	
			) 
		{
			$evtDir=VMeeting::GetEventDir($meetingInfo);
			$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
			$url.="?s=".SC_GETTRANSCRIPTS."&hostid=$hostId";
			$url.="&evtdir=$evtDir";
			$url.="&".rand();
			
//			$resp=HTTP_Request($url, '', 'GET', 15);						
			$resp=@file_get_contents($url);
			if (!$resp) {
				sleep(1);
				$resp=@file_get_contents($url);
			}

			if ($resp==false) {
				// error has occurred. 
				// write out the error to the log file but don't stop because it is not critical
				include_once("includes/log_error.php");
				LogError("$url returns null response.");
			} else if (strpos($resp, "ERROR")===0) {
				// error has occurred. 
				// write out the error to the log file but don't stop because it is not critical
				include_once("includes/log_error.php");
				LogError("$url returns $resp");
			} else
				$transcripts=$resp;
			
		}
		
		// get polling results
		$pollQuestions=$pollResults='';
		if (defined('ENABLE_POLLING') && constant('ENABLE_POLLING')=='1') {
			$pollQuestions='';	
			$evtDir=VMeeting::GetEventDir($meetingInfo);
			$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
			$url.="?s=".SC_GETPOLLS;
			$url.="&evtdir=$evtDir";
			$url.="&".rand();
			$pollResults=@file_get_contents($url);
			if (!$pollResults) {
				sleep(1);
				$pollResults=@file_get_contents($url);
			}		
			if ($pollResults==false) {
				include_once("includes/log_error.php");
				LogError("$url returns null response.");
			} else {
				
				// parse the poll results xml to find all questions
				// find the questions from the db and save them with the session data
				$pollXml = simplexml_load_string($pollResults);
				if ($pollXml) {
					require_once("dbobjects/vquestion.php");
					$ques=array();	
					foreach ($pollXml->children() as $aPoll) {
						foreach ($aPoll->children() as $aEvent) {
							if (isset($aEvent->answer[0])) {
								$qid=$aEvent->answer[0]["questionid"];
								if (!in_array($qid, $ques))
									$ques[]=$qid;
							}
						}
					}
					
					$pollQues='';
					foreach ($ques as $qid) {
						$qObj=new VQuestion($qid);
						$qObj->Get($qInfo);
						if (isset($qInfo['id'])) {
							$pollQues.=$qObj->ToXML("question", $qInfo);
						}			
					}
					if ($pollQues!='')
						$pollQuestions=XML_HEADER."\n<questions>\n$pollQues\n</questions>";
				}
			}
		}
		// update the session info			
		$session=new VSession($meetingInfo['session_id']);
		if ($session->End($transcripts, $pollResults, $pollQuestions)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($session->GetErrorMsg());
			return $this->mErrorCode;
		}

		// set the meeting status
		$updateInfo=array();
		$updateInfo['status']='STOP';
		$updateInfo['locked']='N';	// unlock the meeting in case it is locked
		$updateInfo['webserver_id']='0';	// reset the webserver_id so next start meeting will use the group's server setting
		if ($this->Update($updateInfo)!=ERR_NONE) {
			return $this->mErrorCode;
		}
		
		// update the meeting cache file
		if (isset($hostInfo['id']) && $serverUrl!='') {
			$meetingInfo['status']='STOP';
			$meetingInfo['locked']='N';
			$meetingInfo['webserver_id']='0';
			VMeeting::WriteSessionCache($meetingInfo, $hostInfo, $serverUrl);
		}
/*
		$meetingInfo['status']='STOP';

		// update the status on the server
		$meetingXml=VMeeting::GetXML($hostInfo, $meetingInfo);
		$xmlFile=$meetingDir.VM_MEETING_FILE;
		if ($webServer->UploadData($meetingXml, strlen($meetingXml), $xmlFile, $response)
				!=ERR_NONE) 
		{
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($webServer->GetErrorMsg());
			return $this->mErrorCode;
		}
*/		

		// Delete meeting event data folders
		// Only do this if no recording was done during the meeting
		// Otherwise, keep all the meeting data around so we can re-generate the recording
		// in case there is a need. Most likely we don't need this because the data should
		// have been saved to another recording folder but we keep the original for insurance.
		// Need a way to clean up later...
		if ($meetingInfo['rec_event_id']=='0' && $serverUrl!='') {
			// wait for everyone to receive the event
			sleep(5);
			
			$url=VWebServer::GetScriptUrl($serverInfo['url'], $php_ext);
			
			$login=rawurlencode($serverInfo['login']);
			$password=$serverInfo['password'];
			$evtDir=VMeeting::GetEventDir($meetingInfo);
			
			$url.="?s=".SC_ENDMEETING."&id=$login&code=$password";
			$url.="&dir=$meetingDir";
			$url.="&evtDir=$evtDir";
			$succeeded=false;
			
			if (!VWebServer::CallScript($url, $response)) {
				/* ignore the error because the hosting server may be down
				$this->mErrorCode=ERR_ERROR;
				$this->SetErrorMsg($response." returned from ".$serverInfo['url']);
				return $this->mErrorCode;
				*/
			}
		} else if ($serverUrl!='') {
			// wait for attendees to receive the end meeting event
			sleep(1);
		}
		
		// delete the meeting cache file
		$infoFile=VMeeting::GetSessionCachePath($meetingInfo['access_id']);
		@unlink($infoFile);
		
		// see if we need to send a report to the host
		$brand=new VBrand($meetingInfo['brand_id']);
		$brand->Get($brandInfo);

		if (isset($brandInfo['send_report']) &&  $brandInfo['send_report']=='Y' && $sendReport
//			&& defined('ENABLE_TRANSCRIPTS') && constant('ENABLE_TRANSCRIPTS')=='1' 
			&& isset($hostInfo['access_id']) && $meetingInfo['send_report']=='Y') 
		{
			$hostEmail=$hostInfo['email'];
			if ($hostEmail=='')
				$hostEmail=$hostInfo['login'];

			if (valid_email($hostEmail) && $hostInfo['login']!=VUSER_GUEST && $hostInfo['login']!=ROOT_USER) {
				require_once("dbobjects/vmailtemplate.php");
				// send meeting session report with email to the host
				$report=VMeeting::GetSessionReport($meetingInfo, $transcripts, $attendeeList, $pollQuestions, $pollResults);
				
				if ($report) {

					$from=$brandInfo['from_email'];
					$fromName=$brandInfo['from_name'];
					
					$toName=VUser::GetFullName($hostInfo);

					$subject=_Text("Meeting report for");
					$subject.=" \"".$meetingInfo['title']."\"";
					VMailTemplate::Send($fromName, $from, $toName, $hostEmail, $subject, $report, '', '', '', true, null, $brandInfo);
				}
			}
		}
		
		return $this->mErrorCode;		
	}
	function GetSessionReport($meetingInfo, $transcripts, $attendeeList, $pollQues, $pollResults)
	{	
		require_once("dbobjects/vmailtemplate.php");
		global $VARGS;
		
		$templateInfo=array();
		VMailTemplate::GetMailTemplate($meetingInfo['brand_id'], 'MT_REPORT2', $templateInfo);
		if (!isset($templateInfo['body_text']))
			return false;
		
		$sessionId=$meetingInfo['session_id'];
		$meetingId=$meetingInfo['access_id'];
		$title=$meetingInfo['title'];
		$session=new VSession($sessionId);
		$session->GetValue('start_time', $startTime);
		GetSessionTimeZone($tzSName, $tz);
		VObject::ConvertTZ($startTime, 'SYSTEM', $tz, $tzSTime);
		
		$VARGS['session_id']=$sessionId;
		$VARGS['in_email']='1';
		$VARGS['show_all']='1';
		
		ob_start();
		@include_once($GLOBALS['LOCALE_FILE']);
		include_once("includes/common_text.php");
		include_once("includes/admin_attendee.php");
		$attHtml=ob_get_contents();
		ob_end_clean();
		
		$html=VSession::XmlToHtmlTranscript($transcripts);
		if ($html!='') {
			$transHtml= <<<EOD
<div class='heading1'>Transcripts</div>
$html
<div class='m_caption'>*Time is measured from the start of the meeting.</div>
EOD;
		} else
			$transHtml='';

		$html=VSession::XmlToHtmlPollResults($sessionId, $pollQues, $pollResults);
		if ($html!='') {
			$pollHtml= <<<EOD
<div class='heading1'>Polls</div>
$html
EOD;
		} else
			$pollHtml='';
		
		$templateText=str_replace('[MEETING_ID]', $meetingId, $templateInfo['body_text']);
		$templateText=str_replace('[SESSION_ID]', $sessionId, $templateText);
		$templateText=str_replace('[MEETING_TITLE]', $title, $templateText);
		$templateText=str_replace('[START_TIME]', $tzSTime." ".$tzName, $templateText);
		$templateText=str_replace('[ATTENDEES]', $attHtml, $templateText);
		$templateText=str_replace('[TRANSCRIPTS]', $transHtml, $templateText);
		$templateText=str_replace('[POLLS]', $pollHtml, $templateText);
		return $templateText;
	}
	/**
	* Delete the meeting on the web server
	* @return integer error code
	*/		
	function DeleteMeeting()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE) {
			return $this->GetErrorCode();
		}
			
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			// ok if the host user is not found
//			$this->mErrorCode=$host->GetErrorCode();
//			$this->SetErrorMsg($host->GetErrorMsg());
//			return $this->GetErrorCode();
		}
//		$hostId='';
//		if (isset($hostInfo['access_id'])) {
//			$hostId=$hostInfo['access_id'];
//			$this->mErrorCode=ERR_ILL;
//			$this->SetErrorMsg("host access_id not set");
//			return $this->GetErrorCode();
//		}
/*				
		if ($meetingInfo['status']=='REC')
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
*/
		$webServerId='0';
		if ($meetingInfo['webserver_id']!='0')
			$webServerId=$meetingInfo['webserver_id'];
		else if (isset($hostInfo['id']))
			$webServerId=VUser::GetWebServerId($hostInfo);
		
		if ($webServerId!='0') {
			$webServer=new VWebServer($webServerId);
			$serverInfo=array();
			if ($webServer->Get($serverInfo)!=ERR_NONE) {
				// OK if the web server is not found
//				$this->mErrorCode=$webServer->GetErrorCode();
//				$this->SetErrorMsg($webServer->GetErrorMsg());
//				return $this->GetErrorCode();		
			}

			if (isset($serverInfo['url']) && $serverInfo['url']!='') {
				$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
				$serverUrl=VWebServer::AddSlash($serverInfo['url']);
				$meetingDirUrl=$serverUrl.$meetingDir;
				$php_ext=$serverInfo['php_ext'];
										
				$meetingPwd=$meetingInfo['password'];
				$hostName=rawurlencode(VUser::GetFullName($hostInfo));
				$evtDir=$this->GetEventDir($meetingInfo);
				
				// delete meeting folders
				$url=VWebServer::GetScriptUrl($serverInfo['url'], $php_ext);

				$login=rawurlencode($serverInfo['login']);
				$password=$serverInfo['password'];
				
				$url.="?s=".SC_DELMEETING."&id=$login&code=$password";
				$url.="&dir=$meetingDir";
				$url.="&evtDir=$evtDir";
				$succeeded=false;
				
				if (!VWebServer::CallScript($url, $response)) {
//					$this->mErrorCode=ERR_ERROR;
//					$this->SetErrorMsg($response);
//					return $this->mErrorCode;
				}
			}
		}

		// delete the meeting from db
		if ($this->Drop()!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("Couldn't delete the record");
			return $this->mErrorCode;
		}
		return $this->mErrorCode;	
		
	}
	/**
	*
	*/
	function GetMeetingUrl(&$url, $viewer=false, $user='')
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
			
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE)
			return $this->GetErrorCode();
	
		$brandId=$meetingInfo['brand_id'];
		$brand=new VBrand($brandId);
		if ($brand->GetValue('site_url', $brandUrl)!=ERR_NONE) {
			$this->mErrorCode=$brand->GetErrorCode();
			$this->SetErrorMsg($brand->GetErrorMsg());			
			return $this->GetErrorCode();
		}
		
		if ($viewer) {
			$url=$brandUrl."?viewer=".$meetingInfo['access_id'];
			if ($user!='') {
				$url.="&user=".rawurlencode($user);
				if ($meetingInfo['login_type']=='PWD')
					$url.="&pass=".rawurlencode($meetingInfo['password']);					
			}
		} else {
			$url=$brandUrl."?meeting=".$meetingInfo['access_id'];
		}	
		
		return $this->GetErrorCode();	
	}
	/**
	*
	*/
//	function CreateUrl($meetingDirUrl, $php_ext, $host, $doUpdate=false)
	function CreateUrl($serverInfo, $hostInfo, $meetingInfo, $doUpdate=false)
	{
//		$this->mErrorCode=ERR_NONE;
//		$this->mErrorMsg='';

		if (!$doUpdate) {
			$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
			$serverUrl=VWebServer::AddSlash($serverInfo['url']);
			$meetingDirUrl=$serverUrl.$meetingDir;
			$php_ext=$serverInfo['php_ext'];

			// check if the meeting dir exists on the web server
			// if not, create the user dir and then the meeting dir		
			$url=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
//			$response=@file_get_contents($url);
			$response=HTTP_Request($url);
			if ($response==false || strpos($response, "OK")===false)
				$doUpdate=true;
		
		}
		
		if ($doUpdate) {
			$dir=$hostInfo['access_id'];
			$errMsg=VUser::CreateServerDir($serverInfo, $dir);
			if ($errMsg!='') {
				return $errMsg;
			}
			$errMsg=VMeeting::CreateServerDir($serverInfo, $hostInfo, $meetingInfo);
			if ($errMsg!='') {
				return $errMsg;
			}

		}

		return '';
	}
	/**
	*
	*/
	function GetViewerUrl($isHost, &$url, $create=false, $hostingServerUrl='')
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
			
		$meetingInfo=array();
		if ($this->Get($meetingInfo)!=ERR_NONE)
			return $this->GetErrorCode();
		
		$host=new VUser($meetingInfo['host_id']);
		$hostInfo=array();
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$this->mErrorCode=$host->GetErrorCode();
			$this->SetErrorMsg($host->GetErrorMsg());
			return $this->GetErrorCode();
		}
		
		if ($hostingServerUrl=='') {
			
			if ($meetingInfo['status']=='REC' && $meetingInfo['storageserver_id']!='0') {
				// for a recording, the data could be stored on the storage server			
				$storageServer=new VStorageServer($meetingInfo['storageserver_id']);
				$serverInfo=array();
				if ($storageServer->Get($serverInfo)!=ERR_NONE) {
					$this->mErrorCode=$storageServer->GetErrorCode();
					$this->SetErrorMsg($storageServer->GetErrorMsg());
					return $this->GetErrorCode();
				}	
				$php_ext='php';
				$serverUrl=VWebServer::AddSlash($serverInfo['url']);
				$serverInfo['php_ext']='php';
		
				$serverInfo['def_page']='index.htm';
				$serverInfo['file_perm']='777';
				$serverInfo['login']='host';
				$serverInfo['password']=$serverInfo['access_code'];
				$errMsg=VMeeting::CreateServerDir($serverInfo, $hostInfo, $meetingInfo);
				if ($errMsg!='')
					return $errMsg;

			} else {
				
				if ($meetingInfo['webserver_id']!='0')
					$webServerId=$meetingInfo['webserver_id'];
				else
					$webServerId=VUser::GetWebServerId($hostInfo);		
			
				if ($webServerId<=0) {
					$this->mErrorCode=ERR_ERROR;
					$this->SetErrorMsg("webserver_id not set");
					return $this->mErrorCode;
				}
				$webServer=new VWebServer($webServerId);
				$serverInfo=array();
				if ($webServer->Get($serverInfo)!=ERR_NONE) {
					$this->mErrorCode=$webServer->GetErrorCode();
					$this->SetErrorMsg($webServer->GetErrorMsg());
					return $this->GetErrorCode();
				}	
				$php_ext=$serverInfo['php_ext'];
				$serverUrl=VWebServer::AddSlash($serverInfo['url']);
			}
		} else {
			
			// just use the hosting server url passed in.
			// this is called from WriteSessionCache, which needs to use a php variable for the url
			$php_ext="php";
			$serverUrl=$hostingServerUrl;
			$create=false;	// do not try to access the url because it is just a symbolic value
		}
		
/*
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);
		$meetingDirUrl=$serverUrl.$meetingDir;		
		$url=$meetingDirUrl.VM_VIEWER.".".$php_ext;
		if ($isHost && $meetingInfo['status']!='REC') {
			$url.="?host_id=".$meetingInfo['host_id'];
			$url.="&meeting_id=".$meetingInfo['access_id'];
		}
*/
		$url=$serverUrl.VM_VIEWER.".".$php_ext;
		$url.="?meeting_id=".$meetingInfo['access_id'];
		if ($isHost && $meetingInfo['status']!='REC') {
			$url.="&host_id=".$meetingInfo['host_id'];
		}
		
		if (defined("ENABLE_CACHING_SERVERS") && ENABLE_CACHING_SERVERS=='1') {
			// if this is a live meeting and slave servers are used, tell the viewer to check for connections to each server
			if ($meetingInfo['status']!='REC' && isset($serverInfo) && $serverInfo['slave_ids']!='' && $serverInfo['max_connections']>=0) {
				$url.="&check=1";			
			}
		}
		
		if ($create && $meetingInfo['status']!='REC') {
			
			// the meeting was created on a different web server
			// update this web server
			$doUpdate=($webServerId!=$meetingInfo['webserver_id']);
			$errMsg=VMeeting::CreateUrl($serverInfo, $hostInfo, $meetingInfo, $doUpdate);
			if ($errMsg!='') {
				$this->mErrorCode=ERR_ERROR;
				$this->SetErrorMsg($errMsg);
				return $this->mErrorCode;
			}
		}
			
		return $this->GetErrorCode();
	}

	/**
	* @static
	* @return string
	*/		
	static function GetSharingXML($hostInfo, $meetingInfo, $version, $minVersion, $downloadUrl, $hostingServerUrl='')
	{
		require_once("dbobjects/vremoteserver.php");
		require_once("dbobjects/vtoken.php");
		require_once("dbobjects/vbrand.php");
		
		$brandId=$meetingInfo['brand_id'];
		$brand=new VBrand($brandId);
		$brand->GetValue('name', $brandName);
		
		$xml="<vshowsc \n";
		
		$xml.="version=\"".$version."\"\n";
		$xml.="minVersion=\"".$minVersion."\"\n";
		$xml.="downloadUrl=\"".$downloadUrl."\"\n";
//		if ($brandName!='')
//			$xml.="windowTitle=\"".$brandName."\" ";
		$xml.="windowTitle=\"".VObject::StrToXML($meetingInfo['title'])."\"\n";
		$xml.="meetingTitle=\"".VObject::StrToXML($meetingInfo['title'])."\"\n";
		$xml.="meetingId=\"".$meetingInfo['access_id']."\"\n";
		
//		$webServerId=VUser::GetWebServerId($hostInfo);
		if ($meetingInfo['webserver_id']!=0)
			$webServerId=$meetingInfo['webserver_id'];
		else
			$webServerId=VUser::GetWebServerId($hostInfo);
		
		if ($webServerId<=0) {
			return ("webserver_id not set");
		}		
		$webServer=new VWebServer($webServerId);

		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			return($webServer->GetErrorMsg());
		}
		if ($hostingServerUrl!='')
			$serverUrl=$hostingServerUrl;
		else
			$serverUrl=$serverInfo['url'];

		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);		
//		$serverUrl=VWebServer::AddSlash($serverInfo['url']);
//		$meetingDirUrl=VWebServer::AddSlash($serverUrl).$meetingDir;
		$meetingDirUrl=$serverUrl.$meetingDir;
		
		$xml.="meetingDir=\"".$meetingDirUrl."\"\n";
		$evtDir=VMeeting::GetEventDir($meetingInfo);
		$xml.="sessionDir=\"".$evtDir."\"\n";
		$xml.="frameDir=\"vframes\"\n";
		$xml.="login=\"\"\n";
		$xml.="password=\"\"\n";
	
		$php_ext=$serverInfo['php_ext'];
		$url=VWebServer::GetScriptUrl('', $php_ext);
		$uploadUrl=$url."?s=".SC_UPLOADFILE;
		$xml.="uploadScript=\"".$uploadUrl."\"\n";
		$postUrl=$url."?s=".SC_POSTEVENT;
		$xml.="eventScript=\"".$postUrl."\"\n";
		$postUrl=$url."?s=".SC_VFTP;
		// use token for authentication
		$token=VToken::AddToken($brandName, $meetingInfo['access_id'], '0');
		$fileUrl=$postUrl."&id=token&code=".VToken::GetBUMToken($brandName, '0', $meetingInfo['access_id'], $token);
//		$fileUrl=$postUrl."&id=${hostInfo['access_id']}&code=".$serverInfo['password'];
//		$fileUrl=$postUrl."&id=${serverInfo['login']}&code=".$serverInfo['password'];
//		$fileUrl=$postUrl."&id=sig&code=".ComputeScriptSignature($_SERVER['REMOTE_ADDR']);
		$fileUrl=VObject::StrToXML($fileUrl);
		$xml.="fileScript=\"".$fileUrl."\"\n";
		$shareUrl=$meetingDirUrl."vscript.php?s=".SC_SHARE."&evtdir=$evtDir/";
		$shareUrl=VObject::StrToXML($shareUrl);
		$xml.="shareScript=\"".$shareUrl."\"\n";
		$xml.="docDir=\"vdocuments\"\n";
		$xml.="serverUrl=\"".$serverUrl."\"\n";
		$xml.="hostID=\"".$hostInfo['access_id']."\"\n";
		
		// always record for now because the xml is written to a cache file when a meeting is started
		// and we don't know if recording will be turned on or not at that time.
		$xml.="recording=\"true\"\n";
/*
		if ($meetingInfo['status']=='START_REC')
			$xml.="recording=\"true\"\n";
		else
			$xml.="recording=\"false\"\n";
*/

//		$xml.="libDir=\"".$hostInfo['access_id']."/vlibrary/\"\n";
		// upload to the meeting's document dir instead of the user's library dir because the upload may be done by a guest presenter
		$xml.="libDir=\"".$meetingDir.$evtDir."/vdocuments/\"\n";
		//		$xml.="keyframe=\"30\"\n";
		$xml.="keyframe=\"50\"\n";	// increase the keyframe interval
		$xml.="uploadMethod=\"POST\"\n";
//		$xml.="maxUploadSize=\"51200\"\n";	// max file size per upload
		$xml.="maxUploadSize=\"40000\"\n";	// max file size per upload
		$xml.="useZlib=\"1\"\n";	// instruct the php scripts to encode iphone screen sharing files with zlib
		
		$groupId=$hostInfo['group_id'];
		$group=new VGroup($groupId);
		$groupInfo=array();
		$group->Get($groupInfo);
		
		$remoteServerId=$groupInfo['remoteserver_id'];
		if ($remoteServerId>0) {
			$attUrl=$url."?s=".SC_GETATTENDEES.htmlspecialchars("&")."evtdir=".$evtDir."/";
			$xml.="attendeeScript=\"".$attUrl."\"\n";
			
			$remoteServer=new VRemoteServer($remoteServerId);
			$remoteInfo=array();
			$remoteServer->Get($remoteInfo);
			
			$xml.="rcServer=\"".$remoteInfo['server_url']."\"\n";
			$xml.="rcClient=\"".$remoteInfo['client_url']."\"\n";
			$xml.="rcPassword=\"".$remoteInfo['password']."\"\n";					
		}

		$xml.="/>";
		return $xml;
	}
	/**
	* @static
	*/
/*
	function GetLoadAudioUrl($userInfo, $meetingInfo, $authenticate) {
		// get the web server url
		$webServerId=$meetingInfo['webserver_id'];
		$webServer=new VWebServer($webServerId);
		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			return '';		
		}
		if ($serverInfo['url']=='') {
			return '';
		}
		
		$meetingDir=VMeeting::GetMeetingDir($userInfo, $meetingInfo);
		$serverUrl=VWebServer::AddSlash($serverInfo['url']);
		$meetingDirUrl=$serverUrl.$meetingDir;
		$php_ext=$serverInfo['php_ext'];
		$login=rawurlencode($serverInfo['login']);
		$password=$serverInfo['password'];
		
		$loadUrl=VWebServer::GetScriptUrl($meetingDirUrl, $php_ext);
		$loadUrl.="?s=".SC_GETAUDIO;
		if ($authenticate)
			$loadUrl.="&id=$login&code=$password";
			
		return $loadUrl;
	}
*/
	/**
	 * Get the recording export url
	* @static
	*/	
	static function GetExportRecUrl($userInfo, $meetingInfo, $getAudio=true, $authenticate=false) {

		if ($meetingInfo['storageserver_id']=='0') {		
			// get the web server url		
			$webServerId=$meetingInfo['webserver_id'];
			$webServer=new VWebServer($webServerId);
			$serverInfo=array();
			if ($webServer->Get($serverInfo)!=ERR_NONE) {
				return '';		
			}
			
			$serverUrl=	$serverInfo['url'];
			$login=$serverInfo['login'];
			$code=$serverInfo['password'];
				
			
		} else {
			// get the storage server url		
			$webServerId=$meetingInfo['storageserver_id'];
			$webServer=new VStorageServer($webServerId);
			$serverInfo=array();
			if ($webServer->Get($serverInfo)!=ERR_NONE) {
				return '';		
			}
			if (!isset($serverInfo['url']))
				return '';
				
			$serverUrl=	$serverInfo['url'];
			$login="host";
			$code=$serverInfo['access_code'];
		
		}
		
		$meetingDir=VMeeting::GetMeetingDir($userInfo, $meetingInfo);
		$serverUrl=VWebServer::AddSlash($serverUrl);
		$meetingDirUrl=$serverUrl.$meetingDir;
		$login=rawurlencode($login);
		
		$loadUrl=VWebServer::GetScriptUrl($meetingDirUrl, "php");
		if ($getAudio) {
			$loadUrl.="?s=".SC_GETAUDIO;
		} else {
			$loadUrl.="?s=".SC_GETREC;
		}
		$loadUrl.="&evtdir=".VMeeting::GetEventDir($meetingInfo);
		if ($authenticate)
			$loadUrl.="&id=$login&code=$code";
		
		return $loadUrl;
	}
	/**
	* @static
	* @return bool
	*/		
	static function IsMeetingStarted($meetingInfo) {
		if ($meetingInfo['status']=='STOP' || $meetingInfo['status']=='REC')
			return false;
		else
			return true;

/*
		if ($meetingInfo['status']=='START' || $meetingInfo['status']=='LOCK' || $meetingInfo['status']=='START_REC')
			return true;
		else
			return false;
*/
	}
	
	/**
	* @static
	* @return bool
	*/	
	static function IsMeetingInProgress($meetingInfo) {
		
		if (!VMeeting::IsMeetingStarted($meetingInfo))
			return false;

		if ($meetingInfo['session_id']>0) {
			$session=new VSession($meetingInfo['session_id']);
			return $session->IsInProgress();
		} else 
			return false;
/*
		$sessionInfo=array();
		$session->Get($sessionInfo);
		
		if (isset($sessionInfo['mod_time'])) {
			$lastTime=$sessionInfo['mod_time'];
			if ($lastTime=='0000-00-00 00:00:00')
				$lastTime=$sessionInfo['start_time'];
			
			list($sDate, $sTime)=explode(" ", $lastTime);
			list($sYear, $sMonth, $sDay)=explode("-", $sDate);
			list($hh, $mm, $ss)=explode(":", $sTime);
			$time2=mktime($hh, $mm, $ss, $sMonth, $sDay, $sYear);
		} else {
			$time2=0;
		}
		
		// if last modification time is less than 20 seconds old, assume the meeting is in progress
		if (time()-$time2<20)
			return true;
		else {
			return false;
		}
*/
	}	
	
	static function GetInvitationText($meetingInfo, $meetingUrl, $html=false, $brandInfo=null)
	{	
		require_once("dbobjects/vmailtemplate.php");
//		global $meetingInviteTemp, $recInviteTemp, $iphoneInviteTemp;
		global $gText;
		
		$mailInfo=array();
		$brandId=($brandInfo==null)?0:$brandInfo['id'];
		
		$iphoneAppUrl=$iphoneMeetingUrl='';
		if ($meetingInfo['status']=='REC') {
			VMailTemplate::GetMailTemplate($brandId, 'MT_INVITE_PLAY', $mailInfo);
						
		} else {
			VMailTemplate::GetMailTemplate($brandId, 'MT_INVITE', $mailInfo);
		}
					
		if (!isset($mailInfo['id'])) {
			return 'Mail template not found.';				
		}
/*		
		$deviceStr='';
		if (isset($brandInfo) && strpos($brandInfo['mobile'], "iPhone")!==false) {
			VMailTemplate::GetMailTemplate($brandId, 'INVITE_IPHONE', $iphoneTempInfo);
			
			$iphoneInviteTemp=VMailTemplate::GetBody($iphoneTempInfo, null, $brandInfo, $gText);
			$iphoneJoinText=_Text("Join the meeting from iPhone");
			$iphoneDownladText=_Text("You must install the iPhone App first");
			$iphoneInviteTemp=str_replace("MT_IPHONE_JOIN", $iphoneJoinText, $iphoneInviteTemp);
			$iphoneInviteTemp=str_replace("MT_IPHONE_APP_DOWNLOAD", $iphoneDownladText, $iphoneInviteTemp);

//			$temp.=$iphoneInviteTemp;				
			$mobileApp=$brandInfo['mobile_app'];
			$pos1=strpos($mobileApp, "iPhone=");
			if ($pos1!==false) {
				$pos1+=strlen("iPhone=");
				$appUrl=substr($mobileApp, $pos1);
				$pos2=strpos($appUrl, "\n");
				if ($pos2!==false) {
					$appUrl=substr($appUrl, 0, $pos2);
				}
				$iphoneAppUrl=$appUrl;
			} 
			// this url will launch the iPhone app directly without going through a meeting info page
			$iphoneMeetingUrl=$meetingUrl."&page=HOME_JOIN&redirect=1";
			if ($meetingInfo['login_type']=='PWD')
				$iphoneMeetingUrl.="&pass=".rawurlencode($meetingInfo['password']);
				
				
			if ($iphoneMeetingUrl!='') {
				if ($html) {
					$iphoneMeetingUrl="<a href=\"$iphoneMeetingUrl\">".$iphoneMeetingUrl."</a>";
					$iphoneAppUrl="<a href=\"$iphoneAppUrl\">".$iphoneAppUrl."</a>";
				}
				$deviceStr=str_replace("[IPHONE_DOWNLOAD_URL]", $iphoneAppUrl, $iphoneInviteTemp);
				$deviceStr=str_replace("[IPHONE_MEETING_URL]", $iphoneMeetingUrl, $deviceStr);
			}

		}
*/
		if ($html)
			$meetingUrl="<a href=\"$meetingUrl\">".$meetingUrl."</a>";
		
		$host=new VUser($meetingInfo['host_id']);
		$host->Get($hostInfo);
		$body=VMailTemplate::GetBody($mailInfo, $hostInfo, $brandInfo, $gText, $meetingInfo, null, $meetingUrl);
			
//		$body=str_replace("[DEVICE_INFO]", $deviceStr, $body);

/*		
		$password='';
		if ($meetingInfo['login_type']=='PWD')
			$password=$meetingInfo['password'];
			
		$meetingTimeStr='';
		if ($meetingInfo['status']=='REC' || $meetingInfo['scheduled']=='Y') {
			$dtime=$meetingInfo['date_time'];
			//GetLocalTimeZone($tzName, $tz);
			GetSessionTimeZone($tzName, $tz);
			VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $tzTime);
			if ($tzTime!='') {
				list($meetingDateStr, $time24Str)=explode(" ", $tzTime);
				list($year, $mon, $day)=explode("-", $meetingDateStr);
				list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
				$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
				$meetingTimeStr=H24ToH12($meetHour, $meetMin);
			}
		}

		$dateTimeStr='';
		$durStr='';
		if ($meetingInfo['status']!='REC' && $meetingInfo['scheduled']=='Y') {
			list($hh, $mm, $ss)=explode(":", $meetingInfo['duration']);
			$durStr="$hh:$mm";
			$dateStr=date('l F d, Y', $theDate);
			$dateTimeStr=$dateStr." ".$meetingTimeStr." ".$tzName;
		}
		
		$phoneStr='';
		$accCode='';
		if ($meetingInfo['status']!='REC' && $meetingInfo['tele_conf']=='Y') {
			$phoneStr=$meetingInfo['tele_num'];
			if ($meetingInfo['tele_num2']!='') {
				$phoneStr.=" Or ".$meetingInfo['tele_num2'];
			}
			if ($meetingInfo['tele_pcode']!='')
				$accCode=$meetingInfo['tele_pcode'];
		}
		
		if ($html)
			$meetingUrl="<a href=\"$meetingUrl\">".$meetingUrl."</a>";
		$body=str_replace("[MEETING_URL]", $meetingUrl, $temp);
		$body=str_replace("[PHONE_NUMBER]", $phoneStr, $body);
		$body=str_replace("[ACCESS_CODE]", $accCode, $body);
		$body=str_replace("[PASSWORD]", $password, $body);
		$body=str_replace("[DATE_TIME]", $dateTimeStr, $body);
		$body=str_replace("[DURATION]", $durStr, $body);
*/		

		
		if ($html)
			$body=str_replace("\n", "<br>", $body);
		
		return $body;
		
	/*
		$body=$gText['MD_JOIN_TEXT']."\n\n";
		if ($meetingInfo['status']=='REC')
			$body=$gText['MD_PLAY_REC_TEXT']."\n\n";
		
		$body.= $gText['MD_MEETING_URL'].":\n".$url."\n";
		
		if ($meetingInfo['status']=='REC' || $meetingInfo['scheduled']=='Y') {
			$dtime=$meetingInfo['date_time'];
			//GetLocalTimeZone($tzName, $tz);
			GetSessionTimeZone($tzName, $tz);
			VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $tzTime);
			if ($tzTime!='') {
				list($meetingDateStr, $time24Str)=explode(" ", $tzTime);
				list($year, $mon, $day)=explode("-", $meetingDateStr);
				list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
				$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
				$meetingTimeStr=H24ToH12($meetHour, $meetMin);
			}
		}

		if ($meetingInfo['login_type']=='PWD')
			$body.="Password: ".$meetingInfo['password']."\n";
			
		if ($meetingInfo['status']=='REC') {
			// recording
			$dateStr=date('l F d, Y', $theDate);
			$durStr=$meetingInfo['duration'];
			$body.=$gText['MD_RECORDED'].": ".$dateStr." ".$meetingTimeStr."\n";
			$body.=$gText['MD_DURATION'].": ".$durStr."\n";
		} else if ($meetingInfo['scheduled']=='Y') {
			list($hh, $mm, $ss)=explode(":", $meetingInfo['duration']);
			$durStr="$hh:$mm";
			$dateStr=date('l F d, Y', $theDate);
			$body.=$gText['MD_DATE_TIME'].": ".$dateStr." ".$meetingTimeStr." ".$tzName."\n";
			$body.=$gText['MD_DURATION'].": ".$durStr."\n";
		}
		
		if ($meetingInfo['status']!='REC' && $meetingInfo['tele_conf']=='Y') {
			$phoneStr=$gText['MD_TELEPHONE'].": ".$meetingInfo['tele_num'];
			if ($meetingInfo['tele_pcode']!='')
				$phoneStr.=" ".$gText['MD_PHONE_PCODE']." ".$meetingInfo['tele_pcode'];
				
			$body.=$phoneStr."\n";
		}
	*/	
		
	}

	/**
	* @static
	* @return int
	*/		
	static function NotifyRegisteredUsers($meetingInfo, $customText, &$errMsg, &$usersSent)
	{
		global $gText;
require_once("dbobjects/vmailtemplate.php");
require_once("dbobjects/vregistration.php");

		$fatalErr='-1';
		$errMsg='';
		$usersSent=0;
		
		$brand=new VBrand($meetingInfo['brand_id']);
		if ($brand->Get($brandInfo)!=ERR_NONE) {
			$errMsg=$brand->GetErrorMsg();
			return $fatalErr;
		}

		if (!isset($brandInfo['id']))
			return 1;	// shouldn't happen
			
		$host=new VUser($meetingInfo['host_id']);
		if ($host->Get($hostInfo)!=ERR_NONE) {
			$errMsg=$host->GetErrorMsg();
			return $fatalErr;
		}
			
		if (!isset($hostInfo['id']))
			return 1;	// shouldn't happen
			
		// find all registered users for the meeting and add them to the BCC list
		$query3="meeting_id='".$meetingInfo['id']."'";
		$errMsg=VObject::SelectAll(TB_REGISTRATION, $query3, $result3);
		if ($errMsg!='')
			return $fatalErr;

		$bccList=array();
		while ($regInfo = mysql_fetch_array($result3, MYSQL_ASSOC)) {
			$bccList[]=$regInfo['email'];
		}
		
		$count=count($bccList);
		if ($count==0)
			return 0;	// no one to sent to

// FIXME:			
		// For debugging only to make sure email is sent
		// should be removed in Production.
//		$bccList[]="webmaster@persony.com";
			
		// get the email template
		$mailInfo=array();	
		$errMsg=VMailTemplate::GetMailTemplate($brandInfo['id'], 'MT_REGISTER', $mailInfo);
		if ($errMsg!='')
			return $fatalErr;
		
		if (!isset($mailInfo['id']))
			return 1;	// shouldn't happen
		
		// get the custom email text from the registration form
		if ($customText!='') {
			// MT_REGISTER_INFO may be followed by : or nothing so handle both cases
			$mailInfo['body_text']=str_replace('MT_REGISTER_INFO:', $customText, $mailInfo['body_text']);
			$mailInfo['body_text']=str_replace('MT_REGISTER_INFO', $customText, $mailInfo['body_text']);
		}	
			
		$fromName=$brandInfo['from_name'];
		$from=$brandInfo['from_email'];

		$subject=$gText['MD_REGISTRATION'];
		$subject.=" (".$meetingInfo['title'].")";
		$meetingUrl=$brandInfo['site_url']."?meeting=".$meetingInfo['access_id'];		
		
		$genericInfo['name']="Registered User";
		$genericInfo['email']="Your registered email address";
		$body=VMailTemplate::GetBody($mailInfo, null, $brandInfo, $gText, $meetingInfo, $genericInfo, $meetingUrl);
		// send the email to the host
		$to=$hostInfo['email'];
		if ($to=='') {
			$to=$hostInfo['login'];
		}
		$toName=$host->GetFullName($hostInfo);	

		$errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
				'', '', '', false, $bccList, $brandInfo);							
		if ($errMsg!='') {
			return 1;
		}

		// record the time the email is sent to each registered user
		$updateInfo=array();
		$updateInfo['notice_time']='#NOW()';			
		while ($regInfo = mysql_fetch_array($result3, MYSQL_ASSOC)) {
			if (isset($regInfo['notice_time'])) {
				$reg=new VRegistration($regInfo['id']);
				if ($reg->Update($updateInfo)!=ERR_NONE)
					break;
			}
		}

		$usersSent=$count;
		return 0;
	}

}

?>
