<?php 

// (c)Copyright 2007, Persony, Inc. All rights reserved.
	
	include "vpresent_version.php";
/*
	include_once "../server_config.php";
	if (defined("LOG_DIR") && LOG_DIR!='') {
		$today=date("y_m_d");
		$logFile=LOG_DIR."download_$today.log";
	} else
		$logFile='';
	
	if (isset($_SERVER['REMOTE_ADDR']))
		$userip=$_SERVER['REMOTE_ADDR'];
	else
		$userip='';
	$hostname=@gethostbyaddr($userip);
	$product="vpresent";
*/
	if (isset($_GET['installer']))
		$vpresent_installer=$_GET['installer'];
	if (!isset($vpresent_installer) || !file_exists($vpresent_installer))
		die ("Installer not defined or not found.");
		
	$downloadFile=basename($vpresent_installer);
//	$downloadUrl=$vpresent_installer;
/*	
	if ($logFile!='')
		$fp=@fopen($logFile, "a");
	else
		$fp=null;
	if ($fp) {
		$datestr=date("Y F d H:i:s");
		$logstr="${datestr} ${userip} ($hostname) ${downloadFile}\r\n";
		@fwrite($fp, $logstr);
		@fclose($fp);
	}
*/	
//	header("Location: $downloadUrl");

	$pathItems=pathinfo($downloadFile);
	$ext=strtolower($pathItems['extension']);
	if ($ext!='exe' && $ext!='msi')
		die ("Invalid file type '$ext'");
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Length: " . filesize($downloadFile));
	if ($ext=='exe')
		header("Content-Type: application/exe");
	else
		header("Content-Type: application/x-ole-storage");	// for MSI files
	header("Content-Disposition: attachment; filename=${downloadFile}");
	@readfile($downloadFile); 
	
	
?>
