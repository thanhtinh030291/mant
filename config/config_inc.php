<?php

$g_crypto_master_salt     = 'OWGtMEpullDzxLUe0ov/cKTrVaNsXZan/qt+FZ3x/mI=';
$g_window_title           = 'Health eTalk';

# --- Database Configuration ---
$g_hostname               = 'localhost';
$g_db_type                = 'mysqli';
$g_database_name          = 'mantis_pcv';
$g_db_username            = 'root';
$g_db_password            = 'Ngonlua@1';

# --- Database Configuration ---
$g_hbs_hostname           = '192.168.148.4';
$g_hbs_port              = '1521';
$g_hbs_database_name      = 'VPROD';
$g_hbs_username           = 'hbs';
$g_hbs_password           = 'colial2005';

# --- Anonymous Access / Signup ---
$g_allow_signup            = OFF;
$g_allow_anonymous_login   = OFF;
$g_anonymous_account       = '';
$g_reauthentication = OFF;
$g_send_reset_password = OFF;

$g_logo_image    = 'images/logo_blue_cross-trans.png';
//$g_show_detailed_errors = ON;

//$g_log_level = LOG_ALL;
//$g_log_destination = 'page';

$g_file_upload_method = DISK;
$g_file_upload_max_num = 10;
$g_attachments_file_permissions = 0755;
$g_max_file_size = 104857600;
$g_allowed_files = 'rtf,doc,docx,xls,xlsx,pdf,jpg,tif,csv,7z,zip,msg,rar,bmp,png';
$g_disallowed_files = 'php,php3,phtml,html,class,java,exe,pl';

$g_absolute_path_default_upload_folder = '/data/attachment/mantis_pcv/';

$g_limit_reporters = ON;

$g_show_realname = ON;
$g_move_bug_threshold = UPDATER;
$g_time_tracking_without_note = OFF;

$g_backward_year_count = 100;
$g_forward_year_count = 100;

$g_session_validation = OFF;

# --- Email Configuration ---	# --- Email Configuration ---
$g_phpMailer_method		= PHPMAILER_METHOD_MAIL; 	 # or PHPMAILER_METHOD_SMTP, PHPMAILER_METHOD_SENDMAIL
$g_administrator_email  = 'mantis@pacificcross.vn';
$g_webmaster_email      = 'mantis@pacificcross.vn';
$g_from_name         	= 'PCV Issue Tracker';
$g_from_email           = 'mantis@pacificcross.vn';   # the "From: " field in emails
$g_return_path_email    = 'mantis@pacificcross.vn';   # the return address for bounced mail

$g_email_receive_own   = ON;
$g_email_send_using_cronjob = ON;
$g_email_send_using_cronjob_number = 15;


//Hide fields in view page
#Thuan
$g_enable_profiles = OFF;
$g_tag_view_threshold = NOBODY;
$g_tag_attach_threshold = NOBODY;
$g_set_view_status_threshold = DEVELOPER;
$g_change_view_status_threshold = NOBODY;
$g_default_timezone = 'Asia/Ho_Chi_Minh';
$g_default_limit_view = 50;

$g_short_date_format    = 'Y-m-d';
$g_normal_date_format   = 'Y-m-d H:i';
$g_complete_date_format = 'Y-m-d H:i T';
$g_calendar_date_format   = 'Y-m-d H:i';

$g_bug_report_page_fields = array(
    'category_id',
    'view_state',
    'handler',
    //'priority',
    //'severity',
    //'reproducibility',
    //'platform',
    //'os',
    //'os_version',
    //'product_version',
    //'product_build',
    //'target_version',
    'summary',
    'description',
    //'additional_info',
    //'steps_to_reproduce',
    'attachments',
    // 'due_date',
);

$g_bug_view_page_fields = array (
    'id',
    'project',
    'category_id',
    'view_state',
    'date_submitted',
    'last_updated',
    'reporter',
    'handler',
    //'priority',
    //'severity',
    //'reproducibility',
    'status',
    //'resolution',
    // 'projection',
    // 'eta',
    // 'platform',
    // 'os',
    // 'os_version',
    // 'product_version',
    // 'product_build',
    // 'target_version',
    // 'fixed_in_version',
    'summary',
    'description',
    // 'additional_info',
    // 'steps_to_reproduce',
    // 'tags',
    'attachments',
    // 'due_date',
);

$g_bug_update_page_fields = array(
	'additional_info',
	'category_id',
	'date_submitted',
	'description',
	// 'due_date',
	// 'eta',
	//'fixed_in_version',
	'handler',
	'id',
	'last_updated',
	//'os',
	//'os_version',
	//'platform',
	//'priority',
	//'product_build',
	//'product_version',
	'project',
	// 'projection',
	'reporter',
	//'reproducibility',
	//'resolution',
	//'severity',
	'status',
	//'steps_to_reproduce',
	'summary',
	//'target_version',
	'view_state',
);
//Customize Resolution
//$g_resolution_enum_string = '10:open,11:accepted,12:partialyaccepted,13:declined,30:reopened';
//$g_resolution_enum_string = '10:open,20:fixed,30:reopened,40:unable to duplicate,50:not fixable,60:duplicate,70:not a bug,80:suspended,90:wont fix';
//$g_resolution_colors['accepted'] = '#ACE7AE';

//Customize status-------------------------------------------------------------------
# Revised enum string with new 'open' status
$g_status_enum_string = '10:new,11:accepted,12:partiallyaccepted,13:declined,14:pending,15:re-open,16:inforequest,17:infosubmitted,18:readyforprocess,19:offered,20:feedback,21:paid_approved,22:finalized,23:re-underwrite,24:withdrawn,25:issued_unpaid,26:begin_consultation,27:re_consult,28:more_time_request,30:acknowledged,40:confirmed,50:assigned,60:open,61:renew,62:next_payment,80:resolved,81:terminated,82:finish_consultation,90:closed';

# Status color additions
$g_status_colors['terminated'] = '#b05f62';
$g_status_colors['declined'] = '#ff0000';
$g_status_colors['withdrawn'] = '#633e05';
$g_status_colors['issued_unpaid'] = '#63ba5d';
$g_status_colors['accepted'] = '#00ff00';
$g_status_colors['partiallyaccepted'] = '#146b26';
$g_status_colors['offered'] = '#128068';
$g_status_colors['pending'] = '#f0f000';
$g_status_colors['inforequest'] = '#a39255';
$g_status_colors['infosubmitted'] = '#deed8a';
$g_status_colors['readyforprocess'] = '#f500e9';
$g_status_colors['paid_approved'] = '#8a3e86';
$g_status_colors['finalized'] = '#b8aeb8';
$g_status_colors['re-open'] = '#7579e6';
$g_status_colors['reunderwrite'] = '#0e54b5';
$g_status_colors['renew'] = '#59b6de';
$g_status_colors['next_payment'] = '#6c918f';
$g_status_colors['begin_consultation'] = '#F2F542';
$g_status_colors['re_consult'] = '#F5425D';
$g_status_colors['more_time_request'] = '#9A11BF';
$g_status_colors['finish_consultation'] = '#13D440';

$g_manage_user_threshold = MANAGER;
$g_export_bug_threshold = DEVELOPER;
$g_view_history_threshold = DEVELOPER;
$g_monitor_bug_threshold = DEVELOPER;
$g_monitor_add_others_bug_threshold = UPDATER;
$g_show_monitor_list_threshold = DEVELOPER;
$g_show_duplicated_on_edit = DEVELOPER;
$g_relationship_add_others_bug_threshold = VIEWER;
$g_claim_reasons = array(
    25 => 40,
    27 => 41,
    28 => 42,
    29 => 43,
    30 => 44,
    31 => 45,
    32 => 46,
    33 => 47,
    34 => 48,
    35 => 49,
    36 => 50,
    37 => 51,
    38 => 52,
    39 => 53
);
$g_transfer_history = array(
    0 => '#',
    1 => 'Date',
    2 => 'Amount',
    3 => 'Status',
    4 => 'Method',
    5 => 'Details',
    6 => 'MDTC',
    7 => 'Remark'
);
$g_transfer_history_cid = 19;
$g_claim_no_cid = 9;
$g_transfer_status_cid = 18;
$g_transfer_date_cid = 15;
$g_transfer_amount_cid = 16;
$g_transfer_remark_cid = 17;
$g_mdtc_cid = 54;
$g_transfer_history_ids = array( 9, 15, 16, 17, 18, 19, 54 );
$g_transfer_status_color = array(
    'Transferred' => '#00ff00',
    'Return' => 'ff6600',
    '' => '',
);

$g_roadmap_view_threshold = NOBODY;
$g_view_changelog_threshold = NOBODY;
$g_view_summary_threshold = NOBODY;

$g_show_detailed_errors = ON;
