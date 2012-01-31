jQuery(document).ready( function($) {
		
	// validate form
	$( '#custom-post-type-onomies-edit-cpt-form' ).validate({
		onkeyup: false
	});
	
	// validate custom post type name to make sure it contains valid characters
	$.validator.addMethod( 'custom_post_type_onomies_validate_name_characters', function( value, element ) {
		return this.optional( element ) || ( value.length <= 20 && !value.match( /([^a-z0-9\_])/ ) );
	}, 'Your post type name is invalid.' );
	
	// validate custom post type name to make sure post type doesnt already exist
	$.validator.addMethod( 'custom_post_type_onomies_validate_name', function( value, element ) {
		var validator = this, response;
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'custom_post_type_onomy_validate_if_post_type_exists',
				original_custom_post_type_onomies_cpt_name: $( "#custom-post-type-onomies-custom-post-type-original-name" ).val(),
				custom_post_type_onomies_cpt_name: value
			},
			success: function( data ) {
				response = ( data == 'true' ) ? true : false;
			}
		});
		$.validator.messages.custom_post_type_onomies_validate_name = 'The post type name, "' + value + '", already exists. Please choose another name.';
		return response;
	}, 'That post type name already exists. Please choose another name.' );
	
	// show message
	$( '.show_cpt_message' ).click( function( event ) {
		event.preventDefault();
		alert( $( this ).attr( 'alt' ) );		
	});
	
	// show delete confirmation
	$( '.delete_cpt_onomy_custom_post_type' ).click( function( event ) {
		var $message = 'Are you sure you want to delete this custom post type?\n\nThere is NO undo and once you click "OK", all of your settings will be gone.\n\n';
		$message += 'FYI: Deleting your custom post type DOES NOT delete the actual posts.\nThey\'ll be waiting for you if you decide to register this post type again.\nJust make sure you use the same name.';
		var $confirm = confirm( $message );
		if ( $confirm != true )
			event.preventDefault();
	});
	
	// dim post type name if already set
	$( 'input#custom-post-type-onomies-custom-post-type-name' ).addClass( 'inactive' );
	$( 'input#custom-post-type-onomies-custom-post-type-name' ).focus( function() {
		$( this ).removeClass( 'inactive' );
	});
	$( 'input#custom-post-type-onomies-custom-post-type-name' ).blur( function() {
		$( this ).addClass( 'inactive' );
	});
	
	// reset properties
	$( 'table.edit_custom_post_type .reset_property' ).click( function() {
		$( this ).closest( 'table' ).find( 'input[type="radio"]:checked' ).removeAttr( 'checked' );
	});
	
	// take care of the advanced open-close and messages
	$( 'table.edit_custom_post_type td.advanced' ).each( function() {
		$( this ).children( 'table' ).custom_post_type_onomies_setup_advanced_table();		
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
	var $close_message = 'Close ';
	if ( $edit_table == 'labels' ) $close_message += 'Labels';
	else $close_message += 'Advanced Options';
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
	
	// create mesage
	var $message = '';
	if ( $edit_table == 'labels' ) $message += 'Instead of sticking with the boring defaults, why don\'t you customize the labels used for your custom post type. They can really add a nice touch.';
	else $message += 'You can make your custom post type as "advanced" as you like but, beware, some of these options can get tricky. Visit the "Help" tab if you get stuck.';
	$message += ' <span class="show_advanced">';
	if ( $edit_table == 'labels' ) $message += 'Customize the Labels';
	else $message += 'Edit the Advanced Options';
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
			cache: false,
			data: {
				action: 'custom_post_type_onomy_update_edit_custom_post_type_closed_edit_tables',
				custom_post_type_onomies_edit_table: $edit_table,
				custom_post_type_onomies_edit_table_show: 'true'
			}
		});
		
	});
	
}