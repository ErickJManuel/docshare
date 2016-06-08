<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

chdir("../../");

require_once("dbobjects/vtoken.php");
require_once('dbobjects/vmeeting.php');
require_once('dbobjects/vbrand.php');

$errMessages=array();
$errMessages['400']="A required input parameter is missing.";
$errMessages['401']="The password does not match our records.";
$errMessages['404']="An input field is not found in our records.";
$errMessages['500']="An internal error has occurred.";

$httpErrors=array();
$httpErrors['400']="Bad Request";
$httpErrors['401']="Unauthorized";
$httpErrors['403']="Forbidden";
$httpErrors['404']="Not Found";
$httpErrors['500']="Internal Error";

$errXmlTemplate='';

function ExitWithError($errCode, $responseType='xml', $message='')
{
	global $errMessages, $errXmlTemplate;
	global $httpErrors;
	$msg=$message;
	if ($msg=='' && isset($errMessages[$errCode]))
		$msg=$errMessages[$errCode];
	
	if ($responseType=='xml') {
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-type: text/xml");
		$xml=str_replace("[ERROR_CODE]", $errCode, $errXmlTemplate);
		$xml=str_replace("[ERROR_MESSAGE]", $msg, $xml);		
		echo $xml;
	} else if ($responseType=='var') {
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-type: text/plain");

		echo "error=".$errCode." ".$msg;
	} else {

		header("HTTP/1.0 $errCode ".$httpErrors[$errCode]);		
		echo $msg;
	}
	exit();		
	
}

function LoadXmlFile($xmlFile)
{
	$fp=fopen($xmlFile, "r");
	if ($fp) {
		$content=fread($fp, filesize($xmlFile));
		fclose($fp);
		return $content;
	}
	return false;
}

$errXmlTemplate=LoadXmlFile("rest/error.xml");

$responseType='';
if (isset($_REQUEST['response'])) {
	$responseType=$_REQUEST['response'];
}

$method=$_SERVER['REQUEST_METHOD'];
$postData=&$_POST;
// Allow a post request to be sent with "GET" if method=POST is set
if (isset($_GET['method']) && $_GET['method']=='POST') {
	$method=$_GET['method'];
	$postData=&$_GET;
}

If ($method=='GET') {
	if (!isset($_GET['meeting_id']))
		ExitWithError("400", $responseType, "meeting_id is not provided");
	
	$meetingId=$_GET['meeting_id'];
	
	$meetingFile=VMeeting::GetSessionCachePath($meetingId);
	$accessDb=true;
	if (VMeeting::IsSessionCacheValid($meetingFile)) {
		@include_once($meetingFile);
		if (isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
			$accessDb=false;
		}
	}
	
	if ($accessDb) {
		$query="access_id='".$meetingId."'";
		VObject::Select(TB_MEETING, $query, $meetingInfo);
		if (!isset($meetingInfo['id'])) {
			ExitWithError("404", $responseType, "Meeting id is not found in our records");
		}
		$login='NAME';
		if ($meetingInfo['login_type']=='PWD' ) {
			$login='PWD';
		} else if ($meetingInfo['login_type']=='REGIS' ) {
			$login='REGIS';		
		} else if ($meetingInfo['login_type']=='NONE' ) {
			$login='NONE';		
		}
	} else {		
		$login='NAME';
		if ($_loginType=='PWD' || $_loginType=='REGIS' || $_loginType=='NONE')
			$login=$_loginType;
	}
	
	echo "login=$login";
	exit();
}

$brand='';
if (isset($postData['brand'])) {
	$brand=$postData['brand'];
}

$meetingId='';
if (isset($postData['meeting_id'])) {
	$meetingId=$postData['meeting_id'];
}
$meetingPwd='';
if (isset($postData['meeting_password'])) {
	$meetingPwd=$postData['meeting_password'];
}

// for registered users of a meeting
$attendeeLogin='';
if (isset($postData['attendee_login'])) {
	$attendeeLogin=trim($postData['attendee_login']);
}

// for moderators
$memberId='';
if (isset($postData['member_id'])) {
	$memberId=$postData['member_id'];
}
$memberLogin='';
if (isset($postData['member_login'])) {
	$memberLogin=$postData['member_login'];
}
$memberPwd='';
if (isset($postData['member_password'])) {
	$memberPwd=$postData['member_password'];
}

$ignoreCase=false;
if (isset($postData['ignore_case']))
	$ignoreCase=true;

if ($brand=='') {
	ExitWithError("400", $responseType);
}

$bCacheKey=TB_BRAND.'name'.$brand;
$bCacheFile=VObject::GetCachePath($bCacheKey);
$canRead=VObject::ReadFromCache($bCacheFile, $brandInfo);

if (!$canRead || !isset($brandInfo['id'])) {
	// access the DB to find the brand info
	VObject::Find(TB_BRAND, 'name', $brand, $brandInfo);
	if (!isset($brandInfo['id'])) {			
		ExitWithError("404", $responseType, "Brand id is not found in our records");
	}
}

$memberInfo=array();
if ($memberId!='' || $memberLogin!='') {
	
	// create a member token. DB access is required.
	if ($memberId!='') {
		$query="brand_id='".$brandInfo['id']."' AND access_id='".$memberId."'";
	} else {		
		$query="brand_id='".$brandInfo['id']."' AND LOWER(login)='".addslashes(strtolower($memberLogin))."'";
	}
	VObject::Select(TB_USER, $query, $memberInfo);
	
	if (!isset($memberInfo['id'])) {
		ExitWithError("404", $responseType, "Member is not found");
	}
	
	$memberId=$memberInfo['access_id'];
/*	
	if ($memberPwd=='') {

		// make sure the client is requesting fro an authorized server
		// if the php is run in CLI, REMOTE_ADDR is not set and should be allowed
		if (isset($_SERVER['REMOTE_ADDR'])) {

			// allow password to be missing if the request is from an authorized server.
			// currently this only applies to the conversion servers		
			GetArg("host", $remoteHost);
			if ($remoteHost=='') {
				die("host=$remoteHost, remote_addr=".$_SERVER['REMOTE_ADDR']." server_name=".$_SERVER['SERVER_NAME']);
				ExitWithError("401", $responseType);			
			}			
			
			$ip=gethostbyname($remoteHost);
			if ($ip && $ip!='' && $ip!=$_SERVER['REMOTE_ADDR'])
				die("ip=$ip remote_addr=".$_SERVER['REMOTE_ADDR']);
//				ExitWithError("401", $responseType);			
		}

		// see if the request is made from one of the conversion servers assigned to this brand (or all brands.)
		$query="url LIKE '%".$remoteHost."%' AND (brand_id='".$memberInfo['brand_id']."' OR brand_id='0')";
		VObject::Select(TB_CONVERSIONSERVER, $query, $converInfo);
		if (!isset($converInfo['id'])) {
			ExitWithError("401", $responseType);
		}

	} else
*/	
	// input password may be md5 encrypted
	if (($ignoreCase?(strtolower($memberInfo['password'])!=strtolower($memberPwd)):($memberInfo['password']!=$memberPwd))
		&& md5($memberInfo['password'])!=$memberPwd)
	{
		ExitWithError("401", $responseType, "Password does not match");
	}
	
}

$regName='';
if ($meetingId!='') {
	
	$meetingFile=VMeeting::GetSessionCachePath($meetingId);
	$accessDb=true;

	if (VMeeting::IsSessionCacheValid($meetingFile)) {
		@include_once($meetingFile);
		if (isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
			$accessDb=false;
		}
	}

	if ($accessDb) {
		$query="brand_id='".$brandInfo['id']."' AND access_id='".$meetingId."'";
		VObject::Select(TB_MEETING, $query, $meetingInfo);
		if (!isset($meetingInfo['id'])) {
			ExitWithError("404", $responseType);
		}
		
		
		// if the user is not a host of the meeting, verify the meeting password	
		if (!isset($memberInfo['id']) || $meetingInfo['host_id']!=$memberInfo['id']) {
			
			if ($meetingInfo['login_type']=='PWD' ) {
					
				// input password may be md5 encrypted
				if (($ignoreCase?(strtolower($meetingInfo['password'])!=strtolower($meetingPwd)):($meetingInfo['password']!=$meetingPwd))
					&& md5($meetingInfo['password'])!=$meetingPwd)
				{
					ExitWithError("401", $responseType, "Password does not match");
				}
			} else if ($meetingInfo['login_type']=='REGIS') {
				
				require_once('dbobjects/vregistration.php');
				require_once('dbobjects/vregform.php');
				
				if ($attendeeLogin=='') {					
					ExitWithError("400", $responseType, "Attendee login is missing");					
				} else {
				
					$mid=$meetingInfo['id'];
					$query="LOWER(email)='".addslashes(strtolower($attendeeLogin))."' AND meeting_id='$mid'";
					$regInfo=array();
					VObject::Select(TB_REGISTRATION, $query, $regInfo);
						
					if (!isset($regInfo['id']))
						ExitWithError("404", $responseType, "Attendee name is not a registered email address");					
			
					$regName=$regInfo['name'];
				}				
				
			}
		}


	} else {
		
		// use data from the cache file; no db access here
		
		// if the user is not a host of the meeting, verify the meeting password	
		if (!isset($memberInfo['id']) || $_hostId!=$memberInfo['id']) {
			
			if ($_loginType=='PWD' ) {
				// input password may be md5 encrypted
				if (($ignoreCase?(strtolower($_meetingPassword)!=strtolower($meetingPwd)):($_meetingPassword!=$meetingPwd)) 
					&& md5($_meetingPassword)!= $meetingPwd)
				{
					ExitWithError("401", $responseType, "Password does not match");
				}

			} else if ($_loginType=='REGIS') {				
				require_once('dbobjects/vregistration.php');
				
				if ($attendeeLogin=='') {			
					ExitWithError("400", $responseType, "Attendee login is missing");				
				} else if (isset($_registration[$attendeeLogin])) {
					$regName=$_registration[$attendeeLogin]['name'];
				} else if (isset($_registration[strtolower($attendeeLogin)])) {
					$regName=$_registration[strtolower($attendeeLogin)]['name'];
				} else {
					ExitWithError("404", $responseType, "Attendee name is not a registered email address");									
				}
			}
		}

	}
	
}


// add a new token
$tokenCode=VToken::AddToken($brand, $meetingId, $memberId, $memberInfo);

if ($tokenCode=='') {
	ExitWithError("500", $responseType);	
}

if ($responseType=='xml') {
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-type: text/xml");
	$resp=
"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<response xmlns=\"http://schemas.persony.com/wc2/rest/1.0\">
<token>$tokenCode</token>\n";

	if ($regName!='') {
		$regName=htmlspecialchars($regName);
		$resp.="<reg_name>$regName</reg_name>\n";
	}

	$resp.="</response>";	
	echo $resp;
	
} else {
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	header("Content-type: text/plain");

	echo "token=".$tokenCode;
	
	if ($regName!='')
		echo "&reg_name=".$regName;

}
exit();

?>

