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

// this file is deprecated and replaced with a token request to rest/signin/index.php
/* Use a meeting cache file to remove the need to access the database */

require_once('dbobjects/vmeeting.php');

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
	@include_once($meetingFile);
	if (isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
// turn off database access if a meeting is already in progress
		$accessDb=false;
	}
}

$meetingXml='';
if (!$accessDb) {
	// get data from cache; no database access
	
	if (isset($_loginType) && $_loginType=='PWD') {
		if (GetArg('password', $pwd) && $pwd!='') {
			if (!isset($_meetingPassword) || $pwd==$_meetingPassword) {
				API_EXIT(API_NOERR, 'OK');
			} else {
				API_EXIT(API_ERR, 'Password does not match');		
			}	
		} else {
			API_EXIT(API_ERR, 'Missing password');		
		}	
	} else if (isset($_loginType) && $_loginType=='REGIS') {
		GetArg('email', $email);
		$email=trim($email);
		$lemail=strtolower($email);
		if ($email!='') {
			// make it case insensitive			
			if (isset($_registration[$email]) || isset($_registration[$lemail])) {
				require_once('dbobjects/vregistration.php');
				// must add this for IE7 to work on SSL download
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				
				header("Content-Type: text/xml");
				$regXml=XML_HEADER."\n";
				if (isset($_registration[$email]))
					$regXml.=VRegistration::GetXML($_registration[$email], null);
				else
					$regXml.=VRegistration::GetXML($_registration[$lemail], null);
								
				echo $regXml;
				//exit();
				API_EXIT(API_NOMSG);
			} else {
				API_EXIT(API_ERR, "Not a registered user for the meeting");
			}
		} else {
			
			API_EXIT(API_ERR, 'Missing email address');
			
		}
			
	} else {
		API_EXIT(API_NOERR, 'OK');
	}	

} else {

	require_once('api_includes/meeting_common.php');
			
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
	if (!isset($meetingInfo['id']))
		API_EXIT(API_ERR, "Meeting id not set.");
			
	if ($meetingInfo['login_type']=='PWD') {
		
		if (GetArg('password', $pwd) && $pwd!='') {
			if ($pwd==$meetingInfo['password']) {
				API_EXIT(API_NOERR, 'OK');
			} else {
				API_EXIT(API_ERR, 'Password does not match');		
			}	
		} else {
			API_EXIT(API_ERR, 'Missing password');		
		}
		
	} else if ($meetingInfo['login_type']=='REGIS') {
		require_once('dbobjects/vregistration.php');
		require_once('dbobjects/vregform.php');
		GetArg('email', $email);
		$email=trim($email);
	
		if ($email!='') {
			
			$mid=$meetingInfo['id'];
//			$query="email='$email' AND meeting_id='$mid'";
			$query="LOWER(email)='".addslashes(strtolower($email))."' AND meeting_id='$mid'";
			$regInfo=array();
			if (($errMsg=VObject::Select(TB_REGISTRATION, $query, $regInfo))!='')
				API_EXIT(API_ERR, $errMsg);
				
			if (!isset($regInfo['id']))
				API_EXIT(API_ERR, "Not a registered user for the meeting");
	
			$formId=$meetingInfo['regform_id'];
			$formInfo=array();
			if ($formId=='0') {
				VRegForm::GetDefault($formInfo);			
			} else {
				$form=new VRegForm($formId);
				if ($form->Get($formInfo)!=ERR_NONE) {
					// ignore error
				}
			}
	
					
			$regXml=XML_HEADER."\n";
			$regXml.=VRegistration::GetXML($regInfo, $formInfo);
			
			// must add this for IE7 to work on SSL download
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
	
			header("Content-Type: text/xml");
			echo $regXml;
			//exit();
			API_EXIT(API_NOMSG);
				
		} else {
			API_EXIT(API_ERR, 'Missing email address');
		}	
		
	} else {
		API_EXIT(API_NOERR, 'OK');
	
	}
	
}
?>