<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");

define('TOKEN_USE_DB', 'off');	// set it to 'off' to disable the use of token DB (always use token files)
define('TOKEN_EXP_TIME', 28800);	// token expiration time in seconds. need to be long enough to last for the longest meeting.
//define('TOKEN_DIR', DIR_TEMP."tokens/");	// directory path to store the token files
define('TOKEN_DIR', "tokens/");	// directory path under the temp dir to store the token files

/**
 * @package     VShow
 * @access      public
 */
class VToken extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VToken($id=0)
	{
		$this->VObject(TB_TOKEN);
		$this->SetRowId($id);
	}
	/**
	 * @static
	 * Create a token from the input
	 * @return string error message
	 */	
	static function ComputeToken($brand, $meetingId, $userId)
	{
		$hr=floor(time()/3600);	// current hour; so we can return the same token
		$cacheKey=md5($brand.$meetingId.$userId.$hr);	
		return $cacheKey;
	}
	/**
	 * @static
	 * Create a file cache path for a token (only needed if DB is down)
	 * @return string error message
	 */		
	static function GetTokenCacheFile($tokenCode)
	{
		return GetTempDir().TOKEN_DIR.TB_TOKEN."_".md5($tokenCode).".php";
	}
	/**
	 * @static
	 * Create a new token for the provided input parameters
	 * @return string error message
	 */	
	static function AddToken($brand, $meetingId, $userId, $userInfo=null)
	{
		if ($meetingId=='')
			$meetingId='0';
		if ($userId=='')
			$userId='0';
		
		// one of the two fields need to be non zero
		if ($meetingId=='0' && $userId=='0') {
			return false;
		}
		
		// write the user info to a cache file
		if ($userId!='0' && $userId!='') {	
			$cacheKey=TB_USER.'access_id'.$userId;
			$cacheFile=VObject::GetCachePath($cacheKey);
			VObject::WriteToCache($cacheFile, $userInfo);
		}
		
		// remove outdated tokens first
		VToken::DeleteOldTokens();
		
		$useDB=false;
		if (TOKEN_USE_DB!='off') {
			$useDB=VObject::CanOpenDB();
		}
		
		// create a token in the DB if it is up.	
		if ($useDB) {

			// see if a token already exists for the given request		
			$query="TIME_TO_SEC(TIMEDIFF(NOW(), create_time))<'".TOKEN_EXP_TIME."'";
			$query.=" AND brand='".$brand."'";
			$query.=" AND meeting_id='".$meetingId."'";
			$query.=" AND user_id='".$userId."'";
			
			VObject::Select(TB_TOKEN, $query, $tokenInfo);
		
			// a token already exists for the given request
			// reset the token expiration time and use it instead of creating a new token
			if (isset($tokenInfo['id'])) {
				$updateInfo=array();
				$updateInfo['create_time']="#NOW()";
				$token=new VToken($tokenInfo['id']);
				$token->Update($updateInfo);
				
				return $tokenInfo['token'];
					
			} else {

				// create a new token in the DB
				$tokenInfo=array();

				$tokenInfo['brand']=$brand;
				$tokenInfo['meeting_id']=$meetingId;
				$tokenInfo['user_id']=$userId;		
				$tokenInfo['token']=md5(microtime());
				$tokenInfo['create_time']="#NOW()";	
				if (isset($userInfo['permission']))
					$tokenInfo['permission']=$userInfo['permission'];
			
				$token=new VToken();
				if ($token->Insert($tokenInfo)==ERR_NONE)
					return $tokenInfo['token'];				

			}
		
		} else {

			// DB is down or not used; write the token to a cache file instead
//			$tokenCode=md5(microtime());
			// we want to reuse the same token if it already exists
			$tokenCode=VToken::ComputeToken($brand, $meetingId, $userId);
		
			$cacheFile=VToken::GetTokenCacheFile($tokenCode);
			$tokenInfo=array();

			$tokenInfo['brand']=$brand;
			$tokenInfo['meeting_id']=$meetingId;
			$tokenInfo['user_id']=$userId;		
			$tokenInfo['token']=$tokenCode;
			$tokenInfo['create_time']=time();
			if (isset($userInfo['permission']))
				$tokenInfo['permission']=$userInfo['permission'];
			
			$tokenDir=GetTempDir().TOKEN_DIR;
			if (!is_dir($tokenDir)) {
				umask(0);
				@mkdir($tokenDir, 0777);
			}

			if (VObject::WriteToCache($cacheFile, $tokenInfo)) {
				return $tokenCode;				
			}

		}
		
		return false;
	}

	/**
	 * @static
	 * @return string error message
	 */
	static function GetToken($token, &$tokenInfo)
	{
		// token can be stored in the DB or cache file, depending on if the DB was up when the token was created
		// retrieve from the cache file if it exists
		$cacheFile=VToken::GetTokenCacheFile($token);
		if (file_exists($cacheFile)) {
			if ((time()-@filemtime($cacheFile))<TOKEN_EXP_TIME) {				
				VObject::ReadFromCache($cacheFile, $tokenInfo);	
			}		
		} else if (TOKEN_USE_DB!='off') {
			// Otherwse, check if the token is stored in the DB			
			$query="TIME_TO_SEC(TIMEDIFF(NOW(), create_time))<'".TOKEN_EXP_TIME."'";
			$query.=" AND token='".trim($token)."'";
			VObject::Select(TB_TOKEN, $query, $tokenInfo);			
		}

		return '';	
	}
	/**
	 * @static
	 * Delete expired tokens. This should be called in cron.php
	 */
	static function DeleteOldTokens()
	{		
		// delete old DB tokens
		if (TOKEN_USE_DB!='off') {
			$query="TIME_TO_SEC(TIMEDIFF(NOW(), create_time))>'".TOKEN_EXP_TIME."'";
			VObject::DropSelections(TB_TOKEN, $query);
		}
	
		// delete old file tokens
		$nowTime=time();
		$dir=GetTempDir().TOKEN_DIR;
		if ($dh = @opendir($dir)) { 
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!=".." && strpos($file, TB_TOKEN)===0) {					
					$theFile=$dir.$file;
					$mtime=@filemtime($theFile);
					if (($nowTime-$mtime)>TOKEN_EXP_TIME) {
						@unlink($theFile);						
					}					
				}
			}
			closedir($dh);
		}			

	}
	/*
	* @static
	* Get a string that includes brand, userId, meetingId and token to be passed to hosting server's command scripts
	* @return string
	*/
	static function GetBUMToken($brandName, $userId, $meetingId, $tokenCode)
	{
		return $brandName."_".$userId."_".$meetingId."_".$tokenCode;
	}
	

	
}


?>