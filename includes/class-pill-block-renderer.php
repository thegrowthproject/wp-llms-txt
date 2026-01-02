<?php
/**
 * Pill Block Renderer Helper
 *
 * Shared rendering logic for pill/toggle blocks (blog-category-filter).
 * Handles two-state styling: active and inactive pills with style switching.
 *
 * Key difference from TGP_Button_Block_Renderer:
 * - Supports active/inactive states with different styles
 * - Uses marker classes for conditional CSS custom properties
 * - Typography and border-radius apply to BOTH states
 * - Colors, border, shadow apply only to ACTIVE state
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pill Block Renderer class.
 *
 * Consolidates rendering logic for toggleable pill-style blocks.
 */
class TGP_Pill_Block_Renderer {

	/**
	 * Default inactive style class.
	 *
	 * @var string
	 */
	const INACTIVE_STYLE = 'secondary-button';

	/**
	 * Default active style variation.
	 *
	 * @var string
	 */
	const DEFAULT_ACTIVE_STYLE = 'button-brand';

	/**
	 * Extract style attributes from block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array Extracted style attributes.
	 */
	public static function get_style_attributes( $attributes ) {
		return [
			// Color presets.
			'bg_color_preset'   => $attributes['backgroundColor'] ?? null,
			'text_color_preset' => $attributes['textColor'] ?? null,

			// Custom color values.
			'bg_color_custom'   => $attributes['style']['color']['background'] ?? null,
			'text_color_custom' => $attributes['style']['color']['text'] ?? null,

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
		];
	}

	/**
	 * Resolve color values from presets or custom values.
	 *
	 * @param array $style_attrs Style attributes from get_style_attributes().
	 * @return array Resolved colors with 'background' and 'text' keys.
	 */
	public static function resolve_colors( $style_attrs ) {
		$background = $style_attrs['bg_color_custom'];
		if ( ! $background && $style_attrs['bg_color_preset'] ) {
			$background = 'var(--wp--preset--color--' . $style_attrs['bg_color_preset'] . ')';
		}

		$text = $style_attrs['text_color_custom'];
		if ( ! $text && $style_attrs['text_color_preset'] ) {
			$text = 'var(--wp--preset--color--' . $style_attrs['text_color_preset'] . ')';
		}

		return [
			'background' => $background,
			'text'       => $text,
		];
	}

	/**
	 * Detect style variation from wrapper attributes string.
	 *
	 * @param string $wrapper_attrs_string Wrapper attributes from get_block_wrapper_attributes().
	 * @return string The style variation name.
	 */
	public static function get_style_variation( $wrapper_attrs_string ) {
		if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $match ) ) {
			return $match[1];
		}
		return self::DEFAULT_ACTIVE_STYLE;
	}

	/**
	 * Build pill wrapper classes for individual pills.
	 *
	 * Returns the base classes; active/inactive style classes are handled
	 * by Interactivity API data-wp-class directives.
	 *
	 * @return array Array of CSS class names.
	 */
	public static function build_pill_wrapper_classes() {
		return [ 'wp-block-button', 'is-style-' . self::INACTIVE_STYLE ];
	}

	/**
	 * Build inner button classes.
	 *
	 * @param string $base_class The block-specific base class.
	 * @return array Array of CSS class names.
	 */
	public static function build_button_classes( $base_class ) {
		return [ 'wp-block-button__link', 'wp-element-button', $base_class ];
	}

	/**
	 * Build inline styles for buttons (apply to BOTH states).
	 *
	 * Typography, border-radius, and padding apply to all pills.
	 *
	 * @param array $style_attrs Style attributes from get_style_attributes().
	 * @return string The style attribute value, or empty string.
	 */
	public static function build_button_styles( $style_attrs ) {
		$styles = [];

		// Typography.
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

		// Border radius.
		$border = $style_attrs['border'];
		if ( $border && isset( $border['radius'] ) ) {
			if ( is_array( $border['radius'] ) ) {
				$tl = $border['radius']['topLeft'] ?? '0';
				$tr = $border['radius']['topRight'] ?? '0';
				$br = $border['radius']['bottomRight'] ?? '0';
				$bl = $border['radius']['bottomLeft'] ?? '0';
				$styles[] = 'border-radius: ' . $tl . ' ' . $tr . ' ' . $br . ' ' . $bl;
			} else {
				$styles[] = 'border-radius: ' . $border['radius'];
			}
		}

		// Padding.
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

		return ! empty( $styles ) ? implode( '; ', $styles ) : '';
	}

	/**
	 * Build active-state CSS custom properties and marker classes.
	 *
	 * Colors, border (except radius), and shadow only apply to active pills.
	 * Returns both the CSS custom properties and marker classes.
	 *
	 * @param array $style_attrs Style attributes from get_style_attributes().
	 * @param array $colors      Resolved colors from resolve_colors().
	 * @return array Array with 'styles' (CSS properties) and 'classes' (marker classes).
	 */
	public static function build_active_state_styles( $style_attrs, $colors ) {
		$styles  = [];
		$classes = [];

		// Background color.
		if ( $colors['background'] ) {
			$classes[] = 'has-custom-active-bg';
			$styles[]  = '--tgp-active-bg: ' . $colors['background'];
		}

		// Text color.
		if ( $colors['text'] ) {
			$classes[] = 'has-custom-active-text';
			$styles[]  = '--tgp-active-text: ' . $colors['text'];
		}

		// Border (width, style, color - not radius).
		$border = $style_attrs['border'];
		if ( $border ) {
			if ( isset( $border['width'] ) ) {
				$classes[] = 'has-custom-active-border';
				$styles[]  = '--tgp-active-border-width: ' . $border['width'];
				// Default to solid if width set but style isn't.
				if ( ! isset( $border['style'] ) ) {
					$styles[] = '--tgp-active-border-style: solid';
				}
			}
			if ( isset( $border['style'] ) ) {
				$styles[] = '--tgp-active-border-style: ' . $border['style'];
			}
			if ( isset( $border['color'] ) ) {
				$styles[] = '--tgp-active-border-color: ' . $border['color'];
			}
		}

		// Shadow.
		if ( $style_attrs['shadow'] ) {
			$classes[] = 'has-custom-active-shadow';
			$styles[]  = '--tgp-active-box-shadow: ' . $style_attrs['shadow'];
		}

		return [
			'styles'  => $styles,
			'classes' => $classes,
		];
	}

	/**
	 * Get button style attribute string.
	 *
	 * @param array $style_attrs Style attributes from get_style_attributes().
	 * @return string The style attribute (e.g., ' style="..."'), or empty string.
	 */
	public static function get_button_style_attribute( $style_attrs ) {
		$styles = self::build_button_styles( $style_attrs );
		if ( empty( $styles ) ) {
			return '';
		}
		return ' style="' . esc_attr( $styles ) . '"';
	}

	/**
	 * Get wrapper style attribute string for active-state custom properties.
	 *
	 * @param array $active_styles Active state styles from build_active_state_styles().
	 * @return string The style attribute (e.g., ' style="..."'), or empty string.
	 */
	public static function get_wrapper_style_attribute( $active_styles ) {
		if ( empty( $active_styles['styles'] ) ) {
			return '';
		}
		return ' style="' . esc_attr( implode( '; ', $active_styles['styles'] ) ) . '"';
	}

	/**
	 * Inject marker classes into wrapper attributes string.
	 *
	 * @param string $wrapper_attrs_string Original wrapper attributes.
	 * @param array  $marker_classes       Marker classes to add.
	 * @return string Modified wrapper attributes string.
	 */
	public static function inject_marker_classes( $wrapper_attrs_string, $marker_classes ) {
		if ( empty( $marker_classes ) ) {
			return $wrapper_attrs_string;
		}

		$class_string = ' ' . implode( ' ', $marker_classes );
		return preg_replace(
			'/class="([^"]*)"/',
			'class="$1' . esc_attr( $class_string ) . '"',
			$wrapper_attrs_string
		);
	}

	/**
	 * Get style class names for Interactivity API directives.
	 *
	 * @param string $active_style The active style variation.
	 * @return array Array with 'active' and 'inactive' class names.
	 */
	public static function get_style_classes( $active_style ) {
		return [
			'active'   => 'is-style-' . $active_style,
			'inactive' => 'is-style-' . self::INACTIVE_STYLE,
		];
	}
}
