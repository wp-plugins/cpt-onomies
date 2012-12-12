<?php

global $wpdb;

// if uninstall not called from the WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();
	
// delete options
delete_option( 'custom_post_type_onomies_custom_post_types' );
delete_option( 'custom_post_type_onomies_other_custom_post_types' );

// delete user options
$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "usermeta WHERE meta_key IN ( 'wp_custom_post_type_onomies_show_edit_tables', 'custom_post_type_onomies_show_edit_tables' )", NULL ) );
	
// delete postmeta
$wpdb->query( $wpdb->prepare( "DELETE FROM " . $wpdb->prefix . "postmeta WHERE meta_key = %s", '_custom_post_type_onomies_relationship' ) );

?>