<?php
/**
 * @package     PRestAPI
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 */
require_once("rest/pcollection.php");
require_once("dbobjects/vregistration.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vregform.php");

/**
 * @package     PRestAPI
 * @access      public
 */
class PRegistrations extends PCollection 
{
	var $meetingInfo=array();
	/**
	 * Constructor
	 */	
	function PRegistrations()
	{
		$this->PRestAPI("registrations");
		$this->mBeginTag="<!--BEGIN_REGISTRATIONS-->";
		$this->mEndTag="<!--END_REGISTRATIONS-->";
		$this->mTableName=TB_REGISTRATION;
		$this->mSynopsis="Registrations is a collection of all meeting registrations.";
		$this->mMethods="GET";
		$this->mRequired=array(
			"meeting_id" => "Meeting id. Returns registrations that belong to the meeting.",			
			);
		$this->mOptional=array(
			"start" => "Index for the starting registration (0 for the first registration.) Default is 0.",
			"count" => "The number of registrations to return. Default (and maximum) is ".$this->mMaxItems.".",
		);
		$this->mReturned=array(
			'[START]' => "The starting index for the records returned.",
			'[NEXT]' => "The starting index for requesting the next set of records. '-1' is returned if no more records are available.",
			);		

	}
	function VerifyInput()
	{
		$meetingAId='';
		if (isset($_GET['meeting_id']))
			$meetingAId=$_GET['meeting_id'];
			
		if ($meetingAId=='')
			return("meeting_id is not provided.");

		$memberId=GetSessionValue('member_id');
		if ($memberId=='')
			return("Access is not authorized.(1)");
		
		$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingAId, $this->meetingInfo);
		if ($errMsg!='')
			return ($errMsg);			

		if (!isset($this->meetingInfo['id']))
			return "No meeting is found to match the input $meetingId.";
		
		if ($this->meetingInfo['brand_id']!=GetSessionValue('member_brand'))
			return("Access is not authorized.(2)");
				
		if ($this->meetingInfo['host_id']!=$memberId && 
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand_name')!=$_REQUEST['brand'])
			)
		{
			return("Access is not authorized.(3)");
		}						

		return '';
	}	


	function Get()
	{
		$respXml=$this->LoadResponseXml();
		$regTemp=$this->GetSubXml('<!--BEGIN_REGISTRATIONS-->', '<!--END_REGISTRATIONS-->', $respXml);
		$fieldTemp=$this->GetSubXml('<!--BEGIN_FIELDS-->', '<!--END_FIELDS-->', $regTemp);
		
		$start=0;
		$count=$this->mMaxItems;
		$brandName='';
		if (isset($_GET['start']))
			$start=$_GET['start'];
		if (isset($_GET['count'])) {
			$count=$_GET['count'];
			if ($count>$this->mMaxItems)
				$count=$this->mMaxItems;
		}
		
		$errMsg=$this->VerifyInput();
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_BAD_REQUEST);
			$this->SetErrorMessage($errMsg);
			return '';
		}
			
		$query="meeting_id='".$this->meetingInfo['id']."'";	

		$errMsg=VObject::SelectAll(TB_REGISTRATION, $query, $result, $start, $count, "*", "id", true);
		if ($errMsg!='') {
			$this->SetStatusCode(PCODE_NOT_FOUND);
			$this->SetErrorMessage($errMsg);
			return '';
		}
		$num_rows = mysql_num_rows($result);
		
		if ($num_rows==0)
			$next=-1;
		else			
			$next=$start+$num_rows;		
			
		$respXml=str_replace("[START]", $start, $respXml);
		$respXml=str_replace("[NEXT]", $next, $respXml);
		
		$newXml='';
		$lastFormId=0;
		$lastFormInfo=null;
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$rowXml=str_replace("[DATE_TIME]", $row['date_time'], $regTemp);
			$rowXml=str_replace("[FULL_NAME]", htmlspecialchars($row['name']), $rowXml);
			$rowXml=str_replace("[EMAIL]", htmlspecialchars($row['email']), $rowXml);
			
			$formId=$row['regform_id'];
			if ($formId>0 && $formId!=$lastFormId) {
				$regForm=new VRegForm($formId);
				$regFormInfo=array();
				$regForm->Get($regFormInfo);
				$lastFormInfo=$regFormInfo;
				$lastFormId=$formId;
			} else if ($formId>0 && $formId==$lastFormId) {
				$regFormInfo=$lastFormInfo;
			} else {
				$regFormInfo=array();				
			}
			

			$newFldXml='';
			for ($i=0; $i<16; $i++) {
				$formKey="key_".$i;
				$regKey="field_".$i;
				$val=isset($row[$regKey])?$row[$regKey]:'';
				$fieldName=isset($regFormInfo[$formKey])?$regFormInfo[$formKey]:$regKey;
				$pos=strpos($fieldName, "=");
				if ($pos!==false)
					$fieldName=substr($fieldName, 0, $pos);
					
				if ($val!='') {
					$aFldXml=str_replace("[FIELD_NAME]", htmlspecialchars($fieldName), $fieldTemp);
					$aFldXml=str_replace("[FIELD_VALUE]", htmlspecialchars($val), $aFldXml);
					$newFldXml.=$aFldXml;
				}
				
			}
			$rowXml=str_replace($fieldTemp, $newFldXml, $rowXml);
			
			$newXml.=$rowXml;		
		}
				
		$retXml=str_replace($regTemp, $newXml, $respXml);
		
		$this->SetStatusCode(PCODE_OK);
		return $retXml;
	}

}


?>