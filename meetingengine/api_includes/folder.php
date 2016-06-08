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

// this file is not being used now and not tested yet.

require_once("dbobjects/vfolder.php");
	
$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in.");

$folderInfo=array();


if ($cmd=='SET_FOLDER' || $cmd=='DELETE_FOLDER') {
	GetArg('id', $folderId);
	if ($folderId=='') {
		return API_EXIT(API_ERR, "Missing input parameter 'id'.");
	}
	$folder=new VFolder($folderId);
	$folder->GetValue('owner_id', $ownerId);
	if ($ownerId!=$memberId)
		return API_EXIT(API_ERR, "Not authorized.");
	
	
} else if ($cmd=='ADD_FOLDER') {
	GetArg('name', $folderName);
	$folder=new VFolder();
	$folderInfo['name']=$folderName;
	$folderInfo['owner_id']=$memberId;
	$folderInfo['brand_id']=$memberBrand;
}

if (GetArg('name', $arg))
	$folderInfo['name']=$arg;	

if ($cmd=='SET_FOLDER') {
	if ($folder->Update($folderInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $folder->GetErrorMsg());
} else if ($cmd=='ADD_FOLDER') {
	if ($folder->Insert($folderInfo)!=ERR_NONE)
		return API_EXIT(API_ERR, $folder->GetErrorMsg());
} else if ($cmd=='DELETE_FOLDER') {
		
	$query="folder_id='$folderId'";
	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
	if ($errMsg!='')
		return API_EXIT(API_ERR, $errMsg);
		
	// move all meetings in the folder to the top folder (id=0)
	$aMeetingInfo=array();
	$aMeetingInfo['folder_id']=0;		
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$aMeeting=new VMeeting($row['id']);
		$aMeeting->Update($aMeetingInfo);
	}
		
	if ($folder->Drop()!=ERR_NONE)
		return API_EXIT(API_ERR, $folder->GetErrorMsg());

}
?>