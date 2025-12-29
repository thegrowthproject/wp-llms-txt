<?php
/**
 * LLMs.txt Generator
 *
 * Generates the /llms.txt index file listing all available content
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TGP_LLMs_Txt_Generator {

    /**
     * Constructor
     */
    public function __construct() {
        // Regenerate on post save
        add_action( 'save_post', [ $this, 'maybe_regenerate' ], 10, 2 );
    }

    /**
     * Generate llms.txt content
     *
     * @return string The llms.txt content
     */
    public function generate() {
        $site_name = get_bloginfo( 'name' );
        $site_description = get_bloginfo( 'description' );
        $site_url = home_url();

        $output = "# {$site_name} - llms.txt\n";
        $output .= "# {$site_url}/llms.txt\n";
        $output .= "#\n";
        $output .= "# This file helps AI systems understand and access our content.\n";
        $output .= "# Add .md to any URL to get the markdown version.\n";
        $output .= "#\n";
        $output .= "# Standard: https://llmstxt.org/\n\n";

        $output .= "> {$site_description}\n\n";

        // Site description
        $output .= "The Growth Project provides senior technology delivery support for mid-market businesses. ";
        $output .= "We cover AI implementation, systems integration, DevOps, and operator perspectives.\n\n";

        // Key pages
        $output .= "## Pages\n\n";
        $output .= $this->get_pages_section();

        // Blog posts by category
        $output .= "## Blog Posts\n\n";
        $output .= $this->get_posts_section();

        // Contact info
        $output .= "## Contact\n\n";
        $output .= "- Website: {$site_url}\n";
        $output .= "- Contact: {$site_url}/contact/\n";

        return $output;
    }

    /**
     * Get pages section
     */
    private function get_pages_section() {
        $output = '';

        // Define key pages to include
        $key_pages = [
            'about'    => 'About us and how we work',
            'services' => 'What we deliver',
            'contact'  => 'Get in touch',
        ];

        foreach ( $key_pages as $slug => $description ) {
            $page = get_page_by_path( $slug );
            if ( $page ) {
                $url = get_permalink( $page );
                $md_url = $this->get_md_url( $url );
                $title = get_the_title( $page );
                $output .= "- [{$title}]({$md_url}): {$description}\n";
            }
        }

        $output .= "\n";
        return $output;
    }

    /**
     * Get blog posts section
     */
    private function get_posts_section() {
        $output = '';

        // Get all categories with posts
        $categories = get_categories( [
            'hide_empty' => true,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );

        if ( empty( $categories ) ) {
            // No categories, just list all posts
            $output .= $this->get_posts_list();
        } else {
            // Group by category
            foreach ( $categories as $category ) {
                $output .= "### {$category->name}\n\n";

                $posts = get_posts( [
                    'category'       => $category->term_id,
                    'posts_per_page' => -1,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                    'post_status'    => 'publish',
                ] );

                foreach ( $posts as $post ) {
                    $url = get_permalink( $post );
                    $md_url = $this->get_md_url( $url );
                    $title = get_the_title( $post );
                    $excerpt = $this->get_short_excerpt( $post );
                    $output .= "- [{$title}]({$md_url}): {$excerpt}\n";
                }

                $output .= "\n";
            }
        }

        return $output;
    }

    /**
     * Get all posts list (no categories)
     */
    private function get_posts_list() {
        $output = '';

        $posts = get_posts( [
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish',
        ] );

        foreach ( $posts as $post ) {
            $url = get_permalink( $post );
            $md_url = $this->get_md_url( $url );
            $title = get_the_title( $post );
            $excerpt = $this->get_short_excerpt( $post );
            $output .= "- [{$title}]({$md_url}): {$excerpt}\n";
        }

        return $output . "\n";
    }

    /**
     * Get short excerpt for llms.txt
     */
    private function get_short_excerpt( $post ) {
        $excerpt = get_the_excerpt( $post );
        if ( empty( $excerpt ) ) {
            $content = strip_tags( $post->post_content );
            $content = preg_replace( '/<!--.*?-->/s', '', $content );
            $excerpt = wp_trim_words( $content, 15, '...' );
        } else {
            $excerpt = wp_trim_words( $excerpt, 15, '...' );
        }
        return $excerpt;
    }

    /**
     * Convert URL to .md URL
     */
    private function get_md_url( $url ) {
        // Remove trailing slash and add .md
        $url = rtrim( $url, '/' );
        return $url . '.md';
    }

    /**
     * Maybe regenerate on post save
     */
    public function maybe_regenerate( $post_id, $post ) {
        // Skip autosaves and revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Only for published posts and pages
        if ( $post->post_status !== 'publish' ) {
            return;
        }

        if ( ! in_array( $post->post_type, [ 'post', 'page' ], true ) ) {
            return;
        }

        // Could cache the generated content here if needed
        // For now, llms.txt is generated on-the-fly
    }
}
