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

require_once("dbobjects/vvideoserver.php");
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

if ($cmd=='ADD_VIDEO' || $cmd=='SET_VIDEO') {
	
	$videoInfo=array();
	if ($cmd=='ADD_VIDEO') {
		
		$video=new VVideoServer();
		$videoInfo['brand_id']=$userInfo['brand_id'];		
		
	} else {
		GetArg('id', $videoServerId);
		
		if (!isset($videoServerId))	
			API_EXIT(API_ERR, "Missing video server id");
		
		$video=new VVideoServer($videoServerId);
		$video->GetValue('brand_id', $brandId);
		if ($userInfo['brand_id']!=$brandId)
			API_EXIT(API_ERR, "Not an administrator of this brand");	
		
	}
	
	if (GetArg('name', $arg))
		$videoInfo['name']=$arg;
	if (GetArg('url', $arg))
		$videoInfo['url']=$arg;
	if (GetArg('bandwidth', $arg))
		$videoInfo['bandwidth']=$arg;
	if (GetArg('width', $arg))
		$videoInfo['width']=$arg;
	if (GetArg('height', $arg))
		$videoInfo['height']=$arg;
	if (GetArg('max_wind', $arg))
		$videoInfo['max_wind']=$arg;
	if (GetArg('type', $arg))
		$videoInfo['type']=$arg;
	elseif (GetArg('has_voip', $arg)) {
		if ($arg=='N')
			$videoInfo['type']='VIDEO';
		elseif ($arg=='Y')
			$videoInfo['type']='BOTH';
			
	}
		
	if (GetArg('size', $arg)) {
		if ($arg=='0') {
			$videoInfo['width']='0';
			$videoInfo['height']='0';
			$videoInfo['bandwidth']='0';
		} else {						
			$words=explode('-', $arg);
			if (count($words)>2) {
				$videoInfo['width']=$words[0];
				$videoInfo['height']=$words[1];
				$videoInfo['bandwidth']=$words[2];
			}
		}
	}
	
	if (GetArg('audio_rate', $arg)) {
		$videoInfo['audio_rate']=$arg;
	}
		
	if ($cmd=='SET_VIDEO') {
		if ($video->Update($videoInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $video->GetErrorMsg(), 'Update');
	} else {
		
		if (!isset($videoInfo['name']) || $videoInfo['name']=='')
			API_EXIT(API_ERR, 'Missing name');
		
		if (!isset($videoInfo['url']) || $videoInfo['url']=='')
			API_EXIT(API_ERR, 'Missing url');

		if ($video->Insert($videoInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $video->GetErrorMsg(), 'Insert');		
			
	}	
		
} else if ($cmd=='DELETE_VIDEO') {
			
	GetArg('id', $id);	
	if (!isset($id))	
		API_EXIT(API_ERR, "Missing video server id");
	
	$video=new VVideoServer($id);
	$video->GetValue('brand_id', $brandId);
	if ($userInfo['brand_id']!=$brandId)
		API_EXIT(API_ERR, "Not an administrator of this brand");	

	$iquery="brand_id ='".$brandId."'";
	$iquery.=" AND videoserver_id='$id'";
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
					
	if ($video->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $video->GetErrorMsg(), 'Delete');

}

?>