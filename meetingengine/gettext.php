<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


require_once("includes/common_lib.php");

StartSession();

// need to sign in to console.php first to run the script
if (GetSessionValue('root_login')=='')
	die("Not signed in");
	
// the output locale name
// if the locale file exists, the processing will merge the new strings with the existing ones
if (isset($_GET['output']))
	$outputName=$_GET['output'];
else
	$outputName="en";
	
$localeDir="locales/";
$outputFile=$localeDir.$outputName.".php";
$outputTmpFile=$localeDir."_".$outputName.".php.tmp";
$backupFile=$localeDir."_".$outputName.".php.bak";	// prefix with _ so the file won't be listed in the UI

// list files or directories to be excluded from processing
$excludedFiles=array(
	"scripts", "scripts_source", "notes", "temp", "locales", "gettext.php", "themes", "console", "provider", "rest_doc", "rest", "examples", "hooks",
	"language", "load_test", "phplot", "ppt", "api_includes", "dbobjects", "download", "sso_doc", "vinstall", "vlocale", "converter"
	);
	
// write results to file
$outFp=@fopen($outputTmpFile, "wb");
if (!$outFp)
	die("Couldn't open $outputTmpFile. Make sure the directory $localeDir is writable.");

$gTranslatorName="name";
$gTranslatorEmail="email";
$gTranslationDate="date";

// if the existing locale file exists, include that so we can keep the current translated text
if (file_exists($outputFile)) {
	include_once($outputFile);
}

$gttext=array();
$gtsource=array();
$gtcomments=array();
echo "<pre>\n";
ProcDir(".");
echo "</pre>\n";


$thisFile=basename(__FILE__);
$datestr=date("Y F d H:i:s");

$header=
"/**
 * @package     Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 *
 * Created by $thisFile $datestr
 *
 */
";

fwrite($outFp, "<?php\r\n");
fwrite($outFp, $header."\r\n");
fwrite($outFp, "// Replace the right side of the text below between the double quotes with the translator's information:\r\n");
fwrite($outFp, "\$gTranslatorName=\"$gTranslatorName\";\r\n");
fwrite($outFp, "\$gTranslatorEmail=\"$gTranslatorEmail\";\r\n");
fwrite($outFp, "\$gTranslationDate=\"$gTranslationDate\";\r\n");
fwrite($outFp, "\r\n");
fwrite($outFp, "// Replace the right side of the text below between the double quotes with your translated text.\r\n");
fwrite($outFp, "// e.g. \$gltext['5a95a425f74314a96f13a2f136992178']=\"your translated text here\";\r\n");
fwrite($outFp, "// If you must use double qoutes in the text, add a backslash before each double quote.\r\n");
fwrite($outFp, "// Do not change anything else.\r\n");
fwrite($outFp, "\$gltext=array();\r\n\r\n");

foreach ($gttext as $key => $text) {	
	if (isset($gltext[$key])) {
		// the text exists in the current file
		// keep the existing text
		$currText=$gltext[$key];
		$currText=str_replace('"', '\"', $currText);
		fwrite($outFp, "\$gltext['$key']=\"$currText\";\r\n");
		fwrite($outFp, "//_State: Existing\r\n");
	} else {
		fwrite($outFp, "\$gltext['$key']=\"$text\";\r\n");
		fwrite($outFp, "//_State: New\r\n");
	}
	fwrite($outFp, "//_Orginal: $text\r\n");
	if (isset($gtsource[$key]))
		fwrite($outFp, $gtsource[$key]);
	if (isset($gtcomments[$key]))
		fwrite($outFp, $gtcomments[$key]);
	
	fwrite($outFp, "\r\n");		
}
fwrite($outFp, "?>\r\n");
fclose($outFp);
chmod($outputTmpFile, 0777);

if (file_exists($outputFile))
	@rename($outputFile, $backupFile);
@rename($outputTmpFile, $outputFile);

function ProcFile($file) {
	echo("Processing $file\n");
	
	$fp = fopen ($file, "r");
	$lineNum=0;
	while (!feof ($fp)) { 
		$line = fgets($fp, 4096);
		$lineNum++;
		ProcLine($line, $file, $lineNum);
	} 
	fclose ($fp);
}

function ProcDir($dir) {
	global $excludedFiles;
	
	if ($dh = @opendir($dir)) {
		while (($filename = @readdir($dh)) !== false) {
			if ($filename!="." && $filename!="..")
			{
				$skip=false;
				foreach ($excludedFiles as $item) {
					if ($filename==$item) {
						$skip=true;
						break;
					}
						
				}
				if ($skip)
					continue;
														
				$theItem=$dir."/".$filename;
				if (is_file($theItem)) {
					
					if (strpos($filename, ".php")!==false)
						ProcFile($theItem);
					
				} else if (is_dir($theItem))
					ProcDir($theItem);
			}
		}
		closedir($dh);
	}
}



// find an instance of Text("...") or Text('...') in $line
// returns a key, which is md5('...') and '...'
// Text("...") has to be in the same line
// return true if an instance is found
function ProcLine($line, $file, $lineNum)
{
	global $gttext, $gtsource, $gtcomments;
	
	$func="_Text(";
	
	$quote="'";
	$endQuote="')";
	$pos1=strpos($line, $func.$quote);
	if ($pos1===false) {
		$quote="\"";	
		$endQuote="\")";
		$pos1=strpos($line, $func.$quote);
	}
	
	if ($pos1===false)
		return false;

	$pos1+=strlen($func)+1;
	
	$pos2=strpos($line, $endQuote, $pos1);
	if ($pos2===false)
		return false;
	
	$text=substr($line, $pos1, $pos2-$pos1);
		
	// look for optional comments for the string. the comments should start with _Comment:
	$key=md5($text);
	
	$commentKey="_Comment:";
	$commentText="";
	$pos3=strpos($line, $commentKey, $pos2);
	if ($pos3!==false) {
		$commentText="//".substr($line, $pos3);
	}	
	
	$sourceText="//_Source: ".$file." line: ".$lineNum."\r\n";

	// the text string exists already; 
	if (!isset($gttext[$key])) {		
		$gttext[$key]=$text;
	}
	
	// if text string exists already, append the source line numbers or comments
	if (!isset($gtsource[$key])) {		
		$gtsource[$key]=$sourceText;
	} else {
		$gtsource[$key].=$sourceText;		
	}
	if ($commentText!='') {
		if (!isset($gtcomments[$key])) {	
			$gtcomments[$key]=$commentText;
		} else {
			$gtcomments[$key].=$commentText;		
		}
	}
	return true;
}
?>