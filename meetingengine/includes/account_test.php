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
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vvideoserver.php");
require_once("dbobjects/vgroup.php");


if (GetArg("meeting", $meetingId)) {
	VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
	if (isset($meetingInfo['host_id'])) {
		$user=new VUser($meetingInfo['host_id']);
	}
} else {
	$memberId=GetSessionValue('member_id');
	if ($memberId!='')
		$user=new VUser($memberId);
}

if (isset($user)) {
	$user->Get($userInfo);
	$webServerId=VUser::GetWebServerId($userInfo);
	$webServer=new VWebServer($webServerId);
	$serverInfo=array();
	$webServer->Get($serverInfo);
} else {
	$serverInfo=array();
	$serverInfo['url']='';
}

$swfFile="speedtest.swf";
$swfName="speedtest";
$requiredVersion="7";
$width=510;
$height=460;

$videoServerUrl='';
$licenseId=$userInfo['license_id'];
$license=new VLicense($licenseId);
$licInfo=array();
$license->Get($licInfo);

if ($licInfo['video_conf']=='Y') {
	$group=new VGroup($userInfo['group_id']);
	$group->GetValue('videoserver_id', $videoId);
	if ($videoId && $videoId!='0') {
		$videoInfo=array();
		$videoServer=new VVideoServer($videoId);
		$videoServer->GetValue('url', $videoServerUrl);
	}
}
//$flashVars="MgmtServerUrl=".VWebServer::AddPaths(SITE_URL, "scripts/vtest.php");
//$flashVars.="&HostServerUrl=".VWebServer::AddPaths($serverInfo['url'], "scripts/vtest.php");
$flashVars="HostServerUrl=".VWebServer::AddPaths($serverInfo['url'], "scripts/vtest.php");
if ($videoServerUrl!='')
	$flashVars.="&RtmpServerUrl=".$videoServerUrl;
$flashVars.="&ds_0=400&ds_1=1000&us_0=200&us_1=400&lt_0=600&lt_1=300";	// thresholds for speeds rating Download slow: <400kbps fair: <1000kbsp; Upload slow: <200kbps fair:<400kbps


$testUrl=$gBrandInfo['site_url']."speedtest.php?flashvars=".rawurlencode($flashVars);
// if "crossdomain.xml" is not required, we must use the swf file from the hosting site.
if (defined("REQUIRE_CROSSDOMAIN") && constant("REQUIRE_CROSSDOMAIN")=='0') {
/*
<script type="text/javascript" src="swfobject.js"></script>
<script type="text/javascript">
swfobject.registerObject("<?php echo $swfName?>", "9.0.0", "expressInstall.swf");
</script>
*/
?>
<center>
<iframe src ="<?php echo $testUrl?>" width="515" height="645" frameborder=0 marginwidth=0 marginheight=0 scrolling="no">
  <p>Your browser does not support iframes.</p>
</iframe>
</center>

<?php
} else {
	// use the swf file from the management server
	// crossdomain.xml is required on the hosting site

?>

<?php
	// see http://learnswfobject.com/the-basics/static-publishing
?>
<center>
<div id="flashcontent">  	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
			id="<?php echo $swfName?>" width="<?php echo $width?>" height="<?php echo $height?>">
			<param name="movie" value="<?php echo $swfFile."?".$flashVars?>" />
			<param name="quality" value="best" />
			<param name="bgcolor" value="#ffffff" />
			<param name="allowScriptAccess" value="sameDomain" />
			
			
			<object type="application/x-shockwave-flash" data="<?php echo $swfFile."?".$flashVars?>"
				quality="best" bgcolor="#ffffff"
				width="<?php echo $width?>" height="<?php echo $height?>" name="<?php echo $swfName?>"
				play="true"
				loop="false"
				quality="best"
				allowScriptAccess="sameDomain">
			

				<div class="noflash">
					<p>You need the latest version of the Adobe Flash Player.<p/>
					<p><a target=_blank href="https://www.adobe.com/go/getflashplayer"><img src="https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
				</div>
			
			
			</object>
			

	</object>
</div>
</center>

<script type="text/javascript"><!--<?php/*	if (IsIPhoneUser() || IsIPadUser()) {		$text=$gText['M_NO_FLASH_SUP'];		print <<<END	document.getElementById("flashcontent").innerHTML="$text";END;	} else {		print <<<END	var so = new SWFObject("$swfFile", "$swfName", "$width", "$height", "$requiredVersion", "#FFFFFF");	so.addParam("flashvars", "$flashVars");	so.useExpressInstall("expressinstall.swf");
	so.write("flashcontent");END;	}*/?>//--></script>


<?php
}
?>
