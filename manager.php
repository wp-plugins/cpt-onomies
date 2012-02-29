<?php

/**
 * Holds the functions needed for managing the custom post types and taxonomies.
 *
 * @since 1.0
 */
class CPT_ONOMIES_MANAGER {
	
	public $user_settings = array(
		'custom_post_types' => array(),
		'other_custom_post_types' => array()
	);
	
	/**
	 * Retrieves the user's plugin options and defines $user_settings.
	 * Registers the custom post types and taxonomies.
	 *
	 * Adds WordPress hooks (actions and filters).
	 *
	 * @since 1.0
	 */
	public function CPT_ONOMIES_MANAGER() { $this->__construct(); }
	public function __construct() {
		// get user settings
		$custom_post_types = get_option( CPT_ONOMIES_UNDERSCORE . '_custom_post_types' );
		$other_custom_post_types = get_option( CPT_ONOMIES_UNDERSCORE . '_other_custom_post_types' );
		if ( $custom_post_types ) $this->user_settings[ 'custom_post_types' ] = $custom_post_types;
		if ( $other_custom_post_types ) $this->user_settings[ 'other_custom_post_types' ] = $other_custom_post_types;
		
		// register custom query vars
		add_filter( 'query_vars', array( &$this, 'register_custom_query_vars' ) );
		
		// manage user capabilities
		add_filter( 'user_has_cap', array( &$this, 'user_has_term_capabilities' ), 10, 3 );
		
		// tweak the query
		add_filter( 'request', array( &$this, 'change_query_vars' ) );
		add_action( 'pre_get_posts', array( &$this, 'add_cpt_onomy_term_queried_object' ), 1 );
		add_filter( 'posts_clauses', array( &$this, 'posts_clauses' ), 100, 2 );
		
		// clean up the query
		add_action( 'pre_get_posts', array( &$this, 'clean_get_posts_terms_query' ), 100 );
		
		// register custom post types and taxonomies
		add_action( 'init', array( &$this, 'register_custom_post_types_and_taxonomies' ), 100 );
	}
	
	/**
	 * Adds the custom query variable 'cpt_onomy_archive' to WordPress's
	 * WP_Query class which allows the plugin to create custom rewrites and queries.
	 *
	 * This function is applied to the filter 'query_vars'.
	 *
	 * @since 1.0
	 * @param array $vars - the query variables already created by WordPress
	 * @return array - the filtered query variables 
	 */
	public function register_custom_query_vars( $vars ) {
		array_push( $vars, 'cpt_onomy_archive' );
		return $vars;
	}
	
	/**
	 * As of version 1.0.3, this function cleans up queries for front and back end tax queries.
	 *
	 * For front-end CPT-onomy archive pages, it removes 'name' so WordPress does not think this 
	 * is a single post AND it defines which post types to show, i.e. which post types are attached
	 * to the CPT-onomy.
	 *
	 * This function is also run on the admin "edit posts" screen so you can filter posts by a CPT-onomy.
	 * It removes 'name' so WordPress does not think we are looking for a post with that 'name'.
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
		if ( isset( $query[ 'cpt_onomy_archive' ] ) && !empty( $query[ 'cpt_onomy_archive' ] ) ) {
			// make sure CPT-onomy AND term exists, otherwise, why bother
			$taxonomy = $query[ 'cpt_onomy_archive' ];
			if ( $this->is_registered_cpt_onomy( $taxonomy ) && isset( $query[ $taxonomy ] ) && !empty( $query[ $taxonomy ] ) ) { 
				
				// make sure the term exists
				$cpt_onomy_term = explode( "/", $query[ $taxonomy ] );
				// get parent
				$parent_term_id = 0;
				if ( count( $cpt_onomy_term ) > 1 ) {
					$parent_term = $cpt_onomy->get_term_by( 'slug', $cpt_onomy_term[ count( $cpt_onomy_term ) - 2 ], $taxonomy );
					if ( isset( $parent_term->term_id ) ) $parent_term_id = $parent_term->term_id;
				}		
										
				$cpt_onomy_term = $cpt_onomy->get_term_by( 'slug', $cpt_onomy_term[ count( $cpt_onomy_term ) - 1 ], $taxonomy, NULL, NULL, $parent_term_id );
				if ( !empty( $cpt_onomy_term ) ) {															
					
					// is supposed to be a child so redirect to correct URL
					if ( $cpt_onomy_term->parent != $parent_term_id ) {
						wp_redirect( $cpt_onomy->get_term_link( $cpt_onomy_term->term_id, $taxonomy ) );
						exit;
					}
					
					// to avoid confusion with other children of the same name, change term to term id
					else if ( $cpt_onomy_term->parent )
						$query[ $taxonomy ] = $cpt_onomy_term->term_id;
												
					// the 'name' variable makes WordPress think this is a single post with the assigned 'name'
					unset( $query[ 'name' ] );
					
					// replace the 'post_type' variable with the post types that are attached to the CPT-onomy
					$tax = get_taxonomy( $taxonomy );
					$query[ 'post_type' ] = $tax->object_type;
					
				}
				
			}
		}
		// for filtering by CPT-onomy on admin edit posts screen
		else if ( is_admin()  && $pagenow == 'edit.php' && isset( $post_type ) ) {
			foreach( get_taxonomies( array( '_builtin' => false, 'public' => true ), 'objects' ) as $taxonomy => $tax ) {
				if ( isset( $_REQUEST[ $taxonomy ] ) && !empty( $_REQUEST[ $taxonomy ] ) && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) )  {
					
					if ( is_numeric( $_REQUEST[ $taxonomy ] ) )
						$cpt_onomy_term = $cpt_onomy->get_term( (int) $_REQUEST[ $taxonomy ], $taxonomy );
					else
						$cpt_onomy_term = $cpt_onomy->get_term_by( 'slug', $_REQUEST[ $taxonomy ], $taxonomy );
					
					// the 'name' variable makes WordPress think we are looking for a post with that 'name'
					if ( !empty( $cpt_onomy_term ) )
						unset( $query[ 'name' ] );

				}
			}
		}
		
		return $query;
	}
	
	/**
	 * This function is used for CPT-onomy archive pages (on the front-end of the site)
	 * in order to trick WordPress into thinking this is a legit taxonomy archive page.
	 * 
	 * This function was created because we cannot hook into WordPress get_term_by(), without receiving an error.
	 * get_term_by() is responsible for passing the term's information to the query, which tells
	 * WordPress this is a taxonomy archive page, so this function creates the term information and
	 * passes it to the query.
	 *
	 * The CPT-onomy archive page query works without the queried object, but it is still required for
	 * other aspects of the page that use the queried object information, i.e. the page title.
	 *
	 * This function is applied to the action 'pre_get_posts'.
	 * 	 
	 * @since 1.0
	 * @uses $cpt_onomy
	 * @param array $query - the query variables already created by WordPress
	 */
	public function add_cpt_onomy_term_queried_object( $query ) {
		global $cpt_onomy;
		// for CPT-onomy archive page on front-end
		if ( isset( $query->query[ 'cpt_onomy_archive' ] ) && !empty( $query->query[ 'cpt_onomy_archive' ] ) ) {
			// make sure CPT-onomy AND term exists, otherwise, why bother
			$taxonomy = $query->query[ 'cpt_onomy_archive' ];
			if ( $this->is_registered_cpt_onomy( $taxonomy ) && isset( $query->query[ $taxonomy ] ) && !empty( $query->query[ $taxonomy ] ) ) {
															
				// make sure the term exists
				if ( is_numeric( $query->query[ $taxonomy ] ) )
					$cpt_onomy_term = $cpt_onomy->get_term( $query->query[ $taxonomy ], $taxonomy );
				else
					$cpt_onomy_term = $cpt_onomy->get_term_by( 'slug', $query->query[ $taxonomy ], $taxonomy );
					
				if ( !empty( $cpt_onomy_term ) ) {															
					// add queried object and queried object ID
					$query->queried_object = $cpt_onomy_term;
					$query->queried_object_id = $cpt_onomy_term->term_id;					
				}
				
			}
		}
	}
	
	/**
	 * As of version 1.0.3, this function detects tax queries in the front and back end and
	 * adjusts the posts query accordingly.
	 *
	 * This function is invoked by the filter 'posts_clauses'.
	 * 
	 * @since 1.0.3
	 * @uses $wpdb, $cpt_onomy
	 * @param array $clauses - the clauses variables already created by WordPress
	 * @param WP_Query object $query - all of the query info
	 * @return array - the clauses info after it has been filtered
	 */
	public function posts_clauses( $clauses, $query ) {
		global $wpdb, $cpt_onomy;
		if ( isset( $query->tax_query ) ) {	
			$is_registered_cpt_onomy = false;
			$taxonomies = array( 'join' => '', 'where' => array() );
			$new_where = array();
			$c = $t = 1;
			foreach ( $query->tax_query->queries as $this_query ) {
				
				if ( !taxonomy_exists( $this_query[ 'taxonomy' ] )  )
					continue;
					
				$is_registered_cpt_onomy = $this->is_registered_cpt_onomy( $this_query[ 'taxonomy' ] );
		
				$this_query[ 'terms' ] = array_unique( (array) $this_query[ 'terms' ] );
					
				if ( empty( $this_query[ 'terms' ] ) )
					continue;
					
				// if terms are ID, change field
				foreach ( $this_query[ 'terms' ] as $term ) {
					if ( is_numeric( $term ) ) {
						$this_query[ 'field' ] = 'id';
						break;
					}
				}
			
				// CPT-onomies
				if ( $is_registered_cpt_onomy ) {
					switch ( $this_query[ 'field' ] ) {
						case 'slug':
						case 'name':						
							$terms = "'" . implode( "','", array_map( 'sanitize_title_for_query', $this_query[ 'terms' ] ) ) . "'";
							$terms = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE " . ( ( strtolower( $this_query[ 'field' ] ) == 'slug' ) ? 'post_name' : 'post_title' ) . " IN ($terms)" );
							break;		
						default:
							$terms = array_map( 'intval', $this_query[ 'terms' ] );						
					}
				}
				// taxonomies
				else {
					switch ( $this_query[ 'field' ] ) {
						case 'slug':
						case 'name':
							$terms = "'" . implode( "','", array_map( 'sanitize_title_for_query', $this_query[ 'terms' ] ) ) . "'";
							$terms = $wpdb->get_col( "
								SELECT $wpdb->term_taxonomy.term_taxonomy_id
								FROM $wpdb->term_taxonomy
								INNER JOIN $wpdb->terms USING (term_id)
								WHERE taxonomy = '{$this_query[ 'taxonomy' ]}'
								AND $wpdb->terms.{$this_query[ 'field' ]} IN ($terms)
							" );
							break;
						default:
							$terms = implode( ',', array_map( 'intval', $this_query[ 'terms' ] ) );
							$terms = $wpdb->get_col( "
								SELECT term_taxonomy_id
								FROM $wpdb->term_taxonomy
								WHERE taxonomy = '{$this_query[ 'taxonomy' ]}'
								AND term_id IN ($terms)
							" );
					}					
				}
				
				if ( 'AND' == $this_query[ 'operator' ] && count( $terms ) < count( $this_query[ 'terms' ] ) )
					return;
									
				$this_query[ 'terms' ] = $terms;
						
				if ( is_taxonomy_hierarchical( $this_query[ 'taxonomy' ] ) && $this_query['include_children'] ) {
					
					$children = array();
					foreach ( $this_query[ 'terms' ] as $term ) {
						
						// for hierarchical CPT-onomies	
						if ( $is_registered_cpt_onomy )
							$children = array_merge( $children, $cpt_onomy->get_term_children( $term, $this_query[ 'taxonomy' ] ) );
						// taxonomies
						else
							$children = array_merge( $children, get_term_children( $term, $this_query['taxonomy'] ) );
							
						$children[] = $term;
					}
					$this_query[ 'terms' ] = $children;
					
				}
				
				extract( $this_query );
				
				$primary_table = $wpdb->posts;
				$primary_id_column = 'ID';
				
				sort( $terms );
	
				if ( 'IN' == $operator ) {
					
					if ( empty( $terms ) )
						continue;
	
					$terms = implode( ',', $terms );
					
					// CPT-onomies
					if ( $is_registered_cpt_onomy ) {
	
						$alias = $c ? 'cpt_onomy_pm' . $c : $wpdb->postmeta;
		
						$clauses[ 'join' ] .= " INNER JOIN $wpdb->postmeta";
						$clauses[ 'join' ] .= $c ? " AS $alias" : '';
						$clauses[ 'join' ] .= " ON ($wpdb->posts.ID = $alias.post_id AND $alias.meta_key = '" . CPT_ONOMIES_POSTMETA_KEY . "')";
								
						$new_where[] = "$alias.meta_value $operator ($terms)";
						
						$c++;
						
					}
					// taxonomies
					else {
					
						$alias = $t ? 'cpt_onomy_tt' . $t : $wpdb->term_relationships;

						$taxonomies[ 'join' ] .= " INNER JOIN $wpdb->term_relationships";
						$taxonomies[ 'join' ] .= $t ? " AS $alias" : '';
						$taxonomies[ 'join' ] .= " ON ($primary_table.$primary_id_column = $alias.object_id)";
						
						$new_where[] = $taxonomies[ 'where' ][] = "$alias.term_taxonomy_id $operator ($terms)";
						
						$t++;
						
					}
					
				} elseif ( 'NOT IN' == $operator ) {
	
					if ( empty( $terms ) )
						continue;
	
					$terms = implode( ',', $terms );
					
					// CPT-onomies
					if ( $is_registered_cpt_onomy ) {
	
						$new_where[] = "$wpdb->posts.ID NOT IN (
							SELECT post_id
							FROM $wpdb->postmeta
							WHERE meta_key = '" . CPT_ONOMIES_POSTMETA_KEY . "'
							AND meta_value IN ($terms)
						)";
						
					}
					// taxonomies
					else {
						
						$new_where[] = $taxonomies[ 'where' ][] = "$primary_table.$primary_id_column NOT IN (
							SELECT object_id
							FROM $wpdb->term_relationships
							WHERE term_taxonomy_id IN ($terms)
						)";
				
					}
					
				} elseif ( 'AND' == $operator ) {
	
					if ( empty( $terms ) )
						continue;
	
					$num_terms = count( $terms );
	
					$terms = implode( ',', $terms );
					
					// CPT-onomies
					if ( $is_registered_cpt_onomy ) {
	
						$new_where[] = "(
							SELECT COUNT(1)
							FROM $wpdb->postmeta
							WHERE meta_key = '" . CPT_ONOMIES_POSTMETA_KEY . "'
							AND meta_value IN ($terms)
							AND post_id = $wpdb->posts.ID
						) = $num_terms";
						
					}
					// taxonomies
					else {
						
						$new_where[] = $taxonomies[ 'where' ][] = "(
							SELECT COUNT(1)
							FROM $wpdb->term_relationships
							WHERE term_taxonomy_id IN ($terms)
							AND object_id = $primary_table.$primary_id_column
						) = $num_terms";
				
					}
					
				}
				
			}
			
			// only add taxonomies 'join' if it doesn't already exist
			if ( $clauses[ 'join' ] && $taxonomies[ 'join' ] && strpos( $clauses[ 'join' ], $taxonomies[ 'join' ] ) === false )
				$clauses[ 'join' ] .= $taxonomies[ 'join' ];
			
			// remove old taxonomies 'where' so we can add new 'where'
			if ( $taxonomies[ 'where' ] ) {
				
				$tax_where = " AND ( ";
					foreach ( $taxonomies[ 'where' ] as $where_index => $add_where ) {
						if ( $where_index > 0 )
							$tax_where .= " " . $query->tax_query->relation . " ";
						$tax_where .= $add_where;
					}
				$tax_where .= " )";
				
				$clauses[ 'where' ] = str_replace( $tax_where, '', $clauses[ 'where' ] );
				
			}
			
			if ( !empty( $new_where ) )  {
				
				// remove 0 = 1
				$clauses[ 'where' ] = preg_replace( '/0\s\=\s1\sAND\s/i', '', $clauses[ 'where' ] );
											
				$clauses[ 'where' ] .= " AND ( ";
					foreach ( $new_where as $where_index => $add_where ) {
						if ( $where_index > 0 )
							$clauses[ 'where' ] .= " " . $query->tax_query->relation . " ";
						$clauses[ 'where' ] .= $add_where;
					}
				$clauses[ 'where' ] .= " )";
					
			}
							
		}
		return $clauses;
	}
	
	/**
	 * Because retrieving CPT-onomy terms involves get_posts(), we have to set some
	 * measures in place to remove any filters or queries that might affect retrieving
	 * the CPT-onomy terms.
	 *
	 * It detects the query variable 'get_cpt_onomy_terms' before editing the query. 
	 *
	 * This function is applied to the action 'pre_get_posts'.
	 * 	 
	 * @since 1.0.3
	 * @param array $query - the query variables already created by WordPress
	 */
	public function clean_get_posts_terms_query( $query ) {
		if ( isset( $query->query_vars[ 'get_cpt_onomy_terms' ] ) ) {
			
			// remove all tax queries
			$query->set( 'taxonomy', NULL );
			$query->set( 'term', NULL );
			if ( isset( $query->tax_query ) )
				$query->tax_query = NULL;
			if ( isset( $query->query[ 'taxonomy' ] ) )
				$query->query_vars[ 'taxonomy' ] = NULL;
			if ( isset( $query->query[ 'term' ] ) )
				$query->query[ 'term' ] = NULL;
			
			// remove all meta queries
			$query->set( 'meta_key', NULL );
			$query->set( 'meta_value', NULL );
			if ( isset( $query->meta_query ) )
				$query->meta_query = NULL;
			if ( isset( $query->query[ 'meta_key' ] ) )
				$query->query_vars[ 'meta_key' ] = NULL;
			if ( isset( $query->query[ 'meta_value' ] ) )
				$query->query[ 'meta_value' ] = NULL;
				
		}
	}
	
	/**
	 * This function hooks into WordPress current_user_can() whenever WordPress
	 * is checking that the user can 'assign_$taxonomy_terms', 'manage_$taxonomy_terms', 
	 * 'edit_$taxonomy_terms' or 'delete_$taxonomy_terms'.
	 *
	 * If assign, it checks user settings to see if user role has permission to assign.
	 * If 'manage', 'edit' or 'delete, it tells WordPress NO!
	 *
	 * This function is applied to the filter 'user_has_cap'.
	 *
	 * @since 1.0
	 * @param array $allcaps - all of the user's preset capabilities
	 * @param array $caps - the capabilities we're testing
	 * @param array $args - additional arguments passed to the function
	 * @return array - the filtered $allcaps
	 */
	public function user_has_term_capabilities( $allcaps, $caps, $args ) {
		// no one can manage, edit, or delete CPT-onomy terms
		foreach( $caps as $this_cap ) {
			
			// if user has capability manually assigned, then allow
			// otherwise, check user settings
			if ( preg_match( '/assign\_([a-z\_]+)\_terms/i', $this_cap ) && !isset( $allcaps[ $this_cap ] ) ) {
				
				// get taxonomy
				$taxonomy = preg_replace( '/(assign_|_terms)/i', '', $this_cap );
										
				// if registered CPT-onomy
				if ( taxonomy_exists( $taxonomy ) && $this->is_registered_cpt_onomy( $taxonomy ) ) {
					
					// default
					$allow = false;
					
					// no capabilities are assigned so everyone has access if they can 'assign_terms' or the custom assigning capability
					if ( array_key_exists( $taxonomy, $this->user_settings[ 'custom_post_types' ] ) && !isset( $this->user_settings[ 'custom_post_types' ][ $taxonomy ][ 'restrict_user_capabilities' ] ) )
						$allow = true;
					// check the "other" custom post types
					else if ( array_key_exists( $taxonomy, $this->user_settings[ 'other_custom_post_types' ] ) && !isset( $this->user_settings[ 'other_custom_post_types' ][ $taxonomy ][ 'restrict_user_capabilities' ] ) )
						$allow = true;
					
					// the capability is restricted to specific roles
					else {
																
						// get user roles to see if user has capability to assign taxonomy
						// $args contains the user id
						$user = new WP_User( $args[1] );
						foreach ( $user->roles as $role ) {
														
							// test to see if role is selected
							if ( array_key_exists( $taxonomy, $this->user_settings[ 'custom_post_types' ] ) && in_array( $role, $this->user_settings[ 'custom_post_types' ][ $taxonomy ][ 'restrict_user_capabilities' ] ) ) {
								$allow = true;
								break;
							}
							// test "other" custom post types
							else if ( array_key_exists( $taxonomy, $this->user_settings[ 'other_custom_post_types' ] ) && in_array( $role, $this->user_settings[ 'other_custom_post_types' ][ $taxonomy ][ 'restrict_user_capabilities' ] ) ) {
								$allow = true;
								break;
							}
								
						}
								
					}
					
					// assign the required capability
					if ( $allow )
						$allcaps[ $this_cap ] = 1;
					else
						unset( $allcaps[ $this_cap ] );					
					
				}
					
			}
			
			// NO ONE is allowed to manage, edit or delete
			else if ( preg_match( '/(manage|edit|delete)\_([a-z\_]+)\_terms/i', $this_cap ) ) {
				
				// get taxonomy
				$taxonomy = preg_replace( '/(manage_|edit_|delete_|_terms)/i', '', $this_cap );
								
				// if registered CPT-onomy
				if ( taxonomy_exists( $taxonomy ) && $this->is_registered_cpt_onomy( $taxonomy ) )
					unset( $allcaps[ $this_cap ] );
					
			}
			
		}
		return $allcaps;
	}
	
	/**
	 * This functions checks to see if a custom post type is a custom post type
	 * registered by this plugin. When this plugin registers a custom post type,
	 * it adds the argument 'created_by_cpt_onomies' for testing purposes.
	 *
	 * @since 1.0
	 * @param string $cpt_key - the key, or alias, for the custom post type you are checking
	 * @return boolean - whether this custom post type is a post type registered by this plugin
	 */
	public function is_registered_cpt( $cpt_key ) {
		if ( !empty( $cpt_key ) && post_type_exists( $cpt_key ) ) {
			$post_type = get_post_type_object( $cpt_key );
			if ( isset( $post_type->created_by_cpt_onomies ) && $post_type->created_by_cpt_onomies == true )
				return true;
		}
		return false;
	}
	
	/**
	 * This functions checks to see if a taxonomy is a taxonomy
	 * registered by this plugin. When this plugin registers a taxonomy,
	 * it adds the argument 'cpt_onomy' for testing purposes.
	 * 
	 * @since 1.0
	 * @param string $tax - the key, or alias, for the taxonomy you are checking
	 * @return boolean - whether this taxonomy is a taxonomy registered by this plugin
	 */
	public function is_registered_cpt_onomy( $taxonomy ) {
		if ( !empty( $taxonomy ) && taxonomy_exists( $taxonomy ) ) {
			$tax = get_taxonomy( $taxonomy );
			if ( isset( $tax->cpt_onomy ) && $tax->cpt_onomy == true )
				return true;
		}
		return false;
	}
	
	/**
	 * Registers the user's custom post types.
	 *
	 * If 'Use Custom Post Type as Taxonomy' is set, registers a CPT-onomy
	 * and adds a rewrite rule to display CPT-onomy archive page at {cpt name}/tax/{term}.
	 *
	 * This function is invoked by the action 'init'.
	 *
	 * @since 1.0
	 * @uses $wp_rewrite
	 */
	public function register_custom_post_types_and_taxonomies() {
		global $wp_rewrite;
		if ( !empty( $this->user_settings[ 'custom_post_types' ] ) ) {
			foreach( $this->user_settings[ 'custom_post_types' ] as $cpt_key => $cpt ) {
				if ( !isset( $cpt[ 'deactivate' ] ) ) {
					
					// create label
					// if no label, set to 'Posts'
					if ( !isset( $cpt[ 'label' ] ) || empty( $cpt[ 'label' ] ) )
						$this->user_settings[ 'custom_post_types' ][ $cpt_key ][ 'label' ] = $cpt[ 'label' ] = 'Posts';
					$label = strip_tags( $cpt[ 'label' ] );			
							
					if ( !empty( $label ) && !empty( $cpt_key ) ) {
						
						// create labels
						$labels = array( 'name' => __( $label ) );
						if ( isset( $cpt[ 'singular_name' ] ) && !empty( $cpt[ 'singular_name' ] ) )
							$labels[ 'singular_name' ] = __( strip_tags( $cpt[ 'singular_name' ] ) );
						if ( isset( $cpt[ 'add_new' ] ) && !empty( $cpt[ 'add_new' ] ) ) 
							$labels[ 'add_new' ] = __( strip_tags( $cpt[ 'add_new' ] ) );
						if ( isset( $cpt[ 'add_new_item' ] ) && !empty( $cpt[ 'add_new_item' ] ) )
							$labels[ 'add_new_item' ] = __( strip_tags( $cpt[ 'add_new_item' ] ) );
						if ( isset( $cpt[ 'edit_item' ] ) && !empty( $cpt[ 'edit_item' ] ) )
							$labels[ 'edit_item' ] = __( strip_tags( $cpt[ 'edit_item' ] ) );
						if ( isset( $cpt[ 'new_item' ] ) && !empty( $cpt[ 'new_item' ] ) )
							$labels[ 'new_item' ] = __( strip_tags( $cpt[ 'new_item' ] ) );
						if ( isset( $cpt[ 'all_items' ] ) && !empty( $cpt[ 'all_items' ] ) )
							$labels[ 'all_items' ] = __( strip_tags( $cpt[ 'all_items' ] ) );
						if ( isset( $cpt[ 'view_item' ] ) && !empty( $cpt[ 'view_item' ] ) )
							$labels[ 'view_item' ] = __( strip_tags( $cpt[ 'view_item' ] ) );
						if ( isset( $cpt[ 'search_items' ] ) && !empty( $cpt[ 'search_items' ] ) )
							$labels[ 'search_items' ] = __( strip_tags( $cpt[ 'search_items' ] ) );					
						if ( isset( $cpt[ 'not_found' ] ) && !empty( $cpt[ 'not_found' ] ) )
							$labels[ 'not_found' ] = __( strip_tags( $cpt[ 'not_found' ] ) );
						if ( isset( $cpt[ 'not_found_in_trash' ] ) && !empty( $cpt[ 'not_found_in_trash' ] ) )
							$labels[ 'not_found_in_trash' ] = __( strip_tags( $cpt[ 'not_found_in_trash' ] ) );
						if ( isset( $cpt[ 'parent_item_colon' ] ) && !empty( $cpt[ 'parent_item_colon' ] ) )
							$labels[ 'parent_item_colon' ] = __( strip_tags( $cpt[ 'parent_item_colon' ] ) );
						if ( isset( $cpt[ 'menu_name' ] ) && !empty( $cpt[ 'menu_name' ] ) )
							$labels[ 'menu_name' ] = __( strip_tags( $cpt[ 'menu_name' ] ) );
						
						// WP default = false, plugin default = true
						$public = ( !$cpt[ 'public' ] ) ? false : true;
						
						$args = array(
							'created_by_cpt_onomies' => true,
							'label' => $label,
							'labels' => $labels,
							'public' => $public
						);
						
						// boolean (optional) default = false
						// this must be defined for use with register_taxonomy()
						$args[ 'hierarchical' ] = ( isset( $cpt[ 'hierarchical' ] ) && $cpt[ 'hierarchical' ] ) ? true : false;
							
						// array (optional) default = array( 'title', 'editor' )
						if ( isset( $cpt[ 'supports' ] ) && !empty( $cpt[ 'supports' ] ) )
							$args[ 'supports' ] = $cpt[ 'supports' ];
						// array (optional) no default
						if ( isset( $cpt[ 'taxonomies' ] ) && !empty( $cpt[ 'taxonomies' ] ) )
							$args[ 'taxonomies' ] = $cpt[ 'taxonomies' ];
						
						// boolean (optional) default = public
						if ( isset( $cpt[ 'show_ui' ] ) )
							$args[ 'show_ui' ] = ( !$cpt[ 'show_ui' ] ) ? false : true;
						// boolean (optional) default = public
						if ( isset( $cpt[ 'show_in_nav_menus' ] ) )
							$args[ 'show_in_nav_menus' ] = ( !$cpt[ 'show_in_nav_menus' ] ) ? false : true;
						// boolean (optional) default = public
						if ( isset( $cpt[ 'publicly_queryable' ] ) )
							$args[ 'publicly_queryable' ] = ( !$cpt[ 'publicly_queryable' ] ) ? false : true;
						// boolean (optional) default = opposite of public
						if ( isset( $cpt[ 'exclude_from_search' ] ) )
							$args[ 'exclude_from_search' ] = ( $cpt[ 'exclude_from_search' ] ) ? true : false;
						// boolean (optional) default = false
						if ( isset( $cpt[ 'map_meta_cap' ] ) )
							$args[ 'map_meta_cap' ] = ( $cpt[ 'map_meta_cap' ] ) ? true : false;
						// boolean (optional) default = true
						if ( isset( $cpt[ 'can_export' ] ) )
							$args[ 'can_export' ] = ( !$cpt[ 'can_export' ] ) ? false : true;
							
						// integer (optional) default = NULL
						if ( isset( $cpt[ 'menu_position' ] ) && !empty( $cpt[ 'menu_position' ] ) && is_numeric( $cpt[ 'menu_position' ] ) )
							$args[ 'menu_position' ] = intval( $cpt[ 'menu_position' ] );
						
						// string (optional) default is blank
						if ( isset( $cpt[ 'description' ] ) && !empty( $cpt[ 'description' ] ) )
							$args[ 'description' ] = strip_tags( $cpt[ 'description' ] );
						// string (optional) default = NULL
						if ( isset( $cpt[ 'menu_icon' ] ) && !empty( $cpt[ 'menu_icon' ] ) )
							$args[ 'menu_icon' ] = $cpt[ 'menu_icon' ];
						// string (optional) no default
						if ( isset( $cpt[ 'register_meta_box_cb' ] ) && !empty( $cpt[ 'register_meta_box_cb' ] ) )
							$args[ 'register_meta_box_cb' ] = $cpt[ 'register_meta_box_cb' ];
						// string (optional) default = EP_PERMALINK
						if ( isset( $cpt[ 'permalink_epmask' ] ) && !empty( $cpt[ 'permalink_epmask' ] ) )
							$args[ 'permalink_epmask' ] = $cpt[ 'permalink_epmask' ];
							
						// string or array (optional) default = "post"
						if ( isset( $cpt[ 'capability_type' ] ) && !empty( $cpt[ 'capability_type' ] ) )
							$args[ 'capability_type' ] = $cpt[ 'capability_type' ];
						
						// boolean or string (optional)
						// default = true (which is opposite of WP default so we must include the setting)
						// if set to string 'true', then store as true
						// else if not set to false, store string	
						if ( isset( $cpt[ 'has_archive' ] ) && !empty( $cpt[ 'has_archive' ] ) && strtolower( $cpt[ 'has_archive' ] ) != 'true' ) {
							if ( strtolower( $cpt[ 'has_archive' ] ) == 'false' ) $args[ 'has_archive' ] = false;
							else if ( strtolower( $cpt[ 'has_archive' ] ) != 'true' ) $args[ 'has_archive' ] = $cpt[ 'has_archive' ];
						}
						else
							$args[ 'has_archive' ] = true;
						
						// boolean or string (optional) default = true
						// if set to string 'false', then store as false
						// else if set to true, store string	
						if ( isset( $cpt[ 'query_var' ] ) && !empty( $cpt[ 'query_var' ] ) ) {
							if ( strtolower( $cpt[ 'query_var' ] ) == 'false' ) $args[ 'query_var' ] = false;
							else if ( strtolower( $cpt[ 'query_var' ] ) != 'true' ) $args[ 'query_var' ] = $cpt[ 'query_var' ];							
						}	
						
						// boolean or string (optional) default = NULL
						// if set to string 'false', then store as false
						// if set to string 'true', then store as true
						// if set to another string, store string
						if ( isset( $cpt[ 'show_in_menu' ] ) && !empty( $cpt[ 'show_in_menu' ] ) ) {
							if ( strtolower( $cpt[ 'show_in_menu' ] ) == 'false' ) $args[ 'show_in_menu' ] = false;
							else if ( strtolower( $cpt[ 'show_in_menu' ] ) == 'true' ) $args[ 'show_in_menu' ] = true;
							else $args[ 'show_in_menu' ] = $cpt[ 'show_in_menu' ];							
						}
						
						// array (optional) default = capability_type is used to construct 
						// if you include blank capabilities, it messes up that capability
						if ( isset( $cpt[ 'capabilities' ] ) && !empty( $cpt[ 'capabilities' ] ) ) {
							foreach( $cpt[ 'capabilities' ] as $capability_key => $capability ) {
								if ( !empty( $capability ) ) $args[ 'capabilities' ][ $capability_key ] = $capability;
							}
						}
						
						// boolean or array (optional) default = true and use post type as slug 
						if ( isset( $cpt[ 'rewrite' ] ) && !empty( $cpt[ 'rewrite' ] ) ) {
							if ( isset( $cpt[ 'rewrite' ][ 'enable_rewrite' ] ) && !$cpt[ 'rewrite' ][ 'enable_rewrite' ] )
								$args[ 'rewrite' ] = false;
							else {
								// remove" enable rewrite" and include the rest
								unset( $cpt[ 'rewrite' ][ 'enable_rewrite' ] );
								if ( isset( $cpt[ 'rewrite' ] ) && !empty( $cpt[ 'rewrite' ] ) )
									$args[ 'rewrite' ] = $cpt[ 'rewrite' ];								
							}
						}
											
						// make sure post type does not already exist
						if ( !post_type_exists( $cpt_key ) ) {
							
							// if designated, register custom taxonomy
							if ( !taxonomy_exists( $cpt_key ) && isset( $cpt[ 'attach_to_post_type' ] ) && !empty( $cpt[ 'attach_to_post_type' ] ) ) {
								
								//setup rewrite slug
								$taxonomy_slug = $cpt_key;
								if ( !empty( $args[ 'rewrite' ][ 'slug' ] ) )
									$taxonomy_slug = $args[ 'rewrite' ][ 'slug' ];
									
								// make sure post types exist
								foreach( $cpt[ 'attach_to_post_type' ] as $attached_key => $attached_post_type ) { 
									if ( !post_type_exists( $attached_post_type ) && !array_key_exists( $attached_post_type, $this->user_settings[ 'custom_post_types' ] ) )
										unset( $cpt[ 'attach_to_post_type' ][ $attached_key ] );
								}
								
								if ( !empty( $cpt[ 'attach_to_post_type' ] ) ) {
								
									register_taxonomy( $cpt_key, $cpt[ 'attach_to_post_type' ], array(
										'cpt_onomy' => true,
										'label' => $label,
										'public' => $public,
										'show_in_nav_menus' => false,
										'show_ui' => false,
										'show_tagcloud' => false,
										'hierarchical' => $args[ 'hierarchical' ],
										'rewrite' => array( 'slug' => $taxonomy_slug . '/tax', 'with_front' => $args[ 'rewrite' ][ 'with_front' ], 'hierarchical' => $args[ 'hierarchical' ] ),
										'capabilities' => array(
											'manage_terms' => 'manage_'.$cpt_key.'_terms',
											'edit_terms' => 'edit_'.$cpt_key.'_terms',
											'delete_terms' => 'delete_'.$cpt_key.'_terms',
											'assign_terms' => 'assign_'.$cpt_key.'_terms'
										)
									));
									
									// add rewrite rule to display CPT-onomy archive page - '{cpt name}/tax/{term}
									// default is true
									if ( !( isset( $cpt[ 'has_cpt_onomy_archive' ] ) && !$cpt[ 'has_cpt_onomy_archive' ] ) ) {
										$permastruct = str_replace( '%'.$cpt_key.'%', '([^\s]*)', $wp_rewrite->get_extra_permastruct( $cpt_key ) );
										if ( substr( $permastruct, 0, 1 ) == '/' ) $permastruct = substr( $permastruct, 1 );
										add_rewrite_rule( '^'.$permastruct.'/?', 'index.php?'.$cpt_key.'=$matches[1]&cpt_onomy_archive='.$cpt_key, 'top' );
									}
									
								}
								
							}
									
							// must register post type last so the post type will win the rewrite war
							register_post_type( $cpt_key, $args );
														
						}
		
					}
					
				}
			}
		}		
		// register OTHER custom post types as taxonomies
		if ( !empty( $this->user_settings[ 'other_custom_post_types' ] ) ) {
			foreach( $this->user_settings[ 'other_custom_post_types' ] as $cpt_key => $cpt_settings ) {
				
				// make sure post type exists and, if designated, register custom taxonomy
				if ( post_type_exists( $cpt_key ) && !$this->is_registered_cpt( $cpt_key ) && isset( $cpt_settings[ 'attach_to_post_type' ] ) && !empty( $cpt_settings[ 'attach_to_post_type' ] ) ) {
									
					// make sure post types exist
					foreach( $cpt_settings[ 'attach_to_post_type' ] as $attached_key => $attached_post_type ) { 
						if ( !post_type_exists( $attached_post_type ) && !array_key_exists( $attached_post_type, $this->user_settings[ 'other_custom_post_types' ] ) )
							unset( $cpt_settings[ 'attach_to_post_type' ][ $attached_key ] );
					}
					
					if ( !empty( $cpt_settings[ 'attach_to_post_type' ] ) ) {
					
						// get post type
						$custom_post_type = get_post_type_object( $cpt_key );
						
						// create label
						$label = strip_tags( $custom_post_type->label );
											
						if ( !taxonomy_exists( $cpt_key ) ) {
							
							// Because these are "other" custom post types and they are already registered,
							// we cannot declare a taxonomy rewrite because the post type MUST win the rewrite war.
							
							register_taxonomy( $cpt_key, $cpt_settings[ 'attach_to_post_type' ], array(
								'cpt_onomy' => true,
								'label' => $label,
								'public' => $custom_post_type->public,
								'show_in_nav_menus' => false,
								'show_ui' => false,
								'show_tagcloud' => false,
								'hierarchical' => false,
								'capabilities' => array(
									'manage_terms' => 'manage_'.$cpt_key.'_terms',
									'edit_terms' => 'edit_'.$cpt_key.'_terms',
									'delete_terms' => 'delete_'.$cpt_key.'_terms',
									'assign_terms' => 'assign_'.$cpt_key.'_terms'
								)
							));
							
							// add rewrite rule to display CPT-onomy archive page - '{cpt name}/tax/{term}
							// default is true
							// unlike CPT-onomies attached to custom post types registered by this plugin,
							// we have to add "tax" to the permastruct ourselves because we cannot add tax to the taxonomy rewrite
							if ( !( isset( $cpt_settings[ 'has_cpt_onomy_archive' ] ) && !$cpt_settings[ 'has_cpt_onomy_archive' ] ) )  {
								$permastruct = str_replace( '%'.$cpt_key.'%', 'tax/([^\s]*)', $wp_rewrite->get_extra_permastruct( $cpt_key ) );
								if ( substr( $permastruct, 0, 1 ) == '/' ) $permastruct = substr( $permastruct, 1 );
								add_rewrite_rule( '^'.$permastruct.'/?', 'index.php?'.$cpt_key.'=$matches[1]&cpt_onomy_archive='.$cpt_key, 'top' );
							}
							
						}
						
					}
					
				}
			}
		}
	}	
}

?>