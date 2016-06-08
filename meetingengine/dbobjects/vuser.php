<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("scripts.php");
require_once("vobject.php");
require_once("vwebserver.php");
require_once("vstorageserver.php");
require_once("vbrand.php");
require_once("vgroup.php");
require_once("vcontent.php");
/**
* Const
*/
define("VUSER_GUEST", "guest_user");
define("VUSER_ID_LENGTH", 7);

/**
 * @package     VShow
 * @access      public
 */
class VUser extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VUser($id=0)
	{
		$this->VObject(TB_USER);
		$this->SetRowId($id);
	}	
	/**
	* Insert a row to TB_USER
	* @access public 
	* @param array  column values. 'access_id' will be automatically assigned.
	* @return integer error code
	*/		
	function Insert($info)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId>0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id already set");
			return $this->mErrorCode;
		}
		// create a unique access_id
		while (1) {
			// 7 digits
			// change the range if VUSER_ID_LENGTH is changed
			$accessId=mt_rand(1000000, 9999999);
			if (!$this->InTable(TB_USER, 'access_id', $accessId))
				break;
		}
/*
		// create a unique dir name
		while (1) {
			// 7 digits
			$dir=mt_rand(1000000, 9999999);
			if (!$this->InTable(TB_USER, 'dir', $dir))
				break;
		}
*/
		$info['access_id']=$accessId;
		// use the access id for the dir name	
//		$info['dir']=$accessId;	
		return parent::Insert($info);
	}
	
	/**
	* static
	* @return string
	*/
	static function GetWebServerId($hostInfo)
	{
		$webServerId='0';
		if (isset($hostInfo['webserver_id']))
			$webServerId=$hostInfo['webserver_id'];
		if ($webServerId=='0') {
			$groupId=$hostInfo['group_id'];
			$group=new VGroup($groupId);
			$group->GetValue('webserver_id', $webServerId);
		}
		
		return $webServerId;
	}
	/**
	* @static
	* Create user dir on the server defined in $serverInfo
	* @return string error message
	*/		
	static function CreateServerDir($serverInfo, $dir)
	{

		$url=VWebServer::GetScriptUrl($serverInfo['url'], $serverInfo['php_ext']);
	
		$php_ext=$serverInfo['php_ext'];
		$def_page=$serverInfo['def_page'];
		$file_perm=$serverInfo['file_perm'];
		$login=rawurlencode($serverInfo['login']);
		$password=$serverInfo['password'];
		
		$url.="?s=".SC_NEWROOM."&dir=$dir";
		$url.="&ext=$php_ext&index=$def_page&mode=$file_perm";
		$url.="&id=$login&code=$password";
		
		if (VWebServer::GetUrl($url, $response)) {
			if (strpos($response, "OK")>=0) {

			} else {
				return ("Invalid response ".$response);
			}
		} else {
			return ("Couldn't get url ".$url);
		}
	
		return '';
		
	}
	
	/**
	* Update user dir on the web server
	* @return integer error code
	*/		
	function UpdateServer()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		$userInfo=array();
		if ($this->Get($userInfo)!=ERR_NONE){
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("Couldn't get user info");
			return $this->mErrorCode;
		}
		$dir=$userInfo['access_id'];
		if ($dir=='') {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("User access_id not set");
			return $this->mErrorCode;
		}		
		
		//$webServerId=$userInfo['webserver_id'];
		$webServerId=VUser::GetWebServerId($userInfo);
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}		
		$server=new VWebServer($webServerId);
		$serverInfo=array();
		if ($server->Get($serverInfo)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($server->GetErrorMsg());
			return $this->mErrorCode;		
		}	
		if ($serverInfo['url']=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Web server url not set");
			return $this->mErrorCode;
		}
		
		$errMsg=VUser::CreateServerDir($serverInfo, $dir);
		if ($errMsg!='') {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
		}
		return $this->mErrorCode;
	}
	/**
	* @static
	* @return string full name of the user
	*/
	static function GetFullName($userInfo)
	{
		$name='';
		if (isset($userInfo['first_name']))
			$name=$userInfo['first_name'];
		if (isset($userInfo['last_name']) && $userInfo['last_name']!='') {
			if ($name!='')
				$name.=" ";
			$name.=$userInfo['last_name'];
		}
		return $name;

	}
	/**
	* @static
	* @return string
	*/		
	function GetAttendeeXML($userInfo)
	{
		$name=VObject::StrToXML(VUser::GetFullName($userInfo));
		$email=$userInfo['email'];
		if ($email=='')
			$email=$userInfo['login'];
//		$xml=XML_HEADER."\n"; // do not include header because the file needs to concatnated
		$xml="<attendeeinfo fullname=\"".$name."\" ";
		$xml.="id=\"".$userInfo['access_id']."\" ";
		$xml.="email=\"".VObject::StrToXML($email)."\" ";
		$xml.="title=\"".VObject::StrToXML($userInfo['title'])."\" ";
		$xml.="comapny=\"".VObject::StrToXML($userInfo['org'])."\" ";
		$xml.="phone=\"".VObject::StrToXML($userInfo['phone'])."\" ";
		$xml.="/>";
		return $xml;
	}
	
	/**
	* @static
	* @return string
	*/
/*	
	function GetUploadXML($userInfo, $version, $minVersion, $downloadUrl)
	{
		$xml="<vshowsc\n";
		
		$xml.="version=\"".$version."\"\n";
		$xml.="minVersion=\"".$minVersion."\"\n";
		$xml.="downloadUrl=\"".$downloadUrl."\"\n";
		
		$webServerId=VUser::GetWebServerId($userInfo);
		if ($webServerId<=0) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg("webserver_id not set");
			return $this->mErrorCode;
		}		
		$webServer=new VWebServer($webServerId);

		$serverInfo=array();
		if ($webServer->Get($serverInfo)!=ERR_NONE) {
			return($webServer->GetErrorMsg());
		}
		$php_ext=$serverInfo['php_ext'];
		$url=VWebServer::GetScriptUrl('', $php_ext);
		$postUrl=$url."?s=".SC_VFTP;

		$fileUrl=$postUrl."&id=${serverInfo['login']}&code=${serverInfo['password']}";
//		$fileUrl=rawurlencode($fileUrl);
		$xml.="fileScript=\"".$fileUrl."\"\n";
		$xml.="serverUrl=\"".$serverInfo['url']."\"\n";
		$xml.="hostID=\"".$userInfo['access_id']."\"\n";

		$xml.="/>";
		return $xml;
	}
*/	
	
	/**
	* @static
	* @return string
	*/		
	function GetVCard($userInfo)
	{
		$userName=VUser::GetFullName($userInfo);
		$roomUrl='';
		
		$brandId=$userInfo['brand_id'];
		$brand=new VBrand($brandId);
		if ($brand->GetValue('site_url', $brandUrl)==ERR_NONE) {
			$roomUrl=$brandUrl;
			$roomUrl.="?room=".$userInfo['login'];
		}
		$vcf=		
"BEGIN:VCARD\n
VERSION:2.1\n
N:$userName\n
FN:$userName\n
ORG:${userInfo['org']}\n
TITLE:${userInfo['title']}\n
TEL;WORK;VOICE:${userInfo['phone']}\n
TEL;CELL;VOICE:${userInfo['mobile']}\n
TEL;WORK;FAX:${userInfo['fax']}\n
ADR;WORK:;;${userInfo['street']};${userInfo['city']};${userInfo['state']};${userInfo['zip']};${userInfo['country']}\n
LABEL;WORK;ENCODING=QUOTED-PRINTABLE:${userInfo['street']}=0D=0A${userInfo['city']}, ${userInfo['state']} ${userInfo['zip']}=0D=0A${userInfo['country']}\n
URL;WORK:$roomUrl\n
EMAIL;PREF;INTERNET:${userInfo['email']}\n
END:VCARD";
		
		return $vcf;
	}
	
	/**
	* Delete the user from the db and on the web server
	* @return integer error code
	*/		
	function DeleteUser($ignoreError=true)
	{
require_once("vmeeting.php");

		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		
		$userInfo=array();
		if ($this->Get($userInfo)!=ERR_NONE)
			return $this->GetErrorCode();
			
		// delete all meetings for the user
		$query="host_id='".$userInfo['id']."'";
		$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
		if ($errMsg!='' && !$ignoreError) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}
		$num_rows = mysql_num_rows($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$meeting=new VMeeting($row['id']);
			if ($meeting->DeleteMeeting()!=ERR_NONE) {
				// ignore errors
				if (!$ignoreError) {
					$this->mErrorCode=ERR_ERROR;
					$this->SetErrorMsg($meeting->GetErrorMsg());
					return $this->mErrorCode;
				}			
			}
		}
		
		// delete all db library content for the user
		$query="owner_id='".$userInfo['id']."'";
		$errMsg=VObject::SelectAll(TB_CONTENT, $query, $result1);
		if ($errMsg!='' && !$ignoreError) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}
		$num_rows = mysql_num_rows($result1);
		while ($row = mysql_fetch_array($result1, MYSQL_ASSOC)) {
			$content=new VContent($row['id']);
			if ($content->Drop()!=ERR_NONE) {
				// ignore errors
				if (!$ignoreError) {
					$this->mErrorCode=ERR_ERROR;
					$this->SetErrorMsg($content->GetErrorMsg());
					return $this->mErrorCode;
				}			
			}
		}
			
//		$webServerId=$userInfo['webserver_id'];
		$webServerId=VUser::GetWebServerId($userInfo);
		if ($webServerId>0) {
			$webServer=new VWebServer($webServerId);
			$serverInfo=array();
			if ($webServer->Get($serverInfo)!=ERR_NONE) {
				// ignore errors
				if (!$ignoreError) {
					$this->mErrorCode=ERR_ERROR;
					$this->SetErrorMsg($webServer->GetErrorMsg());
					return $this->mErrorCode;
				}
			}

			if (isset($serverInfo['url']) && $serverInfo['url']!='') {		
			
				$serverUrl=VWebServer::AddSlash($serverInfo['url']);
				$php_ext=$serverInfo['php_ext'];
				
				// delete the user dir
				$dir=$userInfo['access_id'];
				$url=VWebServer::GetScriptUrl($serverInfo['url'], $php_ext);

				$login=rawurlencode($serverInfo['login']);
				$password=$serverInfo['password'];
				
				$url.="?s=".SC_DELROOM."&id=$login&code=$password";
				$url.="&dir=$dir";
				$succeeded=false;
				
				if (!VWebServer::CallScript($url, $response)) {
					if (!$ignoreError) {
						$this->mErrorCode=ERR_ERROR;
						$this->SetErrorMsg($response."return from ".$serverInfo['url']);
						return $this->mErrorCode;
					}
				}

			}
		}		

		// delete the user from db
		if ($this->Drop()!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($response);
			return $this->mErrorCode;
		}

		return $this->mErrorCode;	

	}
	/**
	* @static
	* Get the disk usage of the user on a server
	* @return string error message
	*/		
	static function GetDiskUsage($brandId, $userInfo, $serverUrl, &$meetingsSize, &$libSize)
	{
		$query="brand_id='$brandId' AND url = '$serverUrl'";
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
		if ($errMsg) {
			return($errMsg);
		}
		if (!isset($serverInfo['id'])) {
			return("Web server not found");
		}
		
		$libSize=0;
		$meetingsSize=0;

		$scriptUrl=VWebServer::GetScriptUrl($serverInfo['url'], $serverInfo['php_ext']);		
		$getSizeUrl=$scriptUrl."?s=".SC_VFTP."&cmd=getsize&arg1=".$userInfo['access_id']."/vlibrary";
		$id=$serverInfo['login'];
		$password=$serverInfo['password'];
		$getSizeUrl.="&id=$id&code=$password";
		

		//echo "server url=".$getSizeUrl;
		if (VWebServer::GetUrl($getSizeUrl, $response)) {
			$items=explode("\n", $response, 2);
			if (count($items)>1 && $items[0]=='OK') {
				$libSize=(int)$items[1];
			}
		} else {
			return ("Couldn't get 'My Library' size");
		}
		$getSizeUrl=$scriptUrl."?s=".SC_VFTP."&cmd=getsize&arg1=".$userInfo['access_id']."/vmeetings";
		$getSizeUrl.="&id=$id&code=$password";

		if (VWebServer::GetUrl($getSizeUrl, $response)) {
			$items=explode("\n", $response, 2);
			if (count($items)>1 && $items[0]=='OK') {
				$meetingsSize=(int)$items[1];
			} else {
				// ignore any error because the user dir may not exist		
//				echo "meetings size=".$response." ".$getSizeUrl;
			}		
		} else {
			return ("Couldn't get 'My Meetings' size");
		}

		return '';
	}
	/**
	* @static
	* Return the user's storage server url and access code
	* @return string error message
	*/		
	static function GetStorageUrl($brandId, $userInfo, &$storageUrl, &$storageAccessId, &$storageAccessCode, &$storageServerId)
	{
		// check if there is a storage server assigned to the user's group
		$group = new VGroup($userInfo['group_id']);
		if ($group->GetValue('storageserver_id', $serverId)!=ERR_NONE)
			return $group->GetErrorMsg();
		
		if ($serverId!='0') {
			// a storage server is assigned
			$server = new VStorageServer($serverId);
			if ($server->Get($serverInfo)!=ERR_NONE)
				return $server->GetErrorMsg();
			
			$storageUrl=$serverInfo['url'];
			$storageAccessId='host';
			$storageAccessCode=$serverInfo['access_code'];
			$storageServerId=$serverId;
			
		} else {

			// if storage server is not set
			// assume the contents are stored on the brand's main hosting site				
			$brand=new VBrand($brandId);
			
			if ($brand->GetValue('site_url', $serverUrl)!=ERR_NONE) {
				return $brand->GetErrorMsg();	
			}
			$query="brand_id='".$brandId."' AND url = '$serverUrl'";
			$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
			if ($errMsg) {
				return ($errMsg);	
			}
			if (!isset($serverInfo['id'])) {
				return ("Site '$serverUrl' is not found in our records.");	
			}
			
			$storageUrl=$serverUrl;
			$storageAccessId=$serverInfo['login'];
			$storageAccessCode=$serverInfo['password'];
			$storageServerId='0';
		}
		return '';
		
	}
	/**
	* @static
	* Return the user's public and private library diretory path on the library server
	*/		
	static function GetLibraryPath($userAccessId, &$publicLibPath, &$privateLibPath)
	{
		$publicLibPath="vlibrary";
		if ($userAccessId!='')
			$privateLibPath=$userAccessId."/vlibrary";
		else
			$privateLibPath='';
	}

	static function VerifyLicense($userInfo, &$errMsg)
	{
		require_once("dbobjects/vlicensekey.php");
		require_once("dbobjects/vlicense.php");

		$lic=new VLicense($userInfo['license_id']);
		$lic->Get($licInfo);
		if (!isset($licInfo['id'])) {
			$errMsg="Your account type is not defined";
			return false;
		}
			
		$key=new VLicenseKey($userInfo['licensekey_id']);
			
		if (!$key->VerifyKey($keyInfo, $errMsg)) {
			return false;
		}
		
		if ($licInfo['code']!=$keyInfo['license_code']) {
			$errMsg="Your account type does not match the license key";
			return false;
		}
		return true;
	}

}
?>