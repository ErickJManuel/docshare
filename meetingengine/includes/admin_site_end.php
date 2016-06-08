<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */



function PrintMsg($msg) {
print <<<END
<script type="text/javascript">
<!--
	document.getElementById('message').innerHTML+="$msg<br>";
//-->
</script>
END;
flush();
}

	
$configFile='site_config.php';

GetArg('version_number', $versionNumber);

// find the management server source url of this version
$errMsg=VObject::Select(TB_VERSION, "number='$versionNumber'", $verInfo);
if ($errMsg!='') {
	PrintMsg($errMsg);
	return;
}
	
if (!isset($verInfo['id'])) {
	PrintMsg("Version not found.");
	return;
}

$srcUrl=$verInfo['source_url'];
if (isset($_SERVER['HTTPS']) && $verInfo['ssl_source_url']!='')
	$srcUrl=$verInfo['ssl_source_url'];

$query="brand_id='$brandId'";
$errMsg=VObject::SelectAll(TB_WEBSERVER, $query, $result);
if ($errMsg!='') {
	PrintMsg($errMsg);
	return;
}

while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

	$webserver=new VWebServer($row['id']);
	$serverInfo=array();
	$errMsg=$webserver->Get($serverInfo);
	
	if ($errMsg!='') {
		PrintMsg($errMsg);
		return;
	}
	
	PrintMsg("Updating ".$serverInfo['url']."...");

	$installUrl=$serverInfo['url']."vinstall.php";
	$data="server=".$srcUrl.
		"&brand=".$brandName."&win_title=".rawurlencode($brandInfo['product_name']).
		"&login=".$serverInfo['login']."&password=".$serverInfo['password'].
		"&version=".$versionNumber.
		"&no_update=1&install=1&silence=1";

	$content=HTTP_Request($installUrl, $data, 'POST', 60);
	
	if ($content==false) {
		PrintMsg("Couldn't get respose from $installUrl");
	} elseif ($content!='OK') {
		PrintMsg("ERROR in getting response from $installUrl.");
		PrintMsg("Response=".htmlspecialchars($content));
	} else {
		$winfo=array();
		$winfo['installed_version']=$versionNumber;
		$webserver->Update($winfo);	
		PrintMsg("Update completed.");
	}
	
	PrintMsg ("<br>");
}

PrintMsg ("You may need to clear your browser's cache to see the changes.<br>");

/*
$retUrl=$GLOBAL['BRAND_URL']."?page=ADMIN_SITE&rand=".rand();
if (SID!='')
	$retUrl.="&".SID;

$target=$GLOBAL['TARGET'];
PrintMsg ("<a target=\"$target\" href='$retUrl'>Return</a>");
*/

?>