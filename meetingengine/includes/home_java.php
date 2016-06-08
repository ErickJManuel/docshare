<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


?>

<div class='heading1'>Java for Mac OS X</div>

Presenter Java Client uses <b>"vpresent.jnlp"</b> file to launch <b>Java Web Start</b>.
<p>
<a target=_blank href="http://support.apple.com/downloads/Java_for_Mac_OS_X_10_5_Update_4">Java for Mac OS X 10.5 Update 4</a> broke the file type association between JNLP files and Java Web Start.
Instead of launching Java, the file is opened in a text editor.
<p>
Until Apple fixes this problem, you can manually associate the file with Java Web Start:
<ol>
<li>Find a <b>vpresent.jnlp</b> file in your downloads folder or your desktop. The file is downloaded when you start "screen sharing" from your meeting viewer.</li><br>

<li>Right click the JNLP file (or hold down the ctrl key) and select <b>Get Info</b>.</li><br>

<li>In the <b>Open with</b> menu, select <b>Other...</b></li></br>

<li>In the choose application window, select:<br>
<b>Macintosh HD/System/Library/CoreServices/Java Web Start</b></li><br>

<li>Check the box for <b>Always Open With</b> and then click the <b>Add</b> button.</li><br>

<li>In the Get Info window, click the <b>Change All...</b> button to change the file association for all JNLP files.</li><br>

</ol>
When a JNLP file is opened the next time, Java should be launched.