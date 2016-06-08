<?php
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * Use a file to track the "alive" status of a value (such as a meeting paticipant)
 * Each live participant should call the function AddValue at least once every 30 seconds (defined in $exp_time) to be "alive".
 * AddValue will trigger the report of the number of "live" files in the tracking directory by calling a url no more than once every 3 seconds (defined in $report_time)
 * 
 */
 
class VTracker
{
	var $exp_time=30;					// expiration time of a tracking file in seconds
	var $report_time=3;					// report interval of a tracking file status in seconds
	var $report_url='';					// url to call to report the traking file status
	
	var	$hash_key='tr1357';				// some random string
	var	$dir;							// directory to store the files
	var $report_file="report.stat";
	var $file_prefix="f_";
	var $logging=false;					// turn on/off logging
    
    /**
    * 
    */
    function VTracker($dir, $reportUrl)
    {
		$this->dir=$dir;
		$this->report_url=$reportUrl;
		// create the dir if it doesn't exist
		if (!is_dir($dir)) {
			$mode=fileperms("./");
			umask(0);
			if (@mkdir($dir, $mode)) {
				@chmod($dir, $mode);
				// add index file
				$indexFilePath=$dir."/index.htm";
				$ofp=@fopen($indexFilePath, "w");
				if ($ofp) {
					fwrite($ofp, "<html></html>");
					fclose($ofp);
					@chmod($indexFilePath, $mode);
				}
			}
		}

	}
    /**
	* Call the function at least once every 30 seconds (defined in $exp_time) to stay "alive".
    */	
	function AddValue($key, $value)
	{
		$file=$this->GetFileFromKey($key);
		$this->AddFile($file, $value);
		
		if ($this->IsReportDue()) {
			$this->GarbageCollection();
			$this->ReportValues();
		}

		return true;
	}
	
	function Log($message) {
		$fp=fopen($this->dir."log.log", "a");
		fwrite($fp, $message."\r\n");
		fclose($fp);
	}
	
	function AddFile($file, $data='')
	{
		$fp=@fopen($file, "w");
		if (!$fp)
			return;
			
		if (flock($fp, LOCK_EX)) {
			fwrite($fp, $data);
			fflush($fp);
			flock($fp, LOCK_UN);
		}
	
		fclose($fp);
		chmod($file, 0777);

		if ($this->logging) {
			clearstatcache();	// need this to make sure file info is not gotten from a cache
			$this->Log("add $file time=".time()." mtime=".filemtime($file));
		}
	
		return true;
	}	
	/*
	* Check if a report is due.
	* We only want to report the value no more than once during the report_time interval
	*/
	function IsReportDue()
	{
		$file=$this->dir.$this->report_file;
		clearstatcache();	// need this to make sure file info is not gotten from a cache
		if (!file_exists($file)) {
			$this->AddFile($file);
			return true;
		} else if ((time()-filemtime($file))>$this->report_time) {
			touch($file);
			return true;
		}
		return false;
	}
	/*
	* Report of the number of "live" files in the tracking directory by calling $report_url
	*/
	function ReportValues()
	{
		// report the number of active files
		$count=0;
		$dir=$this->dir;
		if ($dh = @opendir($dir)) {
			while (($file = @readdir($dh)) !== false) {
				if (strpos($file, $this->file_prefix)!==false) {
					$count++;
				}
			}
			closedir($dh);
		}
		
		
		$url=$this->report_url."api.php?cmd=SET_STATS";
		$url.="&server_name=".$_SERVER['SERVER_NAME'];
		$url.="&attendees=$count";
		$url.="&report_id=".md5(realpath($this->dir));	// append an id that is tied to the report directory
		
		if ($this->logging)
			$this->Log("report $url");
		
		@file_get_contents($url);
	}
	
	function GetFileFromKey($key)
	{
		return $this->dir.$this->file_prefix.md5($key.$hash_key);
	}
	
	function IsFileExpired($file)
	{
		$expired=false;
		if (file_exists($file)) {
			$expTime=time()-$this->exp_time;
			$modTime=filemtime($file);
			if ($modTime<$expTime) {
				$expired=true;
			}
		}
			
		return $expired;
	}
	
    function GarbageCollection()
    {
		$dir=$this->dir;
		if ($dh = @opendir($dir)) {
			$fileList = array();
			while (($file = @readdir($dh)) !== false) {
				if (strpos($file, $this->file_prefix)===0) {
					$fileList[] = $dir.$file;
				}
			}
			closedir($dh);
			clearstatcache();	// need this to make sure file info is not gotten from a cache
			foreach ($fileList as $fileItem) {
				if ($this->IsFileExpired($fileItem)) {
					
					if ($this->logging)
						$this->Log("delete $fileItem time=".time()." mtime=".filemtime($fileItem));

					@unlink($fileItem);
				}
			}
		}
	}
}
?>