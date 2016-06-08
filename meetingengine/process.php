<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common.php");
require_once("includes/brand.php");
require_once($GLOBALS['LOCALE_FILE']);
require_once("includes/common_text.php");

//if (GetSessionValue('member_id')=='') {
if (GetSessionValue('member_brand')!=$gBrandInfo['id']) {
	require_once("includes/go_signin.php");
}

if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';

function HideLoader() {
	print <<<END
<script type="text/javascript">
<!--
	document.getElementById('loader').style.display='none';
//-->
</script>
END;
	flush();
}

function PrintProgressMessage($msg) {
	print <<<END
<script type="text/javascript">
<!--
	document.getElementById('loader_text').innerHTML="$msg";
//-->
</script>
END;
	flush();
}

function PrintEndMessage($msg) {
	print <<<END
<script type="text/javascript">
<!--
	document.getElementById('loader_text').innerHTML="$msg";
	document.getElementById('loader_icon').className="inform_icon";
//-->
</script>
END;
	flush();
	sleep(3);
}

?>

<html>
<head>
<title>PHP Progressbar</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="<?php echo SITE_URL."themes/".$GLOBALS['THEME']?>/" rel="stylesheet" type="text/css">

</style>
</head>

<body>
<table id='loader' width=100% height=300px>
<tr><td width=40%>&nbsp;</td>
<td id='loader_icon' class="wait_icon">&nbsp;</td>
<td id='loader_text'>Loading...</td>
</tr>
</table>
<?php  

	// Flush all buffers
	ob_end_flush();  
	flush();
	
	$redirect_url='';
	
	if ($GLOBALS['SUB_PAGE']==PG_PROCESS_AUDIO)
		include_once("includes/process_audio.php");

	if($redirect_url != "") { ?>
		<script type="text/JavaScript">
		<!--
		top.location.href= "<?php echo $redirect_url; ?>";
		//-->
		</script>
<?php } ?>

</body>
</html>
