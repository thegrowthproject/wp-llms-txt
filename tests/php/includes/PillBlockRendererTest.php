<?php
/**
 * Tests for TGP_Pill_Block_Renderer.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP_Pill_Block_Renderer;

/**
 * Test class for TGP_Pill_Block_Renderer.
 */
class PillBlockRendererTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock esc_attr.
		Functions\when( 'esc_attr' )->returnArg();
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test get_style_attributes extracts color presets.
	 */
	public function test_get_style_attributes_extracts_color_presets(): void {
		$attributes = [
			'backgroundColor' => 'primary',
			'textColor'       => 'white',
		];

		$result = TGP_Pill_Block_Renderer::get_style_attributes( $attributes );

		$this->assertEquals( 'primary', $result['bg_color_preset'] );
		$this->assertEquals( 'white', $result['text_color_preset'] );
	}

	/**
	 * Test get_style_attributes extracts custom colors.
	 */
	public function test_get_style_attributes_extracts_custom_colors(): void {
		$attributes = [
			'style' => [
				'color' => [
					'background' => '#ff0000',
					'text'       => '#ffffff',
				],
			],
		];

		$result = TGP_Pill_Block_Renderer::get_style_attributes( $attributes );

		$this->assertEquals( '#ff0000', $result['bg_color_custom'] );
		$this->assertEquals( '#ffffff', $result['text_color_custom'] );
	}

	/**
	 * Test get_style_attributes extracts typography.
	 */
	public function test_get_style_attributes_extracts_typography(): void {
		$attributes = [
			'style' => [
				'typography' => [
					'fontSize'   => '14px',
					'fontWeight' => '600',
				],
			],
		];

		$result = TGP_Pill_Block_Renderer::get_style_attributes( $attributes );

		$this->assertEquals( '14px', $result['font_size'] );
		$this->assertEquals( '600', $result['font_weight'] );
	}

	/**
	 * Test resolve_colors with custom values.
	 */
	public function test_resolve_colors_prefers_custom(): void {
		$style_attrs = [
			'bg_color_custom'   => '#ff0000',
			'bg_color_preset'   => 'primary',
			'text_color_custom' => '#ffffff',
			'text_color_preset' => 'white',
		];

		$result = TGP_Pill_Block_Renderer::resolve_colors( $style_attrs );

		$this->assertEquals( '#ff0000', $result['background'] );
		$this->assertEquals( '#ffffff', $result['text'] );
	}

	/**
	 * Test resolve_colors converts presets to CSS vars.
	 */
	public function test_resolve_colors_converts_presets(): void {
		$style_attrs = [
			'bg_color_custom'   => null,
			'bg_color_preset'   => 'primary',
			'text_color_custom' => null,
			'text_color_preset' => 'white',
		];

		$result = TGP_Pill_Block_Renderer::resolve_colors( $style_attrs );

		$this->assertEquals( 'var(--wp--preset--color--primary)', $result['background'] );
		$this->assertEquals( 'var(--wp--preset--color--white)', $result['text'] );
	}

	/**
	 * Test resolve_colors returns null when no colors.
	 */
	public function test_resolve_colors_returns_null_when_empty(): void {
		$style_attrs = [
			'bg_color_custom'   => null,
			'bg_color_preset'   => null,
			'text_color_custom' => null,
			'text_color_preset' => null,
		];

		$result = TGP_Pill_Block_Renderer::resolve_colors( $style_attrs );

		$this->assertNull( $result['background'] );
		$this->assertNull( $result['text'] );
	}

	/**
	 * Test get_style_variation detects button-brand.
	 */
	public function test_get_style_variation_detects_button_brand(): void {
		$wrapper = 'class="wp-block-tgp-test is-style-button-brand"';

		$result = TGP_Pill_Block_Renderer::get_style_variation( $wrapper );

		$this->assertEquals( 'button-brand', $result );
	}

	/**
	 * Test get_style_variation defaults to button-brand.
	 */
	public function test_get_style_variation_defaults_to_button_brand(): void {
		$wrapper = 'class="wp-block-tgp-test"';

		$result = TGP_Pill_Block_Renderer::get_style_variation( $wrapper );

		$this->assertEquals( 'button-brand', $result );
	}

	/**
	 * Test build_pill_wrapper_classes returns correct classes.
	 */
	public function test_build_pill_wrapper_classes(): void {
		$result = TGP_Pill_Block_Renderer::build_pill_wrapper_classes();

		$this->assertContains( 'wp-block-button', $result );
		$this->assertContains( 'is-style-secondary-button', $result );
	}

	/**
	 * Test build_button_classes includes base class.
	 */
	public function test_build_button_classes(): void {
		$result = TGP_Pill_Block_Renderer::build_button_classes( 'my-custom-class' );

		$this->assertContains( 'wp-block-button__link', $result );
		$this->assertContains( 'wp-element-button', $result );
		$this->assertContains( 'my-custom-class', $result );
	}

	/**
	 * Test build_button_styles generates typography.
	 */
	public function test_build_button_styles_generates_typography(): void {
		$style_attrs = [
			'font_size'       => '14px',
			'line_height'     => '1.4',
			'font_weight'     => '600',
			'font_family'     => null,
			'letter_spacing'  => null,
			'text_transform'  => 'uppercase',
			'text_decoration' => null,
			'padding'         => null,
			'border'          => null,
		];

		$result = TGP_Pill_Block_Renderer::build_button_styles( $style_attrs );

		$this->assertStringContainsString( 'font-size: 14px', $result );
		$this->assertStringContainsString( 'line-height: 1.4', $result );
		$this->assertStringContainsString( 'font-weight: 600', $result );
		$this->assertStringContainsString( 'text-transform: uppercase', $result );
	}

	/**
	 * Test build_button_styles generates border radius.
	 */
	public function test_build_button_styles_generates_border_radius(): void {
		$style_attrs = [
			'font_size'       => null,
			'line_height'     => null,
			'font_weight'     => null,
			'font_family'     => null,
			'letter_spacing'  => null,
			'text_transform'  => null,
			'text_decoration' => null,
			'padding'         => null,
			'border'          => [
				'radius' => '20px',
			],
		];

		$result = TGP_Pill_Block_Renderer::build_button_styles( $style_attrs );

		$this->assertStringContainsString( 'border-radius: 20px', $result );
	}

	/**
	 * Test build_button_styles generates padding.
	 */
	public function test_build_button_styles_generates_padding(): void {
		$style_attrs = [
			'font_size'       => null,
			'line_height'     => null,
			'font_weight'     => null,
			'font_family'     => null,
			'letter_spacing'  => null,
			'text_transform'  => null,
			'text_decoration' => null,
			'padding'         => [
				'top'    => '8px',
				'right'  => '16px',
				'bottom' => '8px',
				'left'   => '16px',
			],
			'border'          => null,
		];

		$result = TGP_Pill_Block_Renderer::build_button_styles( $style_attrs );

		$this->assertStringContainsString( 'padding-top: 8px', $result );
		$this->assertStringContainsString( 'padding-right: 16px', $result );
	}

	/**
	 * Test build_active_state_styles adds marker classes.
	 */
	public function test_build_active_state_styles_adds_marker_classes(): void {
		$style_attrs = [
			'border' => [
				'width' => '2px',
				'color' => '#000',
			],
			'shadow' => '0 2px 4px rgba(0,0,0,0.1)',
		];
		$colors = [
			'background' => '#ff0000',
			'text'       => '#ffffff',
		];

		$result = TGP_Pill_Block_Renderer::build_active_state_styles( $style_attrs, $colors );

		$this->assertContains( 'has-custom-active-bg', $result['classes'] );
		$this->assertContains( 'has-custom-active-text', $result['classes'] );
		$this->assertContains( 'has-custom-active-border', $result['classes'] );
		$this->assertContains( 'has-custom-active-shadow', $result['classes'] );
	}

	/**
	 * Test build_active_state_styles generates CSS vars.
	 */
	public function test_build_active_state_styles_generates_css_vars(): void {
		$style_attrs = [
			'border' => null,
			'shadow' => null,
		];
		$colors = [
			'background' => '#ff0000',
			'text'       => '#ffffff',
		];

		$result = TGP_Pill_Block_Renderer::build_active_state_styles( $style_attrs, $colors );

		$this->assertContains( '--tgp-active-bg: #ff0000', $result['styles'] );
		$this->assertContains( '--tgp-active-text: #ffffff', $result['styles'] );
	}

	/**
	 * Test build_active_state_styles adds default border style.
	 */
	public function test_build_active_state_styles_adds_default_border_style(): void {
		$style_attrs = [
			'border' => [
				'width' => '2px',
			],
			'shadow' => null,
		];
		$colors = [
			'background' => null,
			'text'       => null,
		];

		$result = TGP_Pill_Block_Renderer::build_active_state_styles( $style_attrs, $colors );

		$this->assertContains( '--tgp-active-border-width: 2px', $result['styles'] );
		$this->assertContains( '--tgp-active-border-style: solid', $result['styles'] );
	}

	/**
	 * Test get_style_classes returns correct classes.
	 */
	public function test_get_style_classes(): void {
		$result = TGP_Pill_Block_Renderer::get_style_classes( 'button-dark' );

		$this->assertEquals( 'is-style-button-dark', $result['active'] );
		$this->assertEquals( 'is-style-secondary-button', $result['inactive'] );
	}

	/**
	 * Test inject_marker_classes adds classes to wrapper.
	 */
	public function test_inject_marker_classes(): void {
		$wrapper = 'class="wp-block-test"';
		$classes = [ 'has-custom-active-bg', 'has-custom-active-text' ];

		$result = TGP_Pill_Block_Renderer::inject_marker_classes( $wrapper, $classes );

		$this->assertStringContainsString( 'has-custom-active-bg', $result );
		$this->assertStringContainsString( 'has-custom-active-text', $result );
	}

	/**
	 * Test inject_marker_classes returns unchanged when no classes.
	 */
	public function test_inject_marker_classes_unchanged_when_empty(): void {
		$wrapper = 'class="wp-block-test"';
		$classes = [];

		$result = TGP_Pill_Block_Renderer::inject_marker_classes( $wrapper, $classes );

		$this->assertEquals( $wrapper, $result );
	}

	/**
	 * Test get_button_style_attribute returns formatted.
	 */
	public function test_get_button_style_attribute(): void {
		$style_attrs = [
			'font_size'       => '14px',
			'line_height'     => null,
			'font_weight'     => null,
			'font_family'     => null,
			'letter_spacing'  => null,
			'text_transform'  => null,
			'text_decoration' => null,
			'padding'         => null,
			'border'          => null,
		];

		$result = TGP_Pill_Block_Renderer::get_button_style_attribute( $style_attrs );

		$this->assertStringStartsWith( ' style="', $result );
		$this->assertStringContainsString( 'font-size: 14px', $result );
	}

	/**
	 * Test get_wrapper_style_attribute returns formatted.
	 */
	public function test_get_wrapper_style_attribute(): void {
		$active_styles = [
			'styles'  => [ '--tgp-active-bg: #ff0000', '--tgp-active-text: #fff' ],
			'classes' => [ 'has-custom-active-bg' ],
		];

		$result = TGP_Pill_Block_Renderer::get_wrapper_style_attribute( $active_styles );

		$this->assertStringStartsWith( ' style="', $result );
		$this->assertStringContainsString( '--tgp-active-bg: #ff0000', $result );
	}
}
