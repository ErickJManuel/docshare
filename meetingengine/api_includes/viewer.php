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

require_once("dbobjects/vimage.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vviewer.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);
	
GetArg('id', $viewerId);

// this is to fix a bug. should remove this later.
//if ($viewerId==1)
//	$viewerId=0;

$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if (($memberPerm!='ADMIN' || $memberBrand!=$userInfo['brand_id']) && $userInfo['login']==VUSER_GUEST)
	return API_EXIT(API_ERR, "You cannot set the properties of a default user");

if ($viewerId==0 || $viewerId==$userInfo['viewer_id']) {
	// OK to set own viewer
	
} else {
	// get the user's brand viewer id
	$brand=new VBrand($userInfo['brand_id']);
	$brand->GetValue('viewer_id', $brandViewerId);
	
	if ($brandViewerId==$viewerId) {
		// check if the member is an admin of the brand
		if ($userInfo['permission']!='ADMIN') 
		{
			API_EXIT(API_ERR, "Not an administrator of the brand");
		}	
	} else {
		API_EXIT(API_ERR, "Not authorized");		
	}
}

GetArg('delete_background', $deleteBg);

$viewerInfo=array();
// if the viewer is not set or it is the default viewer, create a new viewer for this user
if ($viewerId==0) {
	if ($deleteBg!='') {
		API_EXIT(API_ERR, "The viewer that the background belongs to is not found.");
	}
	$viewer=new VViewer();
} else {
	$viewer=new VViewer($viewerId);
	if ($deleteBg!='') {
		GetArg('back_id', $backId);
	
		if ($backId==0)
			API_EXIT(API_ERR, "Background does not exist.");	
		
		$background=new VBackground($backId);
		$background->Get($backInfo);
		if (!isset($backInfo['id']))
			API_EXIT(API_ERR, "Background is not found.");	
		
		$canDelete=false;	
		// the user is allowed to delete the background if he owns the background or
		// he is an admin of the brand that owns the background and the background is not owned by a particular user
		if ($backInfo['author_id']==$userInfo['id'] ||
			($backInfo['author_id']=='0' && $userInfo['brand_id']==$backInfo['brand_id'] && $userInfo['permission']=='ADMIN'))
			$canDelete=true;
		
		if (!$canDelete)
			API_EXIT(API_ERR, "You are not allowed to delete this background.");		
		
		// delete the pict file
		if ($backInfo['onpict_id']!=0) {
			$pict=new VImage($backInfo['onpict_id']);
			$pict->GetValue('file_name', $oldFile);						
//			$oldFile=DIR_IMAGE.$oldFile;
			$oldFile=VImage::GetFilePath($oldFile);
			if (file_exists($oldFile))
				@unlink($oldFile);
				
			$pict->Drop();
		}
		if ($backInfo['offpict_id']!=0 && $backInfo['offpict_id']!=$backInfo['onpict_id']) {
			$pict=new VImage($backInfo['offpict_id']);
			$pict->GetValue('file_name', $oldFile);						
//			$oldFile=DIR_IMAGE.$oldFile;
			$oldFile=VImage::GetFilePath($oldFile);
			if (file_exists($oldFile))
				@unlink($oldFile);
			$pict->Drop();
		}
		
		
		// delete the background	
		if ($background->Drop()!=ERR_NONE)
			API_EXIT(API_ERR, $background->GetErrorMsg());
			
		// udpate the viewer
		$newInfo=array();
		$newInfo['back_id']=0;
		if ($viewer->Update($newInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $viewer->GetErrorMsg());

		return;

	}
}
/*		
if (GetArg('logo_id', $arg))
	$viewerInfo['logo_id']=$arg;
*/
if (GetArg('reset_logo', $arg) && $arg=='1') {
		
	// need to delete old logo if it exists
	$pictId=0;
	if ($viewerId!=0) {
		$viewer->GetValue('logo_id', $pictId);
	}
	
	// only delete if it is my own logo
	if ($pictId!=0) {
		$logo=new VImage($pictId);
		$logo->GetValue('file_name', $fileName);
		// check if the fileName starts with "default". note the "==0" means the index is 0 (found)
		if (strpos($fileName, "default")==0) {
		} else {
//			$oldFile=DIR_IMAGE.$oldFile;
			$oldFile=VImage::GetFilePath($fileName);
			if (file_exists($oldFile))
				unlink($oldFile);
		}
	}

	// set the logo to point to the default
	$viewerInfo['logo_id']=0;
}

if (GetArg('back_id', $arg))
	$viewerInfo['back_id']=$arg;	

if (GetArg('att_snd', $arg))
	$viewerInfo['att_snd']=$arg;
	
if (GetArg('hand_snd', $arg))
	$viewerInfo['hand_snd']=$arg;

if (GetArg('msg_snd', $arg))
	$viewerInfo['msg_snd']=$arg;
	
if (GetArg('waitmusic_url', $arg)) {
	if ($arg!='' && strpos(strtolower($arg), "http")!==0)
		$arg="http://".$arg;
	$viewerInfo['waitmusic_url']=$arg;
}

if (GetArg('send_all', $arg))
	$viewerInfo['send_all']=$arg;
	
if (GetArg('see_all', $arg)) {
	$viewerInfo['see_all']=$arg;
	if ($viewerInfo['see_all']=='N')
		$viewerInfo['send_all']='N';
}

if (GetArg('end_url', $arg)) {
	if ($arg!='' && strpos(strtolower($arg), "http")!==0)
		$arg="http://".$arg;
	$viewerInfo['end_url']=$arg;
}

if (GetArg('presenter_client', $arg))
	$viewerInfo['presenter_client']=$arg;
	
if (isset($_FILES['logo_file']['tmp_name']) && $_FILES['logo_file']['tmp_name']!='') {	
	
	$tempFile=$_FILES['logo_file']['tmp_name'];
	$srcFile=$_FILES['logo_file']['name'];
	$pictId=0;
	// check if this is my own viewer
	if ($viewerId!=0) {
		$viewer->GetValue('logo_id', $pictId);
	}
			
/*
	// check if this is my own logo
	if ($pictId!=0 && $pictId!=1) {
		$logo=new VImage($pictId);
		$logo->GetValue('file_name', $fileName);
		// check if the fileName starts with "default". note the "==0" means the index is 0 (found)
		if (strpos($fileName, "default")==0)
			$pictId=0;
	}
*/
	$errMsg=ProcessUploadImage($tempFile, $srcFile, $userInfo['id'], $pictId, '', VIEWER_LOGO_WIDTH, VIEWER_LOGO_HEIGHT, 'STRETCH');
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
	
	$viewerInfo['logo_id']=$pictId;
	
}

if (isset($_FILES['background_file']['tmp_name']) && $_FILES['background_file']['tmp_name']!='') {	
	
	$tempFile=$_FILES['background_file']['tmp_name'];
	$srcFile=$_FILES['background_file']['name'];
	$pictId=0;
	$errMsg=ProcessUploadImage($tempFile, $srcFile, $userInfo['id'], $pictId, 'jpg', BACKGROUND_WIDTH, BACKGROUND_HEIGHT, 'RESIZE_CROP');
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
		
	// create a new background
	$background=new VBackground();
	$backInfo=array();
	$backInfo['onpict_id']=$pictId;
	$backInfo['offpict_id']=$pictId;
	$backInfo['brand_id']=$userInfo['brand_id'];
	GetArg('public_background', $public);
	if ($public=='1' && $userInfo['permission']=='ADMIN') {
		// make the background available to all users of the brand
		// author_id needs to be 0 for it to be visible to other brand users.
		$backInfo['public']='Y';
		$backInfo['author_id']='0';
	} else {
		$backInfo['author_id']=$userInfo['id'];
	}
		
	$backInfo['wb_x']=$backInfo['screen_x']=$backInfo['slide_x']=BACKGROUND_WIDTH/2;
	$backInfo['wb_y']=$backInfo['screen_y']=$backInfo['slide_y']=BACKGROUND_HEIGHT/2;
	$backInfo['wb_s']=$backInfo['screen_s']=$backInfo['slide_s']='200';
		
	$fileName=basename($srcFile);
	// remove file extension from the name
	if (($pos=strrpos($fileName, "."))>0)
		$fileName=substr($fileName, 0, $pos);
	$backInfo['name']=$fileName;
	
	if ($background->Insert($backInfo)!=ERR_NONE)
		return($background->GetErrorMsg());
		
	if ($background->GetValue('id', $backgroundId)!=ERR_NONE)
		return($background->GetErrorMsg());

	$viewerInfo['back_id']=$backgroundId;
}

if ($viewerId==0) {
	if ($viewer->Insert($viewerInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $viewer->GetErrorMsg());
	
	if ($viewer->GetValue('id', $viewerId))
		API_EXIT(API_ERR, $viewer->GetErrorMsg());
	
	$newInfo=array();
	$newInfo['viewer_id']=$viewerId;
	if ($user->Update($newInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $user->GetErrorMsg());
	
} else {
		
	if ($viewer->Update($viewerInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $viewer->GetErrorMsg());
	
}


?>