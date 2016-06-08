<?php 
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */	
	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	include_once $includeFile;
	

	
    function fsize($file) {
		if (file_exists($file))
			return number_format(filesize($file)/1024, 2);
		else
			return 0;
    }
	$file=$GET_VARS['file'];
	if ($file=='' || !file_exists($file))
		exit();

	$srcName=$GET_VARS['src'];
	if ($srcName=='')
		$srcName=basename($file);
						
	// only allow downloading of files in the current folder or a sub folder
	if (strpos($file, "../")!==false || $file[0]=='/')
		die("File path not allowed.");
	// file path must contain vdocuments or vlibrary
	if (strpos($file, "vdocuments")===false && strpos($file, "vlibrary")===false)
		die("File path not allowed.");

	if (is_dir($file)) {
		die("Not supported");
// FIXME:
// It creates a zip file of a library folder but the files inside the zip are not valid.
// Seems to have something to do with binary files
		// this is a lib slide directory;
		// create a zip file
		// check if the zip file already exists in the cache folder
		$zipFile=$gCacheDir.md5($file).".zip";
		$hasCache=false;
		if (file_exists($zipFile)) {
			$fileSize=filesize($zipFile);
			$expTime=time()-60*60;	// one day old
			$modTime=filemtime($zipFile);
			if ($modTime<$expTime) {
				$hasCache=true;
			}
		}
		
		if (!$hasCache) {
			// check if zip is enabled for php
			if (!extension_loaded('zip')) {
				// zip is not enabled; quit
				die("ZIP extension is not enabled on the server.");
			}				
			umask(0);
			// $mode=fileperms("./");
			if (!MyMkDir($gCacheDir, 0777, "index.html"))
				die ("can't create ".$gCacheDir);

			if (file_exists($zipFile))
				unlink($zipFile);
				
			// create a zip file
			$zip = new ZipArchive();

			if ($zip->open($zipFile, ZIPARCHIVE::CREATE)!==TRUE) {
				die("cannot open $zipFile\n");
			}
			
			// find the xml file that describes the directory content
			// the xml file has the same name as the directory
			if ($file[strlen($file)-1]=='/') {
				$xmlFile=substr($file, 0, strlen($file)-1);
			} else {
				$xmlFile=$file;
			}
			$xmlFile.=".xml";
			$libDir='vlibrary/';
			$pos=strpos($file, $libDir);
			if ($pos===false)
				die("Not a valid library path name");
			$libPath=substr($file, 0, $pos+strlen($libDir));
			
			$xmlContent='';
			$xfp=@fopen($xmlFile, "r");
			if ($xfp) {
				$xmlContent=fread($xfp, filesize($xmlFile));
				fclose($xfp);
			} else {
				die("Couldn't find the table of content file.");
			}
			
			// use the file name without the extension for the zip dir name
			$srcParts=pathinfo($srcName);
			if (isset($srcParts['filename']))
				$dirName=$srcParts['filename'];
			else {
				$dirName=$srcParts['basename'];
				if (isset($srcParts['extension'])) {
					$dirName=str_replace(".".$srcParts['extension'], "", $dirName);
				}
			}
	
			// parse the xml file to get all the slides
			$slidesList=array();
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
			xml_set_character_data_handler($xml_parser, "parse_xml_data");
			xml_parse($xml_parser, $xmlContent, true);
			xml_parser_free($xml_parser);

			foreach($slidesList as $slideInfo) {
				$srcFile=$libPath.$slideInfo['fileName'];
				$localFile=$dirName."/".basename($srcFile);
				if (file_exists($srcFile)) {
					/** 
					 * addFile keeps the file open.
					 * Use addFromString to work around file descriptor number limitation (to avoid failure
					 * upon adding more than typically 253 or 1024 files to ZIP) */		
					// echo ("addFile ".$srcFile." ".$localFile."<br>\n");
					//if ($zip->addFile($srcFile, $localFile)!==true)
					//	die("Can't add file ".$srcFile);
					$contents=@file_get_contents($srcFile);
					if ($contents!==false) {
						$zip->addFromString( localFile, $contents );
					}

				}
			}

	
			if (!$zip->close())
				die("Couldn't create $zipFile");
			
		}
	
		$srcName.=".zip";
		$file=$zipFile;
		
	} else {
		$fileParts=pathinfo($file);
		$fileExt='';
		if (isset($fileParts['extension']))
			$fileExt=$fileParts['extension'];
		// don't allow downloading of certain file types
		if ($fileExt=='php')
			die("File type not allowed.");
								
		// if srcName doesn't contain a file extension, add that from the file path
		$srcParts=pathinfo($srcName);
		if (!isset($srcParts['extension']) || $srcParts['extension']=='') {
			$srcName.=".".$fileExt;
		}
	}


	if (isset($GET_POST['download']) || isset($GET_VARS['download'])) {

		// must add this for IE7 to work on SSL download
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');

		header("Content-Length: " . filesize($file));
		header("Content-Type: " . filetype($file));

		$srcName=str_replace(" ", "_", $srcName);
		header("Content-Disposition: attachment; filename=".$srcName);
//   			readfile($file);

		// obtain a shared lock to read the file
		$fp=@fopen($file, "rb");
		if ($fp && flock($fp, LOCK_SH)) {
			while (1) {
				$data=fread($fp, 8192);
				if (strlen($data)==0)
					break;
				echo $data;
			}
			flock($fp, LOCK_UN);
			fclose($fp);
		}

	} else {
		// must add this for IE7 to work on SSL download
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');

		echo "<html><head></head><body>\n";
		if ($msg!='')
			echo $msg."<br>";
		
//		$downloadScript="vscript.php";
		
//		echo '<form method="GET" action="'.$downloadScript.'" name="downloadForm" >';
		echo "<a href=\"".htmlspecialchars($file)."\">".htmlspecialchars($srcName)."</a> (".fsize($file)." KB) ";
//		echo "<input type='hidden' value='vdownload' name='s'>";
//		echo "<input type='hidden' value='".$file."' name='file'>";
//		echo "<input type='hidden' value='".$srcName."' name='src'>";
//		echo '<input type="submit" value="download" name="download">';
//		echo "</form></body></html>";
	}
	
	
	// parse slide xml files
	function start_xml_tag($parser, $name, $attribs) {
		global $slidesList;
		if ($name=='slides') {
			$slidesList=array();

		} else if ($name=='slide') {
			$index=count($slidesList)-1;
			$slidesList[$index]=array();
			foreach ($attribs as $key => $val) {
				$slidesList[$index][$key].=$val;			
			}	
		}
	}

	function end_xml_tag($parser, $name) {

	}
	function parse_xml_data($parser, $data) {

	}

?>
