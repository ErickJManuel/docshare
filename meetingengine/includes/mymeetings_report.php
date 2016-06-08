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

require_once("includes/meetings_common.php");

$userReport=true;

$memberId=GetSessionValue('member_id');
$user=new VUser($memberId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

$userEmail=$userInfo['login'];

GetArg('meeting', $meetingId);

require_once("includes/admin_report.php");

?>