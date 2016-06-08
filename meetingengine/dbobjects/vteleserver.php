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
class VTeleServer extends VObject 
{

	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VTeleServer($id=0)
	{
		$this->VObject(TB_TELESERVER);
		$this->SetRowId($id);
	}
}

?>