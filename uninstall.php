<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

require_once( __DIR__ . '/classes/model.php' );
require_once( __DIR__ . '/classes/models/campaign.php' );
require_once( __DIR__ . '/classes/models/opportunity.php' );
require_once( __DIR__ . '/classes/models/report.php' );
require_once( __DIR__ . '/classes/models/email-template.php' );

ReachSEO_Report::drop_table();
ReachSEO_Opportunity::drop_table();
ReachSEO_Email_Template::drop_table();
ReachSEO_Campaign::drop_table();

delete_option( 'reachseo_settings' );
delete_option( 'reachseo_display_tour' );
delete_option( 'reachseo_version' );
