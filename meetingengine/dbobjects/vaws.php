<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
define("AWS_EC2_ENDPOINT", "http://ec2.amazonaws.com");


class VEC2Image {
	var $imageId='';
	var $imageLocation='';
	var $imageState='';
	var $imageOwnerId='';
	var $isPublic='';
	var $productCode='';
}
class VEC2Instance {
	var $instanceId='';
	var $imageId='';
	var $instanceStatecode='';
	var $instanceStatename='';
	var $dnsName='';
	var $amiLaunchIndex='';
}

define("AWS_API_VERSION", "2007-08-29");

/**
 * @package     VShow
 * @access      public
 */
class VAWS extends VObject 
{
	/**
	 * @access private
	 */
	var $mAccessKey='';
	/**
	 * @access private
	 */
	var $mSecretKey='';
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VAWS($id=0)
	{
		$this->VObject(TB_AWS);
		$this->SetRowId($id);
	}
	function SetKeyPair($accessKey, $secretKey)
	{
		$this->mAccessKey=$accessKey;
		$this->mSecretKey=$secretKey;
	}
	function RunInstances($imageName, $instanceType, &$instanceId, &$state)
	{
		global $itemIndex, $inItem, $instances;
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
	
		$action='RunInstances';
		$args['ImageId']=$imageName;
		$args['MinCount']='1';
		$args['MaxCount']='1';
		if ($instanceType!='')
			$args['InstanceType']=$instanceType;

		$result=$this->RequestEC2($action, $args, $errMsg);
		
		if ($result==false) {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		if (($errMsg=VAWS::GetEC2Error($result))!='') {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		$instances=array();
		$inItem=false;
		$itemIndex=0;
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_instances_tag", "end_instances_tag");
		xml_set_character_data_handler($xml_parser, "parse_instances");
		xml_parse($xml_parser, $result, true);
		xml_parser_free($xml_parser);
		
		//echo("count=".count($instances));
		//print_r($instances);
		
		if (count($instances)>0) {
			$instanceId=$instances[0]->instanceId;
			$state=$instances[0]->instanceStatename;
		} else
			$instanceId='';
			
			
		return $this->mErrorCode;

	}
	function DescribeInstance($instanceId, &$instance_out)
	{
		global $itemIndex, $inItem, $instances;
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
	
		$action='DescribeInstances';
		$args['InstanceId']=$instanceId;
		
		$result=$this->RequestEC2($action, $args, $errMsg);
		
		if ($result==false) {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		if (($errMsg=VAWS::GetEC2Error($result))!='') {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		$instances=array();
		$inItem=false;
		$itemIndex=0;
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_instances_tag", "end_instances_tag");
		xml_set_character_data_handler($xml_parser, "parse_instances");
		xml_parse($xml_parser, $result, true);
		xml_parser_free($xml_parser);

		if (count($instances)>0) {
			$instance_out=$instances[0];
		} else {
			$this->SetErrorMsg("Instance ".$instanceId." not found in AWS response.");
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}

		return $this->mErrorCode;

	}
	function RebootInstance($instanceId)
	{
		global $itemIndex, $inItem, $instances;
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
	
		$action='RebootInstances';
		$args['InstanceId']=$instanceId;
		
		$result=$this->RequestEC2($action, $args, $errMsg);
		
		if ($result==false) {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		if (($errMsg=VAWS::GetEC2Error($result))!='') {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}

		return $this->mErrorCode;

	}
	function TerminateInstance($instanceId, &$instance_out)
	{
		global $itemIndex, $inItem, $instances;
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
	
		$action='TerminateInstances';
		$args['InstanceId']=$instanceId;
		
		$result=$this->RequestEC2($action, $args, $errMsg);
		
		if ($result==false) {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		if (($errMsg=VAWS::GetEC2Error($result))!='') {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		$instances=array();
		$inItem=false;
		$itemIndex=0;
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_instances_tag", "end_instances_tag");
		xml_set_character_data_handler($xml_parser, "parse_instances");
		xml_parse($xml_parser, $result, true);
		xml_parser_free($xml_parser);

		if (count($instances)>0) {
			$instance_out=$instances[0];
		} else {
			$this->SetErrorMsg("Instance ".$instanceId." not found");
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}

		return $this->mErrorCode;

	}
	// not tested yet
	function DescribeInstances($instanceIds, &$out_instances)
	{
		global $itemIndex, $inItem, $instances;
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
	
		$action='DescribeInstances';
		$id=0;
		foreach ($instancedIds as $id) {
			$args['InstanceId.$i']=$instanceIds;
			$i++;
		}

		$result=$this->RequestEC2($action, $args, $errMsg);
		
		if ($result==false) {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		if (($errMsg=VAWS::GetEC2Error($result))!='') {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		$instances=array();
		$inItem=false;
		$itemIndex=0;
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_instances_tag", "end_instances_tag");
		xml_set_character_data_handler($xml_parser, "parse_instances");
		xml_parse($xml_parser, $result, true);
		xml_parser_free($xml_parser);
		
		echo("count=".count($instances));
		print_r($instances);					
			
		return $this->mErrorCode;

	}
	
	// not tested yet
	function DescribeImages($ownerId, $public, &$images)
	{
		global $itemIndex, $inItem, $gImages;
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
	
		$action='DescribeImages';
		$args['Owner']=$ownerId;
		if ($public)
			$args['ExecutableBy']='all';
		$result=$this->RequestEC2($action, $args, $errMsg);
		
		if ($result==false) {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		if (($errMsg=VAWS::GetEC2Error($result))!='') {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
		$gImages=array();
		$inItem=false;
		$itemIndex=0;
		$xml_parser  =  xml_parser_create("");
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_element_handler($xml_parser, "start_images_tag", "end_images_tag");
		xml_set_character_data_handler($xml_parser, "parse_images");
		xml_parse($xml_parser, $result, true);
		xml_parser_free($xml_parser);
		
		$images=$gImages;					
		
		return $this->mErrorCode;
	}
	/**
	* @access private Get EC2 error, if any
	* @return string
	*/
	function GetEC2Error($response)
	{
		if (strpos($response, "<Response><Errors>")!==false) {
			$pos1=strpos($response, "<Message>");
			$pos2=strpos($response, "</Message>");
			if ($pos1>0 && $pos2>$pos1) {
				$pos1+=strlen("<Message>");
				$msg=substr($response, $pos1, $pos2-$pos1);
				return $msg;
			}
		} elseif (strpos($response, "Http/1.1")!==false) {
			return $response;
		}
		return '';		
	}
	/**
	* @access public Send a request to EC2
	* @return string
	*/
	function RequestEC2($action, $args, &$errorMsg)
	{
		$ec2_url = $this->GenerateEC2Url($action, $args);
		$result=$this->MakeHttpRequest($ec2_url, $errorMsg);

		return $result;		
	}
	/**
	* @access public Generate EC2 request url
	* @return string
	*/	
	function GenerateEC2Url($action, $args=null) {
		
		$params=array();
		$params['AWSAccessKeyId']=$this->mAccessKey;
		$params['SignatureVersion']='1';
		$params['Version']=AWS_API_VERSION;
		
		$timestamp =  VAWS::GenerateTimestamp();
		$timestamp_enc = rawurlencode($timestamp);
		
		$params['Action']=$action;
		$params['Timestamp']=$timestamp;
		
		// append additional input parameters
		if ($args) {
			foreach ($args as $key => $value) {
				$params[$key]=$value;
			}
		}
		// sort by keys
		uksort($params, "cmp_lower");
		reset($params);
		
		// concatenate sorted keys and values to a string for creating a signature
		$data='';
		foreach ($params as $key => $value) {
			$data.=$key.$value;
		}
		
	//	echo $data."\n";

		// create the request signature from the input string and the secret key
		$signature_enc = rawurlencode(VAWS::CalculateRFC2104HMAC($data, $this->mSecretKey));
		
		// construct a query for the request
		$query='';
		$params['Timestamp']=$timestamp_enc;
		$params['Signature']=$signature_enc;
		foreach ($params as $key => $value) {
			if ($query!='')
				$query.="&";
			$query.=$key."=".$value;
		}
	//	echo $query."\n";
		
		return AWS_EC2_ENDPOINT."?".$query;

	}

	/**
	* @static Calculate signature using HMAC: http://www.faqs.org/rfcs/rfc2104.html
	* @return string
	*/		
	function CalculateRFC2104HMAC ($data, $key) {
		return base64_encode (
			pack("H*", sha1((str_pad($key, 64, chr(0x00))
			^(str_repeat(chr(0x5c), 64))) .
			pack("H*", sha1((str_pad($key, 64, chr(0x00))
			^(str_repeat(chr(0x36), 64))) . $data))))
		);
	}
	/**
	* @static Timestamp format: yyyy-MM-dd'T'HH:mm:ss'Z'
	* @return string
	*/		
	function GenerateTimestamp () {
		return gmdate("Y-m-d\TH:i:s\Z", time());
	}
	/**
	* @static Make an http request to the specified URL and return the result
	* @return string
	*/	
	function MakeHttpRequest($url, &$curlError){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		$result = curl_exec($ch);
		if ($result==false)
			$curlError=curl_error($ch);
		curl_close($ch);
		return $result;
	}

}



// case insensitive sort
function cmp_lower($a, $b) {
	$la=strtolower($a);
	$lb=strtolower($b);
	if ($la == $lb) { 
		return 0; 
	} 
	return ($la > $lb) ? 1 : -1; 
}

$current_tag='';
$itemIndex=0;
$inItem=false;
$gImages=null;

function start_images_tag($parser, $name, $attribs) {
    global $current_tag, $itemIndex, $inItem, $gImages;
	$current_tag = $name;
	if ($name=='imagesSet')
		$inItem=true;
	else if ($name=='item' && $inItem) {
		$itemIndex=count($gImages);
		array_push($gImages, new VEC2Image());
	}
}

function end_images_tag($parser, $name) {
    global $current_tag, $itemIndex, $inItem, $gImages;
	$current_tag = '';
	if ($name=='imagesSet')
		$inItem=false;
	else if ($name=='item' && $inItem) {
//		echo("end_tag $name");
	}

}
function parse_images($parser, $data) {
	global $current_tag, $itemIndex, $inItem, $gImages;

	switch ($current_tag) {
        case "imageId":
			if ($inItem)
				$gImages[$itemIndex]->imageId=$data;
            break;
        case "imageLocation":
			if ($inItem)
				$gImages[$itemIndex]->imageLocation=$data;
            break;
        case "imageState":
			if ($inItem)
				$gImages[$itemIndex]->imageState=$data;
            break;
        case "imageOwnerId":
			if ($inItem)
				$gImages[$itemIndex]->imageOwnerId=$data;
            break;
        case "isPublic":
			if ($inItem)
				$gImages[$itemIndex]->isPublic=$data;
            break;
        case "productCode":
			if ($inItem)
				$gImages[$itemIndex]->productCode=$data;
            break;
    }
}


$instances=null;


function start_instances_tag($parser, $name, $attribs) {
    global $current_tag, $itemIndex, $inItem, $instances;
	$current_tag = $name;
	if ($name=='instancesSet')
		$inItem=true;
	else if ($name=='item' && $inItem) {
		$itemIndex=count($instances);
		array_push($instances, new VEC2Instance());
	}
}

function end_instances_tag($parser, $name) {
    global $current_tag, $itemIndex, $inItem, $instances;
	$current_tag = '';
	if ($name=='instancesSet')
		$inItem=false;
	else if ($name=='item' && $inItem) {
//		echo("end_tag $name");
	}

}
function parse_instances($parser, $data) {
	global $current_tag, $itemIndex, $inItem, $instances;

	switch ($current_tag) {
        case "instanceId":
			if ($inItem)
				$instances[$itemIndex]->instanceId=$data;
            break;
        case "imageId":
			if ($inItem)
				$instances[$itemIndex]->imageId=$data;
            break;
        case "instanceType":
			if ($inItem)
				$instances[$itemIndex]->instanceType=$data;
            break;
        case "code":
			if ($inItem)
				$instances[$itemIndex]->instanceStatecode=$data;
            break;
        case "name":
			if ($inItem)
				$instances[$itemIndex]->instanceStatename=$data;
            break;
        case "dnsName":
			if ($inItem)
				$instances[$itemIndex]->dnsName=$data;
            break;
        case "launchTime":
			if ($inItem)
				$instances[$itemIndex]->launchTime=$data;
            break;
        case "amiLaunchIndex":
			if ($inItem)
				$instances[$itemIndex]->amiLaunchIndex=$data;
            break;
    }
}
