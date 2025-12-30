( function() {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function() {
		const copyButtons = document.querySelectorAll( '.tgp-copy-btn' );

		copyButtons.forEach( function( button ) {
			button.addEventListener( 'click', function( e ) {
				e.preventDefault();

				const postId = this.getAttribute( 'data-post-id' );
				const btnText = this.querySelector( '.tgp-btn-text' );
				const originalText = btnText ? btnText.textContent : 'Copy for LLM';

				// Show loading state.
				if ( btnText ) {
					btnText.textContent = 'Copying...';
				}
				button.disabled = true;

				// Fetch markdown via AJAX.
				const formData = new FormData();
				formData.append( 'action', 'tgp_get_markdown' );
				formData.append( 'post_id', postId );
				formData.append( 'nonce', window.tgpLlmBlock ? window.tgpLlmBlock.nonce : '' );

				fetch( window.tgpLlmBlock ? window.tgpLlmBlock.ajaxUrl : '/wp-admin/admin-ajax.php', {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				} )
				.then( function( response ) {
					return response.json();
				} )
				.then( function( data ) {
					if ( data.success && data.data.markdown ) {
						return navigator.clipboard.writeText( data.data.markdown ).then( function() {
							if ( btnText ) {
								btnText.textContent = 'Copied!';
							}
							setTimeout( function() {
								if ( btnText ) {
									btnText.textContent = originalText;
								}
								button.disabled = false;
							}, 2000 );
						} );
					} else {
						throw new Error( data.data || 'Failed to get markdown' );
					}
				} )
				.catch( function( error ) {
					console.error( 'Copy failed:', error );
					if ( btnText ) {
						btnText.textContent = 'Failed';
					}
					setTimeout( function() {
						if ( btnText ) {
							btnText.textContent = originalText;
						}
						button.disabled = false;
					}, 2000 );
				} );
			} );
		} );
	} );
} )();
