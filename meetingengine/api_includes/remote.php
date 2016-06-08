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

require_once("dbobjects/vremoteserver.php");
require_once("dbobjects/vuser.php");
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

if ($cmd=='ADD_REMOTE' || $cmd=='SET_REMOTE') {
	
	if ($cmd=='ADD_REMOTE') {
		
		$remote=new VRemoteServer();
		$remoteInfo['brand_id']=$userInfo['brand_id'];		
		
	} else {
		GetArg('id', $remoteId);
		
		if (!isset($remoteId))	
			API_EXIT(API_ERR, "Missing video server id");
		
		$remote=new VRemoteServer($remoteId);
		$remote->GetValue('brand_id', $brandId);
		if ($userInfo['brand_id']!=$brandId)
			API_EXIT(API_ERR, "Not an administrator of this brand");	
		
	}
	
	if (GetArg('name', $arg))
		$remoteInfo['name']=$arg;
	if (GetArg('server_url', $arg))
		$remoteInfo['server_url']=$arg;
	if (GetArg('client_url', $arg))
		$remoteInfo['client_url']=$arg;
	if (GetArg('password', $arg))
		$remoteInfo['password']=$arg;
		
	if ($cmd=='SET_REMOTE') {
		if ($remote->Update($remoteInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $remote->GetErrorMsg(), 'Update');
	} else {
		
		if (!isset($remoteInfo['name']) || $remoteInfo['name']=='')
			API_EXIT(API_ERR, 'Missing name');
		
		if (!isset($remoteInfo['server_url']) || $remoteInfo['server_url']=='')
			API_EXIT(API_ERR, 'Missing server_url');
		if (!isset($remoteInfo['client_url']) || $remoteInfo['client_url']=='')
			API_EXIT(API_ERR, 'Missing client_url');

		if ($remote->Insert($remoteInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $remote->GetErrorMsg(), 'Insert');		
			
	}	
		
} else if ($cmd=='DELETE_REMOTE') {
			
	GetArg('id', $id);	
	if (!isset($id))	
		API_EXIT(API_ERR, "Missing server id");
	
	$remote=new VRemoteServer($id);
	$remote->GetValue('brand_id', $brandId);
	if ($userInfo['brand_id']!=$brandId)
		API_EXIT(API_ERR, "Not an administrator of this brand");
		
	$iquery="brand_id ='".$brandId."'";
	$iquery.=" AND remoteserver_id='$id'";
	$errMsg=VObject::SelectAll(TB_GROUP, $iquery, $iresult2);
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
	$groupName='';
	while ($irow = mysql_fetch_array($iresult2, MYSQL_ASSOC)) {
		$group=new VGroup($irow['id']);
		$group->GetValue('name', $aname);
		$groupName.="'$aname' ";
	}
	if ($groupName!='')
		return API_EXIT(API_ERR, "The server cannot be deleted because the following groups are using it: $groupName");	

	if ($remote->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $remote->GetErrorMsg(), 'Delete');

}

?>