<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
/* This file is no longer used */

require_once("vobject.php");
/**
 * @package     VShow
 * @access      public
 */
class VAttendeeLive extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VAttendeeLive($id=0)
	{
		$this->VObject(TB_ATTENDEE_LIVE);
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
		$xml.="drawing=\"".($attInfo['can_draw']=='N'?'false':'true')."\"\n";
		$xml.="isPresenter=\"".($attInfo['can_present']=='N'?'false':'true')."\"\n";
		$xml.="emoticon=\"".VObject::StrToXml($attInfo['emoticon'])."\"\n";
		if (isset($attInfo['show_webcam']) && $attInfo['show_webcam']=='Y')
			$xml.="video=\"true\"\n";
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
	/**
	* Update the mod_time
	* @return integer error code
	 */	
	function Touch()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		
		$info=array();
		//		$info['mod_time']='#NULL';
		$info['mod_time']='#NOW()';
		if ($this->Update($info)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($this->GetErrorMsg());
			return $this->mErrorCode;
		}		
		return $this->mErrorCode;
	}
	/**
	* @return integer error code
	 */	
	function Resume()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		
		$this->GetValue('mod_time', $lastModTime);
		$this->GetValue('break_time', $oldBreakTime);
		
		$query="SELECT TIME_TO_SEC(TIMEDIFF(NOW(), '$lastModTime'))";		
		$ret=VObject::SendQuery($query, $sqlResults);			
		if ($ret!='') {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($ret);
			return $this->mErrorCode;
		}
		$rowInfo= mysql_fetch_row($sqlResults);
		$breakTime=(integer)implode(' ', $rowInfo);
		
		$info=array();
		$info['mod_time']='#NOW()';		
		
		if ($breakTime>VSESSION_TIMEOUT) {
			//			$info['break_time']="#SUM(break_time, TIME_TO_SEC(TIMEDIFF(NOW(), mod_time)))";
			$info['break_time']=$breakTime+(integer)$oldBreakTime;
		}
		
		if ($this->Update($info)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($this->GetErrorMsg());
			return $this->mErrorCode;
		}		
		return $this->mErrorCode;
	}
	
	
}

?>