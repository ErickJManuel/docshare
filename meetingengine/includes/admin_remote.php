<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vremoteserver.php");

$thisPage=$_SERVER['PHP_SELF'];
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">Delete";
$addIcon="themes/add.gif";
//$addUrl=$thisPage."?page=".PG_ADMIN_ADD_REMOTE."&brand=".$GLOBALS['BRAND_NAME'];
$addUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_REMOTE;
if (SID!='')
	$addUrl.="&".SID;
$addBtn="<a target='$target' href=\"$addUrl\"><img src=\"$addIcon\"> ${gText['M_ADD_HOSTING']}</a>";
//$editUrl=$thisPage."?page=".PG_ADMIN_EDIT_REMOTE."&brand=".$GLOBALS['BRAND_NAME'];
$editUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_REMOTE;
if (SID!='')
	$editUrl.="&".SID;
$retPage=$thisPage."?page=".PG_ADMIN_HOSTING."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
$deleteUrl=VM_API."?cmd=DELETE_REMOTE&return=".$retPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUrl.="&".SID;

?>

<div class="list_tools"><?php echo $addBtn?></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">ID</th>
    <th class="pipe">Name</th>
    <th class="pipe">URL</th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

// allow to use servers available to all brands
//$query="brand_id ='".$GLOBALS['BRAND_ID']."'";
$query="brand_id ='".$GLOBALS['BRAND_ID']."' OR brand_id='0'";
	
$errMsg=VObject::SelectAll(TB_REMOTESERVER, $query, $result);
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
		
	$itemUrl=$editUrl."&id=".$row['id'];
	if ($row['brand_id']!='0') {
		echo "<td class=\"u_item u_item_ws\"><a target=$target href=\"$itemUrl\">".$row['id']."</td>\n";
	} else {
		echo "<td class=\"u_item u_item_ws\">".$row['id']."</td>\n";
	}	
//	echo "<td class=\"u_item u_item_ws\">".$row['id']."</td>\n";
	$name=$row['name'];
	if (strlen($name)>50) {
		$name=substr($name, 0, 48);
		$name.="...";
	}

	$name=htmlspecialchars($name);
	if ($row['brand_id']!='0') {
		echo "<td class=\"u_name\"><a target=$target href=\"$itemUrl\">".$name."</td>\n";
	} else {
		echo "<td class=\"u_name\">".$name."</td>\n";
	}
//	echo "<td class=\"u_name\"><a href=\"$itemUrl\">".$name."</a></td>\n";
	$url=$row['server_url'];
	if (strlen($url)>50) {
		$url=substr($url, 0, 48);
		$url.="...";
	}

	echo "<td class=\"u_item\">".$url."</td>\n";
	
	// allow editing only on servers assigned to this brand
	if ($row['brand_id']!='0') {
		$deleteItemUrl=$deleteUrl."&id=".$row['id'];
		echo "<td class=\"m_tool\">";

		$format=$gText['M_CONFIRM_DELETE'];
		$msg=sprintf($format, "\'".addslashes($name)."\'");
		echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteItemUrl\">".$deleteBtn."</a></td>\n";
	} else {
		echo "<td class=\"m_tool\">&nbsp;</td>";
	}
/*
	$deleteItemUrl=$deleteUrl."&id=".$row['id'];
	echo "<td class=\"m_tool\">";

	$format=$gText['M_CONFIRM_DELETE'];
	$msg=sprintf($format, "\'".addslashes($name)."\'");
	echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteItemUrl\">".$deleteBtn."</a></td>\n";
*/
	echo "</tr>\n";
	
	$rowCount++;
}
?>
</table>

