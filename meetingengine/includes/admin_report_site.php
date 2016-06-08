<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

$reportIcon="themes/preview.gif";
$reportBtn="<img src=\"$reportIcon\">";

$reportUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_SITE_ATTENDEES;
if (SID!='')
	$reportUrl.="&".SID;

$editWebUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_WEB;
if (SID!='')
	$editWebUrl.="&".SID;

?>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe"><?php echo $gText['MT_URL']?></th>
    <th class="pipe"><?php echo "IP"?></th>
    <th class="tr"><?php echo _Text("Attendees")?></th>
</tr>

<?php

$query="brand_id ='".$GLOBALS['BRAND_ID']."'";
	
$errMsg=VObject::SelectAll(TB_WEBSERVER, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
} 

$num_rows = mysql_num_rows($result);
if ($num_rows==0) {
	echo "<tr>";
	echo "<td colspan=4>&nbsp;</td>";
	echo "</tr>";
}
$rowCount=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if ($rowCount % 2)
		echo "<tr class=\"u_bg\">\n";
	else
		echo "<tr>\n";
	
	$itemUrl=$editWebUrl."&id=".$row['id'];
	if ($row['brand_id']!='0') {
		echo "<td class=\"u_item u_item_ws\"><a target=$target href=\"$itemUrl\">".$row['id']."</td>\n";
	} else {
		echo "<td class=\"u_item u_item_ws\">".$row['id']."</td>\n";
	}
	$name=$row['name'];
	if (strlen($name)>28) {
		$name=substr($name, 0, 25);
		$name.="...";
	}

	$name=htmlspecialchars($name);
	echo "<td class=\"u_name\"><a target='$target' href=\"$itemUrl\">".$name."</a></td>\n";
	$url=$row['url'];
	if (strlen($url)>50) {
		$url=substr($url, 0, 47);
		$url.="...";
	}
	
	echo "<td class=\"u_item\">".$url."</td>\n";

	$urlItems=parse_url($row['url']);
	$ip=gethostbyname($urlItems['host']);

	echo "<td class=\"u_item\">".$ip."</td>\n";	
	$attUrl=$reportUrl."&site_id=".$row['id'];
	echo "<td class=\"u_item_c\"><a target='".$GLOBALS['TARGET']."' href='$attUrl'>".$reportBtn."</a></td>\n";	
	
	echo "</tr>\n";
	
	$rowCount++;
}
?>
</table>
