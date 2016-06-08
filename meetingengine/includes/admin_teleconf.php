<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vteleserver.php");
$versionFile="vversion.php";

$thisPage=$_SERVER['PHP_SELF'];
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">Delete";
$addIcon="themes/add.gif";
//$addUrl=$thisPage."?page=".PG_ADMIN_ADD_TELE."&brand=".$GLOBALS['BRAND_NAME'];
$addUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_TELE;
if (SID!='')
	$addUrl.="&".SID;
$addBtn="<a target='$target' href=\"$addUrl\"><img src=\"$addIcon\"> ${gText['M_ADD_HOSTING']}</a>";

//$editUrl=$thisPage."?page=".PG_ADMIN_EDIT_TELE."&brand=".$GLOBALS['BRAND_NAME'];
$editUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_TELE;
if (SID!='')
	$editUrl.="&".SID;
$retPage=$thisPage."?page=".PG_ADMIN_HOSTING."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
$deleteUrl=VM_API."?cmd=DELETE_TELE&return=".$retPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUrl.="&".SID;

?>

<div class="list_tools"><?php echo $addBtn?></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe" style="width:300px"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe"><?php echo _Text("Recording")?></th>
    <th class="pipe"><?php echo _Text("Control")?></th>
    <th class="pipe"><?php echo _Text("Dial-out")?></th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

$query="brand_id ='".$GLOBALS['BRAND_ID']."' OR brand_id='0'";
	
$errMsg=VObject::SelectAll(TB_TELESERVER, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
} 

$num_rows = mysql_num_rows($result);
if ($num_rows==0) {
	echo "<tr>";
	echo "<td colspan=6>&nbsp;</td>";
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
	if (strlen($name)>43) {
		$name=substr($name, 0, 40);
		$name.="...";
	}

	$name=htmlspecialchars($name);
	if ($row['brand_id']!='0') {
		echo "<td class=\"u_name\"><a target=$target href=\"$itemUrl\">".$name."</a></td>\n";	
	} else {
		echo "<td class=\"u_name\">".$name."</td>\n";	
	}
	$canDial=$row['can_dialout'];
	$canModify=$row['can_modify'];

//	if ($gBrandInfo['can_record']=='Y')
		$canRec=$row['can_record'];
//	else
//		$canRec='N';


	echo "<td class=\"u_item_c\">".$canRec."</td>\n";
	echo "<td class=\"u_item_c\">".$row['can_control']."</td>\n";
	echo "<td class=\"u_item_c\">".$canDial."</td>\n";
	
	if ($row['brand_id']!='0' && $canModify!='N') {
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

