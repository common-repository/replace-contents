<?php
/**
 * Uninstall
 *
 * @package Replace Contents
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/* For Single site */
if ( ! is_multisite() ) {
	delete_option( 'replace_contents_logs' );
	delete_option( 'replace_contents_max_execution_time' );
} else {
	/* For Multisite */
	global $wpdb;
	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		delete_option( 'replace_contents_logs' );
		delete_option( 'replace_contents_max_execution_time' );
	}
	switch_to_blog( $original_blog_id );

	/* For site options. */
	delete_site_option( 'replace_contents_logs' );
	delete_site_option( 'replace_contents_max_execution_time' );
}
