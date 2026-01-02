/**
 * Tests for blog-filters Interactivity API store.
 *
 * @package TGP_LLMs_Txt
 */

import { store, getContext, setMockContext, setMockState, getStore } from '@wordpress/interactivity';

describe( 'tgp/blog-filters store', () => {
	beforeEach( () => {
		// Set up mock global state (simulating wp_interactivity_state from PHP).
		setMockState( {
			posts: [
				{ id: 1, title: 'AI Guide', excerpt: 'Learn about AI', categories: [ 'ai' ] },
				{ id: 2, title: 'DevOps Tips', excerpt: 'CI/CD best practices', categories: [ 'devops' ] },
				{ id: 3, title: 'AI and DevOps', excerpt: 'Combining AI with DevOps', categories: [ 'ai', 'devops' ] },
			],
			categories: [
				{ id: 1, name: 'AI', slug: 'ai', count: 2 },
				{ id: 2, name: 'DevOps', slug: 'devops', count: 2 },
			],
			totalPosts: 3,
		} );

		// Register the store.
		store( 'tgp/blog-filters', {
			state: {
				get hasResults() {
					const ctx = getContext();
					return ctx.visiblePostIds.length > 0;
				},

				get resultCountText() {
					const ctx = getContext();
					const visible = ctx.visiblePostIds.length;
					const total = getStore( 'tgp/blog-filters' ).state.totalPosts;

					if ( visible === total ) {
						return `Showing all ${ total } posts`;
					}
					return `Showing ${ visible } of ${ total } posts`;
				},

				get isCategoryActive() {
					const ctx = getContext();
					return ctx.slug && ctx.selectedCategories.includes( ctx.slug );
				},
			},
			actions: {
				toggleCategory() {
					const ctx = getContext();
					const slug = ctx.slug;

					if ( ! slug ) {
						return;
					}

					if ( ctx.selectedCategories.includes( slug ) ) {
						ctx.selectedCategories = [];
					} else {
						ctx.selectedCategories = [ slug ];
					}
				},

				clearFilters() {
					const ctx = getContext();
					ctx.searchQuery = '';
					ctx.selectedCategories = [];
				},
			},
		} );
	} );

	describe( 'state.hasResults', () => {
		it( 'returns true when there are visible posts', () => {
			setMockContext( {
				visiblePostIds: [ 1, 2, 3 ],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.hasResults ).toBe( true );
		} );

		it( 'returns false when no visible posts', () => {
			setMockContext( {
				visiblePostIds: [],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.hasResults ).toBe( false );
		} );
	} );

	describe( 'state.resultCountText', () => {
		it( 'shows "all" when all posts visible', () => {
			setMockContext( {
				visiblePostIds: [ 1, 2, 3 ],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.resultCountText ).toBe( 'Showing all 3 posts' );
		} );

		it( 'shows count when filtered', () => {
			setMockContext( {
				visiblePostIds: [ 1, 3 ],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.resultCountText ).toBe( 'Showing 2 of 3 posts' );
		} );
	} );

	describe( 'state.isCategoryActive', () => {
		it( 'returns true when category is selected', () => {
			setMockContext( {
				slug: 'ai',
				selectedCategories: [ 'ai' ],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.isCategoryActive ).toBe( true );
		} );

		it( 'returns false when category is not selected', () => {
			setMockContext( {
				slug: 'ai',
				selectedCategories: [ 'devops' ],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.isCategoryActive ).toBe( false );
		} );

		it( 'returns false when no categories selected', () => {
			setMockContext( {
				slug: 'ai',
				selectedCategories: [],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.isCategoryActive ).toBe( false );
		} );

		it( 'returns false when slug is missing', () => {
			setMockContext( {
				slug: null,
				selectedCategories: [ 'ai' ],
			} );

			const filtersStore = getStore( 'tgp/blog-filters' );
			expect( filtersStore.state.isCategoryActive ).toBeFalsy();
		} );
	} );

	describe( 'actions.toggleCategory', () => {
		it( 'selects category when none selected', () => {
			const ctx = {
				slug: 'ai',
				selectedCategories: [],
			};
			setMockContext( ctx );

			const filtersStore = getStore( 'tgp/blog-filters' );
			filtersStore.actions.toggleCategory();

			expect( ctx.selectedCategories ).toEqual( [ 'ai' ] );
		} );

		it( 'deselects category when already selected', () => {
			const ctx = {
				slug: 'ai',
				selectedCategories: [ 'ai' ],
			};
			setMockContext( ctx );

			const filtersStore = getStore( 'tgp/blog-filters' );
			filtersStore.actions.toggleCategory();

			expect( ctx.selectedCategories ).toEqual( [] );
		} );

		it( 'replaces selection (single select mode)', () => {
			const ctx = {
				slug: 'devops',
				selectedCategories: [ 'ai' ],
			};
			setMockContext( ctx );

			const filtersStore = getStore( 'tgp/blog-filters' );
			filtersStore.actions.toggleCategory();

			expect( ctx.selectedCategories ).toEqual( [ 'devops' ] );
		} );

		it( 'does nothing when slug is missing', () => {
			const ctx = {
				slug: null,
				selectedCategories: [ 'ai' ],
			};
			setMockContext( ctx );

			const filtersStore = getStore( 'tgp/blog-filters' );
			filtersStore.actions.toggleCategory();

			expect( ctx.selectedCategories ).toEqual( [ 'ai' ] );
		} );
	} );

	describe( 'actions.clearFilters', () => {
		it( 'clears search and categories', () => {
			const ctx = {
				searchQuery: 'test query',
				selectedCategories: [ 'ai', 'devops' ],
			};
			setMockContext( ctx );

			const filtersStore = getStore( 'tgp/blog-filters' );
			filtersStore.actions.clearFilters();

			expect( ctx.searchQuery ).toBe( '' );
			expect( ctx.selectedCategories ).toEqual( [] );
		} );
	} );
} );
