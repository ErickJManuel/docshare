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

require_once("dbobjects/vstorageserver.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vtoken.php");

$memberId=GetSessionValue('member_id');
$member=new VUser($memberId);
$memberInfo=array();
$member->Get($memberInfo);
if (!isset($memberInfo['id'])) {
	ShowError("Couldn't find the login user.");
	return;
}

//$libUrl=SITE_URL."getlibrary.php?user_id=".$memberId;
$libUrl=SITE_URL."getlibrary.php?user_id=".$memberId.rawurlencode("&")."rand_path=".mt_rand(); 

//$webServerId=VUser::GetWebServerId($memberInfo);
//$serverUrl=$gBrandInfo['site_url'];

$errMsg=VUser::GetStorageUrl($memberInfo['brand_id'], $memberInfo, $serverUrl, $id, $password, $storageServerId);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}


$user_id=$memberInfo['access_id'];

// create a token to be used for API authentication
$brandName=GetSessionValue('brand_name');

$token=VToken::AddToken($brandName, '0', $user_id, $memberInfo);

$bumToken=VToken::GetBUMToken($brandName,$user_id,'0',$token);


$url=$serverUrl."vscript.php";
$url.="?s=vftp&cmd=postinfo";
//$url.="&id=$id&code=$password";
// switch to token for authentication
// the token code must be brand_userid_meetingid_token
$url.="&id=token&code=".$bumToken;

//$content=@file_get_contents($url);
$content=HTTP_Request($url);

if ($content==false) {
	ShowError("Couldn't get a response from library server ".$serverUrl);
	//	return;
} else {
	$content=str_replace("\n", "&", trim($content));
	
	$args=explode("&", $content);
	$count=count($args);
	
	if ($args[0]!='OK') {
		ShowError("Invalid response returned from library server ".$serverUrl);
		//	return;
	}
}

$uploadMax=0;
$postMax=0;
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

$maxSize=(int)$uploadSize*1024*1024;

$apiUrl=SITE_URL.VM_API."?cmd=GET_LIB_UPLOAD&user=".$user_id."&token=".$token;

$myUploadUrl="vpresent://--presentation&".VWebServer::EncodeDelimiter1($apiUrl);
$apiUrl.="&public=1";
$publicUploadUrl="vpresent://--presentation&".VWebServer::EncodeDelimiter1($apiUrl);


$pptServer=SITE_URL."ppt/";
$uploadUrl=$pptServer."upload.php";

$flashVars="id=token&code=".$bumToken;
//$flashVars="id=$user_id&code=$password";
//$flashVars="id=$id&code=$password";
//$flashVars="id=sig&code=".ComputeScriptSignature($_SERVER['REMOTE_ADDR']);
$flashVars.="&Libraries=$libUrl&UploadSize=$uploadSize";
$flashVars.="&Text=language/libmgr&Locale=en";
$flashVars.="&Local_ppt=vpresent://--presentation%26".SITE_URL.VM_API."?cmd=GET_LIB_UPLOAD|user=".$user_id."|token=".$token;
$flashVars.="&rand=".rand();
$swfFile='libmgr.swf';
$swfName='libmgr';
$requiredVersion="9";

//$downloadPage='';

// not used. kept for reference. concatenate multi-line text into a single line for javasript string variables.
function convertNL($txt) {
	return eregi_replace("\n","\\n",eregi_replace("\r","",$txt));
};

// if "crossdomain.xml" is not required, we must use the swf file from the hosting site in order to access the lib content on the site.
if (defined("REQUIRE_CROSSDOMAIN") && constant("REQUIRE_CROSSDOMAIN")=='0') {
	$libMgrUrl=$gBrandInfo['site_url']."library_manager.php?flashvars=".rawurlencode($flashVars);
?>

<iframe src ="<?php echo $libMgrUrl?>" width="100%" height="100%" frameborder=0 marginwidth=0 marginheight=0 scrolling="no">
  <p>Your browser does not support iframes.</p>
</iframe>

<?php
} else {
	// use the swf file from the management server to browse the content on the hosting site
	// crossdomain.xml is required on the hosting site
?>

<?php
/*
<script type="text/javascript" src="swfobject.js"></script><script type="text/javascript">
swfobject.registerObject("<?php echo $swfName?>", "9.0.28", "expressInstall.swf");
</script>
*/?>

<style type="text/css">
		
	#flashcontent {
		height: 100%;
		width: 100%;
	}
	
</style>

<div id="flashcontent">	
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
			id="<?php echo $swfName?>" width="100%" height="100%" >
			<param name="movie" value="<?php echo $swfFile?>" />
			<param name="flashvars" value="<?php echo $flashVars?>" />
			<param name="quality" value="best" />
			<param name="wmode" value="opaque" />
			<param name="bgcolor" value="#ffffff" />
			<param name="allowScriptAccess" value="sameDomain" />
			
			<object type="application/x-shockwave-flash" data="<?php echo $swfFile?>"
				quality="best" bgcolor="#ffffff"
				width="100%" height="100%" name="<?php echo $swfName?>" align="middle"
				flashvars="<?php echo $flashVars?>"
				play="true"
				loop="false"
				quality="best"
				wmode="opaque"
				allowScriptAccess="sameDomain">
			
				<div class="noflash">
					<p>You need the latest version of the Adobe Flash Player.<p/>
					<p><a target=_blank href="https://www.adobe.com/go/getflashplayer"><img src="https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
				</div>
			
			</object>
			
	</object>

</div>

<script type="text/javascript">
<!--
<?php/*	if (IsIPhoneUser() || IsIPadUser()) {		$text=$gText['M_NO_FLASH_SUP'];		print <<<END	document.getElementById("flashcontent").innerHTML="$text";END;	} else {		print <<<END
	var so = new SWFObject("$swfFile", "$swfName", "100%", "100%", "$requiredVersion", "#FFFFFF");
	so.addParam("flashvars", "$flashVars");
	so.addParam("wmode", "opaque");
	so.addParam("quality", "best");
	so.useExpressInstall("expressinstall.swf");
	so.write("flashcontent");
END;
	}
*/
// -->
</script>


<?php
}
?>
