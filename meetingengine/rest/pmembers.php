<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("rest/pmember.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PMembers extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PMembers()
	{
		$this->PRestAPI("members");
		$this->mBeginTag="<!--BEGIN_MEMBERS-->";
		$this->mEndTag="<!--END_MEMBERS-->";
		$this->mTableName=TB_USER;
		$this->mSynopsis="Members is a collection of all members of the site.";
		$this->mMethods="GET";
		$this->mRequired=array();
		$this->mOptional=array(
			"start" => "Index for the starting user (0 for the first member.) Default is 0.",
			"count" => "The number of users to return. Default (and maximum) is ".$this->mMaxItems.".",
			"group_id" => "Group id. Returns only members of the group.",
			"license_code" => "License code. Returns only members that match the license code.",
			"from_date" => "YYYY-MM-DD. Returns only members created on or after from_date.",
			"to_date" => "YYYY-MM-DD. Returns only members created on or before to_date.",
			"active" => "Y or N. Returns only active (Y) or inactive (N) members.",
		);
		$this->mReturned=array(
			'[START]' => "The starting index for the records returned.",
			'[NEXT]' => "The starting index for requesting the next set of records. '-1' is returned if no more records are available.",
			);		

	}
	function VerifyInput()
	{			
		if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
		{
			return("Access is not authorized.");		
		}
		return '';
	}
	function GetSelectQuery()
	{
		$query="brand_id='".$this->mBrandId."'";
		if (isset($_GET['group_id'])) {
			$query.=" AND (group_id='".$_GET['group_id']."')";
		}
		if (isset($_GET['active'])) {
			$query.=" AND (active='".$_GET['active']."')";
		}
		if (isset($_GET['from_date'])) {
			$fromDate=$_GET['from_date']." 00:00:00";
			$query.=" AND (create_date>='".$fromDate."')";
		}
		if (isset($_GET['to_date'])) {
			$toDate=$_GET['to_date']." 24:00:00";
			$query.=" AND (create_date<='".$toDate."')";
		}
		if (isset($_GET['license_code'])) {			
			$errMsg=VObject::Find(TB_LICENSE, 'code', $_GET['license_code'], $licenseInfo);
			if (isset($licenseInfo['id']))
				$query.=" AND (license_id='".$licenseInfo['id']."')";
		}
		return $query;
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{		
		return PMember::ReplaceObjectTags($objInfo, $sourceXml);
	}
/*
	function Get()
	{
		$respXml=$this->LoadResponseXml();
		$userXml=$this->GetSubXml('<!--BEGIN_MEMBERS-->', '<!--END_MEMBERS-->', $respXml);
		
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
			
		$errMsg=VObject::SelectAll(TB_USER, $query, $result, $offset, $count, "*", "id", true);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$respXml=str_replace("[OFFSET]", $offset, $respXml);
		$newXml='';
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$userId=$row['access_id'];
			$rowXml=str_replace("[MEMBER_ID]", $userId, $userXml);
			$rowXml=str_replace("[LOGIN]", $row['login'], $rowXml);
			$newXml.=$rowXml;		
		}
				
		$retXml=str_replace($userXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}
*/
}


?>