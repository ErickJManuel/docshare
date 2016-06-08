<?php
/**
 * Persony Web Conferencing 2.0
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * 
 */
/**
 * required base PEAR package
 */


/*
if (isset($gScriptDir)) {
require_once $gScriptDir."PEAR.php";
require_once $gScriptDir."iswfcommon.php";
require_once $gScriptDir."File.php";

} else {
require_once "PEAR.php";
require_once "iswfcommon.php";
require_once "File.php";
}
*/

/**
 * 
 * @author luzw
 *
 */
class SwfDataExtractor
{
	/**
	 * is a valid swf
	 * @var boolean
	 * @access private
	 */
	var $isValid = 0;
	/**
	 * stirng file name to parse
	 * @var string
	 */
	var $file = "";

	var $head = "";

	var $point = 0;
	
//	var $hadJpg = false;
	
	var $xml;

//	var $out_path = "";
	
	var $isKey = 0;
	
	var $swfNumber = 0;
	
	//current frame - xml child object.
	var $currFrame;
	
	// current object
	var $currW = 0;
	var $currH = 0; 
	var $currX = 0;
	var $currY = 0;
	var $currId='';
	var $format='';
	var $type='';
	var $id='';
	var $bitmapPos=0;
	var $bitmapSize=0;
	var $fillColor=null;
	

	/**
	 * Decodes swf to xml. 
	 * @param $file - swf file.
	 * @param $iskey - is this key frame ? 
	 * @return unknown_type
	 */
	function SwfDataExtractor($file="", $iskey = 0, $swfNumber) {	
		$this->file = $file;
		$this->head = File::read($this->file, 3);
//		$this->out_path = $out_path;
		$this->isKey = $iskey;
		$this->swfNumber = $swfNumber;
		if(PEAR::isError($this->head)){
			return $this->head;
		}
		File::rewind($this->file, "rb");
		if($this->head == "CWS"){
			$data = File::read($this->file, 8);
			$_data = gzuncompress(File::read($this->file, filesize($this->file)));
			$data = $data . $_data;
			$this->data = $data;
			$this->compression = 1;
			$this->isValid = 1;
		} else if ($this->head == "FWS"){
			$this->data = File::read($this->file, filesize($this->file));
			$this->isValid = 1;
		} else {
			/**
			 * invalid SWF file, or invalid head tag found
			 */
			$this->isValid = 0;
		}
		File::close($this->file, "rb");		
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function returnSwfXml() {
		return $this->printSwfInfo($this->isKey);
	}
	
	/**
	 * 
	 * @param $isKey
	 * @return unknown_type
	 */
	function printSwfInfo($isKey) {		

		$ver = $this->getVersion();		
		$length = $this->getLength();		
		$rect = $this->getMovieRect();		
		$fps = $this->getFrameRate();		
		$frames = $this->getFrameCount();	

		// determine the header size--either 21 or 22, depending on the Frame rect n-bits size
		// the Windows Client generates a 22 byte header to accommodate larger screen rect (N-bits=17)
		// the Java Client generates a 21 byte header (N-bits=17)		
		if ($this->data[8]=="\x88") // n-bits=17 (0x88)
			$headerSize=22;
		else	// n-bits=15 (0x78)
			$headerSize=21;

		$this->_seek($headerSize);
			
		//reading tags
/*
		$this->_seek(21);
		$unp = unpack('C',$this->_read(1));		
		if($unp[1] == 63){			
			$this->_seek(21);
		} else {
			$this->_seek(22);
		}
*/
		$this->xml = simplexml_load_string("<frames></frames>");
		$this->xml->addAttribute("id", $this->swfNumber);
		//$this->xml = "<frames><key>".$isKey."</key>";
		$len = strlen($this->data);
		while($this->point < $len)
		{
			$tag = $this->_readTag();						
			$this->_tagEval($tag);
		}

		return $this->xml;
	}	
	
	/**
	 * 
	 * @param $tag
	 * @return unknown_type
	 */
	function _tagEval($tag)
	{		
		if($tag[0] == 36) {
			$pos = $this->point + $tag[1];
			$this->_seek($pos);
		} elseif ($tag[0] == 20) {	// DefineBitsLossless2
			$this->type=$tag[0];
			$this->_extractZlib($tag);			 
		} elseif ($tag[0] == 21) {	// DefineBitsJPEG2
		
//			$this->hadJpg = true;
			$this->type=$tag[0];
			$this->_extractJpg($tag);			 
		} elseif ($tag[0] == 26) {	// PlaceObject2
//			if($this->hadJpg) {
			if($this->type=='20' || $this->type=='21' || isset($this->fillColor)) {
				$this->_extractPlaceObject($tag);
				$this->_addObject();
			} else {
				$this->_skipTag($tag);
			}
//			$this->hadJpg = false;
			$this->_resetObject();				
				
		} elseif ($tag[0] == 2 || $tag[0]==22 || $tag[0]==32) {	// DefineShape, 2, 3

			$this->_extractShape($tag);
				
		} else {
			$this->_skipTag($tag);
		}
	}
	
	private function _addObject() {
		//attach results to current xml, generating new frame.
		if ($this->bitmapSize>0) {
			$oldPos=$this->point;
			if ($this->type=='21') {	// DefineBitsJPEG2
				$this->_seek($this->bitmapPos+4);	// JPEG data starts on the 5th byte
				$dataSize=$this->bitmapSize-4;
				$data=$this->_read($dataSize);			
				$this->currFrame = $this->xml->addChild("frame", base64_encode($data));
			} else if ($this->type=='20') {	// DefineBitsLossless
				$this->_seek($this->bitmapPos);	
				$dataSize=$this->bitmapSize;
				$data=$this->_read($dataSize);			
				$this->currFrame = $this->xml->addChild("frame", base64_encode($data));
			}
			$this->point=$oldPos;
/*			
$file=$this->id;
if ($this->type=='21')
	$file.=".jpg";
else
	$file.=".dbl";
$ffp=fopen($file, "wb");
fwrite($ffp, $data, $dataSize);
fclose($ffp);
*/
		} else {
			$this->currFrame = $this->xml->addChild("frame");
		}
		//set id attr. 
		$this->currFrame->addAttribute("type", $this->type);
		$this->currFrame->addAttribute("id", $this->id);
	
		if ($this->format!='')
			$this->currFrame->addAttribute("format", $this->format);
			
		if (isset($this->fillColor) && $this->bitmapSize==0) {
			$this->currFrame->addAttribute("color", implode(",", $this->fillColor));
		}
					
		$this->currFrame->addAttribute("x", $this->currX);
		$this->currFrame->addAttribute("y", $this->currY);			
		$this->currFrame->addAttribute("width", $this->currW);
		$this->currFrame->addAttribute("height", $this->currH);

		if($this->isKey == 1) {
			$this->currFrame->addAttribute("keyframe", "true");
			$this->isKey = 0;
		} else {
			$this->currFrame->addAttribute("keyframe", "false");
		}

	}
	
	private function _resetObject() {
		$this->type = '';
		$this->id='';
		$this->format='';
		$this->currFrame = null;
		$this->currH = 0;
		$this->currW = 0;
		$this->currX = 0;
		$this->currY = 0;
		$this->bitmapPos=0;
		$this->bitmapSize=0;
		if (isset($this->fillColor))
			unset($this->fillColor);
	}
	
	/**
	 * 
	 * @param $tag
	 * @return unknown_type
	 */
	private function _skipTag($tag) {
		$pos = $this->point + $tag[1];
		$this->_seek($pos);
	}

	/**
	 * 
	 * @param $tag
	 * @return unknown_type
	 */
	private function _extractJpg($tag) {
		//move to proper position		
		$pos = $this->point + $tag[1];
		//read jpg name.
		$this->id = $this->_readshort();
		//read base64encoded jpg content
//		$content = $this->getEncodedContent($tag[1] - 2);
		$this->bitmapPos = $this->point;
		$this->bitmapSize = $tag[1] - 2;

		//seek to end position.
		$this->_seek($pos);
/*
		//attach results to current xml, generating new frame.
		$this->currFrame = $this->xml->addChild("frame", $content);
		//set id attr. 
		$this->currFrame->addAttribute("type", $this->type);
		$this->currFrame->addAttribute("id", $name);
		//width, height, keyframe		
//		$this->currFrame->addAttribute("width", $this->currW);
//		$this->currFrame->addAttribute("height", $this->currH);
		if($this->isKey == 1) {
			$this->currFrame->addAttribute("keyframe", "true");
			$this->isKey = 0;			
		} else {
			$this->currFrame->addAttribute("keyframe", "false");
		}
*/		
	}
	
	/**
	 * 
	 * @param $tag
	 * @return unknown_type
	 */
	private function _extractZlib($tag) {
		// find the end position; $tag[1] is the length of the tag		
		$pos = $this->point + $tag[1];
		//read name.
		$this->id = $this->_readshort();
		$this->format= $this->_readByte();
		$this->currW= $this->_readshort();
		$this->currH= $this->_readshort();
		//read base64encoded jpg content
//		$content = $this->getEncodedContent($tag[1] - 2);
		$this->bitmapPos = $this->point;
		$this->bitmapSize = $tag[1] - 7;

		//seek to end position.
		$this->_seek($pos);
/*
		//attach results to current xml, generating new frame.
		$this->currFrame = $this->xml->addChild("frame", $content);
		//set id attr. 
		$this->currFrame->addAttribute("type", $this->type);
		$this->currFrame->addAttribute("id", $name);
		$this->currFrame->addAttribute("format", $format);
		if($this->isKey == 1) {
			$this->currFrame->addAttribute("keyframe", "true");
			$this->isKey = 0;			
		} else {
			$this->currFrame->addAttribute("keyframe", "false");
		}		
*/

	}
	
	/**
	 * 
	 * @param $tag
	 * @return unknown_type
	 */
	private function _extractShape($tag) {
		$pos = $this->point + $tag[1];
		$name = $this->_readshort();
		$rect = $this->_readRect();	
		
		// Windows Client stores x, y, w, h in DefineShape
		// Java Client stores only w, h in DefineShape3 (x, y are defined in PlaceObject2)
		// Get the coordinates for the bitmap rect
		$this->currX=$rect['xmin'];
		$this->currY=$rect['ymin'];
		$this->currW=$rect['width'];
		$this->currH=$rect['height'];		
		
		$discard=$this->_readByte();
		$fillType=$this->_readByte();
		
		if ($this->bitmapSize==0 && $fillType=='0') {
			
			$this->id=$name;
			$this->type=$tag[0];
			// get the fill color
			$rr=$this->_readByte();
			$gg=$this->_readByte();
			$bb=$this->_readByte();
			$this->fillColor=array("red"=>$rr, "green"=>$gg, "blue"=>$bb);			
		}

		//seek to end position.
		$this->_seek($pos);

	}

	/**
	 * 
	 * @param $tag
	 * @return unknown_type
	 */
	private function _extractPlaceObject($tag) {
		$before = $this->point;
		$pos = $this->point + 5;
		
		// read flags
		$this->_begin();
		$discard=$this->_readbits(5);
		$hasMatrix=$this->_readbits(1);
		
		// Java Client stores x, y in the Matrix record
		// Windows Client does not have Matrix
		if ($hasMatrix==1) {
			// jump to the Matrix
			$this->_seek($pos);
			//first 2 bytes - rotation and scale.
			$this->_begin();
			$l = $this->_readbits(2);
			$l = $this->_readbits(5);		
			$this->currX = $this->_readbits($l)/20;
			$this->currY = $this->_readbits($l)/20;		
	
		}
				
		// jump to the end of the tag		
		$pos = $before + $tag[1];
		$this->_seek($pos);
		
	}

	/**
	 * Save current swf as a new file
	 * @param string $filename filename
	 * @param boolean $overwrite overwrite existing file
	 * @return boolean true if saved succesfully
	 * @access public
	 */
	function getEncodedContent($length)
	{
		$newdata = str_replace("\xff\xd8\xff\xd9", "",$this->_read($length));	
/*	
		$tmpfname = tempnam(sys_get_temp_dir(), "swf");
		$temp = fopen($tmpfname, "w");
		fwrite($temp, $newdata, strlen($newdata));
		list($w, $h) = getimagesize($tmpfname);
		$this->currW = $w;
		$this->currH = $h;
		fclose($temp);
*/
		return base64_encode($newdata);		
	}

	/**
	 * read tag type, tag length
	 * @return array
	 * @access private
	 */
	function _readTag()
	{
		$n = $this->_readshort();
		if($n == 0)
		{
			return false;
		}
		$tagn = $n>>6;
		$length = $n&0x3F;
		if($length == 0x3F)
		{
			$length = $this->_readlong();
		}
		return array($tagn,$length);
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function getLength() {
		$this->_seek(4);
		$test1 = $this->_read(4);
		$real_size = unpack( "Vnum", $test1 );
		return $real_size['num'];
	}

	/**
	 * Return the current number of frames
	 * @return mixed interger or error if invalid file format
	 * @access public
	 */
	function getFrameCount()
	{
		$this->_seek(8);
		if($this->_readRectBits() == 15){
			$this->_seek(18);
		} else {
			$this->_seek(19);
		}
		return $this->_readshort();
	}

	/**
	 * read long
	 * @access private
	 */
	function _readlong(){
		$val = $this->_read(4);
		$ret = unpack("L", $val);
		return $ret[1];
	}

	/**
	 * Return the current SWF frame rate
	 * @return mixed interger frame rate in fps or Error if invalid file
	 * @access public
	 */
	function getFrameRate()
	{
		$this->_seek(8);
		if($this->_readRectBits() == 15){
			$this->_seek(16);
		} else {
			$this->_seek(17);
		}
		$fps = unpack('vrate',$this->_read(2));

		return $fps['rate']/256;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function _readshort(){
		$pack = unpack('vshort',$this->_read(2));
		return $pack['short'];
	}

	/**
	 * Return the current version of player used
	 * @return mixed interger or error if invalid file format
	 * @access public
	 */
	function getVersion()
	{
		$this->_seek(3);
		return $this->_readbyte();
	}
	
	/**
	 * Return the current frame rect of the movie
	 * @return mixed interger or error if invalid file format
	 * @access public
	 */
	function getMovieRect()
	{
		$this->_seek(8);
		return $this->_readRect();
	}

	/**
	 * move the internal pointer
	 * @param integer $num
	 * @access private
	 */
	function _seek($num){
		if($num < 0){
			$num = 0;
		} else if($num > strlen($this->data)){
			$num = strlen($this->data);
		}
		$this->point = $num;
	}

	/**
	 * read single byte
	 * @return string
	 * @access private
	 */
	function _readByte(){
		$ret = unpack("Cbyte",$this->_read(1));
		return $ret['byte'];
	}

	/**
	 * read internal data file
	 * @param integer $n number of byte to read
	 * @return array
	 * @access private
	 */
	function _read($n)
	{
		$ret = substr($this->data, $this->point, $n);
		$this->point += $n;
		return $ret;
	}

	/**
	 * read a rect type
	 * @return rect
	 * @access private
	 */
	function _readRect(){
		$l = $this->_readRectBits();
		$xmin = $this->_readbits($l)/20;
		$xmax = $this->_readbits($l)/20;
		$ymin = $this->_readbits($l)/20;
		$ymax = $this->_readbits($l)/20;
		$rect = array(
			"xmin" => $xmin,
			"xmax" => $xmax,
			"ymin" => $ymin,
			"ymax" => $ymax,			
            "width" => $xmax-$xmin,
            "height" => $ymax-$ymin,
		);
/*
		$rect = array(
		$xmax,
		$ymax,
            "width" => $xmax,
            "height" => $ymax
		);
*/
		return $rect;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	function _readRectBits()
	{
//		$this->_seek(8);
		$this->_begin();
		$l = $this->_readbits(5);
		return $l;
	}
	/**
	 * begin reading of rect object
	 * @access private
	 */
	function _begin(){
		$this->current = $this->_readbyte();
		$this->position = 1;
	}
	/**
	 * read bites
	 * @param integer $nbits number of bits to read
	 * @return string
	 * @access private
	 */
	function _readbits($nbits){
		$n = 0;
		$r = 0;
		while($n < $nbits){
			$r = ($r<<1) + $this->_getbits($this->position);
			$this->_incpos();
			$n += 1;
		}
		return $r;
	}

	/**
	 * getbits
	 * @param integer $n
	 * @return long
	 * @access private
	 */
	function _getbits($n){
		return ($this->current>>(8-$n))&1;
	}

	/**
	 * read position internal to rect
	 * @access private
	 */
	function _incpos(){
		$this->position += 1;
		if($this->position>8){
			$this->position = 1;
			$this->current = $this->_readbyte();
		}
	}
}
?>