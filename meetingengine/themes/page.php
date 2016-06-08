<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


print <<<END

#page_menu {   }
#page_menu ul { text-align:left; font-size:110%; list-style: none; margin: 0px; padding: 0 0 0 20px; clear: both;}
#page_menu ul li { margin: 0px 30px 0 0; padding: 5px 0 0 0; float: left; display: block; white-space: nowrap; text-align: left; font-weight: bold;}
#page_menu ul li a { text-decoration: none; position: relative; outline: none;}
#page_menu ul li a:hover, #page_menu ul li.on a { text-decoration: underline; font-weight: 900; } 

.login { text-align: left; }
.login table { font-size:120%; margin: 20px 0 20px 50px; }
.login p {margin: 0; }
.login div#login_error { font-size:80%; padding-bottom: 10px; color: ${colors['error']}; }
div#login_signup { font-size:110%; padding: 0px 0 0 0; margin: 0px 0 0px 50px; }
div#forgotpwd { margin: 40px 0 0 50px; font-size: 10px; line-height: 1.8em; }
div#forgotpwd #getpwderror { color: ${colors['error']}; }
#login_id, #login_pass { height: 30px;}


.lib_mgr { margin: 0px; padding: 0px; width:100%; height: 500px;}
.error { background: transparent url('../error.gif') no-repeat 0 0; line-height: 16px; padding: 0px 0 0px 20px; margin: 10px 0 10px 0; font-size: 100%; color: ${colors['error']}; font-weight: 900; }
.inform { background: transparent url('../inform.gif') no-repeat 0 0; line-height: 16px; padding: 0px 0 0px 20px; margin: 10px 0 10px 0; color: ${colors['message']}; font-weight: 900; }
.rt_align { text-align: right; }

.tool_tip { margin: 10px 0 0 10px; padding:4px 4px 4px 4px; position: absolute; z-index: 1; visibility: hidden; background-color:${colors['tip_bg']}; }
.tool_icon { padding: 0 5px 0 0; }
.signup_button { padding-top: 5px; }
.info_text { padding-left: 10px; padding-bottom: 5px;}
.ret_link { padding-left: 20px; }

#invite { margin: 10px 50px 10px 0; font-size:120%; width: 100%; clear: both;}
.invite_val { margin-left: 15px; text-align: left;}
.invite_msg { margin: 15px 0px 0 15px; text-align: left; font-weight:bold;}
.invite_info { width: 100%; }
.invite_url { font-size:100%; font-weight:bold; }
.invite_id { font-size:100%; text-align: right; }
.invite_id span {font-weight:bold; }
.invite_bar { margin-top: 10px; padding: 5px 0 0 20px; height: 20px; font-weight: bold; background: ${colors['table_hdr']}; color:#fff; font-size: 100%;  }

.large-text { font-size: 130%;}

.meeting_list { margin: 5px 0 0 0; font-size: 84%; width: 100%; padding-top: 0px;}
.meetings_tz1 { padding-top: 10px; text-align: right; font-size: 90% }
.meeting_list img { border-width:0; vertical-align:text-bottom; }
.meetings_tz2 { padding: 10px 0 0 100px; font-size: 90% }

.meeting_list tr { vertical-align: top; }
.meeting_list th { font-size: 90%; padding: 5px 7px; background: ${colors['table_hdr']} ; color: #fff; }

.meeting_list th.tl { background: ${colors['table_hdr']}; }
.meeting_list th.tr { background: ${colors['table_hdr']}; }
.meeting_list .pipe { border-right: 1px solid #fff; }
.meeting_list td { border-bottom: 1px solid ${colors['select_bg']};}
.meeting_list td.m_id { padding: 5px 5px 0px 0px;}
.m_info { }
.m_info ul, .m_date ul, .m_tool ul, .m_but ul { list-style: none; padding: 0px 0 5px 0; margin: 0px 0 0px 0;}
.m_info li, .m_date li, .m_but li { padding: 5px 0 0 0;}
.m_tool li { padding: 2px 0 0 0;}
.m_title { font-size: 110%; font-weight: bold; }
.m_desc { line-height: 1.2em; color: ${colors['content']};}
.m_host a { text-decoration:underline !important; }
.m_date3 { width:100px; padding-left: 5px; text-align:center; font-size:90%; vertical-align:middle;}
.m_icon img { border-width:0; padding: 0px 8px 0px 0px; }
.m_param { padding-left:5px; padding-right:10px;  font-size: 100%; font-weight: bold; }
.m_vinfo { text-align: center; vertical-align: middle; }

.u_item { padding: 5px 5px 5px 5px;}
.u_item_b { font-weight:bold; }
.u_name { font-weight:bold; width: 180px; padding: 5px 5px 5px 5px;}
.u_group { width: 80px; }
.u_item_c { text-align: center; padding: 5px 5px 5px 5px;}
.u_bg { background: ${colors['table_bg']}; }
.u_item_w300 { width: 300px; }
.u_item_ws { width: 40px; }
.u_item_w100 { width: 100px; }
.u_item_i { font-style: italic; color: ${colors['content']};}

.m_date { font-size: 90%; padding: 5px 5px;}
.m_date2 { width:100px;  padding: 5px 7px; vertical-align:middle;}
.progress { padding: 5px 5px 5px 5px; color:${colors['link_h']}; font-weight:900;}
.m_tool {  padding-top: 5px; padding-bottom: 5px; padding-right: 5px; font-size: 80%; text-align:left; vertical-align:middle;}
.m_tool a { text-decoration: none;}
.m_tool img { vertical-align:middle;}

.m_but { padding-left: 5px; text-align:left; font-size:100%; font-weight:900; vertical-align:middle;}
.m_but2 { width:85px;}
.m_but a { text-decoration: none; }

.meeting_detail{ margin: 10px; font-size: 100%; width:100%; clear: both; }
.m_key { width:130px; text-align: right; margin: 0; padding: 8px 10px 8px 0; vertical-align:top; font-size: 110%; font-weight: 900; }
.m_key img { vertical-align: middle }
.m_key_w { width:200px; }
.m_key_w2 { width:220px; }
.m_key_m { width:160px; }
.m_subkey { font-weight: bold; }
.m_val { line-height: 1.5em; margin: 0; padding: 8px 0 8px 0px; vertical-align:top;}
.m_key1 { text-align: right; padding: 8px 3px 8px 0px; width:40px; font-size: 120%; font-weight: 900; vertical-align:top;}
.m_url { padding: 12px 0 8px 0px; vertical-align:top;}
.sub_val3 { padding-left: 42px; }
.sub_val2 { padding-left: 22px; }
.sub_val1 { padding-left: 0px; }
.sub_choice { padding-left: 22px; padding-top: 10px; }
.m_caption { line-height: 1.3em; padding: 2px 10px 0 10px; font-size: 80%; color: ${colors['content']}; }
.top_line { border-top: solid 1px #777 }

.m_button_l { margin-left: 15px; font-size: 130%; font-weight:bold; }
.m_button_l a { text-decoration: none !important; }
.m_button_l img { vertical-align: text-bottom; }

.m_button { margin-left: 15px; font-size: 110%; font-weight:bold; }
.m_button_s { margin-left: 5px; font-size: 100%; font-weight:bold;}
.m_button img, .m_button_s img { vertical-align:text-bottom; }

.m_button_m { font-size: 100%; font-weight:bold; }
.m_button_m img { vertical-align:text-bottom; }
.m_button_m a { text-decoration: none !important; }

.meeting_frame_top { background: url($frame_top_pict) top left no-repeat; margin: 15px 0 0 10px; padding: 4px 0px 0 0; width: 500px;  }
.meeting_frame_bot { background: url($frame_bot_pict) bottom left no-repeat; margin: 0 0 0px 0px; padding: 0; width: 500px; min-height: 130px;}
* html .meeting_frame_bot {  height: 130px;}	/* for IE becaues it doesn't support min-height (why?) */

.meeting_host { margin: 10px 0 10px 10px; font-size: 110%; }
.meeting_desc { margin: 0px 0px 10px 10px; font-size: 100%; }


.page_nav { font-size: 100%; margin: 0px 3px 0 0; padding: 5px 0 0 0px; }
.page_nav ul { list-style: none; margin: 0px 0 0px 0px; padding: 0px 0px 0px 0px;}
.page_nav li { float: left; padding: 0px 5px 5px 5px; }
.page_nav li a { }
.page_nav li.on { background-color: ${colors['select_bg']}; }
.page_nav li.on a { text-decoration: underline !important;  }
.page_nav_count { padding-left: 20px; padding-right: 5px; }

#locale { margin-top: 5px; }

#user_info { font-size: 100%; width: 100%; padding: 20px 20px 20px 20px;  }
#user_info_l { padding: 30px 5px 20px 10px; vertical-align: top; text-align: center;}
#user_info_r { width: 340px; padding: 30px 10px 20px 5px; vertical-align: top;  }
#user_name { font-size: 130%; font-weight: bold; padding: 0px 0 10px 0; }
#user_title { padding: 5px 0 5px 0;}
#user_address, #user_city, #user_country { font-size: 90%; padding: 0px 0 0px 0; }
.info_key { padding-right: 20px; text-align: right; font-weight: bold; font-size: 110%; }
.info_key2 {  padding-right: 20px; text-align: right; font-size: 110%; }

.conf_info { font-size: 100%; width: 100%; padding: 10px 20px 5px 20px;  }
.conf_info1 { font-size: 100%; width: 100%; padding: 5px 20px 5px 70px;  }
.conf_key { font-weight: bold; font-size: 110%; }
.conf_btns { padding-left: 30px; padding-top: 10px; }
.conf_btn { padding-left: 10px; }

#room_info_l { width: 100px; vertical-align: top; text-align: left; }
#room_info_r { width: auto; vertical-align: top;  }

#user_email { font-size: 100%; padding: 5px 0 5px 0; }
#user_phone { font-size: 100%; padding: 5px 0 5px 0; }
#user_room { font-size: 90%; padding: 3px 0 5px 0; }
.phone_key { font-size: 90%; }


#user_vcard { padding: 5px 0 10px 0;}
#user_vcard img { vertical-align: text-bottom;}

#edit_prof_msg { padding: 0px 0px 0 100px; font-size: 90%; color: ${colors['content']}; }

.edit_user { padding: 0px 3px 0 0px; font-size: 80%; color: ${colors['content']}; }

.list_tools { font-weight: bold; text-align:left; font-size: 110%; padding: 10px 0 5px 0;}
.list_tools img { vertical-align: text-bottom; }
.list_item { padding-right: 15px; }
.list_item_select a { text-decoration: underline; }

.bullet_list { font-weight: bold; text-align:left; font-size: 110%;}
.bullet_list li { padding-bottom: 10px; }

#send_all { padding-left: 20px; }
#back_pict { padding-left: 10px; }
#back_pict img { vertical-align: top; width: 120px; height: 90px; }

.itemlist { list-style: none; margin: 10px 0 10px 0; padding: 0 0 0 30px;}

.sublist ul { list-style: none; float: left; clear: both; font-size:110%; font-weight: bold; margin: 10px 0 10px 0; padding: 0 0 0 0px; }
.sublist ul li { float:left; margin: 0px 0px 10px 0; padding: 0 0 0 30px; }
.sublist ul li a:hover, .sublist_on a {text-decoration: underline !important; color:${colors['link_h']} !important; }

.meetings_select { margin: 0px; }
#select_show { }
#select_search { padding-left: 15px; }
#select_search2 { padding-top: 5px; }

#meeting_stat { text-align: right; }

.report_bar { width: 100%; padding-top: 5px;}
.report_left {}
.report_right { text-align: right; font-weight: bold; font-size: 110%; margin: 0px 0 5px 0; }

.comment_heading { margin-top: 10px; width: 100%; }
.comment_head_text { font-size: 130%; font-weight: bold; }
.comment_post { text-align: right; font-size: 110%; }
.comment_post_icon { vertical-align:middle;}
.comment_count {padding-left: 15px; font-size: 100%; }
.comment_tb { padding: 0px 0 5px 0; }

.comment_item { padding: 0; margin: 10px 0 10px 0; width:100%; }
.comment_box { width:20px; }
.comment_title { width: 100%; height: 22px; background-color: ${colors['table_bg']} ; }
.comment_icon { vertical-align:middle; width: 24px; }
.comment_name { padding-left: 0px; width: 240px; font-size: 100%; font-weight: bold; text-align: left; }
.comment_time { padding-left: 5px; width: 140px; font-size: 90%;  text-align: left;}
.comment_remove { width: 60px; padding-right: 5px; text-align: right;}
.comment_meeting { text-align: left; font-size: 100%;}
.comment_email { width: 200px; padding-left: 5px; padding-right: 5px; font-size: 90%; font-weight: normal; }

.comment_body { margin: 10px 20px 0px 30px; font-size: 100%; }

.right-aligned { text-align:right; padding-right:10px; padding-bottom:0px; }

.alert { color: ${colors['error']}; font-weight: 900; }

.user_detail{margin: 10px 0 10px 0; font-size: 100%; width:100%; clear: both; }
.user_detail td.m_key { width:130px; text-align: right; padding: 8px 10px 8px 0; vertical-align:middle; font-size: 110%; font-weight: 900; }
.user_detail td.m_val { line-height: 2em; padding: 8px 0 8px 0px; vertical-align:top;}

.download-step { font-size: 100%; font-weight: bold; padding: 10px 0 10px 0;}

.text-box { border-width:1; background-color:#f0f0f0; border-style:dotted; }
.text-box pre { font-size:12px; padding:0 10px 0 10px; }

#version_text { font-size:10px; vertical-align: top; text-align: right; padding-right: 5px;}

.wait_icon {
	width: 48px; height: 48px;
	background: transparent url('../loading.gif') no-repeat 50% 50%;
	vertical-align: middle;
}
.error_icon {
	width: 48px; height: 48px;
	background: transparent url('../error.gif') no-repeat 50% 50%;
	vertical-align: middle;

}
.inform_icon {
	width: 48px; height: 48px;	
	background: transparent url('../inform.gif') no-repeat 50% 50%;
	vertical-align: middle;
}

/*works for FF*/
#shade {
	display: none;
	z-index: 5;
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	clear: none;
	margin: 0;

	background-color: #fff;
	/* for IE */
	filter:alpha(opacity=50);
	/* CSS3 standard */
	opacity:0.5;
}
/*IE will need an 'adjustment'*/
* html #shade
{
     height: expression(document.documentElement.scrollHeight + 'px');
}

#load_bar {
	display: none;
	z-index: 10;
	position: absolute;
	left: 50%;
	top: 50%;
	margin-top:-50px;
	margin-left:-30px;
	vertical-align: middle;
	width: 100px;
	height: 60px;
	-webkit-border-radius:10px;
	border:3px solid #aaa;
	background: #fff url('../loading.gif') no-repeat 50% 50%;
}
/*IE will need an 'adjustment'*/
* html #load_bar
{
    top: expression(document.documentElement.scrollHeight/2 + 'px');
}


div.progress_box {
	z-index:2;
	position: absolute;
	left: 50%;
	top: 50%;
	margin-top:-80px;
	margin-left:-240px;
	width: 480px;
	height: 160px;
	vertical-align: middle;
	padding: 0;
	background-color: #ffffff;
	border: 1px solid black;
	-webkit-border-radius:10px;

	/* for IE */
/*	filter:alpha(opacity=85); */
	/* CSS3 standard */
/*	opacity:0.85; */
}
div.progress_box table
{
	width: 400px;
	padding: 0;
	font-weight: bold;
	font-size: 16px;
	color: #000000;
}


#meeting_signin {
	padding: 5px 0 0px 10px;
}

.mpage_row { clear: both; width: 580px; margin-left: 100px; }
.mpage_col { float: left; width: 290px; vertical-align: middle;}
.mpage_label { padding-bottom: 3px; }
.mpage_button1 { padding: 10px;}
.mpage_button2 { padding: 10px; font-weight: bold;}
.mpage_img1 { vertical-align: middle; margin-left: 30px}
.mpage_img2 { vertical-align: middle; padding-right: 5px;}

.mpage_title { font-weight: bold; }
.mpage_list {margin: 10px; }
.mpage_list_item { margin-left: 0px; height: 20px; }
.mpage_list_label { text-align: right; padding-right: 10px; font-weight: bold;}
.mpage_list_val { white-space: nowrap; font-weight: bold;}
.mpage_text { font-weight: normal; padding: 10px; }

.poll_tb { width: 100px; border-collapse:collapse; border: 1px solid #999; line-height: 10px;}
.poll_bg { background-color:#f0f0f0;}
.poll_bar { background-color:#33cc33;}
END;

// div#cookieDisabled { font-size: 80%; margin: 5px 0 0 !important; font-weight: bold; color: ${colors['error']}; }

?>