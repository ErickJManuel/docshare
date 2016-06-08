<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


print <<<END

body { margin: 3 0 3 0px; padding: 0px; text-align: center; background: ${colors['page_bg']} url(${picts['page_bg']}); color: $colors[0]; font: ${fonts['body']}; text-align: center; }
img { border-width:0px; vertical-align: middle}
div { margin: 0px; padding: 0px; min-height: 0px; }
form { margin: 0px; padding: 0px; min-height: 0px; }
input, select, textarea {font: ${fonts['body']}; }

.heading1 { margin-top: 10px; margin-bottom: 5px; font: ${fonts['heading1']}; padding-bottom:3px; color: ${colors['heading1']}; }
.heading2 { margin-top: 10px; margin-bottom: 5px; font: ${fonts['heading2']}; color: ${colors['heading2']}}
.heading3 { margin-top: 10px; margin-bottom: 5px; font-size:110%; font-weight: bold;}
.right_hd1 { margin-top: 0; margin-bottom: 5px; font-weight: bold; font-size:120%; color:${colors['heading1']}; }
.right_hd2 { margin-top: 0; margin-bottom: 5px; font-size:100%; color:${colors['link']};}

#main_page {
	background-color: #fff; 
	width: 800px;
	border-style: solid;
	border-width:1; 
	border-color: ${colors['select_bg']};
	margin-bottom:20px;
}
#main_content, #main_tabs, #bottom_content { text-align: left; width: 760px; margin: 5px 0px 0px 0px; padding: 0px; position: relative; }
#main_content, #bottom_content { background: ${colors['content_bg']}; }
#main_content a, #bottom_content a, #main_tabs a {text-decoration:none; color:${colors['link']};}
#main_content a:hover, #bottom_content a:hover, #main_tabs a:hover { text-decoration:underline; color:${colors['link_h']};}

#main_content table, #bottom_content table { font-size: 100%; color:${colors['content']};}

#top-bar { margin: 10px 10px 0px 10px; padding:0px; width:760px; font: ${fonts['body']};}

#top-logo {text-align:left; padding-left: 0px; width:420px; }
#top-logo a {text-decoration:none;}

#sign-in { padding-right: 20px; width:340px; text-align: right;  color:${colors['content']}}
#sign-in a { font-weight: 900; text-decoration:none; color:${colors['link']};}
#sign-in a:hover { text-decoration:underline; color:${colors['link_h']};}

#nav-primary {}
#nav-primary ul { text-align:center; font-size:14px; list-style: none; margin: 0px 0 0 0; padding: 0 0 0 50px; float: left; clear: both; }
#nav-primary ul li { min-width: 80px; margin: 0 3px; padding: 0; float: left; background: ${colors['tab_bg']} url($main_tab_r_pict) no-repeat 100% 0; white-space: nowrap; }
#nav-primary ul li a { color: ${colors['tab_name']}; display: block; text-decoration: none; background: url($main_tab_l_pict) no-repeat 0 0; margin: 0; padding: 7px 12px 6px 12px; text-align: center; font-weight: bold; }
#nav-primary ul li a:hover { color: ${colors['tab_name']}; display: block; text-decoration: none; }
#nav-primary ul li.on  { background-color: ${colors['tab_o']};  background-position: 100% -${tab_pict_h2}px; }
#nav-primary ul li.on a { background-position: 0 -${tab_pict_h2}px; }
#nav-primary ul li.over { background-color: ${colors['tab_o']};  background-position: 100% -${tab_pict_h}px; }
#nav-primary ul li.over a { background-position: 0 -${tab_pict_h}px; }
#nav-primary ul li.out { background-color: ${colors['tab_bg']};  background-position: 100% 0; }
#nav-primary ul li.out a { background-position: 0 0; }

#nav-secondary { clear: both; background: ${colors['tab_o']} url($sub_menu_r_pict) no-repeat 100% 0; height: 27px; }
#nav-secondary ul { text-align:center; font-size:12px; list-style: none; background: url($sub_menu_l_pict) no-repeat 0 0; margin: 0px; padding: 0 0 0 30px; float: left; clear: both; }
#nav-secondary ul li { margin: 2px 30px 0 0; padding: 4px 0 0 0; float: left; display: block; white-space: nowrap; text-align: center; font-weight: bold;}
#nav-secondary ul li a { color: ${colors['tab_name']}; text-decoration: none; position: relative; outline: none;}
#nav-secondary ul li a:hover, #nav-secondary ul li.on a { text-decoration: underline; font-weight: 900; }

.col1p { width: 0.01%; height: 400px; }
#left-content 
{
	height:350px; 
	padding: 10px 0px 10px 20px; background-color:${colors['content_bg']};
}
#l-text
{
	width: 510px;
	margin: 0px;
	font:${fonts['content']}; line-height: 1.2em; color:${colors['content']}; text-align:justify;
}
#one-text
{
	font:${fonts['content']}; line-height: 1.2em; color:${colors['content']}; text-align:justify;
}

#right-content .right_box {padding: 5px 0px 0px 0px; margin:0px 0px 0 0; color:${colors['content']}; }
#r-text { text-align:left; font:${fonts['body']}; }
#r-text  a { color:${colors['link']}; font-weight:bold; text-decoration:none;   }
#r-text ul { margin-top: 5px; }

#one-content {
	height:350px; 
	margin: 0px;
	padding: 10px 35px 10px 35px;
	background-color:${colors['content_bg']}; 
	}

#footer-bar { text-align:center; background-color: ${colors['footer_bg']}; height:24px; }
#footer-bar ul { font:${fonts['body']}; list-style: none; margin: 0px 0 0 0; padding: 0 0 0 150px; float:left; }
#footer-bar ul li { margin: 0px 35px 0 0; padding: 0 0 0 0; float: left; display: block; white-space: nowrap; }

#footer_text { padding-top:10px; padding-bottom: 10px; min-height: 40px; text-align:center; color:${colors['content']}; }

.box1_top { margin-left: 5px; padding: 0 0 0 7px; height:8px; width:200px; background: transparent url($right_top_pict) bottom left no-repeat;}
.box1_mid { margin-left: 5px; padding: 0 0 0 7px; background: transparent url($right_mid_pict) top left repeat-y; }
.box1_bottom { margin-left: 5px; padding: 0 0 5 7px; height:8px; width:200px; background: transparent url($right_bot_pict) top left no-repeat; }

.box2_top {  margin: 0px 0 0 0; padding: 5px 0 0 0; height:6px; width:184px; background: transparent url($righti_top_pict) bottom left no-repeat; }
.box2_mid { margin: 0px; padding: 0px; width:184px; background: transparent url($righti_mid_pict) top left repeat; }
.box2_bottom { margin: 0 0 0 0; padding: 0 0 5px 0; height:6px; width:184px; background: transparent url($righti_bot_pict) top left no-repeat; }
.box-text { padding: 0 0 0px 8px; }

#iphone-message { 
	text-align: left;
	background: transparent url('../iPhone.gif') top left no-repeat;
	vertical-align: middle;
	padding-left: 72px;
	height: 64px;
	font-size: 30px;
	font-weight: bold;
}
#iphone-message a {
	text-decoration:none;
}

END;

?>