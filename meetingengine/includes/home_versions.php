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


$query="1";
GetArg("version_number", $versionNumber);
$versionOpts=GetVersionOptions($versionNumber);
$brandId=$GLOBALS['BRAND_ID'];

$versDate='';
$versionInfo=array();
if ($versionNumber!='') {
	VObject::Select(TB_VERSION, "number='$versionNumber' AND (brand_id='$brandId' OR brand_id='0')", $versionInfo);
	if (isset($versionInfo['date'])) {
		$versDateTime=$versionInfo['date'];
		list($versDate, $versTime)=explode(" ", $versDateTime);
	}
}

function GetVersionOptions(&$versionNumber)
{
	$brandId=$GLOBALS['BRAND_ID'];	

//	$query="((brand_id='$brandId' OR brand_id='0') AND (number>='$minNum')";
	$query="((brand_id='$brandId' OR brand_id='0')";
	if ($GLOBALS['SITE_LEVEL']=='')
		$query.=" AND (type='final')";
	elseif ($GLOBALS['SITE_LEVEL']=='beta')
		$query.=" AND (type='final' OR type='beta')";

	if ($versionNumber!='') {
		$query.=") OR (number='$versionNumber')";			
	} else
		$query.=")";
		
	$errMsg=VObject::SelectAll(TB_VERSION, $query, $result, 0, 0, '*', 'number', true);
	if ($errMsg!='') {
		return '';
	}
	$str="<select name=\"version_number\">\n";

	$num_rows = mysql_num_rows($result);
	$index=0;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		
		// if versionNumber is not set, set it to the highest version
		if ($versionNumber=='' && $index==0)
			$versionNumber=$row['number'];
		
		if ($row['type']!='final')
			$versionStr=$row['number']." ".$row['type'];
		else
			$versionStr=$row['number'];
		
		if ($versionNumber==$row['number'])
			$str.="<option value=\"".$row['number']."\" selected>".$versionStr."</option>\n";
		else 
			$str.="<option value=\"".$row['number']."\">".$versionStr."</option>\n";
			
		$index++;
	}
	$str.="</select>\n";
	return $str;	
}

function ShowNotes($title, $notes) {
	if (!isset($notes) || count($notes)==0)
		return;
	
	echo "<h3>$title</h3>\n";
	
	echo "<ul>\n";
	foreach ($notes as $item) {
		echo "<li>$item</li>\n";				
	}
	echo "</ul>\n";
}


function ShowLogs($title, $notes) {
	if (!isset($notes) || count($notes)==0)
		return;
		
	echo "<h3>$title</h3>\n";
	
	foreach ($notes as $k => $arr) {
		echo $k."\n";		
		$count=count($arr);
		echo "<ul>\n";
		for ($i=0; $i<$count; $i+=2) {
			$desc=$arr[$i];
			$files=$arr[$i+1];
			echo "<li>$desc</li>\n";
			$fileItems=explode(",", $files);
			echo "<ul>\n";
			foreach ($fileItems as $aFile) {
				echo "<li>$aFile</li>\n";				
			}
			echo "</ul>\n";
		}
		echo "</ul>\n";
	}
}
?>

<div class='heading1'><?php echo _Text("Release Notes")?></div>

<form method='POST' action="" name='version_form'>

<div><?php echo _Text("Select a version")?>:&nbsp;
<?php echo $versionOpts?>
<input type="submit" name="submit" value="Select">
</div>
</form>
<p><?php echo $versDate?></p>

<?php 

GetArg('show_log', $showLog);
$verText=str_replace(".", "_", $versionNumber);
$dir="notes/";
if (isset($versionInfo['source_url']) && $versionInfo['source_url']!='') {
	$file=$versionInfo['source_url'].$dir.$verText.".php";
	$data=@file_get_contents($file);
	if ($data)
		echo $data;
	else
		echo _Text("Not available");
} else {
	$file=$dir.$verText.".php";
	include($file);
}

/*
if (file_exists($file)) {
	include_once($file);
	if (isset($gFeatures))
		ShowNotes(_Text("New features"), $gFeatures);	
	if (isset($gBugs))
		ShowNotes(_Text("Bugs fixed"), $gBugs);
	if (isset($gLogs) && $showLog)
		ShowLogs(_Text("Change Logs"), $gLogs);

} else {
	echo _Text("Not available");
}

if (isset($gLogs)) {
	if (!$showLog) {
		$theText=_Text("Show change logs");
		$target=$GLOBALS['TARGET'];
		$url=$GLOBALS['BRAND_URL']."?page=".PG_HOME_VERSIONS."&version_number=".$versionNumber."&show_log=1";
print <<<END
<div><a target='$target' href='$url'>$theText</a></div>
END;
	
	} else {
		$theText=_Text("Hide change logs");
		$target=$GLOBALS['TARGET'];
		$url=$GLOBALS['BRAND_URL']."?page=".PG_HOME_VERSIONS."&version_number=".$versionNumber;
print <<<END
<div><a target='$target' href='$url'>$theText</a></div>
END;
	}
	
}
*/	
?>