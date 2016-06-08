<?php 

$script='';
if (isset($_GET['s'])) 
{
	$file='';
	if (isset($gScriptDir))
		$file.=$gScriptDir;
	$file.=basename($_GET['s']);
	$file.=".php";
	@include $file;
} else {
	@include_once("version.php");
	echo "OK ".$version;
}	
?>
