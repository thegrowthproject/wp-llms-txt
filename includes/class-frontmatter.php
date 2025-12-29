<?php
/**
 * YAML Frontmatter Generator
 *
 * Generates YAML frontmatter for markdown output
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TGP_Frontmatter {

    /**
     * The post object
     */
    private $post;

    /**
     * Constructor
     *
     * @param WP_Post $post The post object
     */
    public function __construct( $post ) {
        $this->post = $post;
    }

    /**
     * Generate YAML frontmatter
     *
     * @return string YAML frontmatter block
     */
    public function generate() {
        $data = $this->get_frontmatter_data();

        $yaml = "---\n";
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $yaml .= "{$key}:\n";
                foreach ( $value as $item ) {
                    $yaml .= "  - " . $this->escape_yaml_value( $item ) . "\n";
                }
            } else {
                $yaml .= "{$key}: " . $this->escape_yaml_value( $value ) . "\n";
            }
        }
        $yaml .= "---";

        return $yaml;
    }

    /**
     * Get frontmatter data array
     *
     * @return array Frontmatter key-value pairs
     */
    private function get_frontmatter_data() {
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
     * Get post description/excerpt
     */
    private function get_description() {
        $excerpt = get_the_excerpt( $this->post );
        if ( empty( $excerpt ) ) {
            // Generate from content
            $content = strip_tags( $this->post->post_content );
            $content = preg_replace( '/<!--.*?-->/s', '', $content );
            $excerpt = wp_trim_words( $content, 30, '...' );
        }
        return $excerpt;
    }

    /**
     * Get author name
     */
    private function get_author() {
        $author_id = $this->post->post_author;
        return get_the_author_meta( 'display_name', $author_id );
    }

    /**
     * Get primary category
     */
    private function get_primary_category() {
        $categories = get_the_category( $this->post->ID );
        if ( ! empty( $categories ) ) {
            // Try to get primary category (if Yoast or similar is installed)
            if ( class_exists( 'WPSEO_Primary_Term' ) ) {
                $primary_term = new WPSEO_Primary_Term( 'category', $this->post->ID );
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
     * Get post tags
     */
    private function get_tags() {
        $tags = get_the_tags( $this->post->ID );
        if ( $tags && ! is_wp_error( $tags ) ) {
            return array_map( function( $tag ) {
                return $tag->name;
            }, $tags );
        }
        return [];
    }

    /**
     * Estimate reading time in minutes
     */
    private function estimate_reading_time() {
        $content = strip_tags( $this->post->post_content );
        $word_count = str_word_count( $content );
        $minutes = ceil( $word_count / 200 ); // Assume 200 words per minute
        return $minutes . ' min read';
    }

    /**
     * Escape YAML value if needed
     */
    private function escape_yaml_value( $value ) {
        // If value contains special characters, wrap in quotes
        if ( preg_match( '/[:\[\]{}#&*!|>\'"%@`]/', $value ) ||
             preg_match( '/^[\s]|[\s]$/', $value ) ) {
            // Escape double quotes and wrap
            $value = '"' . str_replace( '"', '\\"', $value ) . '"';
        }
        return $value;
    }
}
