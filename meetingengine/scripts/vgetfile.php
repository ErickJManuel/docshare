<?php
// (c)Copyright 2009, Persony, Inc. All rights reserved.

// assume the function is called in the meetint dir and
// the cache dir is in the site dir

$includeFile='vinclude.php';
if (isset($gScriptDir))
	$includeFile=$gScriptDir.$includeFile;
require_once($includeFile);

$cacheIncFile='vcache.php';
if (isset($gScriptDir))
	$cacheIncFile=$gScriptDir.$cacheIncFile;
include_once($cacheIncFile);

$trackerIncFile='vtracker.php';
if (isset($gScriptDir))
	$trackerIncFile=$gScriptDir.$trackerIncFile;
require_once($trackerIncFile);


$file="";
if (isset($_GET['file'])) {
	$file=$_GET['file'];
}
if ($file=='')
	exit();

// don't allow getting these file types
//$ext = strtolower(array_pop(explode('.',$file)));
$fileParts=pathinfo($file);
$ext=isset($file['extension'])?strtolower($file['extension']):'';
if ($ext=='php')
	exit();
	
// If the file is not a url, check if the file path is under the web root
// Don't allow getting a file outside of the web root
if (strpos(strtolower($file), "http://")!==0 && strpos(strtolower($file), "https://")!==0 && isset($_SERVER['DOCUMENT_ROOT'])) {
	$filePath=realpath($file);
	// replace the back slashes with the forward slashes
	$filePath=str_replace("\\", "/", $filePath);
	$webroot=$_SERVER['DOCUMENT_ROOT'];
	$webroot=str_replace("\\", "/", $webroot);
	
	// if the filePath doesn't begings with the web root path, don't allow the access
	if (strpos($filePath, $webroot)!==0)
		exit();
	
}

if (strpos($file, "vgetattendees")!==false) {
	
	$pos1=strpos($file, "?");
	$args=substr($file, $pos1+1);
	$argList=explode("&", $args);
	$params=array();
	foreach ($argList as $anArg) {
		$keyVal=explode("=", $anArg);
		$params[$keyVal[0]]=isset($keyVal[1])?$keyVal[1]:'';

	}
	$fromId=isset($params['from'])?$params['from']:'';
	if ($fromId!='') {
		$attDir="../attendees/";
		
		// track the concurrent attendee connections and report the number of connections to the management server
		$configFile="../site_config.php";
		if (isset($gScriptDir)) {
			$configFile=$gScriptDir.$configFile;
			$attDir=$gScriptDir.$attDir;
		}
		@include_once($configFile);
		$attTracker=new VTracker($attDir, $serverUrl);

		$userip=GetIP();
		$fromName=isset($params['from_name'])?rawurlencode($params['from_name']):'';
		$serverId=isset($params['server_id'])?$params['server_id']:'';
		$meetingId=isset($params['meeting_id'])?$params['meeting_id']:'';
		$attData="userid=$fromId&username=$fromName&userip=$userip&serverid=$serverId&meetingid=$meetingId";
		
		$attTracker->AddValue($fromId, 	$attData);
	}
	
	$args='';
	foreach ($params as $key=>$val) {
		if ($args!='')
			$args.="&";
		$args.=$key."=".rawurlencode($val);
	}
	$fileUrl=substr($file, 0, $pos1)."?".$args;
	$fileUrl.="&no_report=1";

	// get the attendees from the master server
	// append no_report so the master server wouldn't duplicate the report of attendee connections 
	$content=@file_get_contents($fileUrl);
	if ($content) {
		$cache=new VCache();
		$fileSize=strlen($content);
		$cache->WriteHeader($file, $fileSize);
		$cache->WriteContent($content, $fileSize, 0);
	}
	exit();

}

// to enable caching of the file on the server	
$noCaching=false;
if (isset($_GET['no_caching'])) {
	$noCaching=true;
}

$expTime=0;	// use default time
if (isset($_GET['exp_time'])) {
	$expTime=$_GET['exp_time'];
}

$cachingOnly=false;
if (isset($_GET['caching_only'])) {
	$cachingOnly=true;
}

$useBrowserCache=false;
if (isset($_GET['use_browser_cache'])) {
	$useBrowserCache=true;
}
// download the file
$downloadName='';
if (isset($_GET['download_name'])) {
	$downloadName=$_GET['download_name'];
}

if (isset($gScriptDir))
	$cacheDir=$gScriptDir."../cache/";
else
	$cacheDir="cache/";

// add a session dir to the cache dir path so we don't have all cache files in one directory, which slows down the performance
$sessionDir='';
if (isset($_GET['cache_session'])) {
	$sessionDir=$_GET['cache_session'];
}	
if ($sessionDir!='')
	$cacheDir.=$sessionDir."/";


$timely=false;
if (isset($_GET['timely'])) {
	// create a cache file that is only valid for 1 second.
	// this is needed to get live events files because the same request is made repeatedly the result is time sensitive
	$timely=true;
}
// max download speed in bytes per second; set it to 0 to disable throttling
//$maxSpeed=0;
//$maxSpeed=50*1024;
$maxSpeed=60*1024;

if (isset($_GET['speed']) && $_GET['speed']!='') {
	$maxSpeed=(integer)$_GET['speed'];
}
	
// check if this is a url	
if (strpos($file, "http://")===0 || strpos($file, "https://")===0) {
	
	// re-encode the url params in case it contains spaces
	$pos1=strpos($file, "?");
	if ($pos1>0) {
		$args=substr($file, $pos1+1);
		$argList=explode("&", $args);
		$params=array();
		foreach ($argList as $anArg) {
			$keyVal=explode("=", $anArg);
			if (isset($keyVal[0]))
				$params[$keyVal[0]]=isset($keyVal[1])?$keyVal[1]:'';

		}
		$args='';
		foreach ($params as $key=>$val) {
			if ($args!='')
				$args.="&";
			$args.=$key."=".rawurlencode($val);
		}
		$file=substr($file, 0, $pos1)."?".$args;
	}

/* can't use the check because it creates problems when caching servers reside on the same server as the master server
	$serverName='';
	if (isset($_SERVER['HTTP_HOST']))
		$serverName=$_SERVER['HTTP_HOST'];
	elseif (isset($_SERVER['SERVER_NAME']))
		$serverName=$_SERVER['SERVER_NAME'];
	
	$urlInfo = parse_url($file);
	$hostName='';
	if (isset($urlInfo["host"]))
		$hostName=$urlInfo["host"];
	
	if ($serverName==$hostName && $serverName!='') {
		// this is a local file
		// get the local file path of the url

		$path=$_SERVER['PHP_SELF'];
		$pathItems=explode("/", $path);
		$pathCount=count($pathItems)-2;
		$dir='';
		for ($i=0; $i<$pathCount; $i++)
			$dir.="../";

		$localPath=$dir.substr($urlInfo['path'], 1);
		VCache::WriteHeader($localPath, filesize($localPath));
		VCache::WriteFile($localPath, $maxSpeed);	

		exit();
	} else 
*/

	$mode=fileperms("./");
	if (!$noCaching && !MyMkDir($cacheDir, $mode, "index.html")) {
		// can't create cache files; simply redirect to the url
		header("Location: ".$file);
		exit();
	}
		
	if ($cachingOnly) {
		// get the content from the url ($file) and save it to a local cache file if it doesn't exist
		// return the cache file size only, not the content
		$cache=new VCache($cacheDir);
		$key=$cache->GetCacheKeyFromUrl($file, $timely);
		$cacheFile=	$cache->GetCacheFileFromKey($key);
		if (!$cache->IsCacheValid($cacheFile, false, $expTime)) {
			// get the content from the url and save it to the cache file
			$content='';
			$cache->AddValueFromUrl($file, $key, $content, true, false);
			if (file_exists($cacheFile))
				echo ("OK size=".filesize($cacheFile));
			else
				echo("ERROR");
		} else {
			echo ("OK size=".filesize($cacheFile));
		}
		exit();
		
	} else if ($noCaching) {
		
		// The code doesn't seem to be used anymore because no_caching is only used to get attendee data, which is handled previously already.
		// return the content from the url and write it to the output without caching it locally
		
		// if output throttling is off, simply redirect it to the url
		if ($maxSpeed==0)
			header("Location: ".$file);
		else {
			// throttling is on			
			// it will not work for a large file but we shouldn't get a large file anyway
			$content=@file_get_contents($file);
			if ($content) {
				$fileSize=strlen($content);
				$cache=new VCache();
				$cache->WriteHeader($file, $fileSize, $downloadName, $useBrowserCache);
				$cache->WriteContent($content, $fileSize, $maxSpeed);
			} else {
				header("Location: ".$file);
			}
		}
		exit();
		
	} else {

		$cache=new VCache($cacheDir);
		$key=$cache->GetCacheKeyFromUrl($file, $timely);

// this doesn't load the entire file and can be used to stream a large file (e.g. video)		
		$cacheFile=	$cache->GetCacheFileFromKey($key);
		if ($cache->IsCacheValid($cacheFile, false, $expTime)) {
			$cache->WriteHeader($cacheFile, filesize($cacheFile), $downloadName, $useBrowserCache);
			$cache->WriteFile($cacheFile, $maxSpeed);		
			exit();
		}
		
/* this one loads the entire file first so it may not work for large files
		$content='';
		// see if the file is already in the cache
		if ($cache->GetValue($key, false, $content)) {
			//echo ("read\n"); exit();
			$cacheFile=$cache->GetCacheFileFromKey($key);		
			$fileSize=strlen($content);
			$cache->WriteHeader($cacheFile, $fileSize, $downloadName, $useBrowserCache);
			$cache->WriteContent($content, $fileSize, $maxSpeed);
			exit();
		}
*/

		// file not in the cache. get it from the source and save it to the cache
		// only does it if on one is doing it
		// this will load the entire file to memory so don't use this to load a large file
		// for large files use "caching_only" to load it first and then stream it the code above
		$content='';
		if ($cache->AddValueFromUrl($file, $key, $content)) {
			$cacheFile=$cache->GetCacheFileFromKey($key);
			$fileSize=strlen($content);
			$cache->WriteHeader($cacheFile, $fileSize, $downloadName, $useBrowserCache);
			$cache->WriteContent($content, $fileSize, $maxSpeed);
			$cache->GarbageCollection();
			exit();
		} else {
			$content='';
			// couldn't add the file to the cache because someone is already adding it
			// wait for it to be available (up to 15 seconds)
			// if flock is blocking, there shouldn't be a wait here because the file should be in the cache now
			for ($i=0; $i<50; $i++) {
				if ($cache->GetValue($key, true, $content))
					break;
				usleep(300*1000);
			}

//			if ($cache->GetValue($key, true, $content)) {
				//echo ("read2\n"); exit();
				$cacheFile=$cache->GetCacheFileFromKey($key);
				$fileSize=strlen($content);
				$cache->WriteHeader($cacheFile, $fileSize, $downloadName, $useBrowserCache);
				$cache->WriteContent($content, $fileSize, $maxSpeed);
//			}
			exit();		
		}
		
/*	This is an attempt to replace the code above that loads the entire file but it is not quite working
// when using this to load sharing frames from a slave server, it causes very long delay; not sure why
// we don't really need this as the only time we need it is load a video file and we should use this for that.
		// file not in the cache. get it from the source and save it to the cache
		// only does it if on one is doing it
		$content='';
		if ($cache->AddValueFromUrl($file, $key, $content, false, false)) {
			// I am the one who added the cache from the url
			// write out the cache file
			VCache::WriteHeader($cacheFile, filesize($cacheFile), $downloadName, $useBrowserCache);
			VCache::WriteFile($cacheFile, $maxSpeed);
			// do this only if I am the one added the cache because we only want one call to this function	
			$cache->GarbageCollection();
			exit();			
		} else {
			
			// Someone else added the cache
			VCache::WriteHeader($cacheFile, filesize($cacheFile), $downloadName, $useBrowserCache);
			VCache::WriteFile($cacheFile, $maxSpeed);

			exit();		
		}
*/


	}
}


// reading a local file	
$timeOut=0;
if (isset($_GET['timeout']))
	$timeOut=(int)$_GET['timeout'];

if ($timeOut>$gMaxWaitTime)
	$timeOut=$gMaxWaitTime;

//	$file=$evtDir.$file;

if ($timeOut>0) {
	$startTime=time();
	$canusleep=-1;

	while (1) {
		if (file_exists($file))
			break;
		MSleep($gCheckFileDelay, $canusleep);
		$currTime=time();
		if (($currTime-$startTime)>$timeOut)
			break;
	}
}

if(is_file($file)){
	$cache=new VCache();
	$cache->WriteHeader($file, filesize($file), $downloadName, $useBrowserCache);
	$cache->WriteFile($file, $maxSpeed);		
	exit();

} else {
	exit();
}

/*
function UrlToLocalName($urlInfo)
{
	
	
	
}

function GetLocalFile($file, $maxSpeed, $canusleep)
{
	$fileInfo = pathinfo($file);
	$extension=$fileInfo["extension"];
	
	$contentType='';
	switch ($extension) {
		case 'xml':
		case 'XML':
			$contentType='text/xml';
			break;
		case 'swf':
		case 'SWF':
			$contentType='application/x-shockwave-flash';
			break;
		case 'flv':
		case 'FLV':
			$contentType='video/x-flv';
			break;
		case 'jpg':
		case 'JPG':
			$contentType='image/jpeg';
			break;
		case 'mp3':
		case 'MP3':
			$contentType='audio/mpeg';
			break;
		case 'inf':
		case 'txt':
			$contentType='text/plain';
			break;
		case 'php':
			return; // don't return php files
		default:
    		$contentType=filetype($file);
			break;

	}	
	$fileSize=filesize($file);
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-Length: " . $fileSize);
	header("Content-Type: $contentType");
	
	OutputFile($file, $maxSpeed, $canusleep);

}

function IsCacheing() {
	global $gCacheDir;
	
	// cacheing doesn't work because it may get a partially written cached file
	// always return false for now
	return false;

	if (isset($_GET['cache']) && $_GET['cache']=='1') {
		$ret=MyMkDir($gCacheDir, 0777, "index.html");
		return $ret;
	}
	return false;
}

function UrlToCacheName($url) {

	$fileInfo = pathinfo($url);
	$extension=$fileInfo["extension"];
	if ($extension!='')
		return md5($url).".$extension";
	else
		return md5($url);
}

// return the cahced file if it exists and is not older than $expTime (in sec.)
// this doesn't work now (see below)
function GetCachedFile($url, $expTime=3600) {
	global $gCacheDir;

	$cacheFile=	$gCacheDir.UrlToCacheName($url);
	$tempFile=$cacheFile.".tmp";
	// can't use file_exists because the file could be partially written
	if (file_exists($cacheFile)) {
		$curTime=time();
		$modTime=filemtime($cacheFile);
		if ($modTime>time()-$expTime)
			return $cacheFile;
//		else
//			@unlink($cacheFile);	// delete outdated file
	} elseif (file_exists($tempFile)) {
		
		// if the temp file is older than 30 sec, assume it is no good and delete it so we can download it again.
		$theTime=time()-30;
		$modTime=filemtime($tempFile);
		if ($modTime>$theTime)
			return $cacheFile;
//		else
//			@unlink($tempFile);	// delete outdated temp file
			
	}
	return '';	
}

function LoadUrlToCache($url) {
	global $gCacheDir;
		
	$cacheFile=	$gCacheDir.UrlToCacheName($url);
	$tempFile=$cacheFile.".tmp";
	$ofp=@fopen($tempFile, "a+b"); // use a+ so we don't truncate the file if we can't get the lock
	if (!$ofp)
		return '';
	
	// use non-block lock as it may take a while to download the file	
	if (flock($ofp, LOCK_EX)) {
		$ifp=@fopen($url, "rb");
		if ($ifp) {	
			rewind($ofp);
			ftruncate($ofp, 0);				
  			while(!feof($ifp)) {
				$res=fread($ifp, 10240);			
				fwrite($ofp, $res);
			}
			fflush($ofp);
			flock($ofp, LOCK_UN);
			fclose($ifp);
			fclose($ofp);
			
			// need this for Windows as rename would fail if the file exists
			if (file_exists($cacheFile))
				@unlink($cacheFile);
			@rename($tempFile, $cacheFile);
		} else {
			// can't get the url
			flock($ofp, LOCK_UN);
			fclose($ofp);
			@unlink($tempFile);
			return '';			
		}
		
	} else {
		// can't lock the file
		// still return the cache file so we can check for its availability
		fclose($ofp);
	}

	
	return $cacheFile;
	
}

function ClearCache() {
	
}

function OutputFile($dataFile, $bytesPerSec, $canusleep) {

	// if output speed throttling is on
	if ($bytesPerSec>0) {
		if ($canusleep)
			$bufSize=8192;
		else
			$bufSize=$bytesPerSec;
		
		$ifp=@fopen($dataFile, "rb");

		while ($buffer=fread($ifp, $bufSize)) {
			$length=strlen($buffer);
			if ($length<=0)
				break;
			print($buffer);					
			flush();
			
			if ($canusleep) {
				$bufTime=round(1000000*$length/$bytesPerSec);	// in micro second;
				if ($bufTime>1000000)
					$bufTime=1000000;
				usleep($bufTime);
			} else if ($length==$bufSize) {	// more data to read
				sleep(1);
			}
		}

		fclose($ifp);			
		
		
	} else {	
		@readfile($dataFile);
	}
	
}
*/
?>