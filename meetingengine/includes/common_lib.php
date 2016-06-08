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

define('ROOT_USER', '_root');
define('PICT_SIZE', 96);
define("BACKGROUND_WIDTH", 800);
define("BACKGROUND_HEIGHT", 600);

define("SESSION_EXP_TIME", 60*30);
define("IPHONE_SESSION_EXP_TIME", 60*30); // (iPhone user session time)

// directory path to store session files
// must use a different dir if the session expiration times are different
// as the session files will be removed if the exp. time is up
define("SESSION_DIR", "/tmp/persony/session");
define("IPHONE_SESSION_DIR", "/tmp/persony/iphone_session");

// Should move this to DB. Keep it here for now.
// Locale code: http://publib.boulder.ibm.com/infocenter/comphelp/v101v121/index.jsp?topic=/com.ibm.aix.cbl.doc/PGandLR/ref/rpnls03a.htm
$gLocaleTable=array(
	"en" => "English",
	"zh_CN" => "Chinese(Simplified)",
	"zh_TW" => "Chinese(Traditional)",
	"cs" => "Czech",
	"de" => "German",
	"it" => "Italian",
	"pt" => "Portuguese",
	"sv" => "Swedish",
	"es" => "Spanish",
//	"ru" => "Russian",
	);

$VARGS=array();

date_default_timezone_set('UTC');

function SetSessionExpiration($sec) {
	
	// destroy the cookie when the browser window closes
//	setcookie(session_name(), session_id(), 0, "/");

	$expTime=time()+$sec;

	$CookieInfo = session_get_cookie_params();
	if ( (empty($CookieInfo['domain'])) && (empty($CookieInfo['secure'])) ) {
	    setcookie(session_name(), session_id(), $expTime, $CookieInfo['path']);
	} elseif (empty($CookieInfo['secure'])) {
	    setcookie(session_name(), session_id(), $expTime, $CookieInfo['path'], $CookieInfo['domain']);
	} else {
	    setcookie(session_name(), session_id(), $expTime, $CookieInfo['path'], $CookieInfo['domain'], $CookieInfo['secure']);
	}

}

function StartSession($devType='') {
	
	$my_tmp_dir=SESSION_DIR;


	// timeout value for the cookie
	if ($devType=='iphone') {
		$cookie_timeout = IPHONE_SESSION_EXP_TIME; // in seconds
//		$my_tmp_dir=IPHONE_SESSION_DIR;
	} else {
		$cookie_timeout = SESSION_EXP_TIME; // in seconds
	}

	// timeout value for the garbage collector
	//   we add 300 seconds, just in case the user's computer clock
	//   was synchronized meanwhile; 600 secs (10 minutes) should be
	//   enough - just to ensure there is session data until the
	//   cookie expires
//	$garbage_timeout = $cookie_timeout + 600; // in seconds
	$longest_timeout=(integer)IPHONE_SESSION_EXP_TIME;
	$garbage_timeout = $longest_timeout + 600; // in seconds

	// set the PHP session id (PHPSESSID) cookie to a custom value
	session_set_cookie_params($cookie_timeout);

	// set the garbage collector - who will clean the session files -
	//   to our custom timeout
	ini_set('session.gc_maxlifetime', $garbage_timeout);

	// we need a distinct directory for the session files,
	//   otherwise another garbage collector with a lower gc_maxlifetime
	//   will clean our files aswell - but in an own directory, we only
	//   clean sessions with our "own" garbage collector (which has a
	//   custom timeout/maxlifetime set each time one of our scripts is
	//   executed)
/*
	strstr(strtoupper(substr($_SERVER["OS"], 0, 3)), "WIN") ? 
		$sep = "\\" : $sep = "/";
*/
/*
	$sessdir = ini_get('session.save_path');
	
	$len=strlen($sessdir);
	if ($len>0) {
		if (strpos($sessdir, $my_tmp_dir)===false) {

			if ($sessdir[$len-1]=='/' || $sessdir[$len-1]=='\\')
				$sessdir.=$my_tmp_dir;
			else
				$sessdir.='/'.$my_tmp_dir;
		}
	} else {
		$sessdir="/tmp/$my_tmp_dir";
	}
*/

/*
	$currDir=getcwd();
	$currDir=str_replace("\\", "/", $currDir);
	$sessDir=$currDir."/".$my_tmp_dir;
//echo $sessDir;
	$dirOk=true;
	if (!is_dir($sessDir)) {
		umask(0);
		$dirOk=@mkdir($sessDir, 0777); 
	}
	if ($dirOk)
		ini_set('session.save_path', $sessDir);
*/
    session_start();

    SetSessionExpiration($cookie_timeout);
}

function EndSession() {
    session_unset();
	setcookie (session_name(), '', (time () - 2592000), '/', '', 0);
    session_destroy();
}

function UnsetSessionValue($key) {
	unset($_SESSION[$key]);     
}

function SetSessionValue($key, $value) {
    $_SESSION[$key]=$value;
    return ($_SESSION[$key]==$value);
}

function GetSessionValue($key) {
    if (isset($_SESSION[$key]))
    	return $_SESSION[$key];
    else
    	return '';
}

function MySripSlashes($val)
{
	if (get_magic_quotes_gpc())
		return stripslashes($val);
	else
		return $val;
}

function GetArg($key, &$arg)
{
	global $VARGS;
	
    $keyVal='';
	if (isset($_REQUEST[$key]))
        $keyVal=$_REQUEST[$key];
	else if (isset($VARGS[$key]))
		$keyVal=$VARGS[$key];
	else
		return false;
/*
    if (array_key_exists($key, $_GET))
        $keyVal=$_GET[$key];
    else if (array_key_exists($key, $_POST))
        $keyVal=$_POST[$key];
    else if (array_key_exists($key, $GLOBALS))
        $keyVal=$GLOBALS[$key];
	else
		return false;
*/
    $valStr='';
    if (is_array($keyVal)) {
        foreach ($keyVal as $value) {
            if ($value!='') {
	            if ($valStr=='')
	                $valStr=MySripSlashes($value);
	            else
	                $valStr.=", $value";
	        }
        }
    } else {
        $valStr=MySripSlashes($keyVal);
    }
	// remove php tag injection
    $valStr=str_replace("<?", "", $valStr);
    $valStr=str_replace("?>", "", $valStr);
	
	$arg=$valStr;	
    return true;
}

function IsSubmitted($submitName)
{
    return (isset($_POST[$submitName]));
}

// This function is not used anymore as it doesn't support SMTP authentication
/*
function SendMail2($from, $to, $subject, $body, $cc="", $bcc="", $atth="", $atthStr="")
{       	
    $message='';
    $headers='';
	if ($atth!="" && $atthStr!="") {

		$headers ="MIME-Version: 1.0\n";
	   	$headers.="From: $from\n";
	    if ($cc!="")
	    	$headers.="Cc: $cc\n";
	    if ($bcc!="")
	    	$headers.="Bcc: $bcc\n";
	    	
		$atthStr=chunk_split(base64_encode($atthStr));
	
		$headers .= "Content-Type: multipart/mixed;\n\tboundary=\"MIME_BOUNDRY_OUTER\"\n";
		
		$message .= "This is a multi-part message in MIME format.\n";			
		$message .= "\n--MIME_BOUNDRY_OUTER\n";			
//			$message .="Content-Type: multipart/alternative;\n\tboundary=\"MIME_BOUNDRY_INNER\"\n\n";			
//			$message .= "\n--MIME_BOUNDRY_INNER\n";
		$message .= "Content-Type: text/plain;\n\tcharset=\"iso-8859-1\"\n";
//			$message .= "Content-Transfer-Encoding: quoted-printable\n\n";

		$message .= $body;
		
		$message .= "\n\n";
//			$message .= "\n--MIME_BOUNDRY_INNER\n\n";
		$message .= "\n--MIME_BOUNDRY_OUTER\n";
		$message .= "Content-Type: text/plain;\n\tname=\"".$atth."\"\n";
		$message .= "Content-Transfer-Encoding: base64\n";
		$message .= "Content-disposition: attachment;\n\tfilename=\"".$atth."\"\n\n";
		$message .= "\n";
		$message .= "$atthStr\n\n";
		//message ends
		$message .= "\n--MIME_BOUNDRY_OUTER--\n";
		
	} else {
	
	   	$headers.="From: $from\r\n";
	    if ($cc!="")
	    	$headers.="Cc: $cc\r\n";
	    if ($bcc!="")
	    	$headers.="Bcc: $bcc\r\n";
	    	
//	     	$headers.="Reply-To: $from\r\n";
//	    $headers.="X-Mailer: PHP/". phpversion()."\r\n";

		$message.=$body;
	}

	if (!@mail($to, $subject, $message, $headers))
		return "Failed to send email from the mail server.";
	
	return ''; 
}
*/

function ErrorExit($msg)
{
include_once("includes/log_error.php");
	echo("ERROR ".$msg);
	LogError($msg);
	DoExit();
}

function ShowError($msg)
{
//	$msg=htmlspecialchars($msg);
	$msg=str_replace("\n", "<br>", $msg);
	$msg=str_replace("\\", "", $msg);
//	if (isset($GLOBALS['THEME'])) {
//		$errorIcon="themes/error.gif";
//		echo "<div class=\"error\"><img src=\"$errorIcon\"> $msg</div>";
//	} else {
		echo "<div class=\"error\">$msg</div>";
//	}
}

function DoExit()
{
require_once("dbobjects/vobject.php");
	VObject::CloseDB();
	exit();
}


function ShowMessage($msg)
{
//	$msg=htmlspecialchars($msg);
	$msg=str_replace("\n", "<br>", $msg);
	$msg=str_replace("\\", "", $msg);
/*	if (isset($GLOBALS['THEME'])) {
		$informIcon="themes/inform.gif";
		echo "<div class=\"inform\"><img src=\"$informIcon\"> $msg</div>";
	} else {
*/
		echo "<div class=\"inform\">$msg</div>";
//	}
}


function H24ToH12($hh, $mm, $ss=null)
{
	$ih=intval($hh);
	if ($ih==0)
		$h12='12';
	else if ($ih>12)
		$h12=$ih-12;
	else
		$h12=$ih;		
	
	$h12.=":".$mm;
	
	if (isset($ss))
		$h12.=":".$ss;

	if ($ih<12)
		$h12.=' am';
	else
		$h12.=' pm';
		
	return $h12;
}
/*
function TimeZoneSelector($selected)
{
	$str="<select name=\"time_zone\">";
	
	$selhr=substr($selected, 0, strlen($selected)-3); // remove :00
	
	for ($i=-12; $i<=13; $i++) {
		
		$tz='';
		if ($i>=0)
			$tz='+';

		$tz.=(string)$i.":00";
		$tzstr="GMT$tz";
		
		if ($i==$selhr)
			$str.="<option value=\"$tz\" selected>$tzstr</option>";
		else
			$str.="<option value=\"$tz\">$tzstr</option>";
	}
   $str.="</select>";
	return $str;
}
*/
function IsDST($tz)
{
	
// doesn't work if the server is on GMT
	//if (date("I") == 1)
	//	return true;
}
/*
function GetTZName($tz, $dst)
{
	$tzName='GMT'.$tz;

	if ($dst=='Y') {
		if ($tz=="-10:00")
			$tzName="Hawaii Standard Time";
		elseif ($tz=="-08:00")
			$tzName="Pacific Daylight Time";
		elseif ($tz=="-07:00")
			$tzName="Mountain Daylight Time";
		elseif ($tz=="-06:00")
			$tzName="Central Daylight Time";
		elseif ($tz=="-05:00")
			$tzName="Eastern Daylight Time";
	} else {

		if ($tz=="-10:00")
			$tzName="Hawaii Standard Time";
		elseif ($tz=="-08:00")
			$tzName="Pacific Standard Time";
		elseif ($tz=="-07:00")
			$tzName="Mountain Standard Time";
		elseif ($tz=="-06:00")
			$tzName="Central Standard Time";
		elseif ($tz=="-05:00")
			$tzName="Eastern Standard Time";
	}
		
	return $tzName;	
}

*/
function GetTimeZones($selected, $onChangeStr='')
{
	if ($onChangeStr!='')
		$str="<select id=\"time_zone\" name=\"time_zone\" onchange=\"$onChangeStr\">";
	else
		$str="<select id=\"time_zone\" name=\"time_zone\">";
	
//	$dst=GetSessionValue("dst");	
	if (is_daylight_savings(time(), "-08:00")) {
		$dst=true;
	} else {
		$dst=false;	
	}

	for ($i=-10; $i<=26; $i++) {
		
		if ($i<0) {
			$hh=(string)$i;
/*			
			if ($i==-10) {
				$val="HT";
				$tzName="Hawaii Standard Time";
			} else */
			if ($i==-8 && !$dst) {
				$val="PT";
				//$tzName="Pacific Standard Time";
				$tzName="Pacific Time";
			} else if ($i==-7 && $dst) {
				$val="PT";
				//$tzName="Pacific Daylight Time";
				$tzName="Pacific Time";
			} else if ($i==-7 && !$dst) {
				$val="MT";
				//$tzName="Mountain Standard Time";
				$tzName="Mountain Time";
			} else if ($i==-6 && $dst) {
				$val="MT";
				//$tzName="Mountain Daylight Time";
				$tzName="Mountain Time";
			} else if ($i==-6 && !$dst) {
				$val="CT";
				//$tzName="Central Standard Time";
				$tzName="Central Time";
			} else if ($i==-5 && $dst) {
				$val="CT";
				//$tzName="Central Daylight Time";
				$tzName="Central Time";
			} else if ($i==-5 && !$dst) {
				$val="ET";
				//$tzName="Eastern Standard Time";
				$tzName="Eastern Time";
			} else if ($i==-4 && $dst) {
				$val="ET";
				//$tzName="Eastern Daylight Time";
				$tzName="Eastern Time";
			} else {
				continue;
			}			
			
		} else {
		
			$hh=(string)abs($i-12);
			if ($i<3)
				$val="-".$hh.":00";
			else if ($i<12)
				$val="-0".$hh.":00";
			else if ($i==12)
				$val="+0".$hh.":00";
			else if ($i<22)
				$val="+0".$hh.":00";
			else
				$val="+".$hh.":00";
			
			$tzName="UTC".$val;
			
		}
		
		if ($val==$selected)
			$str.="<option value=\"$val\" selected>$tzName</option>\n";
		else
			$str.="<option value=\"$val\">$tzName</option>\n";
			
		// add :30 increment for these time zones
		if ($i==17 || $i==21 || $i==22) {
			$hh=$i-12;
			if ($i<22)
				$val="+0".$hh.":30";
			else			
				$val="+".$hh.":30";
			$tzName="UTC".$val;
			if ($val==$selected)
				$str.="<option value=\"$val\" selected>&nbsp;&nbsp;$tzName</option>\n";
			else
				$str.="<option value=\"$val\">&nbsp;&nbsp;$tzName</option>\n";
			
		}
		
	}
    $str.="</select>\n";
	return $str;
}	

function GetServerTimeZone(&$tzName, &$tzOffset)
{
	$tzOffset=date('O'); // e.g. -0700
	$tzOffset=substr($tzOffset, 0, strlen($tzOffset)-2);
	$tzOffset.=":00"; // e.g. -07:00
	$tzName=date('T');
}

function GetTimeZoneByDate($stz, $dateVal, &$tzName, &$tz)
{
	list($dateStr, $timeStr)=explode(" ", $dateVal);
	list($year, $month, $day)=explode("-", $dateStr);
	list($hour, $min, $sec)=explode(":", $timeStr);
	$val=mktime($hour, 0, 0, $month, $day, $year);
	GetTimeZoneByTime($stz, $val, $tzName, $tz);
}
function GetTimeZoneByTime($stz, $timeVal, &$tzName, &$tz)
{
	if ($stz=='PT' || $stz=='MT' || $stz=='CT' || $stz=='ET') {
		if (is_daylight_savings($timeVal, "-08:00")) {
			$dst=true;
		} else {
			$dst=false;	
		}
		
		if ($stz=='PT') {
			if ($dst) {
				$tz="-07:00";
//				$tzName="Pacific Daylight Time";
				$tzName="Pacific Time";
			} else {
				$tz="-08:00";
//				$tzName="Pacific Standard Time";
				$tzName="Pacific Time";
			}
		} else if ($stz=='MT') {
			if ($dst) {
				$tz="-06:00";
//				$tzName="Mountain Daylight Time";
				$tzName="Mountain Time";
			} else {
				$tz="-07:00";
//				$tzName="Mountain Standard Time";
				$tzName="Mountain Time";
			}
		} else if ($stz=='CT') {
			if ($dst) {
				$tz="-05:00";
//				$tzName="Central Daylight Time";
				$tzName="Central Time";
			} else {
				$tz="-06:00";
//				$tzName="Central Standard Time";
				$tzName="Central Time";
			}		
		} else if ($stz=='ET') {
			if ($dst) {
				$tz="-04:00";
//				$tzName="Eastern Daylight Time";
				$tzName="Eastern Time";
			} else {
				$tz="-05:00";
//				$tzName="Eastern Standard Time";
				$tzName="Eastern Time";
			}		
		}
	} else {
		$tz=$stz;
		$tzName="UTC".$tz;
	}	
}

function GetTimeZoneName($stz, &$tzName, &$tz)
{
	if ($stz=='')
		$stz='PT';
/*
	if ($stz=='PT' || $stz=='MT' || $stz=='CT' || $stz=='ET') {
		if (is_daylight_savings(time(), "-08:00")) {
			$dst=true;
		} else {
			$dst=false;	
		}
	}

	if ($stz=='PT') {
		if ($dst) {
			$tz="-07:00";
			$tzName="Pacific Daylight Time";
		} else {
			$tz="-08:00";
			$tzName="Pacific Standard Time";
		}
	} else if ($stz=='MT') {
		if ($dst) {
			$tz="-06:00";
			$tzName="Mountain Daylight Time";
		} else {
			$tz="-07:00";
			$tzName="Mountain Standard Time";
		}
	} else if ($stz=='CT') {
		if ($dst) {
			$tz="-05:00";
			$tzName="Central Daylight Time";
		} else {
			$tz="-06:00";
			$tzName="Central Standard Time";
		}		
	} else if ($stz=='ET') {
		if ($dst) {
			$tz="-04:00";
			$tzName="Eastern Daylight Time";
		} else {
			$tz="-05:00";
			$tzName="Eastern Standard Time";
		}		
	} else {
		$tz=$stz;
//		$tzName="GMT".$tz;
		$tzName="UTC".$tz;
	}
*/
	GetTimeZoneByTime($stz, time(), $tzName, $tz);	
	return $tzName;
	
}
function GetSessionTimeZone(&$tzName, &$tz)
{
	$stz=GetSessionValue('time_zone');	
		
	GetTimeZoneName($stz, $tzName, $tz);
}

function BreakText($srcText, $maxLen)
{
	if (strlen($srcText)>$maxLen){
		$retText=substr($srcText, 0, $maxLen);
		$retText.="<br>";
		$retText.=substr($srcText, $maxLen);
		return $retText;
	} else {
		return $srcText;
	}
}

/**
* @param string $listPage url of the listing page
* @param integer $startRow index to the starting row
* @param integer $currentRow index to the current row to display
* @param integer $totalRows total number of rows in the listing
* @param integer $rowsPerPage number of rows to display in each page
* @param integer $numPages number of pages to show in the navigation bar
* @param integer $pageInc number of pages to increment/decrement when the next/previous link is clicked
* @return null print html code
*/

function ShowPageNavBar($listPage, $startRow, $currentRow, $totalRows, $rowsPerPage, $numPages, $pageInc)
{
	global $gText;
	
	$row1=$currentRow+1;
	if ($row1>$totalRows)
		$row1=$totalRows;
	$row2=$currentRow+$rowsPerPage;
	if ($row2>$totalRows)
		$row2=$totalRows;
		
	echo "<div class=\"page_nav\">\n";
	echo "<ul>\n";
	$lc=htmlspecialchars("<");
	$rc=htmlspecialchars(">");
	
	$target=$GLOBALS['TARGET'];
	// first
	if ($totalRows>0) {
		$offset=0;
		/*
		$lastRow=$rowsPerPage*($numPages-1);
		if ($lastRow>$totalRows)
			$lastRow=$totalRows;
		$offset=$currentRow;
		if ($offset>$lastRow)
			$offset=$lastRow;
		*/
		$link=$listPage."&offset=$offset&count=$rowsPerPage&total=$totalRows&start=0";
		echo "<li><a target=\"$target\" href=\"$link\">|$lc</a></li>\n";
	}

	// previous
	$previous=$startRow-$rowsPerPage*$pageInc;
	if ($previous>=0) {
		$lastRow=$previous+$rowsPerPage*($numPages-1);
		if ($lastRow>$totalRows)
			$lastRow=$totalRows;
		$offset=$currentRow;
		if ($offset>$lastRow)
			$offset=$lastRow;
		$link=$listPage."&offset=$offset&count=$rowsPerPage&total=$totalRows&start=$previous";
		echo "<li><a target=\"$target\" href=\"$link\">$lc</a></li>\n";
	}

	$pos=$startRow;
	$index=floor($pos/$rowsPerPage);
	$max=$pos+$numPages*$rowsPerPage;
	if ($max>$totalRows)
		$max=$totalRows;
	while ($pos<$max) {
		$index++;
		$link=$listPage."&offset=$pos&count=$rowsPerPage&total=$totalRows&start=$startRow";
		if ($pos==$currentRow)
			echo "<li class=\"on\"><a target=\"$target\" href=\"$link\">$index</a></li>\n";
		else
			echo "<li><a target=\"$target\" href=\"$link\">$index</a></li>\n";
		$pos+=$rowsPerPage;
	}

	// next
	$next=(int)($startRow)+$pageInc*$rowsPerPage;
	if ($next<$totalRows && $next>1) {
		$offset=$currentRow;
		if ($offset<$next)
			$offset=$next;
		$link=$listPage."&offset=$offset&count=$rowsPerPage&total=$totalRows&start=$next";
		echo "<li><a target=\"$target\" href=\"$link\">$rc</a></li>\n";
	}
	
	// last
	if ($totalRows>$rowsPerPage) {
		$lastPage=ceil($totalRows/$rowsPerPage);
		$startRow=($lastPage-1)*$rowsPerPage;
		$npage=$lastPage%$pageInc;
		if ($npage==0)
			$npage=$pageInc;
		$startPage=$lastPage-$npage;
		if ($startPage<0)
			$startPage=0;
		$start=$startPage*$rowsPerPage;
		
		$offset=$currentRow;
		if ($offset<$start)
			$offset=$start;
		
		$link=$listPage."&offset=$offset&count=$rowsPerPage&total=$totalRows&start=$start";
		echo "<li><a target=\"$target\" href=\"$link\">$rc|</a></li>\n";
	}
	echo "</ul>\n";
	echo "<span class='page_nav_count'>$row1-$row2  ${gText['M_TOTAL']}: $totalRows</span>";
	echo "</div>\n";
		
}

function RandomPassword($len = 6, $number=true)
{
	if ($number) {
		// assume $len is 6 for now
		$pass=(string)mt_rand(100000, 999999);		
	} else {
		$pass = '';
		$lchar = 0;
		$char = 0;
		for($i = 0; $i < $len; $i++)
		{
			while($char == $lchar)
			{
				$char = rand(48, 109);
				if($char > 57) $char += 7;
				if($char > 90) $char += 6;
			}
			$pass .= chr($char);
			$lchar = $char;
		}
	}
	return $pass;
}

function SecToStr($seconds)
{
	$hh=floor($seconds/3600);
	$seconds-=$hh*3600;
	$mm=floor($seconds/60);
	$ss=$seconds-$mm*60;
	
	if ($hh<10)
		$hhs="0".strval($hh);
	else
		$hhs=strval($hh);
	if ($mm<10)
		$mms="0".strval($mm);
	else
		$mms=strval($mm);
	if ($ss<10)
		$sss="0".strval($ss);
	else
		$sss=strval($ss);	

	return $hhs.":".$mms.":".$sss;
}

function GetServerUrl()
{
	if ($_SERVER['SERVER_PORT']=="80")
		$url="http://".$_SERVER['SERVER_NAME'];
	else if ($_SERVER['SERVER_PORT']=="443")
		$url="https://".$_SERVER['SERVER_NAME'];
	else {
		$url="http://".$_SERVER['SERVER_NAME'];
		$url.=":".$_SERVER['SERVER_PORT'];
	}	

	return $url."/";
}
function GetScriptUrl()
{
	$url=GetServerUrl();
	$path=$_SERVER["SCRIPT_NAME"];
	$url.=$path;
	return $url;
}
function GetScriptDirUrl()
{
	$url=GetServerUrl();
	$path=$_SERVER["SCRIPT_NAME"];
	if (strlen($path)>1) {
		$path=str_replace(basename($path), '', $path);
	}
	$url.=$path;
	return $url;
}
function FormKeyToText($key)
{
	global $gText;
	$key1=str_replace('[','', $key);
	$key1=str_replace(']', '', $key1);
	$key1="M_".$key1;
	
	if (isset($gText[$key1]))
		return $gText[$key1];
	else	
		return $key;
}

/**
* function for working out if it is daylight savings for a specific date.
* must specify when daylight savings starts and ends in the format
* "[1=First,2=Second,3=Third,4=Fourth,L=Last]:NameOfDay:Of The Month"
* e.g. "L:Sun:3" = Last Sunday in March
* some will not meet this criteria but most do.
*
* see - http://webexhibits.org/daylightsaving/g.html  for daylight savings times around the world.
* 
*/
//function is_daylight_savings($gmtime,$DSTStart = '',$DSTEnd = ''){
function is_daylight_savings($gmtime, $tz) {
	
	if ($tz=="-08:00" || $tz=="-07:00" || $tz=="-06:00" || $tz=="-05:00") {
		$DSTStart = "2:Sun:3";
		$DSTEnd = "1:Sun:11";		
	} else {
		return false;
	}
	
 //global $locale;
 //For Most Of Australia
 //DayLightSavings Starts Last sunday in October
/*
 $DSTStart = "L:Sun:10";//$locale['DSTStart'];
 //DayLightSavings Ends Last Sunday in March
 $DSTEnd = "L:Sun:3";//$locale['DSTEnd'];
 */

 //$DSTStart = split(":",$DSTStart);
 //$DSTEnd = split(":",$DSTEnd);
$DSTStart = explode(":",$DSTStart);
$DSTEnd = explode(":",$DSTEnd);
 
 $gmtMonth = date("n",$gmtime);
//echo("gmtMonth=".$gmtMonth." startm=".$DSTStart[2]." endm=".$DSTEnd[2]);
 // if not even in the Important changeover months.

if (($DSTStart[2]<$DSTEnd[2]) && ($gmtMonth < $DSTStart[2] || $gmtMonth > $DSTEnd[2])) {
	// for northern hemisphere
	return false;
} else if ($DSTStart[2]>=$DSTEnd[2] && $gmtMonth < $DSTStart[2] && $gmtMonth > $DSTEnd[2]) {
	// for down under
	return false;
 } else if ($DSTStart[2]<$DSTEnd[2] && $gmtMonth > $DSTStart[2] && $gmtMonth < $DSTEnd[2]) {
	// for northern hemisphere
	return true;
 } else if (($DSTStart[2]>=$DSTEnd[2]) && ($gmtMonth > $DSTStart[2] || $gmtMonth < $DSTEnd[2])) {
	// for down under
	return true;
 } else {
  //it is in the Start or End Month
  if ($gmtMonth == $DSTStart[2]){
     $True = true;
     $week = $DSTStart[0];
     $ImportantDay = $DSTStart[1];
  } else {//it is in the End Month
     $True = false;
     $week = $DSTEnd[0];
     $ImportantDay = $DSTEnd[1];
  }
   //get the day of the month
   $gmtDay = date("j",$gmtime);
   //work out what week it starts/ends.
   if ($week == 'L'){
     $week = 4;
     $ldom = 4;//last day of month factor
   } else {
	$ldom=0;
	}
   //if the week in which it starts/ends has not been reached
   if($gmtDay <= ($week-1)*7){
     return (!$True);
   } else {
     $gmtDate = getdate($gmtime);
     //go by a Day of the Week Basis
     for ($i=($week-1)*7+1;$i<=(($week*7)+$ldom);$i++){
       $checkDate = mktime(0,0,0,$gmtDate["mon"],$i,$gmtDate["year"]);
       //get the actual day it starts/ends
       if (date("D",$checkDate) == "Sun" && date("n",$checkDate) == $gmtMonth ){
         $day = date("j",$checkDate);
       }
     }
//echo(" day=".$day." gmtDay=".$gmtDay);
     if ($gmtDay < $day) {//if it has not reached the day
       return (!$True);
     } else {
       return $True;
     }
   }
 }
}

function Get12Months($selected)
{
	global $gText;
	
	$thisYear=(int)date('Y');
	$thisMonth=(int)date('n');
	$endMonth=$thisMonth;
	
	$startMonth=$endMonth-11;
	if ($startMonth<1) {
		$startMonth+=12;
		$startYear=$thisYear-1;	
	} else {
		$startYear=$thisYear;
	}
	
	$str="<select name=\"date\">\n";
	$str.="<option value=\"\">".$gText['M_ALL_MONTHS']."</option>\n";
	
	if ($selected=='TODAY')
		$str.="<option selected value=\"TODAY\">".$gText['M_TODAY']."</option>\n";
	else
		$str.="<option value=\"TODAY\">".$gText['M_TODAY']."</option>\n";
	
	// if a particular date is selected, show that as the selection
	if ($selected=='NOW') {
		$str.="<option selected value=\"$selected\">Now</option>\n";
	} elseif ($selected!='TODAY') {
		$items=explode('-', $selected);
		// day is set
		if (isset($items[2]))
			$str.="<option selected value=\"$selected\">$selected</option>\n";
	}

	
	for ($i=$endMonth; $i>=1; $i--) {
		if ($i<10)
			$val='0'.(string)$i;
		else
			$val=(string)$i;

		$monStr=$thisYear."-".$val;
			
		if ($monStr==$selected)
			$str.="<option value=\"$monStr\" selected>$monStr</option>\n";
		else
			$str.="<option value=\"$monStr\">$monStr</option>\n";

	}
	
	if ($i!=$endMonth) {
		for ($i=12; $i>=$startMonth; $i--) {
				
			if ($i<10)
				$val='0'.(string)$i;
			else
				$val=(string)$i;

			$monStr=$startYear."-".$val;
				
			if ($monStr==$selected)
				$str.="<option value=\"$monStr\" selected>$monStr</option>\n";
			else
				$str.="<option value=\"$monStr\">$monStr</option>\n";
		}
	}
	

    $str.="</select>\n";
	return $str;
}


// send HTTP HEAD request to get a remote file size
// this code is not tested yet
/*
function remote_file_size ($url){ 
	$head = ""; 
	$url_p = parse_url($url); 
	$host = $url_p["host"]; 
*/
//	if(!preg_match("/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/",$host)){
		// a domain name was given, not an IP
//		$ip=gethostbyname($host);
//		if(!preg_match("/[0-9]*\.[0-9]*\.[0-9]*\.[0-9]*/",$ip)){
			//domain could not be resolved
//			return -1;
//		}
//	}
/*
	$port = intval($url_p["port"]); 
	if(!$port) $port=80;
	$path = $url_p["path"]; 
	//echo "Getting " . $host . ":" . $port . $path . " ...";

	$fp = fsockopen($host, $port, $errno, $errstr, 20); 
	if(!$fp) { 
		return false; 
		} else { 
		fputs($fp, "HEAD "  . $url  . " HTTP/1.1\r\n"); 
		fputs($fp, "HOST: " . $host . "\r\n"); 
		fputs($fp, 'User-Agent: http://www.example.com/my_application\r\n');
		fputs($fp, 'Connection: close\r\n\r\n'); 
		$headers = ""; 
		while (!feof($fp)) { 
			$headers .= fgets ($fp, 128); 
			} 
		} 
	fclose ($fp); 
	//echo $errno .": " . $errstr . "<br />";
	$return = -2; 
	$arr_headers = explode("\n", $headers); 
	// echo "HTTP headers for <a href='" . $url . "'>..." . substr($url,strlen($url)-20). "</a>:";
	// echo "<div class='http_headers'>";
	foreach($arr_headers as $header) { 
		// if (trim($header)) echo trim($header) . "<br />";
		$s1 = "HTTP/1.1"; 
		$s2 = "Content-Length: "; 
		$s3 = "Location: "; 
		if(substr(strtolower ($header), 0, strlen($s1)) == strtolower($s1)) $status = substr($header, strlen($s1)); 
		if(substr(strtolower ($header), 0, strlen($s2)) == strtolower($s2)) $size   = substr($header, strlen($s2));  
		if(substr(strtolower ($header), 0, strlen($s3)) == strtolower($s3)) $newurl = substr($header, strlen($s3));  
		} 
	// echo "</div>";
	if(intval($size) > 0) {
		$return=intval($size);
	} else {
		$return=$status;
	}
	// echo intval($status) .": [" . $newurl . "]<br />";
	if (intval($status)==302 && strlen($newurl) > 0) {
		// 302 redirect: get HTTP HEAD of new URL
		$return=remote_file_size($newurl);
	}
	return $return; 
} 
*/

function HTTP_Request($url, $data='', $method='GET', $timeout=15){

	// check if curl is installed
//	if (defined('CURLOPT_PORT')) {
	if (function_exists('curl_init')) {
		if ($method=='GET') {
			if ($data!='')
				$url.="?".$data;
		}
		
//		$header[]= "Accept: */*";
		
		$ch = curl_init($url);
		// Check for the constant definitions. Some constants may not be defined.
		if (defined('CURLOPT_SSL_VERIFYPEER'))
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		if (defined('CURLOPT_SSL_VERIFYHOST'))
			curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		if (defined('CURLOPT_USERAGENT'))
			curl_setopt($ch, CURLOPT_USERAGENT, "Web Conferencing/2.0 (Management Server)");
//		if (defined('CURLOPT_HTTPHEADER'))
//			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		if (defined('CURLOPT_TIMEOUT'))
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if (defined('CURLOPT_RETURNTRANSFER'))
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// CURLOPT_FOLLOWLOCATION cannot be activated when in safe_mode or an open_basedir is set
//		if (defined('CURLOPT_FOLLOWLOCATION'))
//			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		if (defined('CURLOPT_AUTOREFERER'))
			curl_setopt($ch, CURLOPT_AUTOREFERER, 1);		
		if ($method=='POST') {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);			
		}

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	} else {
		if ($method=='GET') {
			if ($data!='')
				$url.="?".$data;
			return @file_get_contents($url);
		} else {
			
			return false;
/*			
// the following code doesn't seem to work. don't need it anyway as long as curl is enabled.

			$urlInfo=parse_url($url);
			
			$server=$urlInfo['host'];
			$hostName=$server;
			
			$port=80;
			if ($urlInfo['scheme']=='https') {
				$port=443;
				$server="ssl://".$hostName;
			}
				
			if (isset($urlInfo['port'])) {
				$port=$urlInfo['port'];
			}
			$path=$urlInfo['path'];
			
			$errorCode=0;
			$erroMsg='';		
			$sock = fsockopen($server, $port, $errorCode, $errorMsg, 30); 
			if (!$sock) {
				return false;
			}
			$dataSize=strlen($data);
			fputs($sock, "POST $path HTTP/1.0\r\n"); 
			fputs($sock, "Host: $hostName\r\n"); 
			fputs($sock, "Content-type: application/octet-stream\r\n"); 
			fputs($sock, "Content-length: " . $dataSize . "\r\n"); 
			fputs($sock, "\r\n");
			fputs($sock, "$data\r\n");
			fputs($sock, "\r\n"); 
			
			$header = ""; 
			while ($str = trim(fgets($sock, 4096)))
				$header .= "$str\n";
						
			$response = "";
			$size=0;
			while (!feof($sock) && $size<10000) {
				$response .= fgets($sock, 4096);
				$size+=4096;
			}
			
			fclose($sock); 
			
			list($version, $errorCode, $errorMsg)=sscanf($header, "%s %d %s");
			if ($errorCode>=400)
				return false;
			else
				return $response;
*/			
		}
	}
}

// http://www.phpit.net/code/valid-email/
// This function is used to check if an e-mail address is valid. It’s been created by Dave Child, and it’s probably one of the best e-mail validation functions out there (it actually follows all the specifications).

function valid_email($email) {
	// First, we check that there's one @ symbol, and that the lengths are right
//	if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
	if (!preg_match("/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/", $email)) {
		// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
		return false;
	}
/*
	// Split it into sections to make life easier
	$email_array = explode("@", $email);
	$local_array = explode(".", $email_array[0]);
	for ($i = 0; $i < sizeof($local_array); $i++) {
		if (!ereg("^(([A-Za-z0-9!#$%&#038;'*+/=?^_`{|}~-][A-Za-z0-9!#$%&#038;'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) {
			return false;
		}
	}  
	if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
		$domain_array = explode(".", $email_array[1]);
		if (sizeof($domain_array) < 2) {
			return false; // Not enough parts to domain
		}
		for ($i = 0; $i < sizeof($domain_array); $i++) {
			if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) {
				return false;
			}
		}
	}
*/
	return true;
}

function AddSpacesToPhone($phone) {
	if (strlen($phone)==10) {
		$nn=$phone[0];
		$nn.=$phone[1];
		$nn.=$phone[2];
		$nn.=" ";
		$nn.=$phone[3];
		$nn.=$phone[4];
		$nn.=$phone[5];
		$nn.=" ";
		$nn.=$phone[6];
		$nn.=$phone[7];
		$nn.=$phone[8];
		$nn.=$phone[9];
		return $nn;
	}
	
	return $phone;
}

function RemoveSpacesFromPhone($num, $removePrefix=true)
{
	// remove the long distance prefix if present
	if ($removePrefix && $num!='' && $num[0]=='1')
		$num=substr($num, 1);
/*
	// probably should change this to remove any non-numeric char
	$num=str_replace(" ", "", $num);
	$num=str_replace("-", "", $num);
	$num=str_replace("(", "", $num);
	$num=str_replace(")", "", $num);
	$num=str_replace("#", "", $num);
	$num=str_replace("+", "", $num);
	$num=str_replace("&", "", $num);
	$num=str_replace("?", "", $num);
	$num=str_replace("=", "", $num);
*/
	$num=RemoveNonNumbers($num);
	return $num;
}
// remove any non-numeric char
function RemoveNonNumbers($str)
{
	$len=strlen($str);
	$output='';
	$ord0=ord('0');
	$ord9=ord('9');

	for ($i=0; $i<$len; $i++) {
		$ordVal=ord($str[$i]);
		if ($ordVal>=$ord0 && $ordVal<=$ord9) {
			$output.=$str[$i];
		}
	}
	return $output;
	
}
function RemoveComma($str)
{
	if (strpos($str, ",")!==false)
		return "\"$str\"";
	else
		return $str;
}
function BytesToMB($bytes)
{
	return (ceil($bytes*1000/(1024*1024))/1000);
}

function ComputeScriptSignature($ip)
{
	return md5("ip".$ip);
}

// find a localized string in $gText that corresponds to the input text
// $gText is an array that contains the localized string indexed with the md5 hash of the text
// use gettext.php to scan all php files to create the gText array
// return the original text if no localized string is found
function _Text($text)
{
	// use this in conjunction with setlocale in brand.php
//	return gettext($text);

	global $gltext;
	
	$key=md5($text);
		
	if (isset($gltext[$key]))
		return $gltext[$key];
	else
		return $text;

}
function GetArrayValue($arr, $key, $default='')
{
	if (array_key_exists($key, $arr))
		return $arr[$key];
	else
		return $default;
}

// detect if the user is connecting from an iPhone or iPod
function IsIPhoneUser() {
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if (strpos($userAgent, "iphone")!==false || strpos($userAgent, "ipod")!==false){
			return true;
		}
	}
	return false;
}

function IsIPadUser() {
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		$userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if (strpos($userAgent, "ipad")!==false){
			return true;
		}
	}
	return false;
}


function GetLocaleOptions($optName, $selectedOpt, $id='', $onChange='')
{	
	global $gLocaleTable;
	
	if ($onChange!='')
		$opts="<select id=\"$id\" name=\"$optName\" onchange=\"$onChange\">\n";
	else
		$opts="<select id=\"$id\" name=\"$optName\">\n";

	foreach ($gLocaleTable as $key => $name) {
		if ($key==$selectedOpt)
			$selected='selected';
		else
			$selected='';
		$opts.="<option $selected value='$key'>$name</option>";
	}

/*		
	if ($dh = @opendir($theDir)) { 

		while (($file = readdir($dh)) !== false) { 
			if ($file=="." || $file=="..")
				continue;
				
			// skip files start with '_'
			if ($file{0}=='_')
				continue;
				
			if ($fileExt) {
				$paths=pathinfo($file);
				if ($paths['extension']!=$fileExt)
					continue;
			}
				
			if (is_file($theDir.$file)) {
				$file=str_replace(".php", "", $file);
				if ($file==$selectedOpt)
					$selected='selected';
				else
					$selected='';
					
				if (isset($nameLookUpTable[$file]))
					$name=$nameLookUpTable[$file];
				else
					$name=$file;
				$opts.="<option $selected value='$file'>$name</option>";
			}
		}
	}
*/	
	$opts.="</select>";
	return $opts;

}

function GetUSStates()
{
	$states=array(
	"Alabama",
	"Alaska",
	"Arizona",
	"Arkansas",
	"California",
	"Colorado",
	"Connecticut",
	"Delaware",
	"District of Columbia",
	"Florida",
	"Georgia",
	"Hawaii",
	"Idaho",
	"Illinois",
	"Indiana",
	"Iowa",
	"Kansas",
	"Kentucky",
	"Louisiana",
	"Maine",
	"Maryland",
	"Massachusetts",
	"Michigan",
	"Minnesota",
	"Mississippi",
	"Missouri",
	"Montana",
	"Nebraska",
	"Nevada",
	"New Hampshire",
	"New Jersey",
	"New Mexico",
	"New York",
	"North Carolina",
	"North Dakota",
	"Ohio",
	"Oklahoma",
	"Oregon",
	"Pennsylvania",
	"Rhode Island",
	"South Carolina",
	"South Dakota",
	"Tennessee",
	"Texas",
	"Utah",
	"Vermont",
	"Virginia",
	"Washington",
	"Washington, D.C.",
	"West Virginia",
	"Wisconsin",
	"Wyoming",
	);
	
	return $states;
}

function GetTempDir()
{
	if (defined("TEMP_DIR") && TEMP_DIR!='') {
		$dir=TEMP_DIR;
		if ($dir[strlen($dir)-1]!="/")
			$dir.="/";
		return $dir;
	} else
		return "temp/";
}

function GetStatsDir()
{
	if (defined("STATS_DIR") && STATS_DIR!='') {
		$dir=STATS_DIR;
		if ($dir[strlen($dir)-1]!="/")
			$dir.="/";
		return $dir;
	} else
		return "temp/";
}

// translate Microsoft characters into Latin 15
// so the xml parser will work correctly if it contains diamond mark type of characters
// however, this doesn't seem to work for Unicode characters so can't use it
function ConvertSpecialChars($str) {
    return strtr($str,
"\x82\x83\x84\x85\x86\x87\x89\x8a" .
"\x8b\x8c\x8e\x91\x92\x93\x94\x95" .
"\x96\x97\x98\x99\x9a\x9b\x9c\x9e\x9f",
"'f\".**^\xa6<\xbc\xb4''" .
"\"\"---~ \xa8>\xbd\xb8\xbe");
}

function getSecurityCode($answer) {
	return md5(date("d").$answer."per");
}

function getSecurityQuestion() {
	$x=mt_rand(1, 9);
	$y=mt_rand(1, 9);
	$sum=$x+$y;
	$securityCode=getSecurityCode($sum);
	$ques=_Text("Security question:").
		" $x + $y =".
		"<input type=\"number\" name=\"security_answer\" size=\"2\">".
		"<input type=\"hidden\" name=\"security_code\" value=\"$securityCode\">";
	return $ques;
}

function checkSecurityAnswer($answer, $securityCode) {
	return ($securityCode==getSecurityCode($answer));
}
?>
