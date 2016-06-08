<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
/**
 * @package     VShow
 * @access      public
 */
class VMedia extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VMedia($id=0)
	{
		$this->VObject(TB_MEDIA);
		$this->SetRowId($id);
	}
	/**
	 * Return the url of the image
	 * @return integer error code
	 */
	function GetUrl($siteUrl, &$imageUrl)
	{	
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		if ($this->GetValue('file_name', $fileName)!=ERR_NONE) {
			return $this->GetErrorCode();
		}
	
		$url=VWebServer::AddPaths($siteUrl, DIR_MEDIA);
		$url.=$fileName;
		return '';		
	}
}


?>