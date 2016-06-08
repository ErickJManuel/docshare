<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("rest/prestapi.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vlicense.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PMember extends PRestAPI 
{
	/**
	 * Constructor
	 */	
	function PMember()
	{
		$this->PRestAPI("member");
		$this->mSynopsis=
"A member of the site can be either a 'host' or an 'admin'. ";
		$this->mMethods="GET,POST,PUT,DELETE";
		$this->mRequired=array(
			'id' => "Member id. Either id or login must be present for GET, PUT, DELETE. Ignored for POST.",
			'login' => "Member login. Required for POST. Either id or login must be present for GET, PUT, DELETE.",
			);
		$this->mOptional=array(
			'group_id' => "Member group_id (see Administration/Groups for valid group ids.) If the parameter is not provided for a POST request, the default group id is assigned.",
			'license_code' => "Member account license code (see Administration/Acounts for valid license code.) If the parameter is not provided for a POST, a trial license is assigned. You cannot change the license_code of a member after it is created if you have a 'Named-user' license model. You must delete the member or chang the license_code using the web UI.",
			'password' => "Member password.",
			'first_name' => "Member first name.",
			'last_name' => "Member first name.",
			'title' => "Member title.",
			'org' => "Member company or organization.",
			'street' => "Street address.",
			'city' => "City.",
			'state' => "State.",
			'country' => "Country.",
			'zip' => "Postal zip code.",
			'email' => "User email address. Can be omitted if it is the same as login.",
			'phone' => "Phone number.",
			'mobile' => "Mobile phone number.",
			'fax' => "Fax phone number.",
			'conf_num' => "Set default teleconference phone number.",
			'conf_num2' => "Set alternative (toll-free) teleconference phone number.",
			'conf_mcode' => "Set default teleconference moderator code.",
			'conf_pcode' => "Set default teleconference participant code.",
			'use_teleserver' => "Is the conference number controllable by the teleconference server assigned to the user's group? (Y or N)",
			'permission' => "Member permission (HOST or ADMIN).",
			'active' => "Is member account active? (Y or N).",
			'time_zone' => "Member default time_zone offset from GMT (e.g. +08:00).",
			);
		$this->mReturned=array(
			'PERMISSION' => "HOST if the member can host a meeting or ADMIN if the member is an admin.",
			'AUDIO_CONFERENCE' => "The default teleconference number assigned to the member.",
			'MEMBER_PICT_URL' => "The member's profile picture url",
			);		
	}

	function Get($userId='')
	{
		$userXml=$this->LoadResponseXml();
		
		$query="brand_id='".$this->mBrandId."'";
		
		// the input can be id (user access_id) or login
		if ($userId=='')
			$this->GetArg('id', $userId);

		$login='';
		$this->GetArg('login', $login);		
		
		if ($userId!='')
			$query.=" AND access_id='".$userId."'";
		else if ($login!='')
			$query.=" AND LOWER(login)='".addslashes(strtolower($login))."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id or login.");
			return '';
		}
				
		$userInfo=array();
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		
		if (!isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("User not found.");
			return '';
		}
		
		if ($userInfo['id']!=GetSessionValue('member_id') &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage("Access is not authorized.");
			return '';			
		}
		
		$userXml=PMember::ReplaceObjectTags($userInfo, $userXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $userXml;
	}
	function ReplaceObjectTags($userInfo, $userXml)
	{
		$userXml=str_replace("[MEMBER_ID]", $userInfo['access_id'], $userXml);
		$userXml=str_replace("[LOGIN]", htmlspecialchars($userInfo['login']), $userXml);
		$userXml=str_replace("[PASSWORD]", htmlspecialchars($userInfo['password']), $userXml);
		$userXml=str_replace("[FIRST_NAME]", htmlspecialchars($userInfo['first_name']), $userXml);
		$userXml=str_replace("[LAST_NAME]", htmlspecialchars($userInfo['last_name']), $userXml);
		$userXml=str_replace("[EMAIL]", htmlspecialchars($userInfo['email']), $userXml);
		$userXml=str_replace("[PERMISSION]", $userInfo['permission'], $userXml);
		$userXml=str_replace("[ACTIVE]", $userInfo['active'], $userXml);
		$licId=$userInfo['license_id'];
		$license=new VLicense($licId);
		$license->GetValue('code', $licCode);
		$userXml=str_replace("[LICENSE_CODE]", $licCode, $userXml);
		$userXml=str_replace("[CREATE_DATE]", $userInfo['create_date'], $userXml);
//		$userXml=str_replace("[CONF_NUM]", $userInfo['tele_num'], $userXml);
//		$userXml=str_replace("[CONF_MCODE]", $userInfo['tele_mcode'], $userXml);
//		$userXml=str_replace("[CONF_PCODE]", $userInfo['tele_pcode'], $userXml);
		$userXml=str_replace("[CONF_NUM]", htmlspecialchars($userInfo['conf_num']), $userXml);
		$userXml=str_replace("[CONF_NUM2]", htmlspecialchars($userInfo['conf_num2']), $userXml);
		$userXml=str_replace("[CONF_MCODE]", htmlspecialchars($userInfo['conf_mcode']), $userXml);
		$userXml=str_replace("[CONF_PCODE]", htmlspecialchars($userInfo['conf_pcode']), $userXml);
		$userXml=str_replace("[USE_TELESERVER]", $userInfo['use_teleserver'], $userXml);
		$userXml=str_replace("[GROUP_ID]", $userInfo['group_id'], $userXml);
		$pictUrl='';
		if ($userInfo['pict_id']>0) {
			require_once("dbobjects/vimage.php");
			$pict=new VImage($userInfo['pict_id']);
			$pict->GetUrl(SITE_URL, $pictUrl);
		}	
		$userXml=str_replace("[PICT_URL]", $pictUrl, $userXml);

		return $userXml;
	}
	function Update($userId='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($userId=='')
			$this->GetPostArg('id', $userId);
		
		$login='';
		$this->GetPostArg('login', $login);		

		if ($userId!='')
			$query.=" AND access_id='".$userId."'";
		else if ($login!='')
			$query.=" AND LOWER(login)='".addslashes(strtolower($login))."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id or login.");
			return '';
		}

		$userInfo=array();
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);
		if (!isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("User not found.");
			return '';
		}
				
		// create the input parameters for api_includes/user.php
		$cmd='SET_USER';
		$api_error_message='';
		$api_exit=false;
		$VARGS['user_id']=$userInfo['id'];
		if (isset($_POST['license_code']))
			$VARGS['license']=$_POST['license_code'];
		require_once('api_includes/user.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->Get($userInfo['access_id']);

	}
	function Insert()
	{
		global $api_error_message, $api_exit, $VARGS;
/*		
		$query="brand_id='".$this->mBrandId."'";
		if (isset($_POST['login']))
			$query.=" AND login='".$_POST['login']."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id or login.");
			return '';
		}

		$userInfo=array();
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);
		if (isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_FORBIDDEN);
			$this->SetErrorMessage("User already exits.");
			return '';
		}
*/				
		$cmd='ADD_USER';
		$api_error_message='';
		$api_exit=false;
		$restApi=true;
		if (isset($_POST['license_code']))
			$VARGS['license']=$_POST['license_code'];
		else {	
			$VARGS['license']="TPV1";	// assign a trial license if license_code is not provided
//			$this->SetStatusCode(PCODE_BAD_REQUEST);
//			$this->SetErrorMessage("Missing parameter 'license_code'");
//			return '';
		}

		require_once('api_includes/user.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->Get($userInfo['access_id']);

	}
	
	function Delete($userId='')
	{
		global $api_error_message, $api_exit, $VARGS;
		
		$query="brand_id='".$this->mBrandId."'";
		if ($userId=='')
			$this->GetPostArg('id', $userId);
		
		$login='';
		$this->GetPostArg('login', $login);
		
		if ($userId!='')
			$query.=" AND access_id='".$userId."'";
		else if ($login!='')
			$query.=" AND LOWER(login)='".addslashes(strtolower($login))."'";
		else {	
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage("Missing parameter id or login.");
			return '';
		}

		$userInfo=array();
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);
		if (!isset($userInfo['id'])) {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage("User not found.");
			return '';
		}
		
		$theLogin=$userInfo['login'];
		
		$cmd='DELETE_USER';
		$api_error_message='';
		$api_exit=false;
		$VARGS['user_id']=$userInfo['id'];
		if (isset($_POST['license_code']))
			$VARGS['license']=$_POST['license_code'];
		require_once('api_includes/user.php');
		
		if ($api_error_message!='') {
			$this->SetStatusCode(PCODE_ERROR);
			$this->SetErrorMessage($api_error_message);
			return '';
		}
	
		return $this->GetMessageXml("User $theLogin deleted.");

	}
}


?>