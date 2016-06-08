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

<style type="text/css">			#flashcontent {		height: 100%;		width: 100%;	}	</style>

<div id="flashcontent">	<!-- the following code will be used only if SWFObject fails to detect the Flash player, which seems to happen on IE in Vista -->
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
</div>

<?php
/*
<script type="text/javascript"><!--		var so = new SWFObject("<?php echo $swfFile; ?>", "<?php echo $swfName?>", "<?php echo $width?>", "<?php echo $height?>", "<?php echo $requiredVersion?>", "#FFFFFF");	so.addParam("flashvars", "<?php echo $flashVars?>");	so.useExpressInstall("expressinstall.swf");
	so.write("flashcontent");--></script>*/?>