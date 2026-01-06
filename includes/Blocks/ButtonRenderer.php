<?php
/**
 * Button Block Renderer Helper
 *
 * Shared rendering logic for button blocks (copy-button, view-button).
 * Handles Block Supports: colors, typography, spacing, borders, shadows.
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Button Block Renderer class.
 *
 * Consolidates duplicate rendering logic used by copy-button and view-button blocks.
 */
class ButtonRenderer {

	/**
	 * Validate a CSS color value.
	 *
	 * Accepts hex colors, rgb/rgba, hsl/hsla, CSS color names, and CSS variables.
	 *
	 * @param mixed $color The color value to validate.
	 * @return bool True if the color value is valid, false otherwise.
	 */
	public static function is_valid_css_color( mixed $color ): bool {
		if ( ! is_string( $color ) || '' === $color ) {
			return false;
		}

		// Whitelist of patterns for safe CSS color values.
		$patterns = [
			// Hex colors: #rgb, #rrggbb, #rrggbbaa.
			'/^#[0-9a-fA-F]{3,8}$/',
			// RGB/RGBA: rgb(r, g, b) or rgba(r, g, b, a) with various formats.
			'/^rgba?\(\s*[\d.%\s,\/]+\s*\)$/',
			// HSL/HSLA: hsl(h, s%, l%) or hsla(h, s%, l%, a).
			'/^hsla?\(\s*[\d.%\s,\/deg]+\s*\)$/',
			// CSS color names (common ones, lowercase).
			'/^(transparent|currentcolor|inherit|initial|unset|' .
				'black|white|red|green|blue|yellow|orange|purple|pink|gray|grey|' .
				'navy|teal|aqua|maroon|olive|lime|fuchsia|silver)$/i',
			// CSS custom properties / variables.
			'/^var\(\s*--[a-zA-Z0-9_-]+(?:\s*,\s*[^)]+)?\s*\)$/',
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $color ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate a CSS gradient value.
	 *
	 * @param mixed $gradient The gradient value to validate.
	 * @return bool True if the gradient value is valid, false otherwise.
	 */
	public static function is_valid_css_gradient( mixed $gradient ): bool {
		if ( ! is_string( $gradient ) || '' === $gradient ) {
			return false;
		}

		// Allow linear-gradient, radial-gradient, conic-gradient and their repeating variants.
		// Also allow CSS variables.
		$patterns = [
			'/^(repeating-)?(linear|radial|conic)-gradient\([^;{}]+\)$/',
			'/^var\(\s*--[a-zA-Z0-9_-]+(?:\s*,\s*[^)]+)?\s*\)$/',
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $gradient ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Extract all style-related attributes from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array Extracted style attributes.
	 */
	public static function get_style_attributes( array $attributes ): array {
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
	public static function get_style_variation( string $wrapper_attrs_string ): array {
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
	public static function build_outer_classes( array $style_attrs, string $style_variation ): array {
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
	public static function build_inner_classes( array $style_attrs, string $base_class, bool $has_style_variation ): array {
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
	public static function build_inline_styles( array $style_attrs, bool $has_style_variation ): string {
		$styles = [];

		// Color styles only if NOT using a style variation.
		// Validate color values before use to prevent CSS injection.
		if ( ! $has_style_variation ) {
			if ( $style_attrs['custom_bg_color'] && self::is_valid_css_color( $style_attrs['custom_bg_color'] ) ) {
				$styles[] = 'background-color: ' . $style_attrs['custom_bg_color'];
			}
			if ( $style_attrs['custom_text_color'] && self::is_valid_css_color( $style_attrs['custom_text_color'] ) ) {
				$styles[] = 'color: ' . $style_attrs['custom_text_color'];
			}
			if ( $style_attrs['custom_gradient'] && self::is_valid_css_gradient( $style_attrs['custom_gradient'] ) ) {
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
	public static function get_style_attribute( array $style_attrs, bool $has_style_variation ): string {
		$inline_styles = self::build_inline_styles( $style_attrs, $has_style_variation );

		if ( empty( $inline_styles ) ) {
			return '';
		}

		return ' style="' . esc_attr( $inline_styles ) . '"';
	}
}
