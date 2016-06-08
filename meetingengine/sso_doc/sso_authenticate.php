<?php
/**
 * SSO authentication example code
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 */

// check if the required paramters are missing
if (!isset($_GET['StorageSessionId']) || !isset($_GET['StorageUserName'])) {
	header("HTTP/1.0 401"); 
	exit();
}

$sessionId=$_GET['StorageSessionId'];	// session id in the client application
$userName=$_GET['StorageUserName'];	// user name in the client application

// return HTTP status code based on the authentication result
if (AuthenticateUser($userName, $sessionId)) {
	header("HTTP/1.0 200");
} else {
	header("HTTP/1.0 401");
}

function AuthenticateUser($userName, $sessionId) {
	// add code here to verify if the user is signed in to the session
	
	// return true if the user is signed in

	// otherwise return false
	return false;
	
}

?>