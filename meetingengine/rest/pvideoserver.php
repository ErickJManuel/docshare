<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vvideoserver.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PVideoServer extends PRestAPI 
{
	/**
	 * Constructor
	 */	
	function PVideoServer()
	{
		$this->PRestAPI("videoserver");
		$this->mSynopsis="VideoServer lets you get, add, modify, and delete a video conference server.";
		$this->mMethods="GET,POST,PUT,DELETE";
		$this->mRequired=array(
			'id' => "VideoServer id. Required for GET, PUT, or DELETE.",
			'name' => "Name of the server. Required for POST.",
			'url' => "VideoServer URL. Required for POST.",
			);
		$this->mOptional=array(
			'bandwidth' => "Video bandwidth in kbps. Set this to 0 to use default (120kbps.)",
			'width' => "Video window width. Set this to 0 to use default (176.)",
			'height' => "Video window height. Set this to 0 to use default (132.)",
			'max_wind' => "Maximum number of video windows. Set this to 0 to use default (6.)",
			'audio_rate' => "Voice over IP sample rate in KHz. Set this to 0 to use default (22 KHz)",
			'has_voip' => "Does the conference has voice over IP? (Y, N.) Default is 'Y'.",
			);
		
	}
	
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		$xml=str_replace("[VIDEOSERVER_ID]", $objInfo['id'], $sourceXml);
		$xml=str_replace("[VIDEOSERVER_NAME]", htmlspecialchars($objInfo['name']), $xml);
		$xml=str_replace("[URL]", $objInfo['url'], $xml);
		$xml=str_replace("[BANDWIDTH]", $objInfo['bandwidth'], $xml);
		$xml=str_replace("[WIDTH]", $objInfo['width'], $xml);
		$xml=str_replace("[HEIGHT]", $objInfo['height'], $xml);
		$xml=str_replace("[MAX_WINDOW]", $objInfo['max_wind'], $xml);
		$xml=str_replace("[AUDIO_RATE]", $objInfo['audio_rate'], $xml);
		if ($objInfo['type']=='VIDEO')
			$hasVoip='N';
		else
			$hasVoip='Y';
		$xml=str_replace("[HAS_VOIP]", $hasVoip, $xml);

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
		$errMsg=VObject::Select(TB_VIDEOSERVER, $query, $itemInfo);
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
		$errMsg=VObject::Select(TB_VIDEOSERVER, $query, $itemInfo);
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$itemId=$id;		
		$cmd='SET_VIDEO';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;
		require_once('api_includes/video.php');
		
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
			
		$cmd='ADD_VIDEO';
		$api_error_message='';
		$api_exit=false;

		require_once('api_includes/video.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
		
		// $video is created in the include file above
		if ($video->GetValue('id', $id)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($video->GetErrorMsg());
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
		$errMsg=VObject::Select(TB_VIDEOSERVER, $query, $itemInfo);
		if (!isset($itemInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("Record not found.");
			return '';
		}
		
		$cmd='DELETE_VIDEO';
		$api_error_message='';
		$api_exit=false;
		require_once('api_includes/video.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->GetMessageXml("Record deleted.");

	}
}


?>