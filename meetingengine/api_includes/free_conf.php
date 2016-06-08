<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;


require_once("server_config.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vteleserver.php");

function GetFreeConfManager($userInfo, &$conf_mgr, &$conf_user, &$conf_pass)
{

	$conf_mgr="";
	$conf_user="";
	$conf_pass="";

	$group=new VGroup($userInfo['group_id']);
	$groupInfo=array();
	if ($group->Get($groupInfo)!=ERR_NONE) {
		return ($group->GetErrorMsg()." group_id=".$userInfo['group_id']);		
	}
	if (!isset($groupInfo['id'])) {
		return ("ERROR Group not found.");
	}
/*	
	$brand=new VBrand($userInfo['brand_id']);
	$brandInfo=array();
	if ($brand->Get($brandInfo)!=ERR_NONE) {
		return API_EXIT(API_ERR, $brand->GetErrorMsg());		
	}

	if (!isset($brandInfo['id'])) {
		return API_EXIT(API_ERR, "Brand not found.");
	}
	
	if (isset($brandInfo['free_audio_conf']) && $brandInfo['free_audio_conf']=='Y' && $groupInfo['free_audio_conf']=='Y') {
		if ($brandInfo['getconf_url']!='') {
			$conf_mgr=$brandInfo['getconf_url'];
			$conf_user=$brandInfo['getconf_login'];
			$conf_pass=$brandInfo['getconf_pwd'];
		} else {
			$conf_mgr=FREEAC_URL;
			$conf_user=FREEAC_LOGIN;
			$conf_pass=FREEAC_PASS;
		}
	}
*/

	$teleServerId=$groupInfo['teleserver_id'];
	if ($teleServerId>0) {
		$teleServer=new VTeleServer($teleServerId);
		$teleServer->Get($teleInfo);
		if ($teleInfo['can_getconf']=='Y') {
			if ($teleInfo['getconf_url']!='') {
				$conf_mgr=$teleInfo['getconf_url'];
				$conf_user=$teleInfo['getconf_login'];
				$conf_pass=$teleInfo['getconf_password'];
			} else {
				$conf_mgr=FREEAC_URL;
				$conf_user=FREEAC_LOGIN;
				$conf_pass=FREEAC_PASS;
			}
		} else {
			return ("ERROR Free teleconference is not enabled.");			
		}
	} else {
		return ("ERROR Free teleconference is not enabled.");				
	}	
	
	return '';
	
}
		
function FreeConfRequest($conf_mgr, $conf_user, $conf_pass, &$freeNum, &$freeMcode, &$freePcode)
{
	$freeNum=str_replace(" ", "", $freeNum);
	$freeNum=str_replace("-", "", $freeNum);

	// delete current codes
	if ($freeNum!='' && $freeMcode!='' && $freePcode!='') {
		$url=$conf_mgr."?cmd=delete&usr=".$conf_user."&pass=".$conf_pass."&phone=".$freeNum."&mod=".$freeMcode."&att=".$freePcode;
		//$res=GetResponse($url);
		$res=HTTP_Request($url);
		if ($res==false) {
			return ("ERROR Couldn't get a response from the teleconference server.");
		}
	}

	$url=$conf_mgr."?cmd=numbers&usr=".$conf_user."&pass=".$conf_pass;
//		$res=GetResponse($url);
	$res=HTTP_Request($url);
	if ($res==false) {
		return ("ERROR Couldn't get a response from the teleconference server.");
	}
/* ignore errors on delete
	if (strpos($res, "RESPONSE")!=0 || strpos($res, "ERROR")!==false) {
		ShowError($res);
		return;
	}
*/				
	// parse number
	list($resp, $freeNum, $totalport, $freeport)=sscanf($res, "%s %s %s %s");
	
	// request codes
//		$url=$conf_mgr."?cmd=create&usr=".$conf_user."&pass=".$conf_pass."&phone=".$phone."&length=7";
	$url=$conf_mgr."?cmd=create&usr=".$conf_user."&pass=".$conf_pass."&phone=".$freeNum;
//		$res=GetResponse($url);
	$res=HTTP_Request($url);
	if ($res==false) {
		return ("ERROR Couldn't get a response from the teleconference server.");
	}
	
	// invalid response
	if (strpos($res, "RESPONSE")!=0) {
		return("ERROR Invalid response received.");
	}
	
	if (strpos($res, "ERROR")===false) {		
		list($resp, $ok, $freeNum, $freeMcode, $freePcode)=sscanf($res, "%s %s %s %s %s");		
	}
	return $res;		

}
		
function FreeConfVerify($conf_mgr, $conf_user, $conf_pass, $freeNum, $freeMcode, $freePcode)
{
	if ($freeNum=='' || $freeMcode=='' || $freePcode=='') {
		return("Missing an input parameter");
	}
	
	$freeNum=str_replace(" ", "", $freeNum);
	$freeNum=str_replace("-", "", $freeNum);
		
	$url=$conf_mgr."?cmd=check&usr=".$conf_user."&pass=".$conf_pass."&phone=".$freeNum."&mod=".$freeMcode."&att=".$freePcode;
//		$res=GetResponse($url);
	$res=HTTP_Request($url);
	if ($res==false) {
		return("ERROR Couldn't get a response from the teleconference server.");
	}		
	
	if (strpos($res, "RESPONSE")!=0) {
		return("ERROR Invalid response received.");
	}
						
	return $res;
	
}
	
function FreeConfDelete($conf_mgr, $conf_user, $conf_pass, $freeNum, $freeMcode, $freePcode)
{
	if ($freeNum=='' || $freeMcode=='' || $freePcode=='') {
		return("ERROR Missing an input parameter");
	}
	$freeNum=str_replace(" ", "", $freeNum);
	$freeNum=str_replace("-", "", $freeNum);

	$url=$conf_mgr."?cmd=delete&usr=".$conf_user."&pass=".$conf_pass."&phone=".$freeNum."&mod=".$freeMcode."&att=".$freePcode;
//		$res=GetResponse($url);
	$res=HTTP_Request($url);
	if ($res==false) {
		return("ERROR Couldn't get a response from the teleconference server.");
	}		
	
	if (strpos($res, "RESPONSE")!=0) {
		return("ERROR Invalid response received.");
	}
	
	return $res;
}


?>