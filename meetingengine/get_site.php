<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */

// Called by the iPhone app to look up a site_url and brand name from a meeting id or member login id
require_once("includes/common_lib.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vbrand.php");

define('XML_HEADER', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>");

GetArg("meeting", $meetingId);
GetArg("login", $login);
GetArg("mobile", $mobile);

if ($meetingId!='') {
	$query="access_id='".addslashes($meetingId)."'";
	
	$errMsg=VObject::Select(TB_MEETING, $query, $meetingInfo);
	if ($errMsg!='') {
		// DB error
		header("HTTP/1.0 500 Internal Error");		
		exit();
				
	} 
	
	if (isset($meetingInfo['id'])) {
		// find the site_url and brand name
		$brand=new VBrand($meetingInfo['brand_id']);
		$brand->Get($brandInfo);
		
		// check if the mobile app is enabled for the site
		if ($mobile!='') {
			if (strpos($brandInfo['mobile'], $mobile)===false) {
				header("HTTP/1.0 403 Forbidden");		
				exit();		
			}
		}
		
		// meeting found
		if (isset($brandInfo['id'])) {
			$xml="<response>\n";
			$xml.="<site>\n";
			$xml.="<site_url>".$brandInfo['site_url']."</site_url>\n";
			$xml.="<brand>".$brandInfo['name']."</brand>\n";
			$xml.="<mobile>".$brandInfo['mobile']."</mobile>\n";
			$xml.="</site>\n";
			$xml.="</response>";
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			header("Content-Type: text/xml");
			echo XML_HEADER."\n";
			
			echo $xml;
		} else {
			header("HTTP/1.0 500 Internal Error");		
			exit();	
		}
		
	} else {
		// not found
		header("HTTP/1.0 404 Not Found");		
		exit();		
	}
	
} else if ($login!='') {

	$query="LOWER(login)='".addslashes(strtolower(trim($login)))."'";
	
	$errMsg=VObject::SelectAll(TB_USER, $query, $result);
	if ($errMsg!='') {
		// DB error
		header("HTTP/1.0 500 Internal Error");		
		exit();	
	} 
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		// not found
		header("HTTP/1.0 404 Not Found");		
		exit();			
	}
	
	$xml="<response>\n";
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		// find the site_url and brand name
		$brand=new VBrand($row['brand_id']);
		$brand->Get($brandInfo);
		if ($mobile!='') {
			if (strpos($brandInfo['mobile'], $mobile)===false)
				continue;
		}
		if (isset($brandInfo['id'])) {			
			$xml.="<site>\n";
			$xml.="<site_name>".htmlspecialchars($brandInfo['product_name'])."</site_name>\n";
			$xml.="<site_url>".$brandInfo['site_url']."</site_url>\n";
			$xml.="<brand>".$brandInfo['name']."</brand>\n";
			$xml.="<mobile>".$brandInfo['mobile']."</mobile>\n";
			$xml.="</site>\n";
		}
	}
	$xml.="</response>";
	
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-Type: text/xml");
	echo XML_HEADER."\n";
	
	echo $xml;
	
	
}

?>