<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");

class VConversionServer extends VObject 
{
	function VConversionServer($id=0)
	{
		$this->VObject(TB_CONVERSIONSERVER);
		$this->SetRowId($id);
	}
}

?>