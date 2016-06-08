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
require_once("dbobjects/vuser.php");

if (GetSessionValue('member_brand')!=$gBrandInfo['id'] || GetSessionValue('member_login')==VUSER_GUEST) {
	require_once("includes/go_signin.php");
}

if (isset($_GET['page']) && $_GET['page']==PG_MEETINGS_START) {
				
	if (IsIPhoneUser()) {
		if (strpos($gBrandInfo['mobile'], "iPhone")===false) {
			$errMsg="This site is not enabled to host a meeting on the iPhone.";			
		} else {
			$errMsg="Please start the meeting from the iPhone optimized site.";						
		}
		$errPage=SITE_URL."?page=".PG_HOME_ERROR."&".SID."&error=";
		header("Location: ".$errPage.rawurlencode($errMsg));
		DoExit();
	} 
	include_once("viewer.php");
	DoExit();
}
// capture the output in case the locale file contains \n or spaces.
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");
require_once("dbobjects/vmeeting.php");

$canRecord=GetSessionValue('can_record');
$hasRegist=GetSessionValue('has_regist');

$GLOBALS['TAB']=PG_MEETINGS;
$GLOBALS['SUB_MENUS']=array();
$GLOBALS['SUB_MENUS'][PG_MEETINGS_LIST]= $gText['M_MEETINGS'];
if ($canRecord!='N')
	$GLOBALS['SUB_MENUS'][PG_MEETINGS_RECORDINGS]= $gText['HOME_RECORDINGS'];

$GLOBALS['SUB_MENUS'][PG_MEETINGS_ROOM]= $gText['MEETINGS_ROOM'];
$GLOBALS['SUB_MENUS'][PG_MEETINGS_VIEWER]= $gText['MEETINGS_VIEWER'];
$GLOBALS['SUB_MENUS'][PG_MEETINGS_REPORT]= $gText['M_REPORTS'];
if ($hasRegist!='N')
	$GLOBALS['SUB_MENUS'][PG_MEETINGS_REGIST]= $gText['MEETINGS_REGIST'];
$GLOBALS['SUB_MENUS'][PG_MEETINGS_COMMENT]= $gText['M_COMMENTS'];



if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';
	
 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/meetings_right.php'; }

?>


<?php

include_once('includes/header.php');
include_once('includes/content-top.php');


if (GetArg('meeting', $accessId) && $accessId!='') {
	$meetingInfo=array();
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $accessId, $meetingInfo);
	if ($errMsg!='')
		ShowError($errMsg);
	else if (!isset($meetingInfo['id']))
		ShowError("Meeting id not found.");
	else {
		$meeting=new VMeeting($meetingInfo['id']);
	}
}


$pageTitle='';
if ($GLOBALS['SUB_PAGE']=="") {
//	$pageTitle=$gText['MEETINGS_TAB'];

} else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_DETAIL) {
	if (isset($meetingInfo['title'])) {
		$pageTitle=htmlspecialchars($meetingInfo['title']);
	}
} else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_ADD) {
	$pageTitle=$gText['M_ADD_MEETING'];
	
} else {
/*
	$arr=$GLOBALS['SUB_MENUS'];
	foreach ($arr as  $pageId => $pageTitle) {
		if ($pageId==$GLOBALS['SUB_PAGE']) {
			$pageTitle=htmlspecialchars($pageTitle);
			break;
		}
	}
*/	
}

?>

<div class="heading1">
	<?php echo $pageTitle?>
</div>
<?php
if ($GLOBALS['SUB_PAGE']=="" || $GLOBALS['SUB_PAGE']==PG_MEETINGS)
	include_once("includes/mymeetings_home.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_LIST)
	include_once("includes/mymeetings_list.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_RECORDINGS)
	include_once("includes/mymeetings_recording.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_ADD)
	include_once("includes/mymeetings_detail.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_ROOM)
	include_once("includes/mymeetings_room.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_VIEWER)
	include_once("includes/mymeetings_viewer.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_REGIST) 
	include_once("includes/mymeetings_regist.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_DETAIL) 
	include_once("includes/mymeetings_detail.php");
//else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_START)
//	include_once("includes/mymeetings_start.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_ATTENDEE)
	include_once("includes/mymeetings_attendee.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_REGFORM)
	include_once("includes/mymeetings_regform.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_REPORT)
	include_once("includes/mymeetings_report.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_COMMENT)
	include_once("includes/mymeetings_comment.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_TRANSCRIPT)
	include_once("includes/mymeetings_transcript.php");
else if ($GLOBALS['SUB_PAGE']==PG_MEETINGS_POLL)
	include_once("includes/mymeetings_poll.php");
else
	include_once("includes/not_found.php");	

include_once('includes/content-bottom.php'); 
include_once('includes/footer.php');

?>
