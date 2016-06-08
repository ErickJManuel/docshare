<?php
/**
 * Persony REST API example code
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 *	Create a new member and then change the member's group id
 */
 
// replace with your api url, key and brand id with those from the Administration/API page
$apiUrl="http://license.persony.net/wc2/rest/";
$apiKey='123456cce6f034c30d407f327ea88cd';
$brand='1200001';

// add a member
$apiObj="member";	// API object

// api pramaters
$args=array(
		"brand" => $brand,
		"method" => "POST",	// use POST to add a member
		"login" => "test1@abc.com", // login name (doesn't have to be email but needs to be unique.)
		"email" => "test1@abc.com", // can be omitted if the login is already an email address
		"password" => "12345",
		"first_name" => "Test",
		"last_name" => "User",
		"license_code" => "TPV1", // a trial member. See Admin/Accounts for the license code.
		"group_id" => "102", // find the group id from the Admin/Groups page.
		);

// construct the POST request string; the values should be url encoded
$request='';
foreach($args as $key => $value)
{
	if ($request!='')
		$request.="&";
	
	// the request value should be url encoded
	$request.= $key."=".urlencode($value);
}

// construct a string for the signature computation
// for POST requests, the signature must be computed from non-encoded values
$sigStr='';
foreach($args as $key => $value)
{
	if ($sigStr!='')
		$sigStr.="&";
	
	$sigStr.= $key."=".$value;
}

$signature=md5($sigStr.$apiKey);	// compute the signature for the request
$request.="&signature=".$signature;	// append the signature to the request

$apiObjUrl=$apiUrl.$apiObj."/"; // trailing slash is required
$resp=http_post_request($apiObjUrl, $request);	// send the request with HTTP POST
if (!$resp)
	die("Couldn't get a response from ".$apiObjUrl);

// check for xml error response code here if there is any

// change the member's group id
$apiObj="member";	// API object
$args=array(
		"brand" => $brand,
		"method" => "PUT",	// use PUT to make a change
		"group_id" => "163", // Assign the member to a different group; find the group id from the Admin/Groups page
		"login" => "test1@abc.com", // login name of the member
		);
		
// construct the POST request string; the values should be url encoded
$request='';
foreach($args as $key => $value)
{
	if ($request!='')
		$request.="&";
	
	// the request value should be url encoded
	$request.= $key."=".urlencode($value);
}

// construct a string for the signature computation
// for POST requests, the signature must be computed from non-encoded values
$sigStr='';
foreach($args as $key => $value)
{
	if ($sigStr!='')
		$sigStr.="&";
	
	// the value should not be encoded 
	$sigStr.= $key."=".$value;
}

$signature=md5($sigStr.$apiKey);	// compute the signature for the request
$request.="&signature=".$signature;

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