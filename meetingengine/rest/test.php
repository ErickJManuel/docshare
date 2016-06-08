<?php
	$obj="meeting";
	$method='PUT';

//	$apiUrl="http://192.168.1.106/NowWeb/rest/$obj/";
//	$apiKey='1234567';
//	$brand='test1';
//	$ip='192.168.1.106';
	$apiUrl="http://license.persony.net/vshow1/rest/$obj/";
	$apiKey='7f868375aac01d88a4cafaaecaa20de';
	$brand='8800867';
	
	$args=array(
		"method" => $method,
		"brand" => $brand,
		"status" => "START",
//		"start" => "2",
//		"count" => "10",
//		"to_date" => "2007-05-19",		
//		"session_id" => "3074",
//		"attendee_id" => "3302372",
//		"in_progress" => "N",
//		"ip" => $ip,
//		"client_data" => 'ec_test123',
		"member_id" => "2428036",
		"id" => "4943406",
//		"host_login" => "janedoe3@persony.com",		
//		"first_name" => "Jane",
//		"last_name" => "Doe3",
//		"license_code" => "P10",
//		"group_id" => "1",
//		"group_id" => "84",
//		"user_id" => "1234567",
//		"title" => rawurlencode("My Meeting 3"), // rawurlencode is needed for GET but not for POST
		);
		
		
	$data='';
	foreach($args as $key => $value)
	{
		if ($data!='')
			$data.="&";
		$data.= "$key=".$value;
	}
		
	$signature=md5($data.$apiKey);
	
	$data.="&signature=".$signature;
	if ($method=='PUT' || $method=='DELETE')
		$method='POST';
	
			
	$resp=make_http_request($apiUrl, $data, $method);
	
	if (!$resp)
		echo("Couldn't get a response from ".$apiUrl);
	else {
		// must add this for IE7 to work on SSL download
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');

		header("Content-type: text/xml");
		echo $resp;	
	}
	
	function make_http_request($url, $data, $method='GET'){

		if ($method=='GET') {
			if ($data!='')
				$url.="?".$data;
				
		}
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
	
		if ($method=='POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);			
		}

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
	
?>