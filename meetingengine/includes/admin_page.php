<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vbrand.php");

$brand=new VBrand($GLOBALS['BRAND_ID']);
$brandInfo=array();
$brand->Get($brandInfo);

GetArg('index', $field);

$pageName='';
if ($field=='home_text')
	$pageName=$gText['M_HOME_PAGE'];
else if ($field=='help_text') {
	$pageName=$gText['TITLE_HELP'];
} else {
	$labelKey=str_replace("text", "label", $field);
	if (isset($brandInfo[$labelKey]))
		$pageName=$brandInfo[$labelKey];
}

if ($pageName=='') {
	ShowError("Page not set");
	return;
}

$thisPage=$_SERVER['PHP_SELF'];
$retPage=$thisPage."?page=".PG_ADMIN_SITE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);
$postUrl=VM_API."?cmd=SET_BRAND&return=$retPage";
if (SID!='')
	$postUrl.="&".SID;	
	
$cancelUrl=$thisPage."?page=".PG_ADMIN_SITE."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$cancelUrl.="&".SID;	

$text=htmlspecialchars($brandInfo[$field]);

?>

<script type="text/javascript">
<!--
	


//-->
</script>
<form method="POST" action="<?php echo $postUrl?>" name="updatemeeting_form">

<div class=heading1><?php echo $gText['M_EDIT']?> '<?php echo $pageName?>'</div>
<div>
	<span class="m_caption">Insert HTML text.</span>
	<span><input checked type="checkbox" name="add_br" value="1">Replace line breaks with HTML '&lt;br&gt;' automatically</span>
</div>
<input type='hidden' name="brand" value="<?php echo $GLOBALS['BRAND_NAME']?>">
<input type='hidden' name="brandUrl" value="<?php echo $GLOBALS['BRAND_URL']?>">
<table class="meeting_detail">

<tr>
	<td class="m_val">
	<textarea id="meet_desc" name="<?php echo $field?>" rows="25" cols="120"><?php echo $text?></textarea>
	</td>
</tr>

<tr>
	<td class="m_val">
	<input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	</td>
</tr>

</table>
</form>