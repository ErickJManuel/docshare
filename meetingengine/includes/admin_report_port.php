<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


$redirect_url=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
if (SID!='')
	$redirect_url.="&".SID;

GetSessionTimeZone($tzName, $tz);

?>

<script type="text/javascript">
<!--
document.getElementById('loader').style.display='inline';
document.getElementById('return_link').href ='<?php echo $redirect_url?>';

function HideLoader() {

	var elem=document.getElementById('loader');
	if (elem) {
		elem.style.display='none';
	}
	return true;
}

//-->

</script>

<?php

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

GetArg('period', $period);
GetArg('group_id', $groupId);
GetArg('port_type', $portType);

if ($period=='')
	$period=-3;
if ($portType=='')
	$portType='ALL';
/*
$postPage=$_SERVER['PHP_SELF']."?page=".PG_ADMIN_REPORT."&type=port";
$postPage.="&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$postPage.="&".SID;
*/
$postPage=$GLOBALS['BRAND_URL'];
	
$periodOpts='';
foreach ($periods as $k => $v) {
	if ($period==$k)
		$periodOpts.="<option value='$k' selected>$v</option>\n";
	else
		$periodOpts.="<option value='$k'>$v</option>\n";
}

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


$portOpts='';
/*
// the port query doesn't support type anymore
foreach ($portList as $k => $v) {
	if ($portType==$k)
		$portOpts.="<option value='$k' selected>$v</option>\n";
	else
		$portOpts.="<option value='$k'>$v</option>\n";
}
*/

$brandId=$GLOBALS['BRAND_ID'];
$query="`brand_id` = '$brandId'";
$prepend="<option value=\"\">".$gText['M_ALL_GROUPS']."</option>\n";
//$groupOpts=VObject::GetFormOptions(TB_GROUP, $query, "group_id", "name", $groupId, $prepend);

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
	$endHour=(integer)date('H');
	$endHour+=1;
	if ($endHour>24)
		$endHour=24;

	$nday=8;
	$fromTime=$nowTime-86400*$nday;
	$hinc=4; // 4 hour increment

} elseif ($period==-30) {
	$endHour=(integer)date('H');
	$endHour+=1;
	if ($endHour>24)
		$endHour=24;

	$nday=31;
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

$format='';
if (!function_exists('imagetypes')) {
	ShowError("GD library not enabled in PHP.");
	return;
}

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

$count=ceil((24*$nday+$endHour)/$hinc); 
$increment=60*$hinc;

// export hourly reports
$expCount=ceil((24*$nday+$endHour)); 
$expIncrement=60;


//$apiUrl=SERVER_URL.VM_API."?cmd=GET_PORT_INFO";
$apiUrl=VM_API."?cmd=GET_PORT_INFO";
$apiUrl.="&from_time=$fromTime&port_type=$portType&brand="
	.$GLOBALS['BRAND_NAME']."&group_id=$groupId";

$apiUrl.="&time_zone=$tz";

if (SID!='')
	$apiUrl.="&".SID;
	
$downloadUrl=$apiUrl."&format=csv&file_name=report&count=$expCount&increment=$expIncrement";

$timezoneText=_Text("Time zone");
echo "<div>$timezoneText: $tzName</div>\n";

?>

<form target="<?php echo $GLOBALS['TARGET']?>" method="GET" action="<?php echo $postPage?>" name="report_form">

<?php
if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
?>
<input type="hidden" name="page" value="ADMIN_REPORT">
<input type="hidden" name="type" value="port">
<table class='report_bar'>
<tr>
<td class='report_left'>Show: 
<select name='period'>
<?php echo $periodOpts?>
</select>&nbsp;

<?php 
/*
<select name='port_type'>
<?php echo $portOpts ?>
</select>

<?php echo $groupOpts?>
*/ 
?>

<input type="submit" name="submit" value=" <?php echo $gText['M_GO']?> "></td>
<td class='report_right'><a href='<?php echo $downloadUrl?>'><img src="<?php echo $exportIcon?>"><?php echo $gText['M_DOWNLOAD']?></a></td>
</tr>
</table>
</form>

<p>

<!--
<table id="loader2" width=100% height=300px style="z-index:1; position: absolute">
<tr>
<td width=30% height=100%>&nbsp;</td><td class="wait_icon">&nbsp;</td><td>Loading...</td>
</tr>
</table>
-->

<!--
<table style="width:100%; height:100%; text-align: center; vertical-align:middle;">
<tr><td>
<img style='vertical-align:middle' src="themes/loading.gif" height="32" width="32" alt=""/> <strong>Loading ...</strong>
</td></tr>
</table>
</div>
-->


<!-- <img src="phplot/examples/inline_image.php?which_format=png&which_title=YES_PNG_IS_ENABLED" /> -->
<?php

//$url=SERVER_URL.$apiUrl."&format=$format&count=$count&increment=$increment";
//die($url);

	if ($format=='gif' || $format=='png' || $format=='jpg')
		echo "<div><img onload='return HideLoader();' alt='Usage graph' src=\"$apiUrl&format=$format&count=$count&increment=$increment\"/></div>\n";
	else {
		$content=file_get_contents($apiUrl."&format=csv");
		echo "<pre>$content</pre>";
	}

?>
<?php
/*
$graph =& new PHPlot(400, 250);
$graph->SetPrintImage(0);
$graph->SetDataType("text-data");
$graph->SetDataValues($plot_data);
$graph->SetYTickIncrement(2);

$graph->SetXTitle("Time");
$graph->SetYTitle("Concurrent ports");
//$graph->SetPlotType("bars");
$graph->SetNewPlotAreaPixels(70,10,375,100);  // where do we want the graph to go
$graph->SetPlotAreaWorld(0,0,7,80);
$graph->DrawGraph();

$graph->SetIsInline(true);

//Print the image
$graph->PrintImage();
*/
//print_r ($plot_data);
?>

