<?php
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * 
 */

include_once ("mimetype.php");

class VCache
{
	var $cache_dir="cache/";			// directory to store the cache file
	
	var $exp_time=3600;					// life time of a cache file in seconds
	var $hash_key='my3971';				// some random string
	var $lock_file=".lock";				//
	var $cache_prefix="f_";			
	var $log_file="access.log";
	var $logging=false;					// turn on/off logging (make sure to turn off for production)
	var $dump_file='garbage.dump';
	var $dump_time=900;					// how often the garbage files are dumped in seconds
	
	/**
	* 
	*/
	function VCache($cacheDir=null)
	{
		if ($cacheDir)
			$this->cache_dir=$cacheDir;
		
	}
	
	/**
	* @static
	*/	
	function WriteHeader($fileName, $fileSize, $downloadName=null, $browserCache=false) {
		$contentType=my_mime_content_type($fileName); 
	
		if ($browserCache) {
			// allow the use of the browser cache; this is mainly for slide files that have a unique file name
			header('Cache-Control: max-age=3600');
		} else {
			// disable browser caching
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
		}
		if ($fileSize)
			header("Content-Length: ".$fileSize);
		if ($contentType && $contentType!='')
			header("Content-Type: ".$contentType);
		if ($downloadName && $downloadName!='')
			header("Content-Disposition: attachment; filename=".$downloadName);
		
		flush();
	}
	
	/**
	* @static
	*/
	function WriteContent($content, $contentSize, $bytesPerSec=0) {
		// if output speed throttling is on
		if ($bytesPerSec>0) {
			$canusleep=function_exists('usleep');
			if ($canusleep)
				$bufSize=8192;
			else
				$bufSize=$bytesPerSec;
			
			$start=0;
			while ($start<$contentSize) {
				$length=$bufSize;
				if ($start+$length>$contentSize)
					$length=$contentSize-$start;
				echo substr($content, $start, $length);
				flush();
				$start+=$length;
				
				if ($canusleep) {
					$bufTime=round(1000000*$length/$bytesPerSec);	// in micro second;
					if ($bufTime>1000000)
						$bufTime=1000000;
					usleep($bufTime);
				} else if ($length==$bufSize) {	// more data to read
					sleep(1);
				}
			}
			
			
		} else {
			echo($content);
			
		}
	}
	
	function WriteFile($dataFile, $bytesPerSec) {
		
		// if output speed throttling is on
		if ($bytesPerSec>0) {
			$canusleep=function_exists('usleep');
			if ($canusleep)
				$bufSize=8192;
			else
				$bufSize=$bytesPerSec;
			
			$lockFile=$dataFile.$this->lock_file;
/*			
			$lfp=@fopen($lockFile, "a");
			// obtain a shared lock to read the cache file
			if ($lfp && flock($lfp, LOCK_SH)) {
				
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
				
				flock($lfp, LOCK_UN);
				fclose($lfp);
			}
*/
			$lfp=@fopen($lockFile, "r");
			
			// obtain a shared lock to read the cache file
			if ($lfp)
				flock($lfp, LOCK_SH);
			
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
			
			if ($lfp) {
				flock($lfp, LOCK_UN);
				fclose($lfp);
			}
			
		} else {	
			@readfile($dataFile);
		}
		
	}
	function LogAccess($message)
	{
		if (!$this->logging)
			return;
		$logFile=$this->cache_dir.$this->log_file;
		$logFp=@fopen($logFile, "a");
		if ($logFp) {
			if (flock($logFp, LOCK_EX)) {
				$size=strlen($value);
				//$pid=getmypid();
				$ip=$_SERVER['REMOTE_ADDR'];
				
				fwrite($logFp, date("Y F d H:i:s")." $ip $message\r\n");
				fflush($logFp);
				flock($logFp, LOCK_UN);
			}
			fclose($logFp);
			chmod($logFile, 0777);
		}
		
	}
	
	function GetCacheFileFromKey($key)
	{
		return $this->cache_dir.$key;
	}
	
	// Add a value to the cache from a URL
	// Return false if the key already exists
	function AddValueFromUrl($url, $key, &$value, $alwaysAdd=false, $returnValue=true)
	{
		$cacheFile=	$this->GetCacheFileFromKey($key);
		//		$this->LogAccess("AddValueFromUrl $key");			
		$lockFile=$cacheFile.$this->lock_file;
		$tempFile=$cacheFile.".tmp";
		$lfp=@fopen($lockFile, "w");
		$added=false;
		$theTime=time();
		// obtain an exclusive lock to add a cache file
		if ($lfp && flock($lfp, LOCK_EX)) {
			
			if ($alwaysAdd || !$this->IsCacheValid($cacheFile)) {
				if ($this->logging) {
					$this->LogAccess("Check $key size=".filesize($cacheFile)." mtime=".filemtime($cacheFile)." now=".time()." diff=".(time()-filemtime($cacheFile)));			
				}
				
				if ($fp = @fopen($tempFile, "wb")) {
					
					if ($this->logging) {
						$this->LogAccess("Request $key $url");
					}				
					
					$sfp=@fopen($url, "rb");
					
					if ($sfp) {	
						if ($returnValue)					
							$value='';
						$size=0;
						while (1) {
							//while (!feof($sfp)) {
							$data=fread($sfp, 8192);
							if ($data) {
								$len=strlen($data);
								fwrite($fp, $data);
								$size+=$len;
							} else {
								$len=0;
							}
							if ($len==0 && feof($sfp))
								break;
							
							if ($returnValue)
								$value.=$data;
						}
						fclose($sfp);
						
						if ($this->logging) {
							$message="Added $key ($size bytes) from $url";
							$this->LogAccess($message);
						}
						
					}
					$added=true;
					
					fflush($fp);
					fclose($fp);
					if (file_exists($cacheFile))
						unlink($cacheFile);
					rename($tempFile, $cacheFile);
					chmod($cacheFile, 0777);
				}
			}
			
			flock($lfp, LOCK_UN);
		}
		if ($lfp)
			fclose($lfp);
		
		chmod($lockFile, 0777);	
		
		
		return $added;
	}
	
	function GetValue($key, $allowEmpty, &$value)
	{		
		$cacheFile=	$this->GetCacheFileFromKey($key);
		//		$str=$allowEmpty?"allowEmpty":"";
		//		$this->LogAccess("GetValue $key $str");
		if (!$this->IsCacheValid($cacheFile))
			return false;
		if (!$allowEmpty && filesize($cacheFile)==0) {
			$timeDiff=time()-filemtime($cacheFile);
			if ($timeDiff<=1) {
				// need to wait 1 second for the empty cache file to become invalid
				sleep(1);
			}
			return false;
		}
		
		$got=false;
		$lockFile=$cacheFile.$this->lock_file;
		$lfp=@fopen($lockFile, "a");
		// obtain a shared lock to read the cache file
		if ($lfp && flock($lfp, LOCK_SH)) {
			//echo ("reading from $cacheFile\n");
			$value=@file_get_contents($cacheFile);
			$got=true;
			flock($lfp, LOCK_UN);
			if ($this->logging) {
				$this->LogAccess("Got $key (".strlen($value)." bytes)");
			}
		}
		if ($lfp)
			fclose($lfp);
		
		return $got;	
		
	}
	
	function GetCacheKeyFromUrl($url, $timely=false)
	{
		$fileInfo = pathinfo($url);
		$extension=$fileInfo["extension"];
		$key='';
		$timeHash='';
		// if the cache file should expire after more than 1 sec old
		if ($timely) {
			$timeHash=time();
			/*
						// add the time stamp to the hash key
						// first find out if a file exists with a time stamp of 1 second old
						// use that instead of creating a new key
						$timeHash=time()-1;	
						if ($extension!='')
							$key=$this->cache_prefix.md5($url.$this->hash_key.$timeHash).".$extension";
						else
							$key=$this->cache_prefix.md5($url.$this->hash_key.$timeHash);
						$cacheFile=	$this->GetCacheFileFromKey($key);
						clearstatcache();	// need this to make sure file info is not gotten from a cache
						if (!file_exists($cacheFile)) {
							// no cache file exists for t-1 second
							// use the current time stamp
							$timeHash=time();
						}
			*/
		}
		
		if ($extension!='')
			$key=$this->cache_prefix.md5($url.$this->hash_key.$timeHash).".$extension";
		else
			$key=$this->cache_prefix.md5($url.$this->hash_key.$timeHash);
		
		//		if ($this->logging)
		//			$this->LogAccess("GetKey $key $url");
		
		return $key;	
	}
	
	// return true if a cache file exists and has not expired
	// set excludeEmptyFile to false to consider an empty file invalid (otherwise, an empty file is still valid as long as it hasn't expired.)
	// set cacheExpTime to 0 to use the default cache exp. time
	function IsCacheValid($cacheFile, $excludeEmptyFile=false, $cacheExpTime=0)
	{
		$valid=false;
		clearstatcache();	// need this to make sure file info is not gotten from a cache
		if (file_exists($cacheFile)) {
			$fileSize=filesize($cacheFile);
			
			$cExpTime=$this->exp_time;
			if ($cacheExpTime>0)
				$cExpTime=$cacheExpTime;
			// if the file is empty, it is considered invalid after 1 second (if excludeEmptyFile is false)
			if (!$excludeEmptyFile && $fileSize==0)
				$expTime=time()-1;
			else
				$expTime=time()-$cExpTime;
			$modTime=filemtime($cacheFile);
			if ($modTime>=$expTime) {
				$valid=true;
			}
			
		}
		
		return $valid;
	}
	
	function DeleteCacheFile($cacheFile)
	{
		$lockFile=$cacheFile.$this->lock_file;
		$lfp=@fopen($lockFile, "w");
		// need to obtain an exclusive lock before we can delete the cache file
		if ($lfp && flock($lfp, LOCK_EX)) {
			@unlink($cacheFile);
			flock($lfp, LOCK_UN);
		}
		if ($lfp)
			fclose($lfp);
		@unlink($lockFile);		// not sure if this would cause problems since it is outside of the critical session
		
	}
	
	function GarbageCollection()
	{
		clearstatcache();	// need this to make sure file info is not gotten from a cache	
		
		// this function can be very slow as it has to traverse all files in the cache directory
		// see when the last time the garbage is dumped by checking the mod time of the dump_file
		// only dump the garbage files again if the dump_time is reached
		$dumpFile=$this->cache_dir.$this->dump_file;
		if (file_exists($dumpFile)) {
			$modTime=filemtime($dumpFile);
			// dumped recently; skip
			if ($modTime>=(time()-$this->dump_time))
				return;
		} else {		
			$fp=@fopen($dumpFile, "w");
			if ($fp) {
				fclose($fp);
				@chmod($dumpFile, 0777);
			}
		}
		touch($dumpFile);
		if ($this->logging)
			$this->LogAccess("Dumping garbage. $modTime");
		
		$dir=$this->cache_dir;
		$count=0;
		$max=100;
		if ($dh = @opendir($dir)) {
			$fileList = array();
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!=".." && $file!=$this->dump_file) {
					$fileList[] = $dir.$file;
				}
			}
			closedir($dh);
			$expTime=time()-$this->exp_time;
			foreach ($fileList as $fileItem) {
				/*
								// don't process the lock file. it will be removed in DeleteCacheFile
								if (strpos($fileItem, $this->lock_file)>0)
									continue;
								if (!$this->IsCacheValid($fileItem, true)) {
									$this->DeleteCacheFile($fileItem);
								}
				*/
				$modTime=filemtime($fileItem);
				if ($modTime<$expTime) {
					@unlink($fileItem);
					$count++;
					// quit after we have reached the max. number
					if ($count>$max)
						break;
				}
				
			}
		}
		// delete will succeed only if the dir is empty
		@unlink($dir);
		
	}
	function DeleteCacheDir()
	{
		$dir=$this->cache_dir;
		if ($dh = @opendir($dir)) {
			$fileList = array();
			while (($file = @readdir($dh)) !== false) {
				if ($file!="." && $file!="..") {
					$fileList[] = $dir.$file;
				}
			}
			closedir($dh);
			
			foreach ($fileList as $fileItem) {
				@unlink($fileItem);
			}
		}
		@unlink($dir);
	}
}


?>