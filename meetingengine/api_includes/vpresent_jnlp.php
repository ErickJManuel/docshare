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

require_once('server_config.php');
require_once("dbobjects/site_url.php");
require_once("dbobjects/vtoken.php");

	$baseUrl=SITE_URL."vpresent_java";
	$title="VPresent";
	
	$vendorName=$_SERVER['HTTP_HOST'];
	if ($vendorName=='')
		$vendorName='Persony Inc.';
	
	if (isset($_GET['vendor']))
		$vendorName=$_GET['vendor'];
	
	if (!isset($_GET['user']))
		die("user not set");
	if (!isset($_GET['meeting']))
		die("meeting not set");
		
		
	$size="800*600";
	if (isset($_GET['size']))
		$size=$_GET['size'];
		
	$quality="50";
	if (isset($_GET['qaulity']))
		$quality=$_GET['qaulity'];
	
	$loc="center";
	
	// for screenshot app only
	$disableShareBtn=false;
	if (isset($_GET['disable_share']))
		$disableShareBtn=true;

	$screenShot='';
	$userId=$_GET['user'];
	// get a meeting access token
	$meetingId=$_GET['meeting'];
	// create a token to be used for API authentication
	$brandName=GetSessionValue('brand_name');
	$token=VToken::AddToken($brandName, $meetingId, "");
	$sessionUrl=SITE_URL."api.php?cmd=GET_SHARING_INFO&amp;meeting_id=$meetingId&amp;user=$userId&amp;token=$token";
	if (GetArg('screenshot', $screenShot))
		$mainClass="com.grapesoftware.vnc2flash.ScreenshotMain";
	else
		$mainClass="com.grapesoftware.vnc2flash.VPresent";
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	// no-cache seems to cause problems with IE; use private instead
//	header('Cache-control: no-cache, must-revalidate');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: application/x-java-jnlp-file");
//	header("Content-Disposition: attachment; filename=vpresent.jnlp");	
	header("Content-Disposition: inline; filename=vpresent.jnlp");	
	

	echo ("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");

/*
<jnlp spec="1.0+"
      codebase="<?php echo $baseUrl?>"
      href="vpresent.php">
*/

/*
Info on jnlp params:
debug - true/false
userId - persony user id 
sessionInfoUrl - session xml url all 

highQuality - (0-100 - jpg compression level)
mediumQuaity
lowQuality

segmentSize - in bytes.
maxBps - in bytes per second.
*/

if ($_SERVER["REMOTE_ADDR"] == "24.22.33.249") { // || $_SERVER["REMOTE_ADDR"] == "50.77.183.225") {
	$vpresent = "vpresentv7.jar";
	$suffix = "v7";
} else {
	$vpresent = "vpresent.jar";
	$suffix = "";
}
?>

<jnlp spec="1.0+" codebase="<?php echo $baseUrl?>" >
      
  <information>
    <title><?php echo $title?></title>
    <vendor><?php echo $vendorName?></vendor>
  </information>
  
  <security>
    <all-permissions/>
  </security>
  
  <resources>
    <j2se version="1.5+" href="http://java.sun.com/products/autodl/j2se"/>
    <j2se version="1.5+"/>
    <jar href="<?php echo $vpresent; ?>" main="true" download="eager"/>
    <jar href="log4j-1.2.9<?php echo $suffix; ?>.jar" download="eager"/>
    <jar href="dom4j-1.6.1<?php echo $suffix; ?>.jar" download="eager"/>
    <jar href="transform-swf-2.1.5<?php echo $suffix; ?>.jar" download="eager"/>
  </resources>
  
  <application-desc main-class="<?php echo $mainClass?>">
    <argument>debug</argument>
    <argument>false</argument>
    <argument>userId</argument>
    <argument><?php echo $userId?></argument>
    <argument>sessionInfoUrl</argument>
    <argument><?php echo $sessionUrl?></argument>
<?php
/*
    <argument>lowQuality</argument>
    <argument>30</argument>
    <argument>mediumQuality</argument>
    <argument>60</argument>
    <argument>highQuality</argument>
    <argument>80</argument>
*/
?>
    <argument>sharingSize</argument>
    <argument><?php echo $size?></argument>
    <argument>sharingLoc</argument>
    <argument><?php echo $loc?></argument>
    <argument>quality</argument>
    <argument><?php echo $quality?></argument>
    <argument>segmentSize</argument>
    <argument>50000</argument>
    <argument>maxBps</argument>
    <argument>40000</argument>
<?php
if ($screenShot=='1' && $disableShareBtn) {
	print <<<END
    <argument>disableShare</argument>
    <argument>1</argument>
END;
}
?>	
  </application-desc>
  
</jnlp>

<?php exit(); ?>
