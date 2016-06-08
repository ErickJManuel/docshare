<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vuser.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PGroup extends PRestAPI 
{
	/**
	 * Constructor
	 */	
	function PGroup()
	{
		$this->PRestAPI("group");
		$this->mSynopsis="A group contains a subset of members of the site. All group members share certain properties. A member must belong to exactly one group.";
		$this->mMethods="GET,POST,PUT,DELETE";
		$this->mRequired=array(
			'id' => "Group id. Required for GET, PUT, or DELETE.",
			);
		$this->mOptional=array(
			'name' => "Name of the group.",
			'description' => "Description of the group.",
			'webserver_id' => "Web conference hosting account id. The id must be for a valid web conference hosting account (see Administration/Hosting.) If the paramter is not provided with a POST request, the default web conference hosting account id will be assigned.",
			'videoserver_id' => "Video conference hosting account id. The id must be for a valid video conference hosting account.",
			'remoteserver_id' => "Remote control hosting account id. The id must be for a valid remote control hosting account.",
			'teleserver_id' => "Teleconference hosting account id. The id must be for a valid teleconference hosting account.",
			);
		
	}

	function Get($groupId='')
	{
		$respXml=$this->LoadResponseXml();
		
		$query="brand_id='".$this->mBrandId."'";
		if ($groupId=='')
			if (isset($_GET['id']))
				$groupId=$_GET['id'];
		
		if ($groupId!='')
			$query.=" AND id='".$groupId."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}
		
		// must be an ADMIN member of the brand or a HOST member that belongs to the group
		if (GetSessionValue('member_id')=='' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
		{
			$this->SetStatusCode(PCODE_UNAUTHORIZED);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		} else if (GetSessionValue('member_perm')!='ADMIN') {
			$member=new VUser(GetSessionValue('member_id'));
			$member->Get($memberInfo);
			
			if (!isset($memberInfo['id'])) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Member record not found or not available.");	
			}
			
			if ($memberInfo['group_id']!=$groupId) {
				$this->SetStatusCode(PCODE_UNAUTHORIZED);
				$this->SetErrorMessage("Access is not authorized.");
				return '';					
			}
		}
			
		$groupInfo=array();
		$errMsg=VObject::Select(TB_GROUP, $query, $groupInfo);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		if (!isset($groupInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		$respXml=PGroup::ReplaceObjectTags($groupInfo, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $respXml;
	}
	function ReplaceObjectTags($groupInfo, $respXml)
	{
		$respXml=str_replace("[GROUP_ID]", $groupInfo['id'], $respXml);
		$respXml=str_replace("[GROUP_NAME]", htmlspecialchars($groupInfo['name']), $respXml);
		$respXml=str_replace("[DESCRIPTION]", htmlspecialchars($groupInfo['description']), $respXml);
		$respXml=str_replace("[WEBSERVER_ID]", $groupInfo['webserver_id'], $respXml);
		$respXml=str_replace("[VIDEOSERVER_ID]", $groupInfo['videoserver_id'], $respXml);
		$respXml=str_replace("[REMOTESERVER_ID]", $groupInfo['remoteserver_id'], $respXml);
//		$respXml=str_replace("[FREE_AUDIO_CONF]", $groupInfo['free_audio_conf'], $respXml);
		$respXml=str_replace("[TELESERVER_ID]", $groupInfo['teleserver_id'], $respXml);
		return $respXml;
	}
	function Update($groupId='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($groupId=='')
			if (isset($_POST['id']))
				$groupId=$_POST['id'];
		
		if ($groupId!='') {
			$query.=" AND id='".$groupId."'";
		} else {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}

		$aGroupInfo=array();
		$errMsg=VObject::Select(TB_GROUP, $query, $aGroupInfo);
		if (!isset($aGroupInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
				
		$cmd='SET_GROUP';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;
		require_once('api_includes/group.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->Get($groupId);

	}
	function Insert()
	{
		global $api_error_message, $api_exit, $VARGS;
			
		$cmd='ADD_GROUP';
		$api_error_message='';
		$api_exit=false;

		require_once('api_includes/group.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
		
		// $group is created in the include file above
		if ($group->GetValue('id', $groupdId)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($group->GetErrorMsg());
			return '';
		}
		return $this->Get($groupdId);

	}
	function Delete($groupId='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($groupId=='')
			if (isset($_POST['id']))
				$groupId=$_POST['id'];
		
		if ($groupId!='') {
			$query.=" AND id='".$groupId."'";
		} else {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}

		$groupInfo=array();
		$errMsg=VObject::Select(TB_GROUP, $query, $groupInfo);
		if (!isset($groupInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$theGroupId=$groupId;		
		$cmd='DELETE_GROUP';
		$api_error_message='';
		$api_exit=false;
		require_once('api_includes/group.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->GetMessageXml("Record deleted.");

	}
}


?>