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

require_once("dbobjects/vtoken.php");
require_once("dbobjects/vuser.php");

$memberId=GetSessionValue('member_id');
if ($memberId=='') {
	ShowError("Your session has expired.");
	return;
}
$member=new VUser($memberId);
$memberInfo=array();
$member->Get($memberInfo);
if (!isset($memberInfo['id'])) {
	ShowError("Couldn't find the login user.");
	return;
}

// if the lib page is loaded in a live meeting, the meeting id is passed in from the meeting viewer
GetArg('meeting', $aMeetingId);
GetArg('user_id', $userId);

// token may be passed in by the Flash viewer if the library page is loaded from the viewer
//GetArg('token', $token);	// don't use the token from the viewer because it doesn't work for participants
$token='';
if ($token=='') {
	// create a token to be used for API authentication
	$brandName=GetSessionValue('brand_name');
	$token=VToken::AddToken($brandName, $aMeetingId, $memberInfo['access_id'], $memberInfo);
}

$libUrl=SITE_URL."libview.php?brand=".$GLOBALS['BRAND_NAME'];
// when the lib manager is called by the Flash viewer from a live meeting, these parameters shall be passed in.
if ($aMeetingId!='')
	$libUrl.="&meeting=".$aMeetingId;
if ($userId!='')
	$libUrl.="&user_id=".$userId;

$mid=$aMeetingId==''?'0':$aMeetingId;
$bumToken=VToken::GetBUMToken($brandName,$memberInfo['access_id'],$mid,$token);

//$serverUrl=$gBrandInfo['site_url'];
VUser::GetStorageUrl($memberInfo['brand_id'], $memberInfo, $storageUrl, $storageId, $storageCode, $storageServerId);

if (GetSessionValue('lib_max_size')=='') {
	
	//$url=$serverUrl."vscript.php";
	$url=$storageUrl."vscript.php";
	$url.="?s=vftp&cmd=postinfo";
	//$url.="&id=$id&code=$password";
	// switch to token for authentication
	// the token code must be brand_userid_meetingid_token
	$url.="&id=token&code=".$bumToken;

	$content=@file_get_contents($url);
	//$content=HTTP_Request($url);

	if ($content===false) {
		ShowError("Couldn't get a response from library server ".$storageUrl);
		//	return;
	} else {
		$content=str_replace("\n", "&", trim($content));
		
		$args=explode("&", $content);
		$count=count($args);
		
		if ($args[0]!='OK') {
			ShowError("Invalid response returned from library server ".$url." rsp=".$content);
			//	return;
		}
	}

	$uploadMax=0;
	$postMax=0;
	$uploadTime=300;
	if (is_array($args)) {
		foreach ($args as $theArg) {
			$items=explode("=", $theArg);
			$key=$items[0];
			if (isset($items[1]))
				$val=$items[1];
			else
				$val='';
			if ($key=="upload_max_filesize")
				$uploadMax=$val;
			else if ($key=="post_max_size")
				$postMax=$val;
			else if ($key=="max_execution_time")
				$uploadTime=$val;
			
		}
	}
	$uploadMax=str_replace("M", '', $uploadMax);
	$postMax=str_replace("M", '', $postMax);

	$uploadSize=(int)$uploadMax;
	if ($postMax<(int)$uploadMax)
		$uploadSize=(int)$postMax;
		
		
	SetSessionValue("lib_max_size", $uploadSize);
	SetSessionValue("lib_max_time", $uploadTime);
}
	
//$maxSize=(int)$uploadSize*1024*1024;

// set these values before embedding the libviewer
SetSessionValue("lib_token", $token);

//if ($aMeetingId!='')
//	SetSessionValue("lib_meeting", $aMeetingId);

//$libUrl.="&maxSize=$uploadSize&maxTime=$uploadTime";

$myLibText=_Text("My Library");
$pubLibText=_Text("Public Library");

$libNum=2;
if ($memberInfo['login']==VUSER_GUEST) {
	$libNum=1;
}
?>

<script type="text/javascript" src="js/lib.js"></script><script type="text/javascript">
<!--
	var libIndex=1;
	var libUrl= "<?php echo $libUrl?>";
//-->
</script>
<link href="themes/lib.css" rel="stylesheet" type="text/css">	

<?php
if (strpos($GLOBALS['THEME'], 'broadsoft')!==false) {
	print <<<END
<div class='m_caption'>Content in the Public Library is loaded by the Administrator. Content in My Library is loaded by the Host.</div>

END;
}
?>

<div class="lib-mgr">
<div class="lib-tabs">
<ul class="tab-list">
<li id='tab1' class="lib_selected" ><a href="javascript:void(0)" onclick="showLib(1); return false;" ><?php echo $pubLibText?></a></li>
<?php
if ($memberInfo['login']!=VUSER_GUEST) {
print <<<END
<li id='tab2' ><a href="javascript:void(0)" onclick='showLib(2); return false;'>$myLibText</a></li>
END;
}
?>

</ul>
</div>

<iframe id='lib-frame' src='' frameborder="0" scrolling="auto"></iframe>

</div>

<script type="text/javascript">
<!--
	showLib(<?php echo $libNum?>);
//-->
</script>
