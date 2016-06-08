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

require_once("includes/common_lib.php");
require_once("api_includes/common.php");
require_once("dbobjects/vuser.php");

$userInfo=array();
$userErrMsg='';
	
if (GetArg('user', $arg) && $arg!='') {
	$userErrMsg=VObject::Find(TB_USER, 'access_id', $arg, $userInfo);
	if ($userErrMsg=='') {
		if (isset($userInfo['id']))
			$user=new VUser($userInfo['id']);	
		else
			$userErrMsg="User not found";
	}
} else if (GetArg('user_id', $arg) && $arg!='') {
	$user=new VUser($arg);
	if ($user->Get($userInfo)!=ERR_NONE)
		$userErrMsg=$user->GetErrorMsg();
		
	elseif (!isset($userInfo['id']))
		$userErrMsg="User not found";	
}
else if (IsLogin()) {
	$userId=GetSessionValue('member_id');
	$user=new VUser($userId);
	if ($user->Get($userInfo)!=ERR_NONE)
		$userErrMsg=$user->GetErrorMsg();		
	elseif (!isset($userInfo['id']))
		$userErrMsg="User not found";
}



?>