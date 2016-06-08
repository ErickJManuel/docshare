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


$joinPage=$GLOBALS['BRAND_URL'];
$target=$GLOBALS['TARGET'];
?>

<div class='heading1'><?php echo _Text("Meeting Participants Sign In")?></div>
<div class="login">
<form target='<?php echo $target?>' onSubmit="return ShowViewer(this);" class="join_form" method="GET" action="<?php echo $joinPage?>" name="join_form">
<input type="hidden" name="page" value="HOME_JOIN">
<input type="hidden" name="redirect" value="1">

<table>

<tr>
<td colspan="3" height="50px">&nbsp;</td>
</tr>

<tr>
<td align="right" width="40%"><div class='heading2'><?php echo _Text("Meeting ID")?>:</div></td>
<td colspan="2" width="60%"><input type="text" name="meeting" size="20"></td>
</tr>

<tr>
	<td width="40%">&nbsp;</td>
	<td colspan="2" width="60%"><input type="submit" name="signin" value='<?php echo _Text("Join Meeting")?>'></td>
</tr>
</table>

</form>

</div>