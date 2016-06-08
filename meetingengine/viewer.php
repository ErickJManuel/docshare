<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
require_once("includes/common.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vhook.php");
require_once("dbobjects/vsession.php");

function MyErrorExit($errMsg) {
//	$errMsg=wordwrap($errMsg, 65, "<br>", 1);
	$siteUrl=GetSessionValue('brand_url');
	if ($siteUrl=='')
		$siteUrl="index.php?brand=".GetSessionValue('brand_name');
		
	$errPage="$siteUrl?page=".PG_HOME_ERROR."&".SID."&error=";
	header("Location: ".$errPage.rawurlencode($errMsg));
	
	DoExit();
}

GetArg('meeting', $meetingId);
if ($meetingId=='')
	GetArg('viewer', $meetingId);
	
if ($meetingId!='') {

	$meetingErrMsg='';
	

	$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
	if ($errMsg!='')
		MyErrorExit($errMsg);

	if (!isset($meetingInfo['id']))
		MyErrorExit("Meeting id '$meetingId' is not found.");		
		
	
	$brand=new VBrand($meetingInfo['brand_id']);
//	$brand->GetValue('hook_id', $hookId);
	$brand->Get($brandInfo);
	$hookId=$brandInfo['hook_id'];
	
	$redirectUrl='';
	$hookInfo=array();
	if ($hookId>0) {
		$hook=new VHook($hookId);
		if ($hook->Get($hookInfo)!=ERR_NONE)
			MyErrorExit($hook->GetErrorMsg());
	}

	$meeting=new VMeeting($meetingInfo['id']);
				
	$isHost=false;
	$memberName=GetSessionValue('member_name');
	$memberId=GetSessionValue('member_id');
	
	if (GetArg('start', $start) || GetArg('resume', $resume) ) {
		if ($memberId=='') {
			require_once("includes/go_signin.php");
		}
		
		$memberInfo=array();
		$member=new VUser($memberId);
		if ($member->Get($memberInfo)!=ERR_NONE)
			MyErrorExit("Member not found.");	

		if ($memberId!=$meetingInfo['host_id']) {
			MyErrorExit("You are not a moderator of the meeting.");	
		}

		$isHost=true;
		
		if ($meetingInfo['status']=='REC')
			$meetingErrMsg="Couldn't start a recording.";
		else {
			if ($start=='1' && $meetingInfo['status']=='STOP') {
				
				if (!defined("HOST_MULTI_MEETINGS") || constant("HOST_MULTI_MEETINGS")=="0") {
	
					// end all my other started meetings first, except for this meeting
					$mid=$meetingInfo['id'];
					//$memberId=GetSessionValue('member_id');
					$query="(status='START' || status='START_REC') AND id<>'$mid' AND host_id='$memberId'";
					$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
					if ($errMsg!='') {
						// ignore
					} else {
						$num_rows = mysql_num_rows($result);
						if ($num_rows>0) {
							MyErrorExit("Another meeting is currently in progress. Please stop all meetings first.");								
						}
						/* don't automatically stop in-progress meetings because it generates support requests and complaints
						// the moderator has to manually stop all in-progress or idle meetings first.
						while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			
							$aMeeting=new VMeeting($row['id']);							
							if ($aMeeting->EndMeeting()!=ERR_NONE) {
								// ignore
							}
						}
						*/
					}

				}
				require_once('api_includes/common.php');			
				
				$api_error_message='';
				$api_exit=false;
				
				require_once('api_includes/start_meeting.php');			

				if ($api_error_message!='') {
					MyErrorExit($api_error_message);
				}					

			} else {
				// resume a meeting; call 'meeting_started' hook
				if (isset($hookInfo['meeting_started']) && $hookInfo['meeting_started']!='') {
					$args=array();
					$args['member_id']=$memberInfo['access_id'];
					$args['meeting_id']=$meetingInfo['access_id'];
					$args['session_id']=$meetingInfo['session_id'];
					if ($hook->CallHook($hookInfo['meeting_started'], $args, $resp)) {
						$code='';
						if (isset($resp['code'])) {
							$code=$resp['code'];
						}								
						if ($code=='300') {
							if (isset($resp['link']) && $resp['link']!='') {
								$redirectUrl=$resp['link'];
								header("Location: $redirectUrl");
								DoExit();
							} else
								MyErrorExit("API Hook 'meeting_started' did not return a redirect link.");
						}		
					} else {
						MyErrorExit("API Hook 'meeting_started' failed to respond.");
					}
				}						
				
			}
		}
	} else {

		if ($meetingInfo['status']=='REC') {
			
			// need to call this to make sure the "viewer.php" file in the meeting folder is up to date
			// shouldn't need this for any recordings created from version 2.2.08 because the viewer.php shouldn't change
			// recordings created with the older version need to be updated.			
			$meeting->UpdateServer();
			
		} 
/* Comment this out because we want to call "join_meeting" hook for a recording too. 
		else {
*/			
			if (isset($hookInfo['join_meeting']) && $hookInfo['join_meeting']!='') {
				// attend a meeting; call 'join_meeting' hook
				$args=array();
				$args['meeting_id']=$meetingInfo['access_id'];
				$args['session_id']=$meetingInfo['session_id'];
				if ($hook->CallHook($hookInfo['join_meeting'], $args, $resp)) {
					$code='';
					if (isset($resp['code'])) {
						$code=$resp['code'];
					}
					if ($code=='400') {
						if (isset($resp['message']))
							MyErrorExit($resp['message']);
						else
							MyErrorExit("API Hook 'join_meeting' refused the request.");
					} elseif ($code=='300') {
						if (isset($resp['link']) && $resp['link']!='') {
																					
							if ($meetingInfo['status']=='STOP' && ($meetingInfo['meeting_type']=='OPEN' || $meetingInfo['meeting_type']=='PANEL')) {
								if ($meeting->StartMeeting()!=ERR_NONE)
									MyErrorExit($meeting->GetErrorMsg());
							}
							
							$redirectUrl=$resp['link'];
							header("Location: $redirectUrl");
							DoExit();
						} else
							MyErrorExit("API Hook 'join_meeting' did not return a redirect link.");
					} elseif ($code=='200') {
						
						
					} else {
						MyErrorExit("API Hook 'join_meeting' did not return a valid response.");						
					}
				} else {
					MyErrorExit("API Hook 'join_meeting' failed to respond.");				
				}
			}
			
			if ($meetingInfo['status']=='STOP' && ($meetingInfo['meeting_type']=='OPEN' || $meetingInfo['meeting_type']=='PANEL')) {
				if ($meeting->StartMeeting()!=ERR_NONE) {
					MyErrorExit($meeting->GetErrorMsg());
				}
			}
//		}
	
	}
	
	$userName='';
	if (GetArg('user', $arg))
		$userName=$arg;
	elseif ($memberName !='')
		$userName=$memberName;
	
	GetArg('pass', $password);		
	GetArg('email', $email);
	
	$iphone=IsIPhoneUser();
	$ipad=IsIPadUser();
	
	if ($iphone || $ipad) {
		require_once("iphone/common.php");
				
		if ($iphone) {
			$appProto=IPHONE_PROTO;
		} else if ($ipad) {
			$appProto=IPAD_PROTO;
		}
		if (isset($gBrandInfo['mobile_app']) && $gBrandInfo['mobile_app']!='') {
			$appInfo=array();
			GetMobileAppInfo($gBrandInfo['mobile_app'], $appInfo);
			if ($iphone) {
				if (isset($appInfo['iphone_proto']) && $appInfo['iphone_proto']!='')
					$appProto=$appInfo['iphone_proto'];
			} else {
				if (isset($appInfo['ipad_proto']) && $appInfo['ipad_proto']!='')
					$appProto=$appInfo['ipad_proto'];
			}
		}

		if ($start=='1')
			$action='start';
		else if ($resume=='1')
			$action='resume';
		else
			$action='join';
			
		if ($action=='start' || $action=='resume') {
			$hostId=$memberInfo['access_id'];
			$hostPass=md5($memberInfo['password']);
			if ($meetingInfo['login_type']=='PWD')
				$password=$meetingInfo['password'];
		} else {
			$hostId=$hostPass='';
		}

			
		$meetingUrl=GetiPhoneAppUrl($appProto, $action, $brandInfo['name'], $brandInfo['site_url'], 
				$meetingInfo['access_id'], $userName, $password, $email, 
				$hostId, $hostPass);
	} else {
		
		if ($meeting->GetViewerUrl($isHost, $meetingUrl, true)!=ERR_NONE) {
			MyErrorExit($meeting->GetErrorMsg());
		}
		
		$argStr='';
		if ($userName!='')
			$argStr.="&user=".rawurlencode($userName);
		
		if ($password!='')
			$argStr.="&pass=".rawurlencode($password);
		
		if ($email!='')
			$argStr.="&email=".rawurlencode($email);
		
		// $brandUrl is for viewer exit to return to orginating page, which may not be the site_url stored in the brand table	
		//	$brandUrl=GetSessionValue("brand_url");
		$brandUrl=$GLOBALS['BRAND_URL'];
		if ($brandUrl!='')
			$argStr.="&brand_url=".$brandUrl;
		
		if ($isHost && SID!='') {
			$sessIdStr=str_replace("=", "%3D", SID);
			$argStr.="&sid=".$sessIdStr;						
		}
		
		if ($argStr!='') {
			if (strpos($meetingUrl, "?")===false)
				$meetingUrl.="?".$argStr;
			else
				$meetingUrl.=$argStr;
		}
		
		if (isset($_GET['client_id']))
			$meetingUrl.="&client_id=".$_GET['client_id'];
		if (isset($_GET['client_code']))
			$meetingUrl.="&client_code=".$_GET['client_code'];
	}
	
	header("Location: $meetingUrl");
	DoExit();


} else {
	
	MyErrorExit("Meeting ID is not set.");		
	
}

?>