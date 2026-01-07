<?php
/**
 * Tests for MarkdownConverter class.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP\LLMsTxt\MarkdownConverter;

/**
 * Test class for MarkdownConverter.
 */
class MarkdownConverterTest extends TestCase {

	/**
	 * The converter instance.
	 *
	 * @var MarkdownConverter
	 */
	private MarkdownConverter $converter;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock wp_strip_all_tags to behave like the real function.
		Functions\when( 'wp_strip_all_tags' )->alias(
			function ( $text ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Mocking WP function in tests.
				return strip_tags( $text );
			}
		);

		$this->converter = new MarkdownConverter();
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test Gutenberg block comments are stripped.
	 */
	public function test_strips_gutenberg_block_comments(): void {
		$content = '<!-- wp:paragraph --><p>Hello world</p><!-- /wp:paragraph -->';

		$result = $this->converter->convert( $content );

		$this->assertStringNotContainsString( 'wp:paragraph', $result );
		$this->assertStringContainsString( 'Hello world', $result );
	}

	/**
	 * Test H1 heading conversion.
	 */
	public function test_converts_h1_headings(): void {
		$content = '<h1>Main Title</h1>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '# Main Title', $result );
	}

	/**
	 * Test H2 heading conversion.
	 */
	public function test_converts_h2_headings(): void {
		$content = '<h2>Section Title</h2>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '## Section Title', $result );
	}

	/**
	 * Test H3 heading conversion.
	 */
	public function test_converts_h3_headings(): void {
		$content = '<h3>Subsection Title</h3>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '### Subsection Title', $result );
	}

	/**
	 * Test H4 heading conversion.
	 */
	public function test_converts_h4_headings(): void {
		$content = '<h4>Minor Title</h4>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '#### Minor Title', $result );
	}

	/**
	 * Test heading with attributes.
	 */
	public function test_converts_heading_with_attributes(): void {
		$content = '<h2 class="wp-block-heading" id="custom-id">Title with Attributes</h2>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '## Title with Attributes', $result );
		$this->assertStringNotContainsString( 'class=', $result );
	}

	/**
	 * Test paragraph conversion.
	 */
	public function test_converts_paragraphs(): void {
		$content = '<p>This is a paragraph.</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( 'This is a paragraph.', $result );
	}

	/**
	 * Test paragraph with inline elements.
	 */
	public function test_converts_paragraph_with_inline_elements(): void {
		$content = '<p>Text with <strong>bold</strong> and <em>italic</em>.</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '**bold**', $result );
		$this->assertStringContainsString( '*italic*', $result );
	}

	/**
	 * Test unordered list conversion.
	 */
	public function test_converts_unordered_lists(): void {
		$content = '<ul><li>Item one</li><li>Item two</li><li>Item three</li></ul>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '- Item one', $result );
		$this->assertStringContainsString( '- Item two', $result );
		$this->assertStringContainsString( '- Item three', $result );
	}

	/**
	 * Test ordered list conversion.
	 */
	public function test_converts_ordered_lists(): void {
		$content = '<ol><li>First</li><li>Second</li><li>Third</li></ol>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '1. First', $result );
		$this->assertStringContainsString( '2. Second', $result );
		$this->assertStringContainsString( '3. Third', $result );
	}

	/**
	 * Test link conversion.
	 */
	public function test_converts_links(): void {
		$content = '<p>Visit <a href="https://example.com">Example Site</a> for more.</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '[Example Site](https://example.com)', $result );
	}

	/**
	 * Test link with attributes.
	 */
	public function test_converts_links_with_attributes(): void {
		$content = '<a href="https://example.com" target="_blank" rel="noopener">Link</a>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '[Link](https://example.com)', $result );
		$this->assertStringNotContainsString( 'target=', $result );
	}

	/**
	 * Test strong/bold conversion.
	 */
	public function test_converts_strong_to_bold(): void {
		$content = '<p><strong>Bold text</strong></p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '**Bold text**', $result );
	}

	/**
	 * Test b tag conversion.
	 */
	public function test_converts_b_tag_to_bold(): void {
		$content = '<p><b>Bold text</b></p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '**Bold text**', $result );
	}

	/**
	 * Test em/italic conversion.
	 */
	public function test_converts_em_to_italic(): void {
		$content = '<p><em>Italic text</em></p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '*Italic text*', $result );
	}

	/**
	 * Test i tag conversion.
	 */
	public function test_converts_i_tag_to_italic(): void {
		$content = '<p><i>Italic text</i></p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '*Italic text*', $result );
	}

	/**
	 * Test mark/highlight conversion.
	 */
	public function test_converts_mark_to_bold(): void {
		$content = '<p><mark>Highlighted text</mark></p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '**Highlighted text**', $result );
	}

	/**
	 * Test blockquote conversion.
	 */
	public function test_converts_blockquotes(): void {
		$content = '<blockquote>This is a quote.</blockquote>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '> This is a quote.', $result );
	}

	/**
	 * Test multiline blockquote conversion.
	 */
	public function test_converts_multiline_blockquotes(): void {
		$content = "<blockquote>Line one\nLine two</blockquote>";

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '> Line one', $result );
		$this->assertStringContainsString( '> Line two', $result );
	}

	/**
	 * Test inline code conversion.
	 */
	public function test_converts_inline_code(): void {
		$content = '<p>Use <code>console.log()</code> for debugging.</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '`console.log()`', $result );
	}

	/**
	 * Test code block conversion.
	 */
	public function test_converts_code_blocks(): void {
		$content = '<pre><code>function hello() {
    return "world";
}</code></pre>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '```', $result );
		$this->assertStringContainsString( 'function hello()', $result );
	}

	/**
	 * Test horizontal rule conversion.
	 */
	public function test_converts_horizontal_rules(): void {
		$content = '<p>Before</p><hr><p>After</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '---', $result );
	}

	/**
	 * Test self-closing hr conversion.
	 */
	public function test_converts_self_closing_hr(): void {
		$content = '<p>Before</p><hr /><p>After</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '---', $result );
	}

	/**
	 * Test table conversion.
	 */
	public function test_converts_tables(): void {
		$content = '<table>
			<thead><tr><th>Name</th><th>Value</th></tr></thead>
			<tbody>
				<tr><td>Foo</td><td>Bar</td></tr>
				<tr><td>Baz</td><td>Qux</td></tr>
			</tbody>
		</table>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '| Name | Value |', $result );
		$this->assertStringContainsString( '|---|---|', $result );
		$this->assertStringContainsString( '| Foo | Bar |', $result );
		$this->assertStringContainsString( '| Baz | Qux |', $result );
	}

	/**
	 * Test Gutenberg table block conversion.
	 */
	public function test_converts_gutenberg_table_blocks(): void {
		$content = '<figure class="wp-block-table">
			<table><thead><tr><th>Col1</th><th>Col2</th></tr></thead>
			<tbody><tr><td>A</td><td>B</td></tr></tbody></table>
		</figure>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '| Col1 | Col2 |', $result );
	}

	/**
	 * Test HTML entity decoding.
	 */
	public function test_decodes_html_entities(): void {
		$content = '<p>Testing &amp; escaping &lt;tags&gt;</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( 'Testing & escaping <tags>', $result );
	}

	/**
	 * Test multiple newlines are normalized.
	 */
	public function test_normalizes_multiple_newlines(): void {
		$content = '<p>First</p>




<p>Second</p>';

		$result = $this->converter->convert( $content );

		// Should not have more than 2 consecutive newlines.
		$this->assertDoesNotMatchRegularExpression( '/\n{3,}/', $result );
	}

	/**
	 * Test remaining HTML tags are removed.
	 */
	public function test_removes_remaining_html_tags(): void {
		$content = '<p>Text with <span class="custom">span</span> tag.</p>';

		$result = $this->converter->convert( $content );

		$this->assertStringNotContainsString( '<span', $result );
		$this->assertStringNotContainsString( '</span>', $result );
	}

	/**
	 * Test complete content conversion.
	 */
	public function test_converts_complete_content(): void {
		$content = '<!-- wp:heading -->
<h2>Introduction</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>This is a <strong>test</strong> with a <a href="https://example.com">link</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul><li>Item 1</li><li>Item 2</li></ul>
<!-- /wp:list -->';

		$result = $this->converter->convert( $content );

		$this->assertStringContainsString( '## Introduction', $result );
		$this->assertStringContainsString( '**test**', $result );
		$this->assertStringContainsString( '[link](https://example.com)', $result );
		$this->assertStringContainsString( '- Item 1', $result );
		$this->assertStringNotContainsString( 'wp:', $result );
	}

	/**
	 * Test empty content returns empty string.
	 */
	public function test_empty_content_returns_empty(): void {
		$result = $this->converter->convert( '' );

		$this->assertEquals( '', $result );
	}

	/**
	 * Test content with only whitespace.
	 */
	public function test_whitespace_only_content(): void {
		$result = $this->converter->convert( '   ' );

		$this->assertEquals( '', $result );
	}

	/**
	 * Test line break conversion.
	 */
	public function test_converts_line_breaks(): void {
		$content = '<p>Line one<br>Line two<br />Line three</p>';

		$result = $this->converter->convert( $content );

		$lines = explode( "\n", $result );
		$non_empty_lines = array_filter( array_map( 'trim', $lines ) );

		$this->assertContains( 'Line one', $non_empty_lines );
	}
}
