<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */

require_once("vobject.php");

class VStorageServer extends VObject 
{
	function VStorageServer($id=0)
	{
		$this->VObject(TB_STORAGESERVER);
		$this->SetRowId($id);
	}
}

?>