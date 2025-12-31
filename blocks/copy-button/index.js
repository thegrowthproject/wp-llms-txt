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
			const { attributes, setAttributes, className } = props;
			const {
				label,
				showIcon,
				width,
				backgroundColor,
				textColor,
				gradient,
				style
			} = attributes;

			// Detect style variation from className
			const styleMatch = className ? className.match( /is-style-([a-z0-9-]+)/ ) : null;
			const styleVariation = styleMatch ? styleMatch[1] : 'fill';
			const hasStyleVariation = styleVariation && styleVariation !== 'fill';

			// Build outer wrapper classes
			const wrapperClasses = [ 'wp-block-button' ];
			if ( width ) {
				wrapperClasses.push( 'has-custom-width' );
				wrapperClasses.push( 'wp-block-button__width-' + width );
			}

			// Get block props with our custom wrapper class
			const blockProps = useBlockProps( {
				className: wrapperClasses.join( ' ' )
			} );

			// Build inner button classes
			const innerClasses = [ 'wp-block-button__link', 'wp-element-button', 'tgp-copy-btn' ];

			// Only add color classes if NOT using a style variation
			if ( ! hasStyleVariation ) {
				if ( backgroundColor ) {
					innerClasses.push( 'has-background' );
					innerClasses.push( 'has-' + backgroundColor + '-background-color' );
				}
				if ( textColor ) {
					innerClasses.push( 'has-text-color' );
					innerClasses.push( 'has-' + textColor + '-color' );
				}
				if ( gradient ) {
					innerClasses.push( 'has-background' );
					innerClasses.push( 'has-' + gradient + '-gradient-background' );
				}
				// Check for custom color values
				if ( style && style.color ) {
					if ( style.color.background || style.color.gradient ) {
						innerClasses.push( 'has-background' );
					}
					if ( style.color.text ) {
						innerClasses.push( 'has-text-color' );
					}
				}
			}

			// Build inline styles
			const innerStyles = {};

			// Color styles only if NOT using a style variation
			if ( ! hasStyleVariation && style && style.color ) {
				if ( style.color.background ) {
					innerStyles.backgroundColor = style.color.background;
				}
				if ( style.color.text ) {
					innerStyles.color = style.color.text;
				}
				if ( style.color.gradient ) {
					innerStyles.background = style.color.gradient;
				}
			}

			// Typography styles (always apply)
			if ( style && style.typography ) {
				if ( style.typography.fontSize ) {
					innerStyles.fontSize = style.typography.fontSize;
				}
				if ( style.typography.lineHeight ) {
					innerStyles.lineHeight = style.typography.lineHeight;
				}
				if ( style.typography.fontWeight ) {
					innerStyles.fontWeight = style.typography.fontWeight;
				}
				if ( style.typography.fontFamily ) {
					innerStyles.fontFamily = style.typography.fontFamily;
				}
				if ( style.typography.letterSpacing ) {
					innerStyles.letterSpacing = style.typography.letterSpacing;
				}
				if ( style.typography.textTransform ) {
					innerStyles.textTransform = style.typography.textTransform;
				}
				if ( style.typography.textDecoration ) {
					innerStyles.textDecoration = style.typography.textDecoration;
				}
			}

			// Spacing styles (always apply)
			if ( style && style.spacing && style.spacing.padding ) {
				const padding = style.spacing.padding;
				if ( padding.top ) {
					innerStyles.paddingTop = padding.top;
				}
				if ( padding.right ) {
					innerStyles.paddingRight = padding.right;
				}
				if ( padding.bottom ) {
					innerStyles.paddingBottom = padding.bottom;
				}
				if ( padding.left ) {
					innerStyles.paddingLeft = padding.left;
				}
			}

			// Border styles (always apply)
			if ( style && style.border ) {
				if ( style.border.radius ) {
					innerStyles.borderRadius = style.border.radius;
				}
				if ( style.border.width ) {
					innerStyles.borderWidth = style.border.width;
				}
				if ( style.border.style ) {
					innerStyles.borderStyle = style.border.style;
				}
				if ( style.border.color ) {
					innerStyles.borderColor = style.border.color;
				}
			}

			// Shadow styles (always apply)
			if ( style && style.shadow ) {
				innerStyles.boxShadow = style.shadow;
			}

			const innerProps = {
				className: innerClasses.join( ' ' ),
				style: Object.keys( innerStyles ).length > 0 ? innerStyles : undefined
			};

			return el( Fragment, {},
				// Inspector Controls (Sidebar)
				el( InspectorControls, {},
					el( PanelBody, {
						// translators: Title for the block settings panel in sidebar.
						title: __( 'Settings', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( ToggleGroupControl, {
							__nextHasNoMarginBottom: true,
							// translators: Label for button width control (25%, 50%, 75%, 100%).
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
							// translators: Label for toggle to show/hide copy icon in button.
							label: __( 'Show Icon', 'tgp-llms-txt' ),
							checked: showIcon,
							onChange: function( value ) {
								setAttributes( { showIcon: value } );
							}
						} )
					)
				),

				// Block Preview - spread blockProps for proper selection and preview
				el( 'div', blockProps,
					el( 'button', innerProps,
						showIcon && el( 'span', { className: 'tgp-btn-icon' }, copyIcon ),
						el( RichText, {
							tagName: 'span',
							className: 'tgp-btn-text',
							value: label,
							onChange: function( value ) {
								setAttributes( { label: value } );
							},
							// translators: Default placeholder text for copy button label.
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
