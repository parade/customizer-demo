( function( $ ) {
	
	wp.customize( 'customizer-demo-single-radio', function( value ) {
		value.bind( function( newval ) {
			console.log('raido',newval);
			var h1 = 48, p = 16;
			if ( 'large' === newval ) {
				h1 = 54;
				p = 24;
			} else if ( 'xlarge' === newval ) {
				h1 = 64;
				p = 32;
			}
			$( 'h1' ).css( 'font-size', h1 );
			$( '.entry-content' ).css( 'font-size', p );
		} );
	} );

	wp.customize( 'customizer-demo-single-text', function( value ) {
		value.bind( function( newval ) {
			$( '.site-title' ).html( newval );
		} );
	} );

	wp.customize( 'customizer-demo-single-checkbox', function( value ) {
		value.bind( function( newval ) {
			if ( newval ) {
				$( '.site-description' ).show();
			} else {
				$( '.site-description' ).hide();
			}
		} );
	} );

	wp.customize( 'customizer-demo-single-image', function( value ) {
		value.bind( function( newval ) {
			$( '.site-header' ).css( 'background', 'url(' + newval + ')' );
		} );
	} );

	wp.customize( 'customizer-demo-single-color', function( value ) {
		value.bind( function( newval ) {
			$( '.site-title' ).css( { 'color': newval } );
			$( '.site-description' ).css( { 'color': newval } );
		} );
	} );

	wp.customize( 'customizer-demo-single-select', function( value ) {
		value.bind( function( newval ) {
			if ( 'hiding' === newval ) {
				$( '#navbar' ).slideUp();
			} else {
				$( '#navbar' ).slideDown();
			}
		} );
	} );

} )( jQuery );
