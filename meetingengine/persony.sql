-- Persony Web Conferencing 2.0
-- Copyright 2009 Persony, Inc.
--
-- Database: `wc2`
--

-- --------------------------------------------------------

--
-- Table structure for table `wc2_attendee`
--

CREATE TABLE IF NOT EXISTS `wc2_attendee` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `attendee_id` int(10) unsigned NOT NULL default '0',
  `session_id` int(10) unsigned NOT NULL default '0',
  `start_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `mod_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `break_time` int(10) unsigned NOT NULL default '0',
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_name` varchar(31) NOT NULL default '',
  `user_ip` varchar(15) NOT NULL default '',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `caller_id` varchar(15) NOT NULL default '',
  `cam_time` int(10) unsigned NOT NULL default '0',
  `server_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `attendee_id` (`attendee_id`),
  KEY `session_id` (`session_id`),
  KEY `mod_time` (`mod_time`),
  KEY `brand_id` (`brand_id`),
  KEY `start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_aws`
--

CREATE TABLE IF NOT EXISTS `wc2_aws` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `image_id` varchar(63) NOT NULL default '',
  `instance_id` varchar(63) NOT NULL default '',
  `host_name` varchar(63) NOT NULL default '',
  `state` varchar(31) NOT NULL default '',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_background`
--

CREATE TABLE IF NOT EXISTS `wc2_background` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(31) NOT NULL default '',
  `description` text NOT NULL default '',
  `onpict_id` int(10) unsigned NOT NULL default '0',
  `offpict_id` int(10) unsigned NOT NULL default '0',
  `wb_x` smallint(6) NOT NULL default '0',
  `wb_y` smallint(6) NOT NULL default '0',
  `wb_s` smallint(6) NOT NULL default '0',
  `screen_x` smallint(6) NOT NULL default '0',
  `screen_y` smallint(6) NOT NULL default '0',
  `screen_s` smallint(6) NOT NULL default '0',
  `slide_x` smallint(6) NOT NULL default '0',
  `slide_y` smallint(6) NOT NULL default '0',
  `slide_s` smallint(6) NOT NULL default '0',
  `public` enum('N','Y') NOT NULL default 'N',
  `author_id` int(10) unsigned NOT NULL default '0',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `public` (`public`),
  KEY `author_id` (`author_id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `background`
--

INSERT INTO `wc2_background` (`id`, `name`, `description`, `onpict_id`, `offpict_id`, `wb_x`, `wb_y`, `wb_s`, `screen_x`, `screen_y`, `screen_s`, `slide_x`, `slide_y`, `slide_s`, `public`, `author_id`, `brand_id`) VALUES
(1, 'Conference room', '', 3, 4, 395, 165, 400, 395, 165, 400, 395, 165, 400, 'Y', 0, 0),
(2, 'Auditorium', '', 5, 6, 400, 220, 230, 400, 220, 230, 400, 220, 230, 'Y', 0, 0),
(3, 'Beach view office', '', 9, 10, 659, 309, 348, 382, 285, 450, 142, 298, 455, 'Y', 0, 0),
(4, 'Bay view office', '', 7, 8, 659, 309, 348, 382, 285, 450, 142, 298, 455, 'Y', 0, 0),
(5, 'Sunset view office', '', 11, 12, 659, 309, 348, 382, 285, 450, 142, 298, 455, 'Y', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `wc2_brand`
--

CREATE TABLE IF NOT EXISTS `wc2_brand` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `create_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `name` varchar(31) NOT NULL default '',
  `site_url` varchar(255) NOT NULL default '',
  `theme` varchar(31) NOT NULL default '',
  `viewer_id` int(10) unsigned NOT NULL default '0',
  `locale` varchar(8) NOT NULL default '0',
  `footnote` varchar(255) NOT NULL default '',
  `admin_id` int(10) unsigned NOT NULL default '0',
  `provider_id` smallint(5) unsigned NOT NULL default '0',
  `getconf_url` varchar(255) NOT NULL default '',
  `getconf_login` varchar(15) NOT NULL default '',
  `getconf_pwd` varchar(15) NOT NULL default '',
  `home_text` text NOT NULL default '',
  `custom_help` enum('N','Y') NOT NULL default 'N',
  `help_text` text NOT NULL default '',
  `footer1_label` varchar(31) NOT NULL default 'About Us',
  `footer1_text` text NOT NULL default '',
  `footer2_label` varchar(31) NOT NULL default 'Contact Us',
  `footer2_text` text NOT NULL default '',
  `footer3_label` varchar(31) NOT NULL default 'Terms of Service',
  `footer3_text` text NOT NULL default '',
  `footer4_label` varchar(31) NOT NULL default 'Privacy Policy',
  `footer4_text` text NOT NULL default '',
  `logo_id` int(10) unsigned NOT NULL default '0',
  `time_zone` varchar(6) NOT NULL default '',
  `add_mail_id` int(10) unsigned NOT NULL default '0',
  `product_name` varchar(63) NOT NULL default '',
  `from_name` varchar(63) NOT NULL default '',
  `company_url` varchar(255) NOT NULL default '',
  `from_email` varchar(63) NOT NULL default '',
  `offerings` varchar(255) NOT NULL default '',
  `trial_signup` enum('N','Y') NOT NULL default 'Y',
  `trial_license_id` smallint(5) unsigned NOT NULL default '0',
  `trial_group_id` int(10) unsigned NOT NULL default '0',
  `notify` enum('N','Y') NOT NULL default 'Y',
  `embed_site` enum('N','Y') NOT NULL default 'N',
  `status` enum('ACTIVE','INACTIVE') NOT NULL default 'ACTIVE',
  `api_key` varchar(32) NOT NULL default '',
  `hide_navbars` enum('N','Y') NOT NULL default 'N',
  `hide_signin` enum('N','Y') NOT NULL default 'N',
  `custom_tabs` varchar(63) NOT NULL default '',
  `hook_id` int(10) unsigned NOT NULL default '0',
  `custom_signin_url` varchar(255) NOT NULL default '',
  `sso_host` varchar(63) NOT NULL default '',
  `logo_link` varchar(255) NOT NULL default '',
  `auto_update` enum('N','Y') NOT NULL default 'N',
  `site_level` enum('','dev','beta') NOT NULL default '',
  `mobile` set('','iPhone','BlackBerry') NOT NULL default '',
  `mobile_app` text NOT NULL default '',
  `send_report` enum('N','Y') NOT NULL default 'Y',
  `share_it` enum('N','Y') NOT NULL default 'Y',
  `enable_licensekey` enum('N','SITE','USER') NOT NULL default 'N',
  `smtp_server` varchar(63) NOT NULL default '',
  `smtp_user` varchar(63) NOT NULL default '',
  `smtp_password` varchar(31) NOT NULL default '',
  `rec_download` enum('N','Y') NOT NULL DEFAULT 'Y',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_comment`
--

CREATE TABLE IF NOT EXISTS `wc2_comment` (
  `id` int(11) NOT NULL auto_increment,
  `text` text NOT NULL default '',
  `meeting_id` int(10) unsigned NOT NULL default '0',
  `host_id` int(10) unsigned NOT NULL default '0',
  `author_id` int(10) unsigned NOT NULL default '0',
  `full_name` varchar(63) NOT NULL default '',
  `email` varchar(63) NOT NULL default '',
  `public` enum('N','Y') NOT NULL default 'N',
  `post_time` datetime NOT NULL default '1961-01-01 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_content`
--

CREATE TABLE IF NOT EXISTS `wc2_content` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` tinytext NOT NULL default '',
  `description` text NOT NULL default '',
  `keywords` tinytext NOT NULL default '',
  `owner_id` int(10) unsigned NOT NULL default '0',
  `file_name` varchar(63) NOT NULL default '',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `create_date` datetime NOT NULL default '1961-01-01 00:00:00',
  `storageserver_id` smallint(5) unsigned NOT NULL default '0',
  `content_id` varchar(32) NOT NULL default '',
  `type` enum('JPG','SWF','MP3','FLV','PPT') NOT NULL default 'JPG',
  `slide_titles` text NOT NULL default '',
  `slide_files` text NOT NULL default '',
  `slide_thumbs` text NOT NULL default '',
  `width` smallint(5) unsigned NOT NULL default '0',
  `height` smallint(5) unsigned NOT NULL default '0',
  `thumb_file` varchar(63) NOT NULL default '',
  `author_name` varchar(31) NOT NULL default '',
  `copyright` varchar(63) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`),
  KEY `owner_id` (`owner_id`),
  KEY `storageserver_id` (`storageserver_id`),
  KEY `content_id` (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wc2_folder`
-- 

CREATE TABLE IF NOT EXISTS `wc2_folder` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(31) NOT NULL default '',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `owner_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`),
  KEY `owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_group`
--

CREATE TABLE IF NOT EXISTS `wc2_group` (
  `id` int(11) NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(31) NOT NULL default '',
  `description` text NOT NULL default '',
  `webserver_id` smallint(5) unsigned NOT NULL default '0',
  `videoserver_id` smallint(5) unsigned NOT NULL default '0',
  `remoteserver_id` smallint(5) unsigned NOT NULL default '0',
  `teleserver_id` smallint(6) NOT NULL default '0',
  `webserver2_id` smallint(5) unsigned NOT NULL default '0',
  `videoserver2_id` smallint(5) unsigned NOT NULL default '0',
  `remoteserver2_id` smallint(5) unsigned NOT NULL default '0',
  `free_audio_conf` enum('N','Y') NOT NULL default 'Y',
  `storageserver_id` smallint(5) unsigned NOT NULL default '0',
  `storageserver2_id` smallint(5) unsigned NOT NULL default '0',
  `conversionserver_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`),
  KEY `webserver_id` (`webserver_id`),
  KEY `videoserver_id` (`videoserver_id`),
  KEY `remoteserver_id` (`remoteserver_id`),
  KEY `teleserver_id` (`teleserver_id`),
  KEY `storageserver_id` (`storageserver_id`),
  KEY `webserver2_id` (`webserver2_id`),
  KEY `conversionserver_id` (`conversionserver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_hook`
--

CREATE TABLE IF NOT EXISTS `wc2_hook` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `add_meeting` varchar(255) NOT NULL default '',
  `meeting_added` varchar(255) NOT NULL default '',
  `delete_meeting` varchar(255) NOT NULL default '',
  `meeting_deleted` varchar(255) NOT NULL default '',
  `set_meeting` varchar(255) NOT NULL default '',
  `meeting_set` varchar(255) NOT NULL default '',
  `start_meeting` varchar(255) NOT NULL default '',
  `meeting_started` varchar(255) NOT NULL default '',
  `end_meeting` varchar(255) NOT NULL default '',
  `meeting_ended` varchar(255) NOT NULL default '',
  `join_meeting` varchar(255) NOT NULL default '',
  `login_meeting` varchar(255) NOT NULL default '',
  `start_recording` varchar(255) NOT NULL default '',
  `recording_started` varchar(255) NOT NULL default '',
  `end_recording` varchar(255) NOT NULL default '',
  `recording_ended` varchar(255) NOT NULL default '',
  `lock_meeting` varchar(255) NOT NULL default '',
  `unlock_meeting` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_image`
--

CREATE TABLE IF NOT EXISTS `wc2_image` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `width` smallint(6) NOT NULL default '0',
  `height` smallint(6) NOT NULL default '0',
  `file_name` varchar(31) NOT NULL default '',
  `author_id` int(10) unsigned NOT NULL default '0',
  `key` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `author_id` (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `image`
--

INSERT INTO `wc2_image` (`id`, `width`, `height`, `file_name`, `author_id`, `key`) VALUES
(1, 0, 0, 'default_logo.jpg', 0, ''),
(2, 0, 0, 'default_banner.jpg', 0, ''),
(3, 0, 0, 'conference_room_on.jpg', 0, ''),
(4, 0, 0, 'conference_room_off.jpg', 0, ''),
(5, 0, 0, 'auditorium_on.jpg', 0, ''),
(6, 0, 0, 'auditorium_off.jpg', 0, ''),
(7, 0, 0, 'office_bay_on.jpg', 0, ''),
(8, 0, 0, 'office_bay_off.jpg', 0, ''),
(9, 0, 0, 'office_beach_on.jpg', 0, ''),
(10, 0, 0, 'office_beach_off.jpg', 0, ''),
(11, 0, 0, 'office_sunset_on.jpg', 0, ''),
(12, 0, 0, 'office_sunset_off.jpg', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `wc2_license`
--

CREATE TABLE IF NOT EXISTS `wc2_license` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(31) NOT NULL default '',
  `code` varchar(8) NOT NULL default '',
  `max_att` smallint(6) NOT NULL default '0',
  `expiration` smallint(6) NOT NULL default '0',
  `video_conf` enum('N','Y') NOT NULL default 'N',
  `trial` enum('N','Y') NOT NULL default 'N',
  `disk_quota` smallint(5) unsigned NOT NULL default '50',
  `meeting_length` smallint(5) unsigned NOT NULL default '180',
  `payment_id` int(10) unsigned NOT NULL default '0',
  `enabled` enum('N','Y') NOT NULL default 'Y',
  `type` enum('USER','PORT') NOT NULL default 'USER',
  `btn_disabled` set('whiteboard','screen','library','snapshot','file','poll','record','register') NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `license`
--

INSERT INTO `wc2_license` (`id`, `name`, `code`, `max_att`, `expiration`, `video_conf`, `trial`, `disk_quota`, `meeting_length`, `payment_id`, `enabled`, `type`, `btn_disabled`) VALUES
(1, 'TRIAL 1x1', 'TPV1', 2, 0, 'Y', 'Y', 10, 30, 0, 'Y', 'USER', 'poll,record,register'),
(2, 'PRO-10', 'P10', 10, 0, 'N', 'N', 100, 180, 0, 'Y', 'USER', 'poll,register'),
(3, 'PRO-10 AV', 'PV10', 10, 0, 'Y', 'N', 100, 180, 0, 'Y', 'USER', 'poll,register'),
(4, 'PRO-25', 'P25', 25, 0, 'N', 'N', 250, 180, 0, 'Y', 'USER', 'poll,register'),
(5, 'PRO-25 AV', 'PV25', 25, 0, 'Y', 'N', 250, 180, 0, 'Y', 'USER', 'poll,register'),
(6, 'PRO-100', 'P100', 100, 0, 'N', 'N', 1000, 180, 0, 'Y', 'USER', ''),
(7, 'PRO-100 AV', 'PV100', 100, 0, 'Y', 'N', 1000, 180, 0, 'Y', 'USER', ''),
(8, 'PRO-250', 'P250', 250, 0, 'N', 'N', 1000, 180, 0, 'Y', 'USER', ''),
(9, 'PRO-250 AV', 'PV250', 250, 0, 'Y', 'N', 1000, 180, 0, 'Y', 'USER', ''),
(10, 'PRO-500', 'P500', 500, 0, 'N', 'N', 1000, 180, 0, 'Y', 'USER', ''),
(11, 'PRO-500 AV', 'PV500', 500, 0, 'Y', 'N', 1000, 180, 0, 'Y', 'USER', ''),
(12, 'PORT-STD', 'PTS', 0, 0, 'N', 'N', 0, 0, 0, 'Y', 'PORT', ''),
(13, 'PORT-AV', 'PTV', 0, 0, 'Y', 'N', 0, 0, 0, 'Y', 'PORT', '');

-- --------------------------------------------------------

--
-- Table structure for table `wc2_licensekey`
--

CREATE TABLE IF NOT EXISTS `wc2_licensekey` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `license_key` varchar(63) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `provider_id` int(10) unsigned NOT NULL DEFAULT '0',
  `license_text` text NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `license_key` (`license_key`),
  KEY `user_id` (`user_id`),
  KEY `provider_id` (`provider_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_mailtemplate`
--

CREATE TABLE `wc2_mailtemplate` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(31) NOT NULL default '',
  `subject` varchar(31) NOT NULL default '',
  `body_text` text NOT NULL,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `author_id` int(11) NOT NULL default '0',
  `type` enum('ADD','TRIAL','CANCEL','REGISTER','INVITE','INVITE_PLAY','PASSWORD','EDIT','REPORT','CUSTOM') NOT NULL default 'ADD',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table `wc2_mailtemplate`
-- 

INSERT INTO `wc2_mailtemplate` (`id`, `name`, `subject`, `body_text`, `brand_id`, `author_id`, `type`) VALUES 
(1, 'MT_ADD_MEMBER', 'MT_ADD_MEMBER_SUBJECT', 'MT_DEAR_NAME,\r\n\r\nMT_SIGNUP_INFO:\r\n\r\nMT_URL: [LOGIN_URL]\r\nMT_LOGIN: [LOGIN]\r\nMT_PASSWORD: [PASSWORD]\r\n\r\nMT_IPHONE_ONLY\r\nMT_IPHONE_APP_DOWNLOAD\r\n[IPHONE_DOWNLOAD_URL]', 0, 0, 'ADD'),
(2, 'MT_SEND_PWD', 'MT_SEND_PWD_SUBJECT', 'MT_DEAR_NAME,\r\n\r\nMT_ACCOUNT_INFO:\r\n\r\nMT_LOGIN: [LOGIN]\r\nMT_PASSWORD: [PASSWORD]\r\n\r\n', 0, 0, 'PASSWORD'),
(3, 'MT_REGISTER', 'MT_REGISTER_SUBJECT', 'MT_DEAR_NAME,\r\n\r\nMT_REGISTER_INFO:\r\n\r\nMT_MEETING_TITLE: [MEETING_TITLE]\r\nMT_URL: [MEETING_URL]\r\nMT_LOGIN: [REGISTERED_EMAIL]\r\nMT_DATE: [MEETING_DATE]\r\nMT_TIME: [MEETING_TIME]\r\n\r\nMT_IPHONE_ONLY\r\nMT_IPHONE_JOIN\r\n[IPHONE_MEETING_URL]\r\n\r\nMT_IPHONE_APP_DOWNLOAD\r\n[IPHONE_DOWNLOAD_URL]', 0, 0, 'REGISTER'),
(4, 'MT_EDIT_MEMBER', 'MT_EDIT_MEMBER_SUBJECT', 'MT_DEAR_NAME,\r\n\r\nMT_EDIT_INFO:\r\n\r\nMT_URL: [LOGIN_URL]\r\nMT_LOGIN: [LOGIN]\r\nMT_PASSWORD: [PASSWORD]\r\n', 0, 0, 'EDIT'),
(5, 'MT_INVITE', '', 'MT_JOIN_MEETING\r\n\r\nMT_URL: [MEETING_URL]\r\nMT_PHONE: [MEETING_PHONE]\r\nMT_PASSWORD: [MEETING_PASSWORD]\r\nMT_DATE: [MEETING_DATE]\r\nMT_TIME: [MEETING_TIME]\r\n\r\nMT_IPHONE_ONLY\r\nMT_IPHONE_JOIN\r\n[IPHONE_MEETING_URL]\r\n\r\nMT_IPHONE_APP_DOWNLOAD\r\n[IPHONE_DOWNLOAD_URL]', 0, 0, 'INVITE'),
(6, 'MT_INVITE_PLAY', '', 'MT_PLAY_RECORDING\r\n\r\nMT_URL: [MEETING_URL]\r\n\r\n', 0, 0, 'INVITE_PLAY'),
(7, 'MT_REPORT', '', '<html><head>\r\n<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">\r\n<style>\r\n<!--\r\nbody { margin: 5px; padding: 0px; font: 12px arial, helvetica, sans-serif; }\r\n.heading1 { margin-top: 10px; margin-bottom: 5px; font: bold 14px arial, helvetica; padding-bottom:3px; color: #454545; }\r\n.div {font: 14px arial, helvetica; }\r\n\r\nul {\r\n	margin: 0;\r\n	padding: 0;\r\n	list-style:none;\r\n	font-size:14px;\r\n}\r\n\r\nul > li {\r\n	margin: 0;\r\n	padding:5px 0 2px 0px;\r\n}\r\n\r\n.meeting_list { margin: 5px 0 0 0; font-size: 100%; width: 100%; padding-top: 0px;}\r\n.meeting_list tr { vertical-align: top; }\r\n.meeting_list th { font-size: 90%; padding: 5px 7px; background-color: #39c ; color: #fff; }\r\n\r\n.meeting_list th.tl { background-color: #39c; }\r\n.meeting_list th.tr { background-color: #39c; }\r\n.meeting_list .pipe { border-right: 1px solid #fff; }\r\n.meeting_list td { border-bottom: 1px solid #ccc;}\r\n.meeting_list td.m_id { padding: 5px 5px 0px 0px;}\r\n\r\n#meeting_stat { text-align: right; }\r\n\r\n.u_name { padding: 5px 5px 5px 0px;}\r\n\r\n.u_item { padding: 5px 5px 5px 5px;}\r\n.u_item_c { text-align: center; padding: 5px 5px 5px 5px;}\r\n.m_caption { line-height: 1.3em; padding: 2px 10px 0 10px; font-size: 80%; }\r\n-->\r\n</style>\r\n</head>\r\n<body>\r\n<ul>\r\n<li><b>Session ID:</b> [SESSION_ID]</li>\r\n<li><b>Meeting:</b> [MEETING_ID] [MEETING_TITLE]</li>\r\n<li><b>Start time:</b> [START_TIME]</li>\r\n</ul>\r\n\r\n[ATTENDEES]\r\n\r\n<div class=''heading1''>Transcripts</div>\r\n[TRANSCRIPTS]\r\n<div class=''m_caption''>*Time is measured from the start of the meeting.</div>\r\n</body>\r\n</html>', 0, 0, 'REPORT'),
(8, 'MT_REPORT2', '', '<html><head>\r\n<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">\r\n<style>\r\n<!--\r\nbody { margin: 5px; padding: 0px; font: 12px arial, helvetica, sans-serif; }\r\n.heading1 { margin-top: 10px; margin-bottom: 5px; font: bold 15px arial, helvetica; color:#454545 }\r\n.heading3 { margin-top: 10px; margin-bottom: 5px; font-size:13px; font-weight: bold;}\r\n.div {font: 14px arial, helvetica; margin: 0px; padding: 0px; min-height: 0px;}\r\n\r\nul {\r\n	margin: 0;\r\n	padding: 0;\r\n	list-style:none;\r\n	font-size:14px;\r\n}\r\n\r\nul > li {\r\n	margin: 0;\r\n	padding:5px 0 2px 0px;\r\n}\r\n\r\n\r\n.meeting_list { margin: 5px 0 0 0; font-size: 100%; width: 100%; padding-top: 0px;}\r\n.meeting_list tr { vertical-align: top; }\r\n.meeting_list th { font-size: 90%; padding: 5px 7px; background-color: #39c ; color: #fff; }\r\n\r\n.meeting_list .pipe { border-right: 1px solid #fff; }\r\n.meeting_list td { border-bottom: 1px solid #ccc;}\r\n.meeting_list td.m_id { padding: 5px 5px 0px 0px;}\r\n\r\n#meeting_stat { text-align: right; }\r\n\r\n.u_item { padding: 5px 5px 5px 5px;}\r\n.u_item_c { text-align: center; padding: 5px 5px 5px 5px;}\r\n.u_bg { background-color:#f0f0f0}\r\n.m_caption { line-height: 1.3em; padding: 2px 10px 0 10px; font-size: 80%; }\r\n.poll_tb { width: 100px; border-collapse:collapse; border: 1px solid #999; line-height: 10px;}\r\n.poll_bg { background-color:#f0f0f0;}\r\n.poll_bar { background-color:#33cc33;}\r\n-->\r\n</style>\r\n</head>\r\n<body>\r\n<ul>\r\n<li><b>Session ID:</b> [SESSION_ID]</li>\r\n<li><b>Meeting:</b> [MEETING_ID] [MEETING_TITLE]</li>\r\n<li><b>Start time:</b> [START_TIME]</li>\r\n</ul>\r\n[ATTENDEES]\r\n\r\n[TRANSCRIPTS]\r\n\r\n[POLLS]\r\n\r\n</body>\r\n</html>', 0, 0, 'REPORT');
-- --------------------------------------------------------

--
-- Table structure for table `wc2_media`
--

CREATE TABLE IF NOT EXISTS `wc2_media` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` tinytext NOT NULL default '',
  `description` text NOT NULL default '',
  `type` enum('FLV','MP3') NOT NULL default 'FLV',
  `length` time NOT NULL default '00:00:00',
  `keyword` tinytext NOT NULL default '',
  `publish` enum('','A','G') NOT NULL default '',
  `author_id` int(10) unsigned NOT NULL default '0',
  `ready` enum('N','Y') NOT NULL default 'N',
  `file_name` varchar(31) NOT NULL default '',
  `library_id` int(10) unsigned NOT NULL default '0',
  `create_date` datetime NOT NULL default '1961-01-01 00:00:00',
  `storageserver_id` smallint(5) unsigned NOT NULL default '0',
  `content_id` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_meeting`
--

CREATE TABLE IF NOT EXISTS `wc2_meeting` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `access_id` int(10) unsigned NOT NULL default '0',
  `folder_id` int(10) unsigned NOT NULL default '0',
  `status` enum('STOP','START','REC','LOCK','START_REC') NOT NULL default 'STOP',
  `login_type` enum('NAME','PWD','REGIS','NONE') NOT NULL default 'NAME',
  `meeting_type` enum('NORMAL','OPEN','PANEL') NOT NULL default 'NORMAL',
  `password` varchar(8) NOT NULL default '',
  `locked` enum('N','Y') NOT NULL default 'N',
  `title` tinytext NOT NULL default '',
  `description` text NOT NULL default '',
  `image_id` int(10) unsigned NOT NULL default '0',
  `host_id` int(10) unsigned NOT NULL default '0',
  `scheduled` enum('N','Y') NOT NULL default 'N',
  `date_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `duration` time NOT NULL default '00:00:00',
  `close_register` enum('N','Y') NOT NULL default 'N',
  `can_rate` enum('N','Y') NOT NULL default 'N',
  `rating` tinyint(4) NOT NULL default '0',
  `can_comment` enum('N','Y') NOT NULL default 'N',
  `payment_id` int(10) unsigned NOT NULL default '0',
  `price` float NOT NULL default '0',
  `currency` tinyint(4) NOT NULL default '0',
  `public` enum('N','Y','S') NOT NULL default 'N',
  `keyword` tinytext NOT NULL default '',
  `audio` enum('N','Y') NOT NULL default 'N',
  `webserver_id` smallint(5) unsigned NOT NULL default '0',
  `storageserver_id` smallint(5) unsigned NOT NULL default '0',
  `tele_conf` enum('N','Y') NOT NULL default 'N',
  `tele_num` varchar(31) NOT NULL default '',
  `tele_num2` varchar(31) NOT NULL default '',
  `tele_mcode` varchar(15) NOT NULL default '',
  `tele_pcode` varchar(15) NOT NULL default '',
  `session_id` int(10) unsigned NOT NULL default '0',
  `regform_id` int(10) unsigned NOT NULL default '0',
  `public_comment` enum('N','Y') NOT NULL default 'Y',
  `client_data` varchar(63) NOT NULL default '',
  `rec_event_id` int(11) NOT NULL default '0',
  `audio_rec_id` varchar(8) NOT NULL default '',
  `audio_sync_time` tinyint(4) NOT NULL default '0',
  `rec_ready` enum('N','Y') NOT NULL default 'N',
  `can_download` enum('N','Y') NOT NULL default 'N',
  `can_download_rec` enum('N','Y') NOT NULL default 'N',
  `ending_rec` enum('N','Y') NOT NULL default 'N',
  `send_report` enum('N','Y') NOT NULL default 'Y',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `access_id` (`access_id`),
  KEY `brand_id` (`brand_id`),
  KEY `host_id` (`host_id`),
  KEY `session_id` (`session_id`),
  KEY `status` (`status`),
  KEY `folder_id` (`folder_id`),
  KEY `login_type` (`login_type`),
  KEY `meeting_type` (`meeting_type`),
  KEY `scheduled` (`scheduled`),
  KEY `regform_id` (`regform_id`),
  KEY `date_time` (`date_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_provider`
--

CREATE TABLE IF NOT EXISTS `wc2_provider` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `create_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `login` varchar(15) NOT NULL default '',
  `account_id` varchar(32) NOT NULL default '',
  `license` varchar(255) NOT NULL default '',
  `password` varchar(8) NOT NULL default '',
  `first_name` varchar(31) NOT NULL default '',
  `last_name` varchar(31) NOT NULL default '',
  `company_name` varchar(63) NOT NULL default '',
  `street` varchar(63) NOT NULL default '',
  `city` varchar(63) NOT NULL default '',
  `state` varchar(63) NOT NULL default '',
  `zip` varchar(15) NOT NULL default '',
  `country` varchar(63) NOT NULL default '',
  `phone` varchar(31) NOT NULL default '',
  `admin_email` varchar(63) NOT NULL default '',
  `status` enum('ACTIVE','INACTIVE','DEMO') NOT NULL default 'ACTIVE',
  `max_sites` smallint(5) unsigned NOT NULL default '0',
  `licensekey_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `account_id` (`account_id`),
  KEY `licensekey_id` (`licensekey_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_question`
--

CREATE TABLE IF NOT EXISTS `wc2_question` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` enum('S','M','T') NOT NULL default 'S',
  `question` tinytext NOT NULL default '',
  `choice_1` tinytext NOT NULL default '',
  `choice_2` tinytext NOT NULL default '',
  `choice_3` tinytext NOT NULL default '',
  `choice_4` tinytext NOT NULL default '',
  `choice_5` tinytext NOT NULL default '',
  `correct` tinyint(4) NOT NULL default '0',
  `quiz_id` int(10) unsigned NOT NULL default '0',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `order` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_quiz`
--

CREATE TABLE IF NOT EXISTS `wc2_quiz` (
  `id` int(11) NOT NULL auto_increment,
  `title` tinytext NOT NULL default '',
  `description` text NOT NULL default '',
  `show_answer` enum('N','Y') NOT NULL default 'N',
  `author_id` int(10) unsigned NOT NULL default '0',
  `publish` enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_regform`
--

CREATE TABLE IF NOT EXISTS `wc2_regform` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(63) NOT NULL,
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `key_1` varchar(255) NOT NULL DEFAULT '[TITLE]',
  `key_2` varchar(255) NOT NULL DEFAULT '[ORG]',
  `key_3` varchar(255) NOT NULL DEFAULT '[STREET]',
  `key_4` varchar(255) NOT NULL DEFAULT '[CITY]',
  `key_5` varchar(255) NOT NULL DEFAULT '[STATE]',
  `key_6` varchar(255) NOT NULL DEFAULT '[ZIP]',
  `key_7` varchar(255) NOT NULL DEFAULT '[COUNTRY]',
  `key_8` varchar(255) NOT NULL DEFAULT '[PHONE]',
  `key_9` varchar(255) NOT NULL DEFAULT '',
  `key_10` varchar(255) NOT NULL DEFAULT '',
  `key_11` varchar(255) NOT NULL DEFAULT '',
  `key_12` varchar(255) NOT NULL DEFAULT '',
  `key_13` varchar(255) NOT NULL DEFAULT '',
  `key_14` varchar(255) NOT NULL DEFAULT '',
  `key_15` varchar(255) NOT NULL DEFAULT '',
  `key_16` varchar(255) NOT NULL DEFAULT '',
  `required_fields` set('key_1','key_2','key_3','key_4','key_5','key_6','key_7','key_8','key_9','key_10','key_11','key_12','key_13','key_14','key_15','key_16') NOT NULL DEFAULT '',
  `auto_reply` enum('N','Y') NOT NULL default 'Y',
  `custom_reply` text NOT NULL DEFAULT '',
  `auto_reminder` tinyint(4) unsigned NOT NULL default '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `auto_reminder` (`auto_reminder`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;



-- --------------------------------------------------------

--
-- Table structure for table `wc2_registration`
--

CREATE TABLE IF NOT EXISTS `wc2_registration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(63) NOT NULL DEFAULT '',
  `name` varchar(63) NOT NULL,
  `meeting_id` int(10) unsigned NOT NULL DEFAULT '0',
  `regform_id` int(10) unsigned NOT NULL DEFAULT '0',
  `field_1` varchar(255) NOT NULL DEFAULT '',
  `field_2` varchar(255) NOT NULL DEFAULT '',
  `field_3` varchar(255) NOT NULL DEFAULT '',
  `field_4` varchar(255) NOT NULL DEFAULT '',
  `field_5` varchar(255) NOT NULL DEFAULT '',
  `field_6` varchar(255) NOT NULL DEFAULT '',
  `field_7` varchar(255) NOT NULL DEFAULT '',
  `field_8` varchar(255) NOT NULL DEFAULT '',
  `field_9` varchar(255) NOT NULL DEFAULT '',
  `field_10` varchar(255) NOT NULL DEFAULT '',
  `field_11` varchar(255) NOT NULL DEFAULT '',
  `field_12` varchar(255) NOT NULL DEFAULT '',
  `field_13` varchar(255) NOT NULL DEFAULT '',
  `field_14` varchar(255) NOT NULL DEFAULT '',
  `field_15` varchar(255) NOT NULL DEFAULT '',
  `field_16` varchar(255) NOT NULL DEFAULT '',
  `date_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notice_time` timestamp NOT NULL DEFAULT '1970-01-01 00:00:01',
  PRIMARY KEY  (`id`),
  KEY `email` (`email`),
  KEY `meeting_id` (`meeting_id`),
  KEY `regform_id` (`regform_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_remoteserver`
--

CREATE TABLE IF NOT EXISTS `wc2_remoteserver` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(31) NOT NULL default '',
  `server_url` varchar(255) NOT NULL default '',
  `client_url` varchar(255) NOT NULL default '',
  `password` varchar(15) NOT NULL default '',
  `aws_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_session`
--

CREATE TABLE IF NOT EXISTS `wc2_session` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `meeting_aid` int(10) unsigned NOT NULL default '0',
  `meeting_title` varchar(31) NOT NULL default '',
  `mod_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `start_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `host_login` varchar(63) NOT NULL default '',
  `max_concur_att` smallint(5) unsigned NOT NULL default '0',
  `license_code` varchar(8) NOT NULL default '',
  `client_data` varchar(63) NOT NULL default '',
  `transcripts` text NOT NULL default '',
  `poll_results` text NOT NULL default '',
  `poll_questions` text NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`),
  KEY `meeting_aid` (`meeting_aid`),
  KEY `start_time` (`start_time`),
  KEY `mod_time` (`mod_time`),
  KEY `license_code` (`license_code`),
  KEY `host_login` (`host_login`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `wc2_storageserver`
--

CREATE TABLE IF NOT EXISTS `wc2_storageserver` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(31) NOT NULL default '',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `url` varchar(255) NOT NULL default '',
  `access_code` varchar(32) NOT NULL default '',
  `installed_version` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
-- --------------------------------------------------------

--
-- Table structure for table `wc2_conversionserver`
--

CREATE TABLE IF NOT EXISTS `wc2_conversionserver` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(63) NOT NULL default '',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `url` varchar(255) NOT NULL default '',
  `ssl_url` varchar(255) NOT NULL default '',
  `access_key` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `wc2_teleserver`
--

CREATE TABLE IF NOT EXISTS `wc2_teleserver` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(31) NOT NULL default '',
  `getconf_url` varchar(255) NOT NULL default '',
  `getconf_login` varchar(31) NOT NULL default '',
  `getconf_password` varchar(31) NOT NULL default '',
  `can_getconf` enum('N','Y') NOT NULL default 'N',
  `rec_sync_time` tinyint(4) NOT NULL default '0',
  `can_record` enum('N','Y') NOT NULL default 'N',
  `server_url` varchar(255) NOT NULL default '',
  `access_key` varchar(63) NOT NULL default '',
  `can_dialout` enum('N','Y') NOT NULL default 'N',
  `can_control` enum('N','Y') NOT NULL default 'Y',
  `dial_tollfree_only` enum('N','Y') NOT NULL default 'Y',
  `rec_ext_conf` enum('N','Y') NOT NULL default 'N' COMMENT 'record an external conference dial in from another bridge',
  `ext_conf_url` varchar(255) NOT NULL default '',
  `can_hangup_all` enum('N','Y') NOT NULL default 'N',
  `can_dial_host` enum('N','Y') NOT NULL default 'N',
  `can_modify` enum('N','Y') NOT NULL default 'Y',
  `show_active_talker` enum('N','Y') NOT NULL default 'N',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wc2_token`
-- 

CREATE TABLE `wc2_token` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `brand` varchar(31) NOT NULL default '',
  `user_id` int(10) unsigned NOT NULL default '0',
  `meeting_id` int(10) unsigned NOT NULL default '0',
  `token` varchar(32) character set utf8 NOT NULL default '',
  `create_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `permission` varchar(15) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_user`
--

CREATE TABLE IF NOT EXISTS `wc2_user` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `create_date` datetime NOT NULL default '1961-01-01 00:00:00',
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `login` varchar(63) NOT NULL default '',
  `password` varchar(8) NOT NULL default '',
  `access_id` int(10) unsigned NOT NULL default '0',
  `first_name` varchar(31) NOT NULL default '',
  `last_name` varchar(31) NOT NULL default '',
  `title` varchar(63) NOT NULL default '',
  `org` varchar(63) NOT NULL default '',
  `street` varchar(63) NOT NULL default '',
  `city` varchar(63) NOT NULL default '',
  `state` varchar(63) NOT NULL default '',
  `country` varchar(63) NOT NULL default '',
  `zip` varchar(15) NOT NULL default '',
  `email` varchar(63) NOT NULL default '',
  `phone` varchar(31) NOT NULL default '',
  `mobile` varchar(15) NOT NULL default '',
  `fax` varchar(15) NOT NULL default '',
  `tele_num` varchar(31) NOT NULL default '',
  `tele_mcode` varchar(15) NOT NULL default '',
  `tele_pcode` varchar(15) NOT NULL default '',
  `free_conf` enum('N','Y') NOT NULL default 'N',
  `pict_id` int(10) unsigned NOT NULL default '0',
  `viewer_id` int(10) unsigned NOT NULL default '0',
  `permission` enum('HOST','ADMIN','OPERATOR') NOT NULL default 'HOST',
  `group_id` int(10) unsigned NOT NULL default '0',
  `active` enum('N','Y') NOT NULL default 'Y',
  `webserver_id` smallint(5) unsigned NOT NULL default '0',
  `videoserver_id` smallint(5) unsigned NOT NULL default '0',
  `remoteserver_id` smallint(5) unsigned NOT NULL default '0',
  `meeting_id` int(10) unsigned NOT NULL default '0',
  `room_name` varchar(31) NOT NULL default '',
  `room_description` tinytext NOT NULL default '',
  `public` enum('N','Y') NOT NULL default 'N',
  `license_id` smallint(5) unsigned NOT NULL default '0',
  `show_pict` enum('N','Y') NOT NULL default 'N',
  `time_zone` varchar(6) NOT NULL default '',
  `public_comment` enum('N','Y') NOT NULL default 'Y',
  `login_session_id` varchar(255) NOT NULL default '',
  `login_start_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `login_mod_time` datetime NOT NULL default '1961-01-01 00:00:00',
  `conf_num` varchar(31) NOT NULL default '',
  `conf_num2` varchar(31) NOT NULL default '',
  `conf_mcode` varchar(15) NOT NULL default '',
  `conf_pcode` varchar(15) NOT NULL default '',
  `use_teleserver` enum('N','Y') NOT NULL default 'N' COMMENT 'use the teleserver of the group to control the conf number',
  `logo_id` int(10) unsigned NOT NULL default '0',
  `licensekey_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `access_id` (`access_id`),
  KEY `login` (`login`),
  KEY `brand_id` (`brand_id`),
  KEY `license_id` (`license_id`),
  KEY `permission` (`permission`),
  KEY `group_id` (`group_id`),
  KEY `licensekey_id` (`licensekey_id`),
  KEY `create_date` (`create_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_version`
--

CREATE TABLE IF NOT EXISTS `wc2_version` (
  `id` tinyint(3) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `number` varchar(15) NOT NULL default '',
  `rollback_number` varchar(15) NOT NULL default '',
  `source_url` varchar(255) NOT NULL default '',
  `ssl_source_url` varchar(255) NOT NULL default '',
  `date` datetime NOT NULL default '1961-01-01 00:00:00',
  `type` enum('dev','beta','final') NOT NULL default 'dev',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_videoserver`
--

CREATE TABLE IF NOT EXISTS `wc2_videoserver` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(31) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `bandwidth` int(11) NOT NULL default '0',
  `width` smallint(6) NOT NULL default '0',
  `height` smallint(6) NOT NULL default '0',
  `max_wind` tinyint(4) NOT NULL default '0',
  `type` enum('BOTH','AUDIO','VIDEO') NOT NULL default 'BOTH',
  `aws_id` int(10) unsigned NOT NULL default '0',
  `audio_rate` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_viewer`
--

CREATE TABLE IF NOT EXISTS `wc2_viewer` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `logo_id` int(10) unsigned NOT NULL default '0',
  `back_id` int(10) unsigned NOT NULL default '0',
  `att_snd` enum('N','Y') NOT NULL default 'Y',
  `msg_snd` enum('N','Y') NOT NULL default 'Y',
  `hand_snd` enum('N','Y') NOT NULL default 'Y',
  `waitmusic_url` varchar(255) NOT NULL default '',
  `see_all` enum('N','Y') NOT NULL default 'Y',
  `send_all` enum('N','Y') NOT NULL default 'Y',
  `end_url` varchar(255) NOT NULL default '',
  `presenter_client` enum('','JAVA','WINDOWS') NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `wc2_webserver`
--

CREATE TABLE IF NOT EXISTS `wc2_webserver` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `brand_id` smallint(5) unsigned NOT NULL default '0',
  `name` varchar(63) NOT NULL default '',
  `login` varchar(31) NOT NULL default '',
  `password` varchar(31) NOT NULL default '',
  `php_ext` enum('php','php4','php5') NOT NULL default 'php',
  `def_page` enum('index.htm','index.html') NOT NULL default 'index.htm',
  `file_perm` enum('','777','755') NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `path` varchar(255) NOT NULL default '',
  `auto_update` enum('N','Y') NOT NULL default 'Y',
  `aws_id` int(10) unsigned NOT NULL default '0',
  `installed_version` varchar(15) NOT NULL default '',
  `slave_ids` varchar(255) NOT NULL DEFAULT '',
  `max_connections` smallint(6) NOT NULL DEFAULT '100',
  PRIMARY KEY  (`id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
