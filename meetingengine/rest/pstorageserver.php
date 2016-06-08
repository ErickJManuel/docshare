<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PStorageServer extends PRestAPI 
{
	/**
	 * Constructor
	 */	
	function PStorageServer()
	{
		$this->PRestAPI("storageserver");
		$this->mSynopsis="StorageServer lets you get, add, modify, and delete a storage server.";
		$this->mMethods="GET,POST,PUT,DELETE";
		$this->mRequired=array(
			'id' => "StorageServer id. Required for GET, PUT, or DELETE.",
			'name' => "Name of the server. Required for POST.",
			'server_url' => "StorageServer URL. Required for POST.",
			'access_key' => "StorageServer access key. Required for POST.",
			);
		$this->mOptional=array(
			);
		
	}
	
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$xml=str_replace("[STORAGESERVER_ID]", $objInfo['id'], $sourceXml);
		$xml=str_replace("[STORAGESERVER_NAME]", htmlspecialchars($objInfo['name']), $xml);
		$xml=str_replace("[URL]", $objInfo['url'], $xml);
		$xml=str_replace("[ACCESS_CODE]", $objInfo['access_code'], $xml);

		return $xml;
	}

	function Get($id='')
	{
		$respXml=$this->LoadResponseXml();
		
		// some server may be shared by multiple brands so need to add brand_id='0' to the query
		$query="(brand_id='".$this->mBrandId."' OR brand_id='0')";
		if ($id=='')
			if (isset($_GET['id']))
				$id=$_GET['id'];
		
		if ($id!='')
			$query.=" AND id='".$id."'";
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
			// a HOST member; make sure it belongs to the group
			$member=new VUser(GetSessionValue('member_id'));
			$member->Get($memberInfo);
			
			if (!isset($memberInfo['id'])) {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMessage("Member record not found or not available.");	
			}
			
			$group=new VGroup($memberInfo['group_id']);
			$group->Get($groupInfo);
			
			if ($groupInfo['teleserver_id']!=$id) {
				$this->SetStatusCode(PCODE_UNAUTHORIZED);
				$this->SetErrorMessage("Access is not authorized.");
				return '';					
			}
		}				
		$itemInfo=array();
		$errMsg=VObject::Select(TB_STORAGESERVER, $query, $itemInfo);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$respXml=$this->ReplaceObjectTags($itemInfo, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $respXml;
	}

	function Update($id='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($id=='')
			if (isset($_POST['id']))
				$id=$_POST['id'];
		
		if ($id!='') {
			$query.=" AND id='".$id."'";
		} else {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}

		$itemInfo=array();
		$errMsg=VObject::Select(TB_STORAGESERVER, $query, $itemInfo);
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$itemId=$id;		
		$cmd='SET_STORAGE';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;
		require_once('api_includes/storage.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->Get($itemId);

	}
	function Insert()
	{
		global $api_error_message, $api_exit, $VARGS;
		
		if (!isset($_POST['name']) || !isset($_POST['server_url']) || !isset($_POST['access_code'])) {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing a required parameter.");
			return '';
		}
			
		$cmd='ADD_STORAGE';
		$api_error_message='';
		$api_exit=false;

		require_once('api_includes/storage.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
		
		// $storage is created in the include file above
		if ($storage->GetValue('id', $id)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($storage->GetErrorMsg());
			return '';
		}
		return $this->Get($id);

	}
	function Delete($id='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($id=='')
			if (isset($_POST['id']))
				$id=$_POST['id'];
		
		if ($id!='') {
			$query.=" AND id='".$id."'";
		} else {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id.");
			return '';
		}

		$itemInfo=array();
		$errMsg=VObject::Select(TB_STORAGESERVER, $query, $itemInfo);
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$cmd='DELETE_STORAGE';
		$api_error_message='';
		$api_exit=false;
		require_once('api_includes/storage.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->GetMessageXml("Record deleted.");

	}
}


?>