<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

ob_flush();
flush();

include_once("dbobjects/vmeeting.php");
include_once("dbobjects/vsession.php");
include_once("includes/log_error.php");
include_once("dbobjects/vtoken.php");

$redirect_url=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_RECORDINGS;
if (SID!='')
	$redirect_url.="&".SID;
?>

<script type="text/javascript">
<!--
	document.getElementById('loader').style.display= 'inline';
	document.getElementById('return_link').href= '<?php echo $redirect_url?>';

//-->
</script>

<?php

GetArg('meeting', $meetingId);
GetArg('process', $process);

VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
if (isset($meetingInfo['id']))
	$meeting=new VMeeting($meetingInfo['id']);	
else {
	PrintEndMessage("Meeting not found.");
	return;
}

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='') {
	PrintEndMessage("User not logged in.");
	return;
}

$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE) {
	PrintEndMessage("Member not found.");
	return;
}

require_once("dbobjects/vgroup.php");
require_once("dbobjects/vteleserver.php");

$xmlStatus='';
$xmlInfo='';
$current_tag='';
$mp3FileSize='';
function start_xml_tag($parser, $name, $attribs) {
	global $xmlStatus, $xmlInfo, $current_tag;
	$current_tag=$name;
}

function end_xml_tag($parser, $name) {
	global $xmlStatus, $xmlInfo, $current_tag;
	$current_tag='';
	
}
function parse_xml($parser, $data) {
	global $xmlStatus, $xmlInfo, $current_tag, $mp3FileSize;
	
	switch ($current_tag) {
		case "cm_status":
			$xmlStatus=$data;
			break;
		case "cm_info":
			$xmlInfo=$data;
			break;
		case "FileSize":
			$mp3FileSize=$data;
			break;
		
	}
}
function ParseXmlData($xmlData) {
	// if the response is not xml, treat it as an error and return
	if (strpos($xmlData, "<?xml")===false) {
		$xmlStatus='500';
		$xmlInfo=$xmlData;
		return;
	}
	$xml_parser  =  xml_parser_create("");
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
	xml_set_character_data_handler($xml_parser, "parse_xml");
	xml_parse($xml_parser, $xmlData, true);
	xml_parser_free($xml_parser);
}

function CopyDirFiles($fromUrl, $fromDir, $toUrl, $login, $password, &$fileCount, $total)
{
	// get a list of files
	$url=$fromUrl."vscript.php?s=vftp&cmd=listdir&arg1=$fromDir";
	$url.="&id=$login&code=$password";

	$content=@file_get_contents($url);
	if ($content==false) {
		sleep(1);
		$content=@file_get_contents($url);
	}
	if ($content==false) {
		LogError("Couldn't get $url");
		return("Couldn't get recording files.");
	}

	$files=explode("\n", $content);
	$count=count($files);
	if ($count==0 || $files[0]!='OK') {
		LogError("Invalid response ".$content);
		return("Unknown error encountered.");
	}	

	for ($i=1; $i<$count; $i++) {
		$filename=$files[$i];
		$len=strlen($filename);
		if ($len==0)
			continue;
		else if ($len>1 && $filename[$len-1]=='/') {
			$ret=CopyDirFiles($fromUrl, $filename, $toUrl, $login, $password, $fileCount, $total);	
			if ($ret!='')
				return $ret;	
		} else {

			$fileInfo = pathinfo($filename);
			$extension=strtolower($fileInfo["extension"]);

			if (strpos($extension, "php")!==false) {
				$fileCount++;
				continue;
			}

			$srcUrl=rawurlencode($fromUrl.$filename);
			$copyUrl=$toUrl."vscript.php?s=vftp&cmd=put_url&arg1=$filename&arg2=&arg3=$srcUrl";
			$copyUrl.="&id=$login&code=$password";
			
			$resp=@file_get_contents($copyUrl);
			if ($resp==false) {
				sleep(1);
				$resp=@file_get_contents($copyUrl);
			}
			
			if ($resp && strpos($resp, "OK")===0) {
				$fileCount++;
				PrintProgressMessage("Copying file $fileCount of $total.");
				
			} else {
				// ignore the error for now
//				return "Couldn't copy file $filename";
				LogError("Couldn't copy file $filename: $resp");
			}

		}
	}
	return '';
}


function CountDirFiles($fromUrl, $fromDir, $login, $password, &$fileCount)
{
	// get a list of files
	$url=$fromUrl."vscript.php?s=vftp&cmd=listdir&arg1=$fromDir";
	$url.="&id=$login&code=$password";

	$content=@file_get_contents($url);
	if ($content==false) {
		sleep(1);
		$content=@file_get_contents($url);
	}
	if ($content==false) {
		LogError("Couldn't get $url");
		return("Couldn't get recording files.");
	}

	$files=explode("\n", $content);
	$count=count($files);
	if ($count==0 || $files[0]!='OK') {
		LogError("Invalid response ".$content);
		return("Unknown error encountered.");
	}	

	for ($i=1; $i<$count; $i++) {
		$filename=$files[$i];
		$len=strlen($filename);
		if ($len==0)
			continue;
		else if ($len>1 && $filename[$len-1]=='/') {
			$ret=CountDirFiles($fromUrl, $filename, $login, $password, $fileCount);	
			if ($ret!='')
				return $ret;	
		} else {

			$fileInfo = pathinfo($filename);
			$extension=strtolower($fileInfo["extension"]);

			if (strpos($extension, "php")!==false)
				continue;
			$fileCount++;
		}
	}
	return '';
}


set_time_limit(30*60);

$ready=true;

// main site--where we want to store the recording data
// unless storage server is used
$siteUrl=$gBrandInfo['site_url'];

// Current hosting site that contains the recording data. It may or may not be the main site.
$meetingServerId=$meetingInfo['webserver_id'];
$webServer=new VWebServer($meetingServerId);
$webServer->Get($webInfo);

PrintProgressMessage(_Text("Processing recording files..."));

// make sure the recording directory is valid
$host=new VUser($meetingInfo['host_id']);
$host->Get($hostInfo);
$meetingDir=VMeeting::GetMeetingDir($hostInfo, $meetingInfo);	

// change it back to storing the recording in the default web conf site
// storing the recording on storage servers doesn't work 
// because a recording folder needs some php files which can only be created if it is on a web conf server.
//VUser::GetStorageUrl($gBrandInfo['id'], $hostInfo, $storageUrl, $login, $pass, $storageServerId);
$storageServerId='0';
$storageUrl=$siteUrl;

// create a token to be used for API authentication
$brandName=GetSessionValue('member_brand_name');
$token=VToken::AddToken($brandName, $meetingInfo['access_id'], $memberInfo['access_id'], $memberInfo);
$bumToken=VToken::GetBUMToken($brandName,$memberInfo['access_id'],$meetingInfo['access_id'],$token);

$serverInfo=array();
// if there is no storage server, store the recording in the default site
// get the default site info
if ($storageServerId=='0') {
	// find the default site's webserver record and store it in $serverInfo
	$query="brand_id='".$gBrandInfo['id']."' AND url='".$siteUrl."'";
	$serverInfo=array();
	$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
	if ($errMsg!='') {
		LogError($errMsg);
		PrintEndMessage("Couldn't get a database record.");
		return;
	}
	if (!isset($serverInfo['id'])) {
		LogError("Couldn't find the web server ".$siteUrl);
		PrintEndMessage("Couldn't find the web server record.");
		return;
	}
	
	$login=$serverInfo['login'];
	$pass=$serverInfo['password'];
	
	// Set up the new meeting folder
	$newInfo=$meetingInfo;
	$newInfo['webserver_id']=$serverInfo['id'];
	if ($meeting->UpdateServer($newInfo)!=ERR_NONE) {
		LogError($meeting->GetErrorMsg());
		PrintEndMessage("Error encountered in creating recording files.");
		return;	
	}
}

// if recording data are not copied yet (duratiion==0) or the user wants to reprocess ($process==2)
// find the original meeting that this recording is created from
// and re-copy all the recorded data from the original meeting to the recording folder
// rec_event_id is the StartRecording event index and must be stored in the recording DB entry
// the original meeting must still have the event data around and on the same server as when the recording took place.
if (($meetingInfo['duration']=='00:00:00' || $process=='2') && $meetingInfo['rec_event_id']!='0') {
	
	$sessionId=$meetingInfo['session_id'];
	$session=new VSession($sessionId);
	$session->GetValue('meeting_aid', $srcMeetingId);
	VObject::Find(TB_MEETING, 'access_id', $srcMeetingId, $srcMeetingInfo);

	if (isset($srcMeetingInfo['id'])) {

		PrintProgressMessage(_Text("Getting recorded data from the source..."));
		
		$srcMeetingDir=VMeeting::GetMeetingDir($hostInfo, $srcMeetingInfo);	
		$srcServerUrl=VMeeting::GetMeetingServerUrl($hostInfo, $srcMeetingInfo);
		$errMsg=VMeeting::CopyRecordingData($srcServerUrl.$srcMeetingDir, $meetingInfo, $duration);
		
		// trigger the copy to the storage server if it is different from the recording site
		$webInfo['url']=$srcServerUrl;
		
		if ($errMsg=='') {
			
			$durInfo=array();
			$durInfo['duration']=VMeeting::SecToStr($duration);
			if ($meeting->Update($durInfo)!=ERR_NONE) {
				LogError($meeting->GetErrorMsg());
				PrintEndMessage("Error in updating database.");
				return;	
			}
			
		} else {
			LogError("GetRecordingData ".$srcServerUrl.$srcMeetingDir." returns ".$errMsg);
		}
	}
}


// if the storage server is different than the web hosting server, copy the recording to the storage server.
if ($storageUrl!=$webInfo['url']) {
	
	PrintProgressMessage(_Text("Copying recorded files..."));

	// the copy here only copies the recording data
	// there are some php scripts for the recording to play are not copied
	// they need to be created using meeting->UpdateServer, which is called earlier.
	// however, we can't call that function for a storage server.
	// that's why we can't store the recording on a storage server.
	// we will have to fix it before we can put recordings on a storage server.
	
	$totalCount=0;
	// use token for authentication
//	$ret=CountDirFiles($webInfo['url'], $meetingDir, $login, $pass, $totalCount);
	$ret=CountDirFiles($webInfo['url'], $meetingDir, "token", $bumToken, $totalCount);
	
	if ($ret!='') {
		LogError($ret);
		PrintProgressMessage("Couldn't get source directory files.");
//		PrintEndMessage("Couldn't get source directory files.");
//		return;
	}

	// copy files from webInfo['url'] (recording site) to $storageUrl (storage server)
	$fileCount=0;
//	$ret=CopyDirFiles($webInfo['url'], $meetingDir, $storageUrl, $login, $pass, $fileCount, $totalCount);
	$ret=CopyDirFiles($webInfo['url'], $meetingDir, $storageUrl, "token", $bumToken, $fileCount, $totalCount);
	
	if ($ret!='') {
		LogError($ret);
		PrintProgressMessage("Couldn't copy directory files.");
//		PrintEndMessage("Couldn't copy directory files.");
//		return;
	}
	
}

$updateInfo=array();
// if there is no storage server, the recording must be stored on the default site
// set the webserver_id to the default site id.
if ($storageServerId=='0') {
	$updateInfo['webserver_id']=$serverInfo['id'];
	$meetingInfo['webserver_id']=$serverInfo['id'];	
}
$updateInfo['storageserver_id']=$storageServerId;
$meetingInfo['storageserver_id']=$storageServerId;
$meeting->Update($updateInfo);
if ($meeting->Update($updateInfo)!=ERR_NONE) {
	LogError($meeting->GetErrorMsg());
	PrintEndMessage("Error in updating database.");
	return;
}

// see if there is audio
$recId=$meetingInfo['audio_rec_id']; // meetingInfo is defined in mymeetings_recording.php

//if ($recId!='' && $meetingInfo['audio']=='N') {
if ($recId!='') {
	$ready=false;
	// get the teleconf server	
	$number=$meetingInfo['tele_num'];
	if ($number=='')
		$number=$meetingInfo['tele_num2'];
		
	$number=RemoveSpacesFromPhone($number);
	$code=RemoveNonNumbers($meetingInfo['tele_pcode']);

	$group=new VGroup($memberInfo['group_id']);
	$group->GetValue('teleserver_id', $teleServerId);
	if ($teleServerId=='0') {
		LogError("No teleconference server is assigned to group ".$memberInfo['group_id']);
		PrintEndMessage("No teleconference server is assigned.");
		return;	
	}
	$teleServer=new VTeleServer($teleServerId);
	$teleInfo=array();
	$teleServer->Get($teleInfo);
	if (!isset($teleInfo['server_url']) || $teleInfo['server_url']=='') {
		LogError("Teleconference server is not found. id=".$teleServerId);
		PrintEndMessage("Teleconference server is not found.");
		return;		
	}
	$recUrl=$teleInfo['server_url'];
	$accessKey=$teleInfo['access_key'];

	//$recUrl=$gBrandInfo['aconf_rec_url'];
	if ($recUrl=='') {
		LogError("Teleconference server url not set. id=".$teleServerId);
		PrintEndMessage("Teleconference server is not set for the site.");
		return;
	}
		
	if ($number=='' || $code=='') {
		LogError("Phone number or code for the recording cannot be found.");
		PrintEndMessage("Phone number or code for the recording cannot be found.");
		return;
	}
/*
	if ($teleInfo['rec_outbound']=='Y') {
		$recUrl.="?cmd=record&key=".md5('record'.$teleInfo['access_key']);
		$recUrl.="&number=1$number&mod=$code&name=$recId";
		
		$checkUrl=$recUrl."&flag=F";
	} else {
*/
		$recUrl.="conference/";		
		$data="file=$recId&phone=$number&id=$code&mp3=S";
		//$data.="&meetingid=".$meetingInfo['id'];
		$data.="&meetingid=".$meetingInfo['access_id'];

		
		if ($accessKey!='')
			$data.="&signature=".md5($data.$accessKey);
		else
			$data.="&nosig=1";
			
		$checkUrl=$recUrl."?".$data;
		
		
//	}

	// check if the audio data is ready
	PrintProgressMessage(_Text("Checking audio data..."));

	$dataSize=0;
	for($i=0; $i<15; $i++) {
		$fileFound=false;
		$res=HTTP_Request($checkUrl, '', 'GET', 30);
		LogRecord("$checkUrl\n$res\n");

		if ($res==false) {
			LogError("Couldn't get ".$checkUrl);
			PrintEndMessage("Couldn't get a response from the server.");
			return;
		} else {
/*			
			if ($teleInfo['rec_outbound']=='Y') {
				if (strpos($res, "OK")===0) {
					$resItems=explode(" ", $res);
					if (count($resItems)>1)
						$dataSize=(integer)$resItems[1];
					$fileFound=true;
					break;
					
				} else if (strpos($res, "ERROR 22")===0) {
					// in progress
					PrintProgressMessage("Waiting for audio data...");
					sleep(6);
					continue;
				} else {
					PrintEndMessage($res);
					return;
				}
			} else {
*/
				$xmlStatus='';
				$xmlInfo='';
				$mp3FileSize='';
				ParseXmlData($res);
				
				// if this is the first time in the loop and 
				// if re-process is set (process==2) and conversion not in progress (status!=100) or the file doesn't exist $xmlStatus==400
				// restart mp3 encoding.	
				if ($i==0 && (($process=='2' && $xmlStatus!='100') || $xmlStatus=='400')) {
					// mp3 file is missing; restart mp3 encoding
					$postData="phone=$number&id=$code&mp3=C&file=$recId";
					//$postData.="&meetingid=".$meetingInfo['id'];
					$postData.="&meetingid=".$meetingInfo['access_id'];

					if ($accessKey!='')
						$postData.="&signature=".md5($postData.$accessKey);
					else
						$postData.="&nosig=1";
				
					PrintProgressMessage(_Text("Starting audio encoding..."));
					$res=HTTP_Request($recUrl, $postData, 'POST', 30);
					LogRecord("$recUrl\n$postData\n$res\n");
					
					if ($res==false) {
						LogError("Couldn't get ".$recUrl);
						PrintEndMessage("Couldn't get a response from the server.");
						return;
					}
					$xmlStatus='';
					$xmlInfo='';
					ParseXmlData($res);
					
					if ((integer)$xmlStatus==400) {
						$xmlInfo=str_replace("\n", " ", $xmlInfo);
						$xmlInfo=str_replace("\r", " ", $xmlInfo);
						LogError("Error: $xmlStatus $xmlInfo");
						// missing audio data; show a more descriptive message
						PrintEndMessage("The audio data is not available. If you just created the recording, please check back in a few minutes.");
						return;
					} else if ((integer)$xmlStatus>400) {
						$xmlInfo=str_replace("\n", " ", $xmlInfo);
						$xmlInfo=str_replace("\r", " ", $xmlInfo);
						LogError("Error: $xmlStatus $xmlInfo");
						PrintEndMessage("$xmlStatus $xmlInfo");
						return;
					} else {
						// no error; wait a bit for the encoding to finish and check again
						sleep(30);
					}
				
				} else if ($xmlStatus=='0') {
					// file exists already
					$dataSize=(integer)$mp3FileSize;
					$fileFound=true;
					break;
				} else if ($xmlStatus=='100') {
					// in progress
					PrintProgressMessage(_Text("Waiting for audio data..."));
					sleep(10);
					
				} else {
					$xmlInfo=str_replace("\n", " ", $xmlInfo);
					$xmlInfo=str_replace("\r", " ", $xmlInfo);
					LogError("Error: $xmlStatus $xmlInfo");
					PrintEndMessage("$xmlStatus $xmlInfo");
					return;
				}
//			}
		}
	}


	$message='';
	if ($fileFound) {
		sleep(1);
		// copy audio data to the recording's hosting server
		// meetingInfo['webserver_id'] and ['storageserver_id'] must be set correctly prior to this
		PrintProgressMessage(_Text("Copying audio data..."));
			
		$loadUrl=VMeeting::GetExportRecUrl($memberInfo, $meetingInfo, true, true);
		if ($loadUrl=='') {
			LogError("Couldn't get $loadUrl");
			PrintEndMessage("Couldn't get web server url.");
			return;		
		}
/*		
		if ($teleInfo['rec_outbound']=='Y') {
			$getUrl=$recUrl."&flag=G";
		} else {
*/
			$data="file=$recId&phone=$number&id=$code&mp3=F";
			//$data.="&meetingid=".$meetingInfo['id'];
			$data.="&meetingid=".$meetingInfo['access_id'];
			
//		}
			
		// put this in a loop and get 256KB each time using byte range request
		$inc=256*1024;
		$count=ceil($dataSize/$inc);
		
		$start=0;
		$end=$inc-1;
		if ($end>=$dataSize)
			$end=$dataSize-1;
			
		for ($i=0; $i<$count; $i++) {
			
			$theData=$data."&startbyte=$start&endbyte=$end";
			if ($accessKey!='')
				$theData.="&signature=".md5($theData.$accessKey);
			else
				$theData.="&nosig=1";
			
			$getUrl=$recUrl."?".$theData;
			
			$url=$loadUrl."&url=".rawurlencode($getUrl);
			$url.="&progress=1&index=$i";
	//		$url.="&index=$i";
			
	//		echo($url."<br>\n");
	/*		
			PrintProgressMessage("Copying $start of $dataSize bytes.");

			$res=@file_get_contents($url);
			if (!$res) {
				sleep(1);
				$res=@file_get_contents($url);
			}
			
			if ($res && strpos($res, "OK")===0) {
				$items=explode(" ", $res);
				$loadSize=(integer)$items[1];
			} else {
				if ($res)
					PrintEndMessage($res);
				else
					PrintEndMessage("Couldn't get audio data from the server.");
				
				HideLoader();
				return;			
			}
	*/
		
			$fp=@fopen($url, "rb");
			if (!$fp) {
				sleep(1);
				$fp=@fopen($url, "rb");
			}			
			if (!$fp) {
				LogError("Couldn't get $url");
				PrintEndMessage("Couldn't get response from server.<br>$loadUrl");
				return;
			}	
			
			$hasError=false;
			$loadSize=0;
			while ($line=fgets($fp,512))
			{
				if (strpos($line, "OK")===0) {
					$items=explode(" ", $line);
					$loadSize=$items[1];
					$percent=round(($start+$loadSize)*100/$dataSize);
					$total=$count-1;
					PrintProgressMessage(_Text("Copying audio data")." $i/$total ($percent%)");
				} else {
					LogError($line." returned from ".$url);
					$line=str_replace("\n", " ", $line);
					$line=str_replace("\r", " ", $line);				
					PrintEndMessage($line);
					$hasError=true;
					break;
				}
			}
			fclose($fp);
			
			if ($hasError) {
				return;
			}

			$start+=$loadSize;
			if ($start>=$dataSize)
				break;
			
			$end=$start+$inc-1;
			if ($end>=$dataSize)
				$end=$dataSize-1;
		}
			
		// merging audio data files
		$url=$loadUrl."&merge=1";
		$res=@file_get_contents($url);
		if ($res && strpos($res, "OK")===0) {
			
			$ready=true;

			PrintEndMessage(_Text("Audio data loaded."));
			
		} else {
			if ($res) {
				LogError($url."\n".$res."\n");
				PrintEndMessage($res);
			} else {
				LogError("Couldn't get $url");
				PrintEndMessage("Couldn't merge audio data.");
			}
			return;
		}
		
		
	} else {
		PrintEndMessage(_Text("Audio file is not available. Please check again later."));
		return;
	}

}

if ($ready) {
	$updateInfo=array();
	if ($recId!='')
		$updateInfo['audio']='Y';
	$updateInfo['rec_ready']='Y';
	if ($meeting->Update($updateInfo)!=ERR_NONE) {
		LogError($meeting->GetErrorMsg());
		PrintEndMessage($meeting->GetErrorMsg());
		return;
	}
	PrintEndMessage(_Text("Processing completed."));

}


function HideLoader() {
	print <<<END
<script type="text/javascript">
<!--
	document.getElementById('loader').style.display='none';
//-->
</script>
END;
	ob_flush();	// need both ob_flush and flush to work. don't know why.
	flush();
}

function PrintProgressMessage($msg) {
	print <<<END
<script type="text/javascript">
<!--
	document.getElementById('loader_text').innerHTML="$msg";
//-->
</script>
END;
	ob_flush();
	flush();
}

function PrintEndMessage($msg) {
	print <<<END
<script type="text/javascript">
<!--
	document.getElementById('loader_text').innerHTML="$msg";
	document.getElementById('loader_icon').className="inform_icon";
//-->
</script>
END;
	ob_flush();
	flush();
}
?>


