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

require_once("dbobjects/vuser.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vmailtemplate.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);
	
if (!GetArg('template_id', $templateId))
	API_EXIT(API_ERR, "Missing template_id");

// check if the member is an admin of the brand
if ($userInfo['permission']!='ADMIN') 
	API_EXIT(API_ERR, "Not authorized");
	
// make sure the user has signed in as an admin of the site
if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
{
	API_EXIT(API_ERR, "Access is not authorized.");			
}				
$brand=new VBrand($userInfo['brand_id']);
if ($brand->Get($brandInfo)!=ERR_NONE) {
	API_EXIT(API_ERR, $brand->GetErrorMsg());
}

$mailInfo=array();
$mail=new VMailTemplate($templateId);
if ($mail->Get($mailInfo)!=ERR_NONE) {
	API_EXIT(API_ERR, $mail->GetErrorMsg());
}
if (!isset($mailInfo['id'])) {
	API_EXIT(API_ERR, "Email template not found");				
}

GetArg('body', $body);
//GetArg('from_name', $fromName);
//GetArg('from_email', $fromEmail);
$fromName=$brandInfo['from_name'];
$fromEmail=$brandInfo['from_email'];

GetArg('to_name', $toName);
GetArg('to_email', $toEmail);
GetArg('subject', $subject);

if (!valid_email($toEmail))
	API_EXIT(API_ERR, "Email address $toEmail is not valid");				


$errMsg=VMailTemplate::Send($fromName, $fromEmail, $toName, $toEmail, $subject, $body,
		'', '', '', false, null, $brandInfo);
if ($errMsg!='')
	API_EXIT(API_ERR, $errMsg);				


?>