<?php 
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * 
 */

$includeFile='vinclude.php';
if (isset($gScriptDir))
	$includeFile=$gScriptDir.$includeFile;
include_once $includeFile;

if (!$_GET['evtdir'])
	ErrorExit("missing parameter 'evtdir'");

$theDir=$_GET['evtdir'];
if ($theDir[strlen($theDir)-1]!='/')
	$theDir.="/";

$pid='';
if (isset($_GET['pollid']))
	$pid=$_GET['pollid'];

// get results of a single poll
if ($pid!='') {
	$qfile=$theDir.$gPollsDir.$pid.".poll";
	$fp = @fopen ($qfile, "r");
	
	$content="<poll>";
	if (@flock($fp, LOCK_SH)) {
		$content.=fread($fp, filesize($qfile));
		flock($fp, LOCK_UN);
	}
	$content.="</poll>";
	@fclose($fp);
} else {

	// get results of all polls
	$content="<polls>";
	$pollDir=$theDir.$gPollsDir;
	if ($dh = @opendir($pollDir)) { 
		while (($file = @readdir($dh)) !== false) {
			if ($file!="." && $file!=".." && strpos($file, "poll")>0) {
				$theFile=$pollDir.$file;
				$fp=@fopen($theFile, "r");
				if (@flock($fp, LOCK_SH)) {
					$content.="<poll>\n";
					$content.=fread($fp, filesize($theFile));
					$content.="</poll>\n";
					flock($fp, LOCK_UN);
				}
				@fclose($fp);
			}
		}
		closedir($dh);
	}
	$content.="</polls>";
}
// must add this for IE7 to work on SSL download
header('Pragma: private');
header('Cache-control: private, must-revalidate');	
header("Content-Type: text/xml");

echo $gXMLHeader;
echo $content;

?>
