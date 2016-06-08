<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vlicense.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PLicenses extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PLicenses()
	{
		$this->PRestAPI("licenses");
		$this->mBeginTag="<!--BEGIN_LICENSES-->";
		$this->mEndTag="<!--END_LICENSES-->";
		$this->mTableName=TB_LICENSE;		
		$this->mSynopsis=
"Licenses is a collection of all license types that can be assigned to a member. A license type defines certain capabilities, such as the meeting length or size, that a member is allowed.";
		$this->mMethods="GET";
		$this->mRequired=array();
		$this->mOptional=array(
			"license_code" => "Return only the license that matches the license_code.",
			"start" => "Index for the starting session (0 for the first session.) Default is 0.",
			"count" => "The number of sessions to return. Default and maximum is 100.",
		);
		$this->mReturned=array(
			'[START]' => "The starting index for the records returned.",
			'[NEXT]' => "The starting index for requesting the next set of records. '-1' is returned if no more records are available.",
			"[MAX_SIZE]" => "Maximal number of participants in a meeting.",
			"[MAX_LENGTH]" => "Maximal length of a meeting in minutes.",
			"[DISK_QUOTA]" => "Disk quota in bytes.",
			"[HAS_VIDEO]" => "Y if video conferencing is allowed, or N otherwise.",
			"[IS_TRIAL]" => "Y if this is a trial license, or N otherwise.",
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
require_once("dbobjects/vsession.php");

		$xml=str_replace("[LICENSE_CODE]", htmlspecialchars($objInfo['code']), $sourceXml);
		$xml=str_replace("[DESCRIPTION]", htmlspecialchars($objInfo['name']), $xml);
		$xml=str_replace("[MAX_SIZE]", htmlspecialchars($objInfo['max_att']), $xml);
		$xml=str_replace("[MAX_LENGTH]", htmlspecialchars($objInfo['meeting_length']), $xml);
		$xml=str_replace("[DISK_QUOTA]", htmlspecialchars($objInfo['disk_quota']), $xml);
		$xml=str_replace("[HAS_VIDEO]", htmlspecialchars($objInfo['video_conf']), $xml);
		$xml=str_replace("[IS_TRIAL]", htmlspecialchars($objInfo['trial']), $xml);
		
		$maxUsers=$objInfo['max_att'];
		if ($maxUsers=='0')
			$maxUsers='1';
		$xml=str_replace("[MAX_SIZE]", $maxUsers, $xml);			

		$session=new VSession($objInfo['id']);
		if ($session->IsInProgress())
			$inProgress='Y';
		else
			$inProgress='N';
		
		$xml=str_replace("[IN_PROGRESS]", $inProgress, $xml);

		return $xml;
	}
	
	function GetSelectQuery()
	{
//		$query="(brand_id='".$this->mBrandId."' OR brand_id='0') AND enabled='Y'";
		$query="enabled='Y'";
		if (isset($_GET['license_code']) ) {
			$query.=" AND code='".$_GET['license_code']."'";
		}	
		return $query;
	}

}


?>