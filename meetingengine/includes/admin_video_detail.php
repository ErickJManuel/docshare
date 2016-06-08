<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vvideoserver.php");
require_once("dbobjects/vwebserver.php");

$maxVideos=12;

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
if (SID!='')
	$cancelUrl.="&".SID;
	
$videoInfo=array();
if (GetArg('id', $videoServerId)) {
	$videoServer=new VVideoServer($videoServerId);
	$videoServer->Get($videoInfo);
	if (!isset($videoInfo['id'])) {
		ShowError("Account not found");
		return;
	}
	if ($videoInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The account does not belong to this site.");
		return;
	}
} else {
	
	$videoInfo['name']='';
	$videoInfo['url']='rtmp://';
	$videoInfo['bandwidth']='0';
	$videoInfo['width']='0';
	$videoInfo['height']='0';
	$videoInfo['max_wind']='0';
	$videoInfo['audio_rate']='0';
	$videoInfo['type']='both';

}

$audioRates=array(
	"0"  => "default (high)",
	"8"  => " 8 KHz (low)",
	"11" => "11 KHz (medium)",
	"22" => "22 KHz (high)",
	"44" => "44 KHz (max)",
	);
	
$videoRates=array(
	"160-120-80"  => "160 x 120 - 80kbps",
	"176-144-120"  => "176 x 144 - 120kbps (Recommended for 6-12 windows)",
	"192-144-160"  => "192 x 144 - 160kbps",
//	"240-180-240"  => "240 x 180 - 240kbps",
	"320-240-240"  => "320 x 240 - 240kbps (Recommended for 3-6 windows)",
	"320-240-400"  => "320 x 240 - 400kbps",
	"640-480-500"  => "640 x 480 - 500kbps (Recommended for 1-2 windows)",
	"640-480-800"  => "640 x 480 - 800kbps",
//	"960-720-1000"  => "960 x 720 - 1 Mbps (720p HD webcam required)",
	"960-720-1200"  => "960 x 720 - 1.2Mbps (720p HD webcam required)",
//	"800-600-300"  => "800 x 600 - 300kbps (screen capture)",
	);


$thisPage=$_SERVER['PHP_SELF'];

//$webPage=$thisPage."?page=".PG_ADMIN_HOSTING."&brand=".$GLOBALS['BRAND_NAME'];
$webPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
$webPage=VWebServer::EncodeDelimiter2($webPage);
$message=$gText['M_SUBMIT_OK'];
$message.=" ".$gText['M_SELECT_PROFILE_FOR_GROUP'];
$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$webPage."&message=".rawurlencode($message)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

if (isset($videoInfo['id'])) 
{
/*
	$retPage="$thisPage?page=".PG_ADMIN_EDIT_VIDEO."&id=".$videoInfo['id'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
*/
	$postUrl=VM_API."?cmd=SET_VIDEO&return=$retPage";
} else {
	$postUrl=VM_API."?cmd=ADD_VIDEO&return=$retPage";
}
$postUrl.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;	

//echo $videoInfo['width']." ".$videoInfo['height']." ".$videoInfo['bandwidth'];

$checkVoip_Y='checked';
$checkVoip_N='';

if ($videoInfo['type']=='VIDEO') {
	$checkVoip_Y='';
	$checkVoip_N='checked';
}

$format=$gText['M_ENTER_VAL'];

?>


<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.name.value=='')
	{
		alert("<?php echo sprintf($format, 'Name')?>");
		theForm.name.focus();
		return (false);
	}
	if (theForm.url.value=='')
	{
		alert("<?php echo sprintf($format, 'URL')?>");
		theForm.url.focus();
		return (false);
	}

	return (true);
}

//-->
</script>


<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<?php
if (isset($videoInfo['id'])) 
{
	$id=$videoInfo['id'];
	$name=htmlspecialchars($videoInfo['name']);
	
print <<<END
<div class="heading1">$name</div>
<input type="hidden" name="id" value="$id">

END;
} else {
	$name='';
	$awsPage=$thisPage."?page=".PG_ADMIN_AWS;
	if (SID!='')
		$awsPage.="&".SID;
		
	$videoText=
_Text("Video conferencing requires an Adobe Flash Media Server 2 (FMS) or other compatible servers, such as Red5. You can create multiple profiles for the same Flash Media Server with different video window sizes and bandwidths. The FMS Application URL must link to a valid application on the FMS server. Consult FMS documentation for creating an application.");
	$fmsText=_Text("Download Adobe FMS");
	$r5Text=_Text("Download Red5");
	
	$awsLink="<a target=${GLOBALS['TARGET']} href='$awsPage'>Amazon Web Services (AWS)</a>";
	$altText=sprintf(_Text("Alternatively, you can launch a pre-configured server on %s."), $awsLink);
	 
print <<<END
<div class="heading1">${gText['M_ADD_VIDEO']}</div>

$videoText

<ul>
<li><a target=_blank href="http://www.adobe.com/go/tryflashmediaserver">$fmsText</a></li>
<li><a target=_blank href="http://osflash.org/red5">$r5Text</a></li>
</ul>

<p>
$altText;

END;
}

?>


<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w">*<?php echo _Text("Profile Name")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="name" size="40" value="<?php echo $name?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*<?php echo _Text("FMS Application URL")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="url" size="65" value="<?php echo $videoInfo['url']?>">
	<div class='m_caption'><?php echo _Text("URL of the Flash Media Server application. e.g. rtmp://www.mysite.com/video")?></div>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"><?php echo _Text("# of Video Windows")?>:</td>
	<td colspan=3 class="m_val">
	<select name="max_wind">
<?php
for ($i=0; $i<=$maxVideos; $i++) {
	if ($i==$videoInfo['max_wind'])
		$selected='selected';
	else
		$selected='';
	if ($i==0)
		echo "<option $selected value=\"$i\">default (6)</option>\n";
	else
		echo "<option $selected value=\"$i\">$i</option>\n";
	
}
?>
	</select>
	<span class='m_caption'><?php echo _Text("Maximum number of video windows in a meeting")?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"><?php echo _Text("Video Size")?>:</td>
	<td colspan=3 class="m_val">
	<select name="size">
	<option value="0">default (176x144)</option>
<?php
	
	$found=false;
	foreach ($videoRates as $k => $v) {
		list($w, $h, $b)=explode("-", $k);
		if ($w==(int)$videoInfo['width'] && $h==(int)$videoInfo['height'] && $b==(int)$videoInfo['bandwidth']) {
			$sel='selected';
			$found=true;
		} else
			$sel='';
		echo "<option $sel value=\"$k\">$v</option>\n";
	}
	
	// if the custom size is not on the default list, add the entry separately 
	if (!$found && $videoInfo['width']!='0') {
		$k=$videoInfo['width']."-".$videoInfo['height']."-".$videoInfo['bandwidth'];
		$v=$videoInfo['width']." x ".$videoInfo['height']." - ".$videoInfo['bandwidth']."kbps";
		echo "<option selected value=\"$k\">$v</option>\n";		
	}
	
/*
for ($i=0; $i<7; $i++) {
	if ($i==0) {
		$w=160; $h=120; $b=80;
	} else if ($i==1) {
		$w=176; $h=144; $b=120;
	} else if ($i==2) {
		$w=192; $h=144; $b=160;
	} else if ($i==3) {
		$w=240; $h=180; $b=240;
	} else if ($i==4) {
		$w=320; $h=240; $b=400;
	} else if ($i==5) {
		$w=640; $h=480; $b=600;
	} else if ($i==6) {
		$w=640; $h=480; $b=800;
	}
	if ($w==(int)$videoInfo['width'] && $h==(int)$videoInfo['height'] && $b==(int)$videoInfo['bandwidth'])
		$sel='selected';
	else
		$sel='';
	echo "<option $sel value=\"$w-$h-$b\">$w x $h -  $b kbps</option>\n";
	
}
*/
?>
	</select>
	<div class='m_caption'>The size and target bit rate for each video window. The total window size should not exceed the size of a typical display screen. The total bit rate should not exceed a typical broadband download bandwidth. Use the recommended size for the number of video windows selected.</div>
	</td>
</tr>


<tr>
	<td class="m_key m_key_w"><?php echo _Text("Voice Over IP")?>:</td>
	<td colspan=3 class="m_val">
	<input <?php echo $checkVoip_Y?> type="radio" name="has_voip" value="Y"><b><?php echo _Text("Enabled")?></b>&nbsp;
	<?php echo _Text("Audio sample rate")?>:
	<select name="audio_rate">
<?php
foreach ($audioRates as $k => $v) {
	if ($k==$videoInfo['audio_rate'])
		$selected='selected';
	else
		$selected='';

	echo "<option $selected value='$k'>$v</option>\n";
}

?>
	</select>  &nbsp;
	<input <?php echo $checkVoip_N?> type="radio" name="has_voip" value="N"><b><?php echo _Text("Disabled")?></b>
	</td>
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	<div class='m_caption'><?php echo $gText['M_SELECT_PROFILE_FOR_GROUP']?></div>
	</td>
</tr>


<?php
if (isset($videoInfo['id'])) 
{
	$url=$videoInfo['url'];
	$pos1=strpos($url, "//");
	$pos2=strrpos($url, "/");
	if ($pos1 && $pos2) {
		$fmsHost=substr($url, $pos1+2, $pos2-$pos1-2);
		$fmsApp=substr($url, $pos2+1);
	} else {
		$fmsHost='';
		$fmsApp='';
	}
	
	$keyText=_Text("FMS Port Tester");
	
	$hostText=_Text("Host"); //_Comment: host name of a server
	$appText=_Text("Application"); //_Comment: a computer application service or program
	
	$text1=_Text("Replace the <b>Host</b> and <b>Application</b> fields in Port Tests with the following");
	$text2=_Text("Click <b>Run</b> to test. The test passes if at least one connection is successful. The browser may not respond while the test is in progress.");
	$text3=_Text("Right click a text filed to paste.");
	
	print <<<END
<tr>
<td colspan=4><hr size=1'></td>
</tr>
<tr>
	<td class="m_key m_key_w">$keyText:</td>
	<td colspan="3" class="m_val">
		<div>$text1:</div>
		<b>$hostText</b>: <input type='text' readonly value='$fmsHost' size='50'><br>
		<b>$appText</b>: <input type='text' readonly value='$fmsApp' size='40'><br>
		<div>$text2</div>
		<div class='m_caption'>$text3</div>
		<div id="so_targ_port_tester_1297064276">
		<embed type="application/x-shockwave-flash" 
		src="port_tester.swf?rev=2055&amp;format=raw" 
		id="fm_port_tester_1297064276" 
		name="fm_port_tester_1297064276" quality="high" height="450" width="350">
		</div>
	</td>
</tr>
END;
}
?>


</table>
</form>