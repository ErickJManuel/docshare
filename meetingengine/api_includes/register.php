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

require_once('api_includes/meeting_common.php');
require_once('dbobjects/vregistration.php');
require_once('api_includes/user_common.php');
require_once('dbobjects/vuser.php');
require_once('dbobjects/vregform.php');

/*
if (!isset($_REQUEST['mathguard_off'])) {
require_once("includes/ClassMathGuard.php");
	if (!MathGuard :: checkResult($_REQUEST['mathguard_answer'], $_REQUEST['mathguard_code'])) {
		API_EXIT(API_ERR, "You have entered an incorrect answer to the security question. Please return to the previous page to respond again.", "", false);
	}
*/
if (!isset($_REQUEST['security_off'])) {
	if (!checkSecurityAnswer($_REQUEST['security_answer'], $_REQUEST['security_code'])) {
		API_EXIT(API_ERR, "You have entered an incorrect answer to the security question. Please return to the previous page to respond again.", "", false);
	}
	
}
if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);

if ($errMsg!='')
	API_EXIT(API_ERR, $errMsg);
		
if (!isset($meetingInfo['id']))
	API_EXIT(API_ERR, "Missing meeting_id");

if ($meetingInfo['login_type']!='REGIS' || $meetingInfo['close_register']=='Y')
	API_EXIT(API_ERR, "The meeting is not open for registration");

$regInfo=array();

$regInfo['meeting_id']=$meetingInfo['id'];
$regInfo['regform_id']=$meetingInfo['regform_id'];

$formInfo=array();
if ($regInfo['regform_id']=='0') {
	if (($errMsg=VRegForm::GetDefault($formInfo))!='') {
		API_EXIT(API_ERR, $errMsg);
	}

} else {
	$regForm=new VRegForm($regInfo['regform_id']);
	if ($regForm->Get($formInfo)!=ERR_NONE) {
		API_EXIT(API_ERR, $regForm->GetErrorMsg());
	}
}	
if (!isset($formInfo['id']))
	API_EXIT(API_ERR, "Registration form not found in our records.");

$email='';
$name='';
$max=VRegForm::$maxFields;
for ($i=1; $i<=$max; $i++) {
	$key="field_".$i;
	if ((GetArg($key, $arg))) {
		
		// if the value is a key to another input param, use the other value
		// This is mainly for getting the "Others" value of the [STATE] selection. (read the value from "_Others" input field)
		if (GetArg("_".$arg, $argVal) && $argVal!='')
			$regInfo[$key]=$argVal;
		else
			$regInfo[$key]=$arg;
	}
	
	// extract name and email from the submission form fields
	// the name can be either FULLNAME or FIRSTNAME, LASTNAME.
	if ($formInfo['key_'.$i]=='[EMAIL]') {
		$email=$arg;
	} else if ($formInfo['key_'.$i]=='[FULLNAME]') {
		$name=$arg;
	} else if ($formInfo['key_'.$i]=='[FIRSTNAME]') {
		if ($name!='')
			$name=$arg." ".$name;
		else
			$name=$arg;
	} else if ($formInfo['key_'.$i]=='[LASTNAME]') {
		if ($name!='')
			$name.=" ".$arg;
		else
			$name=$arg;
	}
}

if ((GetArg('email', $arg)))
	$email=$arg;
	
if ($email=='')	
	API_EXIT(API_ERR, "Missing email");
	
if (!valid_email($email))
	API_EXIT(API_ERR, "The email address '$email' is invalid.");

if ((GetArg('name', $arg)))
	$name=$arg;

if ($name=='')
	$name=$email;
	
$regInfo['email']=trim($email);
$regInfo['name']=$name;

//$query="email='".$email."' AND meeting_id='".$meetingInfo['id']."'";
$query="LOWER(email)='".addslashes(strtolower($email))."' AND meeting_id='".$meetingInfo['id']."'";
if (($errMsg=VObject::Count(TB_REGISTRATION, $query, $count))!='')
//if (($errMsg=VObject::Select(TB_REGISTRATION, $query, $regInfo))!='')
	API_EXIT(API_ERR, $errMsg);

$registered=false;
if ($count>0) {
//if (isset($regInfo['id'])) {
	// OK to re-register. Simply send the email again.
//	$registered=true;
	API_EXIT(API_ERR, "The user '$email' is already registered for the meeting");
}

$hostId=$meetingInfo['host_id'];

$host=new VUser($hostId);
if ($host->Get($hostInfo)!=ERR_NONE)
	API_EXIT(API_ERR, $host->GetErrorMsg());
if (!isset($hostInfo['id']))
	API_EXIT(API_ERR, "Host not found");

$reg=new VRegistration();
if (!$registered) {
	if ($reg->Insert($regInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $reg->GetErrorMsg());
	if ($reg->Get($regInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $reg->GetErrorMsg());
}

$notify=true;
if (isset($formInfo['auto_reply']) && $formInfo['auto_reply']=='N')
	$notify=false;

require_once("dbobjects/vlicense.php");
require_once("dbobjects/vmailtemplate.php");
ob_start();
if (isset($GLOBALS['LOCALE_FILE']))
	@include_once($GLOBALS['LOCALE_FILE']);
else
	@include_once("locales/en.php");
ob_end_clean();
require_once("includes/common_text.php");
	
$brand=new VBrand($hostInfo['brand_id']);
if ($brand->Get($brandInfo)!=ERR_NONE)
	API_EXIT(API_ERR, $brand->GetErrorMsg());
if (!isset($brandInfo['id']))
	API_EXIT(API_ERR, "Brand not found");	
	
$meeting=new VMeeting($meetingInfo['id']);
if ($meeting->IsMeetingStarted($meetingInfo)) {
	$meeting->WriteSessionCache($meetingInfo, $hostInfo);
}

// send email to the registered user
if ($notify) {

	$mailInfo=array();	
	$errMsg=VMailTemplate::GetMailTemplate($brandInfo['id'], 'MT_REGISTER', $mailInfo);

	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
/*		
	$from=$hostInfo['email'];
	if ($from=='')
		$from=$hostInfo['login'];
	$fromName=$host->GetFullName($hostInfo);	
*/

	$fromName=$brandInfo['from_name'];
	$from=$brandInfo['from_email'];

	$subject=$gText['MD_REGISTRATION'];
//	$subject.=" [".$meetingInfo['access_id']."]";
	$subject.=" (".$meetingInfo['title'].")";
	$meeting->GetMeetingUrl($meetingUrl);
	$meetingUrl.="&page=HOME_JOIN&redirect=1&email=".rawurlencode($email);
	
	if (isset($formInfo['custom_reply']) && $formInfo['custom_reply']!='') {
		$mailInfo['body_text']=str_replace('MT_REGISTER_INFO', $formInfo['custom_reply'], $mailInfo['body_text']);
	}

	$body=VMailTemplate::GetBody($mailInfo, null, $brandInfo, $gText, $meetingInfo, $regInfo, $meetingUrl);
	//	$toName=htmlspecialchars($regInfo['name']);
	$toName=$regInfo['name'];
	$to=$regInfo['email'];

	$attachData='';
	$attachFile='';
	if ($meetingInfo['scheduled']=='Y') {
		$attachFile="meeting_".$meetingInfo['access_id'].".ics";
		$attachData=VMeeting::GetICal($meetingInfo, $meetingUrl, false);		
	}

	if (($errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
			$attachData, $attachFile, "text/calendar"))!='')
	{
		$msg="You have been registered succesfully but there is a problem sending your registration information via email. You can still join the meeting using the email address you registered with.";
		API_EXIT(API_ERR, $msg);
	}

	// record the time the email is sent
	if (isset($regInfo['notice_time'])) {
		$updateInfo=array();
		$updateInfo['notice_time']='#NOW()';
		if ($reg->Update($updateInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $reg->GetErrorMsg());
	}

}

if ($registered) {
	API_EXIT(API_ERR, "The user '$email' is already registered for the meeting. The meeting informatin is resent to your email address.");	
}

// send email to the meeting host
if ($hostInfo['login']!=VUSER_GUEST) {
	require_once("dbobjects/vmailtemplate.php");
					
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
	
	$subject=$gText['M_NEW_REGIST'];
//		$toName=htmlspecialchars($host->GetFullName($hostInfo));
	$toName=$host->GetFullName($hostInfo);
	$to=$hostInfo['email'];
	if ($to=='')
		$to=$hostInfo['login'];
		
	if (valid_email($to)) {

		$from=$brandInfo['from_email'];
		//	$fromName=htmlspecialchars($brandInfo['from_name']);
		$fromName=$brandInfo['from_name'];

		$body="meeting=[".$meetingInfo['access_id']."] '".$meetingInfo['title']."'\n";
		$body.=VRegistration::GetText($regInfo, $formInfo);	
		
		$errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
				'', '', "", false, null, $brandInfo);
		
		if ($errMsg!='') {
			// ignore mail error
			//API_EXIT(API_ERR, $errMsg);
		}
	}
}
	

?>