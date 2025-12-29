<?php
/**
 * Endpoint Handler
 *
 * Registers and handles custom URL endpoints for markdown content
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TGP_Endpoint_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Check for our endpoints at init (priority 0 = earliest)
        add_action( 'init', [ $this, 'check_for_custom_endpoints' ], 0 );
        add_action( 'init', [ $this, 'add_rewrite_rules' ], 1 );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect', [ $this, 'handle_request' ], 1 );
    }

    /**
     * Check for custom endpoints early in init
     */
    public function check_for_custom_endpoints() {
        // Get the request path
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        $path = parse_url( $request_uri, PHP_URL_PATH );
        $path = rtrim( $path, '/' );

        // Check for llms.txt
        if ( preg_match( '/\/llms\.txt$/i', $path ) ) {
            $this->serve_llms_txt();
            exit;
        }

        // Check for .md format
        if ( preg_match( '/\.md$/i', $path ) ) {
            // Extract the slug from the path
            $slug = preg_replace( '/\.md$/i', '', basename( $path ) );
            if ( $slug ) {
                $this->md_slug = $slug;
                $this->serve_markdown();
                exit;
            }
        }
    }

    /**
     * Add rewrite rules for .md endpoints
     */
    public function add_rewrite_rules() {
        // Match /blog/post-slug.md (for blogs with /blog/ prefix)
        add_rewrite_rule(
            'blog/([^/]+)\.md$',
            'index.php?name=$matches[1]&tgp_format=md',
            'top'
        );

        // Match /post-slug.md (for posts without prefix)
        add_rewrite_rule(
            '([^/]+)\.md$',
            'index.php?name=$matches[1]&tgp_format=md',
            'top'
        );

        // Match /category/post-slug.md
        add_rewrite_rule(
            '([^/]+)/([^/]+)\.md$',
            'index.php?category_name=$matches[1]&name=$matches[2]&tgp_format=md',
            'top'
        );

        // Match /llms.txt
        add_rewrite_rule(
            'llms\.txt$',
            'index.php?tgp_llms_txt=1',
            'top'
        );

        // Match page slugs with .md
        add_rewrite_rule(
            '(.+?)\.md$',
            'index.php?pagename=$matches[1]&tgp_format=md',
            'top'
        );
    }

    /**
     * Add custom query vars
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'tgp_format';
        $vars[] = 'tgp_llms_txt';
        return $vars;
    }

    /**
     * Store md slug for serving
     */
    private $md_slug = null;

    /**
     * Handle markdown requests
     */
    public function handle_request() {
        global $wp;

        // Handle llms.txt
        if ( get_query_var( 'tgp_llms_txt' ) ) {
            $this->serve_llms_txt();
            exit;
        }

        // Handle .md format - check both query var and our custom var
        if ( get_query_var( 'tgp_format' ) === 'md' ||
             ( isset( $wp->query_vars['tgp_format'] ) && $wp->query_vars['tgp_format'] === 'md' ) ) {
            $this->serve_markdown();
            exit;
        }
    }

    /**
     * Serve markdown version of post/page
     */
    private function serve_markdown() {
        global $post;

        // Get slug from our stored value
        $post_name = $this->md_slug;

        if ( empty( $post_name ) ) {
            $this->send_404();
            return;
        }

        $found_post = null;

        // Try as post first
        $found_post = get_page_by_path( $post_name, OBJECT, 'post' );

        // Then try as page
        if ( ! $found_post ) {
            $found_post = get_page_by_path( $post_name, OBJECT, 'page' );
        }

        if ( ! empty( $found_post ) ) {
            $post = $found_post;
            setup_postdata( $post );
        } else {
            $this->send_404();
            return;
        }

        // Set headers
        header( 'Content-Type: text/markdown; charset=utf-8' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour

        // Generate markdown
        $converter = new TGP_Markdown_Converter();
        $frontmatter = new TGP_Frontmatter( $post );

        // Output
        echo $frontmatter->generate();
        echo "\n\n";
        echo "# " . get_the_title( $post ) . "\n\n";
        echo $converter->convert( $post->post_content );

        exit;
    }

    /**
     * Serve llms.txt file
     */
    private function serve_llms_txt() {
        $generator = new TGP_LLMs_Txt_Generator();

        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Cache-Control: public, max-age=3600' );

        echo $generator->generate();
        exit;
    }

    /**
     * Send 404 response
     */
    private function send_404() {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();

        header( 'Content-Type: text/plain; charset=utf-8' );
        echo "# 404 Not Found\n\nThe requested markdown file could not be found.";
        exit;
    }
}
