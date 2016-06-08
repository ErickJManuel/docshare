<?php
/**
 * Persony Web Conferencing 2.0
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
/**
 * @package     VShow
 * @access      public
 */
class VQuestion extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VQuestion($id=0)
	{
		$this->VObject(TB_QUESTION);
		$this->SetRowId($id);
	}
}


?>