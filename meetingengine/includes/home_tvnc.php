<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


?>

<div class='heading1'>Screen Sharing with TightVNC Server (Linux or Unix)</div>


<ol>

<li class='download-step'>Download and install TightVNC Server</li>
<ul>
<li><a target=_blank href="http://www.tightvnc.com/download.html">Download TightVNC Server</a></li>
</ul>


<li class='download-step'>Start TightVNC Server</li>
<ol>
<li>Set the server to listen on the default port <strong>5900</strong> (other port works too.) You do not need to open the port to outside connections. The port is only used by the presenter software to connect locally.</li>
<li>Type in your password for "<strong>Incoming connections</strong>" and check "<strong>Accept socket connections</strong>."</li>
</ol>
<br>
<img src="images/tvnc.jpg">


<li class='download-step'>Start Screen Sharing</li>
<ul>
<li>Click on the "Share Screen" icon in the meeting viewer.</li>
<li>Click "OK" in the Screen Sharing dialog box.</li>
</ul>
<br>
<img src="images/vine2.jpg">

<li class='download-step'>Connect to TightVNC server</li>
<ul>
<li>A Java app "VPresent" should be downloaded the first time. The app will automatically start.</li>
<li>The Host filed should be "<strong>localhost</strong>"</li>
<li>Enter the Port number <strong>5900</strong> for the TightVNC server.</li>
<li>Click "Connect" in the dialog box.</li>
<li>Enter the password for the TightVNC server. The password should match the one you enter in Step 1.</li>
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



