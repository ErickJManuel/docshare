<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

$memberId=GetSessionValue('member_id');
if ($memberId=='') {
	ShowError("You are not signed in");
	return;
}
	
$addIcon="themes/add.gif";
$addUrl=$GLOBALS['BRAND_URL']."?page=".PG_LIBRARY_QUESTION;
if (SID!='')
	$addUrl.="&".SID;

$addText=_Text("Add a Question");
$addBtn="<a target=${GLOBALS['TARGET']} href=\"$addUrl\"><img src=\"$addIcon\"> $addText</a>";

/* <div class="heading1"><?php echo _Text("Polling")?></div> */

?>

<div class="list_tools">
<span class="list_item"><?php echo $addBtn?></span>
</div>
<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">#</th>
    <th class="pipe"><?php echo _Text("Question")?></th>
    <th class="tr">&nbsp;</th>
</tr>

<?php

$themeDir=SITE_URL."themes/";
$deleteIcon=$themeDir."delete.gif";
//$upIcon=$themeDir."bullet_arrow_up.png";
//$downIcon=$themeDir."bullet_arrow_down.png";

$upIcon=$themeDir."up_arrow.gif";
$downIcon=$themeDir."down_arrow.gif";

$deleteBtn="<img src=\"$deleteIcon\">".$gText['M_DELETE'];
$upBtn="<img src=\"$upIcon\">"._Text("Move up");
$downBtn="<img src=\"$downIcon\">"._Text("Move down");

$retPage=$GLOBALS['BRAND_URL']."?page=".PG_LIBRARY_POLLING;
if (SID!='')
	$retPage.="&".SID;
$deleteUrl=VM_API."?cmd=DELETE_QUESTION&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$deleteUrl.="&".SID;
	
$upUrl=VM_API."?cmd=MOVE_QUESTION&direction=-1&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$upUrl.="&".SID;

$downUrl=VM_API."?cmd=MOVE_QUESTION&direction=+1&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$downUrl.="&".SID;

	
$editPage=$GLOBALS['BRAND_URL']."?page=".PG_LIBRARY_QUESTION;
if (SID!='')
	$editPage.="&".SID;

$query="author_id='".$memberId."'";
$errMsg=VObject::SelectAll(TB_QUESTION, $query, $result, 0, 0, "*", "order");
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		echo "<tr><td class=\"u_item_c\">&nbsp;</td>\n";
		echo "<td class=\"u_item_b\">&nbsp;</td>\n";
		echo "<td class=\"m_tool\">&nbsp;</td></tr>\n";
	}
	$i=1;	
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($i % 2)
			echo "<tr>\n";
		else
			echo "<tr class=\"u_bg\">\n";
	
		echo "<td class=\"u_item_c\" style='width: 30px; vertical-align: middle'>".$i."</td>\n";
		$title=$row['question'];
		if (strlen($title)>55) {
			$title=substr($title, 0, 55-3);
			$title.="...";
		}
		if ($title=='')
			$title='[blank]';
			
		$editRowPage=$editPage."&id=".$row['id'];
		echo "<td class=\"u_item_b\" style='vertical-align: middle;'><a target=${GLOBALS['TARGET']} href=\"$editRowPage\">".htmlspecialchars($title)."</a></td>\n";
		
		
		$format=$gText['M_CONFIRM_DELETE'];
		$text="Question ".$i;
		$msg=sprintf($format, "\'".$text."\'");
		$deleteRowUrl=$deleteUrl."&id=".$row['id'];
		$upRowUrl=$upUrl."&index=".$i;
		$downRowUrl=$downUrl."&index=".$i;
		
		$actionIcons='';
		if ($i==1)
			$visibility="style='visibility:hidden'";
		else
			$visibility='';
		
		$actionIcons.="<a $visibility target=${GLOBALS['TARGET']} href=\"$upRowUrl\">$upBtn</a>";
		
		if ($i==$num_rows)
			$visibility="style='visibility:hidden'";
		else
			$visibility='';
		
		$actionIcons.=" <a $visibility target=${GLOBALS['TARGET']} href=\"$downRowUrl\">$downBtn</a>";
		
		$actionIcons.=" <a onclick=\"return MyConfirm('".$msg."')\" target=${GLOBALS['TARGET']} href=\"$deleteRowUrl\">$deleteBtn</a>\n";
		
		echo "<td class=\"m_tool\" style='width: 250px; text-align: center'>".$actionIcons."</td>\n";
		
		echo "</tr>\n";
		
		$i++;
	}
}

?>

</table>