<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// show pag content only without the header and footer
if (isset($_REQUEST['content_only']))
	return;
	
if (isset($_COOKIE['hide_nav']) && $_COOKIE['hide_nav']=='1' ||
	isset($gBrandInfo['hide_navbars']) && $gBrandInfo['hide_navbars']=='Y')
{
	$GLOBALS['HIDE_NAV']='on';
	$GLOBALS['HIDE_TABS']='on';
}
if ((isset($gBrandInfo['hide_signin']) && $gBrandInfo['hide_signin']=='Y') || GetSessionValue('hide_signin')=='1')
	$GLOBALS['HIDE_SIGNIN']='on';
		
$closeIcon="themes/close.gif";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo isset($GLOBALS['PAGE_TITLE'])?$GLOBALS['PAGE_TITLE']:""; ?></title>
<meta name="keywords" content="web conferencing">
<meta name="description" content="web conferencing">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width;">
<link href="<?php echo "themes/".$GLOBALS['THEME']?>/" rel="stylesheet" type="text/css">


<?php
if (IsIPadUser()) {
/*
	Because the site is always embedded in a frame, the css orientation selection does not work on the iPad Safari
	However, always setting min-height to the portrait height seems to work fine for all orientations
	<link rel="stylesheet" href="themes/ipad_portrait.css" type="text/css"  media="all and (orientation:portrait)" id="orient2_css">
	<link rel="stylesheet" href="themes/ipad_landscape.css" type="text/css"  media="all and (orientation:landscape)" id="orient_css">
*/
	
	echo "<link rel=\"stylesheet\" href=\"themes/ipad.css\" type=\"text/css\" >\n";
}
$localeCss="locales/".$GLOBALS['locale']."/wc2.css";

if (file_exists($localeCss)) {
	
	echo "<link href=\"$localeCss\" rel=\"stylesheet\" type=\"text/css\">\n";
}

if (isset($GLOBALS['HIDE_NAV']) && $GLOBALS['HIDE_NAV']=='on') {
print <<<END
<style type="text/css">
#main_page { border: 0 }
body {background: #fff;}
#top-corner-r { display: none !important; }
#bottom-corner-r { display: none !important; }
#main_content { border: 0; background: #fff }
</style>
END;
}
?>

<script type="text/javascript" src="js/common.js"></script>

</head>
<body>

<center>
<!--
<iframe id='load_iframe' style="display:none; visibility: hidden; height: 0px;" onload='onIFrameLoad();'></iframe>
-->
<div id='load_bar'></div>

<div id='loader' class='progress_box' style='display:none'>
	<div style='text-align: right; margin: 10px'><a id='return_link' target=_top href=''><img src='<?php echo $closeIcon?>'></a></div>
	<table>
	<tr>
	<td id='loader_icon' class="wait_icon">&nbsp;</td>
	<td id='loader_text'>Loading...</td>
	</tr>
	</table>
</div> <!--loader-->

<div id="shade"></div>
<?php 
$startMessage=GetSessionValue('start_message');
if ($startMessage!='') {
	print <<<END
<div id='announcer' class='progress_box'>
<div style='text-align: right; margin: 10px'><a onclick="return SetElemDisplay('announcer', 'none');" href=''><img src='$closeIcon'></a></div>
<table>
<tr>
<td class="inform_icon">&nbsp;</td>
<td >$startMessage</td>
</tr>
</table>
</div>
END;
	SetSessionValue('start_message', '');
}
?>



<?php 
if (isset($GLOBALS['HIDE_NAV']) && $GLOBALS['HIDE_NAV']=='on' && 
	isset($GLOBALS['HIDE_TABS']) && $GLOBALS['HIDE_TABS']=='on' &&
	isset($GLOBALS['SIDE_NAV']) && $GLOBALS['SIDE_NAV']=='off')
{
	echo "<div id='main_body'>\n";
	
	return;
}
?>