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
class PRemoteServers extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PRemoteServers()
	{
		$this->PRestAPI("remoteservers");
		$this->mBeginTag="<!--BEGIN_REMOTESERVERS-->";
		$this->mEndTag="<!--END_REMOTESERVERS-->";
		$this->mTableName=TB_REMOTESERVER;		

		$this->mSynopsis="RemoteServers is a collection of all remote control servers of the site.";
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
		}
		return '';
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		include_once("rest/premoteserver.php");
		$xml=PRemoteServer::ReplaceObjectTags($objInfo, $sourceXml);
		return $xml;
	}

}


?>