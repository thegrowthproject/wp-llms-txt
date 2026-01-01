( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InnerBlocks, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;
	const { SVG, Path } = wp.primitives;

	// Block icon - filter/search combination.
	const blockIcon = el( SVG, {
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24'
	},
		el( Path, {
			d: 'M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z',
			fill: 'currentColor'
		} )
	);

	// Template for default inner blocks.
	const TEMPLATE = [
		[ 'tgp/blog-search', {} ],
		[ 'tgp/blog-category-filter', {} ]
	];

	registerBlockType( 'tgp/blog-filters', {
		icon: blockIcon,

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { showResultCount } = attributes;

			const blockProps = useBlockProps( {
				className: 'wp-block-tgp-blog-filters'
			} );

			return el( Fragment, {},
				// Inspector Controls (Sidebar).
				el( InspectorControls, {},
					el( PanelBody, {
						title: __( 'Settings', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show result count', 'tgp-llms-txt' ),
							help: __( 'Display the number of matching posts.', 'tgp-llms-txt' ),
							checked: showResultCount,
							onChange: function( value ) {
								setAttributes( { showResultCount: value } );
							}
						} )
					)
				),

				// Block Preview.
				el( 'div', blockProps,
					el( InnerBlocks, {
						template: TEMPLATE,
						allowedBlocks: [ 'tgp/blog-search', 'tgp/blog-category-filter' ],
						templateLock: false
					} ),
					showResultCount && el( 'div', {
						className: 'wp-block-tgp-blog-filters__result-count'
					}, __( 'Showing X of Y posts', 'tgp-llms-txt' ) )
				)
			);
		},

		save: function() {
			return el( InnerBlocks.Content );
		}
	} );
} )( window.wp );
