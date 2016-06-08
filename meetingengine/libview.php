<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */


include_once("includes/common.php");
require_once("includes/brand.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vtoken.php");
require_once("dbobjects/vconversionserver.php");
require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vcontent.php");

ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

$downloadUrl=$gBrandInfo['site_url']."vscript.php?s=vdownload&download=1";

$sort='';
GetArg('sort', $sort);

GetArg('lib', $lib);
if ($lib=='')
	$lib='1';

GetArg('media', $media);
if ($media=='')
	$media='0';

//GetArg('token', $token);
$token=GetSessionValue('lib_token');
if ($token=='')
	die("Your session may have expired. Please refresh your page or sign in again.");

// in a live meeting
GetArg('meeting', $meetingId);
GetArg('user_id', $userId);

//$meetingId=GetSessionValue('lib_meeting');

//GetArg('maxSize', $maxSize);
//GetArg('maxTime', $maxTime);
$maxSize=GetSessionValue("lib_max_size");
$maxTime=GetSessionValue("lib_max_time");

if ($maxSize!='')
	$maxSizeStr=$maxSize." MB";
else
	$maxSizeStr='unknown';
if ($maxTime!='')
	$maxTimeStr=$maxTime." sec";
else
	$maxTimeStr='unknown';	

$memberId=GetSessionValue('member_id');
if ($memberId=='') {
	die("Your session has expired. Please sign in again.");
}
$member=new VUser($memberId);
$memberInfo=array();
$member->Get($memberInfo);
if (!isset($memberInfo['id'])) {
	die("The user cannot be found in our records.");
}
//$memberId=GetSessionValue('member_id');
$brandName=GetSessionValue('brand_name');

$mid=$meetingId==''?'0':$meetingId;
$bumToken=VToken::GetBUMToken($brandName,$memberInfo['access_id'],$mid,$token);

// $lib==1 is the public library and only an admin can edit it
$canEdit=false;
if ($lib>1 || $memberInfo['permission']=='ADMIN')
	$canEdit=true;

//determin if the Windows Presenter client is used
$hasWindowsClient=true;

if ((defined('ENABLE_WINDOWS_CLIENT') && constant('ENABLE_WINDOWS_CLIENT')=='0'))
{
	$hasWindowsClient=false;
} else if (IsIPhoneUser() || IsIPadUser()) {
	$hasWindowsClient=false;
} else {	
	require_once("dbobjects/vviewer.php");
	
	$brandViewer=new VViewer($gBrandInfo['viewer_id']);
	$brandViewerInfo=array();
	$brandViewer->Get($brandViewerInfo);
	
	if (isset($brandViewerInfo['presenter_client']) && $brandViewerInfo['presenter_client']=='JAVA')
		$hasWindowsClient=false;

}

$retIcon="themes/icon_return.png";
$downloadIcon="themes/download.gif";

$deleteIcon="themes/delete.gif";
$editIcon="themes/edit.gif";
$addIcon="themes/add.gif";
$shareIcon="themes/people_icon.gif";
$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$editBtn="<img src=\"$editIcon\">".$gText['M_EDIT'];
$shareBtn="<img src=\"$shareIcon\">"._Text("Share");
$addBtn="<img src=\"$addIcon\"> <b>".$gText['M_ADD']."</b>";
$downloadBtn="<img src=\"$downloadIcon\"> <b>".$gText['M_DOWNLOAD']."</b>";

$thisPage=$_SERVER['PHP_SELF']."?lib=".$lib."&media=".$media;
if ($meetingId!='')
	$thisPage.="&meeting=".$meetingId;
if ($userId!='')
	$thisPage.="&user_id=".$userId;

$errMsg=VUser::GetStorageUrl($memberInfo['brand_id'], $memberInfo, $serverUrl, $id, $password, $storageServerId);
if ($errMsg!='') {
	die($errMsg);
}

VUser::GetLibraryPath($memberInfo['access_id'], $pubLibPath, $myLibPath);

if ($lib=='1')
	$libPath=$pubLibPath;
else
	$libPath=$myLibPath; 
	
$libUrl=$serverUrl.$libPath."/";

$viewerUrl="mediaview.php?brand=".$brandName;
$viewerUrl.="&lib=".$lib;
$viewerUrl.="&server_url=".$serverUrl;
if ($meetingId!='')
	$viewerUrl.="&meeting=".$meetingId;
if ($userId!='')
	$viewerUrl.="&user_id=".$userId;

// if the crossdomain file is not in the file server's root directory
// we must use use the swf files reside in the file server to upload files or we may get a securitySandboxError
if (defined("REQUIRE_CROSSDOMAIN") && constant("REQUIRE_CROSSDOMAIN")=='0') {
	$mediaUploader=$serverUrl."media_uploader.swf";
	$slidesUploader=$serverUrl."slides_uploader.swf";
} else {
	$mediaUploader="media_uploader.swf";
	$slidesUploader="slides_uploader.swf";
}

// get library content from the storage site
$url=$serverUrl.SC_SCRIPT.".php?s=".SC_VFTP;
//$url.="&id=$id&code=$password";
$url.="&id=token&code=".$bumToken;
$url.="&cmd=listxml&arg1=".$libPath."/";
$url.="&rand=".rand();

$resp=@file_get_contents($url);

// translate Microsoft characters into Latin 15
// so the xml parser will work correctly if it contains diamond mark type of characters
// however, this doesn't seem to work for Unicode characters so can't use it
//$resp=ConvertSpecialChars($resp);

if ($resp===false)
	die("Couldn't get library content.");

// parse the xml response
// store the results in $contentList
$itemIndex=-1;
$contentList=array();
$xml_parser = xml_parser_create("UTF-8"); 
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
xml_set_character_data_handler($xml_parser, "parse_xml_data");
xml_parse($xml_parser, $resp, true);
xml_parser_free($xml_parser);

// return $contentList by parsing the xml string
function start_xml_tag($parser, $name, $attribs) {
	global $itemIndex, $contentList;
	if ($name=='slides' || $name=='picture' || $name=='media') {
		$contentList[]=array();
		$itemIndex=count($contentList)-1;
		foreach ($attribs as $key => $val) {
			$contentList[$itemIndex][$key]=$val;			
		}
		$contentList[$itemIndex]['_nodeName']=$name;
		$contentList[$itemIndex]['_child']=array();

	} else if ($name=='slide') {
		$index=count($contentList[$itemIndex]['_child'])-1;
		$contentList[$itemIndex]['_child'][$index]=array();
		$contentList[$itemIndex]['_child'][$index]['_nodeName']=$name;
		foreach ($attribs as $key => $val) {
			if (isset($contentList[$itemIndex]['_child'][$index][$key]))
				$contentList[$itemIndex]['_child'][$index][$key].=$val;
			else			
				$contentList[$itemIndex]['_child'][$index][$key]=$val;
		}
		
	}
}

function end_xml_tag($parser, $name) {

}
function parse_xml_data($parser, $data) {

}

// convert an array to an xml string
function Array2XML($item, $libUrl='')
{
	$xml='<'.$item['_nodeName'];
	foreach ($item as $key => $val) {
		if ($key=='_nodeName' || $key=='_child')
			continue;
		
		if ($libUrl!='' && ($key=='fileName' || $key=='thumbnail' || $key=='xmlFile')) {
			$val=str_replace($libUrl, "", $val);
		}
		$xml.=" ".$key."=\"".htmlspecialchars($val)."\"";
	}
	$xml.=">\r\n";
	if (is_array($item['_child'])) {
		foreach ($item['_child'] as $achild) {
			$xml.='	<'.$achild['_nodeName'];
			foreach ($achild as $key => $val) {
				if ($key=='_nodeName' || $key=='_child')
					continue;
					
				if ($libUrl!='' && ($key=='fileName' || $key=='thumbanil' || $key=='xmlFile')) {
					$val=str_replace($libUrl, "", $val);
				}
				$xml.=" ".$key."=\"".htmlspecialchars($val)."\"";
			}
			$xml.="/>\r\n";
		}
	}
	$xml.='</'.$item['_nodeName'].'>';
	return $xml;
}

function IsUrl($file)
{
	if (strpos($file, "http://")===0 || strpos($file, "https://")===0)
		return true;
	return false;
}

function GetContentUrl($libUrl, $item)
{
	if ($item['type']=='PPT') {
		// the slide files created with Flash library browser stores the toc url in the xmlFile tag
		if (isset($item['xmlFile'])&& $item['xmlFile']!='')
			$contentUrl=IsUrl($item['xmlFile'])?$item['xmlFile']:$libUrl.$item['xmlFile'];
		else {
			// the slide files created with VPresent does not store the toc file name but the the file name is the same as the id tag
			$xmlFile=$item['id'].".xml";
			$contentUrl=IsUrl($xmlFile)?$xmlFile:$libUrl.$xmlFile;
		}
	} else {
		$contentUrl=IsUrl($item['fileName'])?$item['fileName']:$libUrl.$item['fileName'];
	}
	return $contentUrl;
}

function GetXmlFile($item, $libUrl='')
{
	$xmlFile='';
	if (isset($item['xmlFile']) && $item['xmlFile']!='')
		$xmlFile=$item['xmlFile'];
	else if (isset($item['id']))
		$xmlFile=$item['id'].".xml";
		
	if ($libUrl!='')
		$xmlFile=str_replace($libUrl, "", $xmlFile);

	return $xmlFile;	
}

function GetXmlUrl($libUrl, $item)
{
	$xmlFile=GetXmlFile($item);
	$xmlUrl=IsUrl($xmlFile)?$xmlFile:$libUrl.$xmlFile;
	return $xmlUrl;
}

if (GetArg('edit', $edit) || GetArg('delete', $delete) || GetArg('share', $share)) {

	GetArg('content_id', $contId);
	if ($contId=='')
		die("Invalid input parameter.");
		
	// go through the library files
	$contsXml='';
	$len=count($contentList);
	
	$item=null;
	$found=false;
	for ($i=0; $i<$len; $i++) {
		$item=$contentList[$i];
		if ($item['id']==$contId) {
			$found=true;
			break;
		}
	}
	
	if ($item==null || !$found)
		die("The file cannot be found.");		
		
	// get the content's storage server url
	// For old content files, there may not be a database record so $contentInfo may be null
	$content=new VContent($contId);
	$errMsg=VObject::Find(TB_CONTENT, 'content_id', $contId, $contentInfo);
	if ($errMsg!='') {
		die($errMsg);
	}
	
	// check permissions
	// public library
	if (isset($contentInfo['owner_id']) && $contentInfo['owner_id']=='0') {
		// verify admin permissions
		if ($memberInfo['permission']!='ADMIN' || $memberInfo['brand_id']!=$contentInfo['brand_id'])
			die("Not authorized");
	} else if (isset($contentInfo['owner_id'])) {
		if ($memberInfo['id']!=$contentInfo['owner_id'])
			die("Not authorized");
	}
	
	// if the content is not stored in the default (main) site,
	// get the server url
	if (isset($contentInfo['storageserver_id']) && $contentInfo['storageserver_id']!='0') {
		$storageServer=new VStorageServer($contentInfo['storageserver_id']);
		$storageServer->GetValue('url', $serverUrl);
	} else {
		$serverUrl=$gBrandInfo['site_url'];
	}

	$rsp=false;		
	if ($edit=='1') {

		GetArg('title', $title);
		
		if ($serverUrl==null || $serverUrl=='') {
			die("Cannot find the record of the server where the content is stored.");			
		} else {
		
			// create a new xml file with the new title
			$item['title']=$title;
			$newXml=Array2XML($item, $serverUrl.$libPath."/");

			// upload new xml to the library
			$url=$serverUrl.SC_SCRIPT.".php?s=".SC_VFTP;
			//$url.="&id=$id&code=$password";
			$url.="&id=token&code=".$bumToken;
			$url.="&cmd=post&arg1=".$libPath."/".GetXmlFile($item, $serverUrl.$libPath."/");
			$url.="&rand=".rand();

			$rsp=HTTP_Request($url, $newXml, "POST");
		}
	} else if ($delete=='1') {
		
		if ($serverUrl==null || $serverUrl=='') {
			// if the db record of the storage server does not exist,
			// simply remove the content db record
			if (isset($contentInfo['id']))
				$content->Drop();
			
		} else {
		
			// remove the directory for a PPT
			if ($item['type']=='PPT') {
				$url=$serverUrl.SC_SCRIPT.".php?s=".SC_VFTP;
				//$url.="&id=$id&code=$password";
				$url.="&id=token&code=".$bumToken;
				$url.="&cmd=rmdir&arg1=".$libPath."/".$item['id'];
				$url.="&arg2=500";	// max number of files to remove in a folder
				$url.="&rand=".rand();

				$rsp=HTTP_Request($url, '', "GET");
				// ignore error
				//if (strpos($rsp, "ERROR")!==false)
				//	die("There was a problem with your request. ".$url);	
								
			} else {
				// remove the media file
				$url=$serverUrl.SC_SCRIPT.".php?s=".SC_VFTP;
				//$url.="&id=$id&code=$password";
				$url.="&id=token&code=".$bumToken;
				// $item['fileName'] may contain absolute url so need to remove the path
				$fileName=str_replace($serverUrl.$libPath."/", "", $item['fileName']);
				$url.="&cmd=rmfile&arg1=".$libPath."/".$fileName;
				$url.="&rand=".rand();
				$rsp=HTTP_Request($url, '', "GET");

				// ignore error
				//if (strpos($rsp, "ERROR")!==false)
				//	die("There was a problem with your request. ".$rsp);				

			}
			
			// remove the xml file
			$url=$serverUrl.SC_SCRIPT.".php?s=".SC_VFTP;
			//$url.="&id=$id&code=$password";
			$url.="&id=token&code=".$bumToken;
			$url.="&cmd=rmfile&arg1=".$libPath."/".GetXmlFile($item, $serverUrl.$libPath."/");
			$url.="&rand=".rand();
			$rsp=HTTP_Request($url, '', "GET");
		}
	} else if ($share=='1') {
		
	
	}
	
	if ($serverUrl!=null && $serverUrl!='') {
		if ($rsp==false)
			die("The server did not respond to your request.");
		
		if (strpos($rsp, "ERROR")!==false)
			die("There was a problem with your request. $url: ".$rsp);				
	}
	// reload the page
	header("Location: $thisPage");			
	exit();

	
}

function sortTitle($a, $b) {
	
	if (isset($a['title']) && isset($b['title'])) {
		$ua=strtoupper($a['title']);
		$ub=strtoupper($b['title']);
	} else {
		$ua=strtoupper($a);
		$ub=strtoupper($b);
	}
	
	if ($ua == $ub) { 
		return 0; 
	} 
	return ($ua < $ub) ? -1 : 1; 

}

function sortDate($a, $b) {
	// most recent first
	if (empty($a["dateTime"])) return 1;
	if (empty($b["dateTime"])) return -1;
	return strcmp(strtotime($b["dateTime"]), strtotime($a["dateTime"]));
}

if ($sort=='date')
	usort($contentList, "sortDate");
else
	usort($contentList, "sortTitle");

$len=count($contentList);

$presCount=$pictCount=$audioCount=$videoCount=0;
for ($i=0; $i<$len; $i++) {
	
	$item=$contentList[$i];
	$className='';
	if ($item['type']=='PPT')
		$presCount++;
	else if ($item['type']=='JPG')
		$pictCount++;
	else if ($item['type']=='MP3')
		$audioCount++;
	else if ($item['type']=='FLV')
		$videoCount++;
}

$total=$presCount+$pictCount+$videoCount+$audioCount;

$apiUrl=SITE_URL.VM_API."?cmd=GET_LIB_UPLOAD"."&token=".$token;
if ($lib=='1')
	$apiUrl.="&public=1";
$vpresentUrl="vpresent://--presentation&".VWebServer::EncodeDelimiter1($apiUrl);
//$vpresentUrl=SERVER_URL."vptest.php?user=".$userId."&token=".$token;
//if ($lib=='1')
//	$vpresentUrl.="&public=1";

$libPageUrl=SITE_URL."libview.php?brand=".$brandName;
if ($meetingId!='')
	$libPageUrl.="&meeting=".$meetingId;
if ($userId!='')
	$libPageUrl.="&user_id=".$userId;

$restUrl=SITE_URL."rest/";
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>


<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?php
/* <link href="themes/<?php echo $theme?>/" rel="stylesheet" type="text/css"> */
?>

<link href="themes/libviewer.css" rel="stylesheet" type="text/css">

<style type="text/css">
	
</style>
<script type="text/javascript" src="js/style.js"></script>
<script type="text/javascript" src="js/lib.js"></script>

<script type="text/javascript">
<!--
	
	var libPageUrl = "<?php echo $libPageUrl."&lib=$lib";?>";
	var mediaIndex=0;
	var vPresentUrl = "<?php echo $vpresentUrl?>";
	var api_url= "<?php echo $restUrl?>";
	var brand = "<?php echo $brandName?>";
	var token = "<?php echo $token?>";
	var meeting_id = "<?php echo $meetingId?>";
	var sender_id="<?php echo $userId?>";
	
//-->
</script>




</head>

<body>


<div id='lib-bar'>
<ul>
<li><a id='media0' href="javascript:void(0)" onclick="showContent(0); return false;"><?php echo _Text("All files");?></a><?php echo "<span class='count2'>$total</span>";?></li>
<li class='i_pict'><a id='media1' href="javascript:void(0)" onclick="showContent(1); return false;"><?php echo _Text("Pictures");?></a><?php echo "<span class='count2'>$pictCount</span>";?></li>
<li class='i_slide'><a id='media2' href="javascript:void(0)" onclick="showContent(2); return false;"><?php echo _Text("Presentations");?></a><?php echo "<span class='count2'>$presCount</span>";?></li>
<li class='i_video'><a id='media3' href="javascript:void(0)" onclick="showContent(3); return false;"><?php echo _Text("Videos");?></a><?php echo "<span class='count2'>$videoCount</span>";?></li>
<li class='i_audio'><a id='media4' href="javascript:void(0)" onclick="showContent(4); return false;"><?php echo _Text("Audios");?></a><?php echo "<span class='count2'>$audioCount</span>";?></li>
<?php
if ($canEdit) {
print <<<END
<li><a href="javascript:void(0)" onclick="addLibItem(mediaIndex); return false;">$addBtn</a></ul>
END;
}
?>
</div>

<div id='lib_content'>

<table>

<?php
/*
if ($canEdit) {

print <<<END

<tr id='add-cont' class="c_icon_add">
<td width='24px'>&nbsp;</td>
<td class="lib_title">&nbsp;</td>
<td class="lib_date">&nbsp;</td>
<td class="lib_tool"><a onclick="addLibItem(mediaIndex); return false;" href="javascript:void(0)">$addBtn</a></td>
</tr>
END;
}
*/
	// go through the library files
	$contsXml='';
	$len=count($contentList);
	
	for ($i=0; $i<$len; $i++) {
		
		$item=$contentList[$i];
		$className='';
		if ($item['type']=='PPT')
			$className='icon_slide';
		else if ($item['type']=='JPG')
			$className='icon_pict';
		else if ($item['type']=='MP3')
			$className='icon_audio';
		else if ($item['type']=='FLV')
			$className='icon_video';
			
		$contId=$item['id'];
		$contTitle=$item['title'];
		if (isset($item['data']))
			$contData=$item['data'];
		else
			$contData='';
		
		$contentUrl=GetContentUrl($libUrl, $item);

		echo "<tr id='file$i' class=\"c_$className\">\n";		
		
		echo "<td width='24px' class=\"$className\">&nbsp;</td>\n";
		
		$viewUrl=$viewerUrl."&url=".urlencode($contentUrl);
		$viewUrl.="&title=".urlencode($contTitle);
		$viewUrl.="&type=".$item['type'];
		
		$count=count($item['_child']);
			
		$title=htmlspecialchars($contTitle);
//		$title=$contTitle;
		if ($title=='')
			$title='[untitled]';
		$countLabel='';
		if ($count>0)
			$countLabel="<span class='count'>".$count."</span>";
		echo "<td class=\"lib_title\"><a onclick='setOpacity(document.documentElement, 5); return true;' href=\"$viewUrl\">$title</a>$countLabel</td>\n";
		if (isset($item['dateTime'])) {
			$dateItems=explode(" ", $item['dateTime']);
			$date=$dateItems[0];
		} else {
			$date='&nbsp;';
		}
		echo "<td class=\"lib_date\">$date</td>\n";
		echo "<td class=\"lib_tool\">\n";
		
		$aTitle=str_replace("\"", "'", $contTitle);
		$aTitle=addslashes($aTitle);
		$id=$item['id'];

		if ($canEdit) {
			echo "<a onclick=\"editLibItem('$aTitle', '$id'); return false;\" href=\"javascript:void(0)\">".$editBtn."</a> ";		
			echo "<a onclick=\"deleteLibItem('$aTitle', '$id'); return false;\" href=\"javascript:void(0)\">".$deleteBtn."</a> ";
		} else {
			echo "&nbsp;";
		}
/* FIXME: not working in some cases; disable for now.
		if ($meetingId!='') {
			$type=$item['type'];
			echo "<a onclick=\"shareLibItem('$aTitle', '$id', '$type', '$contentUrl'); return false;\" href=\"javascript:void(0)\">".$shareBtn."</a> ";		
		}
*/	
		if ($item['type']=='PPT') {
			$filePath=$libPath."/".$item['id']."/";	// slides directory
		} else {
			// $item['fileName'] may contain absolute url so need to remove the path
			$fileName=str_replace($serverUrl.$libPath."/", "", $item['fileName']);
			$filePath=$libPath."/".$fileName;
		}
/* download of presentations is not working because there is a bug in vdownload.php to create a zip file
		$downloadItemUrl=$downloadUrl."&file=".$filePath."&src=".rawurlencode($item['title']);
		echo "<a href=\"$downloadItemUrl\">".$downloadBtn."</a> ";
*/
		echo "</td></tr>\n";
		
		
	}


?>

</table>
</div>

<div style='display: none' id='edit-page'>
<div id='add-bar'>
<ul>
<li id='file_name'></li>
<li id='return'><a href="javascript:void(0)" onclick='refreshLibPage(); return false;'><img src='<?php echo $retIcon?>'></a></li>
</ul>
</div>




<div style='display: none' id='media_selector'>
<div style='padding-top: 60px;'>
Select a media type to add:

<table style='width: 500px; height: 300px; margin-left: 100px; margin-top: 10px; vertical-align: middle; text-align: center'>
<tr>
<td><a id='media1' href="javascript:void(0)" onclick="addLibItem(1); return false;"><img src="themes/picture_64.png"><br> <?php echo _Text("Pictures") ?></a></td>
<td><a id='media1' href="javascript:void(0)" onclick="addLibItem(2); return false;"><img src="themes/slides_64.png"><br> <?php echo _Text("Presentations")?></a></td>
</tr>
<tr>
<td><a id='media1' href="javascript:void(0)" onclick="addLibItem(3); return false;"><img src="themes/video_64.png"><br> <?php echo _Text("Videos")?></a></td>
<td><a id='media1' href="javascript:void(0)" onclick="addLibItem(4); return false;"><img src="themes/audio_64.png"><br> <?php echo _Text("Audios")?></a></td>
</tr>
</table>
</div>
</div>


<?php

$url=$serverUrl.SC_SCRIPT.".php?s=".SC_VFTP;
//$url.="&id=$id&code=$password";
$url.=rawurlencode("&id=token&code=".$bumToken);
$vars="upload_url=$url&upload_dir=$libPath";
//$vars.="&policy_url=".rawurlencode($serverUrl."crossdomain.xml");
$vars.="&policy_url=".$serverUrl."crossdomain.xml";

$uploaderW='420px';
$uploaderH='320px';

$uploaders=array();

$uploaders[]=array(
	"id" => "picture_uploader",
	"vars"=> $vars."&type=picture&title=".rawurlencode(_Text("Add Pictures"))
	);
$uploaders[]=array(
	"id" => "audio_uploader",
	"vars"=> $vars."&type=audio&title=".rawurlencode(_Text("Add Audio Files"))
	);
$uploaders[]=array(
	"id" => "video_uploader",
	"vars"=> $vars."&type=video&title=".rawurlencode(_Text("Add Video Files"))
	);
$uploaders[]=array(
	"id" => "slides_uploader",
	"vars"=> $vars."&type=slides&title=".rawurlencode(_Text("Add Presentation"))
	);
	

$i=1;	
foreach ($uploaders as $uploader) {
	$uploaderId=$uploader['id'];
	$vars=$uploader['vars'];
	
	if ($uploaderId=='video_uploader') {
		$videoPage=$gBrandInfo['site_url']."?page=".PG_HOME_VIDEO;
		$videoNotes="- Videos must be .flv files. See <a target='_blank' href='$videoPage'>instructions</a> for encoding videos.";
	} else {
		$videoNotes='';
	}
	
	if ($uploaderId=='slides_uploader')
		$swfFile=$slidesUploader;
	else
		$swfFile=$mediaUploader;
	
	$swfFile.="?rand=".rand();

	
print <<<END
<div style='display: none' id='$uploaderId'>
	<object width='$uploaderW' height='$uploaderH' classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="https://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" >
	<param name='movie' value='$swfFile'/>
	<param name='wmode' value='opaque'/>
	<param name='play' value='true'/>
	<param name='swliveconnect' value='true'/>
	<param name='allowScriptAccess' value='always'/>
	<param name="flashvars" value='$vars' />
	<embed width='$uploaderW' height='$uploaderH' src='$swfFile' wmode="opaque" play="true" swliveconnect="true" allowScriptAccess="always"
	flashvars='$vars'
	type="application/x-shockwave-flash" pluginspage="https://www.macromedia.com/go/getflashplayer"/>
	</object>
	<div class='upload-note'>
	- Max. upload size per file: <b>$maxSizeStr</b>. Max. upload time per file: <b>$maxTimeStr</b>.<br>
	- Hold down Ctrl or Shift key to select multiple files.<br>
	$videoNotes
	</div>
</div>

END;
	$i++;
	
}

$editUrl=$_SERVER['PHP_SELF'];

//$installer=SITE_URL."download/download.php";
$installer=$gBrandInfo['site_url']."?page=HOME_DOWNLOAD";

$groupId=$memberInfo['group_id'];
$group=new VGroup($groupId);
$groupInfo=array();
$group->Get($groupInfo);

$convId=$groupInfo['conversionserver_id'];
// use the default conversion server. Assume the default server is defined in row 1 of the DB conversionserver table
if ($convId=='0')
	$convId='1';

$convUrl='';
if (defined('USE_CONVERSION_SERVER') && constant('USE_CONVERSION_SERVER')!='0') {
$convServer=new VConversionServer($convId);
$convServer->Get($convInfo);

if (isset($convInfo['url'])) 
	$convUrl=$convInfo['url'];

// If this is an ssl site, use the ssl doc conversion server url if it is available.
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')
	if (isset($convInfo['ssl_url']) && $convInfo['ssl_url']!='')
		$convUrl=$convInfo['ssl_url'];
}

if ($convUrl!='') {
	$args="user_id=".$memberInfo['access_id'];
	$args.="&server_url=".SITE_URL;
	$args.="&lib_url=".$serverUrl;
	$args.="&lib_path=".$libPath;
	$args.="&user_pass=".md5($memberInfo['password']);
	$args.="&brand=".$brandName;
//	$args.="&locale=".$gBrandInfo['locale'];
//	$args.="&css_url=".SITE_URL."themes/".$GLOBALS['THEME'];

	$sig=md5(SITE_URL.$memberInfo['access_id'].$convInfo['access_key']);
	$convUrl.="?".$args."&signature=".$sig;
}

$fspPage=$gBrandInfo['site_url']."?page=".PG_HOME_FSP;


?>



<div style='display: none' id='media_editor'>
<form method='POST' onsubmit='return checkEdit(this)' action='<?php echo $editUrl?>' name='media_editor_form'>
<b>File name: </b><input type='text' name='title' size='40' value=''>
<br><br><br>
<input type='hidden' name='lib' value='<?php echo $lib?>'>
<input type='hidden' name='edit' value='1'>
<input type='hidden' name='media' value=''>
<input type='hidden' name='content_id' value='1'>
<?php
if ($meetingId!='') {
	print <<<END
<input type='hidden' name='user_id' value='$userId'>
<input type='hidden' name='meeting' value='$meetingId'>

END;
}
?>
<input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>"> &nbsp; &nbsp;
<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick='refreshLibPage()'>
</form>
</div>

<div style='display: none' id='add_pres'>
Your presentation must be converted to slide files before they can be added to the library. 
Please select a method to upload your presentation:
<form method='POST' name='add_pres_form'>
<ul>
<?php

if ($convUrl!='') {
print <<<END
<li>Conversion Server: Upload a PowerPoint file and convert on the server<br>
<input type="button" name='upload_ppt' value="Document Converter" onclick='showConverter()'></li>

END;
}

if ($hasWindowsClient) {
print <<<END
<li>Desktop Converter: Convert and upload a PowerPoint file on my desktop <br>
<b>Windows only</b>. Presenter software required. <a target='blank' href='$installer'>Download</a> and install first.<br>
<input type="button" name="upload_ppt" value="Select File..." onclick="launchVPresent(vPresentUrl)"> (.ppt or .pptx)
</li>

END;
}
?>

<li>Upload slide files: Upload slides that are already converted.<br>
<input type="button" name='upload_slides' value="Upload Slides" onclick='showSlidesUploder();'><br>
- Export your presentation to JPEG or Flash slide files first.<br>
- To retain animations, export presentations to Flash files with <a target="_blank" href='<?php echo $fspPage?>'>third party programs</a>.<br>
</li>
</ul>
<input type='hidden' name='lib' value='<?php echo $lib?>'>
<input type='hidden' name='media' value=''>
</form>
</div>



<div style='display: none' id='doc_converter'>
<iframe src ="<?php echo $convUrl?>" width="90%" height="90%" frameborder=0 >
  <p>Your browser does not support iframes.</p>
</iframe>

</div>

</div>


</body>
</html>


<script type="text/javascript">
<!--

<?php
if (IsIPhoneUser() || IsIPadUser()) {	$text=$gText['M_NO_FLASH_SUP'];	print <<<END	var elem;	if (elem=document.getElementById("picture_uploader")) elem.innerHTML="$text";	if (elem=document.getElementById("audio_uploader")) elem.innerHTML="$text";	if (elem=document.getElementById("video_uploader")) elem.innerHTML="$text";	if (elem=document.getElementById("slides_uploader")) elem.innerHTML="$text";END;}

if (count($contentList)==0 && $canEdit && !GetArg("media", $arg)) {
print <<<END
	addLibItem("0");

END;
} else {
print <<<END
	showContent('$media');

END;
}

?>
//-->
</script>
