<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");

class VContent extends VObject 
{
	function VContent($id=0)
	{
		$this->VObject(TB_CONTENT);
		$this->SetRowId($id);
	}
	
	function GetXML($contentInfo, $contentUrl='')
	{
		$xmlFile=$contentUrl.$contentInfo['content_id'].".xml";
		$type=$contentInfo['type'];
		$xml="<xmlFile fileName=\"$xmlFile\">\n";
		if ($type!='PPT' ) {
			if ($type=='JPG') {
				$tagName='picture';
//				$fileName=$contentInfo['content_id'].".jpg";
			} elseif ($type=='SWF') {
				$tagName='picture';
//				$fileName=$contentInfo['content_id'].".swf";
			} elseif ($type=='MP3') {
				$tagName='media';
//				$fileName=$contentInfo['content_id'].".mp3";
			} elseif ($type=='FLV') {
				$tagName='media';
//				$fileName=$contentInfo['content_id'].".flv";
			}

			$xml.="<$tagName id=\"".$contentInfo['content_id']."\"";
			$xml.=" title=\"".htmlspecialchars($contentInfo['title'])."\"";
			$xml.=" fileName=\"".$contentUrl.$contentInfo['file_name']."\"";
			if ($contentInfo['thumb_file']!='')
				$xml.=" thumbnail=\"".$contentUrl.$contentInfo['thumb_file']."\"";
			$xml.=" type=\"".$type."\"";
			$xml.=" xmlFile=\"".$xmlFile."\"";
			$xml.=" dateTime=\"".$contentInfo['create_date']."\"";
			$xml.=" author=\"".htmlspecialchars($contentInfo['author_name'])."\"";
			$xml.=" copyright=\"".htmlspecialchars($contentInfo['copyright'])."\"";
			$xml.=" description=\"".htmlspecialchars($contentInfo['description'])."\"";		
			$xml.=" keywords=\"".htmlspecialchars($contentInfo['keywords'])."\"";		
			$xml.=" />\n";
			
		} else {

			$xml.="<slides id=\"".$contentInfo['content_id']."\"";
			$xml.=" title=\"".htmlspecialchars($contentInfo['title'])."\"";
			$xml.=" type=\"".$type."\"";
			$xml.=" xmlFile=\"".$xmlFile."\"";
			$xml.=" dateTime=\"".$contentInfo['create_date']."\"";
			$xml.=" author=\"".htmlspecialchars($contentInfo['author_name'])."\"";
			$xml.=" copyright=\"".htmlspecialchars($contentInfo['copyright'])."\"";
			$xml.=" description=\"".htmlspecialchars($contentInfo['description'])."\"";		
			$xml.=" keywords=\"".htmlspecialchars($contentInfo['keywords'])."\"";
			if ($contentInfo['width']!='0')
				$xml.=" width=\"".$contentInfo['width']."\"";		
			if ($contentInfo['height']!='0')
				$xml.=" height=\"".$contentInfo['height']."\"";		
			$xml.=" >\n";
			
			$slideFiles=explode("|", $contentInfo['slide_files']);
			$slideThumbs=explode("|", $contentInfo['slide_thumbs']);
			$slideTitles=explode("|", $contentInfo['slide_titles']);
			$slideCount=count($slideFiles);
			for ($i=0; $i<$slideCount; $i++) {
				$atitle=$afile=$athumb='';
				if (isset($slideTitles[$i]))
					$atitle=$slideTitles[$i];
				if (isset($slideFiles[$i]))
					$afile=$contentUrl.$slideFiles[$i];
				if (isset($slideThumbs[$i]) && $slideThumbs[$i]!='')
					$athumb=$contentUrl.$slideThumbs[$i];

				$xml.="	<slide title=\"".$atitle."\"";
				$xml.=" fileName=\"".$afile."\"";
				$xml.=" thumbnail=\"".$athumb."\"";
				$xml.=" />\n";
			}
			
			$xml.="</slides>\n";

		}
		$xml.="</xmlFile>\n";
		return $xml;
	}

}

?>