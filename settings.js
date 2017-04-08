jQuery( function() {

jQuery( '.kgr_medals_settings_term' ).each( function() {
	var div = jQuery( this );
	div.find( '.nav-tab' ).click( function() {
		var tab = jQuery( this );
		tab.toggleClass( 'nav-tab-active' ).siblings( '.nav-tab' ).removeClass( 'nav-tab-active' );
		var id = tab.prop( 'href' ).split( '#' )[1];
		div.find( '.kgr_medals_settings_term_' + id ).toggle().siblings( 'table' ).hide();
		return false;
	} );
} );

} );
