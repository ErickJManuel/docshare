<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PWebServer extends PRestAPI 
{
	/**
	 * Constructor
	 */	
	function PWebServer()
	{
		$this->PRestAPI("webserver");
		$this->mSynopsis="WebServer lets you get, add, modify, and delete a web conference server.";
		$this->mMethods="GET,POST,PUT,DELETE";
		$this->mRequired=array(
			'id' => "Webserver id. Required for GET, PUT, or DELETE.",
			'name' => "Name of the server. Required for POST.",
			'url' => "Webserver URL. Required for POST.",
			'access_key' => "Webserver access key. Required for POST.",
			'max_connections' => "Max. number of connections on this server before caching servers are used. Set the value to '-1' to disable caching. Set the value to '0' to always use caching.",
			'cachingserver_ids' => "A list of caching servers' webserver id, separated by commas.",
			);
		$this->mOptional=array(
			);
		
	}
	
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$subXml=$this->GetSubXml("<!--BEGIN_CACHINGSERVER-->", "<!--END_CACHINGSERVER-->", $sourceXml);
		$xml=str_replace("[WEBSERVER_ID]", $objInfo['id'], $sourceXml);
		$xml=str_replace("[WEBSERVER_NAME]", htmlspecialchars($objInfo['name']), $xml);
		$xml=str_replace("[URL]", $objInfo['url'], $xml);
		$xml=str_replace("[ACCESS_CODE]", $objInfo['password'], $xml);
		$maxConn='-1';
		$cachingXml='';
		if (isset($objInfo['max_connections'])) {
			$maxConn=$objInfo['max_connections'];
			$slaves=explode(",", $objInfo['slave_ids']);
			if (is_array($slaves)) {
				foreach ($slaves as $aslave) {
					$cachingXml.=str_replace("[CACHINGSERVER_ID]", $aslave, $subXml);
				}
			}
		}
		$xml=str_replace($subXml, $cachingXml, $xml);		
		$xml=str_replace("[MAX_CONNECTIONS]", $maxConn, $xml);
		return $xml;
	}

	function Get($id='')
	{
		$respXml=$this->LoadResponseXml();
		
		$query="brand_id='".$this->mBrandId."'";
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
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $itemInfo);
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
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $itemInfo);
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$itemId=$id;		
		$cmd='SET_WEB';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;
		require_once('api_includes/web.php');
		
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
			
		$cmd='ADD_WEB';
		$api_error_message='';
		$api_exit=false;

		require_once('api_includes/web.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
		
		// $web is created in the include file above
		if ($web->GetValue('id', $id)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($web->GetErrorMsg());
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
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $itemInfo);
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$cmd='DELETE_WEB';
		$api_error_message='';
		$api_exit=false;
		require_once('api_includes/web.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->GetMessageXml("Record deleted.");

	}
}


?>