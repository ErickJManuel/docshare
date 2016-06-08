<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// $swfFile and $winTitle should be defined in the file that includes this file

/**
* takes a string of utf-8 encoded characters and converts it to a string of unicode entities
* each unicode entitiy has the form &#nnnnn; n={0..9} and can be displayed by utf-8 supporting
* browsers
* @param $source string encoded using utf-8 [STRING]
* @return string of unicode entities [STRING]
* @access public
*/
function utf8ToUnicodeEntities ($source) {
	// array used to figure what number to decrement from character order value 
	// according to number of characters used to map unicode to ascii by utf-8
	$decrement[4] = 240;
	$decrement[3] = 224;
	$decrement[2] = 192;
	$decrement[1] = 0;

	// the number of bits to shift each charNum by
	$shift[1][0] = 0;
	$shift[2][0] = 6;
	$shift[2][1] = 0;
	$shift[3][0] = 12;
	$shift[3][1] = 6;
	$shift[3][2] = 0;
	$shift[4][0] = 18;
	$shift[4][1] = 12;
	$shift[4][2] = 6;
	$shift[4][3] = 0;

	$pos = 0;
	$len = strlen ($source);
	$encodedString = '';
	while ($pos < $len) {
		$asciiPos = ord (substr ($source, $pos, 1));
		if (($asciiPos >= 240) && ($asciiPos <= 255)) {
			// 4 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 4);
			$pos += 4;
		}
		else if (($asciiPos >= 224) && ($asciiPos <= 239)) {
			// 3 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 3);
			$pos += 3;
		}
		else if (($asciiPos >= 192) && ($asciiPos <= 223)) {
			// 2 chars representing one unicode character
			$thisLetter = substr ($source, $pos, 2);
			$pos += 2;
		}
		else {
			// 1 char (lower ascii)
			$thisLetter = substr ($source, $pos, 1);
			$pos += 1;
		}
		// process the string representing the letter to a unicode entity
		$thisLen = strlen ($thisLetter);
		$thisPos = 0;
		$decimalCode = 0;
		while ($thisPos < $thisLen) {
			$thisCharOrd = ord (substr ($thisLetter, $thisPos, 1));
			if ($thisPos == 0) {
				$charNum = intval ($thisCharOrd - $decrement[$thisLen]);
				$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
			}
			else {
				$charNum = intval ($thisCharOrd - 128);
				$decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
			}
			$thisPos++;
		}
		if ($thisLen == 1)
			$encodedLetter = "&#". str_pad($decimalCode, 3, "0", STR_PAD_LEFT) . ';';
		else
			$encodedLetter = "&#". str_pad($decimalCode, 5, "0", STR_PAD_LEFT) . ';';
		$encodedString .= $encodedLetter;
	}
	return $encodedString;
}

if (!isset($arg))
	$arg='';

if (isset($_GET['user'])) {
	$user=$_GET['user'];
	$user=rawurlencode($user);
	$arg.="&User=".$user;
}
if (isset($_GET['pass']))
	$arg.="&Password=".rawurlencode($_GET['pass']);
if (isset($_GET['email']))
	$arg.="&Email=".$_GET['email'];

if (isset($_GET['vars']))
	$arg.="&VarsFile=".$_GET['vars'];
if (isset($_GET['sid'])) {
	$sid=$_GET['sid'];
	$sid=str_replace("=", "%3D", $sid);
	$arg.="&SessID=".$sid;
}	
if (isset($_GET['host_id'])) {
	$arg.="&HostID=".$_GET['host_id'];
}
if (isset($_GET['brand_url'])) {
	$arg.="&BrandUrl=".$_GET['brand_url'];
}
if (isset($_GET['has_menubar'])) {
	$arg.="&HasMenubar=".$_GET['has_menubar'];
}
if (isset($_GET['has_windows'])) {
	$arg.="&HasWindows=".$_GET['has_windows'];
}
if (isset($_GET['hide_windows'])) {
	$arg.="&HideWindows=".$_GET['hide_windows'];
}
if (isset($_GET['client_id'])) {
	$arg.="&client_id=".$_GET['client_id'];
}
if (isset($_GET['client_code'])) {
	$arg.="&client_code=".$_GET['client_code'];
}

// FSCommand (javascript) is supported
$arg.="&HasFSCommand=1";

if (isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), "chrome")!==false) {
	// for Chrome, we need the external url to open a new window or tab
	$arg.="&UrlTarget=_blank";
} else {
	// for all other browsers, the url shouldn't open a new window
	$arg.="&UrlTarget=_self";
}	


$arg.="&".rand();

if (!isset($swfFile))
	$swfFile="../../../viewer.swf";

$swfFile.="?".rand();
if (!isset($baseDir))
	$baseDir="../../../";
	
$width="100%";
if (isset($_GET['width']))
	$width=$_GET['width'];
$height="100%";
if (isset($_GET['height']))
	$height=$_GET['height'];

if (isset($_GET['title'])) {
	$winTitle=$_GET['title'];
}

$postUrl='';

//$viewerUrl=$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
$swfName="viewer";
$requiredVersion="9.0.28";



?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo utf8ToUnicodeEntities($winTitle); ?></title>

<?php
/*
<script type="text/javascript" src="<?php echo $baseDir?>swfobject.js"></script>
*/
?>

<script type="text/javascript" src="<?php echo $baseDir?>swfobject.js"></script>

<script type="text/javascript" src="<?php echo $baseDir?>viewer.js"></script>

<link href="<?php echo $baseDir?>viewer.css" rel="stylesheet" type="text/css">

<script type="text/javascript">
<!--
	var baseDir = "<?php echo $baseDir?>";
	ShowMessage(true, "<?php if ($loadMessage) echo $loadMessage?>");
    swfobject.registerObject("<?php echo $swfName?>", "<?php echo $requiredVersion?>", "expressInstall.swf");

//-->
</script>

<style type="text/css">

	/* hide from ie on mac */
	html {
		height: 100%;
		overflow: hidden;
	}
	
	#flashcontent {
		height: 100%;
	}
	/* end hide */

	body {
		height: 100%;
		margin: 0;
		padding: 0;
		background-color: #fff;
	}

</style>

</head>


<body >

<div id='message_box' class='message' style='display:none'>
<div class='box_header'><a onclick="ShowMessage(false, ''); return false;" href='javascript:void(0)'>
[ X ]</a></div>
<p id='message_text'>
</p>
</div>
<div id='page_box' class='page' style='display:none'>
<div class='box_header'><a onclick="ShowPageBox(false); return false;" href='javascript:void(0)'>
[ X ]</a></div>
<iframe id='page_content' src=''></iframe>

</div>
<div id='sharing_box' class='page' style='display:none'>
<div class='box_header'><a onclick="ShowSharingBox(false); return false;" href='javascript:void(0)'>
[ X ]</a></div>
<iframe id='sharing_content' src=''></iframe>

</div>

<div id="flashcontent">
	<object id="<?php echo $swfName?>" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="<?php echo $width; ?>" height="<?php echo $height; ?>">
		<param name="flashvars" value="<?php echo $arg; ?>" />
		<param name="movie" value="<?php echo $swfFile; ?>" />
		<param name="swliveconnect" value="true" />
		<param name="wmode" value="opaque" />
		<param name="allowScriptAccess" value="always" />
		<param name="allowFullScreen" value="true" />
		
		<object data="<?php echo $swfFile; ?>" flashvars="<?php echo $arg; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>"
			swliveconnect=true name="<?php echo $swfName?>" wmode="opaque" allowFullScreen="true" allowScriptAccess="always"
			type="application/x-shockwave-flash">
		
			<div class="noflash">
				<p>You need the latest version of the Adobe Flash Player.<p/>
				<p><a target=_blank href="https://www.adobe.com/go/getflashplayer"><img src="https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
			</div>
		
		</object>
	
	</object>
</div>


<?php
/*
<script type="text/javascript">

	
	// <![CDATA[
	var so = new SWFObject("<?php echo $swfFile; ?>", "<?php echo $swfName?>", "<?php echo $width; ?>", "<?php echo $width; ?>", "<?php echo $requiredVersion?>", "#FFFFFF");
	so.addParam("flashvars", "<?php echo $arg; ?>");
	so.addParam("swliveconnect", "true");
	so.addParam("allowFullScreen", "true");
	so.addParam("wmode", "opaque");
	so.addParam("allowScriptAccess", "always");
	so.useExpressInstall('<?php echo $baseDir?>expressinstall.swf');
	so.write("flashcontent");
	// ]]>

</script>
*/
?>

</body>
</html>

