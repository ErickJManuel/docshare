<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("server_config.php");
require_once("dbobjects/site_url.php");
require_once("includes/common_lib.php");
/**
* Const
*/
define("VM_VIEWER", "viewer");
define("VM_API", "api.php");

$gTbPrefix=TB_PREFIX;
if ($gTbPrefix!='')
	$gTbPrefix.="_";

/**
* Database table definitions
* Modify the default table names only if necessary and they must match your database tables.
* e.g. To modify the table name from "meeting" to "my_meeting",
* define('TB_MEETING', "my_meeting");
* "my_meeting" must exist in your database.
*/
/**
* meeting table name
*/
define('TB_MEETING', $gTbPrefix."meeting");
/**
* user table name
*/
define('TB_USER', $gTbPrefix."user");
/**
* webserver table name
*/
define('TB_WEBSERVER', $gTbPrefix."webserver");
/**
* videoserver table name
*/
define('TB_VIDEOSERVER', $gTbPrefix."videoserver");
/**
* remoteserver table name
*/
define('TB_REMOTESERVER', $gTbPrefix."remoteserver");
/**
* group table name
*/
define('TB_GROUP', $gTbPrefix."group");
/**
* site table name
*/
define('TB_SITE', $gTbPrefix."site");
/**
* viewer table name
*/
define('TB_VIEWER', $gTbPrefix."viewer");
/**
* image table name
*/
define('TB_IMAGE', $gTbPrefix."image");
/**
* picture table name
*/
define('TB_PICTURE', $gTbPrefix."picture");
/**
* brand table name
*/
define('TB_BRAND', $gTbPrefix."brand");
/**
* presentation table name
*/
define('TB_PRESENTATION', $gTbPrefix."presentation");
/**
* doc table name
*/
// not used
//define('TB_DOC', $gTbPrefix."doc");
/**
* media table name
*/
define('TB_MEDIA', $gTbPrefix."media");
/**
* locale table name
*/
define('TB_LOCALE', $gTbPrefix."locale");
/**
* background table name
*/
define('TB_BACKGROUND', $gTbPrefix."background");
/**
* session table name
*/
define('TB_SESSION', $gTbPrefix."session");
/**
* attendee table name
*/
define('TB_ATTENDEE', $gTbPrefix."attendee");
/**
* attendee_live table name
*/
define('TB_ATTENDEE_LIVE', $gTbPrefix."attendee_live");
/**
* license table name
*/
define('TB_LICENSE', $gTbPrefix."license");
/**
* regform table name
*/
define('TB_REGFORM', $gTbPrefix."regform");
/**
* registration table name
*/
define('TB_REGISTRATION', $gTbPrefix."registration");
/**
* mailtemplate table name
*/
define('TB_MAILTEMPLATE', $gTbPrefix."mailtemplate");
/**
* comment table name
*/
define('TB_COMMENT', $gTbPrefix."comment");
/**
* provider table name
*/
define('TB_PROVIDER', $gTbPrefix."provider");
/**
* aws table name
*/
define('TB_AWS', $gTbPrefix."aws");
/**
* hook table name
*/
define('TB_HOOK', $gTbPrefix."hook");
/**
* teleserver table name
*/
define('TB_TELESERVER', $gTbPrefix."teleserver");
/**
* version table name
*/
define('TB_VERSION', $gTbPrefix."version");
/**
* folder table name
*/
define('TB_FOLDER', $gTbPrefix."folder");
/**
* library table name
*/
define('TB_LIBRARY', $gTbPrefix."library");
/**
* storage server table name
*/
define('TB_STORAGESERVER', $gTbPrefix."storageserver");
/**
* content table name
*/
define('TB_CONTENT', $gTbPrefix."content");
/**
* token table name
*/
define('TB_TOKEN', $gTbPrefix."token");
/**
* conversionserver table name
*/
define('TB_CONVERSIONSERVER', $gTbPrefix."conversionserver");
/**
* licensekey table name
*/
define('TB_LICENSEKEY', $gTbPrefix."licensekey");
/**
* question table name
*/
define('TB_QUESTION', $gTbPrefix."question");

// Dir names
/**
* locale dir name
*/
define('DIR_LOCALE', "vlocale/");
/**
* image dir name
*/
define('DIR_IMAGE', "vimage/");
/**
* presentation dir name
*/
// define('DIR_PRESENTATION', "vpresentation/");
/**
* media dir name
*/
define('DIR_MEDIA', "vmedia/");
/**
* webserver dir name
*/
define('DIR_WEBSERVER', "vwebserver/");

/**
* doc dir name
*/
define('DIR_DOC', "vdoc/");

/**
* help dir name
*/
define('DIR_HELP', "help/");
/**
* viewer help dir name
*/
// define('DIR_VIEWER_HELP', "help/viewer_help/");
/**
* viewer about dir name
*/
// define('DIR_VIEWER_ABOUT', "help/viewer_about/");
/**
* temp dir name
*/
// Replaced with GetTempDir() in includes/common_lib.php
//define('DIR_TEMP', "temp/");
/**
 * file prefix
 */
define('FILE_PREFIX', "f_");

// Error Codes
/**
 * No Error
 */
define('ERR_NONE', 0);
/**
 * SQL Error
 */
define('ERR_SQL', 1);
/**
 * Illegal operation
 */
define('ERR_ILL', 2);
/**
 * Other errors
 */
define('ERR_ERROR', 3);

/*
 * XML header string
 */
define('XML_HEADER', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
/**
* @static
* @var string time zone UTC offset (e.g. +2:00)
*/
$sTimeZone='';
$sDB=null;
$sDBConnectFailed=false;	

/**
 * Base class for all database objects.
 * @package     VShow
 * @access      public
 */
class VObject {
	/**
	 * @access private
	 * @var integer id of the object in the database table
	 */
	var $mId=0;
	/**
	 * @access private
	 * @var string database table name for the object
	 */
	var $mTbName='';
	/**
	 * @access protected
	 * @var string last error message.
	 */
	var $mErrorMsg='';
	/**
	 * @access protected
	 * @var integer last error code.
	 */
	var $mErrorCode=ERR_NONE;	

	/**
	 * Constructor. Associate the object to database table.
	 * @param string $tbName name of the database table for the object
	 */
	function VObject($tbName) { 
		$this->mTbName=$tbName; 
	}
	/**
	 * Convert value to legal characters to be used in xml
	 * @param string $str
	 * @return string xml text
	 */
	static function StrToXML($str) { 
//		return utf8_encode(htmlspecialchars($str));
		return htmlspecialchars($str);
	}
	
	/**
	 * @static
	 * @param string $query query string to append to
	 * @param string $key
	 * @param string $value
	 * @return string new query
	 */
	static function AppendQuery($query, $key, $val, $mode="AND")
	{
		if ($query!='')
			$query.=" $mode ";		
		$query.="`".$key."`='".addslashes($val)."'";
		return $query;
	}
	/**
	 * @static
	 * @access public
	 * @return boolean
	 */
	static function CanOpenDB()
	{
		global $sDBConnectFailed;
		
		if ($sDBConnectFailed)
			return false;
			
		if (VObject::OpenDB($db)=='') {
			return true;
		} else {
			// store the result so we only test the db connection once per session
			$sDBConnectFailed=true;
			return false;
		}
	}
	/**
	 * @static
	 * @access private
	 */
	static function OpenDB(&$db, $persist=false)
	{
		global $sDB;
		
		if ($sDB==null) {
			
			// open db
			$db = $persist? @mysql_pconnect(DB_SERVER, DB_LOGIN, DB_PASS) :
					@mysql_connect(DB_SERVER, DB_LOGIN, DB_PASS);
/*	don't try it the second time because it takes too long				
			if ($db==FALSE) {
				sleep(1);
				// open db again
				$db = $persist? @mysql_pconnect(DB_SERVER, DB_LOGIN, DB_PASS) :
					@mysql_connect(DB_SERVER, DB_LOGIN, DB_PASS);
			}				
*/				
			if ($db==FALSE) {
				return('MySQL error: '.mysql_errno()." ". mysql_error());
			}
			
			if (!@mysql_select_db(DB_NAME,$db)) {
				mysql_close($db);
				return('MySQL error: '.mysql_errno()." ". mysql_error());
			}
				
			$sDB=$db;
		} else 
			$db=$sDB;
			
		return '';
	}
	/**
	 * @static
	 * @access private
	 */
	static function CloseDB()
	{
		global $sDB;
		if ($sDB!=null) {
			mysql_close($sDB);
			$sDB=null;
		}
	}	
	/**
	 * @static
	 * @access private
	 */
	static function MyAddSlashes($q) {
//		if (is_string($q) && !get_magic_quotes_gpc()) { 
			return addslashes($q); 
//		} else { 
//			return $q;
//		} 
	}		
	/**
	 * @static
	 * @access private
	 */	
	static function ValidateKey($key) {
		if (strpos($key, "'")!==false || 
				strpos($key, ";")!==false || 
				strpos($key, " ")!==false)
			return false;
		else
			return true;
	}			
	/**
	 * @static
	 * @access private
	 */	
	static function VerifyExp($sqlExp='') {		
		if ($sqlExp!='') {
			// possible attack	
			$exp=strtoupper($sqlExp);
			if (strpos($exp, "DELETE ")!==false ||
					strpos($exp, "DROP ")!==false ||
					strpos($exp, "REPLACE ")!==false ||
					strpos($exp, "TRUNCATE ")!==false ||
					strpos($exp, "RENAME ")!==false ||
					strpos($exp, "CREATE ")!==false ||
					strpos($exp, "INSERT ")!==false ||
					strpos($exp, "UPDATE ")!==false ||
					strpos($sqlExp, ";")!==false)
			{
//				return ("Unauthorized database command '".$sqlExp);
				return ("Unauthorized database command.");
			}
		}
		return '';
	}
	
	/**
	 * Select all rows in the table that match sqlExp starting at $offset up to $count rows
	 * return all column fields that match $selectExp
	 * @static
	 * @param string $tbName name of the database table for the object
	 * @param string $sqlExp sql expression
	 * @param resource $sqlResults returned sql resource
	 * @param integer $offset starting offset
	 * @param integer $count max. number of rows matched
	 * @param string $$selectExp column fields selection expression
	 * @return error message or empty if no error
	 */	
	static function SelectAll($tbName, $sqlExp, &$sqlResults, $offset=0, $count=0, $selectExp='*', $orderBy='id', $reverse=false)
	{
		global $sTimeZone;
		if ($tbName=='')
			return "Table name cannot be empty.";
			
		if (is_numeric($offset)==false)
			return "Illegal query parameter offset=".$offset;
		
		if (is_numeric($count)==false)
			return "Illegal query parameter count=".$count;	
		
		$errMsg=VObject::VerifyExp($sqlExp);
		if ($errMsg!='')
			return $errMsg;		
		
		$errMsg=VObject::OpenDB($db);
		
		if ($errMsg!='')
			return $errMsg;
/*			
		if ($sTimeZone!='') {
			$sql="SET time_zone='".$sTimeZone."';";			
			$sqlResults = mysql_query($sql,$db);
			if (!$sqlResults) {
				return('SQL error: ' . mysql_error());
			}
		}
*/		
		$sortReverse='';
		if ($reverse)
			$sortReverse='DESC';
			
		if ($count>0 && $orderBy!='')
			$sql = "SELECT $selectExp FROM `".$tbName."` WHERE ($sqlExp) ORDER BY `$orderBy` $sortReverse LIMIT $offset,$count;";
		elseif ($orderBy!='')
			$sql = "SELECT $selectExp FROM `".$tbName."` WHERE ($sqlExp) ORDER BY `$orderBy` $sortReverse;";
		else
			$sql = "SELECT $selectExp FROM `".$tbName."` WHERE ($sqlExp);";
		
		$sqlResults = mysql_query($sql,$db);
		
		if (!$sqlResults)
			return('MySQL error: '.mysql_errno()." ". mysql_error());
		
		return '';
	}	
	/**
	 * Count the number of rows in tbName that matches the query sqlExp
	 * @param 
	 * @return error message or empty if no error
	 */	
	static function Count($tbName, $sqlExp, &$num_rows)
	{
//		global $sTimeZone;
								
		$errMsg=VObject::VerifyExp($sqlExp);
		if ($errMsg!='')
			return $errMsg;		

		$errMsg=VObject::OpenDB($db);
		
		if ($errMsg!='')
			return $errMsg;
/*
		if ($sTimeZone!='') {
			$sql="SET time_zone='".$sTimeZone."';";			
			$sqlResults = mysql_query($sql,$db);
			if (!$sqlResults) {
				return('SQL error: ' . mysql_error());
			}
		}
*/
		$sql = "SELECT COUNT(*) FROM `".$tbName."` WHERE ($sqlExp);";
//		if ($sTimeZone!='') {
//			echo $sql;
//			exit();
//		}
		
		$sqlResults = mysql_query($sql,$db);
		
		if (!$sqlResults) {
			return('SQL error: ' . mysql_error());
		}
			
		$rowInfo= mysql_fetch_row($sqlResults);

		$num_rows=$rowInfo[0];
				
				
		return '';
	}	
	/**
	 * Find the first row in the table that matches sqlExp
	 * @static
	 * @param string $tbName name of the database table for the object
	 * @param string $$sqlExp sql expression for the selection
	 * @param array $rowInfo returned row values
	 * @return error message or empty if no error
	 */
	static function Select($tbName, $sqlExp, &$rowInfo)
	{
		$errMsg=VObject::SelectAll($tbName, $sqlExp, $result);
		
		if ($errMsg!='')
			return $errMsg;
		
		$rowInfo= mysql_fetch_array($result, MYSQL_ASSOC);
		
		return '';
	}
	/**
	 * Get the current time zone
	 * @static
	 * @return error message or empty if no error
	 */	
	static function GetDateTime(&$date, &$time, &$tz)
	{		
		// open db
//		$db = mysql_connect(DB_SERVER, DB_LOGIN, DB_PASS);
//		if ($db==FALSE)
//			return("Unable to open database");
		$errMsg=VObject::OpenDB($db);
		
		if ($errMsg!='')
			return $errMsg;
							
		$sql = "SELECT CURDATE(), CURTIME(), @@session.time_zone";		
		
		$sqlResults = mysql_query($sql);
		
		if (!$sqlResults)
			return('MySQL error: '.mysql_errno()." ". mysql_error());
		
		$rowInfo= mysql_fetch_row($sqlResults);
		$date=$rowInfo[0];
		$time=$rowInfo[1];
		$tz=$rowInfo[2];
				
		return '';		
	}
	/**
	 * Convert time from one time zone to another
	 * @static
	 * @return error message or empty if no error
	 */	
	static function ConvertTZ($fromTime, $fromTz, $toTz, &$toTime)
	{										
		$sql = "SELECT CONVERT_TZ('$fromTime', '$fromTz', '$toTz')";
		$ret=VObject::SendQuery($sql, $sqlResults);			
		if ($ret!='')
			return($ret);
		$rowInfo= mysql_fetch_row($sqlResults);
		$toTime=implode(' ', $rowInfo);
		mysql_free_result($sqlResults);
		return '';		
	}
	/**
	 * Call SQL with selectQuery
	 * @static
	 * @return error message or empty if no error
	 */	
	static function SendQuery($query, &$sqlResults)
	{		
		$errMsg=VObject::VerifyExp($query);
		if ($errMsg!='')
			return $errMsg;		

		$errMsg=VObject::OpenDB($db);		
		if ($errMsg!='')
			return $errMsg;
									
		$sqlResults = mysql_query($query);
		
		if (!$sqlResults)
			return('MySQL error: '.mysql_errno()." ". mysql_error());
		
		return '';		
	}
	/**
	 * Set time zone to be used in the next db query
	 * @static
	 * @param string $tz UTC time zone offset (e.g. +2:00)
	 */
	static function SetTimeZone($tz)
	{
		global $sTimeZone;
		$sTimeZone=$tz;
	}
	/**
	 * Find any row in the table
	 * @static
	 * @param string $tbName name of the database table for the object
	 * @param array $rowInfo returned row values
	 * @return error message or empty if no error
	 */
	static function SelectAny($tbName, &$rowInfo)
	{
		$errMsg=VObject::SelectAll($tbName, "1", $result);
		
		if ($errMsg!='')
			return $errMsg;
		
		$rowInfo= mysql_fetch_array($result, MYSQL_ASSOC);
		
		return '';
	}
	/**
	 * Find a row in the table that matches the columne name and value
	 * @static
	 * @param string $tbName table name
	 * @param string $col column name to search
	 * @param string $val column value to match
	 * @param array $rowInfo returned row values
	 * @return string error message or empty if there is no error
	 */	
	static function Find($tbName, $col, $val, &$rowInfo)
	{
//		$exp="`".$col."`='".$val."'";
		$exp=VObject::AppendQuery("", $col, $val);
		return VObject::Select($tbName, $exp, $rowInfo);
	}
	/**
	 * Check if a given column value exists in the table
	 * @static
	 * @param string $tbName name of the database table for the object
	 * @param string $col is the column name to search
	 * @param string $val is the column value to match
	 * @return bool true if the value already exists
	 */	
	static function InTable($tbName, $col, $val)
	{
//		$exp="`".$col."`='".$val."'";
		
		$exp=VObject::AppendQuery("", $col, $val);
		$num=0;	
		VObject::Count($tbName, $exp, $num);
//		VObject::SelectAll($tbName, $exp, $result);				
//		$num= mysql_fetch_row($result);
		
		if ($num>0)
			return true;
		else
			return false;
	}	
	/**
	 * Return all rows matching query as html form option
	 * @static
	 * @param string $tbName name of the database table for the object
	 * @param string $query 
	 * @param string $optionName
	 * @param string $colKey
	 * @param string $selectedId
	 * @return string html text
	 */	
	static function GetFormOptions($tbName, $query, $optionName, $colKey, $selectedId, $prepend='', $id='', $onchangeStr='',
		$selectExp='*', $orderBy='id', $reverse=false, $maxLength=32)
	{
		$errMsg=VObject::SelectAll($tbName, $query, $result, 0, 0, $selectExp, $orderBy, $reverse);
		if ($errMsg!='') {
			return '';
		}
		if ($onchangeStr!='')
			$str="<select id=\"$id\" name=\"$optionName\" onchange=\"$onchangeStr\">\n";
		else
			$str="<select id=\"$id\" name=\"$optionName\">\n";

		if ($prepend!='')
			$str.=$prepend."\n";
		$num_rows = mysql_num_rows($result);
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$aName=$row[$colKey];
			if (strlen($aName)>$maxLength)
				$aName=substr($aName, 0, $maxLength-1)."...";
				
			if ($selectedId!='' && $selectedId==$row['id'])
				$str.="<option value=\"".$row['id']."\" selected>".$aName."</option>\n";
			else
				$str.="<option value=\"".$row['id']."\">".$aName."</option>\n";
		}
		$str.="</select>\n";
		return $str;	
	}	
	/**
	 * @return string last error message. empty for no error
	 */	
	function GetErrorMsg()
	{
		return $this->mErrorMsg;
	}
	/**
	 * @param string error message to set
	 */	
	function SetErrorMsg($msg)
	{
		if ($msg!='')
			$this->mErrorMsg=get_class($this).":".$msg;
		else
			$this->mErrorMsg='';
	}
	/**
	 * @return string last error code.
	 */	
	function GetErrorCode()
	{
	}
	/**
	 * @return integer row id of the object in the table
	 */	
	function GetRowId()
	{
		return $this->mId;
	}
	/**
	 * @param integer set row id of the object
	 */	
	function SetRowId($rowId)
	{
		$this->mId=$rowId;
	}
	/**
	 * Check if the object is arleady in the table.
	 * @return bool
	 */			
	function Exists()
	{
		if ($this->mId>0)
			return $this->InTable($this->mTbName, "id", $this->mId);
		else
			return false;
	}
	/**
	 * Get the entire row of the object from the table
	 * @param array row values of the object (return)
	 * @return integer error code
	 */
	function Get(&$rowInfo)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		if ($this->mTbName=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("table not set");
			return $this->mErrorCode;
		}
		$errMsg=$this->Find($this->mTbName, 'id', $this->mId, $rowInfo);
	
		if ($errMsg=='') {
			return ERR_NONE;
		} else {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
	
	}
	/**
	 * Get a column value of the object from the table
	 * @param string $col column field to get
	 * @param string $val column value to return
	 * @return integer error code
	 */
	function GetValue($col, &$val)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		if ($this->mTbName=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("table not set");
			return $this->mErrorCode;
		}
		$exp=VObject::AppendQuery("", "id", $this->mId);
		$errMsg=$this->SelectAll($this->mTbName, $exp, $result, 0, 0, $col);
		
		if ($errMsg=='') {			
			$rowInfo= mysql_fetch_array($result, MYSQL_ASSOC);
			if (isset($rowInfo[$col]))
				$val=$rowInfo[$col];
			else
				$val=null;			
			return ERR_NONE;
		} else {
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_ERROR;
			return $this->mErrorCode;
		}
		
	}

	/**
	 * Print the database value of the object
	 */
	function PrintDB()
	{
		$rowInfo=array();
		if ($this->Get($rowInfo)==ERR_NONE) {
			echo "<h3>".$this->mTbName."</h3>\n";
			$this->Find($this->mTbName, 'id', $this->mId, $rowInfo);
			foreach ($rowInfo as $key => $value) {
				echo ($key."=".$value."<br>\n");	
			}
		} else {
			echo $this->GetErrorMsg();
		}
	}
	/**
	 * Insert the object into the table. The object must be created with id=0.
	 * @param array row values of the object
	 * @return integer error code
	 */		
	function Insert($rowInfo)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId>0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id already set");
			return $this->mErrorCode;
		}
		if ($this->mTbName=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("table not set");
			return $this->mErrorCode;
		}			
		$this->SetErrorMsg($this->OpenDB($db));
		
		if ($this->mErrorMsg!='') {
			$this->mErrorCode=ERR_SQL;
			return $this->mErrorCode;
		}
		
		$dbArgs='';
		foreach($rowInfo as $key => $value) {
			If ($this->ValidateKey($key)==false) {
				$this->SetErrorMsg("Illegal query key=".$key);
				$this->mErrorCode=ERR_SQL;
				return $this->mErrorCode;
			}
			
			if ($dbArgs!='')
				$dbArgs.=", ";
			
//			if (substr($value, 0, 1)=='#') {
			if (preg_match("/^\#[A-Z]+\(\)/", $value)) {
				$dbArgs.="`$key`=".substr($value, 1);
			} else if ($key=='id') {
				// skip
			} else {
				$value=$this->MyAddSlashes($value);
				$dbArgs.="`$key`=('".$value."')";
			}
			
		}
		
		$sql = "INSERT INTO `".$this->mTbName."`";
		if ($dbArgs!='')
			$sql.=" SET " . $dbArgs.";";
		
		$result = mysql_query($sql,$db);
		
		if (!$result) {
			$errMsg='MySQL error: '.mysql_errno()." ". mysql_error();
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_SQL;
			return $this->mErrorCode;
		}

		$this->mId=mysql_insert_id();
		return $this->mErrorCode;
	}
	/**
	 * Update the database value
	 * @param integer id of the row to update
	 * @param array new row values
	 * @return integer error code
	 */		
	function Update($rowInfo)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		if ($this->mTbName=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("table not set");
			return $this->mErrorCode;
		}
		$this->SetErrorMsg($this->OpenDB($db));
		
		if ($this->mErrorMsg!='') {
			$this->mErrorCode=ERR_SQL;
			return $this->mErrorCode;
		}
		
		$dbArgs='';
		foreach($rowInfo as $key => $value) {
			If ($this->ValidateKey($key)==false) {
				$this->SetErrorMsg("Illegal key=".$key);
				$this->mErrorCode=ERR_SQL;
				return $this->mErrorCode;
			}
			
			if ($dbArgs!='')
				$dbArgs.=", ";
			
			if (substr($value, 0, 1)=='#')
				$dbArgs.="`$key`=".substr($value, 1);
			else {
				$value=$this->MyAddSlashes($value);
				$dbArgs.="`$key`=('".$value."')";
			}
			
		}

		// update db to set the assigned flag
		if ($dbArgs!='') {        	
			$sql = "UPDATE `".$this->mTbName."` SET"
				. $dbArgs
				. " WHERE `id`='".$this->mId."';";
			
			$result = mysql_query($sql,$db);
			
			if (!$result) {
				$errMsg='MySQL error: '.mysql_errno()." ". mysql_error();
				$this->SetErrorMsg($errMsg);
				$this->mErrorCode=ERR_SQL;
				return $this->mErrorCode;
			}
		}
//		$rowId=mysql_insert_id();
		return $this->mErrorCode;
		
	}
	/**
	 * Drop the object from the table.
	 * @param integer id of the row to update
	 */		
	function Drop()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';

		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		if ($this->mTbName=='') {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("table not set");
			return $this->mErrorCode;
		}
		$this->SetErrorMsg($this->OpenDB($db));
		
		if ($this->mErrorMsg!='') {
			$this->mErrorCode=ERR_SQL;
			return $this->mErrorCode;
		}		
		
		// update db to set the assigned flag        	
		$sql = "DELETE FROM `".$this->mTbName."`"
			. " WHERE `id`='".$this->mId."';";
		
		
		$result = mysql_query($sql,$db);
		
		if (!$result) {
			$errMsg='MySQL error: '.mysql_errno()." ". mysql_error();
			$this->SetErrorMsg($errMsg);
			$this->mErrorCode=ERR_SQL;
			return $this->mErrorCode;
		}
		$this->mId=0;
//		$rowId=mysql_insert_id();	

		return $this->mErrorCode;
	}
	/**
	 * Drop all rows of a table matching $sqlExp 
	 * @param string error message
	 */
	static function DropSelections($tbName, $sqlExp)
	{
		$errMsg=VObject::VerifyExp($sqlExp);
		if ($errMsg!='')
			return $errMsg;		
		
		$errMsg=VObject::OpenDB($db);
		
		if ($errMsg!='')
			return $errMsg;
	
		$sql = "DELETE FROM `".$tbName."` WHERE $sqlExp;";
		
		$sqlResults = mysql_query($sql,$db);
		
		if (!$sqlResults)
			return('MySQL error: '.mysql_errno()." ". mysql_error());
		
		return '';
	}	
	/**
	 * Return a cache file path
	 * @param string cache key
	 */		
	static function GetCachePath($cacheKey)
	{
//		return DIR_TEMP.md5($cacheKey).".php";
		return GetTempDir().md5($cacheKey).".php";
	}	
	/**
	 * Write the entry to a cache file
	 * @param integer id of the row to write
	 */		
	static function WriteToCache($cacheFile, $rowInfo)
	{
		$content="<?php\n";
		$content.=" \$_rowData=array(\n";		

		foreach ($rowInfo as $key => $value) {
			// don't need to write text fields to the cache
			if (strpos($key, "_text")!==false)
				continue;
			// The cache file may be included in php so we don't want any <? in it.
			$value=str_replace('"', '\"', $value);
			$value=str_replace("<?", "", $value);
			$value=str_replace("?>", "", $value);
			$content.="   \"$key\" => \"$value\",\n";
		}
			
		$content.=" );\n";
		$content.="?>";
		
//		$cacheFile=VObject::GetCachePath($cacheKey);
		$fp=@fopen($cacheFile, "ab");
		if ($fp && flock($fp, LOCK_EX)) {
			ftruncate($fp, 0);
			fwrite($fp, $content);
			flock($fp, LOCK_UN);
		}
		if ($fp) {
			fclose($fp);
			umask(0);
			@chmod($cacheFile, 0777);
			return true;
		}
		return false;
	}
	/**
	 * Read the entry from a cache file
	 * @param array rowInfo
	 * @return boolean returns true if the read is a success
	 */		
	static function ReadFromCache($cacheFile, &$rowInfo)
	{
//		$cacheFile=VObject::GetCachePath($cacheKey);
		$content='';
		$fp=@fopen($cacheFile, "rb");
		if ($fp && flock($fp, LOCK_SH)) {
			@include_once($cacheFile);
			flock($fp, LOCK_UN);
			fclose($fp);
			
			$rowInfo=array();
			if (isset($_rowData)) {
				foreach ($_rowData as $key => $value) {
					$rowInfo[$key]=$value;
				}
			}

			return true;
			
		}
		if ($fp)
			fclose($fp);
			
		return false;
	}
	
	static function GetMicroTime(){ 
		list($usec, $sec) = explode(" ",microtime()); 
		return ((float)$usec + (float)$sec); 
	}
	
	static function GetPingTime()
	{
		VObject::OpenDB($db);
		$time1=VObject::GetMicroTime();
		mysql_ping($db);
		$time2=VObject::GetMicroTime();
		return $time2-$time1;		
	}
	
	static function ToXML($nodeName, $rowInfo)
	{
		$xml="<$nodeName>\n";
		foreach ($rowInfo as $key => $val) {
			$xml.="<$key>".VObject::StrToXml($val)."</$key>\n";
		}
		$xml.="</$nodeName>";
		return $xml;
	}

}

	
?>