<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vlicense.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vbrand.php");

$providerId=GetSessionValue('provider_id');

$date=date('Y-m-d');
$time=date('H:i:s');
$tz=date('T');

list($yy, $mm, $dd)=explode("-", $date);

$ts=mktime(0, 0, 0, $mm, $dd, $yy);
$h24=60*60*24; // 24 hours

$dateOptions='';
for ($i=0; $i<100; $i++) {
	$theDay=date("Y-m-d", $ts);
	$dateOptions.="<option value='$theDay'>$theDay</option>\n";
	$ts-=$h24;
}

$fromDateOptions="<select name='from_date'>$dateOptions</select>\n";
$toDateOptions="<select name='to_date'>$dateOptions</select>\n";

$query="`provider_id` = '$providerId'";
$prepend="<option value=\"\">All sites</option>\n";
$brands=VObject::GetFormOptions(TB_BRAND, $query, "brand_id", "site_url", '', $prepend,
	"", "", "*", "site_url", false, 70);

/*
$timeOptions="<select name=\"time\">";
for ($i=0; $i<24; $i++) {

	if ($i<10)
		$val="0".(string)$i.":00";
	else
		$val=(string)$i.":00";

	$timeOptions.="<option value=\"$val:00\">$val</option>";
}

$timeOptions.="</select>";
*/

/*
$postUrl="provider_report.php?page=REPORT_SESSION";
if (SID!='')
	$exportUrl.="&".SID;
*/
$today=date('Y-m-d T');

$postUrl=VM_API;

$reportName='report'.mt_rand();
?>

<div class='heading1'>Meeting Sessions</div>

Meeting session report shows meeting sessions that have been conducted by members of 
the Web conferencing sites under this Provider Account.

<p>
Today is <strong><?php echo $today?></strong>

<p>
<form method='POST' action='<?php echo $postUrl?>'>
<input type='hidden' name='provider_id' value='<?php echo $providerId?>'>
<input type='hidden' name='file_name' value='<?php echo $reportName?>'>
<input type='hidden' name='format' value='csv'>
<input type='hidden' name='cmd' value='GET_SESSION_INFO'>
<div class='m_val'><b>Report Period From:</b> <?php echo $fromDateOptions?> &nbsp;<b>To:</b> <?php echo $toDateOptions?></div>
<div class='m_val'><b>Web site</b>: <?php echo $brands?></d>
<div class='m_val'><input type='submit' name='export' value='Download Report'>
<span class='m_caption'>Download the report in the CSV format</span></div>
</form>


