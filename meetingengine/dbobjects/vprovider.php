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
class VProvider extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VProvider($id=0)
	{
		$this->VObject(TB_PROVIDER);
		$this->SetRowId($id);
	}
	/**
	* @static
	* @param string license key to verify
	* @return boolean
	*/		
	function VerifyLicenseKey($license)
	{	
		$items=explode(";", $license);
		$count=count($items);	
		if ($count<3)
			return false;
		
		$licstr='';
		for ($i=0; $i<($count-1); $i++) {
			if ($i>0)
				$licstr.=";";
			$licstr.=$items[$i];
		}
		$key=str_replace("\n", "", $items[$count-1]);
		$key=str_replace("\r", "", $key); 
		
		//			$licstr=$items[0].";".$items[1];
		//			$key=str_replace("\n", "", $items[2]);
		//			$key=str_replace("\r", "", $key); 
		if (VLicense::EncryptLicense($licstr, $providerInfo['login'])!=$key)
			return false;
		
		return true;
	}
	/**
	* @static
	* @param array  column values. 
	* @return string
	*/		
	static function ParseLicenseKey($providerInfo, &$licenseType, &$licenseCounts, &$recording)
	{
require_once("vlicense.php");
		$license=$providerInfo['license'];
		
		$items=explode(";", $license);
		$count=count($items);	
		if ($count<3)
			return "Invalid licenes key";
		else {
			
			$licstr='';
			for ($i=0; $i<($count-1); $i++) {
				if ($i>0)
					$licstr.=";";
				$licstr.=$items[$i];
			}
			$key=str_replace("\n", "", $items[$count-1]);
			$key=str_replace("\r", "", $key); 
			
//			$licstr=$items[0].";".$items[1];
//			$key=str_replace("\n", "", $items[2]);
//			$key=str_replace("\r", "", $key); 
			if (VLicense::EncryptLicense($licstr, $providerInfo['login'])!=$key)
				return "Invalid licenes key";
		}

		$licenseType=$items[0];
		$licenseStr=$items[1];
	
		$listItems=explode(',', $licenseStr);
		foreach ($listItems as $v) {
			$subItems=explode(':', $v);
			
			$subItemCount=count($subItems);
			if ($subItemCount==2)
				$licenseCounts[$subItems[0]]=$subItems[1];
			elseif ($subItemCount==1)
				$licenseCounts[$subItems[0]]=0;
		}
		
		$recording='0';
		for ($i=2; $i<($count-1); $i++) {
			$anItem=$items[$i];
			list($itemKey, $itemVal)=explode(":", $anItem);
			if ($itemKey=='R') {
				$recording=$itemVal;
			}
		}
		return '';
	}
	
	/**
	 * User License Key:
	 * Each user record may have an optional license key
	 * If the user license key is present, the user's license is controlled by it.
	 * Otherwise, the user's license is controlled by the "provider's" license.
	 * User license key is designed for each user to individually purchase a license and the license is tied to the user.
	 * Provider license key is designed for a service provider to purchase a pool of licenses and then assign each to a user.
	 * Although allowed, it is not recommended for a site to use both user's license key and provider's license.
	 */	
	
	/**
	* @static
	* @param array  column values. 
	* @return string
	*/		
	static function GetLicenseUsed($provider_id, $brand_id, $licenseCode)
	{		
		// find all brands that use this provider_id
		// and create a query string to find all these brands
		$brandsQuery='';
		if ($provider_id>0) {
			$query="provider_id='".$provider_id."'";
			$errMsg=VObject::SelectAll(TB_BRAND, $query, $result);
			if ($errMsg!='') {
			}
			
			$num_rows = mysql_num_rows($result);
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if ($brandsQuery!='')
					$brandsQuery.=" OR ";
				$brandsQuery.="brand_id ='".$row['id']."'";
			}

		} else if ($brand_id>0) {
			$brandsQuery.="brand_id ='".$brand_id."'";
			
		} else {
			return 0;
		}
		
		$licInfo=array();
		$errMsg=VObject::Find(TB_LICENSE, "code", $licenseCode, $licInfo);
		$licId=$licInfo['id'];
		
		$licenseUsed=0;
		if ($brandsQuery!='') {
			// exclude any users that have a license key
			if ((defined('ENABLE_LICENSE_KEY') && constant('ENABLE_LICENSE_KEY')=='1')) {
				$query="($brandsQuery) AND (license_id='$licId') AND (licensekey_id='0')";			
			} else {
				$query="($brandsQuery) AND (license_id='$licId')";
			}
			VObject::Count(TB_USER, $query, $licenseUsed);
		}
		return $licenseUsed;
		
	}
	
	function GetLicenseAvailable($provider_id, $brandId, $licInfo)
	{
		$licCode=$licInfo['code'];
//		$trial=$licInfo['trial'];
//		if ($trial=='Y')
//			return -1;
			
		$avail=0;
		if ($provider_id>0) {
			$provider=new VProvider($provider_id);
			$providerInfo=array();
			
			if ($provider->Get($providerInfo)!=ERR_NONE) {
				API_EXIT(API_ERR, $provider->GetErrorMsg());	
			}
			
			VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
	//		if ($licenseType=='N') {
				if (isset($licenseCounts[$licCode]))
					$total=$licenseCounts[$licCode];
				else
					$total=0;
				
				if ($total>=0) {	
					$licUsed=VProvider::GetLicenseUsed($provider_id, $brandId, $licCode);
					$avail=(int)$total-(int)$licUsed;
					if ($avail<0)
						$avail=0;
				} else
					$avail=-1; // no limit
	/*
			} else if ($licenseType=='P' || $licenseType=='U') {
				$avail=-1;
			}
	*/
		}
		return $avail;
	}


}


?>