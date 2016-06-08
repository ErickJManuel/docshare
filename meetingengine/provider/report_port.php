<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

$periods=array(
		"-3" => "Last 3 days",
		"-7" => "Last 7 days",
		"-30" => "Last 30 days",
		);

$portList=array(
		"ALL" => "All ports",
		"PTS" => "Standard ports",
		"PTV" => "Video ports",
	);
	
$exportIcon="themes/export.gif";

$providerId=GetSessionValue('provider_id');

GetArg('period', $period);
GetArg('group_id', $groupId);
//GetArg('port_type', $portType);
GetArg('brand_id', $brandId);

if ($period=='')
	$period=-3;
//if ($portType=='')
//	$portType='ALL';

$postPage=$_SERVER['PHP_SELF']."?page=REPORT_PORT";
if (SID!='')
	$postPage.="&".SID;
	
$periodOpts='';
foreach ($periods as $k => $v) {
	if ($period==$k)
		$periodOpts.="<option value='$k' selected>$v</option>\n";
	else
		$periodOpts.="<option value='$k'>$v</option>\n";
}

$query="`provider_id` = '$providerId'";
$prepend="<option value=\"\">All sites</option>\n";
$brands=VObject::GetFormOptions(TB_BRAND, $query, "brand_id", "site_url", $brandId, $prepend);

$thisYear=(int)date('Y');
$thisMonth=(int)date('n');
$endMonth=$thisMonth;

$startMonth=$endMonth-11;
if ($startMonth<1) {
	$startMonth+=12;
	$startYear=$thisYear-1;	
} else {
	$startYear=$thisYear;
}

for ($i=$endMonth; $i>=1; $i--) {
	if ($i<10)
		$val='0'.(string)$i;
	else
		$val=(string)$i;

	$monStr=$thisYear."-".$val;
		
	if ($monStr==$period)
		$periodOpts.="<option value=\"$monStr\" selected>$monStr</option>\n";
	else
		$periodOpts.="<option value=\"$monStr\">$monStr</option>\n";

}

if ($i!=$endMonth) {
	for ($i=12; $i>=$startMonth; $i--) {
			
		if ($i<10)
			$val='0'.(string)$i;
		else
			$val=(string)$i;

		$monStr=$startYear."-".$val;
			
		if ($monStr==$period)
			$periodOpts.="<option value=\"$monStr\" selected>$monStr</option>\n";
		else
			$periodOpts.="<option value=\"$monStr\">$monStr</option>\n";
	}
}

/*
$portOpts='';
foreach ($portList as $k => $v) {
	if ($portType==$k)
		$portOpts.="<option value='$k' selected>$v</option>\n";
	else
		$portOpts.="<option value='$k'>$v</option>\n";
}
*/
$fromTime=0;
$count=0;
$increment=0;

$nowTime=time();

$hinc=1;
$nday=0;
$endHour=0;
if ($period==-3) {
	$endHour=(integer)date('H');
	$endHour+=1;
	if ($endHour>24)
		$endHour=24;

	$nday=2;
	$fromTime=$nowTime-86400*$nday;
	$hinc=1; // 1 hour increment
} elseif ($period==-7) {
	$nday=7;
	$fromTime=$nowTime-86400*$nday;
	$hinc=4; // 4 hour increment

} elseif ($period==-30) {
	$nday=30;
	$fromTime=$nowTime-86400*$nday;
	$hinc=12; // 12 hour increment

} else {
	$items=explode("-", $period);
	$yy='';
	$mm='';
	if (count($items)>0)
		$yy=$items[0];
	if (count($items)>1)
		$mm=$items[1];
	if ($yy=='' || $mm=='') {
		ShowError("Invalid parameter $period");
		return;
	}
	$fromTime=mktime(0,0,0,$mm,1,$yy);
	if ($mm==1 || $mm==3 || $mm==5 || $mm==7 || $mm==8 || $mm==10 || $mm==12)
		$nday=31;
	elseif ($mm==2)
		$nday=28;
	else
		$nday=30;
	$hinc=12; // 12 hour increment
}

$count=floor((24*$nday+$endHour)/$hinc); 
$increment=60*$hinc;

if (!function_exists('imagetypes')) {
	ShowError("GD library not enabled in PHP.");
	return;
}
$format='';
if ( imagetypes() & IMG_PNG )  
	$format='png';
elseif ( imagetypes() & IMG_GIF )  
	$format='gif';
elseif ( imagetypes() & IMG_JPG )  
	$format='jpg';
else {
	ShowError("Couldn't find a supported image type.");
	return;
}
//$format='csv';
$providerId=GetSessionValue('provider_id');

//$apiUrl=SITE_URL.VM_API."?cmd=GET_PORT_INFO";
$apiUrl=VM_API."?cmd=GET_PORT_INFO";
//$apiUrl.="&from_time=$fromTime&count=$count&increment=$increment&port_type=$portType&provider_id=$providerId";
$apiUrl.="&from_time=$fromTime&count=$count&increment=$increment&provider_id=$providerId";
if ($brandId!='')
	$apiUrl.="&brand_id=$brandId";

$downloadUrl=$apiUrl."&format=csv&file_name=report";

	
?>
<div class='heading1'>Concurrent Ports</div>

Concurrent-port report shows the number of meeting participants on all Web conferencing sites under this Provider Account at any particular moment.

<p>
<form method="POST" action="<?php echo $postPage?>" name="report_form">

<div>Show: 
<select name='period'>
<?php echo $periodOpts?>
</select>&nbsp;
<?php /*
<select name='port_type'>
<?php echo $portOpts?>
</select>
*/ ?>
<?php echo $brands?>
<input type="submit" name="submit" value=" <?php echo $gText['M_GO']?> ">
</div>
<div class='report_right'><a href='<?php echo $downloadUrl?>'><img src="<?php echo $exportIcon?>">Download Report</a></div>

</form>

<p>


<!-- <img src="phplot/examples/inline_image.php?which_format=png&which_title=YES_PNG_IS_ENABLED" /> -->
<?php
	
	if ($format=='gif' || $format=='png' || $format=='jpg')
		echo "<div><img alt='Usage graph' src=\"$apiUrl&format=$format\"/></div>\n";
	else {
		$content=file_get_contents($apiUrl."&format=csv");
		echo "<pre>$content</pre>";
	}
?>



