<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("dbobjects/vcomment.php");
require_once("dbobjects/vmeeting.php");
require_once("includes/meetings_common.php");

$thisPage=$_SERVER['PHP_SELF']."?page=".PG_MEETINGS_COMMENT;
if (SID!='')
	$thisPage.="&".SID;


$memberId=GetSessionValue('member_id');

$itemsPerPage=6; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar


if (isset($_POST['submit_public']) || isset($_POST['submit_private']) || isset($_POST['submit_delete'])) {

	$count=count($_POST['selected_comments']);	
	for ($i=0; $i<$count; $i++){
		
		$commentId = $_POST['selected_comments'][$i];
		$comment=new VComment($commentId);
		$comment->GetValue('host_id', $hostId);
		if ($hostId!=$memberId) {
			ShowError("This comment doesn't belong to you or doesn't exist.");
			break;
		}		
			
		if (isset($_POST['submit_public'])) {
			$commentInfo['public']='Y';
			$comment->Update($commentInfo);
		} else if (isset($_POST['submit_private'])) {
			$commentInfo['public']='N';
			$comment->Update($commentInfo);
		} else if (isset($_POST['submit_delete'])) {
			$comment->Drop();			
		}
	}
}

GetArg('id', $id);


// find all of my meetings
$query="`host_id`='$memberId'";
$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

$meetingIds=array();
$num_rows = mysql_num_rows($result);
$selections='<select name="page_id" onchange="gotoPage()">';
$selections.="<option value=\"\">[".$gText['M_ALL_MEETINGS']."]</option>";		

while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	$title=$row['title'];
	if (strlen($title)>30)
		$title=substr($title, 0, 30);
	$title=htmlspecialchars($title);
	
	$meetingIds[]=$row['id'];
	
	if ($row['id']==$id) {
		$selected='selected';
	} else {
		$selected='';
	}
	$selections.="<option $selected value='".$row['id']."'>".$row['access_id'].":".$title."</option>\n";		


}
$selections.="</select>";	
$makePubText=_Text("Make public"); //_Comment: as in making a comment available to the public
$makePriText=_Text("Make private"); //_Comment: as in making a comment for private viewing only

?>

<script type="text/javascript">
<!--
	function gotoPage() {
		var pageId=document.selectmeeting_form.page_id.value;
		window.location="<?php echo $thisPage?>"+"&id="+pageId;
	}
//-->
</script>

<div class='meetings_select'>
<form name="selectmeeting_form">
<span id="select_show"><?php echo $gText['M_MEETING']?>:
<?php echo $selections?></span>
</form></div>

<form method="POST" action="" name="selection_form">
<div>
<?php echo _Text("Selected items")?>:
<input type="submit" name="submit_public" value="<?php echo $makePubText?>">
<input type="submit" name="submit_private" value="<?php echo $makePriText?>">
<input onclick="return MyConfirm('<?php echo _Text("Do you want to delete selected comments?")?>');" type="submit" name="submit_delete" value="Delete">
</div>



<?php

$query='';

if ($id!='') {
	$query="meeting_id='$id'";	
} else {
	// select all meetings of mine
	$select='';
	foreach ($meetingIds as $value) {
		if ($select!='')
			$select.=" OR ";
		$select.="meeting_id='$value'";	
	}
	
	if ($select!='')
		$query=$select;
	else
		$query='0';
	
}


GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_COMMENT, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
	
$errMsg=VObject::SelectAll(TB_COMMENT, $query, $result, $offset, $count, "*", "post_time", true);
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);
	
	$rowCount=0;	
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$commentId=$row['id'];
		$authorId=$row['author_id'];
		if ($authorId>0) {
			$author=new VUser($authorId);
			$authorInfo=array();
			$author->Get($authorInfo);
			$authorName=htmlspecialchars($author->GetFullName($authorInfo));
			$authorUrl=$GLOBALS['BRAND_URL']."?user=".$authorInfo['access_id'];
			if (SID!='')
				$authorUrl.="&".SID;
			$authorName="<a target=${GLOBALS['TARGET']} href=\"$authorUrl\">$authorName</a>";
		} else {
			$authorName=htmlspecialchars($row['full_name']);
			if ($row['email']!='')
				$authorName.="<span class='comment_email'>${row['email']}</span>";
		}
		
		$meetingId=$row['meeting_id'];
		$meeting=new VMeeting($meetingId);
		$meeting->GetValue('title', $meetingTitle);
		$meeting->GetValue('access_id', $accessId);
		
		if (strlen($meetingTitle)>23) {
			$meetingTitle=substr($meetingTitle, 0, 23-3);
			$meetingTitle.="...";
		}
		$meetingPage=$GLOBALS['BRAND_URL']."?meeting=".$accessId;
		if (SID)
			$meetingPage.="&".SID;
		$meetingTitle="<a target=${GLOBALS['TARGET']} href=\"$meetingPage\">$meetingTitle</a>";			
/*
		$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_COMMENT;
		if (SID!='')
			$retPage.="&".SID;
		$retPage=VWebServer::EncodeDelimiter1($retPage);

		$removeCommentUrl=VM_API."?cmd=SET_COMMENT&id=".$row['id']."&public=N&return=".$retPage;
		if (SID!='')
			$removeCommentUrl.="&".SID;

		$msg="remove this comment from the public area";
*/
		$commentBody=htmlspecialchars($row['text']);
		$commentBody=str_replace("\n", "<br>", $commentBody);

		$postTime=$row['post_time'];		
		if ($row['public']=='Y') {
			$pubText=_Text("Public");	//_Comment: as in a public comment or meeting 
		} else {
			$pubText=_Text("Private");	//_Comment: as in a private comment or meeting
		}


print <<<END
<div class='comment_item'>
<table class='comment_title'>
<tr>
<td class='comment_box'><input type='checkbox' name='selected_comments[]' value='$commentId'></td>
<td class='comment_icon'><img src="$commentIcon"></td>
<td class='comment_name'>$authorName</td>
<td class='comment_time'>$postTime</td>
<td class='comment_meeting'>$meetingTitle</td>
<td class='comment_remove'>$pubText</td>
</tr>
</table>
<div class='comment_body'>$commentBody</div>
</div>
END;

	}
}
echo "</form>\n";
echo "<hr size=1>\n";

$listPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_COMMENT;
if ($id!='')
	$listPage.="&id=".$id;
	
ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);


?>
