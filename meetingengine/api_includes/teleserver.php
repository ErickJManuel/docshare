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

require_once("dbobjects/vteleserver.php");
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

if ($cmd=='ADD_TELE' || $cmd=='SET_TELE') {
	
	$teleInfo=array();
	if ($cmd=='ADD_TELE') {
		
		$teleServer=new VTeleServer();
		$teleInfo['brand_id']=$userInfo['brand_id'];		
		
	} else {
		GetArg('id', $teleId);
		
		if (!isset($teleId))	
			API_EXIT(API_ERR, "Missing teleconference server id");
		
		$teleServer=new VTeleServer($teleId);
		$teleServer->GetValue('brand_id', $brandId);
		if ($userInfo['brand_id']!=$brandId)
			API_EXIT(API_ERR, "Not an administrator of this brand");	
		
	}
	
	if (GetArg('name', $arg))
		$teleInfo['name']=$arg;
		
	if (GetArg('rec_sync_time', $arg))
		$teleInfo['rec_sync_time']=$arg;
			
	if (GetArg('server_url', $arg1))
		$teleInfo['server_url']=$arg1;		
	if (GetArg('access_key', $arg1)) {
		// don't assign the key if it contains *, which means this is a display value
		if (strpos($arg1, "*")===false)
			$teleInfo['access_key']=$arg1;
	}
	if (GetArg('can_dialout_checkbox', $arg) && $arg=="1") {		
		if (GetArg('can_dialout_checked', $arg1))
			$teleInfo['can_dialout']='Y';
		else
			$teleInfo['can_dialout']='N';
	}
		
	if (GetArg('dial_tollfree_only_checkbox', $arg) && $arg=="1") {		
		if (GetArg('dial_tollfree_only_checked', $arg1))
			$teleInfo['dial_tollfree_only']='Y';
		else
			$teleInfo['dial_tollfree_only']='N';
	}
		
	if (GetArg('can_record_checkbox', $arg) && $arg=="1") {		
		if (GetArg('can_rec_checked', $arg1))
			$teleInfo['can_record']='Y';
		else
			$teleInfo['can_record']='N';
	}
		
	if (GetArg('can_control_checkbox', $arg) && $arg=="1") {		
		if (GetArg('can_control_checked', $arg1))
			$teleInfo['can_control']='Y';
		else
			$teleInfo['can_control']='N';
	}
	
	if (GetArg('can_hangup_all_checkbox', $arg) && $arg=="1") {		
		if (GetArg('can_hangup_all_checked', $arg1))
			$teleInfo['can_hangup_all']='Y';
		else
			$teleInfo['can_hangup_all']='N';
	}
	if (GetArg('can_record', $arg))
		$teleInfo['can_record']=$arg;
	if (GetArg('can_control', $arg))
		$teleInfo['can_control']=$arg;
	if (GetArg('can_dialout', $arg))
		$teleInfo['can_dialout']=$arg;
	if (GetArg('can_hangup_all', $arg))
		$teleInfo['can_hangup_all']=$arg;
	if (GetArg('can_dial_host', $arg))
		$teleInfo['can_dial_host']=$arg;
	if (GetArg('dial_tollfree_only', $arg))
		$teleInfo['dial_tollfree_only']=$arg;
/*
	if (GetArg('can_getconf_checked', $arg1))
		$teleInfo['can_getconf']='Y';
	else
		$teleInfo['can_getconf']='N';
*/		
	if ($cmd=='SET_TELE') {
		if ($teleServer->Update($teleInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $teleServer->GetErrorMsg(), 'Update');
	} else {
		
		if (!isset($teleInfo['name']) || $teleInfo['name']=='')
			API_EXIT(API_ERR, 'Missing name');
		
		if ($teleServer->Insert($teleInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $teleServer->GetErrorMsg(), 'Insert');		
			
	}	
		
} else if ($cmd=='DELETE_TELE') {
require_once("dbobjects/vgroup.php");
			
	GetArg('id', $id);	
	if (!isset($id))	
		API_EXIT(API_ERR, "Missing teleconference server id");
	
	$teleServer=new VTeleServer($id);
	$teleServer->GetValue('brand_id', $brandId);
	if ($userInfo['brand_id']!=$brandId)
		API_EXIT(API_ERR, "Not an administrator of this brand");	

	$iquery="brand_id ='".$brandId."'";
	$iquery.=" AND teleserver_id='$id'";
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
		API_EXIT(API_ERR, "The server cannot be deleted because the following groups are using it. $groupName ");	
					
	if ($teleServer->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $teleServer->GetErrorMsg(), 'Delete');

}

?>