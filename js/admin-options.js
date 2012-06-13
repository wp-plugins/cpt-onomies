var $cpt_onomies_changed_form = 0;
jQuery.noConflict()(function(){
	
	// count how many times the form is changed
	jQuery( 'form#custom-post-type-onomies-edit-cpt' ).change( function() {
		$cpt_onomies_changed_form++;
	});
	
	// check to make sure info is saved before continuing
	jQuery( 'a:not(.delete_cpt_onomy_custom_post_type):not([target="_blank"])' ).live( 'click', function( event ) {
		if ( $cpt_onomies_changed_form ) {
			var $message = null;
			if ( cpt_onomies_admin_options_L10n.unsaved_message1 != '' )
				$message = cpt_onomies_admin_options_L10n.unsaved_message1
			else
				$message = 'It looks like you might have some unsaved changes.';
			if ( cpt_onomies_admin_options_L10n.unsaved_message2 != '' )
				$message += '\n' + cpt_onomies_admin_options_L10n.unsaved_message2
			else
				$message += '\nAre you sure you want to leave?';
			var $confirm = confirm( $message );
			if ( $confirm != true )
				event.preventDefault();
		}
	});	
	
	// show message
	jQuery( '.show_cpt_message' ).live( 'click', function( event ) {
		event.preventDefault();
		alert( jQuery( this ).attr( 'alt' ) );
	});
	
	// show delete confirmation
	jQuery( '.delete_cpt_onomy_custom_post_type' ).live( 'click', function( event ) {
		var $message = null;
		if ( cpt_onomies_admin_options_L10n.delete_message1 != '' )
			$message = cpt_onomies_admin_options_L10n.delete_message1;
		else
			$message = 'Are you sure you want to delete this custom post type?';
		if ( cpt_onomies_admin_options_L10n.delete_message2 != '' )
			$message += '\n\n' + cpt_onomies_admin_options_L10n.delete_message2;
		else
			$message += '\n\nThere is NO undo and once you click "OK", all of your settings will be gone.';
		if ( cpt_onomies_admin_options_L10n.delete_message3 != '' )
			$message += '\n\n' + cpt_onomies_admin_options_L10n.delete_message3;
		else
			$message += '\n\nDeleting your custom post type DOES NOT delete the actual posts.';
		if ( cpt_onomies_admin_options_L10n.delete_message4 != '' )
			$message += '\n' + cpt_onomies_admin_options_L10n.delete_message4;
		else
			$message += '\nThey\'ll be waiting for you if you decide to register this post type again.';
		if ( cpt_onomies_admin_options_L10n.delete_message5 != '' )
			$message += '\n' + cpt_onomies_admin_options_L10n.delete_message5;
		else
			$message += '\nJust make sure you use the same name.';
		var $confirm = confirm( $message );
		if ( $confirm != true )
			event.preventDefault();
	});
	
	// change the header label
	jQuery( '#custom-post-type-onomies-custom-post-type-label' ).keyup( function() {
		jQuery( '#edit_custom_post_type_header .label' ).html( jQuery( this ).val() );
	});
	jQuery( '#custom-post-type-onomies-custom-post-type-label' ).change( function() {
		jQuery( '#edit_custom_post_type_header .label' ).html( jQuery( this ).val() );
	});

	jQuery( '#custom-post-type-onomies-custom-post-type-label' ).blur( function() {
		jQuery( '#edit_custom_post_type_header .label' ).html( jQuery( this ).val() );
	});
	
	// create a field name
	jQuery( '#custom-post-type-onomies-custom-post-type-label' ).change( function() {
		var $cpt_name = jQuery( '#custom-post-type-onomies-custom-post-type-name' );
		if ( $cpt_name.val() == '' ) {
			// convert label to name
			$cpt_name.val( jQuery( this ).val().replace( /[^a-zA-Z0-9\_\s]/i, '' ).replace( /\s/, '_' ).toLowerCase() );
		}
	});
	
	// dim post type name if already set
	jQuery( 'input#custom-post-type-onomies-custom-post-type-name' ).addClass( 'inactive' );
	jQuery( 'input#custom-post-type-onomies-custom-post-type-name' ).focus( function() {
		jQuery( this ).removeClass( 'inactive' );
	});
	jQuery( 'input#custom-post-type-onomies-custom-post-type-name' ).blur( function() {
		jQuery( this ).addClass( 'inactive' );
	});
	
	// reset properties
	jQuery( 'table.edit_custom_post_type .reset_property' ).live( 'click', function() {
		jQuery( this ).closest( 'table' ).find( 'input[type="radio"]:checked' ).removeAttr( 'checked' );
	});
	
	// take care of the advanced open-close and messages
	jQuery( 'table.edit_custom_post_type td.advanced' ).each( function() {
		jQuery( this ).children( 'table' ).custom_post_type_onomies_setup_advanced_table();		
	});
	
	// open the help tab
	jQuery( '.custom_post_type_onomies_show_help_tab' ).live( 'click', function( event ) {
		event.preventDefault();
		
		// define the help tab
		var $panel = jQuery( '#contextual-help-wrap' );
		if ( !$panel.length )
			return;

		// open help tab
		if ( !$panel.is( ':visible' ) ) {
			
			// scroll to top of page
			jQuery( 'html,body' ).scrollTop( 0 );
			
			// define the "Help" tab link
			var $link = jQuery( '#contextual-help-link' );
			
			// hide any other tab links
			jQuery( '.screen-meta-toggle' ).not( $link.parent() ).css( 'visibility', 'hidden' );

			// show the help tab
			$panel.parent().show();
			$panel.slideDown( 'fast', function() {
				// make the tab link active
				$link.addClass('screen-meta-active');
			});
		
		}
		
	});
	
});

jQuery.fn.custom_post_type_onomies_setup_advanced_table = function() {
	
	var $advanced = jQuery( this );
	
	if ( $advanced.closest( 'table.edit_custom_post_type' ).hasClass( 'show' ) )
		$advanced.custom_post_type_onomies_show_advanced_table();
	else
		$advanced.custom_post_type_onomies_hide_advanced_table();
			
}

jQuery.fn.custom_post_type_onomies_show_advanced_table = function() {
	
	var $advanced = jQuery( this );
	$advanced.removeClass( 'hide' );
	
	// get edit table
	var $edit_table = 'options';
	if ( $advanced.closest( 'table.edit_custom_post_type' ).hasClass( 'labels' ) )
		$edit_table = 'labels';
	
	// create close message
	var $close_message = null;
	if ( $edit_table == 'labels' && cpt_onomies_admin_options_L10n.close_labels != '' )
		$close_message = cpt_onomies_admin_options_L10n.close_labels;
	else if ( $edit_table == 'labels' )
		$close_message = 'Close Labels';
	else if ( cpt_onomies_admin_options_L10n.close_advanced_options != '' )
		$close_message = cpt_onomies_admin_options_L10n.close_advanced_options;
	else
		$close_message = 'Advanced Options';
	$close_message = '<span class="close_advanced">' + $close_message + '</span>';
	
	// add close message	
	if ( $advanced.parent( '.advanced_message' ).size() > 0 )
		$advanced.parent( '.advanced_message' ).remove();
	$advanced.closest( 'td' ).prepend( '<span class="advanced_message">' + $close_message + '</span>' );
	
	// if they click "close"
	$advanced.closest( 'td' ).children( '.advanced_message' ).children( '.close_advanced' ).click( function() {
		
		//remove advanced message and close table
		jQuery( this ).parent( '.advanced_message' ).remove();	
		$advanced.custom_post_type_onomies_hide_advanced_table();	
		
		// update user options
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables',
				custom_post_type_onomies_edit_table: $edit_table,
				custom_post_type_onomies_edit_table_show: 'false'
			}
		});
		
	});	
		
}

jQuery.fn.custom_post_type_onomies_hide_advanced_table = function() {
	
	var $advanced = jQuery( this );
	$advanced.addClass( 'hide' );
	
	// get edit table
	var $edit_table = 'options';
	if ( $advanced.closest( 'table.edit_custom_post_type' ).hasClass( 'labels' ) )
		$edit_table = 'labels';
	
	// create message
	var $message = null;
	if ( $edit_table == 'labels' && cpt_onomies_admin_options_L10n.labels_message1 != '' )
		$message = cpt_onomies_admin_options_L10n.labels_message1;
	else if ( $edit_table == 'labels' )
		$message = 'Instead of sticking with the boring defaults, why don\'t you customize the labels used for your custom post type. They can really add a nice touch.';
	else if ( cpt_onomies_admin_options_L10n.advanced_options_message1 != '' )
		$message = cpt_onomies_admin_options_L10n.advanced_options_message1;
	else
		$message = 'You can make your custom post type as "advanced" as you like but, beware, some of these options can get tricky. Visit the "Help" tab if you get stuck.';
		
	// add links to message
	$message += ' <span class="show_advanced">';
	if ( $edit_table == 'labels' && cpt_onomies_admin_options_L10n.labels_message2 != '' )
		$message += cpt_onomies_admin_options_L10n.labels_message2;
	else if ( $edit_table == 'labels' )
		$message += 'Customize the Labels';
	else if ( cpt_onomies_admin_options_L10n.advanced_options_message2 != '' )
		$message += cpt_onomies_admin_options_L10n.advanced_options_message2;
	else
		$message += 'Edit the Advanced Options';
	$message += '</span>';
	
	// add message
	if ( $advanced.parent( '.advanced_message' ).size() > 0 )
		$advanced.parent( '.advanced_message' ).remove();
	$advanced.closest( 'td' ).prepend( '<span class="advanced_message">' + $message + '</span>' );
	
	// if they click "show"
	$advanced.closest( 'td' ).children( '.advanced_message' ).children( '.show_advanced' ).click( function() {
		
		//remove advanced message and show table
		jQuery( this ).parent( '.advanced_message' ).remove();	
		$advanced.custom_post_type_onomies_show_advanced_table();
		
		// update user options
		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables',
				custom_post_type_onomies_edit_table: $edit_table,
				custom_post_type_onomies_edit_table_show: 'true'
			}
		});
		
	});
	
}