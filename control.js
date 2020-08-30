jQuery( document ).on( 'click', '.kgr-medals-control-add', function() {
	var container = jQuery( this ).parents( '.kgr-medals-control-container' );
	var items = container.find( '.kgr-medals-control-items' );
	var item = container.find( '.kgr-medals-control-item0' ).find( '.kgr-medals-control-item' );
	item.clone().appendTo( items ).children( 'select' ).first().focus();
} );

jQuery( document ).on( 'click', '.kgr-medals-control-up', function() {
	var item = jQuery( this ).parents( '.kgr-medals-control-item' );
	var target = item.prev();
	if ( target.length === 0 )
		return;
	item.detach().insertBefore( target );
} );

jQuery( document ).on( 'click', '.kgr-medals-control-down', function() {
	var item = jQuery( this ).parents( '.kgr-medals-control-item' );
	var target = item.next();
	if ( target.length === 0 )
		return;
	item.detach().insertAfter( target );
} );

jQuery( document ).on( 'click', '.kgr-medals-control-delete', function() {
	var item = jQuery( this ).parents( '.kgr-medals-control-item' );
	item.remove();
} );
