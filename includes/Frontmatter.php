<?php
/**
 * YAML Frontmatter Generator
 *
 * Generates YAML frontmatter for markdown output
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontmatter class.
 */
class Frontmatter {

	/**
	 * The post object.
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $post;

	/**
	 * Constructor
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function __construct( \WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Generate YAML frontmatter
	 *
	 * @return string YAML frontmatter block
	 */
	public function generate(): string {
		$data = $this->get_frontmatter_data();

		$yaml = "---\n";
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$yaml .= "{$key}:\n";
				foreach ( $value as $item ) {
					$yaml .= '  - ' . $this->escape_yaml_value( $item ) . "\n";
				}
			} else {
				$yaml .= "{$key}: " . $this->escape_yaml_value( $value ) . "\n";
			}
		}
		$yaml .= '---';

		return $yaml;
	}

	/**
	 * Get frontmatter data array
	 *
	 * @return array Frontmatter key-value pairs
	 */
	private function get_frontmatter_data(): array {
		$data = [
			'title'       => get_the_title( $this->post ),
			'description' => $this->get_description(),
			'date'        => get_the_date( 'Y-m-d', $this->post ),
			'modified'    => get_the_modified_date( 'Y-m-d', $this->post ),
			'author'      => $this->get_author(),
			'url'         => get_permalink( $this->post ),
		];

		// Add category
		$category = $this->get_primary_category();
		if ( $category ) {
			$data['category'] = $category;
		}

		// Add tags
		$tags = $this->get_tags();
		if ( ! empty( $tags ) ) {
			$data['tags'] = $tags;
		}

		// Add reading time estimate
		$data['reading_time'] = $this->estimate_reading_time();

		return $data;
	}

	/**
	 * Get post description/excerpt.
	 *
	 * @return string The post excerpt or generated description.
	 */
	private function get_description(): string {
		$excerpt = get_the_excerpt( $this->post );
		if ( empty( $excerpt ) ) {
			// Generate from content.
			$content = wp_strip_all_tags( $this->post->post_content );
			$content = preg_replace( '/<!--.*?-->/s', '', $content );
			$excerpt = wp_trim_words( $content, 30, '...' );
		}
		return $excerpt;
	}

	/**
	 * Get author name.
	 *
	 * @return string The author display name.
	 */
	private function get_author(): string {
		$author_id = $this->post->post_author;
		return get_the_author_meta( 'display_name', $author_id );
	}

	/**
	 * Get primary category.
	 *
	 * @return string|null The primary category name or null.
	 */
	private function get_primary_category(): ?string {
		$categories = get_the_category( $this->post->ID );
		if ( ! empty( $categories ) ) {
			// Try to get primary category (if Yoast or similar is installed)
			if ( class_exists( 'WPSEO_Primary_Term' ) ) {
				$primary_term = new \WPSEO_Primary_Term( 'category', $this->post->ID );
				$primary_term_id = $primary_term->get_primary_term();
				if ( $primary_term_id ) {
					$term = get_term( $primary_term_id );
					if ( $term && ! is_wp_error( $term ) ) {
						return $term->name;
					}
				}
			}
			// Fallback to first category
			return $categories[0]->name;
		}
		return null;
	}

	/**
	 * Get post tags.
	 *
	 * @return array<string> Array of tag names.
	 */
	private function get_tags(): array {
		$tags = get_the_tags( $this->post->ID );
		if ( $tags && ! is_wp_error( $tags ) ) {
			return array_map(
				function ( $tag ) {
					return $tag->name;
				},
				$tags
			);
		}
		return [];
	}

	/**
	 * Estimate reading time in minutes.
	 *
	 * @return string The reading time estimate (e.g., "5 min read").
	 */
	private function estimate_reading_time(): string {
		$content = wp_strip_all_tags( $this->post->post_content );
		$word_count = str_word_count( $content );
		$minutes = ceil( $word_count / 200 ); // Assume 200 words per minute
		return $minutes . ' min read';
	}

	/**
	 * Escape YAML value if needed.
	 *
	 * Follows YAML 1.2 spec for double-quoted scalar escaping.
	 * Backslashes must be escaped first, then other special characters.
	 *
	 * @param string $value The value to escape.
	 * @return string The escaped value.
	 */
	private function escape_yaml_value( string $value ): string {
		// Check if value needs quoting: special chars, leading/trailing whitespace,
		// control characters, or could be interpreted as YAML type (true, false, null, numbers).
		$needs_quoting = preg_match( '/[:\[\]{}#&*!|>\'"%@`\n\r\t\\\\]/', $value ) ||
			preg_match( '/^[\s]|[\s]$/', $value ) ||
			preg_match( '/^(true|false|yes|no|null|~|\d+\.?\d*|0x[0-9a-f]+|0o[0-7]+)$/i', $value ) ||
			'' === $value;

		if ( $needs_quoting ) {
			// YAML spec: escape backslashes first, then other characters.
			$value = str_replace( '\\', '\\\\', $value );
			$value = str_replace( '"', '\\"', $value );
			$value = str_replace( "\n", '\\n', $value );
			$value = str_replace( "\r", '\\r', $value );
			$value = str_replace( "\t", '\\t', $value );
			$value = '"' . $value . '"';
		}

		return $value;
	}
}
