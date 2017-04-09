jQuery( function() {

jQuery( document ).on( 'change', '.kgr-medals-settings-campaign-open-checkbox', campaign_open_checkbox_change );

jQuery( '.kgr-medals-settings-campaign-open-checkbox' ).each( campaign_open_checkbox_change );

function campaign_open_checkbox_change() {
	var checkbox = jQuery( this );
	checkbox.siblings( 'input[type="hidden"]' ).val( checkbox.prop( 'checked' ) ? 'on' : 'off' );
}

} );
