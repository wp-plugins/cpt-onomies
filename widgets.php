<?php

/**
 * Registers the plugin's widgets.
 *
 * @since 1.0
 */	
add_action( 'widgets_init', 'cpt_onomies_register_widgets' );
function cpt_onomies_register_widgets() {
	register_widget( 'WP_Widget_CPTonomy_Tag_Cloud' );
}
		
/**
 * CPT-onomy tag cloud widget class
 *
 * This widget was created because the plugin cannot hook into the WordPress tag cloud widget without receiving errors.
 * The widget, however, contains the same functionality. 
 *
 * This class mimics the WordPress class WP_Widget_Tag_Cloud.
 *
 * @since 1.0
 */
class WP_Widget_CPTonomy_Tag_Cloud extends WP_Widget {
	
	function __construct() {
		$widget_ops = array( 'description' => __( 'If you are using a custom post type as a taxonomy, a.k.a "CPT-onomy", this will show your most used tags in cloud format.' ) );
		parent::__construct( 'cpt_onomy_tag_cloud', __( 'CPT-onomy Tag Cloud' ), $widget_ops );
	}
	
	/**
	 * This function creates and prints the widget's HTML for the front-end.
	 * 
	 * @since 1.0
	 * @uses $cpt_onomies_manager, $cpt_onomy
	 * @param array $args - arguments to customize the widget
	 * @param array $instance - the widget's saved information
	 */
	function widget( $args, $instance ) {
		global $cpt_onomies_manager, $cpt_onomy;
		extract( $args );
		if ( isset( $instance[ 'taxonomy' ] ) ) {
			$taxonomy = $instance[ 'taxonomy' ];
			if ( $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) ) {
				$tax = get_taxonomy( $taxonomy );
				if ( $tax->public ) {
					// get tag cloud
					$tag_cloud = $cpt_onomy->wp_tag_cloud( apply_filters( 'widget_tag_cloud_args', array( 'taxonomy' => $taxonomy, 'echo'=>false ) ) );
					// if empty, and they dont' want to show if empty, then don't show
					if ( $instance[ 'show_if_empty' ] || ( !$instance[ 'show_if_empty' ] && !empty( $tag_cloud ) ) ) {
						if ( !empty( $instance[ 'title' ] ) )
							$title = $instance['title'];
						else
							$title = $tax->labels->name;
						$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );
					
						echo $before_widget;
						if ( $title )
							echo $before_title . $title . $after_title;
						echo '<div class="tagcloud">' . $tag_cloud . "</div>\n";
						echo $after_widget;
					}					
				}
			}
		}
	}

	/**
	 * This function updates the widget's settings.
	 * 
	 * @since 1.0
	 * @param array $new_instance - new settings to overwrite old settings
	 * @param array $old_instance - old settings
	 */
	function update( $new_instance, $old_instance ) {
		$instance[ 'title' ] = strip_tags( stripslashes( $new_instance[ 'title' ] ) );
		$instance[ 'taxonomy' ] = stripslashes( $new_instance[ 'taxonomy' ] );
		$instance[ 'show_if_empty' ] = stripslashes( $new_instance[ 'show_if_empty' ] );
		return $instance;
	}
	
	/**
	 * This function prints the widget's form in the admin.
	 * 
	 * @since 1.0
	 * @uses $cpt_onomies_manager
	 * @param $instance - widget settings
	 */
	function form( $instance ) {
		global $cpt_onomies_manager;
		$defaults = array( 'show_if_empty' => 1 );
		$instance = wp_parse_args( $instance, $defaults );
		$current_taxonomy = ( !empty( $instance[ 'taxonomy' ] ) && $cpt_onomies_manager->is_registered_cpt_onomy( $instance[ 'taxonomy' ] ) ) ? $instance['taxonomy'] : NULL;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
		<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php if ( isset( $instance[ 'title' ] ) ) { echo esc_attr( $instance[ 'title' ] ); } ?>" /></p>
		<p><label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:' ) ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
        <?php foreach( get_taxonomies( array( '_builtin' => false, 'public' => true ), 'objects' ) as $taxonomy => $tax ) {
			if ( !empty( $tax->labels->name ) && $cpt_onomies_manager->is_registered_cpt_onomy( $taxonomy ) )  { ?>
	      		<option value="<?php echo esc_attr( $taxonomy ); ?>" <?php selected( $taxonomy, $current_taxonomy ); ?>><?php echo $tax->labels->name; ?></option>
	      	<?php }
		} ?>
		</select></p>
        <p><label for="<?php echo $this->get_field_id( 'show_if_empty' ); ?>"><?php _e( 'Show tag cloud if empty:' ) ?></label>
		<select class="widefat" id="<?php echo $this->get_field_id( 'show_if_empty' ); ?>" name="<?php echo $this->get_field_name( 'show_if_empty' ); ?>">
			<option value="1" <?php selected( $instance[ 'show_if_empty' ], 1 ); ?>>Yes</option>
			<option value="0" <?php selected( $instance[ 'show_if_empty' ], 0 ); ?>>No</option>
		</select></p>
		<?php
	}
	
}

?>