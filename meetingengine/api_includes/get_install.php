<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

	require_once('api_includes/common.php');
	$installDir="vinstall";
	$scriptDir="scripts";

	if ($cmd=='GET_INSTALL_DIR') {
		GetArg("dir", $dir);
		if ($dir!=$installDir && $dir!=$scriptDir)
			API_EXIT(API_ERR, "Invalid directory requested.");
		$list= getdirfiles($dir);
		
		echo "OK";
		if ($list!='')
			echo "\n".$list;
		exit();
		
	} else if ($cmd=='GET_INSTALL_FILE') {
		GetArg("file", $file);
		
		if (strpos($file, $installDir)!=0 && strpos($file, $scriptDir)!=0) {
			API_EXIT(API_ERR, "Invalid file path requested.");
		}
		if (strpos($file, '..')!==false) {
			API_EXIT(API_ERR, "Illegal file path requested.");
		}		

		if (file_exists($file)) {
			// must add this for IE7 to work on SSL download
			header('Pragma: private');
			header('Cache-control: private, must-revalidate');
			
			header("Content-Length: ".@filesize($file)); 
			header("Content-Type: " . @filetype($file));
			//@readfile($newFile);
			$fp=fopen($file, "r");
			fpassthru($fp);
			fclose($fp);
		}
		exit();			
		
	}


	function getdirfiles($theDir, $namesContain='')
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
						}
							
						$fileList.="\n";
					}
				}
			} 
			closedir($dh);
		}
		return $fileList;
	}

?>