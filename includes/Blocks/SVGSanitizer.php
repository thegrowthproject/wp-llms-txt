<?php
/**
 * SVG Sanitizer Helper
 *
 * Provides allowed SVG tags for wp_kses sanitization across all blocks.
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SVG Sanitizer class.
 *
 * Consolidates SVG sanitization logic used by multiple blocks.
 */
class SVGSanitizer {

	/**
	 * Get comprehensive allowed SVG tags for wp_kses.
	 *
	 * Covers all SVG elements used across plugin blocks:
	 * - svg: Container element
	 * - path: Complex shapes
	 * - circle: Circular shapes
	 * - rect: Rectangular shapes
	 * - line: Line segments
	 * - polyline: Connected line segments
	 * - g: Group element
	 * - defs: Reusable definitions
	 * - use: Reference to defs
	 *
	 * @return array Allowed tags array for wp_kses.
	 */
	public static function get_allowed_tags(): array {
		return [
			'svg'      => [
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'class'           => true,
				'aria-hidden'     => true,
				'role'            => true,
				'focusable'       => true,
			],
			'path'     => [
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
			],
			'circle'   => [
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
			'rect'     => [
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'ry'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
			'line'     => [
				'x1'           => true,
				'y1'           => true,
				'x2'           => true,
				'y2'           => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
			'polyline' => [
				'points'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			],
			'g'        => [
				'fill'            => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'transform'       => true,
			],
			'defs'     => [],
			'use'      => [
				'href'       => true,
				'xlink:href' => true,
				'x'          => true,
				'y'          => true,
				'width'      => true,
				'height'     => true,
			],
		];
	}
}
