<?php

require_once("vobject.php");

class VPicture extends VObject 
{
	function VPicture($id=0)
	{
		$this->VObject(TB_PICTURE);
		$this->SetRowId($id);
	}
}

?>