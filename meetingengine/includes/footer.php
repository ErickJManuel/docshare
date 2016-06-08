
<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// show pag content only without the header and footer
if (isset($_REQUEST['content_only']))
	return;

if (isset($GLOBALS['HIDE_NAV']) && $GLOBALS['HIDE_NAV']=='on' && 
		isset($GLOBALS['HIDE_TABS']) && $GLOBALS['HIDE_TABS']=='on' &&
		isset($GLOBALS['SIDE_NAV']) && $GLOBALS['SIDE_NAV']=='off')
{
	print <<<END
	</div> <!--main_body-->

END;
}

?>
	
</center>

</body>

</html>

<?php

if (isset($endPageFile) && $endPageFile!='') {
	//echo str_pad(" ", 512);
//	ob_flush();
	flush();
	include_once($endPageFile);
}

?>