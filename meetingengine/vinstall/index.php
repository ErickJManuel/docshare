<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 *
 * Do not modify this file. This file will be updated automatically by vinstall.php.
 */

if (isset($_GET['ping']))
	die("pong");
	
if (file_exists("site_config.php"))
	include_once("site_config.php");
else
	include_once("config.php");	 // for backword compatibility
/*
$ipad=false;
if (isset($_SERVER['HTTP_USER_AGENT'])) {
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if (strpos($userAgent, "ipad")!==false)
		$ipad=true;
}

if (isset($iphoneEnabled) && $iphoneEnabled && isset($_SERVER['HTTP_USER_AGENT'])) {
	$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if (strpos($userAgent, "iphone")!==false || strpos($userAgent, "ipod")!==false) {
		
		$iphonePage=$serverUrl."iphone/?brand=".$brand;
		if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']!='')
			$iphonePage.="&".$_SERVER['QUERY_STRING'];

		header("Location: $iphonePage");
		exit();	
	}
}
*/	
$frameUrl=$serverUrl."?1";
if (!isset($_GET['brand']))
	$frameUrl.="&brand=".$brand;

if (!isset($_GET['brand_url'])) {
	$proto="http://";
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on')
		$proto="https://";
	elseif (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']=='443')
		$proto="https://";
	
	$server='';
	if (isset($_SERVER['SERVER_NAME']))
		$server=$_SERVER['SERVER_NAME'];
	elseif (isset($_SERVER['HTTP_HOST']))
		$server=$_SERVER['HTTP_HOST'];
	
	$scriptPath='';
	if (isset($_SERVER['PHP_SELF']))
		$scriptPath=$_SERVER['PHP_SELF'];
	elseif (isset($_SERVER['SCRIPT_NAME']))
		$scriptPath=$_SERVER['SCRIPT_NAME'];
	
	$scriptName=basename($scriptPath);
	$path=str_replace($scriptName, '', $scriptPath);
	
	$port='';
	if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT']!='80' && $_SERVER['SERVER_PORT']!='443')
		$port=":".$_SERVER['SERVER_PORT'];
	
	if ($server!='') {
		$brandUrl=$proto.$server.$port.$path;
		$frameUrl.="&brandUrl=".$brandUrl;
	}
}


if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']!='')
	$frameUrl.="&".$_SERVER['QUERY_STRING'];

if (isset($_GET['redirect']) || isset($_GET['StorageServerUrl']) || isset($_GET['StorageUserName'])) {
	header("Location: $frameUrl");
	exit();
}

// $winTitle should be defined in site_config.php. Override it if the title is passed in.
// need to set title, description for Facebook to get the correct meta value when creating a post
if (isset($_GET['title']))
	$winTitle=htmlspecialchars($_GET['title']);

$description='';
if (isset($_GET['d']))
	$description=htmlspecialchars($_GET['d']);

$image='';
if (isset($_GET['i']))
	$image=$_GET['i'];
	
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
   "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<?php
	if (isset($_GET['page']) && $_GET['page']=='HELP') {
		// don't set the viewport for HELP pages because the page format is incorrect on the iPad when the viewport is set
	} else {
		// set the viewport for all other pages
		echo "<meta name=\"viewport\" content=\"width=device-width;\">\n";
	}
?>
<meta name="keywords" content="web conferencing">
<meta name="description" content="<?php echo $description?>">
<?php if ($image!='') {
	echo "<link rel=\"image_src\" href=\"$image\" />\n";
}
?>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $winTitle; ?></title>

</head>
  <frameset rows="100%">
       <frame name="content" src="<?php echo $frameUrl; ?>" scrolling='auto' marginheight='0' marginwidth='0'>
  </frameset>
  <noframes>
	<body><a href="<?php echo $frameUrl; ?>">Go</a></body>
  </noframes>
</html>