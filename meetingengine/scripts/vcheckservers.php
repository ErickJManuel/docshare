<?php

	// $serverUlr should be defined in the including file
	$getUrl=$serverUrl."api.php?cmd=GET_SERVERS&meeting_id=".$_GET['meeting_id'];
	
/*
	$resp=@file_get_contents($getUrl);
	$servers=array();
	if ($resp!==false) {
		$items=explode("&", $resp);
		//response: url_0=...&url_1=...&url_2=...
		//url_0 is the master server
		//url_1, url_2 are slave servers
		if ($items) {
			foreach ($items as $anItem) {
				$itemVals=explode("=", $anItem);
				if (ereg("url", $itemVals[0])) {
					$servers[]=$itemVals[1];
				}
			}
		}
	} else {
		die("Couldn't get a response from the server '$getUrl'.");		
	}
			
	// see if more than 1 server is returned
	// if only one server is returned, we don't need to check
	if (count($servers)<=1) {
		return;
	}
*/
	// create an html page with Javascript to check the server connections from the client side
	$baseDir='';		
	$flashVars="server_url=".rawurlencode($getUrl);
	$flashVars.="&report_url=".rawurlencode("viewer.php?response=1&".$_SERVER['QUERY_STRING']);
	$swfFile="servercheck.swf";
	$swfName="servercheck";
	$requiredVersion="7";
	$width=560;
	$height=400;
	$flashVars.="&show_ui=false";	$flashVars.="&".rand();		

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
<?php
/*
<script type="text/javascript" src="swfobject.js"></script><script type="text/javascript">
swfobject.registerObject("<?php echo $swfName?>", "9.0.28", "expressInstall.swf");
</script>
*/?><style type="text/css">	body {		height: 100%;		margin: 0;		padding: 0;		background-color: #fff;	}
	div.main_text
	{
		text-align: left;
		width: 500px;
		padding: 30px 10px 10px 10px;
		margin: 0;
		font-weight: bold;
		font-size: 16px;
		font-family:Arial;
		color: #777;
	}	#flashcontent {		height: 100%;		width: 100%;	}</style>

<script type="text/javascript"><!--function HideMessage(){	document.getElementById('loader').style.visibility='hidden';
}//--></script>


</head>


<body>


<center>

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

<script type="text/javascript"><?php/*<!--	var so = new SWFObject("<?php echo $swfFile.'?'.rand(); ?>", "<?php echo $swfName?>", "<?php echo $width?>", "<?php echo $height?>", "<?php echo $requiredVersion?>", "#FFFFFF");	so.addParam("flashvars", "<?php echo $flashVars?>");	so.useExpressInstall("expressinstall.swf");
	so.write("flashcontent");//-->*/?></script>

</center>

</body>
</html>


<?php
		exit();
		
//	}

?>