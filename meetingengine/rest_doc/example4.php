<?php
/**
 * Persony REST API example code
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 *	Start a meeting given a meeting id.
 */
 
// Replace with your api url and brand id with those from the Administration/API page
$apiUrl="http://license.persony.net/wc2/rest/";
$apiKey='123456cce6f034c30d407f327ea88cd';
$brand='1200001';

// Starting a meeting is a three-step process.
// 1. Sign on the user with SSO or Token Authentication (see API documentation). If you use Token Authentication, you must request the token from the client side (i.e. the browser.)
// 2. Use the 'meeting' API object to set the meeting status to 'START', and get 'host_url' and 'attendee_url'.
// 3. Direct the user to the 'host_url' or 'attendee_url' page, depending on their role.

// Set meeting status
$apiObj="meeting";	// API object
$method='PUT';	// Use PUT to change the meeting status
$meetingId="1220915"; // Find the meeting id from the member's My Meetings page or use the API meetings object
$status="START"; // Set the meeting status

$request="brand=$brand&method=$method&id=$meetingId&status=$status";
$signature=md5($request.$apiKey);	// compute the signature for the request
$request.="&signature=".$signature;

$apiObjUrl=$apiUrl.$apiObj."/"; // trailing slash is required
$resp=http_post_request($apiObjUrl, $request);
if (!$resp)
	die("Couldn't get a response from ".$apiObjUrl);

// check for xml error response code here if there is any

// show the xml response from the API call
// must add the cahche control headers for IE7 to work on SSL requests
header('Pragma: private');
header('Cache-control: private, must-revalidate');
header("Content-type: text/xml");
echo $resp;

// For the meeting host: 
// The meeting page is defined in the "host_url" parameter from the xml response
// IMPORTANT: The url returned from the xml response contains encoded characters.
// You must convert "&amp;" to "&" in the url before redirecting the user to it.

// For meeting attendees:
// The meeting page is defined in the "attendee_url" parameter from the xml response
// Sign-in to the site is not required.


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