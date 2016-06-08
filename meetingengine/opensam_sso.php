<?PHP
/*   This is a BSD style permissive license.
*    This module is original work by the author.
*
* Copyright (c) 2007, iNetOffice, Inc.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of iNetOffice nor the
*       names of its contributors may be used to endorse or promote products
*       derived from this software without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY iNetOffice ``AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL iNetOffice BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* Author: Tom Snyder, iNetOffice, Inc.
*/


/* -- opensam_sso.php --
 * -- Version 1.00.a  - updated 8/24/07. --
 *
 * SEE: http://opensam.org/4%20SSO%20and%20Provisioning.html
 *
 * This module contains a utility routine that implements OpenSAM Single Sign On.
 *
 * Customize and integrate this module with your authentication routines.
 */
 
 /* MAIN ENTRY POINT.
  *
  * Call this routine from the session initialization modules of your service.
  * If this routine succeeds then establish a new logged-in session to your service
  * and correlate it with a Session ID Cookie that you send back to the client.
  *
  * PARAMETERS 
  *
  *   $StorageServerUrl
  *   $StorageUserName
  *   $StorageSessionId
  *   $StoragePassword
  *     These are the standard OpenSAM authentication credentials and are
  *     OPTIONAL. If you pass in NULLs we will first try cgi parameters and then
  *           _SESSION variables to get the values.
  *
  *     These parameters are also OUT parameters through which we return any values that we
  *     obtained.
  *
  *   $OUT_StorageDomainAndPath
  *     The domain.tld/path portion extracted from the StorageServerUrl.
  *     ** VERY IMPORTANT ** You must confirm this StorageDomainAndPath is correct for the account
  *                          associated with StorageUserName. Although host names can vary for
  *                          log in, you must ensure neither the domainname.tld portion or
  *                          the path portion after that varies to be sure the login was authentic.
  *
  *   $OUT_WebDAVUrlParameters
  *     The CGI parameters that must be appended to the end of any WebDAV request URL to transmit
  *     additional account info to the WebDAV server. It includes the leading ? and is already
  *     URL encoded. Upon success, simply store this and then append it prior to every subsequent
  *     WebDAV request you generate.
  *
  *   $OUT_HTTPStatus
  *     The HTTP status code of the last request. Log or trace this to help diagnose SSO failures.
  *
  * RETURNS:  <> true      if user is successfully authenticated.
  *           <> 0 (zero)  if no login attempt was made because no SSO params were found.
  *           <> false     if login attempt failed because of bad username or password, but all else was satisfactory.
  *           <> string    error message if SSO attempt was made but failed for some reason other than bad username/password.
  *
  *           use if( $retval === true ) to check for success. You must use the triple = comparison.
  */
  
function opensam_sso_authenticate( &$StorageServerUrl, &$StorageUserName, &$StorageSessionId, &$StoragePassword, &$OUT_StorageDomainAndPath, 
 &$OUT_WebDAVUrlParameters, &$OUT_HTTPStatus ) {

  $OUT_HTTPStatus = -99; // indicates no status value available.
  
  // Gather main credentials:
  // notice: StoragePassword and StorageSessionId are alternative ways of logging on.
  //  The StoragePassword travels via HTTP Authenticate headers, the StorageSessionId travels through CGI params.
  //  We also support a special SForceSessionId which travels via CGI params and authenticates us against  
  //     a salesforce.com launch.
  
  $StorageServerUrl_obtained = opensam_obtain_one_sso_param( "StorageServerUrl", $StorageServerUrl );
  $StorageUserName_obtained  = opensam_obtain_one_sso_param( "StorageUserName",  $StorageUserName );
  $StorageSessionId_obtained = opensam_obtain_one_sso_param( "StorageSessionId", $StorageSessionId );
  $StoragePassword_obtained  = opensam_obtain_one_sso_param( "StoragePassword",  $StoragePassword );
  if( empty( $StorageSessionId_obtained ) ) {
    // Try the salesforce one:
    $StorageSessionId_obtained = opensam_obtain_one_sso_param( "StorageSForceSessionId", null );
  }  
  
  // If no SSO params are available at all, then just give up with the 0 didn't-try indicator:
  if( empty( $StorageServerUrl_obtained ) 
   && empty( $StorageUserName_obtained ) 
   && empty( $StorageSessionId_obtained ) 
   && empty( $StoragePassword_obtained ) ) {
    return( 0 );  // NO attempt made.
  }
    
  // Check that we have all the params we need. If not, then give the caller a decent shot at figuring it out.
  if( empty( $StorageServerUrl_obtained ) ) return( "LOGIN FAILED. Blank Server URL. (StorageServerUrl CGI parameter)" );
  if( empty( $StorageUserName_obtained ) )  return( "LOGIN FAILED. Blank user name.  (StorageUserName CGI parameter)" );
  if( empty( $StorageSessionId_obtained ) 
   && empty( $StoragePassword_obtained ) ) {
      return( "LOGIN FAILED. Blank session id and password. (StorageSessionId or StoragePassword CGI parameters)" );
  }
 
  // make a tmp copy because we might add to it.
  $StorageServerUrl_working_copy = $StorageServerUrl_obtained;
  
  // Analyze the special SalesForce OpenSAM SSO values, if any:
  $StorageSForceUrl_obtained       = opensam_obtain_one_sso_param( "StorageSForceUrl", null );
  $StorageSForceSessionId_obtained = opensam_obtain_one_sso_param( "StorageSForceSessionId", null );
  $StorageSForceOrg_obtained       = opensam_obtain_one_sso_param( "StorageSForceOrg", null );
  // see if it appears like any attempt at SalesForce SSO is being made:
  if( !empty( $StorageSForceUrl_obtained ) || !empty( $StorageSForceSessionId_obtained ) || !empty( $StorageSForceOrg_obtained ) ) {
    // Make sure all required values are available:
    if( empty( $StorageSForceUrl_obtained ) )       return( "LOGIN FAILED. Blank SalesForce Server URL. (StorageSForceUrl CGI parameter)" );
    if( empty( $StorageSForceSessionId_obtained ) ) return( "LOGIN FAILED. Blank SalesForce Session ID. (StorageSForceSessionId CGI parameter)" );
    if( empty( $StorageSForceOrg_obtained ) )       return( "LOGIN FAILED. Blank SalesForce Org. (StorageSForceOrg CGI parameter)" );

    // Assemble those values into the special WebDAV URL. Since we have more values than HTTP Authenticate supports, they travel
    // to the WebDAV server as CGI params within the URL itself.
    $WebDAVUrlParameters =  "?partner_id=sforce&url=" . rawurlencode($StorageSForceUrl_obtained) .
                            "&StorageSessionId=" . rawurlencode( $StorageSForceSessionId_obtained ). 
                            "&org=" . rawurlencode( $StorageSForceOrg_obtained ) .
                            "&StorageUserName=" . rawurlencode( $StorageUserName_obtained );
  }
  else if( !empty( $StorageSessionId_obtained ) ) {
    // Put the session ID in the URL:
	$WebDAVUrlParameters = "?StorageSessionId=".rawurlencode( $StorageSessionId_obtained ) .
							"&StorageUserName=" . rawurlencode( $StorageUserName_obtained );
  }

  //
  // First: double check the server to make sure it is configured to require authentication. It is very easy to
  //        misconfigure servers to serve up pages publicly. A server may also be configured to redirect to a login
  //        page which would appear to us as a successful request.
  //
  //        Bottom line: make sure request fails when credentials are not passed.
  //
  //                                                                    YES: we wish to pass in nulls here.
  $curlobj = opensam_create_and_prepare_curl( $StorageServerUrl_working_copy, null, null );
  if( !is_resource( $curlobj ) ) return( $curlobj );  // ERROR return, $curlobj is a string.
	
  $curl_ret = curl_exec( $curlobj );
  $curl_exec_info = curl_getinfo( $curlobj );
  if( $curl_exec_info['http_code'] != 401 ) {
    return( "LOGIN FAILED. StorageServerUrl does not appear to be an authenticating server. Unauthenticated request status was ".
      $curl_exec_info['http_code']." - should have been 401. URL utilized: $StorageServerUrl_working_copy" );
  }


  // 
  // Second: Server is a valid authenticating server. Now pass it the credentials we have and see if
  //         it succeeds.
  //

  // Append the special authentication CGI parameters to the end of the URL:
  if( !empty( $WebDAVUrlParameters ) ) {
    $StorageServerUrl_working_copy .= $WebDAVUrlParameters;                           
  }
	
  $curlobj = opensam_create_and_prepare_curl( $StorageServerUrl_working_copy, $StorageUserName_obtained, $StoragePassword_obtained );
  if( !is_resource( $curlobj ) ) return( $curlobj );  // ERROR return, $curlobj is a string.  
	
  // Perform the GET request and see if the server allows it:
  $curl_ret = curl_exec( $curlobj );
  $curl_exec_info = curl_getinfo( $curlobj );

  // Return back the status for easy diagnostics:
  if( !empty( $curl_exec_info['http_code'] ) ) $OUT_HTTPStatus = $curl_exec_info['http_code'];
  
  // Check any hint of failure whatsoever:
  if( $curl_ret === false || !($curl_exec_info['http_code'] >= 200 && $curl_exec_info['http_code'] <= 299) ) {
    // REQUEST FAILURE. Authentication or something failed. Figure out what.
    if( $curl_exec_info['http_code'] == 401 ) {
      // typical login failure.
      return( false );
    }
    return( "LOGIN FAILED. Remote server failed request with HTTP status ".$curl_exec_info['http_code'] );
  }
  else {
    // REQUEST FULLY SUCCEEDED so the authentication credentials were valid. 
    
    // Provide the domain.tld portion of the URL to the caller so they can confirm it is
    // the correct server for that account.
    $OUT_StorageDomainAndPath = opensam_isolate_domaintldpath_portion( $StorageServerUrl_obtained );
    
    // Return the successful credentials in the OUT params:
    $StorageServerUrl = $StorageServerUrl_obtained;
    $StorageUserName  = $StorageUserName_obtained;
    $StorageSessionId = $StorageSessionId_obtained;
    $StoragePassword  = $StoragePassword_obtained;
    // If any SForce CGI params played a role, then return them to the caller for subsequent use:
    if( !empty( $WebDAVUrlParameters ) ) $OUT_WebDAVUrlParameters = $WebDAVUrlParameters;
    
    return( true );  // SUCCESS return value.
  }
}
 
 // HELPERS
 
 
 // Allocate and prepare the CURL object. This guy handles the connection
 // and request to the StorageServerUrl.
 // RETURNS: the prepared Curl object.
 //          string err message if failure.
function opensam_create_and_prepare_curl( $url, $username, $password ) {
  // Allocate the curl object:
  $curlobj = curl_init( $url );
  if( !$curlobj ) return( "LOGIN FAILED. Invalid Server URL: $url. (curl initialization failed)" );

  //
  // Configure the CURL options to perform a request to the URL with an optimum chance of success.
  //
  $curl_options_array = array( 
    CURLOPT_FAILONERROR       => false,
    CURLOPT_HTTPAUTH          => CURLAUTH_ANY,
    CURLOPT_UNRESTRICTED_AUTH => true,
    CURLOPT_SSL_VERIFYPEER    => false,
    CURLOPT_SSL_VERIFYHOST    => false,
// CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
//    CURLOPT_FOLLOWLOCATION    => true,
	CURLOPT_AUTOREFERER		=> true,
    CURLOPT_RETURNTRANSFER    => true,
    CURLOPT_CONNECTTIMEOUT    => 15,
    CURLOPT_MAXREDIRS         => 5,
  );
  
  // Give it the username, password via HTTP Authenticate if that is the method we are using:
  if( !empty( $password ) ) {
	$curl_options_array[CURLOPT_USERPWD] = "$username:$password"; // this is the funky syntax for curl.
  }

  $i = 1;
  foreach( $curl_options_array as $optionnum => $optionvalue ) { 
    if( !curl_setopt( $curlobj, $optionnum, $optionvalue ) ) {
      return( "LOGIN FAILED. System Error. (curl setopt() failed unexpectedly #$1)" ); 
    }
    ++$i;
  }
  
  return( $curlobj );
}
 
 // Isolate the domain name from a URL.
 //
 // The caller of opensam_sso_authenticate() is responsible for verifying that
 // the passed in StorageServerUrl is valid for the StorageUserName's account by
 // examining just the domain.tld portion of the URL.
 //
 // SEE: http://opensam.org/4%20SSO%20and%20Provisioning.html, 
 //
 // RETURNS: just the domain.tld portion of the URL.
 function opensam_isolate_domaintldpath_portion( $url ) {
  // PHP's parse_url() is entirely bogus and can't handle URLs that don't have a protocol in front of them.
  //$url_parsed = parse_url($url);   -- WONT WORK.
  
  // Use our own regexp:
  // First get the whole hostname-domainname-path section:
  //          protocol - // - domain-and-path           
  if( ereg( "^([^:]+:)?(//)?([^?]*)", $url, $matcharr ) ) {
    $host_domain_and_path = $matcharr[3];
      // Now break out the the domainname.tld portions:
      // Since the expression is anchored at the end of string with the $, we always get the last foo.bar
      // portion of the domain name.
    if( ereg( "([^./]+)\\.([^./]+)(/.*)?$", $host_domain_and_path, $matcharr2 ) ) {
      return(  $matcharr2[1] . "." . $matcharr2[2] . $matcharr2[3] );
    }
  }
  return( "" );  // does not look much like a URL. Cannot be analyzed. 
}
 
 
 
 // Helper to find a SSO param.
 //  
 function opensam_obtain_one_sso_param( $which_sso_param, $sso_param_in ) {
  if( !empty( $sso_param_in ) )             return( $sso_param_in );
  
  // For convenience we check both the _GET and _POST.
  if( !empty( $_GET[$which_sso_param] ) )    return( $_GET[$which_sso_param] );
  if( !empty( $_POST[$which_sso_param] ) )   return( $_POST[$which_sso_param] );
  
  if( !empty( $_SESSION[$which_sso_param] ) ) return( $_SESSION[$which_sso_param] );
  return( null );
}

    
?>