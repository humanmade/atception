(function($){

	$( document ).on( 'click', '.alt-media-add', function(e) {

		e.preventDefault();

		var container = $( this ).parents( 'tr').find( '.alt-media-container' ),
			attachments = $.map( container.find( '.alt-media-item' ), function( item ) {
				return $( item ).data( 'id' );
			} ),
			frame_args = {
				id: 'alt-media-select',
				multiple: container.data( 'multiple' ),
				title: 'Select File',
				library: {
					type: container.data( 'mime-type' )
				},
				describe: false
			},
			frame = wp.media( frame_args );

		frame.open();

		frame.on( 'select', function() {

			var selection = frame.state().get( 'selection' ),
				holder = container.find( '.alt-media-placeholder');

			$.post( ajaxurl, {
				action: 'alt_media_get_thumb',
				alt_media_nonce: container.data( 'nonce' ),
				id: container.data( 'attachment-id' ),
				attachments: attachments,
				alt_attachments: selection.map( function( model ) {
					return model.id;
				} ),
				multiple: container.data( 'multiple' ),
				key: container.data( 'key' )
			}, function( data ) {
				if ( ! data )
					return;

				if ( ! container.data( 'multiple' ) )
					holder.html( '' );

				// update holder
				holder.append( $.map( data.attachments, function( item ) {
					return item.html;
				} ).join( '' ) );
			}, 'json' );

		} );

	} );

	$( document).on( 'click', '.alt-media-delete', function(e) {

		e.preventDefault();

		var container = $( this ).parents( '.alt-media-container' ),
			item = $( this ).parent();

		$.post( ajaxurl, {
			action: 'alt_media_delete',
			alt_media_nonce: container.data( 'nonce' ),
			id: container.data( 'attachment-id' ),
			alt_attachment: item.data( 'id' ),
			key: container.data( 'key' )
		}, function( data ) {
			if ( ! data )
				return;

			item.remove();
		}, 'json' );

	} );

})(jQuery)