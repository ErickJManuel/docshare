<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// use session to store token data so we don't have to check with the server every time
//session_start(); 

function CheckTokenServer($token, $brandId, $userId, $meetingId, &$tokenInfo)
{
	@include "site_config.php";	
	// $serverUrl is defined in the config file
	if (isset($serverUrl)) {
		$url=$serverUrl."rest/token/?token=".$token;
		$url.="&brand=".$brandId;
		$url.="&user_id=".$userId;
		$url.="&meeting_id=".$meetingId;
		
		$resp=@file_get_contents($url);
		if ($resp && strpos($resp, "OK")!==false) {
			$items=explode("&", $resp);
			foreach ($items as $aitem) {
				$keyVal=explode("=", $aitem);
				if (isset($keyVal[1])) {
					$tokenInfo[$keyVal[0]]=$keyVal[1];
				}
			}
			return true;
		}
		return false;
	} else {
		return false;
	}
}
/*
function ReadTokenFromFile($cacheFile, &$tokenFile)
{
	$content='';
	$fp=@fopen($cacheFile, "rb");
	if ($fp && flock($fp, LOCK_SH)) {
		@include_once($cacheFile);
		flock($fp, LOCK_UN);
		fclose($fp);
		
		if (isset($_rowData)) {
			foreach ($_rowData as $key => $value) {
				$rowInfo[$key]=$value;
			}
		}
		return true;
	}
	if ($fp)
		fclose($fp);
		
	return false;
}

function WriteTokenToFile($cacheFile, $tokenInfo)
{
	$content="<?php\n";
	$content.=" \$_rowData=array(\n";		

	foreach ($rowInfo as $key => $value) {
		$content.="   \"$key\" => \"$value\",\n";
	}
		
	$content.=" );\n";
	$content.="?>";
	
	$fp=@fopen($cacheFile, "ab");
	if ($fp && flock($fp, LOCK_EX)) {
		ftruncate($fp, 0);
		fwrite($fp, $content);
		flock($fp, LOCK_UN);
	}
	if ($fp) {
		fclose($fp);
		umask(0);
		@chmod($cacheFile, 0777);
		return true;
	}
	return false;
}
*/
function VerifyToken($token, $brand, $userId, $meetingId, &$tokenInfo)
{
	$currDir=getcwd();
	$currDir=str_replace("\\", "/", $currDir);
	$sessDir=$currDir."/temp";

	$dirOk=true;
	if (!is_dir($sessDir)) {
		umask(0);
		$dirOk=@mkdir($sessDir, 0777); 
	}
	if ($dirOk)
		session_save_path ($sessDir);
		//ini_set('session.save_path', $sessDir);
	
	if (isset($_SESSION[$token])) {
		$sess=&$_SESSION[$token];
		if (isset($sess['brand']) && $sess['brand']==$brand &&
			isset($sess['user_id']) && $sess['user_id']==$userId && 
			isset($sess['meeting_id']) && $sess['meeting_id']==$meetingId) 
		{

			foreach ($sess as $key => $val) {
				$tokenInfo[$key]=$val;
			}
			return true;
		}
	} else {
		if (CheckTokenServer($token, $brand, $userId, $meetingId, $tokenInfo)) {
			// store the token in session data
			$_SESSION[$token]=array(
				"brand"=>$brand,
				"user_id"=>$userId,
				"meeting_id"=>$meetingId
				);

			foreach ($tokenInfo as $key => $val) {
				$_SESSION[$token][$key]=$val;
			}
			return true;	
		}
	}
	return false;
}

?>