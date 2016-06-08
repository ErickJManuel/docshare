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


$hasWindowsClient=true;
if ((defined('ENABLE_WINDOWS_CLIENT') && constant('ENABLE_WINDOWS_CLIENT')=='0'))
{
	$hasWindowsClient=false;
} else {	
	require_once("dbobjects/vviewer.php");
	
	$brandViewer=new VViewer($gBrandInfo['viewer_id']);
	$brandViewerInfo=array();
	$brandViewer->Get($brandViewerInfo);
	
	if (isset($brandViewerInfo['presenter_client']) && $brandViewerInfo['presenter_client']=='JAVA')
		$hasWindowsClient=false;

}
@include_once("download/vpresent_version.php");

$fileSize=@filesize("download/$vpresent_installer");
$vpresent_size=round(($fileSize/(1024*1024))*100)/100;

$brandUrl=$GLOBALS['BRAND_URL'];
$vinePage=$brandUrl."?page=".PG_HOME_VINE;
if (SID!='')
	$vinePage.="&".SID;
$appleRemotePage=$brandUrl."?page=".PG_HOME_ARD;
if (SID!='')
	$appleRemotePage.="&".SID;
$tvncPage=$brandUrl."?page=".PG_HOME_TVNC;
if (SID!='')
	$tvncPage.="&".SID;

$installer=SITE_URL."download/download.php?installer=".$vpresent_installer;
//$installer=SITE_URL."download/".$vpresent_installer;
//$zipFile=SITE_URL."download/".str_replace(".exe", ".zip", $vpresent_installer);
$adminInstaller=SITE_URL."download/download.php?installer=".$vpresent_admin_installer;

$javaPage=$brandUrl."?page=".PG_HOME_JAVA;
if (SID!='')
	$javaPage.="&".SID;
	
//$mirageDriver=SITE_URL."download/download.php?installer=dfmirage-setup-2.0.300.0.exe";
$mirageDriver="http://www.demoforge.com/dfmirage.htm";

?>
<div class='heading1'><?php echo _Text("Presenter Client")?></div>

<?php

if ($hasWindowsClient) {
	print <<<END
	
<div>
Certain presenter functions require a Presenter Client. You have two options:
<ul>
<li><b>Windows Client</b> -- For Windows users only. Installation required.</li>
<li><b>Java Client</b> -- For all users. Java 1.5+ required.</li>
</ul>
</div>

<p>
<hr>

<div style="float: left; width: 54px; padding-top: 5px;"><img src="images/windows_logo.png"></div>
<div class='heading2'>Presenter Windows Client <br>(Windows 7, Vista, XP and 2000)</div>
<p style="clear: both">
The presenter software is required for:
<ul>
<li>Screen sharing</li>
<li>Importing PowerPoint presentations</li>
<li>Sending desktop snapshots</li>
</ul>

<div class='heading3'>Choose an installer</div>
<ul class="itemlist">
<li>
<input type="button" value="Download Default Installer" onClick="window.location='$installer'" >
<span class='info_text'>Version: <b>$vpresent_version</b>&nbsp;&nbsp; Size: <b>$vpresent_size MB</b></span>
</li>
<p>
<li>
For computer administrators only. Install for all users of a computer.<br>
<input type="button" value="Download Admin Installer" onClick="window.location=' $adminInstaller'" >
</li>
</ul>

You are recommended to <b>download and install <a target=_blank href="$mirageDriver">DFMirage Display Driver</a></b>, which fixes some frame update problems and improves screen capture performance.</p>

You may be asked to launch the application <b>"VPresent"</b> when you perform the above presenter functions. You should allow it.
<p>
If you experience problems with your network settings, you should open <b>"VPresent Network Settings"</b> under Windows Start Menu -> All Programs -> VPresent, and change the settings manually.
If you continue to have problems, you should use <b>Presenter Java Client</b> instead.
<p></p>

<br>
<hr>

END;
}
?>

<div style="float: left;  width: 54px; "><img src="images/java_logo.png"></div>
<div class='heading2'>Presenter Java Client <br>(All platforms)</div>
<p style="clear: both">
<b>Mac OS X users:</b> see <a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $javaPage?>">important notes for Java for Mac OS X 10.5 Update 4</a>!
<p>
You must have Java 1.5 or above installed and Java should be enabled for your web browser.
<ul>
<li><a targer=_blank href="http://www.javatester.org/version.html"><b>Java Tester</b></a> (check your Java version)</li>
<li><a target=_blank href="http://www.java.com/en/download/index.jsp"><b>Download Java</b></a> (Sun's Java download page)</li>
</ul>
<p>
You will be prompted to download a Java Web Start file <b>(vpresent.jnlp)</b> when you perform the following presenter functions:
<ul>
<li>Screen sharing</li>
<li>Sending desktop snapshots</li>
</ul>

You may be promoted to allow the Java application access to your computer. You should <b>allow</b> it.
<p></p>

<div class="heading3">Firefox</div>
You may be asked to choose a program to open the file. You should choose <b>"Java Web Start"</b> and check the box <b>"Do this automatically..."</b>.
<p>
<img src="images/firefox_jnlp.jpg">
<p>
<div class="heading3">Google Chrome</div>
Click on the "vpresent.jnlp" file at the lower-left corner of the browser window to open the file. Select "Always open files of this type" to automatically open it.
<p>
<img src="images/chrome_jnlp.jpg">
</p>



