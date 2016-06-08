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
class VImage extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VImage($id=0)
	{
		$this->VObject(TB_IMAGE);
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
	
		$filePath=VImage::GetFilePath($fileName);
		$imageUrl=VWebServer::AddPaths($siteUrl, $filePath);
		$imageUrl=VWebServer::AddPaths($siteUrl, VImage::GetFileUrl($fileName));
		return '';		
	}
	/**
	 * @return string the local file path of the image
	 */
	function GetFilePath($fileName, $mustExist=true)
	{
		// version 2.2.01 or above saves image files to DB_DIR_PATH
		// earlier version saves images to DIR_IMAGE

		if (defined('DB_DIR_PATH') && DB_DIR_PATH!='') {
			$path=DB_DIR_PATH.$fileName;
		} else
			$path=DIR_IMAGE.$fileName;
		return $path;		
	}
	/**
	 * @return string the local file path of the image
	 */
	static function GetFileUrl($fileName)
	{
		if (defined('DB_DIR_URL') && DB_DIR_PATH!='')
			$path=DB_DIR_URL.$fileName;
		else
			$path=DIR_IMAGE.$fileName;
		return $path;
	}
}


?>
