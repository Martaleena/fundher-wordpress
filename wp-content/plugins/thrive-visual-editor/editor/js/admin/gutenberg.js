( function ( $ ) {
	var ThriveGutenbergSwitch = {

		init: function () {
			var $gutenberg = $( '#editor' ),
				$architectNotificationContent = $( '#thrive-gutenberg-switch' ).html(),
				$architectDisplay = $( '<div>' ).append( $architectNotificationContent ),
				$architectLauncher = $architectDisplay.find( '#thrive_preview_button' );

			setTimeout( function () {
				if ( $architectNotificationContent.indexOf( 'postbox' ) !== - 1 ) {
					$gutenberg.find( '.editor-post-title' ).append( $architectDisplay );
					$gutenberg.find( '.editor-block-list__layout' ).hide();
					$gutenberg.find( '.editor-post-title__block' ).css( 'margin-bottom', '0' );
					$gutenberg.find( '.editor-writing-flow__click-redirect' ).hide();
					$gutenberg.find( '.edit-post-header-toolbar' ).css( 'visibility', 'hidden' );
				} else {
					$gutenberg.find( '.edit-post-header-toolbar' ).append( $architectLauncher );
					$architectLauncher.on( 'click', function () {
						$gutenberg.find( '.editor-block-list__layout' ).hide();
					} );
					$gutenberg.find( '.edit-post-header-toolbar' ).css( 'visibility', 'visible' );
				}

				$( '#tcb2-show-wp-editor' ).on( 'click', function () {
					var $editlink = $gutenberg.find( '.tcb-enable-editor' ),
						$postbox = $editlink.closest( '.postbox' );

					$.ajax( {
						type: 'post',
						url: ajaxurl,
						dataType: 'json',
						data: {
							_nonce: TCB_Post_Edit_Data.admin_nonce,
							post_id: this.getAttribute( 'data-id' ),
							action: 'tcb_admin_ajax_controller',
							route: 'disable_tcb'
						}
					} ).done( function ( response ) {
					} );

					$postbox.next( '.tcb-flags' ).find( 'input' ).prop( 'disabled', false );
					$postbox.remove();
					$gutenberg.find( '.editor-block-list__layout' ).show();
					$gutenberg.find( '.edit-post-header-toolbar' ).append( $architectLauncher );
					$gutenberg.find( '.edit-post-header-toolbar' ).css( 'visibility', 'visible' );
				} );

				$architectLauncher.on( 'click', function () {
					$.ajax( {
						type: 'post',
						url: ajaxurl,
						dataType: 'json',
						data: {
							_nonce: TCB_Post_Edit_Data.admin_nonce,
							post_id: this.getAttribute( 'data-id' ),
							action: 'tcb_admin_ajax_controller',
							route: 'change_post_status_gutenberg'
						}
					} )
				} );

				$( '.tcb-revert' ).on( 'click', function () {
					if ( confirm( 'Are you sure you want to DELETE all of the content that was created in this landing page and revert to the theme page? \n If you click OK, any custom content you added to the landing page will be deleted.' ) ) {
						location.href = location.href + '&tve_revert_theme=1';
						$gutenberg.find( '.edit-post-header-toolbar' ).css( 'visibility', 'visible' );
					}
				} );

			}, 1 );
		}
	};

	$( function () {
		ThriveGutenbergSwitch.init();
	} );

}( jQuery ) );
