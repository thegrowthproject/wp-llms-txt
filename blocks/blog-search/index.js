( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const {
		PanelBody,
		ToggleControl,
		TextControl,
		__experimentalUnitControl: UnitControl
	} = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;
	const { SVG, Path } = wp.primitives;

	// Search icon.
	const searchIcon = el( SVG, {
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24'
	},
		el( Path, {
			d: 'M13.5 6C10.5 6 8 8.5 8 11.5c0 1.1.3 2.1.9 3l-3.4 3 1 1.1 3.4-2.9c1 .9 2.2 1.4 3.6 1.4 3 0 5.5-2.5 5.5-5.5C19 8.5 16.5 6 13.5 6zm0 9.5c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z',
			fill: 'currentColor'
		} )
	);

	// Block icon.
	const blockIcon = searchIcon;

	registerBlockType( 'tgp/blog-search', {
		icon: blockIcon,

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { placeholder, showIcon, showClearButton, width } = attributes;

			// Build block styles (applied to outer wrapper).
			const blockStyles = {};
			if ( width ) {
				blockStyles.width = width;
			}

			const blockProps = useBlockProps( {
				className: 'wp-block-tgp-blog-search',
				style: Object.keys( blockStyles ).length > 0 ? blockStyles : undefined
			} );

			return el( Fragment, {},
				// Inspector Controls (Sidebar) - Settings.
				el( InspectorControls, {},
					el( PanelBody, {
						title: __( 'Settings', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( TextControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Placeholder text', 'tgp-llms-txt' ),
							value: placeholder,
							onChange: function( value ) {
								setAttributes( { placeholder: value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show search icon', 'tgp-llms-txt' ),
							checked: showIcon,
							onChange: function( value ) {
								setAttributes( { showIcon: value } );
							}
						} ),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show clear button', 'tgp-llms-txt' ),
							checked: showClearButton,
							onChange: function( value ) {
								setAttributes( { showClearButton: value } );
							}
						} )
					)
				),

				// Inspector Controls (Sidebar) - Styles.
				el( InspectorControls, { group: 'styles' },
					el( PanelBody, {
						title: __( 'Dimensions', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( UnitControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Width', 'tgp-llms-txt' ),
							value: width || '',
							onChange: function( value ) {
								setAttributes( { width: value } );
							},
							units: [
								{ value: 'px', label: 'px', default: 400 },
								{ value: '%', label: '%', default: 100 },
								{ value: 'em', label: 'em', default: 25 },
								{ value: 'rem', label: 'rem', default: 25 }
							]
						} )
					)
				),

				// Block Preview.
				el( 'div', blockProps,
					el( 'div', {
						className: 'wp-block-tgp-blog-search__wrapper'
					},
						showIcon && el( 'span', {
							className: 'wp-block-tgp-blog-search__icon',
							'aria-hidden': 'true'
						},
							el( 'svg', {
								xmlns: 'http://www.w3.org/2000/svg',
								width: 20,
								height: 20,
								viewBox: '0 0 24 24',
								fill: 'none',
								stroke: 'currentColor',
								strokeWidth: 2,
								strokeLinecap: 'round',
								strokeLinejoin: 'round'
							},
								el( 'circle', { cx: 11, cy: 11, r: 8 } ),
								el( 'line', { x1: 21, y1: 21, x2: 16.65, y2: 16.65 } )
							)
						),
						el( 'input', {
							type: 'search',
							className: 'wp-block-tgp-blog-search__input',
							placeholder: placeholder,
							disabled: true
						} ),
						showClearButton && el( 'button', {
							type: 'button',
							className: 'wp-block-tgp-blog-search__clear',
							'aria-label': __( 'Clear search', 'tgp-llms-txt' ),
							disabled: true
						}, '\u00D7' )
					)
				)
			);
		},

		save: function() {
			return null;
		}
	} );
} )( window.wp );
