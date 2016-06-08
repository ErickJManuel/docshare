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

require_once("dbobjects/vlicense.php");
require_once("dbobjects/vsession.php");

GetArg('from_time', $fromTime);
GetArg('count', $count);
GetArg('increment', $increment);
GetArg('format', $format);
GetArg('group_id', $groupId);
GetArg('port_type', $portType);
GetArg("file_name", $fileName);
GetArg('brand', $brandName);
GetArg('provider_id', $providerId);
GetArg('brand_id', $brandId);
GetArg('time_zone', $tz);
if ($tz=='')
	$tz="+00:00";
else if ($tz[0]==' ')
	$tz[0]='+';

if ($fromTime=='' || $count=='' || $increment=='' || $increment=='0')
	return API_EXIT(API_ERR, "Missing input parameters.");	
	
if ($providerId=='' && $brandName=='') {
	return API_EXIT(API_ERR, "Missing input prameter.");	
}

if ($brandName!='') {
	VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
	if (!isset($brandInfo['id']))
		return API_EXIT(API_ERR, "Brand '$brandName' does not match our records");	

	$brandId=$brandInfo['id'];
}

$memberBrand=GetSessionValue('member_brand');
//$memberPerm=GetSessionValue('member_perm');
//$memberId=GetSessionValue('member_id');

// if provider_id is given, we need to log in as a provider
if ($providerId!='') {
	$pid=GetSessionValue('provider_id');
	if ($pid=='' || $pid!=$providerId)
		return API_EXIT(API_ERR, "Not authorized.");
}

// to get info about the brand, we need to log in as a brander user or as a provider

if ($brandId!='' && $providerId=='') {
	if ($memberBrand!=$brandId)
		return API_EXIT(API_ERR, "Not authorized.");
}

$bquery='1';
if ($providerId!='')
	$bquery.=" AND (provider_id='".$providerId."')";

if ($brandId!='') {
	$bquery.=" AND (id = '$brandId')";
}

$errMsg=VObject::SelectAll(TB_BRAND, $bquery, $brandResults);
if ($errMsg!='') {
	return API_EXIT(API_ERR, $errMsg);
}

$brandQuery='';
while ($abrandInfo = mysql_fetch_array($brandResults, MYSQL_ASSOC)) {
	if ($brandQuery!='')
		$brandQuery.=" OR ";
	$bid=$abrandInfo['id'];
	$brandQuery.="(brand_id='$bid')";
}

	
$fromDate=date('Y-m-d', $fromTime);
//$tz=date('T');

$plot_data=array();
			
$second=0;
$sec_inc=$increment*60;

$startDateTime=$fromDate." 00:00";

$theTime=$fromDate." 00:00:00";
$theDate=$fromDate;

$stime=$fromTime;
$gmax=0;
$xtick=6;

$sessionList=array();
$licenseList=array();

for ($i=0; $i<$count; $i++) {
	$second+=$sec_inc;
	$startTime=$theTime;
	$eSecond=$second;
	if ($eSecond>=86400)
		$eSecond=86399;	// The end time cannot be 24:00:00. It needs to be no more than 23:59:59
	$endTime=$theDate." ".SecToStr($eSecond);
	
	// find all attendess of the brand started within the time period
//	$query="(start_time>='$startTime') AND (start_time<='$endTime')";
	$query="(start_time>=CONVERT_TZ('$startTime', '$tz', 'SYSTEM')) AND (start_time<=CONVERT_TZ('$endTime', '$tz', 'SYSTEM'))";
	if ($brandQuery!='')
		$query.=" AND ($brandQuery)";
			
	$errMsg=VObject::SelectAll(TB_ATTENDEE, $query, $result);
	
	if ($errMsg!='') {
		return API_EXIT(API_ERR, $errMsg);	
	}

	$num_rows = mysql_num_rows($result);
	$rowCount=0;
	
	$max_std=0;
	$max_av=0;
	$max_total=0;
	$minInterval=30;
	$lastTime=0;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$attTime=$row['start_time'];

		$thisTime=@strtotime($attTime);
		
		$rowCount++;
		// skip if this attendee's start time is too close to the previous attendee's
		// and the the attendee is not the one in the time period.
		// We always want the check the last one's
		if (($thisTime-$lastTime)<$minInterval && $rowCount<$num_rows) {
			continue;
		}
		
		$lastTime=$thisTime;
		
		if ($groupId!='') {
			$session=new VSession($row['session_id']);
			$session->GetValue('host_login', $hostLogin);
//			$qy="brand_id='".$brandId."' AND login='".$hostLogin."'";
			$qy="brand_id='".$brandId."' AND LOWER(login)='".addslashes(strtolower($hostLogin))."'";
			$errMsg=VObject::Select(TB_USER, $qy, $hostInfo);			

//			VObject::Find(TB_USER, 'login', $hostLogin, $hostInfo);
			if ($hostInfo['group_id']!=$groupId)
				continue;
			
//			$session->GetValue('group_id', $sessGrpId);		
//			if ($sessGrpId!=$groupId)
//				continue;
		}
		
		$hasVideo='';
		if ($portType!='ALL') {
			if (!array_key_exists($row['session_id'], $sessionList)) {		
				$session=new VSession($row['session_id']);
				$session->GetValue('license_code', $licCode);
				$sessionList[$row['session_id']]=$licCode;
			} else {
				$licCode=$sessionList[$row['session_id']];
			}
			
			if (!array_key_exists($licCode, $licenseList)) {
				VObject::Find(TB_LICENSE, 'code', $licCode, $licInfo);
				$hasVideo=$licInfo['video_conf'];
				$licenseList[$licCode]=$hasVideo;
			} else {
				$hasVideo=$licenseList[$licCode];			
			}
		}

		// find all concurrent attendees when this attendee started
		$aquery="start_time<='$attTime' AND mod_time>='$attTime'";
		if ($brandQuery!='')
			$aquery.=" AND ($brandQuery)";
		VObject::Count(TB_ATTENDEE, $aquery, $total);	
		
		if ($total>$max_total)
			$max_total=$total;
		if ($hasVideo=='Y' && $total>$max_av)
			$max_av=$total;
		if ($hasVideo=='N' && $total>$max_std)
			$max_std=$total;
	}
	
	if ($max_total>$gmax)
		$gmax=$max_total;
		
	$theTime=$endTime;
	if ($second>=86400) {
		$stime+=86400;
		$theDate=date('Y-m-d', $stime);
		$second=0;
		$theTime=$theDate." 00:00:00";
	}
	
	if ($format=='csv')
		$label=$startTime;
	else {
		if (($i % $xtick)==0) {
			list($ds, $ts)=explode(" ", $startTime);
			$hh=substr($ts, 0, 5);
			$label=$hh;
			if ($hh=='00:00')
				$label.="\n".$theDate;
		} else
			$label='';
	}
	
	if ($portType=='ALL')
		$plot_data[]=array($label, $max_total);
	elseif ($portType=='PTS')
		$plot_data[]=array($label, $max_std);
	elseif ($portType=='PTV')
		$plot_data[]=array($label, $max_av);
	

}

$endDateTime=substr($endTime, 0, strlen($endTime)-3);

if ($format=='gif' || $format=='png' || $format=='jpg') {
include("phplot/phplot.php");

	GetArg('width', $width);
	if ($width=='')
		$width=680;
	GetArg('height', $height);
	if ($height=='')
		$height=400;
	
	$graph =& new PHPlot($width, $height);
	$graph->SetDataType("text-data");
//	$graph->SetDataType("linear-linear-error");  //Must be first thing
	$graph->SetDataValues($plot_data);
//	$graph->SetYTickIncrement(2);
	$vtick=ceil($gmax/40);
	if ($vtick<1)
		$vtick=1;
	$graph->SetVertTickIncrement($vtick);
	$graph->SetHorizTickIncrement($xtick);
//	$graph->SetYScaleType("log");
	$graph->SetXGridLabelType("title");
	
	$graph->SetXLabel($startDateTime." to ".$endDateTime." UTC ".$tz);
	$graph->SetYLabel("Concurrent ports");
	$graph->SetTitle("Concurrent Port Usage Report");
//	$graph->SetNewPlotAreaPixels(70,10,375,100);  // where do we want the graph to go
//	$graph->SetPlotAreaWorld(0,0,7,80);
//	$graph->SetPlotType("bars");
//	$graph->SetIsInline(true);
	
	$graph->SetDataColors(
		array("blue"),  //Data Colors
		array("black")				//Border Colors
	);  

	$graph->SetPlotAreaWorld(0,0,$count,$gmax+5);
	$graph->SetFileFormat($format);
//	$graph->SetMarginsPixels(5, 5, 5, 5);

	//Draw it
	$graph->DrawGraph();

} elseif ($format=='csv') {
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	
	if ($fileName!='') {
		header("Content-Type: text/csv; charset=us-ascii; name=\"$fileName.csv\"");	
		header("Content-Disposition: attachment; filename=$fileName.csv");
	} else {
		header("Content-Type: text/csv");
	}
	echo "#Title: Concurrent port usage report\n\n";
	echo "#Period: $startDateTime to $endDateTime\n";
	echo "#Timezone: UTC $tz\n";
	if ($portType=='ALL')
		echo "#Port type: All ports\n";
	elseif ($portType=='PTS')
		echo "#Port type: Standard ports\n";
	elseif ($portType=='PTV')
		echo "#Port type: Video ports\n";
	echo "#Interval: $increment minutes\n";
	
	if ($groupId!='') {
		$group=new VGroup($groupId);
		$group->GetValue("name", $groupName);
		echo "#Group: $groupName\n";
	}
		
	echo "\n";
	echo ",time,ports\n";
	
	foreach ($plot_data as $k => $v) {
		$ks=(integer)$k+1;
		echo $ks;
		foreach ($v as $av)
			echo ",$av";
		echo "\n";
	}
	echo "\n";
	
}

API_EXIT(API_NOMSG);


?>