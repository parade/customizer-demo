( function( $ ) {
	wp.customize( 'customizer-demo-single-radio', function( value ) {
		value.bind( function( newval ) {
			alert( newval );
		} );
	} );
	wp.customize( 'customizer-demo-single-text', function( value ) {
		value.bind( function( newval ) {
			alert( newval );
		} );
	} );
} )( jQuery );
