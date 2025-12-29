<?php
/**
 * UI Buttons
 *
 * Handles AJAX for the LLM Buttons block copy functionality.
 * The buttons are now provided via the tgp/llm-buttons Gutenberg block.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TGP_UI_Buttons {

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers for copy functionality (used by the block)
        add_action( 'wp_ajax_tgp_get_markdown', [ $this, 'ajax_get_markdown' ] );
        add_action( 'wp_ajax_nopriv_tgp_get_markdown', [ $this, 'ajax_get_markdown' ] );
    }

    /**
     * AJAX handler to get markdown content
     */
    public function ajax_get_markdown() {
        check_ajax_referer( 'tgp_llm_nonce', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Invalid post ID' );
        }

        $post = get_post( $post_id );

        if ( ! $post || $post->post_status !== 'publish' ) {
            wp_send_json_error( 'Post not found' );
        }

        $converter = new TGP_Markdown_Converter();
        $frontmatter = new TGP_Frontmatter( $post );

        $markdown = $frontmatter->generate();
        $markdown .= "\n\n";
        $markdown .= "# " . get_the_title( $post ) . "\n\n";
        $markdown .= $converter->convert( $post->post_content );

        wp_send_json_success( [ 'markdown' => $markdown ] );
    }
}
