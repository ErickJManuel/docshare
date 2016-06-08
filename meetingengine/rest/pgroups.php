<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("rest/pgroup.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PGroups extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PGroups()
	{
		$this->PRestAPI("groups");
		$this->mBeginTag="<!--BEGIN_GROUPS-->";
		$this->mEndTag="<!--END_GROUPS-->";
		$this->mTableName=TB_GROUP;		

		$this->mSynopsis="Groups is a collection of all groups of the site.";
		$this->mMethods="GET";
		$this->mRequired=array();
		$this->mOptional=array(
			"start" => "Index for the starting group (0 for the first group.) Default is 0.",
			"count" => "The number of groups to return. Default (and maximum) is ".$this->mMaxItems.".",
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
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		return PGroup::ReplaceObjectTags($objInfo, $sourceXml);
	}
/*

	function Get()
	{
		$respXml=$this->LoadResponseXml();
		$userXml=$this->GetSubXml('<!--BEGIN_GROUPS-->', '<!--END_GROUPS-->', $respXml);
		
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
			
		$errMsg=VObject::SelectAll(TB_GROUP, $query, $result, $offset, $count, "*", "id", true);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$respXml=str_replace("[OFFSET]", $offset, $respXml);
		$newXml='';
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rowXml=str_replace("[LINK]", $row['id'], $userXml);
			$rowXml=str_replace("[GROUP_ID]", $row['id'], $userXml);
			$rowXml=str_replace("[GROUP_NAME]", $row['name'], $rowXml);
			$newXml.=$rowXml;		
		}
				
		$retXml=str_replace($userXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}
*/
}


?>