<?php
// this is a test script for audio recording encoding.
	include_once("includes/common_lib.php");
	
	$recUrl="http://66.147.167.136/persony/remote/conference/";
	$accessKey="8447529801689237";
	$number="8882220475";
	$code="6124921";
	$recId="37987584";
	
	$postData="phone=$number&id=$code&mp3=C&file=$recId";
	if ($accessKey!='')
		$postData.="&signature=".md5($postData.$accessKey);
	else
		$postData.="&nosig=1";

	$res=HTTP_Request($recUrl, $postData, 'POST', 30);
	if ($res==false) {
		die("Couldn't get a response from the server.");

	}
	echo $res;

?>