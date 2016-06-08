
function SetClassName(elem, newclass) { 
	elem.className = newclass;
}

function MyConfirm(msg) {

	var ok=confirm(msg);
	if (ok)
		return true;
	else
		return false;
}

function GetElemHtml(elemId)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		return elem.innerHTML;
	}
	return '';
}

function SetElemDisplay(elemId, display)
{
	var elem=document.getElementById(elemId);

	if (elem)
		elem.style.display = display;		
	return true;
}

function SetElemVisibility(elemId, val)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		elem.style.visibility=val;
	}
	return true;
}

function GoToUrl(url)
{
	if (parent)
		parent.location = url;
	else
		window.location = url;
	return true;
}

function FP_changeProp() {//v1.0
 var args=arguments,d=document,i,j,id=args[0],o=FP_getObjectByID(id),s,ao,v,x;
 d.$cpe=new Array(); if(o) for(i=2; i<args.length; i+=2) { v=args[i+1]; s="o"; 
 ao=args[i].split("."); for(j=0; j<ao.length; j++) { s+="."+ao[j]; if(null==eval(s)) { 
  s=null; break; } } x=new Object; x.o=o; x.n=new Array(); x.v=new Array();
 x.n[x.n.length]=s; eval("x.v[x.v.length]="+s); d.$cpe[d.$cpe.length]=x;
 if(s) eval(s+"=v"); }
}

function FP_getObjectByID(id,o) {//v1.0
 var c,el,els,f,m,n; if(!o)o=document; if(o.getElementById) el=o.getElementById(id);
 else if(o.layers) c=o.layers; else if(o.all) el=o.all[id]; if(el) return el;
 if(o.id==id || o.name==id) return o; if(o.childNodes) c=o.childNodes; if(c)
 for(n=0; n<c.length; n++) { el=FP_getObjectByID(id,c[n]); if(el) return el; }
 f=o.forms; if(f) for(n=0; n<f.length; n++) { els=f[n].elements;
 for(m=0; m<els.length; m++){ el=FP_getObjectByID(id,els[n]); if(el) return el; } }
 return null;
}

function FP_changePropRestore() {//v1.0
 var d=document,x; if(d.$cpe) { for(i=0; i<d.$cpe.length; i++) { x=d.$cpe[i];
 if(x.v=="") x.v=""; eval("x."+x.n+"=x.v"); } d.$cpe=null; }
}

function ChangeLocale(elemId, localeUrl)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		window.location.href=localeUrl+"&locale="+elem.options[elem.selectedIndex].value;
	}
	return true;
}

function ChangeTimeZone(elemId, url)
{
	var elem=document.getElementById(elemId);
	if (elem)
		window.location=url+"&time_zone="+elem.value;		

	return true;
}


var is_ie=false;
var is_ns=false;
if (navigator.appName && navigator.appName.indexOf("Microsoft") != -1 && parseInt(navigator.appVersion)>=4)
	is_ie=true;
else if (navigator.appName && navigator.appName.indexOf("Netscape")!=-1 && parseInt(navigator.appVersion)>=5)
	is_ns=true;

function SetOpacity(elem, value) {
    if (elem==null) return;
	if (is_ie)
		elem.style.filter = 'alpha(opacity=' + value*10 + ')';
	else if (is_ns)
		elem.style.opacity = value/10;
}

function ShowLoadBar()
{
    var elem;
    if (elem=document.getElementById('shade')) {
        elem.style.display='block';
    }
    
    if (elem=document.getElementById('load_bar')) {
        elem.style.display='block';
    }

    return true;
}
function HideLoadBar()
{
    var elem;
    if (elem=document.getElementById('load_bar'))
        elem.style.display='none';
        
    if (elem=document.getElementById('shade'))
        elem.style.display='none';
}


function FindParent(node, localName)
{
    while (node && (node.nodeType != 1 || node.localName.toLowerCase() != localName))
        node = node.parentNode;
    return node;
}

function OnLink(elem)
{
/*
    if (elem && elem.href) {
        if (elem.target && elem.target=='_blank')
            return true;
            
        ShowLoadBar();
        if (elem.target && elem.target=='_parent') {
            parent.location=elem.href;
        } else {
            window.location=elem.href;
        }
        return false;
    
    }
*/
    return true;
}

/*

function linkEvent(event) {
    var link = FindParent(event.target, "a");
    if (link)
    {
        if (link.href.indexOf("javascript")<0 && link.target!='_blank')
            ShowLoadBar();
    }
}

// for !IE
if (window.addEventListener) {
    window.addEventListener("click", linkEvent, false);
} else if (window.attachEvent) {
    // for IE; NOT WORKING!
    window.attachEvent("onclick", linkEvent);
}
*/

// use an hidden IFrame to load a page
// http://ajaxpatterns.org/IFrame_Call
/*
var loadIFrame=false;
function onIFrameLoad() {
    //var iframeBody = document.getElementById("load_iframe").contentWindow.document.body;
    HideLoadBar();
    if (loadIFrame) {
        var elem=document.getElementById("load_iframe");
        var body=elem.contentWindow.document.body;
        //alert(body.innerHTML);
        document.getElementById("main_page").innerHTML = body.innerHTML;
        loadIFrame=false;
    }
}

function ShowContent(url) {
	
	var elem=document.getElementById("load_iframe");
	if (!elem) return;
	
    ShowLoadBar();
	loadIFrame=true;
	elem.src=url."&content_only=1";
}
*/


/* This version uses XMLHttpRequest but it doesn't seem to work on IE
function ShowContent(url) {

	var elem=document.getElementById('main_page');
	if (!elem) return;
	
    var req = null;
    try { req = new ActiveXObject("Msxml2.XMLHTTP"); } catch (e) {
        try { req = new ActiveXObject("Microsoft.XMLHTTP"); } catch (e) {
            try { req = new XMLHttpRequest(); } catch(e) {}
        }
    }
    if (req == null) throw new Error('XMLHttpRequest not supported');

    ShowLoadBar();

    req.open("GET", url."&content_only=1", false);
    req.send(null);
    
    req.onerror = function()
    {
        HideLoadBar();
        elem.innerHTML = "Cannot load page";
    };
    
    req.onreadystatechange = function()
    {            
        if (req.readyState == 4)
        {
            HideLoadBar();
            elem.innerHTML = req.responseText;
        }
    };
}



addEventListener("click", function(event)
{
    var link = FindParent(event.target, "a");
    if (link)
    {        
        if (link.target == "_replace")
        {
//            alert(link.href);
            ShowContent(link.href);
        }
        else
            return;
        
        event.preventDefault();        
    }
}, true);
*/

