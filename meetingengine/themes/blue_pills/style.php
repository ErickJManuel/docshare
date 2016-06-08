<?php
//#nav-primary ul li:hover { background-color: ${colors['tab_h']}; background-position: 100% -${tab_pict_h}px; }
//#nav-primary ul li a:hover { color: ${colors['tab_name']} !important; text-decoration: none; background-position: 0 -${tab_pict_h}px; }

print <<<END

body { margin: 3 0 3 0px; padding: 0px; text-align: center; background: ${colors['page_bg']} url(${picts['page_bg']}); color: $colors[0]; font: ${fonts['body']}; text-align: center; }
img { border-width:0px; }
div { margin: 0px; padding: 0px; min-height: 0px; }
form { margin: 0px; padding: 0px; min-height: 0px; }
input, select, textarea {font: ${fonts['body']}; }

.heading1 { margin-top: 10px; margin-bottom: 5px; font: ${fonts['heading1']}; padding-bottom:3px; color: ${colors['heading1']}; }
.heading2 { margin-top: 10px; margin-bottom: 5px; font: ${fonts['heading2']}; color: ${colors['heading2']}}
.heading3 { margin-top: 10px; margin-bottom: 5px; font-size:110%; font-weight: bold;}
.right_hd1 { margin-top: 0; margin-bottom: 5px; font-size:120%; color:${colors['heading1']}; }
.right_hd2 { margin-top: 0; margin-bottom: 5px; font-size:100%; color:${colors['link']};}

#main_page {
	background-color: #fff; 
	width: 800px;
	border-style: solid;
	border-width:1; 
	border-color: ${colors['select_bg']};
}
#main_content, #main_tabs { text-align: left; width: 760px; margin: 5px 0px 0px 0px; padding: 0px; position: relative; }
#main_content { background: ${colors['content_bg']}; }
#main_content a, #main_tabs a {text-decoration:none; color:${colors['link']};}
#main_content a:hover, #main_tabs a:hover { text-decoration:underline; color:${colors['link_h']};}

#main_content table { font-size: 100%; color:${colors['content']};}

#top-bar { margin: 10px 10px 0px 10px; padding:0px; width:760px; font: ${fonts['body']};}

#top-logo {text-align:left; padding-left: 10px; width:420px; }
#top-logo a {text-decoration:none;}

#sign-in { padding-right: 20px; width:340px; text-align: right;  color:${colors['content']}}
#sign-in a { font-weight: 900; text-decoration:none; color:${colors['link']};}
#sign-in a:hover { text-decoration:underline; color:${colors['link_h']};}

#nav-primary {height: 41px; background: #fff url($main_tab_bg_r_pict) no-repeat right bottom; }
#nav-primary ul {
	text-align:center; font-size:13px; list-style: none; 
	margin: 0px 0 0 0px; 
	padding: 0 0 0 50px; float: left; clear: both; 
	background: transparent url($main_tab_bg_l_pict) no-repeat left bottom;
}
#nav-primary ul li { margin: 0 3px; padding: 0; float: left; background: ${colors['tab_bg']} url($main_tab_r_pict) no-repeat 100% 0; white-space: nowrap; }
#nav-primary ul li a { color: ${colors['tab_name']}; display: block; text-decoration: none; background: url($main_tab_l_pict) no-repeat 0 0; margin: 0; text-align: center; font-weight: bold; }
#nav-primary ul li a:hover { color: ${colors['select_tab_name']}; display: block; text-decoration: none; }
#nav-primary ul li.on  { background-color: ${colors['tab_o']};  background-position: 100% -${tab_pict_h2}px; }
#nav-primary ul li.on a { color: ${colors['select_tab_name']}; background-position: 0 -${tab_pict_h2}px; }
#nav-primary ul li.over { background-color: ${colors['tab_o']};  background-position: 100% -${tab_pict_h}px; }
#nav-primary ul li.over a { background-position: 0 -${tab_pict_h}px; }
#nav-primary ul li.out { background-color: ${colors['tab_bg']};  background-position: 100% 0; }
#nav-primary ul li.out a { background-position: 0 0; }

#nav-primary ul li.over a, #nav-primary ul li.on a { padding: 8px 18px 17px 18px; }
#nav-primary ul li a, #nav-primary ul li.out a { padding: 6px 18px 19px 18px; }

#nav-secondary { clear: both; background: ${colors['tab_o']} url($sub_menu_r_pict) repeat 100% 0; height: 27px; }
#nav-secondary ul { text-align:center; font-size:12px; list-style: none; background: ${colors['tab_o']} url($sub_menu_l_pict) no-repeat 0 0; margin: 0px; padding: 0 0 0 40px; float: left; clear: both; }
#nav-secondary ul li { margin: 2px 30px 0 0; padding: 4px 0 0 0; float: left; display: block; white-space: nowrap; text-align: center; font-weight: bold;}
#nav-secondary ul li a { color: ${colors['sub_tab_name']}; text-decoration: none; position: relative; outline: none;}
#nav-secondary ul li a:hover, #nav-secondary ul li.on a { text-decoration: underline; font-weight: 900; }

.col1p { width: 0px; height: 400px; }
#left-content 
{
	height:350px; 
	padding: 10px 10px 10px 20px; 
	background: transparent url($main_page_bg_pict) repeat-x top left;
}
#l-text
{
	width: 510px;
	margin: 0px;
	font:${fonts['content']}; line-height: 1.2em; color:${colors['content']}; text-align:justify;
	background: transparent  url($content_bg_pict) no-repeat 0 0;

}
#one-text
{
	font:${fonts['content']}; line-height: 1.2em; color:${colors['content']}; text-align:justify;
	background: transparent  url($content_bg_pict) no-repeat 0 0;
}

#right-content {
	margin: 0px; 
	padding: 0 10px 0 0;
	background: ${colors['content_bg']} url($main_page_bg_pict) repeat-x top left;
}
#right-content .right_box {
	padding: 0px 0px 0px 0px; margin:0px; color:${colors['content']}; 
	}
#r-text { 
	padding: 0px 0 0 7px; margin:0px; text-align:left; font-size:70%; 
}
#r-text  a { color:${colors['link']}; font-weight:bold; text-decoration:none;   }
#r-text ul { margin-top: 5px; }

#one-content {
	height:350px; 
	margin: 0px;
	padding: 10px 35px 10px 35px;
	background: transparent url($main_page_bg_pict) repeat-x top left;
}

#footer-bar { text-align:center; background-color: ${colors['footer_bg']}; height:24px; }
#footer-bar ul { font:${fonts['body']}; list-style: none; margin: 4px 0 0 0; padding: 0 0 0 200px; float:left; }
#footer-bar ul li { margin: 0px 35px 0 0; padding: 0 0 0 0; float: left; display: block; white-space: nowrap; }

#footer_text { padding-top:10px; text-align:center; height:28px; color:${colors['content']};  }

.box1_top { margin-left: 5px; padding: 0 0 0 7px; height:8px; width:200px; background: transparent url($right_top_pict) bottom left no-repeat;}
.box1_mid { margin-left: 5px; padding: 0 0 0 7px; background: transparent url($right_mid_pict) top left repeat-y; }
.box1_bottom { margin-left: 5px; padding: 0 0 5 7px; height:8px; width:200px; background: transparent url($right_bot_pict) top left no-repeat; }

.box2_top {  margin: 0px 0 0 0; padding: 5px 0 0 0; height:6px; width:184px; background: transparent url($righti_top_pict) bottom left no-repeat; }
.box2_mid { margin: 0px; padding: 0px; width:184px; background: transparent url($righti_mid_pict) top left repeat; }
.box2_bottom { margin: 0 0 0 0; padding: 0 0 5px 0; height:6px; width:184px; background: transparent url($righti_bot_pict) top left no-repeat; }
.box-text { padding: 0 0 0px 8px; }
END;
/*
.login { text-align: left; }
.login table { font-size:120%; margin: 20px 0 20px 50px; }
.login p {margin: 0; }
.login div#login_error { font-size:80%; padding-bottom: 10px; color: ${colors['error']}; }
div#login_signup { font-size:110%; padding: 0px 0 0 0; margin: 0px 0 0px 50px; }
div#cookieDisabled { margin: 5px 0 0 !important; font-weight: bold; color: ${colors['error']}; }
div#forgotpwd { margin: 30px 0 0px 50px; font-size: 90%; line-height: 1.8em; }
div#forgotpwd #getpwderror { color: ${colors['error']}; }

.lib_mgr { margin: 0px; padding: 0px; height: 500px; }
.error { margin: 10px 0 10px 0; padding:0 0 0 0; font-size: 100%; vertical-align: text-bottom; color: ${colors['error']}; font-weight: 900; }
.inform { margin: 10px 0 10px 0; color: ${colors['message']}; font-weight: 900; }
.error img, .inform img { vertical-align: text-bottom; }
.rt_align { text-align: right; }

.tool_tip { margin: 10px 0 0 10px; padding:4px 4px 4px 4px; position: absolute; z-index: 1; visibility: hidden; background-color:${colors['tip_bg']}; }
.tool_icon { padding: 0 5px 0 0; }
.signup_button { padding-top: 5px; }
.info_text { padding-left: 10px; padding-bottom: 5px;}

#invite { margin: 10px 50px 10px 0; font-size:120%; width: 100%; clear: both;}
.invite_val { margin-left: 15px; text-align: left;}
.invite_msg { margin: 15px 0px 0 15px; text-align: left; font-weight:bold;}
.invite_info { width: 100%; }
.invite_url { font-size:100%; font-weight:bold; }
.invite_id { font-size:100%; text-align: right; }
.invite_id span {font-weight:bold; }
.invite_bar { margin-top: 10px; padding: 5px 0 0 20px; font-weight: bold; background-color: ${colors['table_hdr']}; color:#fff; font-size: 100%; width:100%; height: 20px; }



.meeting_list { margin: 5px 0 0 0; font-size: 84%; width: 100%; padding-top: 5px;}
.meetings_tz1 { padding-top: 10px; text-align: right; font-size: 90% }
.meeting_list img { border-width:0; vertical-align:text-bottom; }
.meetings_tz2 { padding: 10px 0 0 100px; font-size: 90% }

.meeting_list tr { vertical-align: top; }
.meeting_list th { font-size: 90%; padding: 5px 7px; background-color: ${colors['table_hdr']} ; color: #fff; }

.meeting_list th.tl { background-color: ${colors['table_hdr']}; }
.meeting_list th.tr { background-color: ${colors['table_hdr']}; }
.meeting_list .pipe { border-right: 1px solid #fff; }
.meeting_list td { border-bottom: 1px solid ${colors['select_bg']};}
.meeting_list td.m_id { width:50px; padding: 5px 5px 0px 0px;}
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

.u_item { padding: 5px 5px 5px 5px;}
.u_item_b { font-weight:bold; }
.u_name { font-weight:bold; width: 180px; padding: 5px 5px 5px 5px;}
.u_group { width: 80px; }
.u_item_c { text-align: center; padding: 5px 5px 5px 5px;}
.u_bg { background: ${colors['table_bg']}; }
.u_item_w300 { width: 300px; }
.u_item_w50 { width: 50px; }
.u_item_w100 { width: 100px; }
.u_item_i { font-style: italic; color: ${colors['content']};}

.m_date { font-size: 90%; width:80px; padding: 5px 7px;}
.m_date2 { width:100px;  padding: 5px 7px; vertical-align:middle;}
.progress { padding: 5px 5px 5px 5px; color:${colors['link_h']}; font-weight:900;}
.m_tool {  width: 170px; padding-right: 5px; font-size: 80%; text-align:left; vertical-align:middle;}
.m_tool a { text-decoration: none; }

.m_but { width:120px; padding-left: 5px; text-align:left; font-size:100%; font-weight:900; vertical-align:middle;}
.m_but2 { width:85px;}
.m_but a { text-decoration: none; }

.meeting_detail{ margin: 10px 10 10 10; font-size: 100%; width:100%; clear: both; }
.m_key { width:130px; text-align: right; padding: 8px 10px 8px 0; vertical-align:top; font-size: 110%; font-weight: 900; }
.m_key_w { width:200px; }
.m_key_m { width:160px; }
.m_subkey { font-weight: bold; }
.m_val { line-height: 2em; padding: 8px 0 8px 0px; vertical-align:top;}
.m_key1 { text-align: right; padding: 8px 3px 8px 0px; width:40px; font-size: 120%; font-weight: 900; vertical-align:top;}
.m_url { padding: 12px 0 8px 0px; vertical-align:top;}
.sub_val3 { padding-left: 42px; }
.sub_val2 { padding-left: 22px; }
.sub_val1 { padding-left: 0px; }
.sub_choice { padding-left: 22px; padding-top: 10px; }
.m_caption { line-height: 1.3em; padding: 2px 10px 0 10px; font-size: 80%; color: ${colors['content']}; }

.m_button { margin-left: 15px; font-size: 110%; font-weight:bold; }
.m_button_s { margin-left: 5px; font-size: 100%; font-weight:bold;}
.m_button img, .m_button_s img { vertical-align:text-bottom; }

.meeting_frame_top { background: url($frame_top_pict) top left no-repeat; margin: 15px 0 0 10px; padding: 4px 0px 0 0; width: 500px;  }
.meeting_frame_bot { background: url($frame_bot_pict) bottom left no-repeat; margin: 0 0 0px 0px; padding: 0; width: 500px; }

.meeting_host { margin: 10px 0 10px 10px; font-size: 110%; }
.meeting_desc { margin: 0px 0px 5px 10px; font-size: 100%; }


.page_nav { font-size: 100%; margin: 0px 3px 0 0; padding: 5px 0 0 0px; }
.page_nav ul { list-style: none; margin: 0px 0 0px 0px; padding: 0px 0px 0px 0px;}
.page_nav li { float: left; padding: 0px 5px 5px 5px; }
.page_nav li a { }
.page_nav li.on { background-color: ${colors['select_bg']}; }
.page_nav li.on a { text-decoration: underline !important;  }
.page_nav_count { padding-left: 20px; padding-right: 5px; }

#user_info { font-size: 100%; width: 100%; padding: 20px 20px 20px 20px;  }
#user_info_l { padding: 30px 5px 20px 10px; vertical-align: top; text-align: center;}
#user_info_r { width: 340px; padding: 30px 10px 20px 5px; vertical-align: top;  }
#user_name { font-size: 130%; font-weight: bold; padding: 0px 0 10px 0; }
#user_title { padding: 5px 0 5px 0;}
#user_address, #user_city, #user_country { font-size: 90%; padding: 0px 0 0px 0; }
.info_key { padding-right: 20px; text-align: right; font-weight: bold; font-size: 110%; }
.info_key2 {  padding-right: 20px; text-align: right; font-size: 110%; }

.conf_info { font-size: 100%; width: 100%; padding: 25px 20px 10px 20px;  }
.conf_info1 { font-size: 100%; width: 100%; padding: 10px 20px 25px 20px;  }
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
.comment_title { width: 100%; height: 22px; background-color: ${colors['table_bg']} ; }
.comment_icon { vertical-align:middle; width: 24px; }
.comment_name { padding-left: 0px; width: 240px; font-size: 100%; font-weight: bold; text-align: left; }
.comment_time { padding-left: 5px; width: 140px; font-size: 90%;  text-align: left;}
.comment_remove { padding-right: 5px; text-align: right;}
.comment_meeting { width: 180px; text-align: left; font-size: 100%;}
.comment_email { padding-left: 5px; padding-right: 5px; font-size: 90%; font-weight: normal; }

.comment_body { margin: 10px 20px 0px 30px; font-size: 100%; }

.alert { color: ${colors['error']}; font-weight: 900; }

.user_detail{margin: 10px 0 10px 0; font-size: 100%; width:100%; clear: both; }
.user_detail td.m_key { width:130px; text-align: right; padding: 8px 10px 8px 0; vertical-align:middle; font-size: 110%; font-weight: 900; }
.user_detail td.m_val { line-height: 2em; padding: 8px 0 8px 0px; vertical-align:top;}

.download-step { font-size: 100%; font-weight: bold; padding: 10px 0 10px 0;}

.text-box { border-width:1; background-color:#f0f0f0; border-style:dotted; }
.text-box pre { font-size:8pt; padding:0 10px 0 10px; }

*/

?>
