<?php
/**
 * Tests for TGP_Button_Block_Renderer.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP_Button_Block_Renderer;

/**
 * Test class for TGP_Button_Block_Renderer.
 */
class ButtonBlockRendererTest extends TestCase {

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
	 * Test get_style_attributes extracts color slugs.
	 */
	public function test_get_style_attributes_extracts_color_slugs(): void {
		$attributes = [
			'backgroundColor' => 'primary',
			'textColor'       => 'white',
			'gradient'        => 'vivid-cyan-blue-to-vivid-purple',
		];

		$result = TGP_Button_Block_Renderer::get_style_attributes( $attributes );

		$this->assertEquals( 'primary', $result['bg_color_slug'] );
		$this->assertEquals( 'white', $result['text_color_slug'] );
		$this->assertEquals( 'vivid-cyan-blue-to-vivid-purple', $result['gradient_slug'] );
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
					'gradient'   => 'linear-gradient(90deg, #f00, #00f)',
				],
			],
		];

		$result = TGP_Button_Block_Renderer::get_style_attributes( $attributes );

		$this->assertEquals( '#ff0000', $result['custom_bg_color'] );
		$this->assertEquals( '#ffffff', $result['custom_text_color'] );
		$this->assertEquals( 'linear-gradient(90deg, #f00, #00f)', $result['custom_gradient'] );
	}

	/**
	 * Test get_style_attributes extracts typography.
	 */
	public function test_get_style_attributes_extracts_typography(): void {
		$attributes = [
			'style' => [
				'typography' => [
					'fontSize'       => '16px',
					'lineHeight'     => '1.5',
					'fontWeight'     => '700',
					'fontFamily'     => 'Inter',
					'letterSpacing'  => '0.05em',
					'textTransform'  => 'uppercase',
					'textDecoration' => 'underline',
				],
			],
		];

		$result = TGP_Button_Block_Renderer::get_style_attributes( $attributes );

		$this->assertEquals( '16px', $result['font_size'] );
		$this->assertEquals( '1.5', $result['line_height'] );
		$this->assertEquals( '700', $result['font_weight'] );
		$this->assertEquals( 'Inter', $result['font_family'] );
		$this->assertEquals( '0.05em', $result['letter_spacing'] );
		$this->assertEquals( 'uppercase', $result['text_transform'] );
		$this->assertEquals( 'underline', $result['text_decoration'] );
	}

	/**
	 * Test get_style_attributes extracts spacing.
	 */
	public function test_get_style_attributes_extracts_spacing(): void {
		$attributes = [
			'style' => [
				'spacing' => [
					'padding' => [
						'top'    => '10px',
						'right'  => '20px',
						'bottom' => '10px',
						'left'   => '20px',
					],
				],
			],
		];

		$result = TGP_Button_Block_Renderer::get_style_attributes( $attributes );

		$this->assertIsArray( $result['padding'] );
		$this->assertEquals( '10px', $result['padding']['top'] );
		$this->assertEquals( '20px', $result['padding']['right'] );
	}

	/**
	 * Test get_style_attributes extracts border.
	 */
	public function test_get_style_attributes_extracts_border(): void {
		$attributes = [
			'style' => [
				'border' => [
					'radius' => '8px',
					'width'  => '2px',
					'style'  => 'solid',
					'color'  => '#000000',
				],
			],
		];

		$result = TGP_Button_Block_Renderer::get_style_attributes( $attributes );

		$this->assertIsArray( $result['border'] );
		$this->assertEquals( '8px', $result['border']['radius'] );
		$this->assertEquals( '2px', $result['border']['width'] );
	}

	/**
	 * Test get_style_variation detects fill style.
	 */
	public function test_get_style_variation_detects_fill(): void {
		$wrapper = 'class="wp-block-button is-style-fill"';

		$result = TGP_Button_Block_Renderer::get_style_variation( $wrapper );

		$this->assertEquals( 'fill', $result['variation'] );
		$this->assertFalse( $result['has_variation'] );
	}

	/**
	 * Test get_style_variation detects outline style.
	 */
	public function test_get_style_variation_detects_outline(): void {
		$wrapper = 'class="wp-block-button is-style-outline"';

		$result = TGP_Button_Block_Renderer::get_style_variation( $wrapper );

		$this->assertEquals( 'outline', $result['variation'] );
		$this->assertTrue( $result['has_variation'] );
	}

	/**
	 * Test get_style_variation defaults to fill.
	 */
	public function test_get_style_variation_defaults_to_fill(): void {
		$wrapper = 'class="wp-block-button"';

		$result = TGP_Button_Block_Renderer::get_style_variation( $wrapper );

		$this->assertEquals( 'fill', $result['variation'] );
		$this->assertFalse( $result['has_variation'] );
	}

	/**
	 * Test build_outer_classes includes style class.
	 */
	public function test_build_outer_classes_includes_style_class(): void {
		$style_attrs = [
			'width' => null,
		];

		$result = TGP_Button_Block_Renderer::build_outer_classes( $style_attrs, 'outline' );

		$this->assertContains( 'wp-block-button', $result );
		$this->assertContains( 'is-style-outline', $result );
	}

	/**
	 * Test build_outer_classes includes width classes.
	 */
	public function test_build_outer_classes_includes_width_classes(): void {
		$style_attrs = [
			'width' => 50,
		];

		$result = TGP_Button_Block_Renderer::build_outer_classes( $style_attrs, 'fill' );

		$this->assertContains( 'has-custom-width', $result );
		$this->assertContains( 'wp-block-button__width-50', $result );
	}

	/**
	 * Test build_inner_classes adds color classes without variation.
	 */
	public function test_build_inner_classes_adds_color_classes(): void {
		$style_attrs = [
			'bg_color_slug'     => 'primary',
			'text_color_slug'   => 'white',
			'gradient_slug'     => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::build_inner_classes(
			$style_attrs,
			'wp-block-tgp-test',
			false
		);

		$this->assertContains( 'has-background', $result );
		$this->assertContains( 'has-primary-background-color', $result );
		$this->assertContains( 'has-text-color', $result );
		$this->assertContains( 'has-white-color', $result );
	}

	/**
	 * Test build_inner_classes skips color classes with variation.
	 */
	public function test_build_inner_classes_skips_colors_with_variation(): void {
		$style_attrs = [
			'bg_color_slug'     => 'primary',
			'text_color_slug'   => 'white',
			'gradient_slug'     => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::build_inner_classes(
			$style_attrs,
			'wp-block-tgp-test',
			true
		);

		$this->assertNotContains( 'has-primary-background-color', $result );
		$this->assertNotContains( 'has-white-color', $result );
	}

	/**
	 * Test build_inline_styles generates typography styles.
	 */
	public function test_build_inline_styles_generates_typography(): void {
		$style_attrs = [
			'font_size'         => '16px',
			'line_height'       => '1.5',
			'font_weight'       => '700',
			'font_family'       => null,
			'letter_spacing'    => null,
			'text_transform'    => null,
			'text_decoration'   => null,
			'padding'           => null,
			'border'            => null,
			'shadow'            => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::build_inline_styles( $style_attrs, false );

		$this->assertStringContainsString( 'font-size: 16px', $result );
		$this->assertStringContainsString( 'line-height: 1.5', $result );
		$this->assertStringContainsString( 'font-weight: 700', $result );
	}

	/**
	 * Test build_inline_styles generates border radius.
	 */
	public function test_build_inline_styles_generates_border_radius(): void {
		$style_attrs = [
			'font_size'         => null,
			'line_height'       => null,
			'font_weight'       => null,
			'font_family'       => null,
			'letter_spacing'    => null,
			'text_transform'    => null,
			'text_decoration'   => null,
			'padding'           => null,
			'border'            => [
				'radius' => '8px',
			],
			'shadow'            => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::build_inline_styles( $style_attrs, false );

		$this->assertStringContainsString( 'border-radius: 8px', $result );
	}

	/**
	 * Test build_inline_styles handles individual corner radii.
	 */
	public function test_build_inline_styles_handles_corner_radii(): void {
		$style_attrs = [
			'font_size'         => null,
			'line_height'       => null,
			'font_weight'       => null,
			'font_family'       => null,
			'letter_spacing'    => null,
			'text_transform'    => null,
			'text_decoration'   => null,
			'padding'           => null,
			'border'            => [
				'radius' => [
					'topLeft'     => '4px',
					'topRight'    => '8px',
					'bottomRight' => '4px',
					'bottomLeft'  => '8px',
				],
			],
			'shadow'            => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::build_inline_styles( $style_attrs, false );

		$this->assertStringContainsString( 'border-radius: 4px 8px 4px 8px', $result );
	}

	/**
	 * Test get_style_attribute returns formatted attribute.
	 */
	public function test_get_style_attribute_returns_formatted(): void {
		$style_attrs = [
			'font_size'         => '16px',
			'line_height'       => null,
			'font_weight'       => null,
			'font_family'       => null,
			'letter_spacing'    => null,
			'text_transform'    => null,
			'text_decoration'   => null,
			'padding'           => null,
			'border'            => null,
			'shadow'            => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::get_style_attribute( $style_attrs, false );

		$this->assertStringStartsWith( ' style="', $result );
		$this->assertStringContainsString( 'font-size: 16px', $result );
	}

	/**
	 * Test get_style_attribute returns empty string when no styles.
	 */
	public function test_get_style_attribute_returns_empty_when_no_styles(): void {
		$style_attrs = [
			'font_size'         => null,
			'line_height'       => null,
			'font_weight'       => null,
			'font_family'       => null,
			'letter_spacing'    => null,
			'text_transform'    => null,
			'text_decoration'   => null,
			'padding'           => null,
			'border'            => null,
			'shadow'            => null,
			'custom_bg_color'   => null,
			'custom_text_color' => null,
			'custom_gradient'   => null,
		];

		$result = TGP_Button_Block_Renderer::get_style_attribute( $style_attrs, false );

		$this->assertEquals( '', $result );
	}
}
