<?php
/**
 * Button Block Renderer Helper
 *
 * Shared rendering logic for button blocks (copy-button, view-button).
 * Handles Block Supports: colors, typography, spacing, borders, shadows.
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Button Block Renderer class.
 *
 * Consolidates duplicate rendering logic used by copy-button and view-button blocks.
 */
class TGP_Button_Block_Renderer {

	/**
	 * Extract all style-related attributes from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array Extracted style attributes.
	 */
	public static function get_style_attributes( $attributes ) {
		return [
			// Color slugs (preset colors).
			'bg_color_slug'   => $attributes['backgroundColor'] ?? null,
			'text_color_slug' => $attributes['textColor'] ?? null,
			'gradient_slug'   => $attributes['gradient'] ?? null,

			// Custom color values.
			'custom_bg_color'   => $attributes['style']['color']['background'] ?? null,
			'custom_text_color' => $attributes['style']['color']['text'] ?? null,
			'custom_gradient'   => $attributes['style']['color']['gradient'] ?? null,

			// Typography.
			'font_size'       => $attributes['style']['typography']['fontSize'] ?? null,
			'line_height'     => $attributes['style']['typography']['lineHeight'] ?? null,
			'font_weight'     => $attributes['style']['typography']['fontWeight'] ?? null,
			'font_family'     => $attributes['style']['typography']['fontFamily'] ?? null,
			'letter_spacing'  => $attributes['style']['typography']['letterSpacing'] ?? null,
			'text_transform'  => $attributes['style']['typography']['textTransform'] ?? null,
			'text_decoration' => $attributes['style']['typography']['textDecoration'] ?? null,

			// Spacing.
			'padding' => $attributes['style']['spacing']['padding'] ?? null,

			// Border.
			'border' => $attributes['style']['border'] ?? null,

			// Shadow.
			'shadow' => $attributes['style']['shadow'] ?? null,

			// Width (button percentage width).
			'width' => $attributes['width'] ?? null,
		];
	}

	/**
	 * Detect style variation from wrapper attributes string.
	 *
	 * @param string $wrapper_attrs_string The wrapper attributes string from get_block_wrapper_attributes().
	 * @return array Array with 'variation' (string) and 'has_variation' (bool) keys.
	 */
	public static function get_style_variation( $wrapper_attrs_string ) {
		$style_variation     = 'fill'; // Default.
		$has_style_variation = false;

		if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $style_match ) ) {
			$style_variation     = $style_match[1];
			$has_style_variation = ( 'fill' !== $style_variation );
		}

		return [
			'variation'     => $style_variation,
			'has_variation' => $has_style_variation,
		];
	}

	/**
	 * Build outer wrapper classes for the button.
	 *
	 * @param array  $style_attrs Style attributes from get_style_attributes().
	 * @param string $style_variation The style variation name.
	 * @return array Array of CSS class names.
	 */
	public static function build_outer_classes( $style_attrs, $style_variation ) {
		$classes = [ 'wp-block-button', 'is-style-' . $style_variation ];

		// Add width classes.
		if ( $style_attrs['width'] ) {
			$classes[] = 'has-custom-width';
			$classes[] = 'wp-block-button__width-' . $style_attrs['width'];
		}

		return $classes;
	}

	/**
	 * Build inner button/link classes.
	 *
	 * @param array  $style_attrs Style attributes from get_style_attributes().
	 * @param string $base_class The block-specific base class (e.g., 'wp-block-tgp-copy-button').
	 * @param bool   $has_style_variation Whether a non-default style variation is active.
	 * @return array Array of CSS class names.
	 */
	public static function build_inner_classes( $style_attrs, $base_class, $has_style_variation ) {
		$classes = [ 'wp-block-button__link', 'wp-element-button', $base_class ];

		// Only add color classes if NOT using a style variation (or using default fill).
		if ( ! $has_style_variation ) {
			if ( $style_attrs['bg_color_slug'] ) {
				$classes[] = 'has-background';
				$classes[] = 'has-' . $style_attrs['bg_color_slug'] . '-background-color';
			}
			if ( $style_attrs['text_color_slug'] ) {
				$classes[] = 'has-text-color';
				$classes[] = 'has-' . $style_attrs['text_color_slug'] . '-color';
			}
			if ( $style_attrs['gradient_slug'] ) {
				$classes[] = 'has-background';
				$classes[] = 'has-' . $style_attrs['gradient_slug'] . '-gradient-background';
			}
			// Add has-background/has-text-color for custom values.
			if ( $style_attrs['custom_bg_color'] || $style_attrs['custom_gradient'] ) {
				$classes[] = 'has-background';
			}
			if ( $style_attrs['custom_text_color'] ) {
				$classes[] = 'has-text-color';
			}
		}

		return $classes;
	}

	/**
	 * Build inline styles string for the button.
	 *
	 * @param array $style_attrs Style attributes from get_style_attributes().
	 * @param bool  $has_style_variation Whether a non-default style variation is active.
	 * @return string The style attribute value (without 'style=' wrapper), or empty string.
	 */
	public static function build_inline_styles( $style_attrs, $has_style_variation ) {
		$styles = [];

		// Color styles only if NOT using a style variation.
		if ( ! $has_style_variation ) {
			if ( $style_attrs['custom_bg_color'] ) {
				$styles[] = 'background-color: ' . $style_attrs['custom_bg_color'];
			}
			if ( $style_attrs['custom_text_color'] ) {
				$styles[] = 'color: ' . $style_attrs['custom_text_color'];
			}
			if ( $style_attrs['custom_gradient'] ) {
				$styles[] = 'background: ' . $style_attrs['custom_gradient'];
			}
		}

		// Typography styles (always apply).
		if ( $style_attrs['font_size'] ) {
			$styles[] = 'font-size: ' . $style_attrs['font_size'];
		}
		if ( $style_attrs['line_height'] ) {
			$styles[] = 'line-height: ' . $style_attrs['line_height'];
		}
		if ( $style_attrs['font_weight'] ) {
			$styles[] = 'font-weight: ' . $style_attrs['font_weight'];
		}
		if ( $style_attrs['font_family'] ) {
			$styles[] = 'font-family: ' . $style_attrs['font_family'];
		}
		if ( $style_attrs['letter_spacing'] ) {
			$styles[] = 'letter-spacing: ' . $style_attrs['letter_spacing'];
		}
		if ( $style_attrs['text_transform'] ) {
			$styles[] = 'text-transform: ' . $style_attrs['text_transform'];
		}
		if ( $style_attrs['text_decoration'] ) {
			$styles[] = 'text-decoration: ' . $style_attrs['text_decoration'];
		}

		// Spacing styles (always apply).
		$padding = $style_attrs['padding'];
		if ( $padding && is_array( $padding ) ) {
			if ( isset( $padding['top'] ) ) {
				$styles[] = 'padding-top: ' . $padding['top'];
			}
			if ( isset( $padding['right'] ) ) {
				$styles[] = 'padding-right: ' . $padding['right'];
			}
			if ( isset( $padding['bottom'] ) ) {
				$styles[] = 'padding-bottom: ' . $padding['bottom'];
			}
			if ( isset( $padding['left'] ) ) {
				$styles[] = 'padding-left: ' . $padding['left'];
			}
		}

		// Border styles (always apply).
		$border = $style_attrs['border'];
		if ( $border ) {
			if ( isset( $border['radius'] ) ) {
				if ( is_array( $border['radius'] ) ) {
					// Individual corner radii.
					$top_left     = $border['radius']['topLeft'] ?? '0';
					$top_right    = $border['radius']['topRight'] ?? '0';
					$bottom_right = $border['radius']['bottomRight'] ?? '0';
					$bottom_left  = $border['radius']['bottomLeft'] ?? '0';
					$styles[]     = 'border-radius: ' . $top_left . ' ' . $top_right . ' ' . $bottom_right . ' ' . $bottom_left;
				} else {
					$styles[] = 'border-radius: ' . $border['radius'];
				}
			}
			if ( isset( $border['width'] ) ) {
				$styles[] = 'border-width: ' . $border['width'];
			}
			if ( isset( $border['style'] ) ) {
				$styles[] = 'border-style: ' . $border['style'];
			}
			if ( isset( $border['color'] ) ) {
				$styles[] = 'border-color: ' . $border['color'];
			}
		}

		// Shadow styles (always apply).
		if ( $style_attrs['shadow'] ) {
			$styles[] = 'box-shadow: ' . $style_attrs['shadow'];
		}

		return ! empty( $styles ) ? implode( '; ', $styles ) : '';
	}

	/**
	 * Build the complete style attribute string.
	 *
	 * @param array $style_attrs Style attributes from get_style_attributes().
	 * @param bool  $has_style_variation Whether a non-default style variation is active.
	 * @return string The complete style attribute (e.g., ' style="..."'), or empty string.
	 */
	public static function get_style_attribute( $style_attrs, $has_style_variation ) {
		$inline_styles = self::build_inline_styles( $style_attrs, $has_style_variation );

		if ( empty( $inline_styles ) ) {
			return '';
		}

		return ' style="' . esc_attr( $inline_styles ) . '"';
	}
}
