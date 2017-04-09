jQuery( function() {

var busy = false;

jQuery( 'a.kgr_medals_clear' ).click( function() {
	if ( busy )
		return false;
	busy = true;
	var link = jQuery( this );
	jQuery.get( link.prop( 'href' ) ).success( function() {
		jQuery( '.kgr_medals_post_medal_active' ).removeClass( 'kgr_medals_post_medal_active' );
		jQuery( '.kgr_medals_stand_step_html' ).html( '' );
		update_total();
	} ).always( function() {
		busy = false;
	} );
	return false;
} );

jQuery( 'a.kgr_medals_post_medal' ).click( function() {
	if ( busy )
		return false;
	busy = true;
	var link = jQuery( this );
	jQuery.get( link.prop( 'href' ) ).success( function() {
		var color = '[data-kgr_medals_color="' + link.data( 'kgr_medals_color' ) + '"]';
		if ( link.hasClass( 'kgr_medals_post_medal_active' ) ) {
			link.removeClass( 'kgr_medals_post_medal_active' );
			jQuery( '.kgr_medals_stand_step' ).has( color ).find( '.kgr_medals_stand_step_html' ).html( '' );
		} else {
			jQuery( '.kgr_medals_post_medal_active' ).filter( color ).removeClass( 'kgr_medals_post_medal_active' );
			link.siblings( '.kgr_medals_post_medal_active' ).each( function() {
				var link = jQuery( this );
				var color = '[data-kgr_medals_color="' + link.data( 'kgr_medals_color' ) + '"]';
				jQuery( '.kgr_medals_stand_step' ).has( color ).find( '.kgr_medals_stand_step_html' ).html( '' );
			} ).removeClass( 'kgr_medals_post_medal_active' );
			link.addClass( 'kgr_medals_post_medal_active' );
			var html = link.parents( '.kgr_medals_post' ).find( '.kgr_medals_post_html' ).html();
			jQuery( '.kgr_medals_stand_step' ).has( color ).find( '.kgr_medals_stand_step_html' ).html( html );
		}
		update_total();
	} ).always( function() {
		busy = false;
	} );
	return false;
} );

jQuery( '.kgr_medals_post_medal_active' ).each( function() {
	var link = jQuery( this );
	var color = '[data-kgr_medals_color="' + link.data( 'kgr_medals_color' ) + '"]';
	var html = link.parents( '.kgr_medals_post' ).find( '.kgr_medals_post_html' ).html();
	jQuery( '.kgr_medals_stand_step' ).has( color ).find( '.kgr_medals_stand_step_html' ).html( html );
} );

function has_own_vote() {
	return jQuery( '.kgr_medals_post_medal_active' ).length !== 0;
}
var old_own_vote = has_own_vote();

function update_total() {
	var new_own_vote = has_own_vote();
	if ( new_own_vote !== old_own_vote ) {
		var votes = parseInt( jQuery( '.kgr_medals_total' ).html() );
		jQuery( '.kgr_medals_total' ).html( new_own_vote ? ( votes + 1 ) : ( votes - 1 ) );
		old_own_vote = new_own_vote;
	}
}

} );
