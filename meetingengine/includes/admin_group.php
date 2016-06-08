<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vgroup.php");
require_once("dbobjects/vwebserver.php");

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar
$maxListChars=80; // max. number of chars to display in the meeting description box

$target=$GLOBALS['TARGET'];
$thisPage=$_SERVER['PHP_SELF'];
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">Delete";
$addIcon="themes/add.gif";
//$addUrl=$thisPage."?page=".PG_ADMIN_ADD_GROUP."&brand=".$GLOBALS['BRAND_NAME'];
$addUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_GROUP;
if (SID!='')
	$addUrl.="&".SID;
$addBtn="<a target=$target href=\"$addUrl\"><img src=\"$addIcon\"> ${gText['M_ADD_GROUP']}</a>";
//$editUrl=$thisPage."?page=".PG_ADMIN_EDIT_GROUP."&brand=".$GLOBALS['BRAND_NAME'];
$editUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_GROUP;
if (SID!='')
	$editUrl.="&".SID;

$retPage=$thisPage."?page=".PG_ADMIN_GROUPS."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);
$deleteUrl=VM_API."?cmd=DELETE_GROUP&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUrl.="&".SID;

//$groupsPage=$thisPage."?page=".PG_ADMIN_GROUPS."&brand=".$GLOBALS['BRAND_NAME'];
$groupsPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_GROUPS;
if (SID!='')
	$groupsPage.="&".SID;

?>


<div class="list_tools"><?php echo $addBtn?></div>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe" style="width:350"><?php echo $gText['MD_DESCRIPTION']?></th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

$selectQuery="1";
$query="$selectQuery AND brand_id ='".$GLOBALS['BRAND_ID']."'";

GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_GROUP, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
	
$errMsg=VObject::SelectAll(TB_GROUP, $query, $result, $offset, $count, "*", "id");
if ($errMsg!='') {
	ShowError($errMsg);
	return;
} 

$num_rows = mysql_num_rows($result);
if ($num_rows==0) {
	echo "<tr>";
	echo "<td colspan=7>&nbsp;</td>";
	echo "</tr>";
}
$rowCount=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if ($rowCount % 2)
		echo "<tr class=\"u_bg\">\n";
	else
		echo "<tr>\n";
	
	$groupUrl=$editUrl."&id=".$row['id'];
	echo "<td class=\"u_item u_item_ws\"><a target=$target href=\"$groupUrl\">".$row['id']."</a></td>\n";
	$name=$row['name'];
	if (strlen($name)>25)
		$name=substr($name, 0, 25);

	$name=htmlspecialchars($name);
	if ($name=='')
		$name='&nbsp;';
	echo "<td class=\"u_name\"><a target=$target href=\"$groupUrl\">".$name."</a></td>\n";
	$desc=$row['description'];
	if (strlen($desc)>52)
		$desc=substr($desc, 0, 50)."...";

	$desc=htmlspecialchars($desc);
	if ($desc=='')
		$desc='&nbsp;';
	echo "<td class=\"u_item\">".$desc."</td>\n";

	echo "<td class=\"m_tool\">";
	if ($row['id']!=$gBrandInfo['trial_group_id']) {
		$deleteGroupUrl=$deleteUrl."&id=".$row['id'];
		$format=$gText['M_CONFIRM_DELETE'];
		$msg=sprintf($format, "\'".addslashes($name)."\'");
		
		echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteGroupUrl\">".$deleteBtn."</a>";
	} else {
		echo "&nbsp;";
	}
	echo "</td>\n</tr>\n";
	
	$rowCount++;
}

echo "</table>";

ShowPageNavBar($groupsPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
