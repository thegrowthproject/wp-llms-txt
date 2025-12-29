( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls, PanelColorSettings } = wp.blockEditor;
	const { PanelBody, ToggleControl, SelectControl, TextControl } = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;

	// Icons as SVG strings
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

	const viewIcon = el( 'svg', {
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
		el( 'path', { d: 'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z' } ),
		el( 'polyline', { points: '14 2 14 8 20 8' } ),
		el( 'line', { x1: 16, y1: 13, x2: 8, y2: 13 } ),
		el( 'line', { x1: 16, y1: 17, x2: 8, y2: 17 } ),
		el( 'polyline', { points: '10 9 9 9 8 9' } )
	);

	// Block icon
	const blockIcon = el( 'svg', {
		xmlns: 'http://www.w3.org/2000/svg',
		width: 24,
		height: 24,
		viewBox: '0 0 24 24',
		fill: 'none',
		stroke: 'currentColor',
		strokeWidth: 2
	},
		el( 'rect', { x: 9, y: 9, width: 13, height: 13, rx: 2, ry: 2 } ),
		el( 'path', { d: 'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1' } )
	);

	registerBlockType( 'tgp/llm-buttons', {
		icon: blockIcon,

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const {
				copyButtonBgColor,
				copyButtonTextColor,
				viewButtonBgColor,
				viewButtonTextColor,
				showIcons,
				layout,
				copyButtonLabel,
				viewButtonLabel
			} = attributes;

			const blockProps = useBlockProps( {
				className: 'wp-block-tgp-llm-buttons ' + ( layout === 'stack' ? 'is-layout-stack' : 'is-layout-row' )
			} );

			return el( Fragment, {},
				// Inspector Controls (Sidebar)
				el( InspectorControls, {},
					// Settings Panel
					el( PanelBody, {
						title: __( 'Button Settings', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( SelectControl, {
							label: __( 'Layout', 'tgp-llms-txt' ),
							value: layout,
							options: [
								{ label: __( 'Row', 'tgp-llms-txt' ), value: 'row' },
								{ label: __( 'Stack', 'tgp-llms-txt' ), value: 'stack' }
							],
							onChange: function( value ) {
								setAttributes( { layout: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Show Icons', 'tgp-llms-txt' ),
							checked: showIcons,
							onChange: function( value ) {
								setAttributes( { showIcons: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Copy Button Label', 'tgp-llms-txt' ),
							value: copyButtonLabel,
							onChange: function( value ) {
								setAttributes( { copyButtonLabel: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'View Button Label', 'tgp-llms-txt' ),
							value: viewButtonLabel,
							onChange: function( value ) {
								setAttributes( { viewButtonLabel: value } );
							}
						} )
					),
					// Copy Button Colors
					el( PanelColorSettings, {
						title: __( 'Copy Button Colors', 'tgp-llms-txt' ),
						initialOpen: false,
						colorSettings: [
							{
								value: copyButtonBgColor,
								onChange: function( value ) {
									setAttributes( { copyButtonBgColor: value } );
								},
								label: __( 'Background', 'tgp-llms-txt' )
							},
							{
								value: copyButtonTextColor,
								onChange: function( value ) {
									setAttributes( { copyButtonTextColor: value } );
								},
								label: __( 'Text', 'tgp-llms-txt' )
							}
						]
					} ),
					// View Button Colors
					el( PanelColorSettings, {
						title: __( 'View Button Colors', 'tgp-llms-txt' ),
						initialOpen: false,
						colorSettings: [
							{
								value: viewButtonBgColor,
								onChange: function( value ) {
									setAttributes( { viewButtonBgColor: value } );
								},
								label: __( 'Background', 'tgp-llms-txt' )
							},
							{
								value: viewButtonTextColor,
								onChange: function( value ) {
									setAttributes( { viewButtonTextColor: value } );
								},
								label: __( 'Text', 'tgp-llms-txt' )
							}
						]
					} )
				),

				// Block Preview
				el( 'div', blockProps,
					el( 'button', {
						type: 'button',
						className: 'tgp-llm-btn tgp-copy-btn',
						style: {
							backgroundColor: copyButtonBgColor,
							color: copyButtonTextColor
						}
					},
						showIcons && el( 'span', { className: 'tgp-btn-icon' }, copyIcon ),
						el( 'span', { className: 'tgp-btn-text' }, copyButtonLabel )
					),
					el( 'a', {
						href: '#',
						className: 'tgp-llm-btn tgp-view-btn',
						style: {
							backgroundColor: viewButtonBgColor,
							color: viewButtonTextColor
						},
						onClick: function( e ) { e.preventDefault(); }
					},
						showIcons && el( 'span', { className: 'tgp-btn-icon' }, viewIcon ),
						el( 'span', { className: 'tgp-btn-text' }, viewButtonLabel )
					)
				)
			);
		},

		// Dynamic block - save returns null
		save: function() {
			return null;
		}
	} );
} )( window.wp );
