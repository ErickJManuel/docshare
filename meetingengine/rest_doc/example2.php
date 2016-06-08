<?php
/**
 * Persony REST API example code
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 *	Create a new group
 */
 
// replace with your api url, key and brand id with those from the Administration/API page
$apiUrl="http://license.persony.net/wc2/rest/";
$apiKey='123456cce6f034c30d407f327ea88cd';
$brand='1200001';

// add a group
$apiObj="group";	// API object
$method='POST';	// Use POST to add a group
$name="Sales & marketing";
$webServerId="101"; // Assign a web conference server to the group. Find the id from the Administration/Hosting page.

// Encode all request values
$request="brand=".urlcode($brand)."&method=".urlencode($method)."&name=".urlencode($name)."&webserver_id=".urlencode($webServerId);

// Use non-encoded values for the signature computation
$sigStr="brand=$brand&method=$method&name=$name&webserver_id=$webServerId";
$signature=md5($sigStr.$apiKey);	// compute the signature for the request
$request.="&signature=".$signature;	// append the signature to the request

$apiObjUrl=$apiUrl.$apiObj."/"; // trailing slash is required
$resp=http_post_request($apiObjUrl, $request);
if (!$resp)
	die("Couldn't get a response from ".$apiObjUrl);

// show the xml response from the API call
// must add the cahche control headers for IE7 to work on SSL requests
header('Pragma: private');
header('Cache-control: private, must-revalidate');
header("Content-type: text/xml");
echo $resp;

// curl must be enabled for this function to work
function http_post_request($url, $data){
	
	if (!function_exists('curl_init'))
		die("Curl not enabled.");

	$ch = curl_init($url);

	// disable ssl site certificate verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	// return the response data
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);			

	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

?>