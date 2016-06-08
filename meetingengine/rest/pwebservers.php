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
class PWebServers extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PWebServers()
	{
		$this->PRestAPI("webservers");
		$this->mBeginTag="<!--BEGIN_WEBSERVERS-->";
		$this->mEndTag="<!--END_WEBSERVERS-->";
		$this->mTableName=TB_WEBSERVER;		

		$this->mSynopsis="WebServers is a collection of all web conference servers of the site.";
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
		include_once("rest/pwebserver.php");
		$xml=PWebServer::ReplaceObjectTags($objInfo, $sourceXml);
		return $xml;
	}

}


?>