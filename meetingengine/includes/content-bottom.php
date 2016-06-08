<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */

if($GLOBALS['SIDE_NAV'] == 'on') { 
	echo "</div> <!--left-text-->\n";
	echo "</td> <!--left-content-->\n";

print <<<END
	<td id='right-content' valign='top'>
	<div class='right_box'>

END;
	include_once($GLOBAL['SIDE_INCLUDE']);

print <<<END

	</div>
	</td> <!--right-content-->
	
END;

} else {
	echo "</div> <!--one-text-->\n";
	echo "</td> <!--one-content-->\n";
}

?>
	</tr>
</table> <!--main_content-->
</div> <!--page-content-->

<?php 
	if (isset($GLOBALS['HIDE_NAV']) && $GLOBALS['HIDE_NAV']=='on') 
		$display="style='display:none'";
	else
		$display='';
?>

<table id='bottom_content' <?php echo $display?> cellspacing=0 cellpadding=0>
	<tr height='10px'>
	</tr>


	<tr id="footer-bar">

		<td colspan="5">
		<div name="bot_bar">
		<ul>
<?php
$url=$GLOBALS['BRAND_URL'];
if (SID!='')
	$url.="?".SID;

// only show the home page link if it is enabled.
if (strpos($GLOBALS['MAIN_TABS'], 'HOME')!==false)
	echo "<li><a target=${GLOBALS['TARGET']} href=\"$url\" onclick='return OnLink(this)'>${gText['HOME_TAB']}</a></li>\n";

for ($i=1; $i<5; $i++) {
	
	$key="FOOTER_".$i;
	$label=$GLOBALS[$key];
	$url=$GLOBALS['BRAND_URL']."?page=HOME_FOOTER_".$i;
	if (SID!='')
		$url.="&".SID;
	if ($label)
		echo "<li><a target=${GLOBALS['TARGET']} href=\"$url\" onclick='return OnLink(this)'>$label</a></li>\n";
}

?>
		</ul>
		</div>
		</td>
	</tr> <!--footer-bar-->
	<tr id='footnote-bar'>
		<td colspan="5">
		<table width='100%'>
		<tr>
			<td width='20%'></td>
			<td width='50%'>
				<div id="footer_text"><?php echo $GLOBALS['FOOTNOTE']?></div>
			</td>
			<td id='version_text'>
<?php
$silent=true;
include 'vinstall/vversion.php';

$versUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_VERSIONS."&version_number=".$version;
if (SID!='')
	$versUrl.="&".SID;

echo "<a target=${GLOBALS['TARGET']} href='$versUrl' onclick='return OnLink(this)'>Version $version</a>\n";

?>
			</td>
		</tr>
		</table>
		</td>
	</tr> <!--footnote-bar-->	

</table> <!--bottom_content-->

<div style='display:none' id="bottom-corner-r"><div id="bottom-corner-l"></div></div>

</div> <!--main_page-->

