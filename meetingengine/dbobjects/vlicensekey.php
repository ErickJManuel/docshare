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
class VLicenseKey extends VObject 
{
	static $secretKey="user";
	static $zeroDate='1961-01-01';
//	static $keyFields=array("requester", "purchase_id", "running_no", "product_id", "license_code", "reg_name", "test_mode", "subscription_date", "start_date", "expiry_date");

	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VLicenseKey($id=0)
	{
		$this->VObject(TB_LICENSEKEY);
		$this->SetRowId($id);
	}
	/**
	 * @static
	 * @param string
	 * @return bool
	 */
	static function VerifyLicenseText($licenseText, &$keyInfo, &$errMsg) {
		
		// check if the license key is valid
		if ($licenseText=='') {
			$errMsg="The license key file is empty";
			return false;
		}
		
		$keyXml=simplexml_load_string($licenseText);
		if (!$keyXml) {
			$errMsg="Couldn't parse the license file";
			return false;
		}
		$keyInfo=array();
		foreach ($keyXml->children() as $child) {
			$keyInfo[$child->getName()]=(string)$child;
		}
		if (!isset($keyInfo['license_key'])) {
			$errMsg="The license_key field is missing";
			return false;
		}
		
		$str=self::$secretKey;
		foreach ($keyInfo as $key => $val) {
			if (($key=='subscription_date' || $key=='start_date' || $key=='expiry_date') && $val<=self::$zeroDate)
				continue;	
			if ($key=='license_key')
				continue;		
			$str.=$val;
		}
		$valid=$keyInfo['license_key']==md5($str);
		if (!$valid) {
			$errMsg="The license_key is not valid";
			return false;
		}
		
		// if an expiration date is set, check if it is expired
		if (isset($keyInfo['expiry_date']) && $keyInfo['expiry_date']>self::$zeroDate) {
			$today=date("Y-m-d");
			if ($keyInfo['expiry_date']<$today) {
				$errMsg="The license_key has expired on ".$keyInfo['expiry_date'];
				return false;
			}
		}
		
		// if a domain ip is set, check if it is the same domain or IP as the server
		if (isset($keyInfo['domain']) && $keyInfo['domain']!='') {
			$matched=false;
			if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], $keyInfo['domain'])!==false)
				$matched=true;
			else if (isset($_SERVER['SERVER_ADDR']) && strpos($_SERVER['SERVER_ADDR'], $keyInfo['domain'])!==false)
				$matched=true;
			else if (isset($_SERVER['HTTP_POST']) && strpos($_SERVER['HTTP_POST'], $keyInfo['domain'])!==false)
				$matched=true;
			
			if (!$matched) {
				$errMsg="The license is limited to the server domain '{$keyInfo['domain']}'.";
				return false;			
			}
		}
		
		return true;
	}
	
	function VerifyKey(&$keyInfo, &$errMsg) {
		
		$errMsg='';
		$this->Get($licKeyInfo);
			
		$valid=$this->VerifyLicenseText($licKeyInfo['license_text'], $keyInfo, $errMsg);
		if (!$valid)
			return false;

		// make sure the license_key stored in the db field match that in the license_text xml data
		if ($keyInfo['license_key']!=$licKeyInfo['license_key']) {
			$errMsg="Inconsistant or mis-matching license key record";
			return false;
		}
		
		return $valid;
	}

	
}

?>