<?php 

	// need to use ob_start to capture any output that may be generated from the include files (new lines or spaces before or after the php code)
	ob_start();
	
// (c)Copyright 2006, Persony, Inc. All rights reserved.
	$includeFile='vtoken.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	
	include_once($includeFile);
	include_once("site_config.php");

	// These should be passed in with the token in IsAuthorized
	$tokenBrand='';
	$userId='';
	$meetingId='';
	$token='';
	$tokenInfo=array();
	
	function IsAuthorized($id, $theCode) {
		global $tokenInfo;
		if ($id=='token') {
			list($br, $uid, $mid, $tk)=@explode("_", $theCode);
			// use token for authentication
			if (VerifyToken($tk, $br, $uid, $mid, $tokenInfo)) {
				return true;
			}

		} else if ($id!='') {
//			@include "vh".$id.".php";
			@include "vhhost.php";
			// $code or $ecode should be defined in hostfile
			// $mcode is md5 encrypted code
			if (isset($code) && $theCode==$code) {
				return true;
			} else if (isset($mcode) && md5($theCode)==$mcode) {
				return true;
			}
			
/*		} else {						
			@include "site_config.php";				
			// make sure the request is from the host defined in $serverUrl of site_config.php
			
			// should be defined in site_config.php			
			if (isset($serverUrl) && isset($_SERVER['REMOTE_ADDR'])) {
				$urlItems=parse_url($serverUrl);
				$hostName=$urlItems['host'];
				$serverIp=gethostbyname($hostName);
				$serverIps=explode(".", $serverIp);
				$remoteAddrs=explode(".", $_SERVER['REMOTE_ADDR']);
				
				// match only the first 3 sub ip addresses
				if ($serverIps[0]==$remoteAddrs[0] &&
						$serverIps[1]==$remoteAddrs[1] &&
						$serverIps[2]==$remoteAddrs[2])
					
					return true;			
			}
*/
		}
	
		return false;
	}
	function IsPathOK($path) {
		if (strpos($path, "..")===false && $path[0]!='/')
			return true;
		else
			return false;
	}
	function IsExtOK($path) {
	
		$fileInfo = pathinfo($path);
		$extension=strtolower($fileInfo["extension"]);

		if (strpos($extension, "php")===false &&
			strpos($extension, "exe")===false &&
			strpos($extension, "asp")===false &&
			strpos($extension, "php3")===false &&
			strpos($extension, "php4")===false
			)
			
			return true;
		else
			return false;
	
	}
	function getfileperms($theDir)
	{
		$decperms = fileperms($theDir);
		$octalperms = sprintf("%o",$decperms);
		$perms=substr($octalperms,1);
		return $perms;
	}
	function getdirfiles($theDir, $namesContain, $listType)
	{
		$fileList="";
		if ($theDir=='')
			$theDir="./";
		if ($theDir[strlen($theDir)-1]!='/')
			$theDir.="/";
		if ($dh = @opendir($theDir)) { 
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!="..") {
					$pos=0;
					if ($namesContain!="")
						$pos=strpos($file, $namesContain);
					if ($pos!==false) {
						$theFile=$theDir.$file;
						$fileList.=$theFile;
						if (is_dir($theFile)) {
							$fileList.="/";
						} else {
							if ($listType>0)
								$fileList.=" ".@filesize($theFile);
							if ($listType>1)
								$fileList.=" ".@filemtime($theFile);
						}
							
						$fileList.="\n";
					}
				}
			} 
			closedir($dh);
		}
		return $fileList;
	}
	function dir_size($dir) {
		$totalsize=0;
		if ($dh = @opendir($dir)) {
			while (($filename = @readdir($dh)) !== false) {
				if ($filename!="." && $filename!="..")
				{
					if (is_file($dir."/".$filename))
						$totalsize+=filesize($dir."/".$filename);

					if (is_dir($dir."/".$filename))
						$totalsize+=dir_size($dir."/".$filename);
				}
			}
			closedir($dh);
		}
		return $totalsize;
	}
	function getxmls($theDir)
	{
		
		$slideIds=array();
		if ($theDir=='')
			$theDir="./";
		if ($theDir[strlen($theDir)-1]!='/')
			$theDir.="/";
			
		$fileList=array();
		if ($dh = @opendir($theDir)) { 
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!="..") {
					$items=pathinfo($file);
					
					//$pos=strpos($file, "xml");
					//if ($pos!==false) {
					if (strtolower($items['extension'])=='xml') {
						$fileList[]=$file;						
					}
				}
			} 
			closedir($dh);
		}
		
		sort($fileList);
		
		$outXml="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$outXml.="<xmlFiles>\n";
		
		foreach($fileList as $file) {
			
			$theFile=$theDir.$file;
			$outXml.="<xmlFile fileName=\"".htmlspecialchars($file)."\">\n";
			$fp=@fopen($theFile, "rb");	
			if ($fp) {				
				while (!feof ($fp)) { 
					$buffer = fgets($fp, 4098);
					// don't include the xml header
					if (strpos($buffer, "<?xml")===false) {
						$outXml.=$buffer;
					}
				
				}
			}
			$outXml.="</xmlFile>\n";
		}
		
		$outXml.="</xmlFiles>\n";
		return $outXml;
	}
	
	// recursive mkdir
	function mkdirs($dirPath, $mode)
	{
		if ($dirPath=='.' || $dirPath=='' || $dirPath=='/')
			return true;
			
		if (is_dir($dirPath)) {
			return true;
		}
		 
		$parentPath = dirname($dirPath);
		if (!mkdirs($parentPath, $mode)) 
			return false;

		return @mkdir($dirPath, $mode);
	}

	function mymkdir($dir, $mode, $indexFile)
	{
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
				return true;
			} else
				return false;
		} else {
			umask(0);
			@chmod($dir, $mode);
			return true;
		}
	}
	
	function ErrorExit($msg) {
		@ob_end_clean();
		$errMsg="ERROR";
		if ($msg!='')
			$errMsg.="\n".$msg;
		echo ($errMsg);
		exit();
	}
	
	function RemoveRandFromPath($path) {
		// remove any char in the path that includes /rand_xxxx/..
		// this is for defeating the browser cache to make the  path name unique
		if (($pos1=strpos($path, "/rand_"))>0) {
			if (($pos2=strpos($path, "..", $pos1))>0) {
				$newPath=substr($path, 0, $pos1);
				$newPath.=substr($path, $pos2+2);
				return $newPath;
			}
		}
		return $path;
	}
	
	// globals for the xml parser
	$tagName='';
	$tagAttribs=array();
	$slideFiles='';
	$slideThumbs='';
	$slideTitles='';

	$parser_version = phpversion();
	if ($parser_version < "4.1.0") {
		$GET_VARS		= &$HTTP_GET_VARS;
		$GET_POST		= &$HTTP_POST_VARS;
		$GET_POST_FILES = &$HTTP_POST_FILES;
		$GET_SERVER		= &$HTTP_SERVER_VARS;
	} else {

		$GET_VARS		= &$_GET;
		$GET_POST		= &$_POST;
		$GET_POST_FILES	= &$_FILES;
		$GET_SERVER		= &$_SERVER;
	}
	
	error_reporting(0);
//	LogEntry("Call", $_SERVER['QUERY_STRING'], "");

	$id='';
	if (isset($GET_VARS['id']))
		$id=$GET_VARS['id'];
	$code='';
	if (isset($GET_VARS['code']))
		$code=$GET_VARS['code'];
	if ($id=='token')
		list($tokenBrand, $userId, $meetingId, $token)=@explode("_", $code);			

	$cmd='';
	if (isset($GET_VARS['cmd']))
		$cmd=$GET_VARS['cmd'];
	$arg1='';
	if (isset($GET_VARS['arg1']))
		$arg1=$GET_VARS['arg1'];
	$arg2='';
	if (isset($GET_VARS['arg2']))
		$arg2=$GET_VARS['arg2'];
	$arg3='';
	if (isset($GET_VARS['arg3']))
		$arg3=$GET_VARS['arg3'];
	
//	$print='';
//	if (isset($GET_VARS['print']))
//		$print=$GET_VARS['print'];

	$signature='';
	if (isset($GET_VARS['signature']))
		$signature=$GET_VARS['signature'];
	
//	if ($print!='')
//		echo "<pre>";

	$msg="";
//	$errMsg='';
	$thePath=getcwd()."/";
//	$thePath='./';
		
	if ($cmd=="listdir") {
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
			
		if (!IsAuthorized($id, $code)) {
			
			// only allow to get files in the vinstall or scripts directories
			if (strpos($arg1, "vinstall")===false && strpos($arg1, "scripts")===false) {
				ErrorExit("Invalid file path requested.");
			}
		}

		$msg= getdirfiles($arg1, $arg2, $arg3);

	} else if ($cmd=="listxml") {
		// this is called by the Flash meeting viewer to get the contents of the vslide or vlibrary folders.
		// don't allow it to get other folders
		$arg1=RemoveRandFromPath($arg1);
		
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
			
		if (strpos($arg1, "vslide")===false && strpos($arg1, "vlibrary")===false)
			ErrorExit("Illegal path ".$arg1);
		
		$msg= getxmls($arg1);
		
		if (($pos1=strpos($arg1, "vlibrary"))!==false) {
			
			$public='0';
			$uid='';	
			if ($pos1==0) {
				// post to public library
				$public='1';
			} else {
				
				// get the user's library
				// get the user id from the path name
				$pathItems=explode("/", $arg1);
				$uid=$pathItems[0];				
			}
			
			// TODO: exclude contents from this url
			$dirUrl=GetDirUrl();
			
			$libMsg=AccessLibrary("GET_CONTENT", $uid, $public, "", "", $dirUrl);
			if ($libMsg) {
				// append the library content from the database
				$msg=str_replace("</xmlFiles>", "<!--db content-->", $msg);
				$msg.=str_replace("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<xmlFiles>\n", "", $libMsg);
			}

		}
/*
	} else if ($cmd=="rename") {
		if (!IsAuthorized($id, $code))
			ErrorExit("Not authorized.");
			
		$arg1=RemoveRandFromPath($arg1);
		$arg2=RemoveRandFromPath($arg2);

		// don't allow operating on a parent folder
		if (!IsPathOK($arg1) || !IsPathOK($arg2))
			ErrorExit("Illegal path ".$arg1." ".$arg2);
			
		$file1=$thePath.$arg1;
		if (strpos($arg1, "evt*") !==false) {
			$aDir=dirname($file1);
			if ($handle = opendir($aDir)) {
				while (false !== ($aFile = readdir($handle))) { 
					if (strpos($aFile, "evt") ===0) { 
						$file1=$aDir."/".$aFile;
						break;
					} 
				} 
				closedir($handle); 
			}
		}
		$file2=$thePath.$arg2;
		
		if (!file_exists($file1))
			ErrorExit("Couldn't rename ".$file1.". File doesn't exist.");

		if (!rename($file1, $file2))
			ErrorExit("Couldn't rename from ".$arg1." to ".$arg2);
*/
	} else if ($cmd=="rmfile") {
		
		// this is called by the Flash library browser to remove a library file
		if (!IsAuthorized($id, $code))
			ErrorExit("Authorization failed. Make sure you are signed in or are authorized to perform this operation.");
			
		$arg1=RemoveRandFromPath($arg1);
		// don't allow operating on a parent folder
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
		
		// limit it to the library folder only
		if (strpos($arg1, "vlibrary/")===false)
			ErrorExit("Illegal path ".$arg1);
			
		$public='0';	
		if (strpos($arg1, "vlibrary/")===0) {
			// public library
			if (isset($tokenInfo['permission']) && $tokenInfo['permission']!='ADMIN')
				ErrorExit("You don't have permissions to delete items in the public library.");
			$public='1';
		}
			
		$newFile=$thePath.$arg1;
			
		if (!file_exists($newFile)) {
			// This can happen if the file is stored in a different storage server from the current server assigned to the user
			// The AccessLibrary call should handle the deletion of the file in that server
			// and there should be no error message here.
			// Leave the error message here to remind us to fix this later.
			// This will only happen if the user is switched to another storage server.
			
			// The above problem is fixed elsewhere.
			// Ignore the error here and proceed to delete the content record in the database server
			//ErrorExit("The file to be deleted is not found and may reside in a different server.");			
		} else if (!@unlink($newFile))
			ErrorExit("Couldn't delete ".$arg1);
		
		$fileInfo = pathinfo($arg1);
		$extension=strtolower($fileInfo["extension"]);
		if (strtolower($extension)=="xml") {				
			$xmlFileName=str_replace(".xml", "", $fileInfo["basename"]);
			AccessLibrary("DELETE_CONTENT", $userId, $public, $xmlFileName);
		}


	} else if ($cmd=="get") {
		
		$arg1=RemoveRandFromPath($arg1);
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);

		if (!IsAuthorized($id, $code)) {
			
			// only allow to get files in the vinstall or scripts directories
			if (strpos($arg1, "vinstall")===false && strpos($arg1, "scripts")===false) {
				ErrorExit("Invalid file path requested.");
			}
		}

		$newFile=$thePath.$arg1;
		if (file_exists($newFile)) {
			// must add this for IE7 to work on SSL download
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');

			header("Content-Length: ".@filesize($newFile)); 
			header("Content-Type: " . @filetype($newFile));
//			@readfile($newFile);
			$fp=fopen($newFile, "r");
			fpassthru($fp);
			fclose($fp);
			exit();
		} else
			ErrorExit("Could not find ".$arg1);
		
	} else if ($cmd=="getsize") {
		if (!IsAuthorized($id, $code))
			ErrorExit("Authorization failed. Make sure you are signed in or are authorized to perform this operation.");

		$arg1=RemoveRandFromPath($arg1);
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);

		$newFile=$thePath.$arg1;
		if (file_exists($newFile)) {
			if (is_dir($newFile))
				$msg= dir_size($newFile);
			else
				$msg= filesize($newFile);
		} else
			ErrorExit("Could not find ".$arg1);
/*
	} else if ($cmd=="iswritable") {
		$arg1=RemoveRandFromPath($arg1);

		$newFile=$thePath.$arg1;
		if (file_exists($newFile)) {
			if (is_writable($newFile))
				$msg="YES";
			else
				$msg="NO";
		} else
			ErrorExit("Could not find ".$arg1);
	} else if ($cmd=="fileperms") {
		$arg1=RemoveRandFromPath($arg1);

		if (file_exists($arg1)) {
			$msg=getfileperms($arg1);
		} else
			ErrorExit("");
*/
	} else if ($cmd=="post" || $cmd=="put" || $cmd=="put_url") {
		// "post" is called by the Flash library browser to upload a file to the library folder
		// "put_url" is used to copy a file from one server to another server when saving a recording (see process_audio.php)
		if (!IsAuthorized($id, $code))
			ErrorExit("Authorization failed. Make sure you are signed in or are authorized to perform this operation.");			

		$arg1=RemoveRandFromPath($arg1);

//		$time1=time();
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
		if (!IsExtOK($arg1))
			ErrorExit("Illegal file type ".$arg1);
			
		$mode=fileperms($thePath);
		if ($arg2=="777")
			$mode=0777;
		else if ($arg2=="755")
			$mode=0755;
			
		$newFile=$thePath.$arg1;
		if ($cmd=="put_url" && file_exists($newFile))
			die("OK");
		

		$addLibXml=false;
		$public='0';
		if ($cmd=="post" && ($pos1=strpos($arg1, "vlibrary/"))!==false) {
			if ($pos1==0) {
				// post to public library
				if (isset($tokenInfo['permission']) && $tokenInfo['permission']!='ADMIN')
					ErrorExit("You don't have permissions to post items to the public library.");

				$public='1';
			}
			$fileInfo = pathinfo($arg1);
			$extension=strtolower($fileInfo["extension"]);
			if (strtolower($extension)=="xml") {
				$xmlFileName=str_replace(".xml", "", $fileInfo["basename"]);
				$addLibXml=true;				
			}
		}
					
		umask(0);
		$theDir=dirname($arg1);
		// create the directory if doesn't exist
		if (!mkdirs($theDir, $mode)) {
			ErrorExit("Couldn't create ".$theDir);
		} else {
			AddIndexFile($theDir);
		}
		
		$tempFile=$newFile.".tmp";
		$fp=@fopen($tempFile, "wb");
		$postData='';
		if ($fp) {

			if ($cmd=="put_url")
				$ifp=@fopen($arg3, "rb");
			else
				$ifp=@fopen("php://input", "rb");
			
			if ($ifp) {
				$postData='';	
	//			while ($data=fread($ifp, 8192)) {
				while (!feof($ifp)) {
					$data=fread($ifp, 8192);
					fwrite($fp, $data);		
					if ($addLibXml)
						$postData.=$data;			
				}

				fclose($ifp);
			}

			fclose ($fp);
			@chmod($tempFile, $mode);
//			$time2=time();

//			$filename=$newFile.".".$time1."_".$time2;
			$filename=$newFile;
			// on Linux rename will overwrite an existing file, which's what we want.
			// on Win32, it doesn't so need to unlink it first
			if (file_exists($filename))
				@unlink($filename);
			$ret=@rename($tempFile, $filename);
			if ($ret) {
				@chmod($filename, $mode);
				// need this for libmgr.swf to work
				@ob_end_clean();
				if ($cmd=='post')
					echo("vftp: post to ".$filename."\n");
			} else {
				ErrorExit("Couldn't rename $tempFile to $filename");
			}
		} else {
			ErrorExit("Couldn't open file $tempFile");
		}

		if ($addLibXml) {
			AccessLibrary("SET_CONTENT", $userId, $public, $xmlFileName, $postData);
		}
/*
	} else if ($cmd=="mkdir") {
		$arg1=RemoveRandFromPath($arg1);

		if (!IsAuthorized($id, $code))
			ErrorExit("Not authorized.");
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
			
		$mode=fileperms("./");
		if ($arg3=="777")
			$mode=0777;
		else if ($arg3=="755")
			$mode=0755;

		$dir=$thePath.$arg1;
		if (!mymkdir($dir, $mode, $arg2))
			ErrorExit("Couldn't create $arg1");				
*/
	} else if ($cmd=="rmdir") {
		
		// this is called by the Flash library browser to remove a library file directory
		if (!IsAuthorized($id, $code))
			ErrorExit("Authorization failed. Make sure you are signed in or are authorized to perform this operation.");

		$arg1=RemoveRandFromPath($arg1);

		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
			
		// limit it to a sub folder under the library folder only
		if (strpos($arg1, "vlibrary/")===false && $arg1!="vlibrary/")
			ErrorExit("Illegal path ".$arg1);
		
		$dir=$thePath.$arg1;
		$max=$arg2;
		if (is_dir($dir)) {
			if ($dh = @opendir($dir)) { 
				$fileList = Array();
				while (($file = @readdir($dh)) !== false) { 
					if ($file!="." && $file!="..") {
						$fileList[] = $dir."/".$file;
					}
				} 
				closedir($dh);
				$ok=true;
				$count=0;
				$errM='';
				foreach ($fileList as $fileItem) {
					$count++;
					if ($count>=$max) break;
					if (is_file($fileItem)) {
						$ok=@unlink($fileItem);
						if (!$ok) {
							$errM="couldn't delete file ${fileItem}";
							break;
						}
					}
				} 
				if ($count>=$max)
					$msg="CONTINUE"; // continue
				else if ($ok && $dir!=".") {
					$ok=@rmdir($dir);
					if (!$ok)
						$errM="Couldn't delete directory $dir";
				}
				if (!$ok)
					ErrorExit($errM);

			} else {
				ErrorExit("Couldn't open directory $dir");
			}
		}
		
	} else if ($cmd=="mpost") {
		// this is used by the presenter software or the Flash library browser to upload files, screenshots, or slides
		$arg1=RemoveRandFromPath($arg1);

		if (!IsAuthorized($id, $code))
			ErrorExit("Authorization failed. Make sure you are signed in or are authorized to perform this operation.");
		if (!IsPathOK($arg1))
			ErrorExit("Illegal path ".$arg1);
		if (!IsExtOK($arg1))
			ErrorExit("Illegal file type ".$arg1);
	
		$newFile=$thePath.$arg1;

		$mode=fileperms($thePath);

		if ($arg2=="777")
			$mode=0777;
		else if ($arg2=="755")
			$mode=0755;

		if (isset($GET_POST_FILES['userfile']['tmp_name']))
			$tempFile = $GET_POST_FILES['userfile']['tmp_name'];
		else if (isset($GET_POST_FILES['Filedata']['tmp_name']))
			$tempFile = $GET_POST_FILES['Filedata']['tmp_name'];
					
		$uploadMax=ini_get('upload_max_filesize');
		$postMax=ini_get('post_max_size');
		$uploadMax=str_replace("M", '', $uploadMax);
		$postMax=str_replace("M", '', $postMax);
		$maxSize=(int)$uploadMax;
		if ((int)$postMax<(int)$uploadMax)
			$maxSize=(int)$postMax;
		$uploadTime=ini_get('max_execution_time');
/*				
		$error='';	
		if (isset($GET_POST_FILES['userfile']['error']))
			$error=$GET_POST_FILES['userfile']['error'];
		elseif (isset($GET_POST_FILES['Filedata']['error']))
			$error=$GET_POST_FILES['Filedata']['error'];
			
		$size=0;	
		if (isset($GET_POST_FILES['userfile']['size']))
			$size=(int)$GET_POST_FILES['userfile']['size'];
		elseif (isset($GET_POST_FILES['Filedata']['size']))
			$size=(int)$GET_POST_FILES['Filedata']['size'];
		$size/=1024;
*/		
		if (!isset($tempFile) || $tempFile=='')
			ErrorExit("File upload failed. You may have exceeded upload size or time limits ($maxSize MB and $uploadTime s.)");
		if ($newFile=='')
			ErrorExit("Missing file name.");
			
		$addLibXml=false;
		$public='0';	
		if (($pos1=strpos($arg1, "vlibrary/"))!==false) {
			if ($pos1==0) {
				// post to public library
				if (isset($tokenInfo['permission']) && $tokenInfo['permission']!='ADMIN')
					ErrorExit("You don't have permissions to post items to the public library.");

				$public='1';
			}
			$fileInfo = pathinfo($arg1);
			$extension=strtolower($fileInfo["extension"]);
			if (strtolower($extension)=="xml") {
				
				$xmlFileName=str_replace(".xml", "", $fileInfo["basename"]);
				
				$addLibXml=true;
			}
		}
		umask(0);
		$theDir=dirname($newFile);
		// create the directory if doesn't exist
		if (!mkdirs($theDir, $mode)) {
			ErrorExit("Couldn't create ".$theDir);
		} else {
			AddIndexFile($theDir);
		}
		
		if (file_exists($newFile))
			@unlink($newFile);

		if (move_uploaded_file($tempFile, $newFile)) {
			umask(0);
			@chmod($newFile, $mode);
		} else {
			ErrorExit("Couldn't move file from ".$tempFile." to ".$newFile);
		}	
		
		if ($addLibXml) {
			// upload the xml toc file of a library content
			// add it to the database instead
			$postData='';
			$ifp=@fopen($newFile, "rb");
			if ($ifp) {
				$postData=fread($ifp, filesize($newFile));
				fclose($ifp);
			}			
			AccessLibrary("SET_CONTENT", $userId, $public, $xmlFileName, $postData);
			
		}
		
	} else if ($cmd=="postinfo" || $cmd="postvars") {
		if (!IsAuthorized($id, $code))
			ErrorExit("Authorization failed. Make sure you are signed in or are authorized to perform this operation.");

		$uploadMax=ini_get('upload_max_filesize');
		$postMax=ini_get('post_max_size');
		$uploadTime=ini_get('max_execution_time');	
		$allowUrl=ini_get('allow_url_fopen');	
	
		$zip=extension_loaded('zip')?1:0;
		$simplexml=function_exists('simplexml_load_string')?1:0;
		$iterators=class_exists('RecursiveIteratorIterator')?1:0;
		$curl=function_exists('curl_init')?1:0;
	
		$msg="upload_max_filesize=$uploadMax&post_max_size=$postMax&max_execution_time=$uploadTime";
		$msg.="&allow_url_fopen=$allowUrl&zip=$zip&simplexml=$simplexml&iterators=$iterators&curl=$curl";
	
		// called from the Flash viewer with LoadVars object
		if ($cmd=="postvars") {
			@ob_end_clean();
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			header("Content-Type: text/plain");
			echo $msg;
			exit();
		}
	}
	
	@ob_end_clean();
	if ($cmd!='listxml') {
		echo "OK";
		if ($msg!='')
			echo "\n".$msg;
	} else {
		// must add this for IE7 to work on SSL download
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Type: text/xml");

//		header("Content-Length: ".strlen($msg)); 
//		header("content-type: text/xml;charset=iso-8859-8-I \r\n");
		echo $msg;
	}
		
//	if ($print!='')
//		echo "</pre>";
		

function LogEntry($cmd, $xmlFile, $data='')
{
	$fp=@fopen("log_entry.log", "a");
	if ($fp) {
		$datestr=date("Y F d H:i:s");
		fwrite($fp, $datestr." ".$_SERVER['PHP_SELF']." $cmd $xmlFile\r\n");
		if ($data!='')
			fwrite($fp, $data."\r\n");
		fclose($fp);
	}
}

function AccessLibrary($cmd, $userId, $public, $contentId='', $xmlData='', $excludedUrl='')
{
	global $tagName, $tagAttribs;
	global $slideFiles, $slideTitles, $slideThumbs;
	global $token, $serverUrl, $brand;
	
	if (!isset($serverUrl)) {
		return false;
	}

	// $serverUrl and $brand are defined in site_config.php
	$apiUrl=$serverUrl."api.php?cmd=$cmd";
	if ($userId!='')
		$apiUrl.="&user=".$userId;
	$apiUrl.="&public=".$public;
	if ($contentId!='')
		$apiUrl.="&content_id=".$contentId;
	$apiUrl.="&brand=".$brand;
	if ($token!='')
		$apiUrl.="&token=".$token;
	
	// for GET_CONTENT, do not include contents from this url
	if ($excludedUrl!='')
		$apiUrl.="&excluded_url=".$excludedUrl;

	$type='';
	if ($xmlData!='') {
		$xml_parser = xml_parser_create("UTF-8"); 
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
		xml_set_character_data_handler($xml_parser, "parse_xml");
		xml_parse($xml_parser, $xmlData, true);
		xml_parser_free($xml_parser);
		
		if ($cmd=='SET_CONTENT') {
			if ($tagName=='media')
				$type=$tagAttribs['type'];
			elseif ($tagName=='picture')			
				$type='JPG';
			elseif ($tagName=='slides') {
				$type='PPT';
				$apiUrl.="&slide_files=".urlencode($slideFiles);
				$apiUrl.="&slide_thumbs=".urlencode($slideThumbs);
				$apiUrl.="&slide_titles=".urlencode($slideTitles);
			}
			$apiUrl.="&type=$type";
			
			// SET_CONTENT will fail on content not in the db
			// create the content if it is missing in the db instead of returning an error
			$apiUrl.="&create=1"; 

			foreach ($tagAttribs as $key => $val) {
				switch ($key) {
					case "title":
						$apiUrl.="&title=".urlencode($val);
						break;
					case "fileName":
						$apiUrl.="&file_name=".$val;
						break;
					case "thumbnail":
						$apiUrl.="&thumb_file=".$val;
						break;
					case "dateTime":
						$apiUrl.="&create_date=".urlencode($val);
						break;
					case "author":
						$apiUrl.="&author_name=".urlencode($val);
						break;
					case "copyright":
						$apiUrl.="&copyright=".urlencode($val);
						break;
					case "keywords":
						$apiUrl.="&keywords=".urlencode($val);
						break;
					case "description":
						$apiUrl.="&description=".urlencode($val);
						break;
					case "width":
						$apiUrl.="&width=".$val;
						break;
					case "height":
						$apiUrl.="&height=".$val;
						break;
					default:
						break;
				}			
			}
		}
	}

	
	if ($output = @file_get_contents($apiUrl)) {
//		LogEntry($apiUrl, $output);
		if (strpos($output, "<error")!==false) {
			return false;
		}
		return $output;
	} else {
		return false;
	}

}


function start_xml_tag($parser, $name, $attribs) {
	global $tagName, $tagAttribs;
	global $slideFiles, $slideTitles, $slideThumbs;
	if ($name=='slides' || $name=='picture' || $name=='media') {
		foreach ($attribs as $key => $val) {
			$tagAttribs[$key]=$val;
		}
		$tagName = $name;

	} else if ($name=='slide') {
		if ($slideFiles!='')
			$slideFiles.='|';
		$slideFiles.=$attribs['fileName'];
		if ($slideThumbs!='')
			$slideThumbs.='|';
		$slideThumbs.=$attribs['thumbnail'];
		if ($slideTitles!='')
			$slideTitles.='|';
		$slideTitles.=str_replace("|", "/", $attribs['title']);
	}
}

function end_xml_tag($parser, $name) {
	if ($name=='slides')
		$inSlides=false;
}
function parse_xml($parser, $data) {

}

function AddIndexFile($theDir)
{
	$indexFile=$theDir."/index.php";
	if (!file_exists($indexFile)) {
		$scriptText="<?php ?>";
		$fp=@fopen($scriptFile, "wb");
		if ($fp) {
			fwrite($fp, $scriptText);
			fclose($fp);
			umask(0);
			@chmod($indexFile, 0777);
		}
	}
}

function GetDirUrl()
{
	$proto="http://";
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')
		$proto="https://";
	elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']=='443')
		$proto="https://";
	
	$server='';
	if (isset($_SERVER['SERVER_NAME']))
		$server=$_SERVER['SERVER_NAME'];
	elseif (isset($_SERVER['HTTP_HOST']))
		$server=$_SERVER['HTTP_HOST'];
	
	$scriptPath='';
	if (isset($_SERVER['PHP_SELF']))
		$scriptPath=$_SERVER['PHP_SELF'];
	elseif (isset($_SERVER['SCRIPT_NAME']))
		$scriptPath=$_SERVER['SCRIPT_NAME'];
	
	$scriptName=basename($scriptPath);
	$path=str_replace($scriptName, '', $scriptPath);
	
	$port='';
	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443')
		$port=":".$_SERVER['SERVER_PORT'];
	
	return $proto.$server.$port.$path;
}

?>
