<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vvideoserver.php");

$thisPage=$_SERVER['PHP_SELF'];
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$addIcon="themes/add.gif";
//$addUrl=$thisPage."?page=".PG_ADMIN_ADD_VIDEO."&brand=".$GLOBALS['BRAND_NAME'];
$addUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_VIDEO;
if (SID!='')
	$addUrl.="&".SID;
$addBtn="<a target='$target' href=\"$addUrl\"><img src=\"$addIcon\"> ${gText['M_ADD_HOSTING']}</a>";
//$editUrl=$thisPage."?page=".PG_ADMIN_EDIT_VIDEO."&brand=".$GLOBALS['BRAND_NAME'];
$editUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_VIDEO;
if (SID!='')
	$editUrl.="&".SID;

$retPage=$thisPage."?page=".PG_ADMIN_HOSTING."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);


$deleteUrl=VM_API."?cmd=DELETE_VIDEO&return=".$retPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUrl.="&".SID;

?>

<div class="list_tools"><?php echo $addBtn?></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe"><?php echo $gText['MT_URL']?></th>
    <th class="pipe">W</th>
    <th class="pipe">H</th>
    <th class="pipe">B</th>
    <th class="pipe">#</th>
    <th class="pipe">V</th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

$query="brand_id ='".$GLOBALS['BRAND_ID']."'";
	
$errMsg=VObject::SelectAll(TB_VIDEOSERVER, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
} 

$num_rows = mysql_num_rows($result);
if ($num_rows==0) {
	echo "<tr>";
	echo "<td colspan=8>&nbsp;</td>";
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
	$name=$row['name'];
	if (strlen($name)>28) {
		$name=substr($name, 0, 25);
		$name.="...";
	}

	$name=htmlspecialchars($name);
	if ($row['brand_id']!='0') {
		echo "<td class=\"u_name\"><a target='$target' href=\"$itemUrl\">".$name."</a></td>\n";
	} else {
		echo "<td class=\"u_name\">".$name."</td>\n";
	}
	$url=$row['url'];
	if (strlen($url)>50) {
		$url=substr($url, 0, 50);
		$url.="...";
	}

	echo "<td class=\"u_item\">".$url."</td>\n";
	if ($row['width']>0)
		echo "<td class=\"u_item_c\">".$row['width']."</td>\n";
	else
		echo "<td class=\"u_item_c\">D</td>\n";
	if ($row['height']>0)
		echo "<td class=\"u_item_c\">".$row['height']."</td>\n";
	else
		echo "<td class=\"u_item_c\">D</td>\n";
	if ($row['bandwidth']>0)
		echo "<td class=\"u_item_c\">".$row['bandwidth']."</td>\n";
	else
		echo "<td class=\"u_item_c\">D</td>\n";
	
	if ($row['max_wind']>0)
		echo "<td class=\"u_item_c\">".$row['max_wind']."</td>\n";
	else
		echo "<td class=\"u_item_c\">D</td>\n";
	
	if ($row['type']=='VIDEO')
		echo "<td class=\"u_item_c\">N</td>\n";
	else
		echo "<td class=\"u_item_c\">Y</td>\n";

	if ($row['brand_id']!='0') {
		$deleteItemUrl=$deleteUrl."&id=".$row['id'];
		echo "<td class=\"m_tool\">";
		
		$format=$gText['M_CONFIRM_DELETE'];
		$msg=sprintf($format, "\'".addslashes($name)."\'");
		echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteItemUrl\">".$deleteBtn."</a></td>\n";
	} else {
		echo "<td class=\"m_tool\">&nbsp;</td>";
	}
	echo "</tr>\n";
	
	$rowCount++;
}
?>
</table>

