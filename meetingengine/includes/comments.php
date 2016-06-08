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

// the following should be defined in the embedding file
// $query
// $meetingId
// $hostId
// $publicComment
// $authorId
// $retPage
// $postComment


$memberId=GetSessionValue('member_id');
$addCommentUrl=VM_API."?cmd=ADD_COMMENT&return=".$retPage;
if (SID!='')
	$addCommentUrl.="&".SID;
	
$removeCommentUrl=VM_API."?cmd=SET_COMMENT&public=N&return=".$retPage;
if (SID!='')
	$removeCommentUrl.="&".SID;

$itemsPerPage=5; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar

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

$format=$gText['M_ENTER_VAL'];

?>

<script type="text/javascript">
<!--
function CheckForm(theForm) {

	if (theForm.comment_text.value=='')
	{
		alert(" <?php echo sprintf($format, 'Comment')?>");
		theForm.comment_text.focus();
		return (false);
	}
	if (theForm.author_id.value=='') {
		if (theForm.full_name.value=='')
		{
			alert(" <?php echo sprintf($format, 'Name')?>");
			theForm.full_name.focus();
			return (false);
		}

	}
	if (theForm.security_answer.value=='') {
		alert("<?php echo sprintf($format, 'Security question')?>");
		theForm.security_answer.focus();
		return (false);
	}
	return (true);
}

function HideCommentBox()
{
	SetElemHtml('comment_box', '');
	var elem=document.getElementById('post_comment');
	elem.style.visibility='visible';
	PreviewComment(false);
	return true;
}
function ShowCommentBox()
{
	SetElemHtml('comment_box', 'comment_box');
	var elem=document.getElementById('post_comment');
	elem.style.visibility='hidden';
	var form=document.getElementById('comment_form_id');
	form.comment_text.focus();

	return true;
}

// Convert special characters to HTML entities
function htmlspecialchars(string) {
    
    string = string.toString();    
    string = string.replace(/&/g, '&amp;');
    string = string.replace(/</g, '&lt;');
    string = string.replace(/>/g, '&gt;');
    
    return string;
}


function PreviewComment(showIt)
{
	var prevElem=document.getElementById('preview_comment_id');
	if (showIt) {
		var nameElem=document.getElementById('preview_author_id');
		var timeElem=document.getElementById('preview_time_id');
		var bodyElem=document.getElementById('preview_body_id');
		var form=document.getElementById('comment_form_id');
		if (form.author_id.value!='')
			nameElem.innerHTML= "<?php echo $memberName?>";
		else
			nameElem.innerHTML=form.full_name.value;
		bodyElem.innerHTML=htmlspecialchars(form.comment_text.value);
		timeElem.innerHTML="preview";
		prevElem.style.display='inline';
	} else {
		prevElem.style.display='none';
	}
	return true;

}

//-->
</script>


<table class='comment_heading'>
<tr>
<td class='comment_head_text'><?php echo $gText['M_COMMENTS']?></td>
<td id='post_comment' class='comment_post'><a href='javascript:void(0)' onclick="return ShowCommentBox();">
<img class='comment_post_icon' src="<?php echo $commentIcon?>"> <?php echo _Text("Post a comment")?></a></td>
</tr>
</table>

<div id='comment_box'>
<form target=<?php echo $GLOBALS['TARGET']?> id='comment_form_id' name='comment_form' onsubmit='return CheckForm(this)' method='POST' action='<?php echo $addCommentUrl?>'>
<input type='hidden' name='author_id' value='<?php echo $authorId?>'>
<input type='hidden' name='meeting_id' value='<?php echo $meetingId?>'>
<input type='hidden' name='host_id' value='<?php echo $hostId?>'>
<table class='comment_tb'>
<tr>
<td colspan='3'>
<textarea name="comment_text" rows="6" cols="80"><?php /*echo $gText['M_ENTER_COMMENT']*/?></textarea>
</td>
</tr>
<?php
if ($authorId=='') {
	print <<<END
<tr>
<td>
Name*: <input type="text" name="full_name" size="30" autocorrect="off"></td>
<td>
Email: <input type="email" name="email" size="30" autocorrect="off" autocapitalize="off"></td>
</tr>

END;
}
$caption='';
if ($authorId=='') {
	$caption.=
"<span style='font-size: 95%; padding-left: 10px;'>".
_Text("Already a member?").
" &nbsp;&nbsp;<a target=${GLOBALS['TARGET']} href=\"$signinPage\">".
_Text("Sign in first")."</a></span><div class='m_caption'>".
_Text("Your email address will not be displayed in the public area.")."</div>";
}

//require_once("includes/ClassMathGuard.php");
//$securityQ=MathGuard::returnQuestion();
$securityQ=getSecurityQuestion();

$reqText=_Text("Required");
$text4=_Text("Your comment will be sent to the meeting host only.");

print <<<END
<tr>
	<td colspan="3">$securityQ * &nbsp; <span class='m_caption'>*$reqText</span></td>
</tr>
END;

if ($publicComment=='Y') {

$text1=_Text("Post comment to the public area");
$text2=_Text("Send private comment to the host");

$previewText=_Text("Preview");
$postText=_Text("Post Comment");
$discardText=_Text("Discard");
$sendText=_Text("Send Comment");
$text3=_Text("The host may remove a comment from the public area.");


print <<<END
<tr>
	<td colspan="3">
	<input checked type="radio" name="public" value="Y"><b>$text1</b>
	<input type="radio" name="public" value="N"><b>$text2</b>
	</td>
</tr>

<tr>
<td colspan="3">
<input type="button" name="preview" value="$previewText" onclick="return PreviewComment(true);">&nbsp;
<input type="submit" name="submit" value="$postText">&nbsp;
<input type="button" name="cancel" value="$discardText" onclick="return HideCommentBox();">$caption
<div class='m_caption'>$text3</div>
</td>
</tr>
END;
} else {
$sendText=_Text("Send Comment");
	
print <<<END
<tr>
<td colspan="3">
<input type="submit" name="submit" value="$sendText">&nbsp;
<input type="button" name="cancel" value="${gText['M_DISCARD']}" onclick="return HideCommentBox();">$caption
<div class='m_caption'>$text4</div>
</td>
</tr>	
END;
	
}
?>

</table>
</form>
</div>

<div id='preview_comment_id' class='comment_item'>
<table class='comment_title'><tr>
<td class='comment_icon'><img src="<?php echo $commentIcon?>"></td>
<td id='preview_author_id' class='comment_name'>&nbsp;</td>
<td id='preview_time_id' class='comment_time'>&nbsp;</td>
<td class='comment_remove'>&nbsp;</td>
</tr></table>
<div id='preview_body_id' class='comment_body'>&nbsp;</div>
<hr>
</div>



<?php

if ($total>0) {
	$thisPage=$_SERVER['PHP_SELF'];
	if ($meetingId!=0) {
		$meeting=new VMeeting($meetingId);
		$meeting->GetValue('access_id', $meeting_aid);
		$listPage=$thisPage."?meeting=".$meeting_aid;
	} else {
		$host=new VUser($hostId);
		$host->GetValue('access_id', $host_aid);
		$listPage=$thisPage."?room=".$host_aid;
	}
	$listPage.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$listPage.="&".SID;

	ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);
}

$errMsg=VObject::SelectAll(TB_COMMENT, $query, $result, $offset, $count, "*", "post_time", true);
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows>0)
		GetSessionTimeZone($tzName, $tz);
	
	$rowCount=0;	
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
		}
		$removeUrl=$removeCommentUrl."&id=".$row['id'];

		VObject::ConvertTZ($row['post_time'], 'SYSTEM', $tz, $postTime);
	
		$commentBody=htmlspecialchars($row['text']);
		$commentBody=str_replace("\n", "<br>", $commentBody);

		if ($hostId==$memberId) {
			$msg=_Text("Do you want to remove this comment from the public area?");
			$remove="<a onclick=\"return MyConfirm('".$msg."')\" href='$removeUrl'>Remove</a>";
		} else {
			$remove='&nbsp;';
		}

print <<<END
<div class='comment_item'>
<table class='comment_title'>
<tr>
<td class='comment_icon'><img src="$commentIcon"></td>
<td class='comment_name'>$authorName</td>
<td class='comment_time'>$postTime</td>
<td class='comment_remove'>$remove</td>
</tr>
</table>
<div class='comment_body'>$commentBody</div>
</div>
END;

	}
}



?>


<script type="text/javascript">
<!--	
var htmlText=new Array();
htmlText['comment_box']= GetElemHtml('comment_box');


function SetElemHtml(elemId, textId)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		if (textId!='')
			elem.innerHTML=htmlText[textId];
		else
			elem.innerHTML='';
		
	}
	return true;
}

<?php 

if ($postComment=='1')
	echo "ShowCommentBox();";
else
	echo "HideCommentBox();";

echo "PreviewComment(false);";

?>

//-->
</script>

