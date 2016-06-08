<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

$gText=array();
$gText['HOME_TAB']=_Text("Home");
$gText['MEETINGS_TAB']=_Text("My Meetings");
$gText['ACCOUNT_TAB']=_Text("My Account");
$gText['ADMIN_TAB']=_Text("Administration");
$gText['CUSTOM_TAB']=_Text("Custom");

$gText['HOME_MEETINGS']=_Text("Meetings");
$gText['HOME_RECORDINGS']=_Text("Recordings");
$gText['HOME_ROOMS']=_Text("Meeting Rooms");

$gText['MEETINGS_ROOM']=_Text("Meeting Room");
$gText['MEETINGS_VIEWER']=_Text("Meeting Viewer");
$gText['MEETINGS_REGIST']=_Text("Registrations");

$gText['LIBRARY_PRES']=_Text("Presentations");
$gText['LIBRARY_PICT']=_Text("Pictures");
$gText['LIBRARY_MEDIA']=_Text("Media");
$gText['LIBRARY_QUIZ']=_Text("Quiz");

$gText['ACCOUNT_PROFILE']=_Text("My Profile");
$gText['ACCOUNT_PASSWORD']=_Text("Password");

$gText['ADMIN_USERS']=_Text("Members");
$gText['ADMIN_GROUPS']=_Text("Groups");
$gText['ADMIN_MEETINGS']=_Text("Meetings");
$gText['ADMIN_SITE']=_Text("Website");
$gText['ADMIN_WEB']=_Text("Web Conference");
$gText['ADMIN_VIDEO']=_Text("Video Conference");
$gText['ADMIN_REPORT']=_Text("Reports");
$gText['ADMIN_REMOTE']=_Text("Remote Control");
$gText['ADMIN_STORAGE']=_Text("Storage Server");

$gText['TITLE_SIGNIN']=_Text("Sign In");
$gText['TITLE_SIGNOUT']=_Text("Sign Out");
$gText['TITLE_HELP']=_Text("Help");
$gText['TEXT_WELCOME']=_Text("Welcome,");
$gText['M_SIGNIN_PAGE']=_Text("Member Sign In");

$gText['M_TODAY_MEETING']=_Text("Today's Meetings");
$gText['M_MEETINGS']=_Text("Meetings");
$gText['M_RECORDINGS']=_Text("Recordings");
$gText['M_HOSTING']=_Text("Hosting");
$gText['M_AUDIO_CONF']=_Text("Teleconference");
$gText['M_FOOTER']=_Text("Footer");
$gText['M_HOME_PAGE']=_Text("Home Page");
$gText['M_VIEWER']=_Text("Viewer");
$gText['M_ACCOUNTS']=_Text("Accounts");
$gText['M_REPORTS']=_Text("Reports");
$gText['M_COMMENTS']=_Text("Comments");
$gText['M_INFORMATION']=_Text("Information");
$gText['M_ACCOUNT_TYPE']=_Text("Account type");
$gText['M_STORAGE_SPACE']=_Text("Storage space");
$gText['M_ACCOUNT']=_Text("Account");
$gText['M_PERMISSION']=_Text("Permission");
$gText['M_SPEED_TEST']=_Text("Speed Test");
$gText['M_RECORD']=_Text("Record");

$gText['M_MY_MEETING']=_Text("My Meeting");
$gText['M_FULLNAME']=_Text("Full name");
$gText['M_FIRSTNAME']=_Text("First name");
$gText['M_LASTNAME']=_Text("Last name");
$gText['M_ID']=_Text("ID");
$gText['M_TITLE']=_Text("Title");
$gText['M_COMPANY']=_Text("Company");
$gText['M_ORG']=_Text("Organization");
$gText['M_CUSTOM']=_Text("[Custom]");
$gText['M_TIME']=_Text("Time");
$gText['M_DATE']=_Text("Date");
$gText['M_LENGTH']=_Text("Length");
$gText['M_START_MEETING']=_Text("Start Meeting");
$gText['M_START']=_Text("Start");
$gText['M_RESUME']=_Text("Resume");
$gText['M_END']=_Text("End");
$gText['M_END_MEETING']=_Text("End Meeting");
$gText['M_END_RECORDING']=_Text("End Recording");
$gText['M_PLAYBACK']=_Text("Play");
$gText['M_CLICK_PLAY']=_Text("Click to Play");
$gText['M_INVITE']=_Text("Invite");
$gText['M_DELETE']=_Text("Delete");
$gText['M_JOIN']=_Text("Join");
$gText['M_CLICK_JOIN']=_Text("Click to Join");
$gText['M_REGISTER']=_Text("Register");
$gText['M_REGISTER_FOR']=_Text("Register for");
$gText['M_VIEW']=_Text("View");
$gText['M_SHOW']=_Text("Show");
$gText['M_GO']=_Text("Go");
$gText['M_SEARCH']=_Text("Search");
$gText['M_SELECT_CURRENT']=_Text("Current meetings");
$gText['M_SELECT_INPROGRESS']=_Text("In-progress meetings");
$gText['M_SELECT_TODAY']=_Text("Today's meetings");
$gText['M_SELECT_PAST']=_Text("Past meetings");
$gText['M_DOWNLOAD_VCARD']=_Text("Download vCard");
$gText['M_DOWNLOAD_ICAL']=_Text("Download iCalendar");
$gText['M_ROOM_ADDRESS']=_Text("Meeting room address");
$gText['M_EDIT']=_Text("Edit");
$gText['M_REGISTER_FOR']=_Text("Register for");
$gText['M_EDIT_PROFILE']=_Text("Edit Profile");
$gText['M_IN_PROGRESS']=_Text("In progress");
$gText['M_IDLE']=_Text("Idle");
$gText['M_STREET']=_Text("Street");
$gText['M_CITY']=_Text("City");
$gText['M_STATE']=_Text("State");
$gText['M_COUNTRY']=_Text("Country");
$gText['M_ZIP']=_Text("Zip code");
$gText['M_PHONE']=_Text("Phone");
$gText['M_MOBILE']=_Text("Mobile");
$gText['M_FAX']=_Text("Fax");
$gText['M_EMAIL']=_Text("Email");
$gText['M_ADD_MEMBER']=_Text("Add a Member");
$gText['M_ADD_MEETING']=_Text("Add a Meeting");
$gText['M_SUBMIT_OK']=_Text("Information has been submitted successfully.");
$gText['M_REQUIRED']=_Text("Required fields");
$gText['M_MY_ROOM']=_Text("My Meeting Room");
$gText['M_VIEW_PROFILE']=_Text("View My Profile");
$gText['M_VIEW_ROOM']=_Text("View My Meeting Room");
$gText['M_VIEW_MEETING']=_Text("View Meeting Page");
$gText['M_ADD']=_Text("Add");
$gText['M_EDIT']=_Text("Edit");
$gText['M_Delete']=_Text("Delete");
$gText['M_SELECT_HOST']=_Text("Select a profile");
$gText['M_SELECT_NONE']=_Text("Select 'None' to disable");
$gText['M_ADD_GROUP']=_Text("Add a Group");
$gText['M_ADD_HOSTING']=_Text("Add a Hosting Profile");
$gText['M_ADD_WEB']=_Text("Add a Web Conference Hosting Profile");
$gText['M_ADD_VIDEO']=_Text("Add a Video Conference Hosting Profile");
$gText['M_ADD_REMOTE']=_Text("Add a Remote Control Hosting Profile");
$gText['M_ADD_TELE']=_Text("Add a Teleconference Hosting Profile");
$gText['M_ADD_STORAGE']=_Text("Add a Storage Server Profile");
$gText['M_SET_TZ']=_Text("Set default time zone");
$gText['M_PROFILE']=_Text("Profile");
$gText['M_VIEWER']=_Text("Viewer");
$gText['M_SEND_EMAIL']=_Text("Send Email");
$gText['M_EMAIL_TEMPLATES']=_Text("Email Templates");
$gText['M_PASSWORD_SENT']=_Text("Your password has been sent to the email address under your account.");
$gText['M_CONFIRM_DELETE']=_Text("Do you want to delete %s?");

$gText['M_ALL_MEMBERS']=_Text("All members");
$gText['M_ACTIVE_MEMBERS']=_Text("Active members");
$gText['M_INACTIVE_MEMBERS']=_Text("Inactive members");
$gText['M_ADMIN_MEMBERS']=_Text("Admin members");
$gText['M_ALL_GROUPS']=_Text("All groups");
$gText['M_ALL_ACCOUNTS']=_Text("All accounts");
$gText['M_ALL_SESSIONS']=_Text("All sessions");
$gText['M_ALL_MEETINGS']=_Text("All meetings");
$gText['M_ALL_FOLDERS']=_Text("All folders");
$gText['M_SESSION']=_Text("Session");
$gText['M_MONTH']=_Text("Month");
$gText['M_MEMBER']=_Text("Member");
$gText['M_MEETING']=_Text("Meeting");
$gText['M_REQ_FIELD']=_Text("Required field");
$gText['M_OPT_FIELD']=_Text("Optional field");
$gText['M_FULL_NAME']=_Text("Full name");
$gText['M_GROUP']=_Text("Group");

$gText['M_BACKGROUND']=_Text("Background");
$gText['M_ATTENDEE']=_Text("Attendee");
$gText['M_ATTENDEES']=_Text("Attendees");
$gText['M_SEE_ALL']=_Text("Attendee can see everyone's name");
$gText['M_SEE_HOST']=_Text("Attendee can only see the moderator's name");
$gText['M_SEND_ALL']=_Text("Attendee can send messages to everyone");
$gText['M_SEND_HOST']=_Text("Attendee can only send messages to the moderator");
$gText['M_SHOW_SESSIONS']=_Text("Show meeting sessions for");
$gText['M_SHOW_DETAILS']=_Text("Show details");
$gText['M_SHOW_SUMMARY']=_Text("Show summary");
$gText['M_LICENSE']=_Text("License");
$gText['M_DOWNLOAD']=_Text("Download");
$gText['M_DOWNLOAD_PRESENTER']=_Text("Download Presenter Software");
$gText['M_SEND_EMAIL']=_Text("Send Email");
$gText['M_OPEN_EMAIL']=_Text("Open My Email");
$gText['M_YOUR_NAME']=_Text("Your Name");
$gText['M_COMMENTS']=_Text("Comments");

$gText['M_DEFAULT']=_Text("Default");
$gText['M_SELECT']=_Text("Select");

$gText['M_SORT_BY']=_Text("Sort by");
$gText['M_HOST_NAME']=_Text("Host name");
$gText['M_ROOM_NAME']=_Text("Room name");
$gText['M_ATTENDEES']=_Text("Attendees");
$gText['M_MEETING_HOST_EMAIL']=_Text("Meeting host email");
$gText['M_EDIT_PAGE']=_Text("Edit page");
$gText['M_FORM_NAME']=_Text("Form name");
$gText['M_REG_FORM']=_Text("Registration form");

$gText['MD_MEETING_ID']=_Text("Meeting ID");
$gText['MD_MEETING_URL']=_Text("Meeting URL");
$gText['MD_MEETING_TITLE']=_Text("Meeting Title");
$gText['MD_DATE_TIME']=_Text("Date/Time");
$gText['MD_DESCRIPTION']=_Text("Description");
$gText['MD_LOGIN']=_Text("Login");
$gText['MD_TYPE']=_Text("Type");
$gText['MD_PUBLISH']=_Text("Publish");
$gText['MD_VIEW']=_Text("View");
$gText['MD_UNSCHEDULED']=_Text("Unscheduled");
$gText['MD_UNSCHEDULED_TEXT']=_Text("Start the meeting anytime.");
$gText['MD_SCHEDULED']=_Text("Scheduled");
$gText['MD_SCHEDULED_TEXT']=_Text("Start the meeting on");
$gText['MD_TIME_ZONE']=_Text("Time zone");
$gText['MD_DATE']=_Text("Date");
$gText['MD_TIME']=_Text("Time");
$gText['MD_DURATION']=_Text("Duration");
$gText['MD_NAME']=_Text("Name");
$gText['MD_NAME_TEXT']=_Text("Participants need to enter a name.");
$gText['MD_NAMEPWD']=_Text("Name/Password");
$gText['MD_NAMEPWD_TEXT']=_Text("Participants need to enter a name and a password.");
$gText['MD_REGISTRATION']=_Text("Registration");
$gText['MD_REGISTRATION_TEXT']=_Text("Participants need to register first.");
$gText['MD_PASSWORD']=_Text("Password");
$gText['MD_NONE']=_Text("None");
$gText['MD_PRIVATE']=_Text("Private");
$gText['MD_PRIVATE_TEXT']=_Text("Unlisted.");
$gText['MD_PUBLIC']=_Text("Public");
$gText['MD_PUBLIC_TEXT']=_Text("List in the home page.");
$gText['MD_TELEPHONE']=_Text("Telephone");
$gText['MD_HAS_TELE']=_Text("Use the telephone number");
$gText['MD_PHONE_NUM']=_Text("Phone");
$gText['MD_PHONE_MCODE']=_Text("Moderator code");
$gText['MD_PHONE_PCODE']=_Text("Attendee code");
$gText['MD_PUBLIC_TIP']=_Text("Public");
$gText['MD_SCHEDULED_TIP']=_Text("Scheduled");
$gText['MD_PASSWORD_TIP']=_Text("Password required");
$gText['MD_TELE_TIP']=_Text("Use telephone");
$gText['MD_RECORDED']=_Text("Recorded");
$gText['MD_AUDIO_TIP']=_Text("Has audio");
$gText['MD_HOSTED_BY']=_Text("Hosted by");
$gText['MD_SEND_INVITE']=_Text("Send Invitation for");
$gText['MD_PLAY_REC_TEXT']=_Text("Please play the following recording.");
$gText['MD_JOIN_TEXT']=_Text("Please join the following meeting.");
$gText['MD_INV_OPEN']=_Text("Send from my default email application");
$gText['MD_INV_SEND']=_Text("Send email from this page");
$gText['MD_INV_MSG']=_Text("Message");
$gText['MD_INV_EMAL']=_Text("Email addresses of the person(s) you want to invite:  (separated by a comma)");
$gText['MD_REGISTER_TIP']=_Text("Registration required");

$gText['MT_ADD_MEMBER']=_Text("New member");
$gText['MT_EDIT_MEMBER']=_Text("Member account changed");
$gText['MT_ADD_MEMBER_SUBJECT']=_Text("Member Information");
$gText['MT_EDIT_MEMBER_SUBJECT']=_Text("Account information changed");
$gText['MT_DEAR_NAME']=_Text("Dear [FULL_NAME]"); // do not modify text in [*]
$gText['MT_SIGNUP_INFO']=_Text("Thanks for signing up. Here is your account information");
$gText['MT_EDIT_INFO']=_Text("Your account information has been changed. Please log in to inspect");
$gText['MT_MEETING_TITLE']=_Text("Meeting Title");
$gText['MT_URL']=_Text("URL");
$gText['MT_LOGIN']=_Text("Login");
$gText['MT_PASSWORD']=_Text("Password");
$gText['MT_SEND_PWD']=_Text("Send account password");
$gText['MT_SEND_PWD_SUBJECT']=_Text("Member Information");
$gText['MT_ACCOUNT_INFO']=_Text("Here is your account information");
$gText['MT_REGISTER']=_Text("Registration");
$gText['MT_REGISTER_SUBJECT']=_Text("Registration Info");
$gText['MT_REGISTER_INFO']=_Text("Thanks for signing up. Here is your registration information");

$gText['M_NEW_COMMENT']=_Text("New Comment");
$gText['M_NEW_REGIST']=_Text("New Registration");
$gText['M_NEW_MEMBER']=_Text("New Member");

$gText['M_LICENSE']=_Text("License");
$gText['M_LENGTH']=_Text("Length");
$gText['M_VIDEO_CONF']=_Text("Video conf.");
$gText['M_DISK_QUOTA']=_Text("Disk quota");
$gText['M_TOTAL']=_Text("Total");
$gText['M_ISSUED']=_Text("Issued");
$gText['M_AVAILABLE']=_Text("Available");

$gText['M_SUBMIT']=_Text("Submit");
$gText['M_CANCEL']=_Text("Cancel");
$gText['M_SAVE']=_Text("Save");
$gText['M_DISCARD']=_Text("Discard");
$gText['M_RESET']=_Text("Reset");

$gText['M_TODAY']=_Text("Today");
$gText['M_THIS_MONTH']=_Text("This month");
$gText['M_LAST_MONTH']=_Text("Last month");
$gText['M_ALL_MONTHS']=_Text("All Months");
$gText['M_TOTAL']=_Text("Total");
$gText['M_TRIALS']=_Text("Trials");
$gText['M_MEMBERS']=_Text("Members");
$gText['M_NON_TRIALS']=_Text("Non-trial accounts");
$gText['M_SESSIONS']=_Text("Sessions");
$gText['M_NUM_SESSIONS']=_Text("# of sessions");
$gText['M_NUM_ATTENDEES']=_Text("# of attendees");
$gText['M_TOTAL_ATTENDEE_TIME']=_Text("Total attendee time");
$gText['M_MAX_ATTENDEES']=_Text("Max. session attendees");
$gText['M_CONCUR_ATTENDEES']=_Text("Max. concurrent attendees");
$gText['M_API_DOC']=_Text("REST API Documentation");
$gText['M_UPLOAD_FILE']=_Text("Select a file to upload.");

$gText['REST_INTRO']=_Text("Introduction");
$gText['REST_EXAMPLES']=_Text("Examples");
$gText['REST_OBJECTS']=_Text("Objects");
$gText['REST_HOOKS']=_Text("Hooks");
$gText['REST_ERRORS']=_Text("Errors");
$gText['REST_TOKEN']=_Text("Token Authentication");
$gText['REST_SIGNATURE']=_Text("Signature Authentication");

$gText['M_PARAMETER']=_Text("Parameter");
$gText['M_DESCRIPTION']=_Text("Description");
$gText['M_ERROR_CODE']=_Text("Error code");
$gText['M_ERROR_MESSAGE']=_Text("Error message");
$gText['M_CODE']=_Text("Code");

$gText['M_NO_LIMIT']=_Text("No limit");
$gText['M_NO']=_Text("No");
$gText['M_YES']=_Text("Yes");
$gText['M_NONE']=_Text("None");
$gText['M_OTHERS']=_Text("Others");

$gText['M_MY_LIBRARY']=_Text("My Library");
$gText['M_PUB_LIBRARY']=_Text("Public Library");

$gText['M_SELECT_PROFILE_FOR_GROUP']=_Text("You must select this profile under Administration/Groups for it to be effective.");

$gText['M_ENTER_VAL']=_Text("Please enter a value for the '%s' field.");
$gText['M_ENTER_COMMENT']=_Text("Enter your comments here...");

$gText['M_NO_FLASH_SUP']=_Text("The feature requies Adobe Flash Player and is not supported on this platform.");
?>