<?php

/**
 * Holds the functions needed for the admin.
 *
 * @since 1.0
 */
class CPT_ONOMIES_ADMIN {
	
	public $options_page;
	
	/**
	 * Adds WordPress hooks (actions and filters).
	 *
	 * This function is only run in the admin.
	 *
	 * @since 1.0
	 */
	public function CPT_ONOMIES_ADMIN() { $this->__construct(); }
	public function __construct() {
		if ( is_admin() ) {
		
			// register user settings
			add_action( 'admin_init', array( &$this, 'register_user_settings' ) );
		
			// if the user visits edit-tags.php to manage the terms, we set them straight
			add_action( 'admin_init', array( &$this, 'deny_edit_tags' ) );
			
			// adds a settings link to the plugins page
			add_filter( 'plugin_action_links', array( &$this, 'add_plugin_action_links' ), 10, 2 );
						
			// add plugin options page
			add_action( 'admin_menu', array( &$this, 'add_plugin_options_page' ) );
			add_action( 'admin_init', array( &$this, 'save_plugin_options_page' ) );
					
			// add styles and scripts for plugin options page
			add_action( 'admin_print_styles-settings_page_'.CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'add_plugin_options_styles' ) );
			add_action( 'admin_print_scripts-settings_page_'.CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'add_plugin_options_scripts' ) );
			add_action( 'wp_ajax_custom_post_type_onomy_validate_if_post_type_exists', array( &$this, 'validate_plugin_options_if_post_type_exists' ) );
			add_action( 'wp_ajax_custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables', array( &$this, 'update_plugin_options_edit_custom_post_type_closed_edit_tables' ) );
						
			// add CPT-onomy "edit" meta boxes
			add_action( 'add_meta_boxes', array( &$this, 'add_cpt_onomy_meta_boxes' ), 10, 2 );
					
			// runs when any post is saved
			add_action( 'save_post', array( &$this, 'save_post' ), 10, 2 );
			
			// tweak the query for filtering "edit posts" screens
			add_filter( 'request', array( &$this, 'change_query_vars' ) );
			add_filter( 'posts_join', array( &$this, 'posts_join' ), 1 );
			
			// add custom admin columns
			add_filter( 'manage_pages_columns', array( &$this, 'add_cpt_onomy_admin_column' ), 10, 1 );
			add_filter( 'manage_posts_columns', array( &$this, 'add_cpt_onomy_admin_column' ), 10, 2 );
			// edit custom admin columns
			add_action( 'manage_pages_custom_column', array( &$this, 'edit_cpt_onomy_admin_column' ), 10, 2 );
			add_action( 'manage_posts_custom_column', array( &$this, 'edit_cpt_onomy_admin_column' ), 10, 2 );
			
		}	
	}
	
	/**
	 * Registers user's plugin settings.
	 *
	 * This function is invoked by the action 'admin_init'.
	 *
	 * @since 1.0
	 */
	public function register_user_settings() {
		register_setting( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_UNDERSCORE . '_custom_post_types', array( &$this, 'validate_plugin_options_custom_post_types' ) );
		register_setting( CPT_ONOMIES_OPTIONS_PAGE . '-other-custom-post-types', CPT_ONOMIES_UNDERSCORE . '_other_custom_post_types', array( &$this, 'validate_plugin_options_other_custom_post_types' ) );
	}
	
	/**
	 * This ajax function is run on the "edit" custom post type page.
	 * It tells the script whether or not the post type name the 
	 * user is trying to enter already exists.
	 *
	 * This function is invoked by the action 'wp_ajax_custom_post_type_onomy_post_type_exists'.
	 *
	 * @since 1.0
	 */
	public function validate_plugin_options_if_post_type_exists() {
		$original_custom_post_type_name = ( isset( $_POST[ 'original_custom_post_type_onomies_cpt_name' ] ) && !empty( $_POST[ 'original_custom_post_type_onomies_cpt_name' ] ) ) ? $_POST[ 'original_custom_post_type_onomies_cpt_name' ] : NULL;
		$custom_post_type_name = ( isset( $_POST[ 'custom_post_type_onomies_cpt_name' ] ) && !empty( $_POST[ 'custom_post_type_onomies_cpt_name' ] ) ) ? $_POST[ 'custom_post_type_onomies_cpt_name' ] : NULL;
		if ( !empty( $original_custom_post_type_name ) && !empty( $custom_post_type_name ) && $custom_post_type_name != $original_custom_post_type_name && post_type_exists( $custom_post_type_name ) )
			echo 'false';
		else if ( empty( $original_custom_post_type_name ) && !empty( $custom_post_type_name ) && post_type_exists( $custom_post_type_name ) )
			echo 'false';
		else
			echo 'true';
		die();
	}
	
	/**
	 * This ajax function is run on the "edit" custom post type page.
	 * It detects when the user has "opened" or "closed" an advanced
	 * edit table and updates the user_option accordingly.
	 *
	 * This function is invoked by the action 'wp_ajax_custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables'.
	 *
	 * @since 1.0
	 * @uses $user_ID
	 */
	public function update_plugin_options_edit_custom_post_type_closed_edit_tables() {
		global $user_ID;
		$edit_table = $_POST[ 'custom_post_type_onomies_edit_table' ];
		if ( !empty( $edit_table ) ) {
			$show = $_POST[ 'custom_post_type_onomies_edit_table_show' ];
			if ( $show == 'true' ) $show = true;
			else $show = false;			
			// get set option
			$option_name = CPT_ONOMIES_UNDERSCORE . '_show_edit_tables';
			$saved_option = get_user_option( $option_name, $user_ID );
			// we need to make sure its saved into the array
			if ( $show ) {
				if ( empty( $saved_option ) || ( !empty( $saved_option ) && !in_array( $edit_table, $saved_option ) ) )
					$saved_option[] = $edit_table;
			}
			// we need to make sure its removed from the array
			else if ( !empty( $saved_option ) && in_array( $edit_table, $saved_option ) ) {
				foreach( $saved_option as $key => $value ) {
					if ( $value == $edit_table )
						unset( $saved_option[ $key ] );
				}
			}
			update_user_option( $user_ID, $option_name, $saved_option, true );
		}
		die();
	}
		
	/**
	 * This function validates the 'custom_post_types' setting anytime update_option() is run.
	 * This includes saving the "edit" options page, when a plugin CPT is deleted on the options page
	 * and when a plugin CPT is activated (by link) on the options page.
	 *
	 * If saving the "edit" options page and a new custom post type is added, the function will edit the redirect to show new CPT
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param array $custom_post_types - the custom post type setting that is being updated
	 * @return array - validated custom post type information
	 */
	public function validate_plugin_options_custom_post_types( $custom_post_types ) {
		global $cpt_onomies_manager;
		// make sure we're saving "edit" options page
		if ( current_user_can( 'manage_options' ) && isset( $_POST[ 'option_page' ] ) && $_POST[ 'option_page' ] == CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types' && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'update' && !empty( $custom_post_types ) ) {
		
			// get saved info
			$saved_post_types = ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ? $cpt_onomies_manager->user_settings[ 'custom_post_types' ] : array();
							
			// if set, will redirect settings page to show specified custom post type
			$redirect_cpt = NULL;
							
			foreach( $custom_post_types as $cpt_key => $cpt ) {
				
				// sanitize the data
				foreach( $cpt as $key => $data ) {
					if ( !is_array( $data ) )
						$cpt[ $key ] = strip_tags( $data );
				}
				
				// these settings are potential arrays
				if ( isset( $cpt[ 'capability_type' ] ) && !empty( $cpt[ 'capability_type' ] ) ) {
					// can be separated by space or comma
					$cpt[ 'capability_type' ] = str_replace( ', ', ',', trim( $cpt[ 'capability_type' ] ) );
					$cpt[ 'capability_type' ] = str_replace( ' ', ',', trim( $cpt[ 'capability_type' ] ) );
					$cpt[ 'capability_type' ] = explode( ',', $cpt[ 'capability_type' ] );
				}
				// validating
				if ( isset( $cpt[ 'register_meta_box_cb' ] ) && !empty( $cpt[ 'register_meta_box_cb' ] ) ) {
					$cpt[ 'register_meta_box_cb' ] = preg_replace( '/([^a-z0-9\_])/i', '', $cpt[ 'register_meta_box_cb' ] );
				}
				// must be numeric
				if ( isset( $cpt[ 'menu_position' ] ) && !empty( $cpt[ 'menu_position' ] ) && is_numeric( $cpt[ 'menu_position' ] ) )
					$cpt[ 'menu_position' ] = intval( $cpt[ 'menu_position' ] );
				else if ( isset( $cpt[ 'menu_position' ] ) && !empty( $cpt[ 'menu_position' ] ) )
					unset( $cpt[ 'menu_position' ] );
					
					
				// Maximum is 20 characters. Can only contain lowercase, alphanumeric characters and underscores
				$valid_name_preg_test = '/([^a-z0-9\_])/i';
				
				$original_name = ( isset( $cpt[ 'original_name' ] ) && !empty( $cpt[ 'original_name' ] ) && strlen( $cpt[ 'original_name' ] ) <= 20 && !preg_match( $valid_name_preg_test, $cpt[ 'original_name' ] ) ) ? strtolower( $cpt[ 'original_name' ] ) : NULL;
				$new_name = ( isset( $cpt[ 'name' ] ) && !empty( $cpt[ 'name' ] ) && strlen( $cpt[ 'name' ] ) <= 20 && !preg_match( $valid_name_preg_test, $cpt[ 'name' ] ) ) ? strtolower( $cpt[ 'name' ] ) : NULL;
				$label = ( isset( $cpt[ 'label' ] ) && !empty( $cpt[ 'label' ] ) ) ? $cpt[ 'label' ] : NULL;
				
				// if no valid name or label, why bother so remove the data
				if ( empty( $original_name ) && empty( $new_name ) && empty( $label ) ) {
					unset( $custom_post_types[ $cpt_key ] );
					$redirect_cpt = 'new';
					// add a settings error to let the user know we made a name up
					add_settings_error( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types', CPT_ONOMIES_DASH . '-custom-post-types-error', 'You must provide a valid "Label" or "Name" for the custom post type to be saved.', 'error' );
				}
				
				else {
				
					// remove names from info
					if ( isset( $cpt[ 'original_name' ] ) )
						unset( $cpt[ 'original_name' ] );
						
					// if no label, then add 'Posts'
					if ( !isset( $cpt[ 'label' ] ) || empty( $cpt[ 'label' ] ) )
						$cpt[ 'label' ] = 'Posts';
						
					// will be the name and key for storing data
					$store_name = NULL;
					
					// take the label and create a name
					if ( empty( $original_name ) && empty( $new_name ) ) {
						
						$made_up_orig = $made_up_name = substr( strtolower( preg_replace( $valid_name_preg_test, '', $cpt[ 'label' ] ) ), 0, 20 );
						$made_up_index = 1;
						while( post_type_exists( $made_up_name ) || array_key_exists( $made_up_name, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) {
							$made_up_name = $made_up_orig . $made_up_index;
							$made_up_index++;				
						}
						
						$store_name = $made_up_name;
						
					}
					else {
						
						// if no original name (new) and new name exists then save under new name
						if ( empty( $original_name ) && !empty( $new_name ) )
							$store_name = $new_name;
							
						// if no new name and original name exists then save under original name
						else if ( empty( $new_name ) && !empty( $original_name ) ) 
							$store_name = $original_name;
							
						// if both original and new name exist and new is different from original
						// then remove info with original name and save under new name
						else if ( !empty( $original_name ) && !empty( $new_name ) && $new_name != $original_name ) {
							
							// remove original name
							unset( $saved_post_types[ $original_name ] );
							
							$store_name = $new_name;
							
						}
						
						// no conflicts. save info under name new
						else
							$store_name = $new_name;
						
					}
					
					// store data
					$cpt[ 'name' ] = $store_name;
					$saved_post_types[ $store_name ] = $cpt;
					
					// redirect
					$redirect_cpt = $store_name;
					
				}
				
			}
	
			// sort custom post types (alphabetically) by post type
			ksort( $saved_post_types );
				
			// change the referer URL to change cpt=new to cpt=[new cpt] so that redirect will show recently added cpt
			if ( isset( $redirect_cpt ) )
				$_REQUEST['_wp_http_referer'] = preg_replace( '/(\&edit\=([^\&]*))/i', '&edit='.$redirect_cpt, $_REQUEST['_wp_http_referer'] );
				
			return $saved_post_types;
			
		}
		return $custom_post_types;
	}
	
	/**
	 * This function validates the "other" custom post types setting anytime update_option() is run.
	 * This function is run on the options page.
	 *
	 * If the "other" custom post type no longer exists, it deletes the settings from the DB.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param array $other_custom_post_types - the other custom post type setting that is being updated
	 * @return array - validated custom post type information
	 */
	public function validate_plugin_options_other_custom_post_types( $other_custom_post_types ) {
		global $cpt_onomies_manager;		
		// make sure we're saving edit page
		// we need these parameters because this function is called whenever update_option is called for our 'other_custom_post_types' option so we only want these tests run when the edit screen is saved
		if ( current_user_can( 'manage_options' ) && isset( $_POST[ 'option_page' ] ) && $_POST[ 'option_page' ] == CPT_ONOMIES_OPTIONS_PAGE . '-other-custom-post-types' && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'update' ) {
			
			$saved_other_post_types = ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) ? $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] : array();
					
			// save information
			if ( !empty( $other_custom_post_types ) ) {
				foreach( $other_custom_post_types as $cpt_key => $cpt ) {
					$saved_other_post_types[ $cpt_key ] = $cpt;
				}
			}
			
			// post types that no longer exist are removed from the settings
			foreach( $saved_other_post_types as $cpt_key => $cpt ) {
				if ( !post_type_exists( $cpt_key ) || ( post_type_exists( $cpt_key ) && ( $cpt_onomies_manager->is_registered_cpt( $cpt_key ) ) ) )
					unset( $saved_other_post_types[ $cpt_key ] );
			}
				
			// sort custom post types (alphabetically) by post type
			ksort( $saved_other_post_types );
			
			return $saved_other_post_types;
			
		}
		return $other_custom_post_types;
	}
	
	/**
	 * The usual admin page for managing terms is edit-tags.php but we do not
	 * want users to access this page. $this->user_cannot_manage_edit_delete_terms()
	 * removes the ability access this page and throws up a 'Cheatin' uh?' message
	 * but this function replaces that message with some helpful text on where to go
	 * to edit the terms.
	 * 
	 * @since 1.0
	 * @uses $cpt_onomies_manager, $pagenow
	 */
	public function deny_edit_tags() {
		global $cpt_onomies_manager, $pagenow;
		if ( $pagenow == 'edit-tags.php' && isset( $_REQUEST[ 'taxonomy' ] ) && $cpt_onomies_manager->is_registered_cpt_onomy( $_REQUEST[ 'taxonomy' ] ) ) {	
			$taxonomy = $_REQUEST[ 'taxonomy' ];		
			$tax = get_taxonomy( $taxonomy );
			$custom_post_type = get_post_type_object( $taxonomy );
			// if the user is capable of editing the post to begin with
			if ( current_user_can( $custom_post_type->cap->edit_posts ) ) {
				wp_die( 'Since \'' . $tax->labels->name . '\' is a registered CPT-onomy, you manage it\'s "terms" by managing the posts created under the custom post type \'' . $tax->labels->name . '\'. So go ahead... <a href="' . add_query_arg( array( 'post_type' => $taxonomy ), admin_url( 'edit.php' ) ) . '">manage the posts.</a>' );
			}
			// otherwise, don't get their hopes up
			else {
				wp_die( 'Since \'' . $tax->labels->name . '\' is a registered CPT-onomy, you manage it\'s "terms" by managing the posts created under the custom post type \'' . $tax->labels->name . '\'. Unfortunately, you don\'t have permission to edit these posts. Sorry. If this is a mistake, contact your administrator. <a href="' . admin_url() . '">' . 'Go to the dashboard.</a>' );
			}
		}
	}
	
	/**
	 * Adds a settings link to plugins page.
	 * 
	 * @since 1.0
	 * @param $links - the links info already created by WordPress
	 * @param $file - the plugin's main file
	 * @return array - the links info after it has been filtered	 
	 */
	public function add_plugin_action_links( $links, $file ) {
		if ( $file == 'cpt-onomies/cpt-onomies.php' )
			$links['settings'] = '<a href="options-general.php?page=' . CPT_ONOMIES_OPTIONS_PAGE . '">' . __( 'Settings' , CPT_ONOMIES_OPTIONS_PAGE ) . '</a>';
		return $links;
	}
	
	/**
	 * Adds a settings/options page for the plugin to the WordPress admin menu, under 'Settings'.
	 *
	 * This function is invoked by the action 'admin_menu'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 */
	public function add_plugin_options_page() {
		global $cpt_onomies_manager;
		// add options page
		$this->options_page = add_options_page( CPT_ONOMIES_PLUGIN_NAME, CPT_ONOMIES_PLUGIN_SHORT_NAME, 'manage_options', CPT_ONOMIES_OPTIONS_PAGE, array( &$this, 'print_plugin_options_page' ) );
		
		// test for edit screen
		// we can't just check for post types that exist because we allow the user to 'deactivate' post types so we need to check the settings
		// if edit = "new", we're good
		// if "other" is set and the post type exists, we're good
		// otherwise make sure this is a CPT stored in the settings
		$edit = ( isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ 'edit' ] ) && ( strtolower( $_REQUEST[ 'edit' ] ) == 'new' || ( isset( $_REQUEST[ 'other' ] ) && post_type_exists( strtolower( $_REQUEST[ 'edit' ] ) ) ) || ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( strtolower( $_REQUEST[ 'edit' ] ), $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ) ) ? true : false;
		
		// add meta boxes for options page
		// boxes just for the edit screen
		if ( $edit ) {
			if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( strtolower( $_REQUEST[ 'edit' ] ), $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) )
				add_meta_box( CPT_ONOMIES_DASH . '-delete-custom-post-type', 'Delete this Custom Post Type', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'delete_custom_post_type' );
			add_meta_box( CPT_ONOMIES_DASH . '-edit-custom-post-type', 'Edit Your Custom Post Type', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'normal', 'core', 'edit_custom_post_type' );
		}
		
		// don't include these meta boxes on edit screen
		else {
			add_meta_box( CPT_ONOMIES_DASH . '-custom-post-types', 'Manage Your Custom Post Types', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'normal', 'core', 'custom_post_types' );			
			add_meta_box( CPT_ONOMIES_DASH . '-other-custom-post-types', 'Other Custom Post Types', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'normal', 'core', 'other_custom_post_types' );	
		}
		
		add_meta_box( CPT_ONOMIES_DASH . '-about', 'About this Plugin', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'about' );
		add_meta_box( CPT_ONOMIES_DASH . '-key', 'What the Icons Mean', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'key' );
		add_meta_box( CPT_ONOMIES_DASH . '-promote', 'Spread the Love', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'promote' );
		add_meta_box( CPT_ONOMIES_DASH . '-support', 'Any Questions?', array( &$this, 'print_plugin_options_meta_box' ), $this->options_page, 'side', 'core', 'support' );	
		
		// adds the help tabs when the option page loads
		add_action( 'load-' . $this->options_page, array( &$this, 'add_plugin_options_help_tab' ) );
	}
	
	/**
	 * This function takes care of a few actions on the options page.
	 * It activates and deletes custom post types.
	 *
	 * This function is invoked by the action 'admin_int'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 */
	public function save_plugin_options_page() {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) && isset( $_REQUEST[ 'page' ] ) && $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ '_wpnonce' ] ) ) {
			
			// activate
			if ( isset( $_REQUEST[ 'activate' ] ) ) {
				$CPT = $_REQUEST[ 'activate' ];
				// verify nonce
				if ( wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'activate-cpt-' . $CPT ) ) {
					// change the activation settings
					if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $CPT, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) {
						
						// remove the setting
						unset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $CPT ][ 'deactivate' ] );
						// update database
						update_option( CPT_ONOMIES_UNDERSCORE . '_custom_post_types', $cpt_onomies_manager->user_settings[ 'custom_post_types' ] );
											
						// redirect
						wp_redirect( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'cptactivated' => $CPT ), admin_url( 'options-general.php' ) ) );
						exit();
					
					}			
				}
				else {
					// add error message
					wp_die( 'Looks like there was an error and the custom post type was not activated. <a href="' . add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) . '">Go back to CPT-onomies</a> and try again.' );
				}
			}
			
			// delete
			else if ( isset( $_REQUEST[ 'delete' ] ) ) {
				$CPT = $_REQUEST[ 'delete' ];
				// verify nonce
				if ( wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'delete-cpt-' . $CPT ) ) {	
					// delete CPT from settings
					if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $CPT, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) {
						
						// remove from settings
						unset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $CPT ] );
						// update database
						update_option( CPT_ONOMIES_UNDERSCORE . '_custom_post_types', $cpt_onomies_manager->user_settings[ 'custom_post_types' ] );
											
						// redirect
						wp_redirect( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'cptdeleted' => '1' ), admin_url( 'options-general.php' ) ) );
						exit();					
					}		
				}
				else {
					// add error message
					wp_die( 'Looks like there was an error and the custom post type was not deleted. <a href="' . add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) . '"> Go back to CPT-onomies</a> and try again.' );
				}
				
			}
			
		}
	}
	
	/**
	 * This functions adds the help tab to the top of the options page.
	 * 
	 * @since 1.0
	 */
	public function add_plugin_options_help_tab() {
	    $screen = get_current_screen();
		 // only add help tab on my options page
	    if ( $screen->id != $this->options_page )
	        return;
		$screen->add_help_tab( array( 
	        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_getting_started',
	        'title'	=> 'Getting Started',
	        'callback'	=> array( &$this, 'get_plugin_options_help_tab_getting_started' )
	    ));
		$screen->add_help_tab( array( 
	        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_managing_editing_your_cpts',
	        'title'	=> 'Managing/Editing Your Custom Post Types',
	        'callback'	=> array( &$this, 'get_plugin_options_help_tab_managing_editing_your_cpts' )
	    ));
		$screen->add_help_tab( array( 
	        'id'	=> CPT_ONOMIES_UNDERSCORE . '_help_troubleshooting',
	        'title'	=> 'Troubleshooting',
	        'callback'	=> array( &$this, 'get_plugin_options_help_tab_troubleshooting' )
	    ));
	}
	
	/**
	 * This function returns the content for the What Is A CPT-onomy "Help" tab on the options page.
	 *
	 * @since 1.0
	 */
	public function get_plugin_options_help_tab_getting_started() { ?>
		<h3>What Is A CPT-onomy?</h3>
        <p>A CPT-onomy is a taxonomy built from a custom post type, using post titles to assign taxonomy relationships just as you would assign taxonomy terms. You can use the CPT-onomies admin to create your own custom post types or use post types created by themes or other plugins.</p>
        
        <p><strong>Is CPT-onomy an official WordPress term?</strong> No. It's just a fun word I made up.</p>
        
        <h4>Need a custom post type but not a CPT-onomy?</h4>
        <p>CPT-onomies offers an extensive custom post type manager, allowing you to create and completely customize your custom post types <strong>without touching one line of code!</strong></p>
        
        <h4>How to Get Started</h4>
        <p>You can't have a CPT-onomy without a custom post type! <a href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => 'new' ), admin_url( 'options-general.php' ) ) ); ?>">Add a new custom post type</a> (or <a href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) ); ?>#cpt-onomies-other-custom-post-types">use custom post types created by themes or other plugins</a>), register the custom post type as a CPT-onomy (under "Register this Custom Post Type as a CPT-onomy" on the edit screen) and CPT-onomies will take care of the rest.</p>
        
        <h4>Why CPT-onomies?</h4>
        <p>It doesn't take long to figure out that custom post types can be a pretty powerful tool for creating and managing numerous types of content. For example, you might use the custom post types "Movies" and "Actors" to build a movie database but what if you wanted to group your "movies" by its "actors"? You could create a custom "actors" taxonomy but then you would have to manage your list of actors in two places: your "actors" custom post type and your "actors" taxonomy. This can be a pretty big hassle, especially if you have an extensive custom post type.</p>
        <p><strong>This is where CPT-onomies steps in.</strong> Register your custom post type as a CPT-onomy and CPT-onomies will build your taxonomy for you, using your post type's post titles as the terms. Pretty cool, huh?
        
        <h4>Using CPT-onomies</h4>
        <p>What's really great about CPT-onomies is that they work just like any other taxonomy, allowing you to use WordPress taxonomy functions, like <a href="http://codex.wordpress.org/Function_Reference/get_terms" target="_blank">get_terms()</a> and <a href="http://codex.wordpress.org/Function_Reference/wp_get_object_terms" target="_blank">wp_get_object_terms()</a>, to access the information you need. CPT-onomies even includes a tag cloud widget for your sidebar.</p>
        
        <p><span class="description"><strong>Note:</strong> Unfortunately, not every taxonomy function can be used at this time. <a href="http://rachelcarden.com/cpt-onomies/documentation/" title="CPT-onomy documentation" target="_blank">Check out the CPT-onomy documentation</a> to see which WordPress taxonomy functions work and when you'll need to access the plugin's CPT-onomy class.</span></p>
    <?php }
	
	/**
	 * This function returns the content for the Managing Your Custom Post Type "Help" tab on the options page.
	 *
	 * @since 1.0
	 */
	public function get_plugin_options_help_tab_managing_editing_your_cpts() { ?>
    	<h3>Managing/Editing Your Custom Post Types</h3>
        <p>For the most part, managing your custom post types is fairly easy. However, there are a few settings that can either be confusing or complicated. If you can't find the answer below, refer to <a href="http://codex.wordpress.org/Function_Reference/register_post_type" title="WordPress Codex page for register_post_type()" target="_blank">the WordPress Codex</a>, <a href="<?php echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>?forum_id=10" title="CPT-onomies support forums" target="_blank">the plugin's support forums</a>, or <a href="http://www.rachelcarden.com/cpt-onomies/" target="_blank">my web site</a> for help.</p>
        <h4>Admin Menu Position (under Advanced Options)</h4>
        <p>If you would like to customize your custom post type's postion in the administration menu, all you have to do is enter a custom menu position. Use the table below as a quide.</p>
        <table class="menu_position" cellpadding="0" cellspacing="0" border="0">
        	<tr>
            	<td><strong>5</strong> - below Posts</td>
                <td><strong>65</strong> - below Plugins</td>
           	</tr>
            <tr>
            	<td><strong>10</strong> - below Media</td>
                <td><strong>70</strong> - below Users</td>
          	</tr>
            <tr>
            	<td><strong>15</strong> - below Links</td>
                <td><strong>75</strong> - below Tools</td>
          	</tr>
            <tr>
            	<td><strong>20</strong> - below Pages</td>
                <td><strong>80</strong> - below Settings</td>
          	</tr>
            <tr>
            	<td><strong>25</strong> - below comments</td>
                <td><strong>100</strong> - below second separator</td>
          	</tr>
            <tr>
            	<td colspan="2"><strong>60</strong> - below first separator</td>
          	</tr>
      	</table>
 	<?php }
	
	/**
	 * This function returns the content for the Troubleshooting "Help" tab on the options page.
	 *
	 * @since 1.0
	 */
	public function get_plugin_options_help_tab_troubleshooting() { ?>
    	<h3>Troubleshooting</h3>
        <p>If you're having trouble, and can't find the answer below, <a href="<?php echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>?forum_id=10" title="CPT-onomies support forums" target="_blank">check the support forums</a> or <a href="http://www.rachelcarden.com/cpt-onomies/" target="_blank">visit my web site</a>. If your problem involves a custom post type setting, <a href="http://codex.wordpress.org/Function_Reference/register_post_type" title="WordPress Codex page for register_post_type()" target="_blank">the WordPress Codex</a> might be able to help.</p>
        <h4>My custom post type and/or CPT-onomy is not showing up</h4>
        <p>Make sure your custom post type has not been deactivated <strong>AND</strong> that your custom post type is "Public" (under "Advanced Options").</p>
        <h4>My custom post type and/or CPT-onomy archive page is not working</h4>
        <p>If archive pages are enabled but are not working correctly, or are receiving a 404 error, it's probably the result of a rewrite or permalink error. Here are a few suggestions to get things working:</p>
        <ul>
        	<li><strong>Double check "Has Archive Page"</strong> Make sure the archive pages are enabled.</li>
        	<li><strong>Are pretty permalinks enabled?</strong> Archive pages will not work without pretty permalinks. Visit Settings->Permalinks and make sure anything but "Default" is selected.</li>
        	<li><strong>Changing rewrite rules:</strong> Whenever rewrite settings are changed, the rules need to be "flushed" to make sure everything is in working order. Flush your rewrite rules by visiting Settings->Permalinks and clicking "Save Changes".</li>
      	</ul>
    <?php }
	
	/**
	 * Queues style sheet for plugin's option page.
	 *
	 * This function is invoked by the action 'admin_print_styles-settings_page_{plugin name}'.
	 *
	 * @since 1.0
	 */	
	public function add_plugin_options_styles() {
		wp_enqueue_style( CPT_ONOMIES_DASH, CPT_ONOMIES_URL . '/css/admin.css' );
	}
	
	/**
	 * Queues scripts for plugin's option page.
	 *
	 * This function is invoked by the action 'admin_print_scripts-settings_page_{plugin name}'.
	 *
	 * @since 1.0
	 */	
	public function add_plugin_options_scripts() {
		wp_enqueue_script( 'jquery-form-validation', CPT_ONOMIES_URL . '/js/jquery.validate.min.js', array( 'jquery' ), '', true );
		wp_enqueue_script( CPT_ONOMIES_DASH, CPT_ONOMIES_URL . '/js/admin.js', array( 'jquery', 'jquery-form-validation' ), '', true );
		// need this script for the metaboxes to work correctly
		wp_enqueue_script( 'post' );
		wp_enqueue_script( 'postbox' );
	}
	
	/**
	 * Returns an object that contains the fields/properties
	 * for creating the admin table for creating/managing custom post types.
	 *
	 * This function is only invoked on the plugin's options page and is only
	 * available for users who have capability to 'manage_options'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @return object - the custom post type properties
	 */
	public function get_plugin_options_page_cpt_properties() {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) ) {
			// gather post type data to use in properties
			$post_type_data = array();
			foreach( get_post_types( array( 'public' => true ), 'objects' ) as $value => $cpt ) {
				// do not include 'attachment', aka media
				if ( !empty( $value ) && $value != 'attachment' && !empty( $cpt->labels->name ) ) {
					$post_type_data[ $value ] = (object) array(
						'label' => $cpt->labels->name
					);
				}
			}
			// gather taxonomy data to use in properties
			$taxonomy_data = array();
			foreach( get_taxonomies( array( 'public' => true ), 'objects' ) as $value => $tax ) {
				if ( !empty( $value ) && !$cpt_onomies_manager->is_registered_cpt_onomy( $value ) && !empty( $tax->labels->name ) ) {
					$taxonomy_data[ $value ] = (object) array(
						'label' => $tax->labels->name
					);
				}
			}
			// gather user data to use in properties
			$user_data = array();
			$wp_roles = new WP_Roles(); 
			foreach ( $wp_roles->role_names as $value => $label ) {
				if ( !empty( $value ) && !empty( $label ) ) {
					$user_data[ $value ] = (object) array(
						'label' => $label
					);
				}
			}
			// create and return properties
			return (object) array(
				'basic' => array(
					'label' => (object) array(
						'label' => 'Label',
						'type' => 'text',
						'validation' => 'required',
						'description' => 'A general, <strong>usually plural</strong>, descriptive name for the post type. <strong><span class="red">This field is required.</span></strong>'
					),
					'name' => (object) array(
						'label' => 'Name',
						'type' => 'text',
						'fieldid' => CPT_ONOMIES_DASH . '-custom-post-type-name',
						'validation' => 'required custom_post_type_onomies_validate_name custom_post_type_onomies_validate_name_characters',
						'description' => 'The name of the post type. This property is very important because it is used to reference the post type all throughout WordPress. <strong>This should contain only lowercase alphanumeric characters and underscores. Maximum is 20 characters.</strong> Be careful about changing this field once it has been set and you have created posts because the posts will not convert to the new name. <strong><span class="red">This field is required.</span></strong>'
					),
					'description' => (object) array(
						'label' => 'Description',
						'type' => 'textarea',
						'description' => 'Feel free to include a description.'
					)
				),
				'cpt_as_taxonomy' => (object) array(
					'label' => 'Register this Custom Post Type as a CPT-onomy',
					'type' => 'group',
					'data' => array(
						'attach_to_post_type' => (object) array(
							'label' => 'Attach to Post Types',
							'type' => 'checkbox',
							'description' => 'This setting allows you to use your custom post type in the same manner as a taxonomy, using your post titles as the terms. This is what we call a "CPT-onomy". You can attach this CPT-onomy to to any post type and assign posts just as you would assign taxonomy terms. <strong><span class="red">A post type must be checked in order to register this custom post type as a CPT-onomy.</span></strong>',
							'data' => $post_type_data
						),
						'has_cpt_onomy_archive' => (object) array(
							'label' => 'Has Archive Page',
							'type' => 'radio',
							'description' => 'This setting allows you to enable archive pages for this CPT-onomy. If enabled, the archive slug will be set to <strong>{ post type name }/tax/{ CPT-onomy term }</strong>, i.e. http://www.yoursite.com/movies/tax/the-princess-bride.',
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
								),
								'false' => (object) array(
									'label' => 'False'
								)
							)
						),
						'restrict_user_capabilities' => (object) array(
							'label' => 'Restrict User\'s Capability to Assign Term Relationships',
							'type' => 'checkbox',
							'description' => 'This setting allows you to grant specific user roles the capability, or permission, to assign term relationships for this CPT-onomy. <strong><span class="red">If no user roles are selected, then all user roles with the capability to \'assign_{ post type name }_terms\' will have permission.</span></strong>',
							'default' => array( 'administrator', 'editor', 'author' ),
							'data' => $user_data					
						)
					)
				),
				'labels' => (object) array(
					'label' => 'Customize the Labels',
					'type' => 'group',
					'advanced' => true,
					'data' => array(
						'singular_name' => (object) array(
							'label' => 'Singular Name',
							'type' => 'text',
							'description' => 'Name for one object of this post type. If not set, defaults to the value of the "Label" property.'
						),
						'add_new' => (object) array(
							'label' => 'Add New',
							'type' => 'text',
							'description' => 'This label is used for "Add New" submenu item. If not set, the default is "Add New" for both hierarchical and non-hierarchical posts.'
						),
						'add_new_item' => (object) array(
							'label' => 'Add New Item',
							'type' => 'text',
							'description' => 'This label is used for the "Add New" button. If not set, the default is "Add New Post" for non-hierarchical posts and "Add New Page" for hierarchical posts.'
						),
						'edit_item' => (object) array(
							'label' => 'Edit Item',
							'type' => 'text',
							'description' => 'This label is used when editing an individual post. If not set, the default is "Edit Post" for non-hierarchical posts and "Edit Page" for hierarchical posts.'
						),
						'new_item' => (object) array(
							'label' => 'New Item',
							'type' => 'text',
							'description' => 'This label is used when creating a new post. If not set, the default is "New Post" for non-hierarchical posts and "New Page" for hierarchical posts.'
						),
						'all_items' => (object) array(
							'label' => 'All Items',
							'type' => 'text',
							'description' => 'This label is used for the "All Items" submenu item. If not set, defaults to the value of the "Label" property.'
						),
						'view_item' => (object) array(
							'label' => 'View Item',
							'type' => 'text',
							'description' => 'This label is used when viewing an individual post. If not set, the default is "View Post" for non-hierarchical posts and "View Page" for hierarchical posts.'
						),
						'search_items' => (object) array(
							'label' => 'Search Items',
							'type' => 'text',
							'description' => 'This label is used for the "Search Posts" button. If not set, the default is "Search Posts" for non-hierarchical posts and "Search Pages" for hierarchical posts.'
						),
						'not_found' => (object) array(
							'label' => 'Not Found',
							'type' => 'text',
							'description' => 'This label is used when no posts are found. If not set, the default is "No posts found" for non-hierarchical posts and "No pages found" for hierarchical posts.'
						),
						'not_found_in_trash' => (object) array(
							'label' => 'Not Found in Trash',
							'type' => 'text',
							'description' => 'This label is used when no posts are found in the trash. If not set, the default is "No posts found in Trash" for non-hierarchical posts and "No pages found in Trash" for hierarchical posts.'
						),
						'parent_item_colon' => (object) array(
							'label' => 'Parent Item Colon',
							'type' => 'text',
							'description' => 'This label is used when displaying a post\'s parent. This string is not used on non-hierarchical posts. If post is hierarchical, and not set, the default is "Parent Page".'
						),
						'menu_name' => (object) array(
							'label' => 'Menu Name',
							'type' => 'text',
							'description' => 'This label is used as the text for the menu item. If not set, defaults to the value of the "Label" property.'
						)
					)
				),
				'options' => (object) array(
					'label' => 'Advanced Options',
					'type' => 'group',
					'advanced' => true,
					'data' => array(
						'public' => (object) array(
							'label' => 'Public',
							'type' => 'radio',
							'description' => 'This setting defines whether this post type is visible in the admin and front-end of your site. This property is a catchall and trickles down to define other properties ("Show UI", "Publicly Queryable", and "Exclude From Search") unless they are set individually. For complete customization, be sure to check the value of these other properties. <strong><span class="red">If the post type is not public, it\'s designated CPT-onomy will not be public.</span></strong>',
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
									),
								'false' => (object) array(
									'label' => 'False'
									)
							)
						),
						'hierarchical' => (object) array(
							'label' => 'Hierarchical',
							'type' => 'radio',
							'description' => 'This setting defines whether this post type is hierarchical, which allows a parent to be specified. In order to define a post\'s parent, the post type must support "Page Attributes".',
							'default' => 0,
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
								),
								'false' => (object) array(
									'label' => 'False'
								)
							)
						),
						'supports' => (object) array(
							'label' => 'Supports',
							'type' => 'checkbox',
							'description' => 'These settings let you register support for certain features. All features are directly associated with a functional area of the edit post screen.',
							'default' => array( 'title', 'editor' ),
							'data' => array(
								'title' => (object) array(
									'label' => 'Title'
									),
								'editor' => (object) array( // Content
									'label' => 'Editor'
									),
								'author' => (object) array(
									'label' => 'Author'
									),
								'thumbnail' => (object) array( // Featured Image) (current theme must also support post-thumbnails
									'label' => 'Thumbnail'
									),
								'excerpt' => (object) array(
									'label' => 'Excerpt'
									),
								'trackbacks' => (object) array(
									'label' => 'Trackbacks'
									),
								'custom-fields' => (object) array(
									'label' => 'Custom Fields'
									),
								'comments' => (object) array(
									'label' => 'Comments'
									),
								'revisions' => (object) array( // will store revisions
									'label' => 'Revisions'
									),
								'page-attributes' => (object) array( // template and menu order (hierarchical must be true)
									'label' => 'Page Attributes'
									),
								'post-formats' => (object) array(
									'label' => 'Post Formats'
									)
							)
						),
						'has_archive' => (object) array(
							'label' => 'Has Archive Page',
							'type' => 'text',
							'description' => 'This setting allows you to define/enable an archives page for this post type. <strong>The default setting is true so leave the field blank if you want an archives page (which will tell WordPress to use the post type name as the slug)</strong> or enter your own customized archive slug. Type <strong>false</strong> if you do not want an archives page.'
						),
						'taxonomies' => (object) array(
							'label' => 'Taxonomies',
							'type' => 'checkbox',
							'description' => 'This setting allows you to add support for pre-existing, registered <strong>non-CPT-onomy</strong> taxonomies.',
							'data' => $taxonomy_data
						),
						'show_ui' => (object) array(
							'label' => 'Show UI',
							'type' => 'radio',
							'description' => 'This setting defines whether to show the administration screens for managing this post type. <strong>If not set, defaults to the value of the "Public" property.</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
									),
								'false' => (object) array(
									'label' => 'False'
									)
							)
						),
						'show_in_menu' => (object) array(
							'label' => 'Show in Admin Menu',
							'type' => 'text',
							'description' => 'This setting allows you to customize the placement of this post type in the admin menu. <strong>Note that "Show UI" must be true.</strong> If you think the menu item is fine where it is, leave this field blank. Type <strong>false</strong> to remove from the menu, <strong>true</strong> to display as a top-level menu, or enter the name of a top-level menu (i.e. <strong>tools.php</strong> or <strong>edit.php?post_type=page</strong>) to add this item to it\'s submenu.'
						),
						'menu_position' => (object) array(
							'label' => 'Admin Menu Position',
							'type' => 'text',
							'validation' => 'digits',
							'description' => 'This setting defines the position in the menu order where the post type item should appear. If you think the menu item is fine where it is, leave this field blank. To move the menu item up or down, enter a custom menu position. <strong>If not set, post types are added below the "Comments" menu item.</strong> Visit the "Help" tab for a list of suggested menu positions.'
						),
						'menu_icon' => (object) array(
							'label' => 'Menu Icon',
							'type' => 'text',
							'description' => 'This setting defines the URL to the image you want to use as the menu icon for this post type in the admin menu. <strong>If not set, the menu will show the Posts icon.</strong>'
						),
						'show_in_nav_menus' => (object) array(
							'label' => 'Show in Nav Menus',
							'type' => 'radio',
							'description' => 'This setting enables posts of this type to appear for selection in the navigation menus. <strong>If not set, defaults to the value of the "Public" property.</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
									),
								'false' => (object) array(
									'label' => 'False'
									)
							)
						),
						'query_var' => (object) array(
							'label' => 'Query Var',
							'type' => 'text',
							'description' => 'This setting defines the query variable used to search for posts of this type. Type <strong>false</strong> to prevent queries or enter a custom query variable name. <strong>If not set, defaults to true and the variable will equal the name of the post type.</strong>'
						),
						'publicly_queryable' => (object) array(
							'label' => 'Publicly Queryable',
							'type' => 'radio',
							'description' => 'This setting defines whether queries for this post type can be performed on the front-end of your site. <strong>If not set, defaults to the value of the "Public" property.</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
									),
								'false' => (object) array(
									'label' => 'False'
									)
							)
						),
						'exclude_from_search' => (object) array(
							'label' => 'Exclude From Search',
							'type' => 'radio',
							'description' => 'This setting allows you to exclude posts with this post type from search results on your site. <strong>If not set, defaults to the OPPOSITE value of the "Public" property.</strong>',
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
									),
								'false' => (object) array(
									'label' => 'False'
									)
							)
						),
						'register_meta_box_cb' => (object) array(
							'label' => 'Register Meta Box Callback',
							'type' => 'text',
							'description' => 'This setting allows you to provide a callback function that will be called for setting up your post type\'s meta boxes. <strong>Enter the function\'s name only.</strong>'
						),
						'rewrite' => (object) array(
							'label' => 'Rewrite',
							'type' => 'group',
							'data' => array(
								'enable_rewrite' => (object) array(
									'label' => 'Enable Permalinks',
									'type' => 'radio',
									'description' => 'This setting allows you to activate custom permalinks for this post type. If <strong>true</strong>, WordPress will create permalinks and use the post type (or "Query Var", if set) as the slug. If <strong>false</strong>, this post type will have no custom permalink structure.',
									'default' => 1,
									'data' => array(
										'true' => (object) array(
											'label' => 'True'
										),
										'false' => (object) array(
											'label' => 'False'
										)
									)
								),
								'slug' => (object) array(
									'label' => 'Slug',
									'type' => 'text',
									'description' => 'If rewrite is enabled, you can customize your permalink rewrite even further by prepending posts with a custom slug. <strong>If not set, defaults to the post type.</strong>'
								),
								'with_front' => (object) array(
									'label' => 'With Front',
									'type' => 'radio',
									'description' => 'This setting defines whether to allow permalinks to be prepended with the permalink front base. Example: If your permalink structure is /blog/, then your links will be: <strong>true</strong> = \'/blog/news/\', <strong>false</strong> = \'/news/\'.',
									'default' => 1,
									'data' => array(
										'true' => (object) array(
											'label' => 'True'
										),
										'false' => (object) array(
											'label' => 'False'
										)
									)
								),
								'feeds' => (object) array(
									'label' => 'Feeds',
									'type' => 'radio',
									'description' => 'This setting defines whether this post type will have a feed for its posts. <strong>"Has Archive Page" needs to be set to true for the feeds to work.</strong> If not set, defaults to the value of the "Has Archive Page" property.',
									'data' => array(
										'true' => (object) array(
											'label' => 'True'
										),
										'false' => (object) array(
											'label' => 'False'
										)
									)
								),
								'pages' => (object) array(
									'label' => 'Pages',
									'type' => 'radio',
									'description' => 'This setting defines whether this post type\'s archive pages should be paginated. <strong>"Has Archive Page" needs to be set to true for the archive pages to work.</strong>',
									'default' => 1,
									'data' => array(
										'true' => (object) array(
											'label' => 'True'
										),
										'false' => (object) array(
											'label' => 'False'
										)
									)
								)
							)
						),
						'map_meta_cap' => (object) array(
							'label' => 'Map Meta Cap',
							'type' => 'radio',
							'description' => 'This setting defines whether to use the internal default meta capability handling.',
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
								),
								'false' => (object) array(
									'label' => 'False'
								)
							)						
						),
						'capability_type' => (object) array(
							'label' => 'Capability Type',
							'type' => 'text',
							'description' => 'This setting allows you to define a custom set of capabilities. This term will be used to build the read, edit, and delete capabilities. The "Capabilities" property below can be used to overwrite specific individual capabilities. If you want to pass multiple capability types to allow for alternative plurals, separate the types with a space or comma, e.g. \'story\', \'stories\'. <strong>If not set, the default is \'post\'.</strong>'
						),
						'capabilities' => (object) array(
							'label' => 'Capabilities',
							'type' => 'group',
							'data' => array(
								'read' => (object) array(
									'label' => 'Read',
									'type' => 'text',
									'description' => 'This capability controls whether objects of this post type can be read by the user.'
								),
								'read_post' => (object) array(
									'label' => 'Read Post',
									'type' => 'text',
									'description' => ''
								),
								'read_private_post' => (object) array(
									'label' => 'Read Private Post',
									'type' => 'text',
									'description' => 'This capability controls whether private objects of this post type can be read by the user.'
								),
								'edit_post' => (object) array(
									'label' => 'Edit Post',
									'type' => 'text',
									'description' => ''
								),
								'edit_posts' => (object) array(
									'label' => 'Edit Posts',
									'type' => 'text',
									'description' => 'This capability controls whether objects of this post type can be edited by the user.'
								),
								'edit_others_posts' => (object) array(
									'label' => 'Edit Others Posts',
									'type' => 'text',
									'description' => 'This capability controls whether objects of this type, owned by other users, can be edited by the user. If the post type does not support an author, then this will behave like edit_posts.'
								),
								'edit_private_posts' => (object) array(
									'label' => 'Edit Private Posts',
									'type' => 'text',
									'description' => 'This capability controls whether private objects of this post type can be edited by the user.'
								),
								'edit_published_posts' => (object) array(
									'label' => 'Edit Published Posts',
									'type' => 'text',
									'description' => 'This capability controls whether published objects of this post type can be edited by the user.'
								),
								'delete_post' => (object) array(
									'label' => 'Delete Post',
									'type' => 'text',
									'description' => ''
								),
								'delete_posts' => (object) array(
									'label' => 'Delete Posts',
									'type' => 'text',
									'description' => 'This capability controls whether objects of this post type can be deleted by the user.'
								),
								'delete_private_posts' => (object) array(
									'label' => 'Delete Private Posts',
									'type' => 'text',
									'description' => 'This capability controls whether private objects of this post type can be deleted by the user.'
								),
								'delete_others_posts' => (object) array(
									'label' => 'Delete Others Posts',
									'type' => 'text',
									'description' => 'This capability controls whether objects, owned by other users, can be deleted by the user. If the post type does not support an author, then this will behave like delete_posts.'
								),
								'delete_published_posts' => (object) array(
									'label' => 'Delete Published Posts',
									'type' => 'text',
									'description' => 'This capability controls whether published objects of this post type can be deleted by the user.'
								),
								'publish_posts' => (object) array(
									'label' => 'Publish Posts',
									'type' => 'text',
									'description' => 'This capability controls whether this user can publish objects of this post type.'
								)
							)
						),
						'can_export' => (object) array(
							'label' => 'Can Export',
							'type' => 'radio',
							'description' => 'This setting defines whether users can export posts with this post type.',
							'default' => 1,
							'data' => array(
								'true' => (object) array(
									'label' => 'True'
									),
								'false' => (object) array(
									'label' => 'False'
									)
							)
						),
						'permalink_epmask' => (object) array(
							'label' => 'Permalink Endpoint Bitmasks',
							'type' => 'text',
							'description' => 'This setting defines the rewrite endpoint bitmask used for posts with this post type. <strong>If not set, defaults to EP_PERMALINK.</strong>'
						)
					)
				),
				'deactivate' => array(
					'deactivate' => (object) array(
						'label' => 'Deactivate',
						'type' => 'checkbox',
						'description' => 'This setting allows you to deactive, or disable, your custom post type (and hide it from WordPress) while allowing you to save your settings for later use. <strong>Deactivating your custom post type does not delete its posts.</strong>',
						'data' => array( 
							'true' => (object) array(
								'label' => 'Deactivate this CPT but save my settings.'
							)
						)
					)
				)
			);
		}
	}
	
	/**
	 * This function is invoked when the plugin's option page is added to output the content.
	 *
	 * This function is invoked by the action 'admin_menu'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 */
	public function print_plugin_options_page() {
		global $cpt_onomies_manager;
		if ( current_user_can( 'manage_options' ) ) { ?>
	    
		    <div id="custom-post-type-onomies" class="wrap">
		
				<?php 
		        // will show the settings icon
		        screen_icon();
		        ?>
				
		       	<h2><?php echo CPT_ONOMIES_PLUGIN_NAME; ?></h2>
                
                <?php
				
				$new = ( isset( $_REQUEST[ 'edit' ] ) && strtolower( $_REQUEST[ 'edit' ] == 'new' ) ) ? true : false;
				$edit = ( !$new && isset( $_REQUEST[ 'edit' ] ) && ( ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( strtolower( $_REQUEST[ 'edit' ] ), $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) || ( post_type_exists( strtolower( $_REQUEST[ 'edit' ] ) ) ) ) ) ? strtolower( $_REQUEST[ 'edit' ] ) : false;
				$other = ( !$new && $edit && isset( $_REQUEST[ 'other' ] ) && post_type_exists( strtolower( $_REQUEST[ 'edit' ] ) ) && !$cpt_onomies_manager->is_registered_cpt( strtolower( $_REQUEST[ 'edit' ] ) ) ) ? true : false;
								
				if ( $new || $edit ) { ?>
                	<h3 class="editingtitle"><?php
										                    	
						if ( $new ) echo 'Creating a New Custom Post Type';
						else {
							
							if ( $other )
								$label = get_post_type_object( $edit )->label;
							
							else if ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $edit, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) )
								$label = $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $edit ][ 'label' ];
								
							echo 'Editing "' . $label . '"';
							
						}
						
					?>&nbsp;&nbsp;<a href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) ); ?>">&laquo; Back to CPT-onomies</a></h3><?php
                                        
				}
				
				// add deleted message
				if ( $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ 'cptdeleted' ] ) ) { ?>
                	<div id="message" class="updated"><p>The custom post type was deleted.</p></div>
				<?php }
				// add activated message
				else if ( $_REQUEST[ 'page' ] == CPT_ONOMIES_OPTIONS_PAGE && isset( $_REQUEST[ 'cptactivated' ] ) ) {
					$activated_cpt = strtolower( $_REQUEST[ 'cptactivated' ] );
					$label = ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $activated_cpt, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) ? $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $activated_cpt ][ 'label' ] : false;
					?>
                	<div id="message" class="updated"><p>The custom post type<?php if ( $label ) echo ' \'' . $label . '\''; ?> is now active.</p></div>
                
                <?php } ?>
                
                <div id="poststuff" class="metabox-holder has-right-sidebar">
					
                    <div id="side-info-column" class="inner-sidebar">
                        <?php do_meta_boxes( $this->options_page, 'side', array() ); ?>
                    </div>
                    
                    <div id="post-body">
						<div id="post-body-content">
                        
                        	<form<?php if ( $new || $edit ) echo ' id="' . CPT_ONOMIES_DASH . '-edit-cpt-form"'; ?> method="post" action="options.php">
                    
								<?php
		                        
		                        // Output nonce, action, and option_page fields
		                        if ( $new || $edit ) {
									if ( $other ) settings_fields( CPT_ONOMIES_OPTIONS_PAGE . '-other-custom-post-types' );
									else settings_fields( CPT_ONOMIES_OPTIONS_PAGE . '-custom-post-types' );
								}
								
								wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
								wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
                
								do_meta_boxes( $this->options_page, 'normal', array() );
								do_meta_boxes( $this->options_page, 'advanced', array() );
								
								if ( $new || $edit )
									submit_button( 'Save Changes', 'primary', 'save_changes', false );
								
								?>
                                
                          	</form>
                            
                      	</div>
                   	</div>
                    
               	</div>
                
	      	</div>
            
	    <?php }
	}
	
	/**
	 * This function is invoked when a meta box is added to plugin's option page.
	 * This 'callback' function prints the html for the meta box.
	 *
	 * This function is invoked by the action 'admin_init'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager, $user_ID
	 * @param array $post - information about the current post, which is empty because there is no current post on a settings page
	 * @param array $metabox - information about the metabox
	 */
	public function print_plugin_options_meta_box( $post, $metabox ) {
		global $cpt_onomies_manager, $user_ID;
		if ( current_user_can( 'manage_options' ) ) {
			switch( $metabox[ 'args' ] ) {
					
				case 'about':
					?>
					<p><strong><a href="<?PHP echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>" title="<?php echo CPT_ONOMIES_PLUGIN_NAME; ?>" target="_blank"><?php echo CPT_ONOMIES_PLUGIN_NAME; ?></a></strong></p>
	                <p><strong>Version:</strong> <?php echo CPT_ONOMIES_VERSION; ?><br />
	                <strong>Author:</strong> <a href="http://www.rachelcarden.com" title="Rachel Carden" target="_blank">Rachel Carden</a></p>
					<?php
					break;
					
				case 'key':
					?>
                    <p class="inactive"><img src="<?php echo CPT_ONOMIES_URL; ?>images/inactive.png" /><span>This CPT is inactive.</span></p>
                    <p class="attention"><img src="<?php echo CPT_ONOMIES_URL; ?>images/attention.png" /><span>This CPT is not registered.</span></p>
                    <p class="working"><img src="<?php echo CPT_ONOMIES_URL; ?>images/working.png" /><span>This CPT is registered and working.</span></p>
					<?php
					break;
					
				case 'promote':
					?>
	                <ul>
	                	<li><a href="<?php echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>" title="Give the plugin a good rating" target="_blank">Give the plugin a good rating</a></li>
	                    <li><a href="https://twitter.com/#!/bamadesigner" title="bamadesigner on Twitter" target="_blank">Follow me on Twitter</a></li>
	                    <li class="donate">
                        	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                            	<input type="hidden" name="cmd" value="_s-xclick">
                                <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHPwYJKoZIhvcNAQcEoIIHMDCCBywCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYCOocE25GGcuu/EALPS+5iJTydBpMC60WXCFlWiX401e2yZ1WZ3eJNErW4/DmFh62h5jexopJE7sPYrXo3AFv9W3w3RsCT8Z14ByBtmNgmu8eG64iK77vNR+9ihVl4SsdO44f9LrYx17TqET7DtX+H1H6CEikW+14W8OV/7G3odiDELMAkGBSsOAwIaBQAwgbwGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIurjqyX1h1jeAgZhgG6HnpnkH1g/F1jtSf/FVmQkQKpe54+FExUhuC+WJOdpqLcRns18YFQh6aWf+hiWQnzrabriKXzxmYPY9/7e/JH8IFBl88e9J9vhOgAebFlhmDiO5VmYaFz32RQZjG+0Txp7rK+gCnboYAKYg+UlzSWWnPFXj0FP9Q+dyG0iwWgxf3mBtL6PrdBPdFabGN+tRMEoR5HzpgKCCA4cwggODMIIC7KADAgECAgEAMA0GCSqGSIb3DQEBBQUAMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbTAeFw0wNDAyMTMxMDEzMTVaFw0zNTAyMTMxMDEzMTVaMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEAwUdO3fxEzEtcnI7ZKZL412XvZPugoni7i7D7prCe0AtaHTc97CYgm7NsAtJyxNLixmhLV8pyIEaiHXWAh8fPKW+R017+EmXrr9EaquPmsVvTywAAE1PMNOKqo2kl4Gxiz9zZqIajOm1fZGWcGS0f5JQ2kBqNbvbg2/Za+GJ/qwUCAwEAAaOB7jCB6zAdBgNVHQ4EFgQUlp98u8ZvF71ZP1LXChvsENZklGswgbsGA1UdIwSBszCBsIAUlp98u8ZvF71ZP1LXChvsENZklGuhgZSkgZEwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tggEAMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAgV86VpqAWuXvX6Oro4qJ1tYVIT5DgWpE692Ag422H7yRIr/9j/iKG4Thia/Oflx4TdL+IFJBAyPK9v6zZNZtBgPBynXb048hsP16l2vi0k5Q2JKiPDsEfBhGI+HnxLXEaUWAcVfCsQFvd2A1sxRr67ip5y2wwBelUecP3AjJ+YcxggGaMIIBlgIBATCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwCQYFKw4DAhoFAKBdMBgGCSqGSIb3DQEJAzELBgkqhkiG9w0BBwEwHAYJKoZIhvcNAQkFMQ8XDTEyMDEyMjAxNTcxN1owIwYJKoZIhvcNAQkEMRYEFLM+7A/BIfDbfoMNsPh6i2wD96IcMA0GCSqGSIb3DQEBAQUABIGAIKir1H3x31uu1CkCUzFVn0+IylEMO8CERoGxmcUZhFtQPk2m1tZbKi21jup8Io9DAqUcXf0qrgtJFMk7aUkx1qb68gqJsgRakLIoeqezJ+NSGs2zusRBlU9kdwMzJdl4bfkTb18XPcP4iROe8GUqrcUrtjyMuI2jRbKclTtJ20g=-----END PKCS7-----">
                                <input class="donatebutton" type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><div style="float:left; padding:6px 0 0 2px;"><a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_flow&SESSION=LSa9VMv8-O9RLhb_Ns4y4Hdiw3DybZ9djrhM-NWy1Xpc4PmyJ9X_2zklJ5W&dispatch=5885d80a13c0db1f8e263663d3faee8db2b24f7b84f1819343fd6c338b1d9d60" target="_blank">a few bucks</a></div>
                                <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                         	</form>
                       	</li>
	               	</ul>
	                <?php
					break;
					
				case 'support':
					?>
	                <p>If the 'Help' tab doesn't answer your question, visit the <a href="<?php echo CPT_ONOMIES_PLUGIN_DIRECTORY_URL; ?>?forum_id=10" title="CPT-onomies support forums" target="_blank">the plugin's support forums</a> or <a href="http://rachelcarden.com/cpt-onomies/" title="Visit my web site" target="_blank">my web site</a>.</p>
	                <p>CPT-onomies tries hard to hook into Wordpress core functions, and take the burden of excess programming away from the user, but sometimes this just can't be done. <a href="http://rachelcarden.com/cpt-onomies/documentation" title="Check out the CPT-onomy documentation" target="_blank">Check out the CPT-onomy documentation</a> to see which WordPress functions work and when you'll need to access the plugin's CPT-onomy class.</p>
	                <p>If you notice any bugs or problems with the plugin, <a href="http://rachelcarden.com/contact/" title="Contact me to report bugs and problems" target="_blank">please let me know</a>.</p>
					<?php
	                break;
				
				case 'custom_post_types':
				case 'other_custom_post_types':
					$other = ( $metabox[ 'args' ] == 'other_custom_post_types' ) ? true : false;
					
					if ( $other ) { ?>
                    	<p>'If you're using a theme, or another plugin, that creates a custom post type, you can still register these "other" custom post types as CPT-onomies. <span class="description">You cannot, however, manage the actual custom post type. Sorry, but that's up to the plugin and/or theme.</span></p>
                    <?php }
					else { ?>
                    	<p>If you'd like to create a custom post type, but don't want to mess with code, you've come to the right place. Customize every setting, or just give us a name, and we'll take care of the rest. <span class="description">For more information, like how to create a CPT-onomy, visit the 'Help' tab.</span></p>
                  	<?php }
					
					// custom post types created by this plugin
					if ( !$other )
						$post_type_objects = $cpt_onomies_manager->user_settings[ 'custom_post_types' ];
					
					// get other (non-builtin) custom post types
					else {
						$post_type_objects = get_post_types( array( '_builtin' => false ), 'objects' );
						foreach( $post_type_objects as $post_type => $CPT ) {
							if ( $cpt_onomies_manager->is_registered_cpt( $post_type ) )
								unset( $post_type_objects[ $post_type ] );
							// gather the plugin settings
							else if ( is_array( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( $post_type, $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) {
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'attach_to_post_type' ] ) )
									$post_type_objects[ $post_type ]->attach_to_post_type = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'attach_to_post_type' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'has_cpt_onomy_archive' ] ) )
									$post_type_objects[ $post_type ]->has_cpt_onomy_archive = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'has_cpt_onomy_archive' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'restrict_user_capabilities' ] ) )
									$post_type_objects[ $post_type ]->restrict_user_capabilities = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $post_type ][ 'restrict_user_capabilities' ];
							}
						}
					}

					// print the table	
					?>
                    <table class="manage_custom_post_type_onomies<?php if ( $other ) echo ' other'; ?>" cellpadding="0" cellspacing="0" border="0">
                        <thead>
                            <tr valign="bottom">
                            	<th class="status">Status</th>
                                <th class="label">Label</th>
                                <th class="name">Name</th>
                                <th class="public">Public</th>
                                <th class="registered_custom_post_type_onomy">Registered<br />CPT-onomy?</th>
                                <th class="attached_to">CPT-onomy<br />Attached to</th>
                                <th class="ability">Ability to Assign Terms</th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php if ( empty( $post_type_objects ) ) { ?>
                            	<tr valign="top">
                                	<td class="none" colspan="7"><?php
                                    	
										if ( $other ) echo 'There are no "other" custom post types.';
										else echo 'What are you waiting for? Custom post types are pretty awesome and you don\'t have to touch one line of code.';
										
									?></td>
                                </tr>
                            <?php }
							else {
								foreach( $post_type_objects as $post_type => $CPT ) {
	                                if ( !is_object( $CPT ) ) $CPT = (object) $CPT;
									if ( !empty( $post_type ) && ( !isset( $CPT->name ) || empty( $CPT->name ) ) ) $CPT->name = $post_type;
									else if ( empty( $post_type ) && isset( $CPT->name ) && !empty( $CPT->name ) ) $post_type = $CPT->name;
									
									// make sure post type and label exist
									if ( !empty( $post_type ) && !( !isset( $CPT->label ) || empty( $CPT->label ) ) ) {
										
										$inactive_cpt = isset( $CPT->deactivate ) ? true : false;
										
										$is_registered_cpt = ( !$inactive_cpt && post_type_exists( $post_type ) && $cpt_onomies_manager->is_registered_cpt( $post_type ) ) ? true : false;
										$is_registered_cpt_onomy = ( !$inactive_cpt && taxonomy_exists( $post_type ) && $cpt_onomies_manager->is_registered_cpt_onomy( $post_type ) ) ? true : false;
										
										$attention_cpt = ( !$inactive_cpt && ( ( !$other && !$is_registered_cpt ) || ( $other && $is_registered_cpt ) ) ) ? true : false;
										$attention_cpt_onomy = ( !$inactive_cpt && ( ( $attention_cpt ) || ( isset( $CPT->attach_to_post_type ) && !$is_registered_cpt_onomy ) ) ) ? true : false;
										
										$message = NULL;
										if ( $attention_cpt ) {
											$builtin = get_post_types( array( '_builtin' => true ), 'objects' );
											$message = 'The custom post type, \'' . $CPT->label . '\', is not registered because ';
											// builtin conflict
											if ( array_key_exists( $post_type, $builtin ) )
												$message .= 'the built-in WordPress post type, \'' . $builtin[ $post_type ]->label . '\' is already registered under the name \'' . $post_type . '\'. Sorry, but WordPress wins on this one. You\'ll have to change the post type name if you want to get \'' . $CPT->label . '\' up and running.';
											// "other" conflict
											else
												$message .= 'another custom post type with the same name already exists. This other custom post type is probably setup in your theme or another plugin. Check out the \'Other Custom Post Types\' section to see what else has been registered.';
										}										
										else if ( $attention_cpt_onomy )
											$message = 'This CPT-onomy did not register because another taxonomy with the same name already exists. If you would like this CPT-onomy to work, please remove the conflicting taxonomy.';
																												
										?>
										<tr valign="top"<?php if ( $inactive_cpt ) echo ' class="inactive"'; else if ( $attention_cpt ) echo ' class="attention"'; ?>>
											<td class="status">&nbsp;</td>
											<td class="label"><?php
																					
												// url args
												$args = array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => $post_type );
												if ( $other ) $args[ 'other' ] = 1;
												
												// edit url
												$edit_url = esc_url( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
												
												// activate url
												$activate_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'activate' => $post_type, '_wpnonce' => wp_create_nonce( 'activate-cpt-' . $post_type ) ), admin_url( 'options-general.php' ) ), 'activate-cpt-' . $post_type );
												
												// delete url
												$delete_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'delete' => $post_type, '_wpnonce' => wp_create_nonce( 'delete-cpt-' . $post_type ) ), admin_url( 'options-general.php' ) ), 'delete-cpt-' . $post_type );
												
												// view url
												$view_url = esc_url( add_query_arg( array( 'post_type' => $post_type ), admin_url( 'edit.php' ) ) );
												
												?>
												<span class="label"><a href="<?php echo $edit_url; ?>"><?php echo $CPT->label; ?></a></span>
												<div class="row-actions"><span class="edit"><a href="<?php echo $edit_url; ?>" title="Edit this custom post type">Edit</a></span><?php if ( $inactive_cpt ) echo ' | <a href="' . $activate_url . '" title="Active this custom post type">Activate this CPT</a><br />'; else if ( !$other ) echo ' | '; ?><?php if ( !$other ) echo '<span class="trash"><a class="delete_cpt_onomy_custom_post_type" title="Delete this custom post type" href="' . $delete_url . '">Delete</a></span>'; ?> | <span class="view"><a href="<?php echo $view_url; ?>" title="View posts">View posts</a></span><?php if ( $attention_cpt ) echo '<span class="attention"><a class="show_cpt_message" href="' . $edit_url . '" title="Find out why this custom post type is not registered" alt="' . $message . '">Find out why this<br />CPT is not registered.</a></span>'; ?></div>
											</td>
											<td class="name"><?php echo $post_type; ?></td>
											<td class="public"><?php if ( $CPT->public ) echo 'Yes'; else echo 'No'; ?></td>
											<td class="registered_custom_post_type_onomy<?php if ( $attention_cpt && $attention_cpt_onomy ) echo ' attention'; else if ( $attention_cpt_onomy ) echo ' error'; else if ( $is_registered_cpt_onomy ) echo ' working'; ?>"><?php
											
												if ( $inactive_cpt && isset( $CPT->attach_to_post_type ) ) echo 'No, because this CPT is inactive.<br /><a href="' . $activate_url . '" title="Activate this custom post type">Activate this CPT</a>';
												else if ( $attention_cpt && $attention_cpt_onomy ) echo 'No, because this CPT is not registered.<br /><a class="show_cpt_message" href="' . $edit_url . '" title="Find out why this custom post type is not registered" alt="' . $message . '">Find out why</a>';
												else if ( $attention_cpt_onomy ) echo 'This CPT-onomy is not registered due to a taxonomy conflict.<br /><a class="show_cpt_message" href="' . $edit_url . '" title="Find out why this CPT-onomy is not registered" alt="' . $message . '">Find out why</a>';
												else if ( $is_registered_cpt_onomy ) echo 'Yes';
												else echo 'No';
												
											?></td>
											<td class="attached_to"><?php
											
												$text = NULL;
												if ( ( !$inactive_cpt && !$attention_cpt_onomy ) && $is_registered_cpt_onomy ) {
													$taxonomy = get_taxonomy( $post_type );
													if ( isset( $taxonomy->object_type ) ) {
														foreach( $taxonomy->object_type as $index => $object_type ) {
															if ( post_type_exists( $object_type ) )
																$text .= '<a href="' . admin_url() . 'edit.php?post_type=' . $object_type. '">' . get_post_type_object( $object_type )->label . '</a><br />';
														}
													}								
												}
												if ( empty( $text ) ) echo '&nbsp;';
												else echo $text;
											
											?></td>
											<td class="ability"><?php
											
												$text = NULL;
												if ( !$attention_cpt_onomy && $is_registered_cpt_onomy ) {
													// get roles
													$wp_roles = new WP_Roles();
													
													if ( isset( $CPT->restrict_user_capabilities ) ) {
														foreach ( $wp_roles->role_names as $role => $name ) {
															if ( in_array( $role, $CPT->restrict_user_capabilities ) )
																$text .= $name . '<br />';								
														}
													}
													// everyone with the capability can
													else {
														$text = 'All user roles';
													}
												}
												if ( empty( $text ) ) echo '&nbsp;';
												else echo $text;
											
											?></td>                            
										</tr>
										
									<?php }
								}
							}
							if ( !$other ) { ?>
                            	<tr valign="top">
                                	<td class="add" colspan="6"><a href="<?php echo esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'edit' => 'new' ), admin_url( 'options-general.php' ) ) ); ?>">Add a new custom post type</a></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                    <?php		
					break;
					
				case 'delete_custom_post_type':
					$edit = $_REQUEST[ 'edit' ];
					$delete_url = esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE, 'delete' => $edit, '_wpnonce' => wp_create_nonce( 'delete-cpt-' . $edit ) ), admin_url( 'options-general.php' ) ), 'delete-cpt-' . $edit );
					?>
                    <p><strong>FYI:</strong> Deleting your custom post type <strong>DOES NOT</strong> delete the actual posts. They'll be waiting for you if you decide to register this post type again. Just make sure you use the same name.</p>
                    <p><strong>HOWEVER</strong>, there is no "undo" and, once you click "Delete", all of your settings will be gone.</p>
                    <a class="delete_cpt_onomy_custom_post_type" href="<?php echo $delete_url; ?>" title="Delete this custom post type">Delete this custom post type</a>
                    <?php
					break;
					
				case 'edit_custom_post_type':
					$new = ( isset( $_REQUEST[ 'edit' ] ) && strtolower( $_REQUEST[ 'edit' ] == 'new' ) ) ? true : false;
					$edit = ( !$new && isset( $_REQUEST[ 'edit' ] ) && ( ( isset( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( strtolower( $_REQUEST[ 'edit' ] ), $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) ) || ( post_type_exists( strtolower( $_REQUEST[ 'edit' ] ) ) ) ) ) ? strtolower( $_REQUEST[ 'edit' ] ) : false;
					$other = ( !$new && $edit && isset( $_REQUEST[ 'other' ] ) && post_type_exists( strtolower( $_REQUEST[ 'edit' ] ) ) && !$cpt_onomies_manager->is_registered_cpt( strtolower( $_REQUEST[ 'edit' ] ) ) ) ? true : false;
					
					$CPT = array();
					if ( $edit ) {
						
						// other post type
						if ( $other ) {
							$CPT = get_post_type_object( $edit );
							$CPT->other = true;
							if ( is_array( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) && array_key_exists( $edit, $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ] ) ) {
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'attach_to_post_type' ] ) )
									$CPT->attach_to_post_type = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'attach_to_post_type' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'has_cpt_onomy_archive' ] ) )
									$CPT->has_cpt_onomy_archive = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'has_cpt_onomy_archive' ];
								if ( isset( $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'restrict_user_capabilities' ] ) )
									$CPT->restrict_user_capabilities = $cpt_onomies_manager->user_settings[ 'other_custom_post_types' ][ $edit ][ 'restrict_user_capabilities' ];
							}
						}
						
						// get saved data
						else if ( is_array( $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) && array_key_exists( $edit, $cpt_onomies_manager->user_settings[ 'custom_post_types' ] ) )
							$CPT = (object) $cpt_onomies_manager->user_settings[ 'custom_post_types' ][ $edit ];
						
					}
					
					if ( $other ) { ?>
                    	<div class="edit_custom_post_type_message information">
                    		<p>This custom post type is probably setup in your theme, or another plugin, but you can still register it for use as a CPT-onomy. You cannot, however, manage the actual custom post type. Sorry, but that's up to the plugin and/or theme.</p>
                       	</div>
                  	<?php }
					
					// print errors
					if ( $edit ) {
					
						$inactive_cpt = isset( $CPT->deactivate ) ? true : false;
										
						$is_registered_cpt = ( !$inactive_cpt && post_type_exists( $edit ) && $cpt_onomies_manager->is_registered_cpt( $edit ) ) ? true : false;
		                $is_registered_cpt_onomy = ( !$inactive_cpt && taxonomy_exists( $edit ) && $cpt_onomies_manager->is_registered_cpt_onomy( $edit ) ) ? true : false;
		                                
						$attention_cpt = ( !$inactive_cpt && ( ( !$other && !$is_registered_cpt ) || ( $other && $is_registered_cpt ) ) ) ? true : false;
						$attention_cpt_onomy = ( !$inactive_cpt && ( ( $attention_cpt ) || ( isset( $CPT->attach_to_post_type ) && !$is_registered_cpt_onomy ) ) ) ? true : false;
						
						?>
	                    
	                    <div class="edit_custom_post_type_message<?php if ( $inactive_cpt ) echo ' inactive'; else if ( $attention_cpt || $attention_cpt_onomy ) echo ' attention'; ?>"><?php
											
							if ( $inactive_cpt )
								echo '<p>This custom post type is currently inactive.</p>';
							else if ( $attention_cpt ) {
								$builtin = get_post_types( array( '_builtin' => true ), 'objects' );
								echo '<p>This custom post type is not registered because ';
								
									// builtin conflict
									if ( array_key_exists( $edit, $builtin ) )
										echo 'the built-in WordPress post type, \'' . $builtin[ $edit ]->label . '\' is already registered under the name \'' . $edit . '\'. Sorry, but WordPress wins on this one. You\'ll have to change the post type name if you want to get \'' . $CPT->label . '\' up and running.';
									// other conflict
									else
										echo 'another custom post type with the same name already exists. This other custom post type probably is setup in your theme or another plugin. <a href="' . esc_url( add_query_arg( array( 'page' => CPT_ONOMIES_OPTIONS_PAGE ), admin_url( 'options-general.php' ) ) ) . '#cpt-onomies-other-custom-post-types">Check out the \'Other Custom Post Types\'</a> to see what else has been registered.';
								
								echo '</p>';
							}
							else if ( $attention_cpt_onomy )
								echo '<p>This custom post type\'s CPT-onomy is not registered because another taxonomy with the same name already exists. If you would like the CPT-onomy to work, please remove the conflicting taxonomy.</p>';
							else
								echo '<p>This custom post type is registered and working.</p>';
						
						?></div>
                        
                  	<?php }
					
					// provide the original "name" for AJAX testing and back-end validation                    
					?><input type="hidden" id="<?php echo CPT_ONOMIES_DASH . '-custom-post-type-original-name'; ?>" name="<?php echo CPT_ONOMIES_UNDERSCORE . '_custom_post_types[' . $edit . '][original_name]'; ?>" value="<?php if ( $edit && !$other && !empty( $CPT ) ) echo $edit; ?>" /><?php
					
					// this allows each user to have a preference on whether to show the "advanced" tables
					$show_edit_tables = get_user_option( CPT_ONOMIES_UNDERSCORE . '_show_edit_tables', $user_ID );
					if ( !is_array( $show_edit_tables ) ) $show_edit_tables = array();
																													
					// get the properties
					$cpt_properties = $this->get_plugin_options_page_cpt_properties();
					
					foreach( $cpt_properties as $section => $properties ) {
						// they can only edit 'cpt_as_taxonomy'
						if ( !$other || ( $other && $section == 'cpt_as_taxonomy' ) ) { ?>
                    	
	                        <table class="edit_custom_post_type <?php echo $section; ?><?php if ( in_array( $section, $show_edit_tables ) ) echo ' show'; ?>" cellpadding="0" cellspacing="0" border="0">
								<tbody>
	                            	<?php if ( isset( $properties->type ) && $properties->type == 'group' && isset( $properties->data ) ) { ?>
	                                	<tr>
	                                    	<td class="label"><?php echo $properties->label; ?></td>
	                                        <td class="group<?php if ( isset( $properties->advanced ) ) echo ' advanced'; ?>">
	                                        	<table cellpadding="0" cellspacing="0" border="0">
													<?php foreach( $properties->data as $property_key => $property ) { ?>
														<tr>
															<td class="label"><?php echo $property->label; ?></td>
															<td class="field"><?php $this->print_plugin_options_edit_custom_post_type_field( $edit, $CPT, $property, $property_key ); ?></td>
														</tr>
													<?php } ?>
												</table>
	                                      	</td>
	                                  	</tr>
	                            	<?php }
									else {
										foreach( $properties as $property_key => $property ) { ?>
		                                	<tr>
												<td class="label"><?php echo $property->label; ?></td>
												<td class="field"><?php $this->print_plugin_options_edit_custom_post_type_field( $edit, $CPT, $property, $property_key ); ?></td>
											</tr>
	                               		<?php }
									} ?>
								</tbody>
							</table>
                        
                 		<?php }
					}
					break;
					
			}
		}
	}
	
	/**
	 * This function is invoked on the edit screen and prints the html for the form fields.
	 *
	 * @since 1.0
	 * @param $cpt_key - the name for the custom post type we're editing
	 * @param $CPT - saved information for the custom post type we're editing
	 * @param object $property - info pulled from $this->get_plugin_options_page_cpt_properties() about this specific field
	 * @param string $property_key - name for property so information can be pulled from $property_info object.
	 * @param string $property_parent_key - allows for pulling property info from within an array.
	 */
	public function print_plugin_options_edit_custom_post_type_field( $cpt_key, $CPT, $property, $property_key, $property_parent_key=NULL ) {
		if ( current_user_can( 'manage_options' ) ) {
			
			$new = empty( $CPT ) ? true : false;
			$cpt_key = ( $new ) ? 'new_custom_post_type' : $cpt_key;
			
			// create field name
			$field_name = CPT_ONOMIES_UNDERSCORE . '_';
				if ( isset( $CPT->other ) ) $field_name .= 'other_';
			$field_name .= 'custom_post_types[' . $cpt_key . ']';			
			if ( isset( $property_parent_key ) ) $field_name .= '[' . $property_parent_key . ']';			
			$field_name .= '[' . $property_key . ']';
						
			switch( $property->type ) {
				
				case 'group':
					if ( isset( $property->data ) ) { ?>
                    	<table cellpadding="0" cellspacing="0" border="0">
                        	<?php foreach( $property->data as $subproperty_key => $subproperty ) { ?>
                            	<tr>
                                	<td class="label"><?php echo $subproperty->label; ?></td>
									<td class="field"><?php $this->print_plugin_options_edit_custom_post_type_field( $cpt_key, $CPT, $subproperty, $subproperty_key, $property_key ); ?></td>
                               	</tr>
                          	<?php } ?>
                      	</table>
                  	<?php }
					break;
					
				case 'text':
				case 'textarea':
					
					// get saved value
					$saved_property_value = NULL;
					if ( !$new ) {
						if ( isset( $property_parent_key ) && isset( $CPT->$property_parent_key ) ) {
							$property_parent = $CPT->$property_parent_key;
							if ( isset( $property_parent[ $property_key ] ) && !empty( $property_parent[ $property_key ] ) )
								$saved_property_value = $property_parent[ $property_key ];
						}
						else if ( isset( $CPT->$property_key ) ) $saved_property_value = $CPT->$property_key;
					}
					else if ( isset( $property->default ) ) $saved_property_value = $property->default;
					
					if ( is_array( $saved_property_value ) && !empty( $saved_property_value ) ) $saved_property_value = esc_attr( strip_tags( implode( ', ', $saved_property_value ) ) );
					else if ( !empty( $saved_property_value ) ) $saved_property_value = esc_attr( strip_tags( $saved_property_value ) );
										
					if ( $property->type == 'text' ) { ?>
                    	
                        <input<?php if ( isset( $property->fieldid ) ) echo ' id="' . $property->fieldid . '"'; ?><?php if ( isset( $property->validation ) ) echo ' class="' . $property->validation . '"'; ?> type="text" name="<?php echo $field_name; ?>" value="<?php if ( !empty( $saved_property_value ) ) echo $saved_property_value; ?>"<?php if ( isset( $property->readonly ) && $property->readonly ) echo ' readonly="readonly"'; ?> />
					
					<?php }
					else if ( $property->type == 'textarea' ) { ?>
                    	
                        <textarea<?php if ( isset( $property->fieldid ) ) echo ' id="' . $property->fieldid . '"'; ?><?php if ( isset( $property->validation ) ) echo ' class="' . $property->validation . '"'; ?> name="<?php echo $field_name; ?>"><?php if ( !empty( $saved_property_value ) ) echo $saved_property_value; ?></textarea>
                   	
					<?php }
					
					if ( isset( $property->description ) && !empty( $property->description ) ) echo '<span class="description">' . $property->description . '</span>';
					
					break;
					
				case 'radio':
				case 'checkbox':
				
					if ( !empty( $property->data ) ) { ?>
									
						<table class="<?php echo $property->type; ?>" cellpadding="0" cellspacing="0" border="0">
							<?php
							
							$td = 1;
							$index = 1;
							foreach( $property->data as $data_name => $data ) {
							
								if ( $data_name == 'true' ) $data_name = 1;
								else if ( $data_name == 'false' ) $data_name = 0;	
																	
								if ( $td == 1 ) echo '<tr>';
													
								$is_default = false;
								if ( isset( $property->default ) && is_array( $property->default ) && in_array( $data_name, $property->default ) )
									$is_default = true;
								else if ( isset( $property->default ) && $data_name == $property->default )
									$is_default = true;
																					
								$is_set = false;
								if ( !$new ) {
									if ( isset( $property_parent_key ) && isset( $CPT->$property_parent_key ) ) {
										$property_parent = $CPT->$property_parent_key;
										if ( isset( $property_parent[ $property_key ] ) && is_array( $property_parent[ $property_key ] ) && in_array( $data_name, $property_parent[ $property_key ] ) )
											$is_set = true;
										else if ( isset( $property_parent[ $property_key ] ) && $data_name == $property_parent[ $property_key ] )
											$is_set = true;
									}
									else if ( isset( $CPT->$property_key ) && is_array( $CPT->$property_key ) && in_array( $data_name, $CPT->$property_key ) )
										$is_set = true;
									else if ( isset( $CPT->$property_key ) && $data_name == $CPT->$property_key )
										$is_set = true;
									else if ( isset( $CPT->other ) && !isset( $CPT->$property_key ) && $is_default )
										$is_set = true;
								}
								// set the defaults
								else if ( $is_default )
									$is_set = true;
												
								?><td<?php if ( $index == count( $property->data ) && $td == 1 ) echo ' colspan="2"'; ?>><label><input<?php if ( isset( $property->validation ) ) echo ' class="' . $property->validation . '"'; ?> type="<?php echo $property->type; ?>" name="<?php echo $field_name; if ( $property->type == 'checkbox' ) { ?><?php if ( count( $property->data ) > 1 ) echo '[]'; ?><?php } ?>" value="<?php echo $data_name; ?>"<?php checked( $is_set ); ?> /><?php if ( $is_default ) { ?><strong><?php } echo $data->label; if ( $is_default ) { ?></strong><?php } ?></label></td><?php
								
								if ( $td == 1 )
									$td = 2;
								else if ( $td == 2 ) {
									$td = 1;
									echo '</tr>';
								}
								
								$index++;
									
							}
							
							if ( isset( $property->description ) && !empty( $property->description ) ) { ?>
                            	<tr><td<?php if ( count( $property->data ) > 1 ) echo ' colspan="2"'; ?>><span class="description"><?php echo $property->description; ?><?php
                                
									if ( $property->type == 'radio' && !isset( $property->default ) ) echo ' <span class="reset_property">Reset property</span>';
								
								?></span></td></tr>
							<?php }
								
							?>
											
						</table>
										
					<?php }
					break;
				
			}
			
		}
	}
	
	/**
	 * If a CPT-onomy is attached to a post type, the plugin adds a meta box to
	 * the post edit screen so the user can assign/manage the taxonomy's terms.
	 *
	 * This function is invoked by the action 'add_meta_boxes'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param string $post_type - the current post's post type
	 * @param object $post - the current post's information
	 */
	public function add_cpt_onomy_meta_boxes( $post_type, $post ) {
		global $cpt_onomies_manager;
		foreach( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy => $tax ) {
			// make sure its public and a registered CPT-onomy
			if ( $tax->public == true && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) ) {
				add_meta_box( CPT_ONOMIES_DASH.'-'.$taxonomy, $tax->label, array( &$this, 'print_cpt_onomy_meta_box' ), $post_type, 'side', 'core', array( 'taxonomy' => $taxonomy ) );
								
			}
		}
	}
	
	/**
	 * This function is invoked when a CPT-onomy meta box is attached to a post type's edit post screen.
	 * This 'callback' function prints the html for the meta box.
	 *
	 * The meta box consists of a checklist that allows the user to assign/manage the taxonomy's terms.
	 * This function mimics a meta box for an ordinary custom taxonomy.
	 *
	 * This code mimics the WordPress function post_categories_meta_box().
	 *
	 * This function is invoked by the action 'add_meta_boxes'.
	 *
	 * @since 1.0
	 * @param object $post - the current post's information
	 * @param array $box - information about the metabox
	 */
	public function print_cpt_onomy_meta_box( $post, $metabox ) {	
		
		// add nonce
		wp_nonce_field( 'assigning_custom_post_type_onomy_taxonomy_relationships', CPT_ONOMIES_UNDERSCORE . '_nonce' );
		
		$defaults = array( 'taxonomy' => NULL );
		if ( !isset( $metabox[ 'args' ] ) || !is_array( $metabox[ 'args' ] ) )
			$args = array();
		else
			$args = $metabox['args'];
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		$tax = get_taxonomy( $taxonomy );

		// add field for testing "editability" when we save the information ?>
        <input type="hidden" name="assigning_<?php echo CPT_ONOMIES_UNDERSCORE; ?>_<?php echo $taxonomy; ?>_relationships" value="1" />
		
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3">Most Used</a></li>
			</ul>
	
			<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display:none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
					<?php $popular_ids = wp_popular_terms_checklist( $taxonomy ); ?>
				</ul>
			</div>
	
			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
				<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
					<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids, 'walker' => new CPTonomy_Walker_Terms_Checklist() ) ); ?>
				</ul>
			</div>
		</div>
		<?php
	}
	
	/**
	 * This function is run when any post is saved.
	 *
	 * This function is invoked by the action 'save_post'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager, $cpt_onomy
	 * @param int $post_id - the ID of the current post
	 * @param object $post - the current post's information
	 */
	public function save_post( $post_id, $post ) {
		global $cpt_onomies_manager, $cpt_onomy;
		
		// verify nonce
		if ( !( isset( $_POST[ CPT_ONOMIES_UNDERSCORE . '_nonce' ] ) && wp_verify_nonce( $_POST[ CPT_ONOMIES_UNDERSCORE . '_nonce' ], 'assigning_custom_post_type_onomy_taxonomy_relationships' ) ) )
			return $post_id;
		
		// check autosave
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
			return $post_id;
			
		// dont save for revisions
		if ( isset( $post->post_type ) && $post->post_type == 'revision' )
			return $post_id;
			
		// check cpt-onomies
		foreach( get_object_taxonomies( $post->post_type, 'objects' ) as $taxonomy => $tax ) {
			
			// make sure cpt-onomy was visible, otherwise we might be deleting relationships for taxonomies that weren't even "editable"
			if ( isset( $_POST[ 'assigning_' . CPT_ONOMIES_UNDERSCORE . '_' . $taxonomy . '_relationships' ] ) && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) ) {
				
				// check permissions
				if ( !current_user_can( $tax->cap->assign_terms ) )
					continue;
							
				// set object terms				
				if ( isset( $_POST[ CPT_ONOMIES_POSTMETA_KEY ][ $taxonomy ] ) )
					$cpt_onomy->wp_set_object_terms( $post_id, $_POST[ CPT_ONOMIES_POSTMETA_KEY ][ $taxonomy ], $taxonomy );
					
				// delete taxonomy relationships
				else
					$cpt_onomy->wp_delete_object_term_relationships( $post_id, $taxonomy );
							
			}
						
		}
	}
		
	/**
	 * This function is run on the edit posts screen so you can filter posts by a CPT-onomy.
	 *
	 * This function is applied to the filter 'request'.
	 * 	 
	 * @since 1.0
	 * @uses $cpt_onomies_manager, $cpt_onomy, $pagenow, $post_type
	 * @param array $query - the query variables already created by WordPress
	 * @return array - the filtered query variables
	 */
	public function change_query_vars( $query ) {
		global $cpt_onomies_manager, $cpt_onomy, $pagenow, $post_type;
		// for filtering by CPT-onomy on admin edit posts screen
		if ( $pagenow == 'edit.php' && isset( $post_type ) ) {
			foreach( get_taxonomies( array( '_builtin' => false, 'public' => true ), 'objects' ) as $taxonomy => $tax ) {
				if ( isset( $_REQUEST[ $taxonomy ] ) && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) )  {
					// make sure the term exists
					$cpt_onomy_term = $cpt_onomy->get_term_by( 'slug', $_REQUEST[ $taxonomy ], $taxonomy );
					if ( !empty( $cpt_onomy_term ) ) {
						unset( $query[ $taxonomy ] );
						unset( $query[ 'name' ] );
					}
				}
			}
		}
		return $query;
	}
	
	/**
	 * This function is run on the edit posts screen so you can filter posts by a CPT-onomy.
	 * 
	 * This function is applied to the filter 'posts_join'.
	 * 	 
	 * @since 1.0
	 * @uses $wpdb, $cpt_onomies_manager, $cpt_onomy, $pagenow, $post_type
	 * @param string $join - the $join query already created by WordPress
	 * @return string - the filtered $join query
	 */
	public function posts_join( $join ) {
		global $wpdb, $cpt_onomies_manager, $cpt_onomy, $pagenow, $post_type;
		// for filtering by CPT-onomy on admin edit posts screen
		if ( $pagenow == 'edit.php' && isset( $post_type ) ) {
			foreach( get_taxonomies( array( '_builtin' => false, 'public' => true ), 'objects' ) as $taxonomy => $tax ) {
				if ( isset( $_REQUEST[ $taxonomy ] ) && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) )  {
					
					// get IDs for ALL posts with this slug
					// this allows us to show multiple slugs with same post_name where hierarchical allows multiples
					$slug = $_REQUEST[ $taxonomy ]; 
					$term_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '" . $slug . "'" ) );
					
					// if term ids exists
					if ( !empty( $term_ids ) ) {
						$join .= " INNER JOIN " . $wpdb->postmeta . " ON " .
							$wpdb->postmeta . ".post_id = " . $wpdb->posts . ".ID AND " .
							$wpdb->postmeta . ".meta_key = '" . CPT_ONOMIES_POSTMETA_KEY . "' AND " . 
							$wpdb->postmeta . ".meta_value IN ( " . implode( ',', $term_ids ) . ") ";
					}
					
				}
			}
		}
		return $join;
	}
	
	/**
	 * If a CPT-onomy is attached to a post type, the plugin adds a column
	 * to the post type's edit screen which lists each post's assigned terms.
	 *
	 * This function adds the columns to the screen.
	 * $this->edit_cpt_onomy_admin_column() adds the assigned terms to each column.
	 *
	 * This function is applied to the filter 'manage_pages_columns' and 'manage_posts_columns'.
	 * The 'posts' filter sends 2 parameters ($columns and $post_type)
	 * but the 'pages' only send $columns so I define $post_type to cover 'pages'.
	 *
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param array $columns - the column info already created by WordPress
	 * @param string $post_type - the name of the post type being managed/edited
	 * @return array - the columns info after it has been filtered
	 */
	public function add_cpt_onomy_admin_column( $columns, $post_type='page' ) {
		global $cpt_onomies_manager;
		foreach( get_object_taxonomies( $post_type, 'objects' ) as $taxonomy => $tax ) {
			// make sure its public and a registered CPT-onomy
			if ( $tax->public == true && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) ) {
					
				// want to add before comments and date
				$split = -1;
				$comments = array_search( 'comments', array_keys( $columns ) );
				$date = array_search( 'date', array_keys( $columns ) );
				
				if ( $comments !== false || $date !== false ) {
					
					if ( $comments !== false && $date !== false )
						$split = ( $comments < $date ) ? $comments : $date;
					else if ( $comments !== false && $date === false )
						$split = $comments;
					else if ( $comments === false && $date !== false )
						$split = $date;
					
				}
				
				// new column
				$new_column = array( CPT_ONOMIES_UNDERSCORE . '_' . $taxonomy => $tax->label );
				
				// add somewhere in the middle
				if ( $split > 0 ) {
					$beginning = array_slice( $columns, 0, $split );
					$end = array_slice( $columns, $split );
					$columns = $beginning + $new_column + $end;
				}
				// add at the beginning
				else if ( $split == 0 )
					$columns = $new_column + $columns;
				// add at the end
				else
					$columns += $new_column;
					
			}
		}
		return $columns;
	}
		
	/**
	 * If a CPT-onomy is attached to a post type, the plugin adds a column
	 * to the post type's edit screen which lists each post's assigned terms.
	 *
	 * $this->add_cpt_onomy_admin_column() adds the columns to the screen.
	 * This function adds the assigned terms to each column.
	 *
	 * This function is applied to the filter 'manage_pages_custom_column' and 'manage_posts_custom_column'.
	 *
	 * @since 1.0
	 * @uses $post
	 * @param string $column_name - the name of the column (which tells us which taxonomy to show)
	 * @param int $post_id - the ID of the current post
	 */
	public function edit_cpt_onomy_admin_column( $column_name, $post_id ) {
		global $post;
		$post_type = strtolower( str_replace( CPT_ONOMIES_UNDERSCORE . '_', '', $column_name ) );
		$terms = wp_get_object_terms( $post_id, $post_type );
		foreach( $terms as $index => $term ) {
			if ( $index > 0 ) echo ', ';
			echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => $post->post_type, $post_type => $term->slug ), 'edit.php' ) ) . '">' . $term->name . '</a>';	
		}
	}
	
}

/**
 * Custom walker used for wp_terms_checklist() so we can edit the input name.
 *
 * @since 1.0
 */
class CPTonomy_Walker_Terms_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	function start_lvl( &$output, $depth, $args ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl( &$output, $depth, $args ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul>\n";
	}

	function start_el( &$output, $category, $depth, $args ) {
		extract( $args );
		if ( !empty( $taxonomy ) ) {
			$class = in_array( $category->term_id, $popular_cats ) ? ' class="popular-category"' : '';
			$output .= "\n".'<li id="' . $taxonomy . '-' . $category->term_id . '"' . $class . '><label class="selectit"><input value="' . $category->term_id . '" type="checkbox" name="' . CPT_ONOMIES_POSTMETA_KEY . '[' . $taxonomy . '][]" id="in-' . $taxonomy . '-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name ) ) . '</label>';
								
		}
	}

	function end_el(&$output, $category, $depth, $args) {
		$output .= "</li>\n";
	}
}
		
?>