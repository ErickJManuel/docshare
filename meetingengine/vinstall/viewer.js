/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
 var baseDir='';
 var isInternetExplorer = navigator.appName.indexOf("Microsoft") > -1;
 var isChrome= isInternetExplorer?false: navigator.userAgent.toLowerCase().indexOf('chrome') > -1;

// Handle all the FSCommand messages in a Flash movie.
function viewer_DoFSCommand(command, args) {
	if (command=='download') {
		var items=args.split(" ");
		var docUrl=items[0];
		var docName=items[1];
		var viewUrl=docUrl;
		var downloadUrl="vscript.php?s=vgetfile&file="+docUrl+"&download_name="+docName;
//		var downloadUrl="vscript.php?s=vdownload&download=1&file="+docUrl+"&src="+docName;
		var message=docName+"<br><br>";
//		message+=" <button onClick=\"window.open('"+viewUrl+"')\">Open</button>";
//		message+=" <button onClick=\"window.location='"+downloadUrl+"'\">Download</button>";
		message+=" <a target='_blank' href='"+viewUrl+"'>Open</a>";
		message+=" &nbsp;&nbsp;<a target='_blank' href='"+downloadUrl+"'>Download</a>";
		ShowMessage(true, message);
	} else if (command=='open') {
		var elem=document.getElementById('page_content');
		elem.src=args;
		elem.style.display='inline';
		document.getElementById('page_box').style.display='';
	} else if (command=='redirect') {

        try //Firefox, Mozilla, Opera, etc.
        {
            window.location.href=args;
            // Does not work on Chorme or IE.
            return "OK";
        }
        catch(e)
        {
            return "ERROR "+e.message;
        }
/*
	} else if (command=='shareScreen') {
	    window.location.href=baseDir+"vpresent_java/?"+args;

		var elem=document.getElementById('sharing_content');
		elem.src= baseDir+"vpresent_java/?"+args;
		elem.style.display='inline';
		document.getElementById('sharing_box').style.visibility='visible';
*/
	}

}
// Hook for Internet Explorer.
//if (navigator.appName && navigator.appName.indexOf("Microsoft") != -1 && navigator.userAgent.indexOf("Windows") != -1 && navigator.userAgent.indexOf("Windows 3.1") == -1) {
if (isInternetExplorer) {
	document.write("<script language=\"VBScript\"\>\n");
	document.write("On Error Resume Next\n");
	document.write("Sub viewer_FSCommand(ByVal command, ByVal args)\n");
	document.write("	Call viewer_DoFSCommand(command, args)\n");
	document.write("End Sub\n");
	document.write("</script\>\n");
}

function ShowMessage(showIt, message) {
	var elem=document.getElementById('message_box');
	if (!elem)
		return false;
	if (showIt) {
		document.getElementById('message_text').innerHTML=message;
		elem.style.display='none';
	} else {
		elem.style.display='';
	}
	return false;
}

function ShowPageBox(showIt)
{
	var elem=document.getElementById('page_box');
	var elem1=document.getElementById('page_content');
	if (!elem || !elem1)
		return false;
	if (showIt) {s
		elem.style.display='';
		elem1.style.display='inline';
	} else {
	    // see if we are closing the library page
	    var refresh=elem1.src.indexOf("LIBRARY")>0;
	    
		elem.style.display='none';
		elem1.src=baseDir+'blank.php';
		elem1.style.display='none';
		
		// call the Flash viewer to refresh its menu to reflect any library changes
		if (refresh) {
			flash_refreshMenu();
		}


	}
	return false;
}

function ShowSharingBox(showIt)
{
	var elem=document.getElementById('sharing_box');
	var elem1=document.getElementById('sharing_content');
	if (!elem || !elem1)
		return false;
	if (showIt) {
		elem.style.display='';
		elem1.style.display='inline';
	} else {
		elem.style.display='none';
		elem1.src=baseDir+'blank.php';
		elem1.style.display='none';
	}
	return false;
}

function flash_refreshMenu() {
    var elem=thisMovie("viewer");
    if (elem) {
        elem.refreshMenu();
    }
}

function thisMovie(movieName) {
	return swfobject.getObjectById(movieName);
/*
    if (navigator.appName.indexOf("Microsoft") != -1) {
        return window[movieName];   // for IE
    } else if (document[movieName]) {
        return document[movieName]; // for FireFox, Safari
    } else {
        return document.getElementById(movieName);  // for Chrome
    }
*/
}
