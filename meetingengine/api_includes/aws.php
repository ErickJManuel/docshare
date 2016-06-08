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

/* This file is not in use anymore so comment it out

require_once("dbobjects/vaws.php");
require_once("dbobjects/vwebserver.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);

if ($userInfo['permission']!='ADMIN') {
	API_EXIT(API_ERR, "Not authorized");	
}
// make sure the user has signed in as an admin of the site
if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
{
	API_EXIT(API_ERR, "Access is not authorized.");			
}
$accessKey=$_POST['access_key'];
$secretKey=$_POST['secret_key'];
$ec2Image=$_POST['ec2_image'];
$action=$_POST['action'];

$webInfo=array();
$awsInfo=array();
$starting=false;
$state='';
$installed='';

if ($cmd=='ADD_WEB') {
	
	$instanceId='';
	$state='';
	$installed='N';
	
	$aws=new VAWS;
	$aws->SetKeyPair($accessKey, $secretKey);

	$err=$aws->RunInstances($ec2Image, "m1.small", $instanceId, $state);

	if ($err!=ERR_NONE) {
		ShowError($aws->GetErrorMsg());
		return;
	}

	if ($instanceId!='') {
		$awsInfo['image_id']=$ec2Image;
		$awsInfo['instance_id']=$instanceId;
		$awsInfo['state']=$state;

		if ($aws->Insert($awsInfo)!=ERR_NONE) {
			ShowError($aws->GetErrorMsg());
			return;
		}
		
		$aws->Get('id', $awsId);
		
		$webServer=new VWebServer();
		$webInfo['brand_id']=$GLOBALS['BRAND_ID'];
		$webInfo['aws_id']=$awsId;
		$webInfo['name']='AWS $instanceId';
		$webInfo['login']='host';
		$webInfo['password']=mt_rand(100000, 999999);
		if ($webServer->Insert($webInfo)!=ERR_NONE) {
			ShowError($webServer->GetErrorMsg());		
			return;
		}		
		
		$starting=true;
	}

	
	
	
} else if ($cmd=='DELETE_WEB') {

if (GetArg('id', $webServerId)) {
	
	$webServer=new VWebServer($webServerId);
	$webServer->Get($webInfo);
	if (!isset($webInfo['id'])) {
		ShowError("Web server not found");
		return;
	}
	
	$awsId=$webInfo['aws_id'];
	$aws=new VAWS($awsId);
	$aws->SetKeyPair($accessKey, $secretKey);
	if ($aws->Get($awsInfo)!=ERR_NONE) {
		ShowError($aws->GetErrorMsg());
		return;
	}
	$instanceId=$awsInfo['instance_id'];
	$state=$awsInfo['state'];
	$installed=$awsInfo['installed'];
	
	if ($action=='check') {
		if ($aws->DescribeInstance($instanceId, $instance)!=ERR_NONE) {
			ShowError($aws->GetErrorMsg());
			return;
		}
			
		$state=$instance->instanceStatename;
	} else if ($action=='delete') {
		
	}
	
} else if ($action=='add') {

*/
?>