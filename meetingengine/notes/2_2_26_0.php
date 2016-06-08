<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
?>

<h3>New features</h3>
<ul>
<li>Redesigned and simplified meeting viewer tool bar and icons.</li>
<li>Polling feature is added to Pro-50 above licenses and is available under My Library tab.</li>
<!-- <li>All branded sites now have the option to add iPhone support using Touch Meeting 1.4 app.</li> -->
<li>Launch Speed Test from the meeting viewer Help menu. Add test result ratings (Good, Fair, Slow.) </li>
<li>Added additional video conference bandwidth settings: 320x240-240kbps and 640x480-500kbps.</li>
<li>Updated html code for embedding the Flash player and display the player download link if it is not installed.</li>
<li>Added new emoticons: "faster", "slower", "louder", "softer".</li>
<li>Add option to remember Java screen sharing size and quality settings.</li>
<li>Improved concurrent port usage reporting speed.</li>
</ul>

<h3>Bugs fixed</h3>
<ul>
<li>Turn off meeting viewer embedding because of two problems:<br>
1. The viewer uses Javascript to do certain work and the embedding doesn't include the Javascript code.<br>
2. The viewer may run on any hosting server assigned to the user's group and the embedding code is bound to the login site.
</li>
<li>When rewind or fast forward a recording, past chat messages and attendees do not show up.</li>
<li>Fixed many style sheet problems with meeting web site. In some language (Spanish), the Admin/API page was not visible.</li>
<li>Fixed various html warnings and errors.</li>
<li>On some rare occasions, the screen sharing appears to be frozen or paused to the remote participants when the presenter is still sharing the screen. The bug is caused by a php script not locking a screen sharing session file properly.</li>
<li>On some occasions, if presenter's connection fails during uploading of screen sharing frames, participants may not be able to continue in the meeting even after the presenter's connection has returned. Participants need to close all browser windows before rejoining the meeting.</li>
<li>When downloading a recording that contains more than 1024 files, the PHP zip handler is not able to add more files to the zip file on some server.</li> 
<!-- <li>Worked around a Flash wmode bug that prevents entering of non-Latin characters in the viewer's Messages window.</li> -->
<li>Extend audio recording conversion wait time to 180 seconds to accommodate long conversion.</li>
</ul>

