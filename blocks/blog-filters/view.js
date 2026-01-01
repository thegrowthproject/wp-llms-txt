/**
 * WordPress Interactivity API store for Blog Filters block.
 *
 * Handles search, category filtering, and URL state synchronization.
 * Single category selection - only one category can be active at a time.
 *
 * @package TGP_LLMs_Txt
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions, callbacks } = store( 'tgp/blog-filters', {
	state: {
		// Global state values (from wp_interactivity_state in PHP).
		posts: [],
		categories: [],
		totalPosts: 0,

		/**
		 * Whether there are any visible posts.
		 */
		get hasResults() {
			const ctx = getContext();
			return ctx.visiblePostIds.length > 0;
		},

		/**
		 * Result count text for display.
		 */
		get resultCountText() {
			const ctx = getContext();
			const visible = ctx.visiblePostIds.length;
			const total = state.totalPosts;

			if ( visible === total ) {
				return `Showing all ${ total } posts`;
			}
			return `Showing ${ visible } of ${ total } posts`;
		},

		/**
		 * Check if the current category pill is active.
		 * Reads slug from element's own context (merged with parent).
		 */
		get isCategoryActive() {
			const ctx = getContext();
			// The slug comes from the button's own data-wp-context.
			return ctx.slug && ctx.selectedCategories.includes( ctx.slug );
		}
	},

	actions: {
		/**
		 * Update search query value (for input binding only).
		 * Does not trigger filtering - that happens on blur or Enter.
		 */
		updateSearch( event ) {
			const ctx = getContext();
			ctx.searchQuery = event.target.value;
		},

		/**
		 * Submit search - apply filters.
		 * Called on blur or Enter key.
		 */
		submitSearch() {
			actions.applyFilters();
		},

		/**
		 * Handle keydown in search field.
		 * Apply filters on Enter key.
		 */
		handleSearchKeydown( event ) {
			if ( event.key === 'Enter' ) {
				event.preventDefault();
				actions.applyFilters();
			}
		},

		/**
		 * Clear the search input.
		 */
		clearSearch() {
			const ctx = getContext();
			ctx.searchQuery = '';
			actions.applyFilters();
		},

		/**
		 * Toggle a category selection (single selection mode).
		 * Only one category can be active at a time.
		 */
		toggleCategory() {
			const ctx = getContext();
			const slug = ctx.slug;

			if ( ! slug ) {
				return;
			}

			// Single selection: toggle off if already selected, otherwise select only this one.
			if ( ctx.selectedCategories.includes( slug ) ) {
				// Deselect - show all posts.
				ctx.selectedCategories = [];
			} else {
				// Select only this category.
				ctx.selectedCategories = [ slug ];
			}

			actions.applyFilters();
		},

		/**
		 * Clear all filters.
		 */
		clearFilters() {
			const ctx = getContext();
			ctx.searchQuery = '';
			ctx.selectedCategories = [];
			actions.applyFilters();
		},

		/**
		 * Apply filters and update visible posts.
		 * Single category selection mode.
		 */
		applyFilters() {
			const ctx = getContext();
			const searchLower = ctx.searchQuery.toLowerCase().trim();
			const selectedCats = ctx.selectedCategories;

			const visibleIds = [];

			for ( const post of state.posts ) {
				let matchesSearch = true;
				let matchesCategories = true;

				// Check search query against title and excerpt.
				if ( searchLower ) {
					const titleLower = post.title.toLowerCase();
					const excerptLower = post.excerpt.toLowerCase();
					matchesSearch = titleLower.includes( searchLower ) ||
									excerptLower.includes( searchLower );
				}

				// Check categories with AND logic.
				if ( selectedCats.length > 0 ) {
					matchesCategories = selectedCats.every(
						( slug ) => post.categories.includes( slug )
					);
				}

				if ( matchesSearch && matchesCategories ) {
					visibleIds.push( post.id );
				}
			}

			// Update context.
			ctx.visiblePostIds = visibleIds;

			// Update DOM - show/hide post elements.
			actions.updatePostVisibility( visibleIds );
		},

		/**
		 * Update post element visibility in the DOM.
		 */
		updatePostVisibility( visibleIds ) {
			// Find all post elements with data-post-id attribute.
			const postElements = document.querySelectorAll( '[data-post-id]' );

			postElements.forEach( ( element ) => {
				const postId = parseInt( element.dataset.postId, 10 );
				if ( visibleIds.includes( postId ) ) {
					element.removeAttribute( 'hidden' );
					element.style.display = '';
				} else {
					element.setAttribute( 'hidden', '' );
					element.style.display = 'none';
				}
			} );
		}
	},

	callbacks: {
		/**
		 * Initialize on load - apply initial filters if URL has params.
		 */
		init() {
			const ctx = getContext();

			// If we have initial filters from URL, ensure posts are filtered.
			if ( ctx.searchQuery || ctx.selectedCategories.length > 0 ) {
				actions.applyFilters();
			} else {
				// No filters - show all posts.
				ctx.visiblePostIds = state.posts.map( ( p ) => p.id );
			}
		},

		/**
		 * Sync filter state to URL.
		 */
		syncUrl() {
			const ctx = getContext();
			const url = new URL( window.location.href );

			// Update search param.
			if ( ctx.searchQuery ) {
				url.searchParams.set( 'search', ctx.searchQuery );
			} else {
				url.searchParams.delete( 'search' );
			}

			// Update categories param.
			if ( ctx.selectedCategories.length > 0 ) {
				url.searchParams.set( 'categories', ctx.selectedCategories.join( ',' ) );
			} else {
				url.searchParams.delete( 'categories' );
			}

			// Update URL without page reload.
			window.history.replaceState( {}, '', url.toString() );
		}
	}
} );
