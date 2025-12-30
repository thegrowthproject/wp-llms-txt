( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const {
		useBlockProps,
		InspectorControls,
		RichText
	} = wp.blockEditor;
	const {
		PanelBody,
		ToggleControl,
		__experimentalToggleGroupControl: ToggleGroupControl,
		__experimentalToggleGroupControlOption: ToggleGroupControlOption
	} = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;
	const { SVG, Path } = wp.primitives;

	// Copy icon SVG
	const copyIcon = el( 'svg', {
		xmlns: 'http://www.w3.org/2000/svg',
		width: 16,
		height: 16,
		viewBox: '0 0 24 24',
		fill: 'none',
		stroke: 'currentColor',
		strokeWidth: 2,
		strokeLinecap: 'round',
		strokeLinejoin: 'round'
	},
		el( 'rect', { x: 9, y: 9, width: 13, height: 13, rx: 2, ry: 2 } ),
		el( 'path', { d: 'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1' } )
	);

	// Block icon - uses WordPress primitives for proper rendering
	const blockIcon = el( SVG, {
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24'
	},
		el( Path, {
			d: 'M8 12.5h8M19 6.5H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2Z',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: 1.5
		} )
	);

	registerBlockType( 'tgp/copy-button', {
		icon: blockIcon,

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { label, showIcon, width } = attributes;

			// Get block props (includes Block Supports styles and classes)
			const blockProps = useBlockProps();

			// Parse blockProps to split classes between wrapper and inner button
			const allClasses = blockProps.className ? blockProps.className.split( ' ' ) : [];

			// Classes for outer wrapper (is-style-*, width classes)
			const wrapperClasses = [ 'wp-block-button' ];

			// Classes for inner button
			const innerClasses = [ 'wp-block-button__link', 'wp-element-button', 'tgp-copy-btn' ];

			// Distribute classes
			allClasses.forEach( function( cls ) {
				if ( cls.indexOf( 'is-style-' ) === 0 ) {
					// Style classes go on outer wrapper
					wrapperClasses.push( cls );
				} else if ( cls.indexOf( 'has-' ) === 0 ) {
					// Color/feature classes go on inner button
					innerClasses.push( cls );
				} else if ( cls.indexOf( 'wp-block-tgp-' ) === 0 ) {
					// Block identifier - skip (we use wp-block-button structure)
				}
			} );

			// Add width classes to wrapper
			if ( width ) {
				wrapperClasses.push( 'has-custom-width' );
				wrapperClasses.push( 'wp-block-button__width-' + width );
			}

			// Build inner button props with styles
			const innerProps = {
				className: innerClasses.join( ' ' )
			};

			// Apply inline styles from blockProps to inner button
			if ( blockProps.style ) {
				innerProps.style = blockProps.style;
			}

			return el( Fragment, {},
				// Inspector Controls (Sidebar)
				el( InspectorControls, {},
					el( PanelBody, {
						title: __( 'Settings', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( ToggleGroupControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Width', 'tgp-llms-txt' ),
							value: width ? String( width ) : undefined,
							onChange: function( value ) {
								setAttributes( { width: value ? Number( value ) : undefined } );
							},
							isBlock: true
						},
							el( ToggleGroupControlOption, { key: '25', value: '25', label: '25%' } ),
							el( ToggleGroupControlOption, { key: '50', value: '50', label: '50%' } ),
							el( ToggleGroupControlOption, { key: '75', value: '75', label: '75%' } ),
							el( ToggleGroupControlOption, { key: '100', value: '100', label: '100%' } )
						),
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show Icon', 'tgp-llms-txt' ),
							checked: showIcon,
							onChange: function( value ) {
								setAttributes( { showIcon: value } );
							}
						} )
					)
				),

				// Block Preview
				el( 'div', { className: wrapperClasses.join( ' ' ) },
					el( 'button', innerProps,
						showIcon && el( 'span', { className: 'tgp-btn-icon' }, copyIcon ),
						el( RichText, {
							tagName: 'span',
							className: 'tgp-btn-text',
							value: label,
							onChange: function( value ) {
								setAttributes( { label: value } );
							},
							placeholder: __( 'Copy for LLM', 'tgp-llms-txt' ),
							allowedFormats: []
						} )
					)
				)
			);
		},

		save: function() {
			return null;
		}
	} );
} )( window.wp );
