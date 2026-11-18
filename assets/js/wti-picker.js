(function ( $ ) {
	'use strict';

	var frame;

	// Open the media library when "Choose image" is clicked.
	$( document ).on( 'click', '.wti-select-image', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.wti-picker' );

		frame = wp.media( {
			title: 'Choose term image',
			button: { text: 'Use this image' },
			multiple: false,
			library: { type: 'image' }
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail : { url: attachment.url, width: '', height: '' };
			$wrap.find( '#wti-image-id' ).val( attachment.id );
			$wrap.find( '.wti-preview' ).html(
				'<img src="' + thumb.url + '" width="' + thumb.width + '" height="' + thumb.height + '" alt="">'
			);
			$wrap.find( '.wti-remove-image' ).show();
		} );

		frame.open();
	} );

	// Clear the selection when "Remove" is clicked.
	$( document ).on( 'click', '.wti-remove-image', function ( e ) {
		e.preventDefault();

		var $wrap = $( this ).closest( '.wti-picker' );
		$wrap.find( '#wti-image-id' ).val( '0' );
		$wrap.find( '.wti-preview' ).empty();
		$( this ).hide();
	} );
})( jQuery );
