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

require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vuser.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);
		
if ($userInfo['permission']!='ADMIN')
{
	API_EXIT(API_ERR, "Not authorized");	
}
// make sure the user has signed in as an admin of the site
if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
{
	API_EXIT(API_ERR, "Access is not authorized.");			
}

if ($cmd=='ADD_STORAGE' || $cmd=='SET_STORAGE')
{
	$storageInfo=array();
	if ($cmd=='ADD_STORAGE')
	{		
		$storage=new VStorageServer();
		$storageInfo['brand_id']=$userInfo['brand_id'];		
	}
	else
	{
		GetArg('id', $storageId);
		
		if (!isset($storageId))
			API_EXIT(API_ERR, "Missing storage server id");
		
		$storage=new VStorageServer($storageId);
		$storage->GetValue('brand_id', $brandId);
		if ($userInfo['brand_id']!=$brandId)
			API_EXIT(API_ERR, "Not an administrator of this brand");	
	}
	
	if (GetArg('name', $arg))
		$storageInfo['name']=$arg;
	if (GetArg('server_url', $arg))
		$storageInfo['url']=$arg;
	if (GetArg('access_code', $arg))
		$storageInfo['access_code']=$arg;

/* don't need this right now
	if (GetArg('path', $arg))
		$storageInfo['path']=$arg;
		
	if (GetArg('access_control', $arg))
	{
		$storageInfo['access_script']="vftp.php";
	}
	else
	{
		$storageInfo['access_script']="";
	}
*/	
	if ($cmd=='SET_STORAGE')
	{
		if ($storage->Update($storageInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $storage->GetErrorMsg(), 'Update');
	}
	else
	{		
		if (!isset($storageInfo['name']) || $storageInfo['name']=='')
			API_EXIT(API_ERR, 'Missing name');
		if (!isset($storageInfo['url']) || $storageInfo['url']=='')
			API_EXIT(API_ERR, 'Missing url');
		if (!isset($storageInfo['access_code']) || $storageInfo['access_code']=='')
			API_EXIT(API_ERR, 'Missing access code');
// don't need this right now
//		if (!isset($storageInfo['path']) || $storageInfo['path']=='')
//			API_EXIT(API_ERR, 'Missing path');
		
		if ($storage->Insert($storageInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $storage->GetErrorMsg(), 'Insert');	
	}
	
	if (GetArg('return', $page))
	{
		if ($storage->GetValue('id', $storageId)!=ERR_NONE)
			API_EXIT(API_ERR, $storage->GetErrorMsg());
			
		$page=VWebServer::DecodeDelimiter1($page);
		if (strpos($page, PG_ADMIN_STORAGE_INSTALL)!==false) {
			if (strpos($page, '?')===false)
				$page.="?id=".$storageId."&".SID;
			else
				$page.="&id=".$storageId."&".SID;
			
			header("Location: $page");
			API_EXIT(API_NOMSG);
		}
	}
}
else if ($cmd=='DELETE_STORAGE')
{
	GetArg('id', $id);	
	if (!isset($id))	
		API_EXIT(API_ERR, "Missing server id");
		
	$storage=new VStorageServer($id);
	$storage->GetValue('brand_id', $brandId);
	if ($userInfo['brand_id']!=$brandId)
		API_EXIT(API_ERR, "Not an administrator of this brand");
		
	$iquery="brand_id ='".$brandId."'";
	$iquery.=" AND storageserver_id='$id'";
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
		
	if ($storage->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $storage->GetErrorMsg(), 'Delete');
}

?>