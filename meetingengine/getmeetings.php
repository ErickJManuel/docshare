<<?php
require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vbrand.php");

$providerId='36';
$query="provider_id='$providerId'";

echo "site url,meeting id,date time,host id,login\n";

VObject::SelectAll(TB_BRAND, $query, $result);
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	$brandId=$row['id'];
	$siteUrl=$row['site_url'];
	$query2="scheduled='Y' AND date_time>='2010-10-03' AND brand_id='$brandId'";
	
	VObject::SelectAll(TB_MEETING, $query2, $result2);
	
	while ($row2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
		$meetingId=$row2['access_id'];
		$title=$row2['title'];
		$dateTime=$row2['date_time'];
		$host=new VUser($row2['host_id']);
		$host->Get($hostInfo);
		$hostId=$hostInfo['access_id'];
		$hostLogin=$hostInfo['login'];
		
		echo "$siteUrl,$meetingId,$dateTime,$hostId,$hostLogin\n";
	}
}


?>