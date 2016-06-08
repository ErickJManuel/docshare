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
require_once("dbobjects/vquestion.php");
require_once("dbobjects/vwebserver.php");

GetArg("id", $questionId);

if ($questionId!='') {
	$question=new VQuestion($questionId);
	if ($question->Get($questionInfo)!=ERR_NONE) {
		ShowError("Error=".$question->GetErrorMsg());
		return;
	}
	
	$type=$questionInfo['type'];
	$questionText=$questionInfo['question'];
	$correct=$questionInfo['correct'];
	
	$choices=array(
		$questionInfo['choice_1'],
		$questionInfo['choice_2'],
		$questionInfo['choice_3'],
		$questionInfo['choice_4'],
		$questionInfo['choice_5']
		);
	
//	$timer=$questionInfo['timer'];
	$showCorrect=$questionInfo['show_correct'];
	
} else {
	
	$questionText='';
	$type='S';
	$correct=0;
	$choices=array("", "", "", "", "");
//	$timer=0;
	$showCorrect='N';
}

$selectChoice=$selectText='';
if ($type=='S')
	$selectChoice='checked';
else if ($type=='T')
	$selectText='checked';
	
	
if (GetArg("hidetabs", $hideTabs) && $hideTabs=='1') {
	$retPage=$GLOBALS['BRAND_URL']."?page=HOME_INFORM&hidetabs=1&message="."Your question has been added.";
	$retPage=VWebServer::EncodeDelimiter1($retPage);
	$target="_self";
} else {
	$retPage=$GLOBALS['BRAND_URL']."?page=".PG_LIBRARY_POLLING;
	$cancelUrl=$retPage;
	$target=$GLOBALS['TARGET'];
}

$correctOpt="<select name='correct' onchange='selectCorrect()'>";
$choiceText=array("None", "A", "B", "C", "D", "E");
foreach ($choiceText as $key => $val) {
	if ($key==$correct)
		$selected="selected";
	else
		$selected='';
		
	$correctOpt.="<option $selected value='$key''>$val</option>";
	
}
$correctOpt.="</select>";
/*
$timerText=array("No timer", "00:15", "00:30", "01:00", "01:30", "02:00", "02:30", "03:00", "04:00", "05:00");

$timerOpt="<select name='timer'>";
foreach ($timerText as $key => $val) {
	if ($key>0) {
		$items=explode(":", $val);
		$sec=(int)$items[0]*60+(int)$items[1];
	} else {
		$sec=0;
	}
	if ($sec==$timer)
		$timerOpt.="<option selected value='$sec'>$val</option>";
	else
		$timerOpt.="<option value='$sec'>$val</option>";
	
}
$timerOpt.="</select>";
*/
if (isset($question)) {
	$postUrl=VM_API."?cmd=SET_QUESTION&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$postUrl.="&".SID;
} else {
	$postUrl=VM_API."?cmd=ADD_QUESTION&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$postUrl.="&".SID;
}

if ($showCorrect=='Y')
	$checkCorrect='checked';
else
	$checkCorrect='';

$format=$gText['M_ENTER_VAL'];

?>
<?php
if (isset($question)) {
	$text=_Text("Question");
	echo "<div class=\"heading1\">$text</div>\n";	
} else {
	$text= _Text("Add a Question");
	echo "<div class=\"heading1\">$text</div>\n";
}
?>

<form onSubmit="return checkQuestionForm(this)"  target="<?php echo $target?>" method="POST" action="<?php echo $postUrl?>" name="add_question_form">
<input type='hidden' name='check_correct' value='1'>

<?php
if (isset($questionInfo['id'])) {
	$id=$questionInfo['id'];
	echo "<input type='hidden' name='id' value='$id'>\n";
}
?>
<table class="meeting_detail">

<tr>
	<td class="m_key"><?php echo _Text("Question")?></td>
	<td colspan="3" class="m_val"><textarea name="question" rows="4" cols="70"><?php echo $questionText?></textarea></td>
</tr>

<tr>
	<td class="m_key"><?php echo _Text("Type")?></td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'>
	<input <?php echo $selectChoice?> type="radio" name="type" value="S" onclick="showChoices(true);">
	<?php echo _Text("Multiple Choice")?> &nbsp;&nbsp;
	<input <?php echo $selectText?> type="radio" name="type" value="T" onclick="showChoices(false);">
	<?php echo _Text("Short Answer")?>
	</div>

	<br>
	<div id="choices">
	<div class='m_caption'><?php echo _Text("Enter up to 5 answers")?></div>
	<div class='sub_choice'><b>A</b>. <input type="text" size="70" name="choice_1" value="<?php echo $choices[0]?>"></div>
	<div class='sub_choice'><b>B</b>. <input type="text" size="70" name="choice_2" value="<?php echo $choices[1]?>"></div>
	<div class='sub_choice'><b>C</b>. <input type="text" size="70" name="choice_3" value="<?php echo $choices[2]?>"></div>
	<div class='sub_choice'><b>D</b>. <input type="text" size="70" name="choice_4" value="<?php echo $choices[3]?>"></div>
	<div class='sub_choice'><b>E</b>. <input type="text" size="70" name="choice_5" value="<?php echo $choices[4]?>"></div>
	
	<div class='sub_choice'>
	<b><?php echo _Text("Correct answer:")?></b> <?php echo $correctOpt?>
<?php
/*
		<span id="show_correct"><input <?php echo $checkCorrect?> name="show_correct" value="Y" type="checkbox">Show correct answer to participants</span>
*/
?>
	</div>

	</div>
	
	
	</td>
</tr>

<?php
/*
<tr>
	<td class="m_key"><?php echo _Text("Timer")?></td>
	<td colspan="3" class="m_val">
	<?php echo _Text("Display a timer (mm:ss):")?> <?php echo $timerOpt?> 
	</td>
</tr>
*/
?>
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;

<?php
	if ($hideTabs!='1') {
		$cancelText=$gText['M_CANCEL'];
		print <<<END
	<input type="button" name="cancel" value="$cancelText" onclick="parent.location='$cancelUrl'; return false;"></td>

END;
}
?>
</tr>

</table>
</form>

<script type="text/javascript">
<!--
/*
function selectCorrect()
{
	var form=document.add_question_form;
	var elem=document.getElementById('show_correct');
	if (elem && form) {
		if (form.correct.selectedIndex>0) {
			elem.style.display='inline';
		} else {
			elem.style.display='none';
		}
	}
}
*/
function checkQuestionForm(form) {

	if (!form)
		return false;

	if (form.question.value=='') {
		alert("<?php echo sprintf($format, 'Question')?>");
		form.question.focus();
		return (false);
	}


	if (form.type[0].checked && (form.choice_1.value=='' || form.choice_2.value==''))
	{
		alert("<?php echo sprintf($format, 'Answer')?>");
		if (form.choice_1.value=='')
			form.choice_1.focus();
		else
			form.choice_2.focus();
		return (false);
	}

	return true;
}

function showChoices(showIt)
{
	var elem=document.getElementById('choices');
	if (elem) {
		if (showIt)
			elem.style.display='block';
		else
			elem.style.display='none';
	}
}

//selectCorrect();

<?php
if ($type=='T')
	echo "showChoices(false);\n";
?>

//-->
</script>