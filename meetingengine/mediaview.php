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

ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

$retIcon="themes/icon_return.png";
$prevIcon="themes/icon_prev.png";
$nextIcon="themes/icon_next.png";
$zoomInIcon="themes/icon_zoom_in.png";
$zoomOutIcon="themes/icon_zoom_out.png";

$videoplayer="videoplayer.swf";
$audioplayer="audioplayer.swf";

GetArg('url', $url);
GetArg('lib', $lib);
GetArg('title', $title);
GetArg('type', $type);
GetArg('server_url', $serverUrl);

$retUrl="libview.php?lib=".$lib."&rand=".rand();
GetArg('meeting', $meetingId);
GetArg('user_id', $userId);
if ($meetingId!='')
	$retUrl.="&meeting=".$meetingId;
if ($userId!='')
	$retUrl.="&user_id=".$userId;

if ($type=='PPT') {
	$retUrl.="&media=2";
} else if ($type=='JPG') {
	$retUrl.="&media=1";
} else if ($type=='MP3') {
	$retUrl.="&media=4";
} else if ($type=='FLV') {
	$retUrl.="&media=3";
}

$fileName=basename($url);
$libUrl=str_replace($fileName, "", $url);

function start_xml_tag($parser, $name, $attribs) {
	global $slidesList;
	if ($name=='slides') {
		$slidesList=array();

	} else if ($name=='slide') {
		$index=count($slidesList)-1;
		$slidesList[$index]=array();
		foreach ($attribs as $key => $val) {
			if (isset($slidesList[$index][$key]))
				$slidesList[$index][$key].=$val;
			else
				$slidesList[$index][$key]=$val;
			
		}	
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

if ($type=='PPT') {

	// this is a TOC xml file
	// get a list of slide urls
	$resp=@file_get_contents($url);
	
	if ($resp===false)
		die("Couldn't get library content.");
	
	// parse the xml response
	$slidesList=array();
	$xml_parser = xml_parser_create("UTF-8"); 
	xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
	xml_set_element_handler($xml_parser, "start_xml_tag", "end_xml_tag");
	xml_set_character_data_handler($xml_parser, "parse_xml_data");
	xml_parse($xml_parser, $resp, true);
	xml_parser_free($xml_parser);

}

$noflashText="<p>Flash Player is required to display this file type.<p>You need to install the Flash Player.";

?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>


<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width"/>
<link href="themes/libviewer.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/lib.js"></script>

<script type="text/javascript">
<!--
	var slideIndex=0;
	var showFullSize=false;
	var slides=new Array();
	
<?php
if ($type=='PPT') {

	foreach($slidesList as $slideInfo) {
		$slideUrl=IsUrl($slideInfo['fileName'])?$slideInfo['fileName']:$libUrl.$slideInfo['fileName'];

print <<<END
	slides.push("$slideUrl");
	
END;
	}

}

?>


//-->
</script>

</head>

<body>


<div id='cont-bar'>
<ul>
<li id='file_name'><?php echo htmlspecialchars($title)?></li>
<li id='return'><a href="<?php echo $retUrl?>"><img src='<?php echo $retIcon?>'></a></li>
<?php 
if ($type=='PPT') {
print <<<END
<li id='prev_slide'><a href='#' onclick='showSlide(slideIndex-1); return false'><img src='$prevIcon'></a></li>
<li id='slide_index'></li>
<li id='next_slide'><a href='#' onclick='showSlide(slideIndex+1); return false'><img src='$nextIcon'></a></li>

END;
	if (!IsIPadUser() && !IsIPhoneUser()) {
		print <<<END
<li id='zoom_slide'><a href='#' onclick='toggleSlide(); return false'><img id='zoom_slide_img' src='$zoomInIcon'></a></li>

END;
	}
}
?>
</ul>
</div>

<div id='lib_content'>

<?php 
if ($type=='PPT') {
print <<<END
	<div id="slide"></div>
END;
} else if ($type=='FLV') {
	$vars="flv=".$url;
	$vars.="&policy_url=".$serverUrl."crossdomain.xml";

// see http://learnswfobject.com/the-basics/static-publishing
print <<<END
<div id='flvplayer'>
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width='550px' height='360px'>
		<param name='movie' value='$videoplayer'/>
		<param name='wmode' value='opaque'/>
		<param name="flashvars" value="$vars" />
		<param name='swliveconnect' value='true'/>
		<param name='allowScriptAccess' value='always'/>
		<param name="quality" value="high" />
		
		<object type="application/x-shockwave-flash" width='550px' height='360px' data='$videoplayer' quality="high" wmode='opaque' swliveconnect="true" allowScriptAccess="always" flashvars='$vars'>
		
			<div class="noflash">
				$noflashText
			</div>
		
		</object>
		
	</object>
</div>
END;
} else if ($type=='JPG') {
print <<<END
	<div id="pict"><img id='pict_img' src='$url'></div>
END;
} else if ($type=='MP3') {
	$vars="mp3=".$url;
	$vars.="&policy_url=".$serverUrl."crossdomain.xml";
	
	// see http://learnswfobject.com/the-basics/static-publishing
print <<<END
<div id='mp3player'>
	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width='300px' height='200px'>
		<param name='movie' value='$audioplayer'/>
		<param name='wmode' value='opaque'/>
		<param name="flashvars" value="$vars" />
		<param name='swliveconnect' value='true'/>
		<param name="flashvars" value="$vars" />
		
		<object type="application/x-shockwave-flash" width='300px' height='200px' src='$audioplayer' wmode='opaque' swliveconnect="true" allowScriptAccess="always" flashvars='$vars'>
		
			<div class="noflash">
				$noflashText
			</div>
		
		</object>
			
	</object>
</div>
END;
}
?>

</div>

</body>
</html>

<script type="text/javascript">
<!--
<?php
/*
if (IsIPhoneUser() || IsIPadUser()) {	$text=$gText['M_NO_FLASH_SUP'];	print <<<END	var elem=document.getElementById("flvplayer");	if (elem) elem.innerHTML="$text";	elem=document.getElementById("mp3player");	if (elem) elem.innerHTML="$text";END;}
*/
if ($type=='PPT') {
print <<<END
	showSlide(slideIndex);
END;
} else if ($type=='FLV') {
	
} else if ($type=='JPG') {

} else if ($type=='MP3') {

}

?>

//-->
</script>