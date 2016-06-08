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

require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vteleserver.php");
require_once("dbobjects/vremoteserver.php");
require_once("dbobjects/vvideoserver.php");
require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
return API_EXIT(API_ERR, $userErrMsg);
	
$groupInfo=array();

if ($cmd=='SET_GROUP' || $cmd=='DELETE_GROUP') {
	GetArg('id', $groupId);
	if ($groupId=='') {
	return API_EXIT(API_ERR, "Missing id");
	}
	$group=new VGroup($groupId);

} else if ($cmd=='ADD_GROUP') {
	$group=new VGroup();
	
	if ($userInfo['permission']=='ROOT') {
		if (GetArg('brand_id', $arg) && $arg!='')
			$groupInfo['brand_id']=$arg;
		else
		return API_EXIT(API_ERR, "brand_id not set");
	} else {
		$groupInfo['brand_id']=$userInfo['brand_id'];			
	}
	
	$groupInfo['description']="";
}
		
// check if the member is an admin of the brand
if ($userInfo['permission']!='ADMIN') 
return API_EXIT(API_ERR, "Not authorized");

// make sure the user has signed in as an admin of the site
if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
{
	API_EXIT(API_ERR, "Access is not authorized.");			
}

if ($cmd=='SET_GROUP' || $cmd=='DELETE_GROUP') {
	$group->GetValue('brand_id', $brandId);
	if ($brandId!=$userInfo['brand_id'])
	return API_EXIT(API_ERR, "Not authorized");			
}


if (GetArg('name', $arg))
	$groupInfo['name']=$arg;	

if (GetArg('description', $arg))
	$groupInfo['description']=$arg;	

if (GetArg('webserver_id', $arg)) {
	if ($arg!='') {
		$aServer=new VWebServer($arg);
		$aServer->GetValue('brand_id', $bid);
		if ($bid!='0' && $bid!='65535' && $bid!=$userInfo['brand_id'])
			return API_EXIT(API_ERR, "Invalid webserver_id $arg.");
	}
	
	$groupInfo['webserver_id']=$arg;
}
if (GetArg('videoserver_id', $arg)) {
	if ($arg!='') {
		$aServear=new VVideoServer($arg);
		$aServear->GetValue('brand_id', $bid);
		if ($bid!='0' && $bid!='65535' && $bid!=$userInfo['brand_id'])
			return API_EXIT(API_ERR, "Invalid videoserver_id $arg.");
	}
	$groupInfo['videoserver_id']=$arg;
}
if (GetArg('remoteserver_id', $arg)) {
	if ($arg!='') {
		$aServear=new VRemoteServer($arg);
		$aServear->GetValue('brand_id', $bid);
		if ($bid!='0' && $bid!='65535' && $bid!=$userInfo['brand_id'])
			return API_EXIT(API_ERR, "Invalid remoteserver_id $arg.");
	}
	$groupInfo['remoteserver_id']=$arg;
}
if (GetArg('teleserver_id', $arg)) {
	if ($arg!='') {
		$aServear=new VTeleServer($arg);
		$aServear->GetValue('brand_id', $bid);
		if ($bid!='0' && $bid!='65535' && $bid!=$userInfo['brand_id'])
			return API_EXIT(API_ERR, "Invalid teleserver_id $arg.");
	}
	$groupInfo['teleserver_id']=$arg;
}
if (GetArg('storageserver_id', $arg)) {
	if ($arg!='') {
		$aServear=new VStorageServer($arg);
		$aServear->GetValue('brand_id', $bid);
		if ($bid!='0' && $bid!='65535' && $bid!=$userInfo['brand_id'])
			return API_EXIT(API_ERR, "Invalid storageserver_id $arg.");
	}
	$groupInfo['storageserver_id']=$arg;
}
if (GetArg('webserver2_id', $arg))
	$groupInfo['webserver2_id']=$arg;
if (GetArg('videoserver2_id', $arg))
	$groupInfo['videoserver2_id']=$arg;
if (GetArg('remoteserver2_id', $arg))
	$groupInfo['remoteserver2_id']=$arg;
if (GetArg('conversionserver_id', $arg))
	$groupInfo['conversionserver_id']=$arg;

//if (GetArg('free_audio_conf', $arg))
//	$groupInfo['free_audio_conf']=$arg;

if ($cmd=='SET_GROUP') {
	if ($group->Update($groupInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $group->GetErrorMsg());
} else if ($cmd=='ADD_GROUP') {

	// if the group's web hosting account is not set, get the default hosting account for the brand
	if (!isset($groupInfo['webserver_id']) || $groupInfo['webserver_id']=='' || $groupInfo['webserver_id']=='0') {
		$brand=new VBrand($groupInfo['brand_id']);
		if ($brand->GetValue('site_url', $siteUrl)!=ERR_NONE)
			return API_EXIT(API_ERR, $brand->GetErrorMsg());
		
		$query="brand_id ='".$groupInfo['brand_id']."'";
		$query.=" AND url='".$siteUrl."'";
			
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $webInfo);
		if ($errMsg!='') {
			return API_EXIT(API_ERR, $errMsg);
		}
		
		if (!isset($webInfo['id']))
			return API_EXIT(API_ERR, "Couldn't find the default web hosting account to assign to the group.");
		
		$groupInfo['webserver_id']=$webInfo['id'];
		
	}
	
	if (!isset($groupInfo['name'])) {
		$groupInfo['name']="Untitled";
	}
	
	if ($group->Insert($groupInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $group->GetErrorMsg());
} else if ($cmd=='DELETE_GROUP') {
	
	$brand=new VBrand($userInfo['brand_id']);
	$brand->GetValue('trial_group_id', $defGroupId);
	if ($defGroupId==$groupId)
		return API_EXIT(API_ERR, "Couldn't delete the default group.");	
	
	$query="group_id='$groupId'";
	$errMsg=VObject::SelectAll(TB_USER, $query, $result);
	if ($errMsg!='')
		return API_EXIT(API_ERR, $errMsg);
	$num_rows = mysql_num_rows($result);
	if ($num_rows>0) {
		return API_EXIT(API_ERR, "Couldn't delete a non-empty group. There are $num_rows users in the group.");		
	}	
	
	if ($group->Drop()!=ERR_NONE)
		return API_EXIT(API_ERR, $group->GetErrorMsg());
}

?>