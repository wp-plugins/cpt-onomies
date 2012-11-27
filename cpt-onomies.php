<?php

/*
Plugin Name: CPT-onomies: Using Custom Post Types as Taxonomies
Plugin URI: http://wordpress.org/extend/plugins/cpt-onomies
Description: A CPT-onomy is a taxonomy built from a custom post type, using the post titles as the taxonomy terms. Create custom post types using the CPT-onomies custom post type manager or use post types created by themes or other plugins.
Version: 1.1.1
Author: Rachel Carden
Author URI: http://www.rachelcarden.com
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

// let's define some stuff. maybe we'll use it later
define( 'CPT_ONOMIES_VERSION', '1.1.1' );
define( 'CPT_ONOMIES_WORDPRESS_MIN', '3.1' );
define( 'CPT_ONOMIES_DIR', dirname( __FILE__ ) );
define( 'CPT_ONOMIES_URL', WP_PLUGIN_URL . '/' . basename( dirname( __FILE__ ) ) . '/' );
define( 'CPT_ONOMIES_PLUGIN_NAME', 'CPT-onomies: Using Custom Post Types as Taxonomies' );
define( 'CPT_ONOMIES_PLUGIN_SHORT_NAME', 'CPT-onomies' );
define( 'CPT_ONOMIES_PLUGIN_DIRECTORY_URL', 'http://wordpress.org/extend/plugins/cpt-onomies/' );
define( 'CPT_ONOMIES_DASH', 'custom-post-type-onomies' );
define( 'CPT_ONOMIES_UNDERSCORE', 'custom_post_type_onomies' );
define( 'CPT_ONOMIES_OPTIONS_PAGE', 'custom-post-type-onomies' );
define( 'CPT_ONOMIES_POSTMETA_KEY', '_custom_post_type_onomies_relationship' );
define( 'CPT_ONOMIES_TEXTDOMAIN', 'cpt-onomies' );

// if we build them, they will load
require_once( CPT_ONOMIES_DIR . '/cpt_onomy.php' );
require_once( CPT_ONOMIES_DIR . '/manager.php' );
if ( is_admin() ) {
	require_once( CPT_ONOMIES_DIR . '/admin.php' );
	require_once( CPT_ONOMIES_DIR . '/admin-settings.php' );
}
require_once( CPT_ONOMIES_DIR . '/widgets.php' );

// for translations
add_action( 'plugins_loaded', 'cpt_onomies_load_textdomain' );
function cpt_onomies_load_textdomain() {
	load_plugin_textdomain( CPT_ONOMIES_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}

// let's get the party started
$cpt_onomy = new CPT_TAXONOMY();
$cpt_onomies_manager = new CPT_ONOMIES_MANAGER();
if ( class_exists( 'CPT_ONOMIES_ADMIN' ) )
	$cpt_onomies_admin = new CPT_ONOMIES_ADMIN();
if ( class_exists( 'CPT_ONOMIES_ADMIN_SETTINGS' ) )
	$cpt_onomies_admin_settings = new CPT_ONOMIES_ADMIN_SETTINGS();

?>