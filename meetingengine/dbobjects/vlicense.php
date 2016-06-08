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
class VLicense extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VLicense($id=0)
	{
		$this->VObject(TB_LICENSE);
		$this->SetRowId($id);
	}
	
	static function EncryptLicense($license, $accountName) {
		return md5($license.$accountName.'qad');
	}

}

?>