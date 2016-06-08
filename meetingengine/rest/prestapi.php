<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */

/*
200 OK 	No error.
201 CREATED 	Creation of a resource was successful.
304 NOT MODIFIED 	The resource hasn't changed since the time specified in the request's If-Modified-Since header.
400 BAD REQUEST 	Invalid request URI or header, or unsupported nonstandard parameter.
401 UNAUTHORIZED 	Authorization required.
403 FORBIDDEN 	Unsupported standard parameter, or authentication or authorization failed.
404 NOT FOUND 	Resource (such as a feed or entry) not found.
409 CONFLICT 	Specified version number doesn't match resource's latest version number.
500 INTERNAL SERVER ERROR 	Internal error. This is the default code that is used for all unrecognized errors.
*/

define('PCODE_OK', 200);
define('PCODE_CREATED', 201);
define('PCODE_BAD_REQUEST', 400);
define('PCODE_UNAUTHORIZED', 401);
define('PCODE_FORBIDDEN', 403);
define('PCODE_NOT_FOUND', 404);
define('PCODE_CONFLICT', 409);
define('PCODE_ERROR', 500);

define('PR_API_DIR', "rest");
define('PR_RESP_XML', "response.xml");
define('PR_ERROR_XML', "error.xml");
define('PR_HELP_XML', "help.xml");
define('PR_MESSAGE_XML', "message.xml");

// set this to true to return error message
$s_returnErrorMsg=true;

$s_required=array(
	"brand" => "Brand id for the site. Each site has a uniqe brand id which can be found under 'Administration/API'.",
	"signature or token" => "Signature or token for the query. See 'Signature Authentication' or 'Token Authentication' for details.",
	);
$s_optional=array(
	"method" => "This parameter is used to send 'PUT' or 'DELETE' via POST requests. Valid values are 'PUT' and 'DELETE'.",
	"ip" => "IP address of the requester. If this parameter is given, it will be checked against the IP of the requester for extra security.",
	"help" => "Returns a help message for the object. No authentication is required.",
	);
	
$s_errorMessages=array(
	PCODE_BAD_REQUEST => "BAD REQUEST Invalid request URI or header, or unsupported nonstandard parameter.",
	PCODE_UNAUTHORIZED => "UNAUTHORIZED Authorization required.",
	PCODE_FORBIDDEN =>	"FORBIDDEN Unsupported standard parameter, or authentication or authorization failed.",
	PCODE_NOT_FOUND =>	"NOT FOUND Resource not found.",
	PCODE_CONFLICT =>	"CONFLICT Specified version number doesn't match resource's latest version number.",
	PCODE_ERROR =>	"INTERNAL SERVER ERROR Internal error. This is the default code that is used for all unrecognized errors.",
	);
	
function GetPost($key, &$val) {
	if (isset($_POST[$key])) {
		$val=$_POST[$key];
		return true;
	} else {
		$val='';
		return false;
	}
}

$prestObjects=array(
	"attendees",
	"command",
	"libraries",
	"licenses",
	"live_attendees",
	"live_event",
	"live_events",
	"meeting",
	"meetings",
	"member",
	"members",
	"group",
	"groups",
	"registrations",
	"remoteserver",
	"remoteservers",
	"sessions",
	"storageserver",
	"storageservers",
	"teleserver",
	"teleservers",
	"videoserver",
	"videoservers",
	"webserver",
	"webservers",
	);
	
/**
 * Base class for all database objects.
 * @package     PRestAPI
 * @access      public
 */
class PRestAPI {
	/**
	 * @access protected
	 * @var string api sub folder path
	 */
	var $mSubDir='';
	/**
	 * @access protected
	 * @var integer http status code
	 */
	var $mStatusCode=0;
	/**
	 * @access protected
	 * @var string error message
	 */
	var $mErrorMsg='';
	/**
	 * @access protected
	 * @var string error message
	 */
	var $mApiKey='';
	/**
	 * @access protected
	 * @var string error message
	 */
	var $mBrandId='';
	/**
	 * @access protected
	 * @var string description
	 */
	var $mSynopsis='';
	/**
	 * @access protected
	 * @var string supported methods
	 */
	var $mMethods='';
	/**
	 * @access protected
	 * @var array required parameters
	 */
	var $mRequired=null;
	/**
	 * @access protected
	 * @var array optional parameters
	 */
	var $mOptional=null;
	/**
	 * @access protected
	 * @var array returned parameters
	 */
	var $mReturned=null;
	/**
	 * @access protected
	 * @var string addtional text
	 */
	var $mAddtional='';
	/**
	 * Constructor. 
	 */
	function PRestAPI($subDir='') {
		$this->mSubDir=$subDir;
	}
	/**
	 * $access static
	 * Get the HTTP GET paramter value
	 * @parameter string argKey to get
	 * @parameter string val retuend value
	 */	
	static function GetArg($argKey, &$val)
	{
		if (isset($_GET[$argKey])) {
			if (get_magic_quotes_gpc())
				$val=stripslashes($_GET[$argKey]);
			else
				$val=$_GET[$argKey];
			return true;
		}
		return false;
	}
	/**
	 * $access static
	 * Get the HTTP POST paramter value
	 * @parameter string argKey to get
	 * @parameter string val retuend value
	 */	
	static function GetPostArg($argKey, &$val)
	{
		if (isset($_POST[$argKey])) {
			if (get_magic_quotes_gpc())
				$val=stripslashes($_POST[$argKey]);
			else
				$val=$_POST[$argKey];
			return true;
		}
		return false;		
	}
	/**
	 * $access static
	 * @parameter objects array
	 */	
	static function GetObjectNames(&$objects)
	{
/*
		$dir=PR_API_DIR;
		$objects=array();
		if ($dh = @opendir($dir)) {
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!="..")
				{
					if (is_dir($dir."/".$file) && $file[0]!='_') {
						$objects[]=$file;
					}
				}
			}
			closedir($dh);
		}
		sort($objects);
*/
		global $prestObjects;
		$objects=array();
		foreach ($prestObjects as $obj)
			$objects[]=$obj;
	}
	/**
	 * @return string
	 */	
	function Get()
	{
		require_once("server_config.php");
		require_once("dbobjects/site_url.php");
		
		$respXml=$this->LoadResponseXml();
		$subXml=$this->GetSubXml('<!--BEGIN_OBJECTS-->', '<!--END_OBJECTS-->', $respXml);

		$apiUrl=SITE_URL;
/*			
		$dir=PR_API_DIR;
		$newXml='';
		if ($dh = @opendir($dir)) {
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!="..")
				{
					if (is_dir($dir."/".$file)) {
						$link=$apiUrl.$file."/";
						$name=$file;
						$xml=str_replace("[LINK]", $link, $subXml);
						$xml=str_replace("[NAME]", $file, $xml);
						$newXml.=$xml."\n";
					}
				}
			}
			closedir($dh);
		}
*/
		$this->GetObjectNames($objects);
		foreach ($objects as $file) {
			$link=$apiUrl.$file."/";
			$xml=str_replace("[LINK]", $link, $subXml);
			$xml=str_replace("[NAME]", $file, $xml);
			$newXml.=$xml."\n";			
		}				
		$retXml=str_replace($subXml, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}

	/**
	 * @return boolean
	 */
	function Authenticate()
	{
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vuser.php");
		$this->SetStatusCode(PCODE_FORBIDDEN);
		
		if (!isset($_REQUEST['signature']) && !isset($_REQUEST['token'])) {
			$this->SetErrorMessage("Missing required parameter 'signature' or 'token'.");
			return false;
		}
		if (!isset($_REQUEST['brand'])) {
			$this->SetErrorMessage("Missing required parameter 'brand'.");
			return false;
		}
		
		// if ip address is given, check if it matches
		if (isset($_REQUEST['ip'])) {
			if (isset($_SERVER['REMOTE_ADDR']) && $_REQUEST['ip']!=$_SERVER['REMOTE_ADDR']) {
				$this->SetErrorMessage("IP address does not match.");
				return false;
			}
		}
		
		if ($_SERVER['REQUEST_METHOD']=='GET')
			$query=$_SERVER['QUERY_STRING'];
		else {
			$query='';
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc())
					$val=stripslashes($v);
				else
					$val=$v;
					
				if ($query!='')
					$query.="&";
				$query.="$k=$val";
			}
		}

		
		$brandName=$_REQUEST['brand'];
		$brandInfo=array();
		VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
		if (!isset($brandInfo['id'])) {
			$this->SetErrorMessage("Brand '$brandName' is not found in our records.");
			return false;
		}

		if (isset($_REQUEST['token'])) {
			// token based authentication
			require_once("dbobjects/vtoken.php");
			VToken::GetToken($_REQUEST['token'], $tokenInfo);
			
			if (!isset($tokenInfo['token'])) {
				$this->SetErrorMessage("The token you provided ${_REQUEST['token']} is not valid.");
				return false;			
			}			
			
			if ($tokenInfo['brand']!=$brandName) {
				$this->SetErrorMessage("The token is not valid for the site.");
				return false;				
			}
			if (isset($tokenInfo['user_id']) && $tokenInfo['user_id']!='0') {
				// log in as the user
				VObject::Find(TB_USER, 'access_id', $tokenInfo['user_id'], $userInfo);
				if (!isset($userInfo['id'])) {
					$this->SetErrorMessage("User associated with the token cannot be found.");
					return false;			
				}
				$memberName=VUSer::GetFullName($userInfo);
				SetSessionValue("member_name", $memberName);
				SetSessionValue("member_id", $userInfo['id']);					
				SetSessionValue("member_perm", $userInfo['permission']);					
				SetSessionValue("member_brand_name", $brandName);	
				SetSessionValue("member_brand", $brandInfo['id']);	
				if ($userInfo['time_zone']!='')
					SetSessionValue("time_zone", $userInfo['time_zone']);			
				SetSessionValue("member_access_id", $userInfo['access_id']);				
			}
			
			SetSessionValue("meeting_access_id", $tokenInfo['meeting_id']);
			
		} else {
			if (!isset($brandInfo['api_key'])) {
				$this->SetErrorMessage("API access key is not set for this site.");
				return false;
			}
			// signature based authentication
			// remove signature from the query string
			$query=str_replace("&signature=".$_REQUEST['signature'], "", $query);	

			// for debugging only. allow signature=nosig				
//			if ($_REQUEST['signature']!='nosig' && $_REQUEST['signature']!=md5($query.$brandInfo['api_key'])) {
			if ($_REQUEST['signature']!=md5($query.$brandInfo['api_key'])) {
				$this->SetErrorMessage("Invalid signature. Input: ".htmlspecialchars($query));
				return false;
			}
			
			// log in as the site admin
			$adminId=$brandInfo['admin_id'];
			$admin=new VUser($adminId);
			$admin->Get($userInfo);
			if (!isset($userInfo['id'])) {
				$this->SetErrorMessage("Site admin is not set or cannot be found.");
				return false;			
			}
			$memberName=VUSer::GetFullName($userInfo);
			SetSessionValue("member_name", $memberName);
			SetSessionValue("member_id", $userInfo['id']);					
			SetSessionValue("member_perm", $userInfo['permission']);					
			SetSessionValue("member_brand", $userInfo['brand_id']);	
			SetSessionValue("member_brand_name", $brandName);	
			if ($userInfo['time_zone']!='')
				SetSessionValue("time_zone", $userInfo['time_zone']);			
			SetSessionValue("member_access_id", $userInfo['access_id']);
		}
		
		SetSessionValue('brand_name', $brandName);
		
		$this->mApiKey=$brandInfo['api_key'];
		$this->mBrandId=$brandInfo['id'];

		$this->SetStatusCode(PCODE_OK);
		$this->SetErrorMessage("");

		return true;
	}
	/**
	 * @static
	 * @return string request method GET, POST, PUT, DELETE
	 */
	static function GetRequestMethod()
	{
		$method=$_SERVER['REQUEST_METHOD'];
		// use POST for DELETE and PUT, if method is specified
		if ($method=='POST' && isset($_POST['method'])) {
			$method=strtoupper($_POST['method']);
		}
		return $method;
	}
	/**
	 * @static
	 * @return string return an xml file and return its content
	 */
	static function LoadXmlFile($xmlFile)
	{
		$fp=fopen($xmlFile, "r");
		if ($fp) {
			$content=fread($fp, filesize($xmlFile));
			fclose($fp);
			return $content;
		}
		return false;
	}
	/**
	 * @return string return the content of response xml
	 */
	function LoadResponseXml()
	{
		$dir=PR_API_DIR."/";
		if ($this->mSubDir!='')
			$dir.=$this->mSubDir."/";
		return PRestAPI::LoadXmlFile($dir.PR_RESP_XML);
	}
	/**
	 * @static
	 * @return string return the content of response xml
	 */
	static function LoadErrorXml()
	{
		$dir=PR_API_DIR."/";
		return PrestAPI::LoadXmlFile($dir.PR_ERROR_XML);
	}
	/**
	 * @static
	 * @return string return the content of response xml
	 */
	static function LoadMessageXml()
	{
		$dir=PR_API_DIR."/";
		return PrestAPI::LoadXmlFile($dir.PR_MESSAGE_XML);
	}
	/**
	 * @static
	 * @return string return the content of response xml
	 */
	static function LoadHelpXml()
	{
		$dir=PR_API_DIR."/";
		return PrestAPI::LoadXmlFile($dir.PR_HELP_XML);
	}
	/**
	 * @static
	 * @return string return the content of response xml
	 */
	static function GetSubXml($beginTag, $endTag, $xml)
	{		
		$pos1=strpos($xml, $beginTag);
		if ($pos1===false)
			return '';
		
		$pos1+=strlen($beginTag);
		$pos2=strpos($xml, $endTag);
		if ($pos2===false)
			return '';
		
		$subStr=substr($xml, $pos1, $pos2-$pos1);
		
		return $subStr;
	}
	/**
	 * 
	 */
	function GetStatusCode()
	{
		return $this->mStatusCode;
	}
	/**
	 * 
	 */
	function SetStatusCode($code)
	{
		$this->mStatusCode=$code;
	}
	/**
	 * @static
	 */
	function SetErrorMessage($msg)
	{
		$this->mErrorMsg=$msg;
	}
	/**
	 * @static
	 */
	function GetErrorMessage($msg)
	{
		return $this->mErrorMsg;
	}	
	/**
	 * 
	 */
	function GetErrorXml()
	{
		$xml=$this->LoadErrorXml();
		$xml=str_replace("[ERROR_CODE]", $this->mStatusCode, $xml);
		$xml=str_replace("[ERROR_MESSAGE]", $this->mErrorMsg, $xml);
		return $xml;
	}
	/**
	 * 
	 */
	function GetMessageXml($message)
	{
		$xml=$this->LoadMessageXml();
		$xml=str_replace("[MESSAGE]", $message, $xml);
		return $xml;
	}
	/**
	 * 
	 */
	function Respond($retXml='')
	{
		global $s_returnErrorMsg;
		
		if ($this->mStatusCode<400) {

			header("HTTP/1.0 ".$this->mStatusCode);		
			if ($retXml && $retXml!='') {
				// must add this for IE7 to work on SSL download
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				header("Content-type: text/xml");
				echo $retXml;
			}
			$this->Quit();
		} else if ($s_returnErrorMsg) {
			
			$xml=$this->GetErrorXml();
			// must add this for IE7 to work on SSL download
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');

			header("Content-type: text/xml");
			echo $xml;
			
			//include_once("includes/log_error.php");
			//LogError($xml);

			$this->Quit();
		} else {	
			header("HTTP/1.0 ".$this->mStatusCode);
			$this->Quit();		
		}

	}
	
	/**
	 * 
	 */
	function GetHelpXml()
	{
		global $s_required, $s_optional;
		
		$req_b='<!--BEGIN_REQUIRED-->';
		$req_e='<!--END_REQUIRED-->';
		$opt_b='<!--BEGIN_OPTIONAL-->';
		$opt_e='<!--END_OPTIONAL-->';
		$ret_b='<!--BEGIN_RETURNED-->';
		$ret_e='<!--END_RETURNED-->';
		
		$helpXml=$this->LoadHelpXml();
		$helpXml=str_replace("[SYNOPSIS]", $this->mSynopsis, $helpXml);
		$helpXml=str_replace("[METHODS]", $this->mMethods, $helpXml);
		$helpXml=str_replace("[ADDITIONAL]", $this->mAddtional, $helpXml);
		
		$requiredXml=$this->GetSubXml($req_b, $req_e, $helpXml);
		$optionalXml=$this->GetSubXml($opt_b, $opt_e, $helpXml);
		$returnedXml=$this->GetSubXml($ret_b, $ret_e, $helpXml);
				
		$newXml='';
		foreach ($s_required as $key => $value) {
			$itemXml=str_replace("[KEY]", $key, $requiredXml);
			$itemXml=str_replace("[VALUE]", $value, $itemXml);
			$newXml.=$itemXml;
		}
		if ($this->mRequired) {
			foreach ($this->mRequired as $key => $value) {
				$itemXml=str_replace("[KEY]", $key, $requiredXml);
				$itemXml=str_replace("[VALUE]", $value, $itemXml);
				$newXml.=$itemXml;
			}
		}
		$retXml=str_replace($req_b.$requiredXml.$req_e, $req_b.$newXml.$req_e, $helpXml);
	
		$newXml='';
		foreach ($s_optional as $key => $value) {
			$itemXml=str_replace("[KEY]", $key, $optionalXml);
			$itemXml=str_replace("[VALUE]", $value, $itemXml);
			$newXml.=$itemXml;
		}
		if ($this->mOptional) {
			foreach ($this->mOptional as $key => $value) {
				$itemXml=str_replace("[KEY]", $key, $optionalXml);
				$itemXml=str_replace("[VALUE]", $value, $itemXml);
				$newXml.=$itemXml;
			}
		}
		$retXml=str_replace($opt_b.$optionalXml.$opt_e, $opt_b.$newXml.$opt_e, $retXml);

		$newXml='';
		if ($this->mReturned) {
			foreach ($this->mReturned as $key => $value) {
				$itemXml=str_replace("[KEY]", $key, $returnedXml);
				$itemXml=str_replace("[VALUE]", $value, $itemXml);
				$newXml.=$itemXml;
			}
		}
		$retXml=str_replace($ret_b.$returnedXml.$ret_e, $ret_b.$newXml.$ret_e, $retXml);
		
		return $retXml;

	}
	/**
	 * 
	 */	
	function Help()
	{		
		$helpXml=$this->GetHelpXml();
		$this->SetStatusCode(PCODE_OK);
		$this->Respond($helpXml);
	}
	/**
	 * All API objects should call this function to exit
	 */
	function Quit()
	{
require_once("dbobjects/vobject.php");
		VObject::CloseDB();
		exit();
	}
}

?>