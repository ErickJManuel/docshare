<?php

require_once("vobject.php");

class VLibrary extends VObject 
{
	/**
	 * VLibrary Contructor
	 *
	 * @param int $id the library id
	 *
	 */
	function VLibrary($id=0)
	{
		$this->VObject(TB_LIBRARY);
		$this->SetRowId($id);
	}
}

?>