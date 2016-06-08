<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in.");

require_once('dbobjects/vregform.php');
require_once('includes/common_text.php');

$formInfo=array();

if ($cmd=='ADD_REGFORM') {
	
	$regForm=new VRegForm();
	$formInfo['author_id']=$memberId;
	
} else if ($cmd=='SET_REGFORM') {
	
	GetArg('form_id', $formId);

	if ($formId=='')
		return API_EXIT(API_ERR, 'form_id is not provided.');
	
	$regForm=new VRegForm($formId);
	$formInfo['author_id']=$memberId;
	
}

if (GetArg('name', $arg))
	$formInfo['name']=$arg;
	
if (GetArg('auto_reply', $arg))
	$formInfo['auto_reply']=$arg;

if (GetArg('custom_reply', $arg)) {
	if ($arg==$gText['MT_REGISTER_INFO'])
		$formInfo['custom_reply']='';
	else
		$formInfo['custom_reply']=$arg;
}
if (GetArg('auto_reminder', $arg))
	$formInfo['auto_reminder']=$arg;

$requiredFields='';	
$max=VRegForm::$maxFields;
$hasEmail=false;
for ($i=1; $i<=$max; $i++) {
	$key="key_".$i;
	if (GetArg($key, $keyArg)) {
		if ($keyArg=='[CUSTOM]') {			
			GetArg("custom_label_".$i, $customLabel);
			GetArg("custom_field_".$i, $customField);
			$customLabel=str_replace("[", "(", $customLabel);
			$customLabel=str_replace("]", ")", $customLabel);
			$customArg=$customLabel;
			if ($customField!='')
				$customArg.="=".str_replace("=", "-", $customField);
			$formInfo[$key]=$customArg;
		} else
			$formInfo[$key]=$keyArg;
	}
	
	$req="required_".$i;
	if (GetArg($req, $reqArg) && ($reqArg=='Y' || $reqArg=='1')) {
		if ($requiredFields!='')
			$requiredFields.=',';
		$requiredFields.=$key;
	}
	
	if ($keyArg=='[EMAIL]') {
		$hasEmail=true;
		if ($reqArg!='Y' && $reqArg!='1') {
			return API_EXIT(API_ERR, "Email must be a required field.");			
		}
	}
}

if ($requiredFields!='')
	$formInfo['required_fields']=$requiredFields;
elseif (GetArg('required_fields', $arg))
	$formInfo['required_fields']=$arg;

GetArg('meeting_id', $meetingId);

// assign a regform to a meeting
if ($meetingId!='') {
	require_once("dbobjects/vmeeting.php");
	$meeting=new VMeeting($meetingId);
	if ($meeting->Get($meetingInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $meeting->GetErrorMsg());
	
	if (!isset($meetingInfo['id']))
		return API_EXIT(API_ERR, "Meeting is not found in our records");
		
	if ($meetingInfo['host_id']!=$memberId) {
		return API_EXIT(API_ERR, "You are not a host of the meeting.");		
	}
}	

if ($cmd=='SET_REGFORM') {
	if ($regForm->Update($formInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $regForm->GetErrorMsg());
} else if ($cmd=='ADD_REGFORM') {
	
	if (!$hasEmail)
		return API_EXIT(API_ERR, "Email field is required.");		

	// add a new form
	if ($regForm->Insert($formInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $regForm->GetErrorMsg());
	
	if (isset($meeting)) {	
		$updateInfo=array();
		$updateInfo['regform_id']=$regForm->GetRowId();
		if ($meeting->Update($updateInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $meeting->GetErrorMsg());
	}
}

	
?>