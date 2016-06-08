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
class PTeleServers extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PTeleServers()
	{
		$this->PRestAPI("teleservers");
		$this->mBeginTag="<!--BEGIN_TELESERVERS-->";
		$this->mEndTag="<!--END_TELESERVERS-->";
		$this->mTableName=TB_TELESERVER;		

		$this->mSynopsis="TeleServers is a collection of all teleconference servers of the site.";
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
		include_once("rest/pteleserver.php");
		$xml=PTeleServer::ReplaceObjectTags($objInfo, $sourceXml);
		return $xml;
	}

}


?>