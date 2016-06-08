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
class PStorageServers extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PStorageServers()
	{
		$this->PRestAPI("storageservers");
		$this->mBeginTag="<!--BEGIN_STORAGESERVERS-->";
		$this->mEndTag="<!--END_STORAGESERVERS-->";
		$this->mTableName=TB_STORAGESERVER;		

		$this->mSynopsis="StorageServers is a collection of all storage servers of the site.";
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
		include_once("rest/pstorageserver.php");
		$xml=PStorageServer::ReplaceObjectTags($objInfo, $sourceXml);
		return $xml;
	}

}


?>