<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vuser.php");
require_once("rest/pmeeting.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PMeetings extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PMeetings()
	{
		$this->PRestAPI("meetings");
		$this->mBeginTag="<!--BEGIN_MEETINGS-->";
		$this->mEndTag="<!--END_MEETINGS-->";
		$this->mTableName=TB_MEETING;
		$this->mSynopsis="Meetings is a collection of all meetings of the site.";
		$this->mMethods="GET";
		$this->mRequired=array();
		$this->mOptional=array(
			"member_id" => "Member id. Returns only meetings that belong to the member.",
			"user_id" => "Same as member_id. (Deprecated)",
			"login" => "Member login name. Returns only meetings that belong to the member.",
			"client_data" => "Client data (up to 63 characters). Returns only meetings that contain the client data.",
			"start" => "Index for the starting meeting (0 for the first meeting.) Default is 0.",
			"count" => "The number of meetings to return. Default (and maximum) is ".$this->mMaxItems.".",
		);
		$this->mReturned=array(
			'[START]' => "The starting index for the records returned.",
			'[NEXT]' => "The starting index for requesting the next set of records. '-1' is returned if no more records are available.",
			);		

	}
	function VerifyInput()
	{
		$userId='';
		if (isset($_GET['member_id']))
			$userId=$_GET['member_id'];
		elseif (isset($_GET['user_id']))
			$userId=$_GET['user_id'];
				
		if ($userId!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			return("Access is not authorized.");
			return '';			
		}
		return '';
	}	
	function GetSelectQuery()
	{
		$query="brand_id='".$this->mBrandId."'";
		
		if (isset($_GET['member_id']))
			$userId=$_GET['member_id'];
		elseif (isset($_GET['user_id']))
			$userId=$_GET['user_id'];
				
		if (isset($_GET['login'])) {
			$this->GetArg('login', $login);
			$userInfo=array();
			$aquery="brand_id='".$this->mBrandId."' AND LOWER(login)='".addslashes(strtolower($login))."'";
			$errMsg=VObject::Select(TB_USER, $aquery, $userInfo);			

//			VObject::Find(TB_USER, 'login', $_GET['login'], $userInfo);
			if (!isset($userInfo['id'])) {
				$this->SetStatusCode(PCODE_NOT_FOUND);
				$this->SetErrorMessage("User not found.");
				return '';				
			}
			
			$query.=" AND host_id='".$userInfo['id']."'";
		} elseif (isset($userId)) {
			
			$userInfo=array();
			VObject::Find(TB_USER, 'access_id', $userId, $userInfo);
			if (!isset($userInfo['id'])) {
				$this->SetStatusCode(PCODE_NOT_FOUND);
				$this->SetErrorMessage("User not found.");
				return '';				
			}
			
			$query.=" AND host_id='".$userInfo['id']."'";
		}
		if (isset($_GET['client_data'])) {
			$query.=" AND client_data='".$_GET['client_data']."'";
		}
		return $query;
	}
	
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		return PMeeting::ReplaceObjectTags($objInfo, $sourceXml);
	}
/*
	function Get()
	{
		$respXml=$this->LoadResponseXml();
		$userXml=$this->GetSubXml('<!--BEGIN_MEETINGS-->', '<!--END_MEETINGS-->', $respXml);
		
		$offset=0;
		$count=100;
		$brandName='';
		if (isset($_GET['start']))
			$offset=$_GET['start'];
		if (isset($_GET['count'])) {
			$count=$_GET['count'];
			if ($count>100)
				$count=100;
		}			
		$query="brand_id='".$this->mBrandId."'";	
		if (isset($_GET['user_id'])) {
			
			$userInfo=array();
			VObject::Find(TB_USER, 'access_id', $_GET['user_id'], $userInfo);
			if (!isset($userInfo['id'])) {
				$this->SetStatusCode(PCODE_NOT_FOUND);
				$this->SetErrorMessage("User not found.");
				return '';				
			}
			
			$query.=" AND host_id='".$userInfo['id']."'";
		}
			
		$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, $offset, $count, "*", "id", true);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$respXml=str_replace("[OFFSET]", $offset, $respXml);
		$newXml='';
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rowXml=str_replace("[MEETING_ID]", $row['access_id'], $userXml);
			$rowXml=str_replace("[MEETING_TITLE]", htmlspecialchars($row['title']), $rowXml);
			$newXml.=$rowXml;		
		}
				
		$retXml=str_replace($userXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}
*/
}


?>