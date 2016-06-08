<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
require_once("includes/common.php");
require_once("includes/brand.php");


//$thisPage=$_SERVER['SCRIPT_NAME'];
//$thisPage=$GLOBALS['BRAND_URL'];

$iphone=IsIPhoneUser();
$ipad=IsIPadUser();

// if the user trying to join a meeting or view a meeting page
// and the user is on an iphone and the site is enabled for the iphone
if (isset($_GET['meeting']) && (isset($_GET['page']) && $_GET['page']==PG_HOME_JOIN) &&
	($iphone || $ipad) && isset($gBrandInfo['mobile']) && strpos($gBrandInfo['mobile'], "iPhone")!==false) {	
	// the user is to join a meeting on an iphone
	// launch the iphone app to join 
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

	
	// redirect to the iphone meeting url
	$password='';
	if (isset($_GET['pass']))
		$password=$_GET['pass'];

	$email='';
	if (isset($_GET['email']))
		$email=$_GET['email'];
		
	$userName=isset($_GET['user'])?$_GET['user']:"";

	$iphoneUrl=GetiPhoneAppUrl($appProto, 'join', $GLOBALS['BRAND_NAME'], $GLOBALS['BRAND_URL'],
		$_GET['meeting'], $userName, $password, $email);
	header("Location: $iphoneUrl");
	exit();

}

// Allow a user to join a meeting even if the database is down.
// If the user is going to the meeting page but the database is down and the meeting is in progress,
// redirect the user to the meeting viewer page from data stored in the meeting cache file.
if (isset($_GET['meeting']) &&
	(!isset($_GET['page']) || $_GET['page']==PG_HOME_JOIN)) {
require_once("dbobjects/vobject.php");
require_once("dbobjects/vmeeting.php");
	
	// verify the database is down
	if (!VObject::CanOpenDB()) {
		$meetingId=$_GET['meeting'];
		$meetingFile=VMeeting::GetSessionCachePath($meetingId);
		if (VMeeting::IsSessionCacheValid($meetingFile)) {
			@include_once($meetingFile);
			
			// check if the meeting is in progress
			// $_attUrl and $_meetingStatus are stored in the $meetingFile
//			if (isset($_attUrl) && isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
			if (isset($_attUrl) && isset($_meetingStatus) && $_meetingStatus!='STOP') {
				
				if (isset($_joinMeetingHook) && $_joinMeetingHook!='') {
require_once("dbobjects/vhook.php");
					// attend a meeting; call 'join_meeting' hook
					$args=array();
					$args['meeting_id']=$meetingId;
					$args['session_id']=$_sessionId;
					if (VHook::CallHook($_joinMeetingHook, $args, $resp)) {
						$code='';
						if (isset($resp['code'])) {
							$code=$resp['code'];
						}
						if ($code=='400') {
							if (isset($resp['message']))
								ShowError($resp['message']);
							else
								ShowError("API Hook 'join_meeting' refused the request.");
							DoExit();
						} elseif ($code=='300') {
							if (isset($resp['link']) && $resp['link']!='') {
								$redirectUrl=$resp['link'];
								header("Location: $redirectUrl");
								exit();
							} else {
								ShowError("API Hook 'join_meeting' did not return a redirect link.");
							}
							DoExit();
						} elseif ($code!='200') {
							ShowError("API Hook 'join_meeting' did not return a valid response.");
							DoExit();							
						}	
					} else {
						ShowError("API Hook 'join_meeting' did not respond.");
						DoExit();						
					}
				}				
							
				header("Location: $_attUrl");
				exit();
			}
		}
	}
}

if ((isset($_GET['page']) && $_GET['page']==PG_HOME_JOIN) ||
	(!isset($_GET['page']) && isset($_GET['viewer']))) {
	include_once("viewer.php");
	DoExit();
}

if (strpos($GLOBALS['MAIN_TABS'], PG_HOME)===false) {

	// home page is disabled, redirect to the meetings page
	if (!isset($_GET['page']) && !isset($_GET['meeting']) && !isset($_GET['user']) && !isset($_GET['room'])) {
		$url='meetings.php?'.$_SERVER['QUERY_STRING'];
		header("Location: $url");
		DoExit();
	}
}

// capture the output in case the locale file contains \n or spaces.
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

if ((defined('ENABLE_RECORDING_TAB') && constant('ENABLE_RECORDING_TAB')=='0'))
{
	$enableRecording=false;
} else {
	$enableRecording=true;
}

$GLOBALS['PAGE_TITLE']=$gText['HOME_TAB'];
$GLOBALS['TAB']=PG_HOME;

if (strpos($GLOBALS['MAIN_TABS'], PG_HOME)!==false) {
	if ($enableRecording) {
		$GLOBALS['SUB_MENUS']=array(
				PG_HOME_MEETINGS => $gText['HOME_MEETINGS'],
				PG_HOME_RECORDINGS => $gText['HOME_RECORDINGS'],
				PG_HOME_ROOMS => $gText['HOME_ROOMS'],
				);
	} else {
		$GLOBALS['SUB_MENUS']=array(
				PG_HOME_MEETINGS => $gText['HOME_MEETINGS'],
				PG_HOME_ROOMS => $gText['HOME_ROOMS'],
				);
	}
} else {
	$GLOBALS['SUB_MENUS']=array();
}

if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else {
	$GLOBALS['SUB_PAGE']='';
}


// will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'on';
if ($GLOBALS['SUB_PAGE']==PG_HOME_INVITE || $GLOBALS['SUB_PAGE']==PG_HOME_VERSIONS)
	$GLOBALS['SIDE_NAV'] = 'off';

// hide top nav tabs?

if ((isset($_GET['meeting']) && $GLOBALS['SUB_PAGE']=='')) {
	$GLOBALS['HIDE_TABS'] = 'on';
	$GLOBALS['SIDE_NAV'] = 'off';	
	$GLOBALS['HIDE_SIGNIN']='on';
	$GLOBALS['SUB_PAGE']=PG_HOME_MEETING;
} else if (isset($_GET['user']) && $GLOBALS['SUB_PAGE']=='') {
	$GLOBALS['HIDE_TABS'] = 'on';
	$GLOBALS['SIDE_NAV'] = 'off';	
	$GLOBALS['HIDE_SIGNIN']='on';
	$GLOBALS['SUB_PAGE']=PG_HOME_USER;
} else if (isset($_GET['room']) && $GLOBALS['SUB_PAGE']=='') {
	$GLOBALS['HIDE_TABS'] = 'on';
	$GLOBALS['SIDE_NAV'] = 'on';	
	$GLOBALS['HIDE_SIGNIN']='on';
	$GLOBALS['SUB_PAGE']=PG_HOME_ROOM;
} else if ( $GLOBALS['SUB_PAGE']==PG_INVITE || 
	$GLOBALS['SUB_PAGE']==PG_DOWNLOAD) 
{
	$GLOBALS['HIDE_TABS'] = 'on';
	$GLOBALS['HIDE_NAV'] = 'on';
	$GLOBALS['HIDE_SIGNIN']='on';
	$GLOBALS['SIDE_NAV'] = 'off';	
	
} else if ($GLOBALS['SUB_PAGE']==PG_REGISTER) 
{
	$GLOBALS['HIDE_TABS'] = 'on';
	$GLOBALS['HIDE_SIGNIN']='on';
	$GLOBALS['SIDE_NAV'] = 'off';	
}

if (GetArg('hidetabs', $arg) && $arg==1) {
	$GLOBALS['HIDE_NAV']='on';
	$GLOBALS['HIDE_TABS']='on';
	$GLOBALS['SIDE_NAV'] = 'off';	
}


if ($GLOBALS['SUB_PAGE']==PG_HOME)
	$GLOBALS['SUB_PAGE']=PG_HOME_MEETINGS;	


 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/home_right.php'; }



?>


<?php 
include_once('includes/header.php'); 

include_once('includes/content-top.php'); 

if ($GLOBALS['SUB_PAGE']==PG_HOME_MEETINGS || $GLOBALS['SUB_PAGE']==PG_HOME || $GLOBALS['SUB_PAGE']=='')
	include_once("includes/home_listing.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_RECORDINGS)
	include_once("includes/home_listing.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_MEETING)
	include_once("includes/home_meeting.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_USER)
	include_once("includes/home_user.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_REGISTER || $GLOBALS['SUB_PAGE']==PG_REGISTER)
	include_once("includes/home_register.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_ROOM)
	include_once("includes/home_room.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_ERROR)
	include_once("includes/error.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_INFORM)
	include_once("includes/inform.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_ROOMS)
	include_once("includes/home_room_list.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_ROOM)
	include_once("includes/home_room.php");
//else if ($GLOBALS['SUB_PAGE']==PG_HOME_SIGNIN)
//	include_once("includes/home_signin.php");
else if (strpos($GLOBALS['SUB_PAGE'], "HOME_FOOTER")!==false)
	include_once("includes/home_footer.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_DOWNLOAD || $GLOBALS['SUB_PAGE']==PG_DOWNLOAD)
	include_once("includes/home_download.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_INVITE || $GLOBALS['SUB_PAGE']==PG_INVITE)
	include_once("includes/home_invite.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_VINE)
	include_once("includes/home_vine.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_ARD)
	include_once("includes/home_ard.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_TVNC)
	include_once("includes/home_tvnc.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_FSP)
	include_once("includes/home_fsp.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_VERSIONS)
	include_once("includes/home_versions.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_COOKIES)
	include_once("includes/home_cookies.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_VIDEO)
	include_once("includes/home_video.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_JAVA)
	include_once("includes/home_java.php");
else if ($GLOBALS['SUB_PAGE']==PG_HOME_TEST)
	include_once("includes/account_test.php");
else
	include_once("includes/not_found.php");

include_once('includes/content-bottom.php'); 

include_once('includes/footer.php');
?>
