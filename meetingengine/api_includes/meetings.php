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


require_once("dbobjects/vfolder.php");
require_once("dbobjects/vmeeting.php");

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Member is not signed in.");
	

if ($cmd=='SET_MEETINGS') {
	$count=count($_POST['selected_meetings']);
	if ($count==0)
		return API_EXIT(API_ERR, "Empty selection.");
	GetArg('folder_id', $folderId);
	if ($folderId=='-1') {
		GetArg('folder_name', $folderName);
		
		if ($folderName=='')
			return API_EXIT(API_ERR, "Folder name cannot be empty.");
		
		$query="name='".VObject::MyAddSlashes($folderName)."' AND brand_id='$memberBrand' AND owner_id='$memberId'";
		VObject::Count(TB_FOLDER, $query, $total);
		if ($total>0)
			return API_EXIT(API_ERR, "The folder '$folderName' already exists.");		
		
		$folder=new VFolder();
		$folderInfo=array();
		$folderInfo['name']=$folderName;
		$folderInfo['brand_id']=$memberBrand;
		$folderInfo['owner_id']=$memberId;
		
		$folder->Insert($folderInfo);
		$folder->GetValue('id', $folderId);
	}
	$meetingInfo=array();
	$meetingInfo['folder_id']=$folderId;
	

	for ($i=0; $i<$count; $i++){
		
		$meetingId = $_POST['selected_meetings'][$i];
		$meeting=new VMeeting($meetingId);
		$meeting->Get($currentInfo);
		
		if (!isset($currentInfo['id']))
			continue;
		
		// check if the member is the host of the meeting
		if ($currentInfo['host_id']!=$memberId) {
				
			// check if the member is an admin of the brand
			if ($memberPerm!='ADMIN' || $memberBrand!=$currentInfo['brand_id']) 
			{
				return API_EXIT(API_ERR, "Not authorized.");
			}
		}	

		$meeting->Update($meetingInfo);
	}
/*	
	if (GetArg('return', $page))
	{
		$page=VWebServer::DecodeDelimiter1($page);
		$page.="&folder_id=".$folderId;
		if (SID!='') {
			if (strpos($page, '?')===false)
				$page.="?".SID;
			else
				$page.="&".SID;
		}
		header("Location: $page");
		exit();
	}
*/
}
?>