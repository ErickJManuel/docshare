<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
//require_once("vsite.php");
require_once("vwebserver.php");
/**
 * @package     VShow
 * @access      public
 */
class VBackground extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VBackground($id=0)
	{
		$this->VObject(TB_BACKGROUND);
		$this->SetRowId($id);
	}
	/**
	 * Retrun xml string from info
	 * @static
	 * @return bool true if the value already exists
	 */	
	static function GetXML($info, $siteUrl='')
	{
		if ($siteUrl=='')
			$siteUrl=SITE_URL;

		$xml="<background name=\"".$info['name']."\" ";
		if ($info['onpict_id']>0) {
			$onpict=new VImage($info['onpict_id']);
			$onpictInfo=array();
			$onpict->Get($onpictInfo);
			$onpictFile=$onpictInfo['file_name'];
			//$xml.="startFile=\"$onpictFile\" ";
			$onpict->GetUrl($siteUrl, $imageUrl);
			$xml.="startFile=\"$imageUrl\" ";
		}
		if ($info['offpict_id']>0) {
			$offpict=new VImage($info['offpict_id']);
			$offpictInfo=array();
			$offpict->Get($offpictInfo);
			$offpictFile=$offpictInfo['file_name'];
			//$xml.="waitFile=\"$offpictFile\" ";
			$offpict->GetUrl($siteUrl, $imageUrl);
			$xml.="waitFile=\"$imageUrl\" ";
		}
		$xml.=">\n";
		
		$x=$info['wb_x'];
		$y=$info['wb_y'];
		$s=$info['wb_s'];
		$steps=10;
		if ($s==0)
			$steps=1;
		$xml.="<zoom name=\"whiteboard\" x=\"$x\" y=\"$y\" scale=\"$s\" steps=\"$steps\" />\n";
		$x=$info['screen_x'];
		$y=$info['screen_y'];
		$s=$info['screen_s'];
		$steps=10;
		if ($s==0)
			$steps=1;
		$xml.="<zoom name=\"screen\" x=\"$x\" y=\"$y\" scale=\"$s\" steps=\"$steps\" />\n";
		$x=$info['slide_x'];
		$y=$info['slide_y'];
		$s=$info['slide_s'];
		$steps=10;
		if ($s==0)
			$steps=1;
		$xml.="<zoom name=\"slide\" x=\"$x\" y=\"$y\" scale=\"$s\" steps=\"$steps\" />\n";
		
		$xml.="</background>\n";
		
		return $xml;
	}
}


?>