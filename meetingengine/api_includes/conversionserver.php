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

require_once("dbobjects/vconversionserver.php");
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

if ($cmd=='ADD_CONVERSION' || $cmd=='SET_CONVERSION')
{
	$convInfo=array();
	if ($cmd=='ADD_CONVERSION')
	{		
		$convServer=new VConversionServer();
		$convInfo['brand_id']=$userInfo['brand_id'];		
	}
	else
	{
		GetArg('id', $convId);
		
		if (!isset($convId))
			API_EXIT(API_ERR, "Missing conversion server id");
		
		$convServer=new VConversionServer($convId);
		$convServer->GetValue('brand_id', $brandId);
		if ($userInfo['brand_id']!=$brandId)
			API_EXIT(API_ERR, "Not an administrator of this brand");	
	}
	
	if (GetArg('name', $arg))
		$convInfo['name']=$arg;
	if (GetArg('server_url', $arg))
		$convInfo['url']=$arg;
	if (GetArg('access_key', $arg))
		$convInfo['access_key']=$arg;
	
	if ($cmd=='SET_CONVERSION')
	{
		if ($convServer->Update($convInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $convServer->GetErrorMsg(), 'Update');
	}
	else
	{		
		if (!isset($convInfo['name']) || $convInfo['name']=='')
			API_EXIT(API_ERR, 'Missing name');
		if (!isset($convInfo['url']) || $convInfo['url']=='')
			API_EXIT(API_ERR, 'Missing url');
		
		if ($convServer->Insert($convInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $convServer->GetErrorMsg(), 'Insert');	
	}
	
	if (GetArg('return', $page))
	{
		if ($convServer->GetValue('id', $convId)!=ERR_NONE)
			API_EXIT(API_ERR, $convServer->GetErrorMsg());
			
		$page=VWebServer::DecodeDelimiter1($page);
			
		header("Location: $page");
		API_EXIT(API_NOMSG);

	}
}
else if ($cmd=='DELETE_CONVERSION')
{
	GetArg('id', $id);	
	if (!isset($id))	
		API_EXIT(API_ERR, "Missing server id");
		
	$convServer=new VConversionServer($id);
	$convServer->GetValue('brand_id', $brandId);
	if ($userInfo['brand_id']!=$brandId)
		API_EXIT(API_ERR, "Not an administrator of this brand");
		
	$iquery="brand_id ='".$brandId."'";
	$iquery.=" AND conversionserver_id='$id'";
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
		
	if ($convServer->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $convServer->GetErrorMsg(), 'Delete');
}

?>