<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

if($GLOBALS['SIDE_NAV'] == 'on') { 
	echo "</div>\n";
	echo "</td>\n";

print <<<END
	<td id='right-content' valign='top'>
	<div class='box1_top'></div>
	<div class='box1_mid'>
	<div id='r-text'>
END;
	include_once($GLOBAL['SIDE_INCLUDE']);

print <<<END
	</div>
	</div>
	<div class='box1_bottom'></div>
	</td>
END;

} else {
	echo "</div>\n";
	echo "</td>\n";

}

?>
	</tr>

</table> <!--main_content-->

</div> <!--main_page-->
</center>
</body>
</html>