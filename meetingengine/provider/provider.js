
function encodeForm(form)
{
    var args = [];

    for (var i = 0; i < form.elements.length; i++)
    {
        if (form.elements[i].type=="text" || form.elements[i].type=="hidden")
            args.push(form.elements[i].name + "=" + escape(form.elements[i].value));
    }

    return args;    
}

function launchInstaller(form) {

	if (form.site_url.value=='' || form.site_url.value=='http://')
	{
		alert("Please enter a value for the \"URL\" field.");
		form.site_url.focus();
		return (false);
	}
/*
	if (form.provider_id) {
	    if (form.admin_email.value=='')
	    {
		    alert("Please enter a value for the \"amdin_email\" field.");
		    form.admin_email.focus();
		    return (false);
	    }
	    if (form.admin_name.value=='')
	    {
		    alert("Please enter a value for the \"admin_name\" field.");
		    form.admin_name.focus();
		    return (false);
	    }
	    if (form.from_email.value=='')
	    {
		    alert("Please enter a value for the \"site_email_address\" field.");
		    form.from_email.focus();
		    return (false);
	    }
	    if (form.from_name.value=='')
	    {
		    alert("Please enter a value for the \"site_email_name\" field.");
		    form.from_name.focus();
		    return (false);
	    }
	    if (form.admin_password.value=='')
	    {
		    alert("Please enter a value for the \"password\" field.");
		    form.admin_password.focus();
		    return (false);
	    }
	    if (form.admin_password.value!=form.admin_password2.value)
	    {
		    alert("Your password does not match.");
		    form.admin_password.focus();
		    return (false);
	    }
	}
*/
    var url=form.site_url.value;
    
    // remove the last slash if it is present
    if (url.charAt(url.length-1)=='/')
        form.site_url.value=url.substring(0, url.length-1);
        
	var args=encodeForm(form);
	url=form.site_url.value+"/vinstall.php";
	url+="?"+args.join("&");
//	alert(url);
	showPageBox(url, true);
}

function showPageBox(url)
{
	var elem=document.getElementById('page_box');
	var elem1=document.getElementById('page_content');
	if (!elem || !elem1)
		return false;

	elem.style.visibility='visible';
	elem1.style.display='block';
	elem1.src=url;

	return false;
}

function hidePageBox()
{
	var elem=document.getElementById('page_box');
	var elem1=document.getElementById('page_content');
	if (!elem || !elem1)
		return false;
	elem.style.visibility='hidden';
	elem1.style.display='none';
	elem1.src='vinstall/blank.php';

	return false;
}

function gotoSite(form)
{
	if (form.site_url.value=='' || form.site_url.value=='http://')
	{
		alert("Please install the site first.");
		form.site_url.focus();
		return (false);
	}
	window.location=form.site_url.value+"/?page=SIGNIN";
	return false;
	
}
