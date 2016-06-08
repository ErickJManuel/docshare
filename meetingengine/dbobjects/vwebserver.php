<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("scripts.php");
require_once("vobject.php");
include_once("includes/common_lib.php");
/**
 * @package     VShow
 * @access      public
 */
class VWebServer extends VObject 
{
	/**
	* Constructor
	* @param integer set $id to non-zero to associate the object with an existing row.
	*/	
	function VWebServer($id=0)
	{
		$this->VObject(TB_WEBSERVER);
		$this->SetRowId($id);
	}
	/**
	* Insert a row
	* @access  public 
	* @param	array  column values. 'access_id' will be automatically assigned.
	* @return integer error code
	*/		
	function Insert($info)
	{
		if (isset($info['url']) && $info['url']!='')
			$info['url']=$this->AddPaths($info['url'], "/");
			
		return parent::Insert($info);
	}
	/**
	* Update a row
	* @access  public 
	* @param	array  column values. 'access_id' will be automatically assigned.
	* @return integer error code
	*/		
	function Update($info)
	{
		if (isset($info['url']) && $info['url']!='')
			$info['url']=$this->AddPaths($info['url'], "/");
			
		return parent::Update($info);
	}
	/**
	* @static
	* @return string
	*/		
	static function GetScriptUrl($url, $php_ext='')
	{
		$scriptUrl=VWebServer::AddSlash($url);
		if ($php_ext!='')
			$scriptUrl.=SC_SCRIPT.".".$php_ext;
		else
			$scriptUrl.=SC_SCRIPT.".php";
			
		return $scriptUrl;
	}
	/**
	* @static
	* @return string error message
	*/		
	static function GetVersion($url, &$version)
	{
		// get the version
//		if ($html = file_get_contents($url)) {
		if ($html = HTTP_Request($url)) {
			// server response example: OK 2.0.0.1/
			$words=explode(" ", $html);
			if ($words[0]!='OK') {
				return ("Invalid server response");
			}
			// remove trailing /
			$version=str_replace("/", "", $words[1]);
			if ($version=='') {
				return ("Couldn't ger server version");
			}
		} else {
			return ("Couldn't get url");
		}
		return '';
	}
	/**
	* Parse http header and return the error code and message
	* FIXME: it currently only returns the first word of the message
	* @static
	* @return bool
	*/
	static function GetHttpError($httpHeader, &$errorCode, &$errorMsg)
	{
		$errorCode=0;
		$errorMsg='';
		list($version, $errorCode, $errorMsg)=sscanf($httpHeader, "%s %d %s");
		if ($errorCode>=400)
			return false;
		else
			return true;
	}
	/**
	* HTTP GET
	* @param $url
	* @param $response
	* @param $errorCode
	* @param @errorMsg
	* @static
	* @return bool
	*/		
	static function GetUrl($url, &$response)
	{		
		if ($response = @file_get_contents($url)) {
//		if ($response = HTTP_Request($url)) {
			return true;
		}
		return false;
	}
	/**
	* @access  public 
	* @return integer error code
	*/		
	static function CallScript($url, &$response)
	{
		if (VWebServer::GetUrl($url, $response)) {
			if (strpos($response, "OK")===false) {
				return false;
			} else {
				return true;
			}
/*
			if (strpos($response, "ERROR")!==false) {
				return false;
			}
*/
		} else {
			$response="Couldn't get url ".$url;
			return false;
		}

		return true;
	}
	/**
	* @access  public 
	* @return bool
	*/		
	static function CallScriptData($serverUrl, $script, $data, $dataSize, &$response)
	{
/*
		$urlInfo=parse_url($serverUrl);
		
		$server=$urlInfo['host'];
		
		if ($urlInfo['scheme']=='http')
			$port=80;
		else if ($urlInfo['scheme']=='https')
			$port=443;
			
		if (isset($urlInfo['port'])) {
			$port=$urlInfo['port'];
		}
		
		$scriptPath=VWebServer::AddPaths($urlInfo['path'], $script);
*/
//		if (VWebServer::Post($server, $port, $scriptPath, $data, $dataSize, 
/*
		if (VWebServer::Post($serverUrl, $script, $data, $dataSize, 
				$response, $errCode, $errMsg))
		{
			if (strpos($response, "ERROR")!==false) {
				return false;
			}
		} else {
			//$response="Couldn't get url ".$server." ".$scriptPath;
			$response=$errCode." ".$errMsg." ".$scriptPath;
			return false;
		}
		return true;	
*/

		$url=VWebServer::AddPaths($serverUrl, $script);
		
		$response=HTTP_Request($url, $data, 'POST');
		if ($response==false)
			return false;
		elseif (strpos($response, "ERROR")!==false)
			return false;
		else
			return true;
	}	
	
	/**
	*
	* @return bool
	*/
//	function Post($server, $port, $scriptPath, $data, $dataSize, 
/*
	function Post($serverUrl, $script, $data, $dataSize, 
		&$response, &$errorCode, &$errorMsg)
	{
		
		$urlInfo=parse_url($serverUrl);
		
		$server=$urlInfo['host'];
		$hostName=$server;
		
		if ($urlInfo['scheme']=='http')
			$port=80;
		else if ($urlInfo['scheme']=='https') {
			$port=443;
			$server="ssl://".$hostName;
		}
			
		if (isset($urlInfo['port'])) {
			$port=$urlInfo['port'];
		}
		$scriptPath=VWebServer::AddPaths($urlInfo['path'], $script);
		
		$errorCode=0;
		$erroMsg='';		
		$sock = fsockopen($server, $port, $errorCode, $errorMsg, 30); 
		if (!$sock) {
			return false;
		}
		
		fputs($sock, "POST $scriptPath HTTP/1.0\r\n"); 
		fputs($sock, "Host: $hostName\r\n"); 
		fputs($sock, "Content-type: application/octet-stream\r\n"); 
		fputs($sock, "Content-length: " . $dataSize . "\r\n"); 
*/
//		fputs($sock, "Accept: */*\r\n"); 
/*
		fputs($sock, "\r\n"); 
		fputs($sock, "$data\r\n"); 
		fputs($sock, "\r\n"); 
		
		$header = ""; 
		while ($str = trim(fgets($sock, 4096)))
			$header .= "$str\n";
		
		//		print "\n"; 
		
		$response = "";
		$size=0;
		while (!feof($sock) && $size<10000) {
			$response .= fgets($sock, 4096);
			$size+=4096;
		}
		
		fclose($sock); 
		
		return VWebServer::GetHttpError($header, $errorCode, $errorMsg);
	}
*/
	/**
	*
	*/
	static function AddSlash($path) {
		$len=strlen($path);
		if ($len>0 && $path[$len-1]!='/') {
			$path.='/';
		}
		return $path;
	}
	/**
	* Concatnate two paths and make sure this one / inbetween
	* @return string Concatnated path
	*/
	static function AddPaths($path1, $path2) {
		
		// if path1 is a php variable, don't add a slash at the end
		if (strpos($path1, "\$")!==false)
			return $path1.$path2;
			
		$len=strlen($path1);
		if ($len>0 && $path1[$len-1]=='/') {
			$slash1=true;
		} else
			$slash1=false;
		$len=strlen($path2);
		if ($len>0 && $path2[0]=='/') {
			$slash2=true;
		} else {
			$slash2=false;
		}
		if ($slash1 && $slash2)
			return $path1.substr($path2, 1);
		if ($slash1 || $slash2)
			return $path1.$path2;
		else
			return $path1."/".$path2;
	}
	/**
	* Upload memory data to the server
	* @return integer error code
	*/		
	function UploadData($data, $dataSize, $remoteFile, &$response)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';		
		$serverInfo=array();
		if ($this->Get($serverInfo)!=ERR_NONE)
			return $this->GetErrorCode();
		if ($serverInfo['url']=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("Url not set");
			return $this->mErrorCode;
		}
		$php_ext=$serverInfo['php_ext'];
		if ($php_ext=='')
			$php_ext='php';
		$file_perm=$serverInfo['file_perm'];
		$login=rawurlencode($serverInfo['login']);
		$password=$serverInfo['password'];
		
		$urlInfo=parse_url($serverInfo['url']);
		
		$server=$urlInfo['host'];
		
		if ($urlInfo['scheme']=='http')
			$port=80;
		else if ($urlInfo['scheme']=='https')
			$port=443;
			
		if (isset($urlInfo['port'])) {
			$port=$urlInfo['port'];
		}
		$scriptPath=$this->AddSlash($urlInfo['path']);

		$scriptPath.=SC_SCRIPT.".".$php_ext."?s=".SC_VFTP."&cmd=post&id=$login&code=$password";
		$scriptPath.="&arg1=".rawurlencode($remoteFile);		
			
		if (!$this->Post($server, $port, $scriptPath, $data, $dataSize, 
				$response, $errCode, $errMsg))
		{
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errCode." ".$errMsg." ".$scriptPath);
			return $this->mErrorCode;
		}
		return $this->mErrorCode;
	}
	/**
	* Upload a file to the server
	* @return integer error code
	*/		
	function UploadFile($localFile, $remoteFile, &$response)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		$fp=@fopen($localFile, 'rb');
		if ($fp) {
			$fileSize=filesize($localFile);
			$content=fread($fp, $fileSize);
			fclose($fp);
			return $this->UploadData($content, $fileSize, $remoteFile, $response);
		} else {
			$this->mErrorCode=ERR_ERROR;
			$this->mErrorMsg="Can't open file ".$localFile;
		}
		return $this->mErrorCode;	
		
	}
	/**
	* Copy all files from fromdir to todir recursively
	* @static
	* @return bool
	*/
	function CopyDir($fromdir, $todir, $mode) {

		$errMsg='';
		umask(0);
		if (!file_exists($todir)) {
			if (!mkdir($todir, $mode)) {
				return ("Error mkdir ".$todir);
			}
			
		}
		if (!is_dir($todir))
			return("Not a directory ".$todir);
			
		if ($fromdir[strlen($fromdir)-1]!='/')
			$fromdir.="/";
		if ($todir[strlen($todir)-1]!='/')
			$todir.="/";
			
		$dir = opendir($fromdir);
		while($file = readdir($dir)) {
			if($file == "." || $file == "..")
				continue;
			
			$fromFile=$fromdir.$file;
			$toFile=$todir.$file;
			
			if (is_file($fromFile)) {
				if (file_exists($toFile))
					unlink($toFile);
				if (!copy($fromFile, $toFile))
					$errMsg="Can't copy file ".$fromFile;
			} else if (is_dir($fromFile)) {
				$errMsg=$this->CopyDir($fromFile, $toFile, $mode);
			}
		}
		closedir($dir);
		return $errMsg;
	}
	/**
	* @static
	* @return bool
	*/	
	function MyMkDir($dir, $mode, $indexFile)
	{
		$ret=true;
		if (!is_dir($dir)) {
			umask(0);
			if (@mkdir($dir, $mode)) {
				@chmod($dir, $mode);
				if ($indexFile && $indexFile!='') {
					$indexFilePath=$dir."/".$indexFile;
					$ofp=@fopen($indexFilePath, "w");
					if ($ofp) {
						fwrite($ofp, "<html></html>");
						fclose($ofp);
						@chmod($indexFilePath, $mode);
					}
				}
				$ret=true;
			} else
				$ret=false;
		} else {
			umask(0);
			@chmod($dir, $mode);
			$ret=true;
		}
		return $ret;
	}
	/**
	* @static
	* @return bool
	*/		
	function MyRmDir($dir) {
		if ($dh = @opendir($dir)) { 
			$fileList = Array();
			while (($file = @readdir($dh)) !== false) { 
				if ($file!="." && $file!="..") {
					$fileList[] = $dir."/".$file;
				}
			} 
			closedir($dh);
			$ok=true;
			foreach ($fileList as $fileItem) {
				$count++;
				if (is_file($fileItem)) {
					$ok=@unlink($fileItem);
					if (!$ok) {
						break;
					}
				}
			} 
			if ($ok && $dir!=".") {
				$ok=@rmdir($dir);
			}
			if (!$ok)
				return false;
			else
				return true;

		} else {
			return false;
		}		
	}
	/**
	* @static
	* @return bool
	*/	
	function CreateFile($outFile, $content, $mode)
	{
		if (file_exists($outFile))
			unlink($outFile);
		umask(0);
		$ofp=@fopen($outFile, "w");
		if (!$ofp)
			return false;
		fwrite($ofp, $content);
		fclose($ofp);
		@chmod($outFile, $mode);
		return true;
	}
	/**
	* Install server files
	* @return integer error code
	*/		
	function Install()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';		
		$serverInfo=array();
		if ($this->Get($serverInfo)!=ERR_NONE)
			return $this->GetErrorCode();

		$php_ext=$serverInfo['php_ext'];
		if ($php_ext=='')
			$php_ext='php';
		$file_perm=$serverInfo['file_perm'];		
		
		if ($serverInfo['ftp_server']=='' && $serverInfo['path']!='') {
			// copy the dir locally
			$toDir=$serverInfo['path'];
			if ($toDir=='') {
				$this->mErrorCode=ERR_ILL;
				$this->SetErrorMsg("path not set");
				return $this->mErrorCode;
			}
			
			if ($toDir[strlen($toDir)-1]!='/')
				$toDir.="/";
			
			$dir="./";
			$fromDir=$dir.DIR_WEBSERVER;

			$mode=fileperms($dir);
			if ($file_perm=="777")
				$mode=0777;
			else if ($file_perm=="755")
				$mode=0755;				
			$errMsg=$this->CopyDir($fromDir, $toDir, $mode);
			if ($errMsg!='') {
				$this->mErrorCode=ERR_ILL;
				$this->SetErrorMsg($errMsg);
				return $this->mErrorCode;
			}
			$fromDir=$dir.SC_DIR;
			$errMsg=$this->CopyDir($fromDir, $toDir.SC_DIR, $mode);
			if ($errMsg!='') {
				$this->mErrorCode=ERR_ILL;
				$this->SetErrorMsg($errMsg);
				return $this->mErrorCode;
			}
			
			$outFile=$toDir."vscript.".$php_ext;
			$content="<?php \$gScriptDir='scripts/'; require_once(\"scripts/vscript.php\"); ?>";
			
			if (!$this->CreateFile($outFile, $content, $mode)) {
				$this->mErrorCode=ERR_ILL;
				$this->SetErrorMsg("can't create file ".$outFile);
				return $this->mErrorCode;
			}	

			$outFile=$toDir."vh".$serverInfo['login'].".".$php_ext;
			$content="<?php \$code=\"".$serverInfo['password']."\"; ?>";
			
			if (!$this->CreateFile($outFile, $content, $mode)) {
				$this->mErrorCode=ERR_ILL;
				$this->SetErrorMsg("can't create file ".$outFile);
				return $this->mErrorCode;
			}			
			
		} else {
			
		}
		
		return $this->mErrorCode;
		
	}
	
	static function EncodeDelimiter1($inputUrl)
	{
		$url=str_replace("?", ";", $inputUrl);
		$url=str_replace("&", "|", $url);
		return $url;
	}
	
	static function EncodeDelimiter2($inputUrl)
	{
		$url=str_replace("?", "^", $inputUrl);
		$url=str_replace("&", "!", $url);
		return $url;
	}
	
	static function DecodeDelimiter1($inputUrl)
	{
		$url=str_replace(";", "?", $inputUrl);
		$url=str_replace("|", "&", $url);
		return $url;
	}
	
	static function DecodeDelimiter2($inputUrl)
	{
		$url=str_replace("^", "?", $inputUrl);
		$url=str_replace("!", "&", $url);
		return $url;
	}
	
}


?>