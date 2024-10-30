<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'ip2location_widget_type' );
delete_option( 'ip2location_widget_language' );
delete_option( 'ip2location_widget_debug_log_enabled' );

wp_cache_flush();
