
<div class='box1_top'>&nbsp;</div>
<div class='box1_mid'>
<div id='r-text'>

<?php

require_once("rest/prestapi.php");
require_once("dbobjects/vhook.php");

$apiTopics=array(
	$gText['M_API_DOC'],
	$gText['REST_OBJECTS'],
	$gText['REST_ERRORS'],
	$gText['REST_HOOKS'],
	);


$target=$GLOBALS['TARGET'];

$apiPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST;
if (SID!='')
	$apiPage.="&".SID;

foreach ($apiTopics as $topic) {
		
	if ($topic==$gText['M_API_DOC']) {
		echo "<div class='right_hd1'><a target=$target href='$apiPage'>$topic</a></div>\n";
	} else {
		echo "<div class='right_hd1'><a target=$target href='$apiPage&topic=$topic'>$topic</a></div>\n";
	}
		
	if ($topic==$gText['REST_OBJECTS']) {
		
		$refPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&topic=".$gText['REST_OBJECTS'];
		if (SID!='')
			$refPage.="&".SID;
			
		PRestAPI::GetObjectNames($objects);

		echo "<ul>\n";

		foreach ($objects as $obj) {
			$thePage=$refPage."&sub1=$obj";
			echo "<li><a target='$target' href='$thePage'>$obj</a></li>\n";
		}
		echo "</ul>\n";		
		
	} elseif ($topic==$gText['REST_HOOKS']) {
		$refPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&topic=".$gText['REST_HOOKS'];
		if (SID!='')
			$refPage.="&".SID;
		
		echo "<ul>\n";

		foreach ($s_hook_info as $key => $val) {
			$thePage=$refPage."&sub1=$key";
			echo "<li><a target='$target' href='$thePage'>$key</a></li>\n";
		}
		echo "</ul>\n";		
		
	} else {
	}
}


?>
<br>
</div>
</div>
<div class='box1_bottom'>&nbsp;</div>

