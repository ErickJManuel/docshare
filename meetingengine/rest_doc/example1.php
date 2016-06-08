<?php
/**
 * Persony REST API example code
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 * Add a new meeting to a member and then return all meetings of the member
 */
 
// replace with your api url, key and brand id with those from the Administration/API page
$apiUrl="http://license.persony.net/wc2/rest/";
$apiKey='123456cce6f034c30d407f327ea88cd';
$brand='1200001';

$memberId='5500001'; // user_id of the member (find the id from the Adminstration/Members page)

// add a meeting to the member
$apiObj="meeting";	// API object
$method='POST';	// use POST to add a meeting
$title="My First & Only Meeting"; // title of the meeting.

// The title parameter should be url-encoded because it contains an ampersand
$request="brand=$brand&method=$method&member_id=$memberId&title=".urlencode($title);

// The signature should be computed from non-encoded values for POST requests
$sigStr="brand=$brand&method=$method&member_id=$memberId&title=$title";

$signature=md5($sigStr.$apiKey);	// compute the signature for the request
$request.="&signature=".$signature;	// append the signature to the request string

$apiObjUrl=$apiUrl.$apiObj."/"; // trailing slash is required
$resp=http_post_request($apiObjUrl, $request);
if (!$resp)
	die("Couldn't get a response from ".$apiObjUrl);

// get all meetings of the member
$apiObj="meetings";	// API object
$request="brand=".$brand."&member_id=".$memberId;
$signature=md5($request.$apiKey);	// compute the signature for the request
$request.="&signature=".$signature;	// append the signature to the request string
$apiObjUrl=$apiUrl.$apiObj."/?".$request;
$resp=@file_get_contents($apiObjUrl);	// use GET for the request
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