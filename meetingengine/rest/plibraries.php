<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vtoken.php");


/**
 * @package     PRestAPI
 * @access      public
 */
class PLibraries extends PCollection 
{
	/**
	 * Constructor
	 */	
	function PLibraries()
	{
		$this->PRestAPI("libraries");
		$this->mBeginTag="<!--BEGIN_LIBRARIES-->";
		$this->mEndTag="<!--END_LIBRARIES-->";
		$this->mTableName='';		

		$this->mSynopsis="Libraries is a collection of all content libraries accessible to a member.";
		$this->mMethods="GET";
		$this->mRequired=array(
			'member_id' => "Member id.",
			);
		$this->mOptional=array(
		);
		$this->mReturned=array(
			'[CONTENT_TYPE]' => "One of 'Picture', 'Presentation', 'Video', or 'Audio'.",
			'[CONTENT_URL]' => "For 'Presentation', the url links to the presentation folder, which contains all the slides for the presentation. For all other types, the url links to the content file.",
			'[CONTENT_DATA]' => "For 'Presentation', the field contains a list of slides. For all other types, the field is empty.",
			'[TOC_URL]' => "The field contains the url to the table of contents file for the content file if it is available.",
		);

	}
	
	function VerifyInput()
	{
		if (!isset($_GET['member_id']) || $_GET['member_id']=='')
			return ("Missing input parameter member_id");
					
		if ($_GET['member_id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			return("Access is not authorized.");
			return '';			
		}
		return '';
	}
	function ReplaceObjectTags($objInfo, $sourceXml)
	{
//		$link=SITE_URL.PR_API_DIR."/group/?id=".$objInfo['id'];
//		$xml=str_replace("[LINK]", htmlspecialchars($link), $sourceXml);
		$xml=str_replace("[GROUP_ID]", $objInfo['id'], $sourceXml);
		$xml=str_replace("[GROUP_NAME]", htmlspecialchars($objInfo['name']), $xml);
		return $xml;
	}


	function Get()
	{
//		require_once("dbobjects/vtoken.php");
//		require_once("dbobjects/vbrand.php");
		global $itemIndex, $contentList;
		
		$errMsg=$this->VerifyInput();
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$respXml=$this->LoadResponseXml();
		$libXml=$this->GetSubXml('<!--BEGIN_LIBRARIES-->', '<!--END_LIBRARIES-->', $respXml);
		$contentXml=$this->GetSubXml('<!--BEGIN_CONTENT-->', '<!--END_CONTENT-->', $libXml);
		
		$memberId='';			
		$this->GetArg('member_id', $memberId);
		
		$errMsg=VObject::Find(TB_USER, 'access_id', $memberId, $userInfo);
		if ($errMsg=='') {
			if (!isset($userInfo['id']))
				$errMsg="Member not found. member_id=$memberId";
		}
		
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		$errMsg=VUser::GetStorageUrl($userInfo['brand_id'], $userInfo, $storageUrl, $storageId, $storageCode, $storageServerId);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		VUser::GetLibraryPath($userInfo['access_id'], $pubLibPath, $myLibPath);
		$pubLibUrl=$storageUrl.$pubLibPath."/";
		$myLibUrl=$storageUrl.$myLibPath."/";
		$pubLibName=_Text("Public Library");
		$myLibName=_Text("My Library");
		$pubLibXml=str_replace("[LIBRARY_NAME]", htmlspecialchars($pubLibName), $libXml);
		$myLibXml=str_replace("[LIBRARY_NAME]", htmlspecialchars($myLibName), $libXml);	
		
		// get library content from the storage site
		$url=$storageUrl.SC_SCRIPT.".php?s=".SC_VFTP;
		$url.="&id=$storageId&code=$storageCode";
/*		
		$brand=new VBrand($userInfo['brand_id']);
		$brand->GetValue('name', $brandName);
		$token=VToken::AddToken($brandName, "0", $userInfo['access_id']);
		$url.="&id=token&code=".VToken::GetBUMToken($brandName, $userInfo['access_id'], "0", $token);
*/
/*
		$webServerId=VUser::GetWebServerId($userInfo);		
		$server=new VWebServer($webServerId);
		$serverInfo=array();
		if ($server->Get($serverInfo)!=ERR_NONE) {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($server->GetErrorMsg());
			return '';
		}	
		if ($serverInfo['url']=='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMsg("Web server url not set");
			return '';
		}
		
		// get public library content
		$url=VWebServer::GetScriptUrl($serverInfo['url'], $serverInfo['php_ext']);
*/		
		for ($l=0; $l<2; $l++) {
			if ($l==0)	{
				$getLibUrl=$url."&cmd=listxml&arg1=".$pubLibPath;
				//$getLibUrl=$url."?s=vftp&cmd=listxml&arg1=".$pubLibPath;
				$libUrl=$pubLibUrl;
			} else {
				$getLibUrl=$url."&cmd=listxml&arg1=".$myLibPath;
				//$getLibUrl=$url."?s=vftp&cmd=listxml&arg1=".$myLibPath;
				$libUrl=$myLibUrl;
			}
		
			if (VWebServer::GetUrl($getLibUrl, $response)) {

				$itemIndex=-1;
				$contentList=array();
				$xml_parser = xml_parser_create("UTF-8"); 
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
				xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
				xml_set_character_data_handler($xml_parser, "parse_xml_data");
				xml_parse($xml_parser, $response, true);
				xml_parser_free($xml_parser);
				
				$contsXml='';
				$len=count($contentList);
				for ($i=0; $i<$len; $i++) {
					$item=$contentList[$i];
					$contType='';
					if ($item['type']=='PPT')
						$contType='Presentation';
					else if ($item['type']=='JPG')
						$contType='Picture';
					else if ($item['type']=='MP3')
						$contType='Audio';
					else if ($item['type']=='FLV')
						$contType='Video';
					$contId=$item['id'];
					$contTitle=htmlspecialchars($item['title']);
					if (isset($item['data']))
						$contData=$item['data'];
					else
						$contData='';
					
					if ($item['type']=='PPT') {
						$contentUrl=$libUrl;
					} else {
						$contentUrl=IsUrl($item['fileName'])?$item['fileName']:$libUrl.$item['fileName'];
					}
					$tocUrl='';
					if ($contType=='Presentation') {
						// the slide files created with Flash library browser stores the toc url in the xmlFile tag
						if (isset($item['xmlFile'])&& $item['xmlFile']!='')
							$tocUrl=IsUrl($item['xmlFile'])?$item['xmlFile']:$libUrl.$item['xmlFile'];
						else {
							// the slide files created with VPresent does not store the toc file name but the the file name is the same as the id tag
							$xmlFile=$item['id'].".xml";
							$tocUrl=IsUrl($xmlFile)?$xmlFile:$libUrl.$xmlFile;
						}
					}
						
					$aContXml=str_replace("[CONTENT_TYPE]", $contType, $contentXml);
					$aContXml=str_replace("[CONTENT_ID]", $contId, $aContXml);
					$aContXml=str_replace("[CONTENT_TITLE]", $contTitle, $aContXml);
					$aContXml=str_replace("[CONTENT_URL]", htmlspecialchars($contentUrl), $aContXml);
					$aContXml=str_replace("[TOC_URL]", htmlspecialchars($tocUrl), $aContXml);
					$aContXml=str_replace("[CONTENT_DATA]", htmlspecialchars($contData), $aContXml);
					$contsXml.=$aContXml;
				}
				
				if ($l==0)
					$pubLibXml=str_replace($contentXml, $contsXml, $pubLibXml);
				else
					$myLibXml=str_replace($contentXml, $contsXml, $myLibXml);

			} else {
				$this->SetStatusCode(PCODE_ERROR);
				$this->SetErrorMsg("Couldn't get response from ".$getLibUrl);
				return '';
			}
		}			
				
		$retXml=str_replace($libXml, $pubLibXml.$myLibXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}

}



$itemIndex=-1;
$contentList=array();

function start_xml_tag($parser, $name, $attribs) {
	global $itemIndex, $contentList;
	if ($name=='slides' || $name=='picture' || $name=='media') {
		$contentList[]=array();
		$itemIndex=count($contentList)-1;
		foreach ($attribs as $key => $val) {
			$contentList[$itemIndex][$key]=$val;			
		}
		$contentList[$itemIndex]['data']='';
	} else if ($name=='slide') {
		$contentList[$itemIndex]['data'].='<slide';
		foreach ($attribs as $key => $val) {
			$contentList[$itemIndex]['data'].=" $key=\"$val\"";			
		}			
		$contentList[$itemIndex]['data'].='/>';			
	}
}

function end_xml_tag($parser, $name) {

}
function parse_xml_data($parser, $data) {

}

function IsUrl($file)
{
	if (strpos($file, "http://")===0 || strpos($file, "https://")===0)
		return true;
	return false;
}

?>