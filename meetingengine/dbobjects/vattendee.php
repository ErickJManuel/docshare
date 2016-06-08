<?php
/**
 * @package     Web Conerencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
/**
 * @package     VShow
 * @access      public
 */
class VAttendee extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VAttendee($id=0)
	{
		$this->VObject(TB_ATTENDEE);
		$this->SetRowId($id);
	}
	/**
	* @static
	* @return string
	*/		
	function GetXML($attInfo, $teleAttXml='')
	{
		$xml="<attendee userid=\"".$attInfo['attendee_id']."\"\n";
		
		$xml.="startTime=\"".strtotime($attInfo['start_time'])."\"\n";
		$xml.="endTime=\"".strtotime($attInfo['mod_time'])."\"\n";
		$xml.=">\n";
		
		$xml.="<attendeeinfo\n";
		$xml.="fullname=\"".VObject::StrToXml($attInfo['user_name'])."\"\n";
		$xml.="ip=\"".$attInfo['user_ip']."\"\n";
		$xml.="callerid=\"".$attInfo['caller_id']."\"\n";
		$xml.="/>\n";
		
		if ($teleAttXml!='')
			$xml.=$teleAttXml."\n";
		
		$xml.="</attendee>\n";				
		return $xml;
	}


}

?>