( function( wp ) {
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const {
		PanelBody,
		ToggleControl,
		__experimentalToggleGroupControl: ToggleGroupControl,
		__experimentalToggleGroupControlOption: ToggleGroupControlOption
	} = wp.components;
	const { __ } = wp.i18n;
	const { createElement: el, Fragment } = wp.element;
	const { SVG, Path } = wp.primitives;

	// Block icon - tag/category.
	const blockIcon = el( SVG, {
		xmlns: 'http://www.w3.org/2000/svg',
		viewBox: '0 0 24 24'
	},
		el( Path, {
			d: 'M4 4h6l8.5 8.5-6 6L4 10V4zm4 3c-.6 0-1 .4-1 1s.4 1 1 1 1-.4 1-1-.4-1-1-1z',
			fill: 'currentColor'
		} )
	);

	// Example categories for editor preview.
	const exampleCategories = [
		{ name: 'Technology', count: 5 },
		{ name: 'Business', count: 3 },
		{ name: 'Design', count: 4 }
	];

	registerBlockType( 'tgp/blog-category-filter', {
		icon: blockIcon,

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const {
				showCount,
				layout,
				style,
				backgroundColor,
				textColor
			} = attributes;

			const blockProps = useBlockProps( {
				className: 'wp-block-tgp-blog-category-filter'
			} );

			// Detect style variation from blockProps.className (includes merged style class).
			const styleMatch = blockProps.className ? blockProps.className.match( /is-style-([a-z0-9-]+)/ ) : null;
			const styleVariation = styleMatch ? styleMatch[1] : 'button-brand';

			const listClass = 'wp-block-tgp-blog-category-filter__list' +
				( layout === 'scroll' ? ' wp-block-tgp-blog-category-filter__list--scroll' : '' );

			// Build wrapper classes.
			// Non-active pills use tint (secondary-button) styling.
			// Active pills use the selected style variation.
			const getWrapperClasses = function( isActive ) {
				const classes = [ 'wp-block-button' ];
				if ( isActive ) {
					classes.push( 'wp-block-tgp-blog-category-filter__pill--active' );
					classes.push( 'is-style-' + styleVariation );
				} else {
					classes.push( 'is-style-secondary-button' );
				}
				return classes.join( ' ' );
			};

			// Build inner button classes.
			const buttonClasses = 'wp-block-button__link wp-element-button wp-block-tgp-blog-category-filter__pill';

			// Build inline styles for buttons.
			// Typography, border-radius, and padding apply to BOTH states.
			// Colors, border width/style/color, and shadow apply to ACTIVE only.
			const getButtonStyles = function( isActive ) {
				const styles = {};

				// Typography styles (both states).
				if ( style && style.typography ) {
					if ( style.typography.fontSize ) {
						styles.fontSize = style.typography.fontSize;
					}
					if ( style.typography.lineHeight ) {
						styles.lineHeight = style.typography.lineHeight;
					}
					if ( style.typography.fontWeight ) {
						styles.fontWeight = style.typography.fontWeight;
					}
					if ( style.typography.fontFamily ) {
						styles.fontFamily = style.typography.fontFamily;
					}
					if ( style.typography.letterSpacing ) {
						styles.letterSpacing = style.typography.letterSpacing;
					}
					if ( style.typography.textTransform ) {
						styles.textTransform = style.typography.textTransform;
					}
					if ( style.typography.textDecoration ) {
						styles.textDecoration = style.typography.textDecoration;
					}
				}

				// Border radius (both states).
				if ( style && style.border && style.border.radius ) {
					if ( typeof style.border.radius === 'string' ) {
						styles.borderRadius = style.border.radius;
					} else {
						const r = style.border.radius;
						styles.borderRadius = ( r.topLeft || '0' ) + ' ' + ( r.topRight || '0' ) + ' ' + ( r.bottomRight || '0' ) + ' ' + ( r.bottomLeft || '0' );
					}
				}

				// Padding (both states).
				if ( style && style.spacing && style.spacing.padding ) {
					const padding = style.spacing.padding;
					if ( typeof padding === 'string' ) {
						styles.padding = padding;
					} else {
						if ( padding.top ) {
							styles.paddingTop = padding.top;
						}
						if ( padding.right ) {
							styles.paddingRight = padding.right;
						}
						if ( padding.bottom ) {
							styles.paddingBottom = padding.bottom;
						}
						if ( padding.left ) {
							styles.paddingLeft = padding.left;
						}
					}
				}

				// Active-only styles below.
				if ( isActive ) {
					// Border width/style/color (active only).
					if ( style && style.border ) {
						if ( style.border.width ) {
							if ( typeof style.border.width === 'string' ) {
								styles.borderWidth = style.border.width;
							} else {
								const w = style.border.width;
								styles.borderWidth = ( w.top || '0' ) + ' ' + ( w.right || '0' ) + ' ' + ( w.bottom || '0' ) + ' ' + ( w.left || '0' );
							}
							// Default to solid if width is set but style isn't.
							if ( ! style.border.style ) {
								styles.borderStyle = 'solid';
							}
						}
						if ( style.border.style ) {
							if ( typeof style.border.style === 'string' ) {
								styles.borderStyle = style.border.style;
							} else {
								const s = style.border.style;
								styles.borderStyle = ( s.top || 'none' ) + ' ' + ( s.right || 'none' ) + ' ' + ( s.bottom || 'none' ) + ' ' + ( s.left || 'none' );
							}
						}
						if ( style.border.color ) {
							if ( typeof style.border.color === 'string' ) {
								styles.borderColor = style.border.color;
							} else {
								const c = style.border.color;
								styles.borderColor = ( c.top || 'transparent' ) + ' ' + ( c.right || 'transparent' ) + ' ' + ( c.bottom || 'transparent' ) + ' ' + ( c.left || 'transparent' );
							}
						}
					}

					// Shadow (active only).
					if ( style && style.shadow ) {
						styles.boxShadow = style.shadow;
					}

					// Colors (active only).
					// Custom colors in style.color take precedence over preset slugs.
					const bgCustom = style && style.color && style.color.background;
					const textCustom = style && style.color && style.color.text;

					if ( bgCustom ) {
						styles.backgroundColor = bgCustom;
					} else if ( backgroundColor ) {
						styles.backgroundColor = 'var(--wp--preset--color--' + backgroundColor + ')';
					}

					if ( textCustom ) {
						styles.color = textCustom;
					} else if ( textColor ) {
						styles.color = 'var(--wp--preset--color--' + textColor + ')';
					}
				}

				return Object.keys( styles ).length > 0 ? styles : undefined;
			};

			return el( Fragment, {},
				// Inspector Controls (Sidebar).
				el( InspectorControls, {},
					el( PanelBody, {
						title: __( 'Settings', 'tgp-llms-txt' ),
						initialOpen: true
					},
						el( ToggleControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Show post count', 'tgp-llms-txt' ),
							help: __( 'Display the number of posts in each category.', 'tgp-llms-txt' ),
							checked: showCount,
							onChange: function( value ) {
								setAttributes( { showCount: value } );
							}
						} ),
						el( ToggleGroupControl, {
							__nextHasNoMarginBottom: true,
							label: __( 'Layout', 'tgp-llms-txt' ),
							value: layout,
							onChange: function( value ) {
								setAttributes( { layout: value } );
							},
							isBlock: true
						},
							el( ToggleGroupControlOption, {
								value: 'wrap',
								label: __( 'Wrap', 'tgp-llms-txt' )
							} ),
							el( ToggleGroupControlOption, {
								value: 'scroll',
								label: __( 'Scroll', 'tgp-llms-txt' )
							} )
						)
					)
				),

				// Block Preview.
				el( 'div', blockProps,
					el( 'div', { className: listClass },
						exampleCategories.map( function( cat, index ) {
							const isActive = index === 0;
							return el( 'div', {
								key: index,
								className: getWrapperClasses( isActive )
							},
								el( 'button', {
									type: 'button',
									className: buttonClasses,
									style: getButtonStyles( isActive ),
									disabled: true
								},
									el( 'span', { className: 'wp-block-tgp-blog-category-filter__pill-name' }, cat.name ),
									showCount && el( 'span', {
										className: 'wp-block-tgp-blog-category-filter__pill-count'
									}, cat.count )
								)
							);
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
