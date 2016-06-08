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

$apiPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_API;
if (SID!='')
	$apiPage.="&".SID;

$examplePage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&code_page=".rawurlencode("sso_doc/sso_authenticate.php");
if (SID!='')
	$examplePage.="&".SID;
	
?>

<div class='heading1'>Single Sign On Documentation</div>

Single Sign On (SSO) allows a client application to automatically sign in an authenticated user 
to the web conferencing site and eliminates the need to have the user sign in again manually.
<!--
The SSO is based on <a target=_blank href='http://www.opensam.org/4%20SSO%20and%20Provisioning.html'>OpenSAM SSO</a>.
-->
<p>
Single Sign On Steps:
<ol>
<li>The client application accesses the web conferencing site using HTTP GET with the CGI parameters:
<blockquote><b>
[web_conferencing_site_url]?<br>
StorageServerUrl=[url]&StorageUserName=[login]&<br>
StoragePassword=[password]&StorageSessionId=[session]
</b>
</blockquote>
<ul>
<li><b>[web_conferencing_site_url]</b>: URL of the web conferencing site to sign in the user to.</li>
<li><b>[url]</b>: URL of the authentication page provided by the client application. 
The page must return a HTTP status code depending on the authentication results (see below.) The url should be a complete url including 'http' but should not contain '?' or '&'.</li>
<li><b>[login]</b>: The login account name, typically the email address, of the user.</li>
<li><b>[password]</b>: Password for the user (only needed if the user doesn't exist.)</li>
<li><b>[session]</b>: Session id or custom data of the client application to identify this session (optional.) The data will be passed back to the client application in Step 3 below.</li>
</ul>
<p>
Additional parameters described in Step 5 and 6 can be added to the HTTP request.
<li>The web conferencing site checks if [url] is authorized. A url is considered authorized if the host name part of the url (e.g. www.mysite.com) is 
listed under the 'SSO Host Name' in the <a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $apiPage?>">Administration/API</a> page (Admin account required) of this site.</li>
<p>
<li>If the url is authorized, the web conferencing site accesses [url] using HTTP GET with the following CGI parameters:
<blockquote>
<b>[url]?StorageSessionId=[session]&StorageUserName=[login]</b>
</blockquote>
where [url], [session], and [login] are CGI parameters from Step 1.
<p>

<li>Upon launched, [url] should respond with the following HTTP status code:</li>
<p>
<ul>
<li><b>200</b>: if the user is authorized, or</li>
<li><b>401</b>: if the the user is not authorized or the CGI parameters are missing or invalid.</li>
</ul>
<p style="font-style:italic; font-weight:bold">If no CGI paramters are provided, [url] must respond with a 401 status code or the url will be regarded as a non-authenticaing url.</p>
<p>
<a target="<?php echo $GLOBALS['TARGET']?>" href="<?php echo $examplePage?>">Show PHP example code for the authentication page.</a>
</p>
<li>Upon receiving HTTP 200 status code from [url], the web conferencing site checks if the user account already exists.
If the user does not exist, it automatically creates a user account using <b>StorageUserName</b>, <b>StoragePassword</b>, and the optional parameters below. 
These parameters should be passed in along with other parameters in Step 1.
</li>
<p>
<ul>
<li><b>StorageUserEmailAddress</b>: email address of the user. The email address is only needed if it is different than StorageUserName.</li>
<li><b>StorageOrg</b>: group id of the user. The group id must belong to a valid group of the site 
(see <a target="<?php echo $GLOBALS['TARGET'];?>" href='<?php echo $GLOBALS['BRAND_URL']."?page=HELP_REST&topic=Objects&sub1=group";?>'>API Documentation|Objects|group</a>
and <a target="<?php echo $GLOBALS['TARGET'];?>" href='<?php echo $GLOBALS['BRAND_URL']."?page=ADMIN_GROUPS";?>'>Administration|Groups</a>).
The default group is used if the paramater is not provided.
</li>
<li><b>StorageProvisionSkel</b>: license_code of the user 
(see <a target="<?php echo $GLOBALS['TARGET'];?>" href='<?php echo $GLOBALS['BRAND_URL']."?page=HELP_REST&topic=Objects&sub1=licenses";?>'>API Documentation|Objects|licenses</a>
and <a target="<?php echo $GLOBALS['TARGET'];?>" href='<?php echo $GLOBALS['BRAND_URL']."?page=ADMIN_ACCOUNTS";?>'>Administratoin|Accounts</a>). 
If the parameter is not provided, a trial account will be assigned to the user.</li>
</ul>
<p>
<li>The user is signed in and then redirected to the default "My Meetings" page or the redirect URL parameter provided below.
The paramter should be passed in along with other parameters in Step 1.
</li>
<p>
<ul>
<li><b>PSRedirectUrl</b>: The url to redirect the user to after the sign-on. The url should be url-encoded.</li>
</ul>
<p>
<a target="<?php echo $GLOBALS['TARGET']?>" href="rest_doc/sso_test.zip">Download PHP example code for using SSO to sign in and start a meeting.</a>


</ol>

