<?php
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * 
 */
/*
if (isset($gScriptDir)) {
require_once $gScriptDir."iswfcommon.php";
require_once $gScriptDir."iswfdataextractor.php";
} else {
require_once "iswfcommon.php";
require_once "iswfdataextractor.php";
}
*/
/**
 * 
 * @author luzw
 *
 */
class SwfProcessor
{
	/**
	 * 
	 * @param $gScriptDir
	 * @param $swfpath
	 * @param $directory
	 * @param $segment
	 * @param $iskeyframe
	 * @param $islast
	 * @param $swfNumber
	 * @return unknown_type
	 */
//	public function processSwfUpload($gScriptDir, $swfpath, $directory, $segment, $iskeyframe, $islast, $swfNumber) {
	public function processSwfUpload($swfpath, $directory, $iskeyframe, $swfNumber) {
//		if( file_exists($directory . __SESSION_DESCR_FILENAME__)) {
//			if($segment == '' || $segment == 0 || $islast == 1) {
//				$directory = getIphoneVframesPath($directory);
//				if($swfNumber == -1) {
//					$number = $this->getSwfNumber($gScriptDir, $directory, $iskeyframe);
//				} else {
					$number = $swfNumber;
//				}
				$filename = $directory . __IPHONE_FILE_PREFIX__.$number.".xml";
				if (!file_exists($filename)) {
					$extractor = new SwfDataExtractor($swfpath, $iskeyframe, $number);
					$swfXmlData = $extractor->returnSwfXml();
					$this->saveXmlToFile($swfXmlData, $filename);
				}
//				$this->updateSessionFile($directory, $swfNumber, $iskeyframe);
//			}
//		}
	}
	
	/**
	 * 
	 * @param $swfXmlData
	 * @param $outpath
	 * @return unknown_type
	 */
	public static function saveXmlToFile($swfXmlData, $outpath) {

		$fp = fopen($outpath, "w+");
		if ($fp && flock($fp, LOCK_EX)) {
			fwrite($fp, $swfXmlData->asXML());
			flock($fp, LOCK_UN);
		}
		if ($fp) {
			fclose($fp);
			@chmod($outpath, 0777);
		}
	}
/*	
	public static function createSessionFile($directory, $sessionid, $last=0, $lastkey=0) {
		$filepath = $directory . __SESSION_DESCR_FILENAME__;

		$xmlstr = "<SESSION><ID>" . htmlentities($sessionid) . "</ID><LAST>$last</LAST><LASTKEY>$lastkey</LASTKEY></SESSION>";
		$el = simplexml_load_string($xmlstr);
		$fp = fopen($filepath, "w");
		if ($fp && flock($fp, LOCK_EX)) {
			fwrite($fp, $el->asXML());
			flock($fp, LOCK_UN);
		}
		if ($fp)
			fclose($fp);

	}
	
	public static function updateSessionFile($directory, $lastFile, $iskeyframe) {		
		$filepath = $directory . __SESSION_DESCR_FILENAME__;
		$fp = fopen($filepath, "r+");
		if ($fp && flock($fp, LOCK_EX)) {		
			$data = fread($fp, filesize($filepath));
			$xml = simplexml_load_string($data);
			$xml->LAST = $lastFile;
			if($iskeyframe == 1) {
				$xml->LASTKEY = $lastFile;
			}
			ftruncate($fp, 0);
			fseek($fp, 0);
			fwrite($fp, $xml->asXML());
			flock($fp, LOCK_UN);
		}
		fclose($fp);
		return $ret;
	}
*/	

	public static function getSessionNumbers($directory) {
/*
		$filepath = $directory . __SESSION_DESCR_FILENAME__;
		$ret=array();
		if (file_exists($filepath)) {
			$xml = simplexml_load_file($filepath);
			$ret[1] = (string)$xml->LASTKEY;
			$ret[0] = (string) $xml->LAST;
		}
		return $ret;
*/

		$inffile = $directory.'vsession.inf';
		$ret=array();
		if (file_exists($inffile)) {
			$fp=fopen($inffile, "rb");
			if ($fp && flock($fp, LOCK_SH)) {
				$line = fgets($fp, 64); 
				list($ret[1], $ret[0]) = sscanf($line, "KeyFrame=%d&LastFrame=%d");
				fclose($fp);
			}
		}
		return $ret;
	}
	public static function startConversion($directory) {
		$filepath = $directory . __IPHONE_START__;
		if (file_exists($filepath))
			touch($filepath);
		else {
			$fp=@fopen($filepath, "w");
			if ($fp) {
				fclose($fp);
				@chmod($filepath, 0777);
			}
		}		
	}
	public static function endConversion($directory) {
		$filepath = $directory . __IPHONE_START__;
		if (file_exists($filepath))
			unlink($filepath);
	}
	public static function isConverting($directory) {
		$filepath = $directory . __IPHONE_START__;
		
		// if the start file exists and has been touched recently (by an iphone client request in vistream.php), conversion is needed
		if (file_exists($filepath)) {
			$modTime=filemtime($filepath);
			if ($modTime>=(time()-60))
				return true;
		}
		
		return false;	
	}
	
	/**
	 * 
	 * @param $gScriptDir
	 * @param $directory
	 * @param $iskeyframe
	 * @return unknown_type
	 */
/*
	private function getSwfNumber($gScriptDir, $directory, $iskeyframe) {		
		$filepath = $directory . __SESSION_DESCR_FILENAME__;
		$fp = fopen($filepath, "r+");
		if ($fp && flock($fp, LOCK_EX)) {		
			$data = fread($fp, filesize($filepath));
			$xml = simplexml_load_string($data);
			$ret = (string)$xml->LAST;
			$xml->LAST = ++$ret;
			if($iskeyframe == 1) {
				$xml->LASTKEY = $ret;
			}
			ftruncate($fp, 0);
			fseek($fp, 0);
			fwrite($fp, $xml->asXML());
			flock($fp, LOCK_UN);
		}
		fclose($fp);
		return $ret;
	}
*/
}
?>
