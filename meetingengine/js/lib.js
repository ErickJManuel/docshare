/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */

 
var ie=false;
var ns=false;
if (navigator.appName && navigator.appName.indexOf("Microsoft") != -1 && parseInt(navigator.appVersion)>=4)
	ie=true;
else if (navigator.appName && navigator.appName.indexOf("Netscape")!=-1 && parseInt(navigator.appVersion)>=5)
	ns=true;

function setOpacity(elem, value) {
    if (elem==null) return;
	if (ie)
		elem.style.filter = 'alpha(opacity=' + value*10 + ')';
	else if (ns)
		elem.style.opacity = value/10;
}

/* Used in lib_manager.php */

    function sendRequest(url,callback,postData) {
	    var req = createXMLHTTPObject();
	    if (!req) return;
	    var method = (postData) ? "POST" : "GET";
	    req.open(method,url,true);
	    req.setRequestHeader('User-Agent','XMLHTTP/1.0');
	    if (postData)
		    req.setRequestHeader('Content-type','application/x-www-form-urlencoded');
	    req.onreadystatechange = function () {
		    if (req.readyState != 4) return;
		    if (req.status != 200 && req.status != 304) {
    //			alert('HTTP error ' + req.status);
			    return;
		    }
		    callback(req);
	    }
	    if (req.readyState == 4) return;
	    req.send(postData);
    }
    
    function getRequest(url) {
	    var req = createXMLHTTPObject();
	    if (!req) return;
	    req.open("GET",url,false);
	    req.send(null);
        return req;
    }


    var XMLHttpFactories = [
	    function () {return new XMLHttpRequest()},
	    function () {return new ActiveXObject("Msxml2.XMLHTTP")},
	    function () {return new ActiveXObject("Msxml3.XMLHTTP")},
	    function () {return new ActiveXObject("Microsoft.XMLHTTP")}
    ];

    function createXMLHTTPObject() {
	    var xmlhttp = false;
	    for (var i=0;i<XMLHttpFactories.length;i++) {
		    try {
			    xmlhttp = XMLHttpFactories[i]();
		    }
		    catch (e) {
			    continue;
		    }
		    break;
	    }
	    return xmlhttp;
    }


	function showLib(index) {
		if (libIndex>=1 && libIndex!=index) {
			document.getElementById("tab"+libIndex).className='';
		}


		document.getElementById("tab"+index).className='lib_selected';

		libIndex=index;
		
		var url= libUrl;
		url+="&lib="+libIndex;
		url+="&rand="+Math.random();
		var elem=document.getElementById('lib-frame');
		if (!elem) return;
		
		if (ie) {
			setOpacity(elem, 5);
			elem.attachEvent("onload", 
				function()
				{
					setOpacity(elem, 10);
				}
			);
		} else if (ns) {
			setOpacity(elem, 5);
			elem.addEventListener("load", 
				function(event)
				{
					setOpacity(elem, 10);
				},
				false
			);
		}	

		elem.src= url;

	}

/* Used in libview.php */
	function hideAllContent() {
		setStyleByClass('tr', 'c_icon_pict', 'display', 'none');
		setStyleByClass('tr', 'c_icon_slide', 'display', 'none');
		setStyleByClass('tr', 'c_icon_video', 'display', 'none');
		setStyleByClass('tr', 'c_icon_audio', 'display', 'none');
//		setStyleByClass('tr', 'c_icon_add', 'display', 'none');
		var elem;
		if ((elem=document.getElementById("edit-page"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("media_selector"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("picture_uploader"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("audio_uploader"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("video_uploader"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("slides_uploader"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("media_editor"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("add_pres"))!=null) elem.style.display="none";
		if ((elem=document.getElementById("doc_converter"))!=null) elem.style.display="none";

	}
	function showAllContent() {
		setStyleByClass('tr', 'c_icon_pict', 'display', 'block');
		setStyleByClass('tr', 'c_icon_slide', 'display', 'block');
		setStyleByClass('tr', 'c_icon_video', 'display', 'block');
		setStyleByClass('tr', 'c_icon_audio', 'display', 'block');
//		setStyleByClass('tr', 'c_icon_add', 'display', 'none');
	}
	
	function showContent(index) {
		document.getElementById("edit-page").style.display="none";
		if (mediaIndex>=0 && mediaIndex!=index) {
			document.getElementById("media"+mediaIndex).className='';
		}

		document.getElementById("media"+index).className='media_selected';
		mediaIndex=index;
		
		if (index>0) {
			var className='c_';
			if (index==1)
				className+='icon_pict';
			else if (index==2)	
				className+='icon_slide';
			else if (index==3)	
				className+='icon_video';
			else if (index==4)	
				className+='icon_audio';
				
			hideAllContent();
			setStyleByClass('tr', className, 'display', 'block');
//			setStyleByClass('tr', 'c_icon_add', 'display', 'block');
		} else {
			showAllContent();
		}
	}
/*
	function stripslashes( str ) {
		// Un-quote string quoted with addslashes()
		return str.replace('/\0/g', '0').replace('/\(.)/g', '$1');
	}

	function editLibItem(name, id) {
		name=stripslashes(name);
		var msg="Enter a new name for this file:";
		var ans=prompt(msg, name);
		if (ans) {
			// replace any double quotes with a single quote
			ans=ans.replace('"', '\'');
			setOpacity(document.documentElement, 5);
			pageUrl+="&edit=1&title="+escape(ans)+"&content_id="+id+"&media="+mediaIndex;
			pageUrl+="&rand="+Math.random();

			window.location=pageUrl;
		}
		
	}
*/
	function editLibItem(name, id) {
		hideAllContent();

		document.getElementById("edit-page").style.display="block";
		var elem=document.getElementById("media_editor");
		if (elem) {
			elem.style.display="block";
			
			var form=document.media_editor_form;
			form.title.value=name;
			form.content_id.value=id;
			form.media.value=mediaIndex;
		}
	}
	
	function deleteLibItem(name, id) {
		var theUrl=libPageUrl;
		var msg="Do you want to delete '"+name+"'?";
		var ans=confirm(msg);
		if (ans) {
			setOpacity(document.documentElement, 5);
			theUrl+="&delete=1&name="+escape(name)+"&content_id="+id+"&media="+mediaIndex;
			theUrl+="&rand="+Math.random();
			window.location=theUrl;
		}	
	}
	
    function htmlspecialchars(string) {
        
        string = string.toString();
        
        // Always encode
        string = string.replace(/&/g, '&amp;');
        string = string.replace(/</g, '&lt;');
        string = string.replace(/>/g, '&gt;');
        string = string.replace(/"/g, '&quot;');
        string = string.replace(/'/g, '&#039;');
        
        return string;
    }
    

    
    function shareLibCallback(req) {
        
        var xmldata=req.responseXML; //retrieve result as an XML object
        var resp=xmldata.getElementsByTagName("error");
        if (resp && resp.length>0) {
           var code=xmldata.getElementsByTagName("code")[0].childNodes[0].nodeValue;
           var message=xmldata.getElementsByTagName("message")[0].childNodes[0].nodeValue;
           alert("An error is encountered: "+code+" "+message);
        } else {
            // close the parent window; doesn't work on IE
            if (window.parent)
               window.parent.closePage();
            
        }
    }
	
	function shareLibItem(name, id, type, url) {
		args=new Array();
		args.push("brand="+brand);
		args.push("meeting_id="+meeting_id);
		args.push("token="+token);
		args.push("sender_id="+sender_id);
//		args.push("sender_name="+sender_name);


	    if (type=='PPT') {	    
	        // the url is a toc xml file
	        // we need to get the first slide's url from the xml
	        
	        // get the lib path url without the xml file name
            libUrl=url.replace(/\\/g,'/').replace(/\/[^\/]*\/?$/, '');
	     
	        // get the toc xml
	        var resp=getRequest(url);		        
	        
	        if (resp) {
	            // parse the xml to get the first slide url
                var xmldata=resp.responseXML;
                var x=xmldata.getElementsByTagName("slide")[0].attributes;
                slideUrl=libUrl+"/"+x.getNamedItem("fileName").nodeValue;
            } else {
                alert("There is a problem getting the library content.");
                return;
            }
		    args.push('event_type=SendSlide');
		    var eventData="<slide toc='"+url+"' slideurl='"+slideUrl+"' presentationid='"+id+"' title=\""+htmlspecialchars(name)+"\"/>";
		    args.push('event_data='+eventData);        
	    } else if (type=='JPG' || type=='SLIDE') {		
		    args.push('event_type=SendSlide');
		    var eventData="<slide slideurl='"+url+"' presentationid='"+id+"' title=\""+htmlspecialchars(name)+"\"/>";
		    args.push('event_data='+eventData);
		} else if (type=='MP3' || type=='FLV') {
		    args.push('event_type=StartMedia');
		    var eventData="<media url='"+url+"' title=\""+htmlspecialchars(name)+"\"/>";
		    args.push('event_data='+eventData);
		}
		
		var post_url=api_url+"live_event/";
		
		sendRequest(post_url, shareLibCallback, args.join("&"));

	}
	
	function addLibItem(index) {
		hideAllContent();
        var elem;
		if (elem=document.getElementById("edit-page")) elem.style.display="block";
		
		if (index==0) {
			if (elem=document.getElementById("media_selector")) elem.style.display="block";
		} else if (index==1) {
			if (elem=document.getElementById("picture_uploader")) elem.style.display="block";
		} else if (index==2) {
//			document.getElementById("slides_uploader").style.display="block";
			if (elem=document.getElementById("add_pres")) elem.style.display="block";
		} else if (index==3) {
			if (elem=document.getElementById("video_uploader")) elem.style.display="block";
		} else if (index==4) {
			if (elem=document.getElementById("audio_uploader")) elem.style.display="block";
		}
	
	}
	
	function showSlidesUploder() {
		hideAllContent();
		document.getElementById("edit-page").style.display="block";
		document.getElementById("slides_uploader").style.display="block";		
	}
	function showConverter() {
		hideAllContent();
		document.getElementById("edit-page").style.display="block";
		document.getElementById("doc_converter").style.display="block";		
	}
	
	function refreshLibPage() {
		var theUrl=libPageUrl;
		theUrl+="&media="+mediaIndex;
		theUrl+="&rand="+Math.random();
		window.location=theUrl;
	}
	
	// called by the Flash mediauploader to close it
	function mediauploader_Close() {
		setTimeout(refreshLibPage, 500);
	}
	
	function checkEdit(form) {
		if (form.title.value=='') {
			alert("You must enter a value for the file name.");
			return false;
		}
		return true;
	}
	
	function launchVPresent(url) {
	    // open a new window to launch the url when using https to avoid security warnings on IE
	    if (ie && window.location.protocol=='https:')
	        window.open(url, "VPresent", "location=no, toolbar=no, menubar=no, directories=no, copyhistory=no, width=300, height=200");
		else
		    window.location=vPresentUrl;		
	}
	
	
	/* used in mediaview.php */

	function showSlide(index) {
		if (index<0 || index>=slides.length)
			return;
			
		var w, h;
		if (!showFullSize) {
			w="480px";
			h="360px";
		} else {
			w="100%";
			h="360px";
		}

		var elem=document.getElementById("slide");
		var file=slides[index];
		var swf=false;
		if (file.toLowerCase().indexOf(".swf")>0) {
			if (!showFullSize) {
				w="480px";
				h="360px";
			} else {
				w="100%";
				h="480px";
			}
			var text="<p>Flash Player is required to display this file type.<p>You need to install the Flash Player.";
			elem.innerHTML=
"<object width='"+w+"' height='"+h+"' class='slide_obj'><param name='movie' value='"+file+"' name='wmode' value='opaque'/> <object type='application/x-shockwave-flash' width='"+w+"' height='"+h+"' data='"+file+"' wmode='opaque'> "+text+"  </object>  </object>";
		} else {
			if (!showFullSize) {
				elem.innerHTML="<img class='zoom_img' src='"+file+"'>";
			} else {
				elem.innerHTML="<img src='"+file+"'>";
			}
		}
		slideIndex=index;
		document.getElementById("slide_index").innerHTML=(index+1)+" / "+slides.length;
	}
	
	
	function toggleSlide()
	{
		showFullSize=!showFullSize;
		showSlide(slideIndex);
		if (showFullSize)
			document.getElementById("zoom_slide_img").src='themes/icon_zoom_out.png';
		else
			document.getElementById("zoom_slide_img").src='themes/icon_zoom_in.png';

	}
	function togglePict()
	{
		showFullSize=!showFullSize;
		var elem=document.getElementById("pict_img");

		if (showFullSize) {
			document.getElementById("zoom_slide_img").src='themes/icon_zoom_out.png';
			elem.className='';
		} else {
			document.getElementById("zoom_slide_img").src='themes/icon_zoom_in.png';
			elem.className='zoom_img';
		}
	}