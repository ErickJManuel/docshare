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
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vviewer.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);
	
$memberId=GetSessionValue('member_id');
if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in");
	
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId!=$userInfo['id']) {

	if ($memberPerm!='ADMIN' || $memberBrand!=$userInfo['brand_id']) 
	{
		API_EXIT(API_ERR, "Not authorized");
	}
}

if (($memberPerm!='ADMIN' || $memberBrand!=$userInfo['brand_id']) && $userInfo['login']==VUSER_GUEST)
	return API_EXIT(API_ERR, "You cannot set the properties of a default user");

$newInfo=array();
if (GetArg('room_name', $arg))
	$newInfo['room_name']=$arg;	

if (GetArg('room_description', $arg))
	$newInfo['room_description']=$arg;	

if (GetArg('public', $arg))
	$newInfo['public']=$arg;
	
if (GetArg('public_comment', $arg))
	$newInfo['public_comment']=$arg;
	
if (GetArg('reset_logo', $arg) && $arg=='1') {
		
	// need to delete old logo if it exists		
	if (isset($userInfo['logo_id']) && $userInfo['logo_id']!='0')
	{
		$logo=new VImage($userInfo['logo_id']);
		$logo->GetValue('file_name', $fileName);

		$oldFile=VImage::GetFilePath($fileName);
		if (file_exists($oldFile))
			unlink($oldFile);
	}

	$newInfo['logo_id']=0;
} elseif (isset($_FILES['logo_file']['tmp_name']) && $_FILES['logo_file']['tmp_name']!='') {
	

	$tempFile = $_FILES['logo_file']['tmp_name'];
	$srcFile=$_FILES['logo_file']['name'];
	
	$pictId=0;
	if (isset($userInfo['logo_id']))
	{
		$pictId=$userInfo['logo_id'];
	}
	
	$errMsg=ProcessUploadImage($tempFile, $srcFile, $userInfo['id'], $pictId, '', MAX_BRAND_LOGO_WIDTH, MAX_BRAND_LOGO_HEIGHT, 'MAX_SIZE');
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
		
	$newInfo['logo_id']=$pictId;
}

	
if ($user->Update($newInfo)!=ERR_NONE)
	API_EXIT(API_ERR, $user->GetErrorMsg());


?>