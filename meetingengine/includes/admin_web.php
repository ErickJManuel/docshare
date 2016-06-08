<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vwebserver.php");
$versionFile="vversion.php";

//$thisPage=$_SERVER['PHP_SELF'];
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$addIcon="themes/add.gif";
//$addUrl=$thisPage."?page=".PG_ADMIN_ADD_WEB."&brand=".$GLOBALS['BRAND_NAME'];
$addUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_WEB;
if (SID!='')
	$addUrl.="&".SID;
$addBtn="<a target='$target' href=\"$addUrl\"><img src=\"$addIcon\"> ${gText['M_ADD_HOSTING']}</a>";
/*
$editAwsUrl=$thisPage."?page=".PG_ADMIN_EDIT_AWS."&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$editAwsUrl.="&".SID;
*/
//$editWebUrl=$thisPage."?page=".PG_ADMIN_EDIT_WEB."&brand=".$GLOBALS['BRAND_NAME'];
$editWebUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_WEB;
if (SID!='')
	$editWebUrl.="&".SID;
$retPage=$thisPage."?page=".PG_ADMIN_HOSTING."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);
$deleteUrl=VM_API."?cmd=DELETE_WEB&return=".$retPage."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUrl.="&".SID;

$siteUrl=$gBrandInfo['site_url'];

?>

<div class="list_tools"><?php echo $addBtn?></div>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe"><?php echo $gText['M_ID']?></th>
    <th class="pipe"><?php echo $gText['MD_NAME']?></th>
    <th class="pipe"><?php echo $gText['MT_URL']?></th>
    <th class="pipe"><?php echo _Text("Version")?></th>
    <th class="tr">&nbsp;</th>
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
	

	$version="n/a";
	if ($row['installed_version']!='')
		$version=$row['installed_version'];
/* don't do the test here as the site may not be accessible
	elseif ($row['url']!='') {
		$aurl=$row['url'].$versionFile;		
		// this should return a version numbe string, which length should be less than 16
		$resp=HTTP_Request($aurl, '', 'GET', 5);
		if ($resp===false || strlen($resp)>16) {
			$version="n/a";
		} else
			$version=$resp;
	}
*/
	echo "<td class=\"u_item\">".$url."</td>\n";

	echo "<td class=\"u_item_c\">".$version."</td>\n";	
	
	if ($row['url']==$siteUrl) {	
		echo "<td class=\"m_tool\">&nbsp;</td>\n";
	} else {		
		$deleteItemUrl=$deleteUrl."&id=".$row['id'];
		echo "<td class=\"m_tool\">";
		
		$format=$gText['M_CONFIRM_DELETE'];
		$msg=sprintf($format, "\'".addslashes($name)."\'");
		echo "<a onclick=\"return MyConfirm('".$msg."')\" href=\"$deleteItemUrl\">".$deleteBtn."</a></td>\n";
	}
	
	echo "</tr>\n";
	
	$rowCount++;
}
?>
</table>

