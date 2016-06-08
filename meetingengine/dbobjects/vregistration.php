<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
require_once("vregform.php");
/**
 * @package     VShow
 * @access      public
 */
class VRegistration extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VRegistration($id=0)
	{
		$this->VObject(TB_REGISTRATION);
		$this->SetRowId($id);
	}
	
	/**
	* @static
	* @param array 
	* @return string
	*/		
	static function GetXML($regInfo, $formInfo)
	{
		$xml="<registration id=\"".$regInfo['id']."\" ";
		$xml.="fullname=\"${regInfo['name']}\" ";
		$xml.="email=\"${regInfo['email']}\" ";
		if ($formInfo) {
			for ($i=1; $i<=VRegForm::$maxFields; $i++) 
			{
				$key="key_".$i;
				$field="field_".$i;
				$fieldKey='';
				if (isset($formInfo[$key])) 
				{
					$keyVal=$formInfo[$key];
					if ($keyVal!='')
						$fieldKey=VRegForm::FormKeyToXmlName($keyVal);
				}
				
				if ($fieldKey!='' && isset($regInfo[$field]))
					$xml.="$fieldKey=\"${regInfo[$field]}\" ";
			}
		}
		$xml.="/>";
		
		return $xml;
	}
	/**
	* @static
	* @param array 
	* @return string
	*/		
	static function GetText($regInfo, $formInfo)
	{
//		$text="id=".$regInfo['id']."\n";
		$text='';
		for ($i=1; $i<=VRegForm::$maxFields; $i++) 
		{
			$key="key_".$i;
			$field="field_".$i;
			$fieldKey='';
			if (isset($formInfo[$key])) 
			{
				$keyVal=$formInfo[$key];
				if ($keyVal!='')
					$fieldKey=VRegForm::FormKeyToXmlName($keyVal);
			}
				
			if ($fieldKey!='' && isset($regInfo[$field]))
				$text.="$fieldKey=${regInfo[$field]}\n";
		}
		$text.="\n";
		
		return $text;
	}

}


?>