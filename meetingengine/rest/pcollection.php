<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vobject.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PCollection extends PRestAPI 
{
	/**
	 * @access protected
	 * @var string
	 */
	var $mBeginTag='';
	/**
	 * @access protected
	 * @var string
	 */
	var $mEndTag='';
	/**
	 * @access protected
	 * @var integer
	 */
	var $mMaxItems=1000;
	/**
	 * @access protected
	 * @var integer http status code
	 */
	/**
	 * @access protected
	 * @var string
	 */
	var $mTableName='';
	/**
	 * @access protected
	 * @var integer http status code
	 */
	/**
	 * Constructor
	 */	
	function PCollection()
	{
	}
	/**
	 * @access protected
	 * @var string error message
	 */	
	function VerifyInput()
	{
		return '';
	}
	
	function GetSelectQuery()
	{
		$query="brand_id='".$this->mBrandId."'";	
		return $query;
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
		return $sourceXml;
	}

	function Get()
	{
		$respXml=$this->LoadResponseXml();
		$subXml=$this->GetSubXml($this->mBeginTag, $this->mEndTag, $respXml);
		
		$start=0;
		$count=$this->mMaxItems;
		$brandName='';
		if (isset($_GET['start']))
			$start=$_GET['start'];
		if (isset($_GET['count'])) {
			$count=$_GET['count'];
			if ($count>$this->mMaxItems)
				$count=$this->mMaxItems;
		}
		
		$errMsg=$this->VerifyInput();
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage($errMsg);
			return '';
		}
			
		$query=$this->GetSelectQuery();
		if ($query=='')
			return '';
			
		$errMsg=VObject::SelectAll($this->mTableName, $query, $result, $start, $count, "*", "id", true);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		$num_rows = mysql_num_rows($result);
		
		if ($num_rows==0)
			$next=-1;
		else			
			$next=$start+$num_rows;		
			
		$respXml=str_replace("[START]", $start, $respXml);
		$respXml=str_replace("[NEXT]", $next, $respXml);
		$newXml='';
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {			
			$newXml.=$this->ReplaceObjectTags($row, $subXml);
		}
				
		$retXml=str_replace($subXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}
}


?>