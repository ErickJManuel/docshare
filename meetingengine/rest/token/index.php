<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


chdir("../../");

if (isset($_GET['token']) && isset($_GET['brand']) && isset($_GET['user_id']) && isset($_GET['meeting_id']))
{
require_once("dbobjects/vtoken.php"); 
	$token=new VToken();
	$token->GetToken($_GET['token'], $tokenInfo);

	if (isset($tokenInfo['token'])) {
		if ($_GET['brand']==$tokenInfo['brand'] && 
			$_GET['user_id']==$tokenInfo['user_id'] && 
			($_GET['meeting_id']==$tokenInfo['meeting_id'] || $tokenInfo['user_id']!='0')
			)
		{
			echo "OK";
			echo "&brand=".$tokenInfo['brand'];
			echo "&user_id=".$tokenInfo['user_id'];
			echo "&meeting_id=".$tokenInfo['meeting_id'];
			if (isset($tokenInfo['permission']))
				echo "&permission=".$tokenInfo['permission'];
			exit();
		} else {
			header("HTTP/1.0 401");
			exit();
		}
	} else {
		header("HTTP/1.0 404");
		exit();
	}
} else {
	header("HTTP/1.0 400");
	exit();	
}

?>

