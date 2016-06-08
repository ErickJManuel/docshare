<?php

require_once("vobject.php");

class VPresentation extends VObject 
{
	function VPresentation($id=0)
	{
		$this->VObject(TB_PRESENTATION);
		$this->SetRowId($id);
	}
}

?>