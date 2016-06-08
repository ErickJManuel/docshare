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


?>

<div class='heading1'>Screen Sharing with Apple Remote Desktop (MacOS X)</div>

<ol>
<li class='download-step'>Start Apple Remote Desktop</li>
<ol>
<li>Open <strong>System Preferences/Sharing</strong> on the Mac</li>
<li>Click on "<strong>Services</strong>" and check <strong>Apple Remote Desktop</strong></li>
<li>In "<strong>Access Privileges...</strong>" dialog box, Check "<strong>VNC Viewers may control screen with password</strong>."</li>
<li>Type in your password.</li>
<li>Click "OK"</li>
</ol>
<br>
<img src="images/ard1.jpg">
<br><br>
<img src="images/ard2.jpg">


<li class='download-step'>Set Screen Resolution (Optional)</li>
<ul>
<li>Open <strong>System Preferences/Displays</strong> on the Mac to set the screen resolution.</li>
<li><strong>1024x768</strong> or lower is recommended for better performance.</li>
<li>Set "Colors" to "<strong>Thousands</strong>" or "<strong>Millions</strong>." "256 Colors" is not supported.</li>
</ul>
<br>
<img src="images/mac_display.jpg">

<li class='download-step'>Start Screen Sharing</li>
<ul>
<li>Click on the "Share Screen" icon in the meeting viewer.</li>
<li>Click "OK" in the Screen Sharing dialog box.</li>
</ul>
<br>
<img src="images/vine2.jpg">

<li class='download-step'>Connect to Apple Remote Desktop</li>
<ul>
<li>A Java app "VPresent" should be downloaded the first time. The app will automatically start.</li>
<li>The Host filed should be "<strong>localhost</strong>"</li>
<li>Enter the Port number <strong>5900</strong> for Apple Remote Desktop.</li>
<li>Click "Connect" in the dialog box.</li>
<li>Enter the password for Apple Remote Desktop. The password should match the one you enter in Step 1.</li>
</ul>
<br>
<img src="images/ard3.jpg">
<br><br>
<img src="images/vine4.jpg">

<li class='download-step'>Start/Pause/Stop Screen Sharing</li>
<ul>
<li>Screen sharing is automatically started after the connection.</li>
<li>Click "<strong>Pause</strong>" to pause screen sharing. Click "<strong>Resume</strong>" to continue.</li>
<li>Click "<strong>Stop</strong>" or close "VPresent" to stop screen sharing.</li>
</ul>
<br>
<img src="images/vine5.jpg">



</ol>



