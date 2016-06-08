<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


// this page is NOT done yet. It's supposed to replace the code in signin.php under the home dir.
require_once('api_includes/common.php');

if (isset($_POST['signin'])) {
	GetArg('password', $password);
	GetArg('login_id', $login);
	
	if ($login=='')
		return API_EXIT(API_ERR, "The email address is missing.");		

	if ($password=='')
		return API_EXIT(API_ERR, "The password is missing.");	
	
	// search to see if the user already exists
	$userInfo=array();
	if (defined('ROOT_USER') && $login==ROOT_USER)
		$query="LOWER(login)= '".addslashes(strtolower($login))."'";
	else
		$query="LOWER(login)='".addslashes(strtolower($login))."' AND brand_id = '".$GLOBALS['BRAND_ID']."'";
	$loginMsg=VObject::Select(TB_USER, $query, $userInfo);
	//echo("count=".count($userInfo)." login=".$login." id=".$userInfo['id']);
			
	if ($loginMsg!='')
		return API_EXIT(API_ERR, $loginMsg);
		
	if  (!isset($userInfo['login']))
		return API_EXIT(API_ERR, "The email address you provided does not match our records.");
			
	if ($userInfo['password']!=$password)
		return API_EXIT(API_ERR, "The password you provided does not match our records.");						

			
	if ($userInfo['active']!='Y') {
		return API_EXIT(API_ERR, 'Your account is not active. Please contact your service provider.');					
	}
				
	if (defined('ROOT_USER') && $login==ROOT_USER) {
		$userInfo['brand_id']=$GLOBALS['BRAND_ID'];
		
		$trialGroupId=$gBrandInfo['trial_group_id'];
		
		$rootInfo=array();
		$rootInfo['brand_id']=$GLOBALS['BRAND_ID'];
		$rootInfo['group_id']=$trialGroupId;
		$rootUser=new VUser($userInfo['id']);
		if ($rootUser->Update($rootInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $rootUser->GetErrorMsg());
		
		SetSessionValue("root_user", 'true');
	}
				
	if (!SetSessionValue("member_id", $userInfo['id'])) {
		return API_EXIT(API_ERR, "Session cookie cannot be set.");
	}

	$memberName=VUSer::GetFullName($userInfo);
	SetSessionValue("member_name", $memberName);
	SetSessionValue("member_perm", $userInfo['permission']);					
	SetSessionValue("member_brand", $userInfo['brand_id']);	
	if ($userInfo['time_zone']!='')
		SetSessionValue("time_zone", $userInfo['time_zone']);	
	
	$newInfo['login_session_id']=session_id();
	$newInfo['login_start_time']='#NOW()';
	$user=new VUser($userInfo['id']);
	$user->Update($newInfo);
	
	include_once("includes/house_keeping.php");
				

} else if (isset($_POST['sendpwd'])) {
	require_once("dbobjects/vmailtemplate.php");
	
	GetArg('sendpwd_id', $getId);
	
	if ($getId=='') {
		return API_EXIT(API_ERR, "The email address is missing.");
	}
	if ($getId==VUSER_GUEST) {
		return API_EXIT(API_ERR, "Invalid login account.");
	} 
		
	$userInfo=array();
	$query="LOWER(login)= '".addslashes(strtolower($getId))."' AND brand_id = '".$GLOBALS['BRAND_ID']."'";
	$getpwdMsg=VObject::Select(TB_USER, $query, $userInfo);
	
	if ($getpwdMsg!='') {
		return API_EXIT(API_ERR, $getpwdMsg);
	}
	if  (!isset($userInfo['login'])) {
		return API_EXIT(API_ERR, "The email address you provided does not match our records.");
	}
	if ($userInfo['active']!='Y') {
		return API_EXIT(API_ERR, 'Your account is not active. Please contact your service provider.');					
	}		
	
	$user=new VUser($userInfo['id']);
	$mailInfo=array();
	$getpwdMsg=VMailTemplate::GetMailTemplate($gBrandInfo['id'], 'MT_SEND_PWD', $mailInfo);				
	
	if (!isset($mailInfo['id'])) {
		return API_EXIT(API_ERR, "Email template not found");							
	}
	
	$from=$gBrandInfo['from_email'];
	$fromName=$gBrandInfo['from_name'];
	$subject=$gText[$mailInfo['subject']];
	$body=VMailTemplate::GetBody($mailInfo, $userInfo, $gBrandInfo, $gText);
	$toName=$user->GetFullName($userInfo);
	$to=$userInfo['email'];
	if ($to=='')
		$to=$userInfo['login'];
	
	if (valid_email($to)) {
		$err=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
			'', '', "", false, null, $gBrandInfo);

		if ($err!='')
			return API_EXIT(API_ERR, $err);
	} else {
		return API_EXIT(API_ERR, "Email address $to is not valid.");							
	}
}

?>