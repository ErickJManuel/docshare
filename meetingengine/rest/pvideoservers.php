<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PVideoServers extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PVideoServers()
	{
		$this->PRestAPI("videoservers");
		$this->mBeginTag="<!--BEGIN_VIDEOSERVERS-->";
		$this->mEndTag="<!--END_VIDEOSERVERS-->";
		$this->mTableName=TB_VIDEOSERVER;		

		$this->mSynopsis="VideoServers is a collection of all video conference servers of the site.";
		$this->mMethods="GET";
		$this->mRequired=array();
		$this->mOptional=array(
		);
		$this->mReturned=array(
		);

	}
	function VerifyInput()
	{				
		if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
		{
			return("Access is not authorized.");
			return '';			
		}
		return '';
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		include_once("rest/pvideoserver.php");
		$xml=PVideoServer::ReplaceObjectTags($objInfo, $sourceXml);
		return $xml;
	}

}


?>