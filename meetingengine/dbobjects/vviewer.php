<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("scripts.php");
require_once("vobject.php");
require_once("vimage.php");
require_once("vbackground.php");
require_once("vvideoserver.php");
require_once("vwebserver.php");
require_once("vbrand.php");
require_once("vlicense.php");
require_once("vteleserver.php");
require_once("vhook.php");
require_once("vconversionserver.php");
require_once("vuser.php");

/**
 * @package     VShow
 * @access      public
 */
class VViewer extends VObject 
{
	/**
	* Constructor
	* @param integer set $id to non-zero to associate the object with an existing row.
	*/	
	function VViewer($id=0)
	{
		$this->VObject(TB_VIEWER);
		$this->SetRowId($id);
	}	
	
	/**
	 * Create a parameter string for the Flash viewer
	 * @param string $siteUrl: management server url
	 * @param string $brandUrl: web conferencing site where the user goes to log in or join
	 * @param string $serverUrl: hosting server url (where the live meeting is hosted)
	 * @static
	 * @return string error message
	*/		
	function GetFlashVars($siteUrl, $brandUrl, $serverUrl, $meetingInfo, $hostInfo, $groupInfo, $locale, $meetingDir, $php_ext, $isHost, $meetingLength, &$params)
	{	
		$viewerInfo=array();
		if ($this->Get($viewerInfo)!=ERR_NONE) {
			return $this->GetErrorMsg();
		}
		if ($meetingInfo['access_id']=='')
			return "access_id not set in meetingInfo";
		
		if ($hostInfo['access_id']=='')
			return "access_id not set in hostInfo";			
/*
		if ($siteUrl{strlen($siteUrl)-1}!='/')
			$siteUrl.='/';
*/					
		$hostId=$hostInfo['access_id'];
		$hostName=VUser::GetFullName($hostInfo);	
		$meetingDirUrl=VWebServer::AddPaths($serverUrl, $meetingDir);
		$localeDirUrl=VWebServer::AddPaths($siteUrl, "vlocale/");
		$roomDirUrl=VWebServer::AddPaths($serverUrl, $hostInfo['access_id']);		

		$params="BaseDir=$meetingDirUrl&RoomDir=../../";
		$params.="&MeetingDir=$meetingDirUrl";
		$params.="&ScriptFile=".SC_SCRIPT.".".$php_ext;
//		$params.="&AppVersion=$version";
		$params.="&FrameCount=10";
		$params.="&LocaleDir=$localeDirUrl";
//		$params.="&CacheFile=1";
// don't turn on media download throttling. there is a bug somewhere that causes it to not work for large flv files.
		$params.="&MediaDownloadSpeed=92160"; // in bytes per sec (90KBps; should be good for most videos)
	
		$hostUrl=$brandUrl."?page=MEETINGS_START&start=1&meeting="
			.$meetingInfo['access_id']."&title=".rawurlencode($meetingInfo['title']);
		$hostUrl=VWebServer::EncodeDelimiter1($hostUrl);
		$params.="&HostUrl=$hostUrl";
		
		$attUrl=$brandUrl."?page=HOME_JOIN&redirect=1&meeting="
			.$meetingInfo['access_id']."&title=".rawurlencode($meetingInfo['title']);
		$attUrl=VWebServer::EncodeDelimiter1($attUrl);
		$params.="&AttendeeUrl=$attUrl";

//		if ($meetingLength>0)
//			$params.="&MaxLength=$meetingLength";
		
		if ($isHost) {
//			$params.="&ShowDownloads=0&AddAttendee=0&EnableAudio=0&HideMenu=0&CanShareSlide=1";
			$params.="&ShowDownloads=0&AddAttendee=0&EnableAudio=0&CanShareSlide=1";
			$params.="&ShowPresenterControls=1&ShowDrawingTools=1";
			$params.="&MyID=".$hostId."&MyName=".rawurlencode($hostName);
			// need the member password to request a member token; md5 encryption is optional
			$params.="&MyPassword=".md5($hostInfo['password']);
			if ($meetingInfo['password']!='')
				$params.="&Password=".rawurlencode($meetingInfo['password']);
		} else if ($meetingInfo['status']=='REC') {
/*	always play back as a participant regardless the login status
// it causes too much confusion when playing back as a host because all chat messages sent to the host show up in the playback when the host is doing the playback.
			$memberId=GetSessionValue("member_access_id");
			// if the user is the host of the recorded meeting, assume the host id during the playback
			if ($memberId!='' && $memberId==$hostId) {
				$params.="&MyID=".$hostId."&MyName=".rawurlencode($hostName);
				$params.="&MyPassword=".md5($hostInfo['password']);
			}
*/
		}
		
//		$params.="&HasMenubar=0";
//		$params.="&HasWindows=0";
//		$params.="&HideWindows=1";
//		$params.="&AppProto=jnlp";
		
		if ($hostInfo['id']<=0) {
			return ('host id not set');
		}
/*		
		$siteInfo=array();
		if (($errMsg=VSite::GetSite($siteInfo))!='') {
			return ($errMsg);
		}
		if (($errMsg=VSite::GetSiteUrl($siteInfo, $siteUrl))!='') {
			return ($errMsg);
		}
*/		
		$brandId=$meetingInfo['brand_id'];
		$brand=new VBrand($brandId);
		$brandInfo=array();
		$brand->Get($brandInfo);
//		$brandUrl=$brandInfo['site_url'];
		$brandViewer=new VViewer($brandInfo['viewer_id']);
		$brandViewerInfo=array();
		$brandViewer->Get($brandViewerInfo);
		
		$theme=$brandInfo['theme'];
		// a custom style name started with "_"
		if ($theme!='' && $theme[0]=='_')
			$params.="&CustomStyle=".$theme;
		
//		$params.="&HostVcf=../../host_".$hostInfo['id'].".vcf";
		if (isset($brandUrl))
			$params.="&HostPage=".VWebServer::EncodeDelimiter1($brandUrl."?user=".$hostInfo['access_id']);
		if ($hostInfo['pict_id']>0) {
			$image=new VImage($hostInfo['pict_id']);

			$imageInfo=array();
			if ($image->Get($imageInfo)!=ERR_NONE) {
				return ($image->GetErrorMsg());
			}
//			if ($imageInfo['file_name']=='') {
//				return ('file_name not set for image '.$hostInfo['pict_id']." ".$imageInfo['title']);
//			}
//			$params.="&HostPict=../../".$imageInfo['file_name']."?".rand();

			if ($image->GetUrl($siteUrl, $imageUrl)!=ERR_NONE)
				return $image->GetErrorMsg();
			$params.="&HostPict=".$imageUrl;
		}

		$licenseId=$hostInfo['license_id'];

		$license=new VLicense($licenseId);
		$licInfo=array();
		if ($license->Get($licInfo)!=ERR_NONE) {
			return("License not found for the user.");
		}	

		if ($licInfo['video_conf']=='Y') {
		
			$videoId=$groupInfo['videoserver_id'];
			$video2Id=$groupInfo['videoserver2_id'];
			if ($videoId!='0') {
				$videoInfo=array();
				$videoServer=new VVideoServer($videoId);
				if ($videoServer->Get($videoInfo)!=ERR_NONE) {
					return $videoServer->GetErrorMsg();
				}
				$params.="&ShowVideo=2&VideoUrl=".$videoInfo['url'];
/* not done yet
				if ($video2Id!='0') {
					$videoInfo2=array();
					$videoServer2=new VVideoServer($video2Id);
					$videoServer2->Get($videoInfo2);
					$params.="&VideoUrl2=".$videoInfo2['url'];
				}
*/
				if ($videoInfo['width']>0)
					$params.="&VideoWidth=".$videoInfo['width'];
				if ($videoInfo['height']>0)
					$params.="&VideoHeight=".$videoInfo['height'];
				if ($videoInfo['bandwidth']>0) {
					$bw=round((int)$videoInfo['bandwidth']*1024/8);
					$params.="&VideoBandwidth=".$bw;
				}
				if ($videoInfo['max_wind']>0)
					$params.="&MaxVideos=".$videoInfo['max_wind'];
				
				if ($videoInfo['type']=='BOTH' || $videoInfo['type']=='AUDIO')
					$params.="&EnableVoip=true";
				
//				if ($videoInfo['type']=='VIDEO')
//					$params.="&VideoType=video";
				
				if (isset($videoInfo['audio_rate'])) {
					$ar=(int)$videoInfo['audio_rate'];
					if ($ar>0)
						$params.="&AudioRate=".$ar;
				}

			}
		}
		
		if ($licInfo['trial']=='Y') {
			$trialMessage="Trial meeting limit: ".$licInfo['max_att']." participants ".$licInfo['meeting_length']." minutes";
			$params.="&TrialMessage=".rawurlencode($trialMessage);			
		}
		
		$canRecord=true;
		if ($licInfo['btn_disabled']!='') {
			$btns=explode(",", $licInfo['btn_disabled']);
			foreach ($btns as $abtn) {
				if ($abtn=='record') {
//					$params.="&CanRecord=0";
					$canRecord=false;
				} elseif ($abtn=='whiteboard')
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
			}			
		}
		
//		if ($canRecord && $brandInfo['aconf_rec_url']!='') {
//		if ($canRecord && $meetingInfo['status']!='REC' && $brandInfo['can_record']=='Y') {
		if ($meetingInfo['status']!='REC') {
			
			$teleServerId=$groupInfo['teleserver_id'];
			if ($teleServerId!='0' && $hostInfo['use_teleserver']=='Y') {
				$teleServer=new VTeleServer($teleServerId);
				$teleInfo=array();
				$teleServer->Get($teleInfo);
				
				if ($teleInfo['can_dialout']=='Y')
					$params.="&CanDialOut=1";
				if ($canRecord && $teleInfo['can_record']=='Y')
					$params.="&CanRecord=1";
				if ($teleInfo['can_dial_host']=='Y')
					$params.="&CanDialHost=1";
				if ($teleInfo['can_hangup_all']=='Y')
					$params.="&CanHangUpAll=1";
								
				// if the record server is enabled and the meeting telephone number is the same as the user's
				// pre-assigned conference number
				if ($canRecord && $teleInfo['can_record']=='Y' && $hostInfo['conf_num']!=''
//					&& $meetingInfo['tele_num']==$hostInfo['tele_num']
//					&& $meetingInfo['tele_mcode']==$hostInfo['tele_mcode']
					)
				{
					if ($hostInfo['conf_num']!='')
						$params.="&RecPhoneNumber=".rawurlencode($hostInfo['conf_num']);
					else
						$params.="&RecPhoneNumber=".rawurlencode($hostInfo['conf_num2']);
					
					if ($isHost)
						$params.="&RecPhoneMCode=".rawurlencode($hostInfo['conf_mcode']);
					$params.="&RecPhonePCode=".rawurlencode($hostInfo['conf_pcode']);
				}
				
				// if the teleserver is controllable and the meeting tele number is same as the one pre-assigned to the user
				if ($teleInfo['can_control']=='Y' && $meetingInfo['tele_num']!='' && $meetingInfo['tele_num']==$hostInfo['conf_num'])
				{
					$teleServerUrl=$teleInfo['server_url'];
					$params.="&TeleControl=1";
//					$params.="&TeleServerId=".$teleServerId;
//					$params.="&TeleServerUrl=".$teleServerUrl;
//					$params.="&TeleServerKey=".$teleInfo['access_key'];
				} else {
					$params.="&TeleControl=0";	
				}
			}
		} else {
			
			// set TeleControl to 1 so the playback will show the phone callers and their call status
			if ($meetingInfo['tele_num']!='' && $meetingInfo['audio_rec_id']!='')
				$params.="&TeleControl=1";
			
		}
		
		if (!$isHost) {
			$hookId=$brandInfo['hook_id'];		
			if ($hookId>0) {
				$hookInfo=array();
				$hook=new VHook($hookId);
				$hook->Get($hookInfo);
				if (isset($hookInfo['login_meeting']) && $hookInfo['login_meeting']!='') {
					$params.="&LoginUrl=".rawurlencode($hookInfo['login_meeting']);
				}
			}
		}
//		$params.="&PolicyUrl=".rawurlencode($brandUrl."crossdomain.xml");
		$params.="&PolicyUrl=".$brandInfo['site_url']."crossdomain.xml";
		
//		$params.="&LoginType=".$meetingInfo['login_type'];
/*	
// Don't request the token here because the token may expire if the user resumes a meeting after a long delay
// Tequest a new token whenever the Flash viewer starts
		if ($isHost) {
			require_once("vtoken.php");

			// create a token to be used for API authentication
			$token=VToken::AddToken($brandInfo['name'], $meetingInfo['access_id'], $hostInfo['access_id'], $hostInfo);	
			$params.="&Token=".$token;
		}
*/		
		if ($licInfo['meeting_length']!=0) {
			$sec=((integer)$licInfo['meeting_length'])*60;
			$params.="&MaxLength=".$sec;
		}
			
		$logoId=$viewerInfo['logo_id'];
		if ($logoId==0)
			$logoId=$brandViewerInfo['logo_id'];
		if ($logoId==0)
			$logoId=1;
		if ($logoId>0) {
			$image1=new VImage($logoId);
/*
			$image1Info=array();
			if ($image1->Get($image1Info)!=ERR_NONE) {
				return ($image1->GetErrorMsg());
			}
*/
//			if ($image1Info['file_name']=='') {
//				return ('file_name not set for image '.$viewerInfo['logo_id']." ".$image1Info['title']);
//			}
//			$params.="&LogoFile=".$image1Info['file_name']."?".rand();

			if ($image1->GetUrl($siteUrl, $imageUrl)!=ERR_NONE)
				return $image1->GetErrorMsg();
			$params.="&LogoFile=".$imageUrl;

		}
		if ($viewerInfo['back_id']>0) {
//			$back=new VBackground($viewerInfo['back_id']);
//			$params.="&Background=background_".$viewerInfo['back_id'].".xml?".rand();
			
			$apiUrl="api.php?cmd=GET_VIEWER_BACKGROUND&meeting_id=".$meetingInfo['access_id'];
			$backgroundUrl=VWebServer::AddPaths($siteUrl, $apiUrl);
			$params.="&Background=".VWebServer::EncodeDelimiter1($backgroundUrl);
		}
/*		
		
		$musicId=$viewerInfo['waitmusic_id'];
		
		if (!$isHost && $musicId>0){
			$media1=new VMedia($musicId);
			$media1Info=array();
			if ($media1->Get($media1Info)!=ERR_NONE) {
				return ($media1->GetErrorMsg());
			}
			if ($media1->GetUrl($siteUrl, $mediaUrl)!=ERR_NONE)
				return $media1->GetErrorMsg();
			$params.="&WaitMusic=".$mediaUrl."?".rand();
		}
*/
		if (!$isHost && $viewerInfo['waitmusic_url']!='') {
			$params.="&WaitMusic=".$viewerInfo['waitmusic_url'];			
		}
			
		if ($locale!='')
			$params.="&DefLocale=".$locale;
			
			
//		$params.="&AboutUrl=".VWebServer::EncodeDelimiter1($brandUrl);
//		if ($brandInfo['custom_help']=='Y')
//			$helpUrl=$brandUrl."?page=HELP";
//		else
			$helpUrl=$brandUrl."?page=HELP";	
		$params.="&HelpUrl=".VWebServer::EncodeDelimiter1($helpUrl);	
		$invitePage=$brandUrl."?page=INVITE&meeting=".$meetingInfo['access_id'];
		$params.="&InvitePage=".VWebServer::EncodeDelimiter1($invitePage);	
		$libPage=$brandUrl."?page=LIBRARY&hidetabs=1";
		
		$convId=$groupInfo['conversionserver_id'];
		// use the default conversion server. Assume the default server is defined in row 1 of the DB conversionserver table
		if ($convId=='0')
			$convId='1';
		
		$convServer=new VConversionServer($convId);
		$convServer->Get($convInfo);
		$convUrl='';
		if (isset($convInfo['url'])) 
			$convUrl=$convInfo['url'];
			
		if ($convUrl!='') {
			$args="user_id=".$hostInfo['access_id'];
			$args.="&server_url=".$siteUrl;
			if ($isHost) {
				// upload to the host's library folder
				$args.="&lib_url=".$brandUrl;
				$args.="&lib_path=".$hostInfo['access_id']."/vlibrary/";
			} else {
				// upload to the site's cache folder
				$args.="&lib_url=".$serverUrl;
				$args.="&lib_path=cache/";
			}
			$args.="&user_pass=".md5($hostInfo['password']);
			$args.="&brand=".$brandInfo['name'];
			$args.="&locale=".$brandInfo['locale'];
			$args.="&css_url=".$siteUrl."themes/".$brandInfo['theme'];		
			
			$sig=md5(SITE_URL.$hostInfo['access_id'].$convInfo['access_key']);
			$converterPage=$convUrl."?".$args."&signature=".$sig;
		}
		
//		$converterPage=$brandUrl."?page=LIBRARY_CONVERTER&hidetabs=1";
		$params.="&LibPage=".VWebServer::EncodeDelimiter1($libPage);	
//		$params.="&ConverterPage=".VWebServer::EncodeDelimiter1($converterPage);	
		$quesionPage=$brandUrl."?page=LIBRARY_QUESTION&hidetabs=1";
		$params.="&QuestionPage=".VWebServer::EncodeDelimiter1($quesionPage);			
		$testPage=$brandUrl."?page=TEST&meeting=".$meetingInfo['access_id'];
		$params.="&TestPage=".VWebServer::EncodeDelimiter1($testPage);	
		$params.="&BrandID=".$brandId;
		$params.="&BrandName=".$brandInfo['name'];
		$downloadUrl=$brandUrl."?page=DOWNLOAD";	
		$params.="&DownloadUrl=".$downloadUrl;
//		$params.="&SessionDir=".VMeeting::GetEventDir($meetingInfo);

/*				
		$endUrl=SITE_URL."exit_meeting.php?meeting=".$meetingInfo['access_id'];
		if ($isHost && $hostInfo['login']!=VUSER_GUEST)
			$endUrl.='&host='.$hostInfo['access_id'];
*/		
		if ($isHost && $hostInfo['login']!=VUSER_GUEST) {
			$endUrl=$brandUrl."?page=MEETINGS";
		} else {

			$endUrl=$viewerInfo['end_url'];
			if ($endUrl=='')
				$endUrl=$brandViewerInfo['end_url'];
			if ($endUrl=='') {
				$endUrl=$brandUrl."?meeting=".$meetingInfo['access_id']."&post_comment=1";
			}						
		}
	
		$params.="&EndPage=".VWebServer::EncodeDelimiter1($endUrl);
		
		if ($viewerInfo['att_snd']=='Y')
			$params.="&AlertSound=1";
		else
			$params.="&AlertSound=0";
		
/*	
		if ($viewerInfo['att_snd']=='Y')
			$params.="&AttendSoundName=default";
		if ($viewerInfo['msg_snd']=='Y')
			$params.="&MessageSoundName=default";
		if ($viewerInfo['hand_snd']=='Y')
			$params.="&HandSoundName=default";
*/			
		if ($isHost || $viewerInfo['see_all']=='Y' || $meetingInfo['meeting_type']=='PANEL')
			$params.="&CanSeeAll=1";
		if ($isHost || $viewerInfo['send_all']=='Y')
			$params.="&CanSendAll=1";
			
		if ($meetingInfo['status']=='REC')
			$params.="&HideWindows=1";
		
		$winClient=true;
		if (isset($brandViewerInfo['presenter_client']) && $brandViewerInfo['presenter_client']=='JAVA')
			$winClient=false;
		if (isset($viewerInfo['presenter_client']) && $viewerInfo['presenter_client']=='JAVA')
			$winClient=false;
		// disable Windows Presenter Client
		if ((defined('ENABLE_WINDOWS_CLIENT') && constant('ENABLE_WINDOWS_CLIENT')=='0') || !$winClient) 
		{
			$params.="&WindowsClient=0";
		}
/*		
		if (isset($GET_VARS['video']) && $GET_VARS['video']=='1')
			$params.="&ShowVideo=1";
		if (isset($GET_VARS['media']) && $GET_VARS['media']=='1')
			$params.="&CanShareMedia=1";
*/		
		return '';
	}

	/**
	* @static
	* @param array $viewerInfo
	* @param VWebServer web server
	* @return string error message
	*/	
/*	
	function UploadBackground($viewerInfo, $server, $remoteDir)
	{		
		if ($viewerInfo['back_id']>0) {
			$back=new VBackground($viewerInfo['back_id']);
			$backInfo=array();
			if ($back->Get($backInfo)!=ERR_NONE) {
				return ($back->GetErrorMsg());
			}
			
			$onpictId=$backInfo['onpict_id'];
			if ($onpictId>0) {
				$onpict=new VImage($onpictId);
				//$onpict->GetValue("file_name", $onpictFile);
				$onpictInfo=array();
				if ($onpict->Get($onpictInfo)!=ERR_NONE) {
					return ($onpict->GetErrorMsg());
				}				
				$dir="./".DIR_IMAGE;
				$onpictFile=$onpictInfo['file_name'];
				if ($server->UploadFile($dir.$onpictFile, $remoteDir.$onpictFile, $response)!=ERR_NONE)
				{
					return $server->GetErrorMsg();
				}
			}
			$offpictId=$backInfo['offpict_id'];
			if ($offpictId>0) {
				$offpict=new VImage($offpictId);
				//$onpict->GetValue("file_name", $onpictFile);
				$offpictInfo=array();
				if ($offpict->Get($offpictInfo)!=ERR_NONE) {
					return ($offpict->GetErrorMsg());
				}				
				$dir="./".DIR_IMAGE;
				$offpictFile=$offpictInfo['file_name'];
				if ($server->UploadFile($dir.$offpictFile, $remoteDir.$offpictFile, $response)!=ERR_NONE)
				{
					return $server->GetErrorMsg();
				}
			}
			
			$remoteFile="background_".$viewerInfo['back_id'].".xml";
			$xml=XML_HEADER."\n";
			$xml.=VBackground::GetXML($backInfo);
			if ($server->UploadData($xml, strlen($xml), $remoteDir.$remoteFile, $response)!=ERR_NONE)
			{
				return $server->GetErrorMsg();
			}
					
		}
		return '';
	}
*/
	/**
	* @param VWebServer web server
	* @param array $hostInfo
	* @return integer error code
	*/	
/*
	function UpdateServer($webServer, $hostInfo)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$viewerInfo=array();
		if ($this->Get($viewerInfo)!=ERR_NONE) {
			return $this->GetErrorCode();
		}

		// use the user's locale if defined.
		$locale=$hostInfo['locale'];
		// otherwise, uset the group's locale
		if ($locale=='')
			$locale=$groupInfo['locale'];
		
		$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);	
		$meetingDirUrl=VWebServer::AddSlash($serverInfo['url']).$meetingDir;
		
		if (($errMsg=$viewer->GetFlashVars($siteUrl, $meetingInfo, $hostInfo, $locale, $meetingDirUrl, $php_ext, false, $viewParams))!='')
		{
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}
		
		$fileName=$meetingDir.VM_VIEWER_FILE;
		if ($webServer->UploadData($viewParams, strlen($viewParams), $fileName, $response)
				!=ERR_NONE)
		{
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($webServer->GetErrorMsg());
			return $this->mErrorCode;
		}
		
		$imageDir="./".DIR_IMAGE;
		$hostDir=$hostInfo['access_id']."/";
		if (($errMsg=$this->UploadBackground($viewerInfo, $webServer, $hostDir))!='')
		{
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}
		
		// upload host vcf file
		
		
		// upload host picture
		if ($hostInfo['pict_id']>0) {
			$pict=new VImage($hostInfo['pict_id']);
			if ($pict->GetValue('file_name', $pictFile)!=ERR_NONE) {
				return ($pict->GetErrorMsg());
			}
			if ($pictFile=='') {
				return "Missing host picture";
			}
			if ($webServer->UploadFile($imageDir.$pictFile, $hostDir.$pictFile, $response)!=ERR_NONE)
			{
				$this->mErrorCode=$webServer->GetErrorCode();
				$this->SetErrorMsg($webServer->GetErrorMsg());
				return $this->mErrorCode;
			}					
		}
		
		// upload viewer logo
		if ($viewerInfo['logo_id']>0) {
			$logo=new VImage($viewerInfo['logo_id']);
			if ($logo->GetValue('file_name', $logoFile)!=ERR_NONE) {
				return ($logo->GetErrorMsg());
			}
			if ($logoFile!='') {		
				if ($webServer->UploadFile($imageDir.$logoFile, $hostDir.$logoFile, $response)!=ERR_NONE)
				{
					$this->mErrorCode=$webServer->GetErrorCode();
					$this->SetErrorMsg($webServer->GetErrorMsg());
					return $this->mErrorCode;
				}	
			}			
		}
			
		return $this->mErrorCode;
			
	}
*/

}
?>