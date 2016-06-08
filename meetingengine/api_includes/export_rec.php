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

// export complete recording as a zip file. NOT DONE YET.

require_once('api_includes/common.php');
require_once("api_includes/zipfile.php");
require_once('api_includes/meeting_common.php');
require_once('dbojectgs/vobject.php');

if ($errMsg!='')
	API_EXIT(API_ERR, $errMsg);

if (!isset($meetingInfo['id']))
	API_EXIT(API_ERR, "Meeting id not set.");

//$filename = DIR_TEMP."output.zip";
$filename = GetTempDir()."output.zip";
$fd = fopen ($filename, "wb");

$zipfile = new zipfile();

$dir=$meetingInfo['access_id']."/";
$zipfile -> add_dir($dir);

$zipfile -> add_dir($dir."files/");
$zipfile -> add_file("test", $dir."files/test.txt");
$out = fwrite ($fd, $zipfile -> flush());

$meetingTitle=$meetingInfo['title'];

$filedata =
"<html>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1'/>
<title>$meetingTitle</title>
</head>
<body style='margin:0; padding:0;'>
<iframe src='files/index.htm' frameborder='0' marginwidth='0' width='100%' height='100%' scrolling='no'></iframe>
</body>
</html>";

$zipfile -> add_file($filedata, $dir."index.htm");

$out = fwrite ($fd, $zipfile -> file());
fclose ($fd);
/*
// the next three lines force an immediate download of the zip file:
header("Content-type: application/octet-stream");
header("Content-disposition: attachment; filename=test.zip");
echo $zipfile -> file();
*/
API_EXIT(API_NOMSG);


?>