<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 */

$flashVars=isset($_GET['flashvars'])?$_GET['flashvars']:'';
$swfFile="speedtest.swf";
$swfName="speedtest";
$requiredVersion="7";
$width=510;
$height=460;

//<script type="text/javascript" src="swfobject.js"></script>
?>

<style type="text/css">

<div id="flashcontent">
  	<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
			id="<?php echo $swfName?>" width="<?php echo $width?>" height="<?php echo $height?>">
			<param name="movie" value="<?php echo $swfFile."?".$flashVars?>" />
			<param name="quality" value="best" />
			<param name="bgcolor" value="#ffffff" />
			<param name="allowScriptAccess" value="sameDomain" />
			
			<object type="application/x-shockwave-flash" data="<?php echo $swfFile."?".$flashVars?>"
				quality="best" bgcolor="#ffffff"
				width="<?php echo $width?>" height="<?php echo $height?>" name="<?php echo $swfName?>"
				play="true"
				loop="false"
				quality="best"
				allowScriptAccess="sameDomain">

				<div class="noflash">
					<p>You need the latest version of the Adobe Flash Player.<p/>
					<p><a target=_blank href="https://www.adobe.com/go/getflashplayer"><img src="https://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
				</div>
			
			</object>
			
	</object>


<?php
/*
<script type="text/javascript">
	so.write("flashcontent");