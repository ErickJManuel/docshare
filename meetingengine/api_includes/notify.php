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

$userAccessId='';
GetArg("user", $userAccessId);

if ($userAccessId=='')
	return API_EXIT(API_ERR, "User is not set.");
	
VObject::Find(TB_USER, 'access_id', $userAccessId, $userInfo);
if (!isset($userInfo['id']))
	return API_EXIT(API_ERR, "User not found");
	
$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId==$userInfo['id'] || ($member_perm='ADMIN' && $member_brand==$userInfo['brand_id'])) {
require_once("dbobjects/vmailtemplate.php");
require_once("dbobjects/vbrand.php");
	$to=$userInfo['email'];
	if ($to=='')
		$to=$userInfo['login'];
	
	if (valid_email($to)) {
		$brand=new VBrand($userInfo['brand_id']);
		$brand->Get($brandInfo);
		if (!isset($brandInfo['id']))
			return API_EXIT(API_ERR, "Brand not found");
		
		$from=$brandInfo['from_email'];
		$fromName=$brandInfo['from_name'];
		GetArg("subject", $subject);
		GetArg("body", $body);
		
		$toName=VUser::GetFullName($userInfo);
		
		$errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
			'', '', "", false, null, $brandInfo);

		if ($errMsg!='')
			return API_EXIT(API_ERR, $errMsg);	
	}
} else {
	return API_EXIT(API_ERR, "Not authorized.");
}

?>