<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

$gXMLHeader="<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

header('Pragma: private');
header('Cache-control: private, must-revalidate');

if (isset($_GET['download'])) {
	
	$size=$_GET['download']; // in KB

	$file="vtest.jpg";
	$fsize=filesize($file);
	if ($fsize==0)
		return;
	
	// find out how many times we need to repeat the file to reach the download size
	$num=ceil($size*1024/$fsize);
	$fsize*=$num;
	
    header("Content-Length: " . $fsize);
	header("Content-Type: image/jpeg");
	
//	readfile($file);
	$buffer=file_get_contents($file);
	for ($i=0; $i<$num; $i++) {
		print $buffer;
	}
	
	
} elseif (isset($_GET['upload'])) {
	$buffer=file_get_contents("php://input");

	header("Content-Type: text/xml");

	if ($buffer) {
		print "<return size=\"".strlen($buffer)."\" />";
//		print "OK ".strlen($buffer);
	} else {
		print "<error/>";
//		print "ERROR";
	}
	
} elseif (isset($_GET['ping'])) {
	
	header("Content-Type: text/xml");

//	print "OK";
	print "<return/>";

} elseif (isset($_GET['get_url'])) {
	$url=$_GET['get_url'];
	$start=GetMicroTime();
	$fp=fopen($url, "rb");
	$fsize=0;
	if ($fp) {
  		while(!feof($fp)) {
			$res=fgets($fp, 8096);
			if ($res)
				$fsize+=strlen($res);
			else
				break;
		}
		fclose($fp);
	}
	$delay=(GetMicroTime()-$start);
	$kbps=round(($fsize*8/1024)/$delay);
	$msec=round($delay*1000);
	
	header("Content-Type: text/xml");
	print "<return size=\"$fsize\" time=\"$msec\" speed=\"$kbps\"/>";
	
//	echo ("file size=".$fsize." download time= $delay sec speed= ".$kbps." kbps\n");
	
}



function GetMicroTime(){ 
	list($usec, $sec) = explode(" ",microtime()); 
	return ((float)$usec + (float)$sec); 
}

?>