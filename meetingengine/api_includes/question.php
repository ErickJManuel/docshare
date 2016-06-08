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


require_once("dbobjects/vquestion.php");

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');
$memberAccId=GetSessionValue('member_access_id');

if ($memberId=='')
	return API_EXIT(API_ERR, "You are not signed in as a member of the site.");

if ($cmd=='ADD_QUESTION' || $cmd=='SET_QUESTION') {
	
	$qInfo=array();
	
	if (GetArg('question', $arg))
		$qInfo['question']=$arg;
	
	if (GetArg('type', $arg)) {
		if ($arg!='S' && $arg!='T')
			API_EXIT(API_ERR, "Invalid value for 'type'");	
		
		$qInfo['type']=$arg;
	}

	if (GetArg('correct', $arg))
		$qInfo['correct']=$arg;
/*
	if (GetArg('show_correct', $arg))
		$qInfo['show_correct']=$arg;
		
	if (GetArg('check_correct', $arg)) {
		if (!isset($qInfo['show_correct']))
			$qInfo['show_correct']='N';		
	}
	
	if (GetArg('timer', $arg))
		$qInfo['timer']=$arg;
*/	
	for ($i=1; $i<=5; $i++) {
		$key="choice_".$i;
		if (GetArg($key, $arg)) {
			$qInfo[$key]=$arg;
		}
	}

	if ($cmd=='ADD_QUESTION') {
		if (!isset($qInfo['question']) || $qInfo['question']=='')
			API_EXIT(API_ERR, "question parameter is not provided");	
		
		$qInfo['author_id']=$memberId;
		$qInfo['order']=time();
		
		$question=new VQuestion();
		if ($question->Insert($qInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $question->GetErrorMsg());	
	} else {
		GetArg("id", $arg);
		if ($arg=='')
			API_EXIT(API_ERR, "id parameter is not provided");	
		
		$question=new VQuestion($arg);
		if ($question->Get($oldInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $question->GetErrorMsg());
			
		if (!isset($oldInfo['id']))	
			API_EXIT(API_ERR, "No record matching the id is found");
			
		if ($oldInfo['author_id']!=$memberId)
			API_EXIT(API_ERR, "Not authorized to set the record");
		
		if ($question->Update($qInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $question->GetErrorMsg());	
	}
	
	
} else if ($cmd=='DELETE_QUESTION') {
	
	GetArg("id", $arg);
	if ($arg=='')
		API_EXIT(API_ERR, "id parameter is not provided");	
	
	$question=new VQuestion($arg);
	if ($question->Get($oldInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $question->GetErrorMsg());
	
	if (!isset($oldInfo['id']))	
		API_EXIT(API_ERR, "No record matching the id is found");
	
	if ($oldInfo['author_id']!=$memberId)
		API_EXIT(API_ERR, "Not authorized to delete the record");
	
	if ($question->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $question->GetErrorMsg());
			
} else if ($cmd=='MOVE_QUESTION') {
	
	GetArg("index", $index);
	if ($index=='' || $index<=0)
		API_EXIT(API_ERR, "index parameter is not provided or invalid");	
	
	GetArg("direction", $direction);
	if ($direction=='')
		API_EXIT(API_ERR, "direction parameter is not provided");	

	$query="author_id='".$memberId."'";
	
	// move up
	if ($direction=='-1') {
		// already on top; ignore
		if ($index==1)
			return;
		
		$offset=$index-2;
	} else if ($direction=='+1') {
		$offset=$index-1;
	}

	$errMsg=VObject::SelectAll(TB_QUESTION, $query, $result, $offset, 2, "*", "order");
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
	
	$infoList=array();
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$infoList[]=$row;
	}
	
	if (count($infoList)<=1) {
		// only one item; ignore
		return;
	}
		
	if ($direction=='-1') {
		$fromInfo=&$infoList[1];
		$toInfo=&$infoList[0];
		
	} else if ($direction=='+1') {
		$fromInfo=&$infoList[0];
		$toInfo=&$infoList[1];
	}
	
	// swap the order
	$fromQ=new VQuestion($fromInfo['id']);
	$fromUpdate=array();
	$fromUpdate['order']=$toInfo['order'];
	$fromQ->Update($fromUpdate);

	if ($fromQ->Update($fromUpdate)!=ERR_NONE)
		API_EXIT(API_ERR, $fromQ->GetErrorMsg());
	
	$toQ=new VQuestion($toInfo['id']);
	$toUpdate=array();
	$toUpdate['order']=$fromInfo['order'];
	if ($toQ->Update($toUpdate)!=ERR_NONE)
		API_EXIT(API_ERR, $toQ->GetErrorMsg());
	
} else if ($cmd=='GET_QUESTION') {
/*
	GetArg("user", $userId);
	if ($userId=='')
		API_EXIT(API_ERR, "user parameter is not provided or invalid");	

	if ($userId!=$memberAccId)
		API_EXIT(API_ERR, "You are not signed in");	
*/
	
	$query="author_id='".$memberId."'";
	$errMsg=VObject::SelectAll(TB_QUESTION, $query, $result, 0, 0, "*", "order");
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
		
	$xml=XML_HEADER."\n";
	
	$xml.="<polls>\n";
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$xml.=VQuestion::ToXML("poll", $row);
		$xml.="\n";
	}
	$xml.="</polls>\n";
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	
	header("Content-Type: text/xml");
	
	echo $xml;
	API_EXIT(API_NOMSG);
	
}

?>