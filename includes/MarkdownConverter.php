<?php
/**
 * Gutenberg to Markdown Converter
 *
 * Converts WordPress Gutenberg block content to clean markdown
 * suitable for LLM consumption.
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Markdown Converter class.
 */
class MarkdownConverter {

	/**
	 * Convert post content to markdown
	 *
	 * @param string $content The post content with Gutenberg blocks.
	 * @return string Clean markdown
	 */
	public function convert( string $content ): string {
		// Step 1: Remove Gutenberg block comments
		$content = $this->strip_block_comments( $content );

		// Step 2: Convert HTML elements to markdown
		$content = $this->convert_headings( $content );
		$content = $this->convert_paragraphs( $content );
		$content = $this->convert_lists( $content );
		$content = $this->convert_tables( $content );
		$content = $this->convert_links( $content );
		$content = $this->convert_emphasis( $content );
		$content = $this->convert_blockquotes( $content );
		$content = $this->convert_code( $content );
		$content = $this->convert_horizontal_rules( $content );

		// Step 3: Clean up
		$content = $this->cleanup( $content );

		return trim( $content );
	}

	/**
	 * Strip Gutenberg block comments
	 *
	 * @param string $content The content to strip.
	 * @return string The content without block comments.
	 */
	private function strip_block_comments( string $content ): string {
		// Remove <!-- wp:something --> and <!-- /wp:something -->
		$content = preg_replace( '/<!--\s*\/?wp:[^>]+-->/s', '', $content );
		return $content;
	}

	/**
	 * Convert headings
	 *
	 * @param string $content The content to convert.
	 * @return string The content with headings converted.
	 */
	private function convert_headings( string $content ): string {
		// H1
		$content = preg_replace_callback(
			'/<h1[^>]*>(.*?)<\/h1>/is',
			function ( $matches ) {
				return '# ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
			},
			$content
		);

		// H2
		$content = preg_replace_callback(
			'/<h2[^>]*>(.*?)<\/h2>/is',
			function ( $matches ) {
				return '## ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
			},
			$content
		);

		// H3
		$content = preg_replace_callback(
			'/<h3[^>]*>(.*?)<\/h3>/is',
			function ( $matches ) {
				return '### ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
			},
			$content
		);

		// H4
		$content = preg_replace_callback(
			'/<h4[^>]*>(.*?)<\/h4>/is',
			function ( $matches ) {
				return '#### ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
			},
			$content
		);

		return $content;
	}

	/**
	 * Convert paragraphs
	 *
	 * @param string $content The content to convert.
	 * @return string The content with paragraphs converted.
	 */
	private function convert_paragraphs( string $content ): string {
		// Convert <p> tags to plain text with double newlines
		$content = preg_replace_callback(
			'/<p[^>]*>(.*?)<\/p>/is',
			function ( $matches ) {
				$text = $this->convert_inline_elements( $matches[1] );
				return $text . "\n\n";
			},
			$content
		);

		return $content;
	}

	/**
	 * Convert lists (ul/ol)
	 *
	 * @param string $content The content to convert.
	 * @return string The content with lists converted.
	 */
	private function convert_lists( string $content ): string {
		// Unordered lists
		$content = preg_replace_callback(
			'/<ul[^>]*>(.*?)<\/ul>/is',
			function ( $matches ) {
				return $this->convert_list_items( $matches[1], '-' ) . "\n";
			},
			$content
		);

		// Ordered lists
		$content = preg_replace_callback(
			'/<ol[^>]*>(.*?)<\/ol>/is',
			function ( $matches ) {
				return $this->convert_list_items( $matches[1], '1.' ) . "\n";
			},
			$content
		);

		return $content;
	}

	/**
	 * Convert list items
	 *
	 * @param string $list_content The list HTML content.
	 * @param string $marker       The list marker (- or 1.).
	 * @return string The converted markdown list.
	 */
	private function convert_list_items( string $list_content, string $marker ): string {
		$result  = '';
		$counter = 1;

		preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $list_content, $matches );

		foreach ( $matches[1] as $item ) {
			$item_text = $this->convert_inline_elements( $item );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Need to preserve specific inline tags.
			$item_text = trim( strip_tags( $item_text, '<strong><em><a><code>' ) );
			$item_text = $this->convert_inline_elements( $item_text );

			if ( '1.' === $marker ) {
				$result .= $counter . '. ' . trim( $item_text ) . "\n";
				++$counter;
			} else {
				$result .= $marker . ' ' . trim( $item_text ) . "\n";
			}
		}

		return $result;
	}

	/**
	 * Convert tables
	 *
	 * @param string $content The content to convert.
	 * @return string The content with tables converted.
	 */
	private function convert_tables( string $content ): string {
		$content = preg_replace_callback(
			'/<figure[^>]*class="[^"]*wp-block-table[^"]*"[^>]*>(.*?)<\/figure>/is',
			function ( $matches ) {
				return $this->parse_table( $matches[1] );
			},
			$content
		);

		// Also handle standalone tables
		$content = preg_replace_callback(
			'/<table[^>]*>(.*?)<\/table>/is',
			function ( $matches ) {
				return $this->parse_table( '<table>' . $matches[1] . '</table>' );
			},
			$content
		);

		return $content;
	}

	/**
	 * Parse HTML table to markdown
	 *
	 * @param string $table_html The table HTML to parse.
	 * @return string The markdown table.
	 */
	private function parse_table( string $table_html ): string {
		$result = "\n";

		// Extract header row
		if ( preg_match( '/<thead[^>]*>(.*?)<\/thead>/is', $table_html, $thead ) ) {
			preg_match_all( '/<th[^>]*>(.*?)<\/th>/is', $thead[1], $headers );
			if ( ! empty( $headers[1] ) ) {
				$header_cells = array_map(
					function ( $cell ) {
						return trim( wp_strip_all_tags( $cell ) );
					},
					$headers[1]
				);

				$result .= '| ' . implode( ' | ', $header_cells ) . " |\n";
				$result .= '|' . str_repeat( '---|', count( $header_cells ) ) . "\n";
			}
		}

		// Extract body rows
		if ( preg_match( '/<tbody[^>]*>(.*?)<\/tbody>/is', $table_html, $tbody ) ) {
			preg_match_all( '/<tr[^>]*>(.*?)<\/tr>/is', $tbody[1], $rows );
			foreach ( $rows[1] as $row ) {
				preg_match_all( '/<td[^>]*>(.*?)<\/td>/is', $row, $cells );
				if ( ! empty( $cells[1] ) ) {
					$cell_values = array_map(
						function ( $cell ) {
							$text = $this->convert_inline_elements( $cell );
							return trim( wp_strip_all_tags( $text ) );
						},
						$cells[1]
					);
					$result     .= '| ' . implode( ' | ', $cell_values ) . " |\n";
				}
			}
		}

		return $result . "\n";
	}

	/**
	 * Convert links
	 *
	 * @param string $content The content to convert.
	 * @return string The content with links converted.
	 */
	private function convert_links( string $content ): string {
		$content = preg_replace_callback(
			'/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
			function ( $matches ) {
				$url  = $matches[1];
				$text = wp_strip_all_tags( $matches[2] );
				return '[' . $text . '](' . $url . ')';
			},
			$content
		);

		return $content;
	}

	/**
	 * Convert emphasis (bold, italic)
	 *
	 * @param string $content The content to convert.
	 * @return string The content with emphasis converted.
	 */
	private function convert_emphasis( string $content ): string {
		// Strong/bold
		$content = preg_replace( '/<strong[^>]*>(.*?)<\/strong>/is', '**$1**', $content );
		$content = preg_replace( '/<b[^>]*>(.*?)<\/b>/is', '**$1**', $content );

		// Emphasis/italic
		$content = preg_replace( '/<em[^>]*>(.*?)<\/em>/is', '*$1*', $content );
		$content = preg_replace( '/<i[^>]*>(.*?)<\/i>/is', '*$1*', $content );

		// Mark/highlight - convert to bold
		$content = preg_replace( '/<mark[^>]*>(.*?)<\/mark>/is', '**$1**', $content );

		return $content;
	}

	/**
	 * Convert blockquotes
	 *
	 * @param string $content The content to convert.
	 * @return string The content with blockquotes converted.
	 */
	private function convert_blockquotes( string $content ): string {
		$content = preg_replace_callback(
			'/<blockquote[^>]*>(.*?)<\/blockquote>/is',
			function ( $matches ) {
				$text   = wp_strip_all_tags( $matches[1] );
				$lines  = explode( "\n", trim( $text ) );
				$quoted = array_map(
					function ( $line ) {
						return '> ' . trim( $line );
					},
					$lines
				);
				return implode( "\n", $quoted ) . "\n\n";
			},
			$content
		);

		return $content;
	}

	/**
	 * Convert code blocks
	 *
	 * @param string $content The content to convert.
	 * @return string The content with code blocks converted.
	 */
	private function convert_code( string $content ): string {
		// Code blocks (pre > code)
		$content = preg_replace_callback(
			'/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
			function ( $matches ) {
				$code = html_entity_decode( $matches[1] );
				return "```\n" . $code . "\n```\n\n";
			},
			$content
		);

		// Inline code
		$content = preg_replace( '/<code[^>]*>(.*?)<\/code>/is', '`$1`', $content );

		return $content;
	}

	/**
	 * Convert horizontal rules
	 *
	 * @param string $content The content to convert.
	 * @return string The content with horizontal rules converted.
	 */
	private function convert_horizontal_rules( string $content ): string {
		$content = preg_replace( '/<hr[^>]*\/?>/is', "\n---\n\n", $content );
		return $content;
	}

	/**
	 * Convert inline elements within text
	 *
	 * @param string $text The text to convert.
	 * @return string The text with inline elements converted.
	 */
	private function convert_inline_elements( string $text ): string {
		// Links
		$text = preg_replace_callback(
			'/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
			function ( $matches ) {
				$url       = $matches[1];
				$link_text = wp_strip_all_tags( $matches[2] );
				return '[' . $link_text . '](' . $url . ')';
			},
			$text
		);

		// Bold
		$text = preg_replace( '/<strong[^>]*>(.*?)<\/strong>/is', '**$1**', $text );
		$text = preg_replace( '/<b[^>]*>(.*?)<\/b>/is', '**$1**', $text );

		// Italic
		$text = preg_replace( '/<em[^>]*>(.*?)<\/em>/is', '*$1*', $text );
		$text = preg_replace( '/<i[^>]*>(.*?)<\/i>/is', '*$1*', $text );

		// Mark
		$text = preg_replace( '/<mark[^>]*>(.*?)<\/mark>/is', '**$1**', $text );

		// Inline code
		$text = preg_replace( '/<code[^>]*>(.*?)<\/code>/is', '`$1`', $text );

		// Line breaks
		$text = preg_replace( '/<br\s*\/?>/is', "\n", $text );

		return $text;
	}

	/**
	 * Strip inline HTML tags but keep text
	 *
	 * @param string $text The text to strip.
	 * @return string The text without inline HTML tags.
	 */
	private function strip_inline_tags( string $text ): string {
		// First convert important elements.
		$text = $this->convert_inline_elements( $text );
		// Then strip any remaining HTML.
		$text = wp_strip_all_tags( $text );
		return trim( $text );
	}

	/**
	 * Final cleanup
	 *
	 * @param string $content The content to clean up.
	 * @return string The cleaned content.
	 */
	private function cleanup( string $content ): string {
		// Remove any remaining HTML tags
		$content = preg_replace( '/<[^>]+>/', '', $content );

		// Decode HTML entities
		$content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

		// Fix multiple newlines (max 2)
		$content = preg_replace( '/\n{3,}/', "\n\n", $content );

		// Fix spaces before newlines
		$content = preg_replace( '/[ \t]+\n/', "\n", $content );

		// Trim lines
		$lines   = explode( "\n", $content );
		$lines   = array_map( 'rtrim', $lines );
		$content = implode( "\n", $lines );

		return $content;
	}
}
