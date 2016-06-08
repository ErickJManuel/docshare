<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


$thisYear=(int)date('Y');
$thisMonth=(int)date('n');
$today=date('Y-m-d T');
$exportIcon="themes/export.gif";
$exportUrl='';


?>

<div class='heading1'>User Licenses</div>

User-license report shows the numbers of named-user licenses that have been issued by each Web conferencing site under this Provider Account.

<table class='report_bar'>
<tr>
<td style='padding: 10px 0 0 0'>Today is <strong><?php echo $today?></strong></td>
<!-- <td class='report_right'><a href='<?php echo $exportUrl?>'><img src="<?php echo $exportIcon?>">Export Report</a></td> -->
</tr>
</table>


<?php
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vbrand.php");

$providerId=GetSessionValue('provider_id');

// get all licenses
$licQuery="code <> 'PTV' AND code <>'PTS'";
$errMsg=VObject::SelectAll(TB_LICENSE, $licQuery, $results);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}
$licCodes=array();
$licIds=array();
$licNames=array();

$tableHeader='';
$i=0;

while ($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
	$tableHeader.="<th class=\"pipe\">${row['name']}</th>\n";
	$licCodes[$i]=$row['code'];
	$licIds[$i]=$row['id'];
	$licNames[$i]=$row['name'];
	$i++;

}
$num_licenses = $i;


// get all brands under this account
$query="provider_id='".$providerId."'";

$errMsg=VObject::SelectAll(TB_BRAND, $query, $brandResults);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

// construct a query that search all brands of this account
$brandQuery='';
while ($row = mysql_fetch_array($brandResults, MYSQL_ASSOC)) {
	if ($brandQuery!='')
		$brandQuery.=" OR ";
	$brandQuery.="brand_id ='".$row['id']."'";
}

$errMsg=VObject::SelectAll(TB_BRAND, $query, $brandResults);

// for each brand
$num_brands = mysql_num_rows($brandResults);
while ($brandInfo = mysql_fetch_array($brandResults, MYSQL_ASSOC)) {
	
	$url=$brandInfo['site_url'];
	$bquery="brand_id = '".$brandInfo['id']."'";
	print <<<END
	<div class='heading2'><a href='$url'>$url</a></div>
<table cellspacing="0" class="meeting_list" >

<tr>
	<th class='pipe'>Month</th>
	$tableHeader
</tr>
END;
	
	$offerings=explode(',', $brandInfo['offerings']);
	
	$num_months=6;
	// for each month
	for ($month=0; $month<$num_months; $month++) {
		
		$theMonNum=$thisMonth-$month;		
		if ($theMonNum<1) {
			$theMonNum+=12;
			$theYear=$thisYear-1;	
		} else {
			$theYear=$thisYear;
		}
		if ($theMonNum<10)
			$theMonth="0".(string)$theMonNum;
		else
			$theMonth=(string)$theMonNum;	
		
		$monStr=$theYear."-".$theMonth;
		
		echo "<tr><td class='u_item_c'>$monStr</td>\n";		
		// for each license type
		for ($lic=0; $lic<$num_licenses; $lic++) {
			$licCode=$licCodes[$lic];
			$licId=$licIds[$lic];
			$total=0;
			
			if ($brandQuery!='') {
				$userQuery="($bquery) AND (license_id='$licId')";
				$userQuery.=" AND (YEAR(create_date)='$theYear' AND MONTH(create_date)='$theMonth')";
				VObject::Count(TB_USER, $userQuery, $total);
			}
			
			$totalStr=(string)$total;
			
			echo "<td class='u_item_c'>$totalStr</td>\n";
		}
		echo "</tr>\n";	
	}
	
	echo "<tr><td class='u_item_c u_item_b''>Total</td>\n";		
	// for each license type
	for ($lic=0; $lic<$num_licenses; $lic++) {
		$licCode=$licCodes[$lic];
		$licId=$licIds[$lic];
		$total=0;
		if ($brandQuery!='') {
			$userQuery="($bquery) AND (license_id='$licId')";
			VObject::Count(TB_USER, $userQuery, $total);
		}
		
		$totalStr=(string)$total;
		
		echo "<td class='u_item_c u_item_b'>$totalStr</td>\n";
	}	
	echo "</tr>\n";	
	
	echo "</table>\n";
	
	
}


?>