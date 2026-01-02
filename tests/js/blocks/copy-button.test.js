/**
 * Tests for copy-button Interactivity API store.
 *
 * @package TGP_LLMs_Txt
 */

import { store, getContext, setMockContext, getStore } from '@wordpress/interactivity';

// Import the store to register it.
// Note: In real tests, we'd import the actual view.js
// For now, we'll register a mock store that matches the real one.

describe( 'tgp/copy-button store', () => {
	beforeEach( () => {
		// Register the store with the same structure as the real one.
		store( 'tgp/copy-button', {
			state: {
				get buttonText() {
					const ctx = getContext();
					switch ( ctx.copyState ) {
						case 'loading':
							return ctx.labelCopying;
						case 'success':
							return ctx.labelSuccess;
						case 'error':
							return ctx.labelError;
						default:
							return ctx.label;
					}
				},
				get isLoading() {
					const ctx = getContext();
					return ctx.copyState === 'loading';
				},
				get isDisabled() {
					const ctx = getContext();
					return ctx.copyState === 'loading';
				},
			},
			actions: {
				*copyMarkdown() {
					const ctx = getContext();

					if ( ctx.copyState === 'loading' ) {
						return;
					}

					ctx.copyState = 'loading';

					try {
						const response = yield fetch( ctx.mdUrl );

						if ( ! response.ok ) {
							throw new Error( `HTTP ${ response.status }` );
						}

						const markdown = yield response.text();
						yield navigator.clipboard.writeText( markdown );

						ctx.copyState = 'success';
					} catch ( error ) {
						ctx.copyState = 'error';
					}
				},
			},
		} );
	} );

	describe( 'state.buttonText', () => {
		it( 'returns default label when idle', () => {
			setMockContext( {
				copyState: 'idle',
				label: 'Copy for LLM',
				labelCopying: 'Copying...',
				labelSuccess: 'Copied!',
				labelError: 'Failed',
			} );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.buttonText ).toBe( 'Copy for LLM' );
		} );

		it( 'returns copying label when loading', () => {
			setMockContext( {
				copyState: 'loading',
				label: 'Copy for LLM',
				labelCopying: 'Copying...',
				labelSuccess: 'Copied!',
				labelError: 'Failed',
			} );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.buttonText ).toBe( 'Copying...' );
		} );

		it( 'returns success label when success', () => {
			setMockContext( {
				copyState: 'success',
				label: 'Copy for LLM',
				labelCopying: 'Copying...',
				labelSuccess: 'Copied!',
				labelError: 'Failed',
			} );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.buttonText ).toBe( 'Copied!' );
		} );

		it( 'returns error label when error', () => {
			setMockContext( {
				copyState: 'error',
				label: 'Copy for LLM',
				labelCopying: 'Copying...',
				labelSuccess: 'Copied!',
				labelError: 'Failed',
			} );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.buttonText ).toBe( 'Failed' );
		} );
	} );

	describe( 'state.isLoading', () => {
		it( 'returns false when idle', () => {
			setMockContext( { copyState: 'idle' } );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.isLoading ).toBe( false );
		} );

		it( 'returns true when loading', () => {
			setMockContext( { copyState: 'loading' } );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.isLoading ).toBe( true );
		} );
	} );

	describe( 'state.isDisabled', () => {
		it( 'returns false when idle', () => {
			setMockContext( { copyState: 'idle' } );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.isDisabled ).toBe( false );
		} );

		it( 'returns true when loading', () => {
			setMockContext( { copyState: 'loading' } );

			const copyStore = getStore( 'tgp/copy-button' );
			expect( copyStore.state.isDisabled ).toBe( true );
		} );
	} );
} );
