<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


require_once("dbobjects/vhook.php");

$srcText=_Text("View source");

$themeDir=SITE_URL."themes/";
$srcIcon=$themeDir."preview.gif";

$srcPage=$GLOBALS['BRAND_URL']."?page=HELP_REST&code_page=";
$allowPage=SITE_URL."hooks/allow.php";
$allowSrcPage=$srcPage."hooks/allow.php";

$denyPage=SITE_URL."hooks/deny.php";
$denySrcPage=$srcPage."hooks/deny.php";

$redirectPage=SITE_URL."hooks/redirect.php";
$redirectSrcPage=$srcPage."hooks/redirect.php";

$thisPage=$_SERVER['PHP_SELF'];
$postUrl=$thisPage."?page=".PG_ADMIN_API_HOOKS;
if (SID!='')
	$postUrl.="&".SID;

$docUrl=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&topic=Hooks";
if (SID!='')
	$docUrl.="&".SID;
	
$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_API;
if (SID!='')
	$cancelUrl.="&".SID;

$hookInfo=array();
if ($gBrandInfo['hook_id']!='0') {
	$hook=new VHook($gBrandInfo['hook_id']);
	if ($hook->Get($hookInfo)!=ERR_NONE) {
		ShowError($hook->GetErrorMsg());
		return;
	}
} else {
	
	foreach ($s_hook_info as $key => $val) {
		$hookInfo[$key]='';
	}
}

if (isset($_POST['submit'])) {
	
	foreach ($s_hook_info as $key => $val) {
		if (GetArg($key, $arg))
			$hookInfo[$key]=$arg;
	}
	
	if (!isset($hook)) {
		$hook=new VHook();
		if ($hook->Insert($hookInfo)!=ERR_NONE) {
			ShowError($hook->GetErrorMsg());
			return;
		}
		
		$hook->Get($hookInfo);
		$brand=new VBrand($GLOBALS['BRAND_ID']);
		$brandInfo=array();
		$brandInfo['hook_id']=$hookInfo['id'];
		if ($brand->Update($brandInfo)!=ERR_NONE) {
			ShowError($brand->GetErrorMsg());
			return;
		}
	} else {
		if ($hook->Update($hookInfo)!=ERR_NONE) {
			ShowError($hook->GetErrorMsg());
			return;
		}
	}	
	
}

?>
<script type="text/javascript">
<!--

function isValidURL(url){
/*
	var RegExp = /^http|https:\/\//; 
	if(RegExp.test(url)){
        return true;
    }else{
        return false;
    }
*/
	return true;
} 
function CheckWebForm(theForm) {

<?php

foreach ($hookInfo as $key => $value) {
	if ($key=='id')
		continue;
print <<<END
	if (theForm.$key.value!='' && !isValidURL(theForm.$key.value))
	{
		alert("Please enter a valid URL.");
		theForm.$key.focus();
		return (false);
	}
END;
}
?>
	return (true);
}


//-->
</script>

<div class='heading1'>API Hooks</div>

<ul>
<li><a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $docUrl?>">API Hooks Documentation</a></li>

<li>
For testing only, you can enter one of the following sample hooks' URL to each field.
You should not use these URLs in your deployment. Note that not all actions are supported by each hook.
<ul>
<li>Allow the action to proceeed:<br><a href="<?php echo $allowPage?>"><?php echo $allowPage?></a> 
<a href="<?php echo $allowSrcPage?>"><img src="<?php echo $srcIcon?>"> <?php echo $srcText?></a></li>
<li>Deny the action:<br><a href="<?php echo $denyPage?>"><?php echo $denyPage?></a>
<a href="<?php echo $denySrcPage?>"><img src="<?php echo $srcIcon?>"> <?php echo $srcText?></a></li>
<li>Redirect to www.google.com:<br><a href="<?php echo $redirectPage?>"><?php echo $redirectPage?></a> 
<a href="<?php echo $redirectSrcPage?>"><img src="<?php echo $srcIcon?>"> <?php echo $srcText?></a></li>
</ul>
</li>
</ul>
<table class="meeting_detail">
<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<tr><td>&nbsp;</td>
<td colspan=3 class='m_caption'>
<?php echo _Text("Type in valid HTTP urls.")?>
</td></tr>

<?php

$target=$GLOBALS['TARGET'];

foreach ($hookInfo as $key => $value) {
	if ($key=='id')
		continue;
	$docPage=$GLOBALS['BRAND_URL']."?page=HELP_REST&topic=Hooks&sub1=".$key;
	print <<<END
<tr>
	<td class="m_key"><a target=$target href='$docPage'>$key</a>:</td>
	<td colspan=3 class="m_val">
	<input type='text' name='$key' size='75' value='$value'>
	</td>
</tr>
END;
}
?>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	</td>
</tr>
</form>


</table>
