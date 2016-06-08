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
class VRegForm extends VObject 
{
	public static $maxFields=16;
	public static $allFields=array("", "[EMAIL]", "[FULLNAME]", "[FIRSTNAME]", "[LASTNAME]", "[TITLE]", "[COMPANY]", "[ORG]", 
		"[STREET]", "[CITY]", "[STATE]", "[ZIP]", "[COUNTRY]", "[PHONE]", "[CUSTOM]");
	public static $requiredDefFields="key_1,key_2";
	public static $defaultFields=array("[EMAIL]", "[FULLNAME]", "[TITLE]", "[COMPANY]", "[STREET]", "[CITY]", "[STATE]", "[ZIP]", "[COUNTRY]", "[PHONE]");
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VRegForm($id=0)
	{
		$this->VObject(TB_REGFORM);
		$this->SetRowId($id);
	}
	/**
	* Get default form info
	* @static
	* @param string error message
	*/	
	static function GetDefault(&$formInfo)
	{
/*
		$form=new VRegForm();
		if (VObject::Find(TB_REGFORM, 'author_id', '0', $formInfo)!=ERR_NONE) {
			return($form->GetErrorMsg());
		}
*/
		$max=self::$maxFields;
		for ($i=1; $i<=$max; $i++) {
			$key="key_$i";
			if (isset(self::$defaultFields[$i-1]))
				$formInfo[$key]=self::$defaultFields[$i-1];
			else
				$formInfo[$key]='';
		}
		$formInfo['required_fields']=self::$requiredDefFields;
		$formInfo['auto_reply']='Y';
		$formInfo['id']='0';
		
		return '';
	}
	/**
	 *
	 */		
	static function FormKeyToXmlName($key)
	{
		if ($key=='[ORG]' || $key=='[COMPANY]')
			return 'companyname';
		if ($key=='[PHONE]')
			return 'phonenumber';
	
		// Remove predefined keys' [ ]
		$key=str_replace('[','', $key);
		$key=str_replace(']', '', $key);
		
		// the key may include an option list (e.g. key=choice1|choice2|choice3)
		// show only the key part
		$items=explode("=", $key);
		return strtolower($items[0]);

	}

}


?>