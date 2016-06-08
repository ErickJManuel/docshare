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


require_once("dbobjects/vcomment.php");
require_once("dbobjects/vuser.php");

if ($cmd=='ADD_COMMENT') {
	require_once("dbobjects/vmailtemplate.php");
	require_once("dbobjects/vmeeting.php");
	require_once("includes/common_text.php");
/*	
	require_once("includes/ClassMathGuard.php");
	if (!MathGuard :: checkResult($_REQUEST['mathguard_answer'], $_REQUEST['mathguard_code'])) {
		API_EXIT(API_ERR, "You have entered an incorrect answer to the security question. Please return to the previous page to respond again.", "", false);
	}
*/
	if (!checkSecurityAnswer($_REQUEST['security_answer'], $_REQUEST['security_code'])) {
		API_EXIT(API_ERR, "You have entered an incorrect answer to the security question. Please return to the previous page to respond again.", "", false);
	}	
	$commentInfo=array();
	$comment=new VComment();
	
	if (GetArg('host_id', $hostId))
		$commentInfo['host_id']=$hostId;
	
	if (GetArg('full_name', $arg))
		$commentInfo['full_name']=$arg;
	
	if (GetArg('comment_text', $arg)) {
		if (IsSpam($arg)) {
			API_EXIT(API_ERR, "Your comment looks like a spam. Please remove any html code in your comment and post it again.");
		}
//		$arg=str_replace($gText['M_ENTER_COMMENT'], "", htmlspecialchars($arg));
		$arg=str_replace($gText['M_ENTER_COMMENT'], "", $arg);
		$commentInfo['text']=$arg;
	}	
	
	if (GetArg('meeting_id', $meetingId))
		$commentInfo['meeting_id']=$meetingId;
	
	if (GetArg('author_id', $arg))
		$commentInfo['author_id']=$arg;
	
	if (GetArg('email', $arg))
		$commentInfo['email']=$arg;
	
	if (GetArg('public', $arg))
		$commentInfo['public']=$arg;
	
	if (!isset($commentInfo['text']) || $commentInfo['text']=='')
		API_EXIT(API_ERR, "Empty comment body.");
		
	$commentInfo['post_time']="#NOW()";
	
	if ($comment->Insert($commentInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $comment->GetErrorMsg());
	
	if ($meetingId!='') {
		$meeting=new VMeeting($meetingId);
		$meeting->Get($meetingInfo);
		if ($hostId=='') {
			$hostId=$meetingInfo['host_id'];
		}
	}
	$host=new VUser($hostId);
	if ($host->Get($hostInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $host->GetErrorMsg());
	if (!isset($hostInfo['id']))
		API_EXIT(API_ERR, "Host not found");
		
	
	if ($hostInfo['login']!=VUSER_GUEST) {
		
		$brand=new VBrand($hostInfo['brand_id']);
		if ($brand->Get($brandInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $brand->GetErrorMsg());
		if (!isset($brandInfo['id']))
			API_EXIT(API_ERR, "Brand not found");
		
		$from=$brandInfo['from_email'];
		$fromName=$brandInfo['from_name'];
		
		$commenterName=$commenterEmail='';
		if (isset($commentInfo['email']))
			$commenterEmail=$commentInfo['email'];
		
		if (isset($commentInfo['full_name'])) {
			$commenterName=$commentInfo['full_name'];
		} else if (isset($commentInfo['author_id'])) {
			$commenter=new VUser($commentInfo['author_id']);
			$commenter->Get($commenterInfo);
			$commenterName=$commenter->GetFullName($commenterInfo);
			$commenterEmail=$commenterInfo['login'];		
		}	
		
		$subject="Comments";
		$toName=$host->GetFullName($hostInfo);
		$to=$hostInfo['email'];
		if ($to=='')
			$to=$hostInfo['login'];
		
		if ($to=='' || $to==null || !valid_email($to))
			API_EXIT(API_ERR, "Host email address not defined");	
		
		$body="From: $commenterName\n";
		$body.="Email: $commenterEmail\n";
		if ($meetingId!='')
			$body.="Meeting: ".$meetingInfo['title']."\n";
		
		if (isset($commentInfo['public']) && $commentInfo['public']=='Y')
			$body.="Type: Public\n";
		else
			$body.="Type: Private\n";
		
		$body.="\n";
		$body.=$commentInfo['text'];
		$errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
				'', '', "", false, null, $brandInfo);
		
		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg);	
	}
	
} else if ($cmd=='SET_COMMENT' || $cmd=='DELETE_COMMENT') {
//require_once('api_includes/user_common.php');

//	if ($userErrMsg!='')
//		API_EXIT(API_ERR, $userErrMsg);
			
	if (GetArg('id', $arg)) {
		$commentId=$arg;
	}
	if ($commentId=='')
		API_EXIT(API_ERR, "Comment id not set");
		
	$comment=new VComment($commentId);
	$comment->GetValue('host_id', $hostId);

	$memberId=GetSessionValue('member_id');
	if ($memberId!=$hostId) {
		// check if the member is an admin
		$member=new VUser($memberId);
		$memberInfo=array();
		$member->Get($memberInfo);			
		
		if ($memberInfo['permission']!='ADMIN' || $memberInfo['brand_id']!=$userInfo['brand_id']) 
		{
			API_EXIT(API_ERR, "Not authorized");
		}
	}
	
	if ($cmd=='DELETE_COMMENT') {
		
		if ($comment->Drop()!=ERR_NONE)
			API_EXIT(API_ERR, $comment->GetErrorMsg());
			
	} else {

		if (GetArg('public', $arg))
			$commentInfo['public']=$arg;
		
		if ($comment->Update($commentInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $comment->GetErrorMsg());
		
	}		
	
}

function IsSpam($text)
{
	// consider it a spam if there is a web link in the comment
	$linkCount=substr_count($text, " href=");
	if ($linkCount>0)
		return true;
	// consider it a spam if there is script code
	$linkCount=substr_count($text, "<script ");
	if ($linkCount>0)
		return true;
	
	return false;
}
	
?>