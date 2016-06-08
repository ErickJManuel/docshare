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

//require_once("dbobjects/vuser.php");
//require_once("dbobjects/vgroup.php");
//require_once("dbobjects/vteleserver.php");
//require_once("dbobjects/vlicense.php");

/* $userInfo must be defined in the embedding script */
/* Set all session values for a signed-in user */
	if (!isset($userInfo['id']))
		return;	
		
	SetSessionValue("member_id", $userInfo['id']);
	SetSessionValue("member_login", $userInfo['login']);
	$memberName=VUSer::GetFullName($userInfo);
	SetSessionValue("member_name", $memberName);
	SetSessionValue("member_perm", $userInfo['permission']);					
	SetSessionValue("member_brand", $userInfo['brand_id']);	
	SetSessionValue("member_brand_name", GetSessionValue('brand_name'));	
	if ($userInfo['time_zone']!='')
		SetSessionValue("time_zone", $userInfo['time_zone']);
	SetSessionValue("member_access_id", $userInfo['access_id']);

/* move the following code to includes/brand.php because the following values are not set if the login is via "token" using api.php */
/*
	$newInfo=array();			
	$newInfo['login_session_id']=session_id();
	$newInfo['login_start_time']='#NOW()';
	$user=new VUser($userInfo['id']);
	$user->Update($newInfo);
					
	// check if recording, library, and registration are enabled for the user
	$canRecord='Y';
	$hasLibrary='Y';
	$hasRegist='Y';
	$canPoll='Y';

// FIXME: when using "token" to sign in, these values are not set.
// We can call this function in api.php but that seems to be too costly since the api request can be called frequently.
	$user->Get($theUserInfo);

	// check first if the user's license allows recording
	$licenseId=$theUserInfo['license_id'];
	$theLicense=new VLicense($licenseId);
	$theLicense->GetValue('btn_disabled', $licDisabled);
	if ($licDisabled && $licDisabled!='') {
		$btns=explode(",", $licDisabled);
		foreach ($btns as $abtn) {
			if ($abtn=='record') {
				$canRecord='N';
			} else if ($abtn=='library') {
				$hasLibrary='N';
			} else if ($abtn=='register') {
				$hasRegist='N';
			} else if ($abtn=='poll') {
				$canPoll='N';
			}
		}
	}

	// check next if the user's group has recording enabled
	if ($canRecord=='Y') {
		$theGroup=new VGroup($theUserInfo['group_id']);
		$theGroup->GetValue('teleserver_id', $theTeleId);
		if ($theTeleId && $theTeleId!='0') {
			$theTeleServer=new VTeleServer($theTeleId);
			$theTeleServer->GetValue('can_record', $canRecord);
		}
	}
	
	SetSessionValue("can_record", $canRecord);
	SetSessionValue("has_library", $hasLibrary);
	SetSessionValue("has_regist", $hasRegist);
	if (defined('ENABLE_POLLING') && constant('ENABLE_POLLING')=='1') {
		SetSessionValue("can_poll", $canPoll);
	}
	
*/
?>