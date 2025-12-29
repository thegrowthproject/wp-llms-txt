<?php
/**
 * Gutenberg to Markdown Converter
 *
 * Converts WordPress Gutenberg block content to clean markdown
 * suitable for LLM consumption.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TGP_Markdown_Converter {

    /**
     * Convert post content to markdown
     *
     * @param string $content The post content with Gutenberg blocks
     * @return string Clean markdown
     */
    public function convert( $content ) {
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
     */
    private function strip_block_comments( $content ) {
        // Remove <!-- wp:something --> and <!-- /wp:something -->
        $content = preg_replace( '/<!--\s*\/?wp:[^>]+-->/s', '', $content );
        return $content;
    }

    /**
     * Convert headings
     */
    private function convert_headings( $content ) {
        // H1
        $content = preg_replace_callback(
            '/<h1[^>]*>(.*?)<\/h1>/is',
            function( $matches ) {
                return '# ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
            },
            $content
        );

        // H2
        $content = preg_replace_callback(
            '/<h2[^>]*>(.*?)<\/h2>/is',
            function( $matches ) {
                return '## ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
            },
            $content
        );

        // H3
        $content = preg_replace_callback(
            '/<h3[^>]*>(.*?)<\/h3>/is',
            function( $matches ) {
                return '### ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
            },
            $content
        );

        // H4
        $content = preg_replace_callback(
            '/<h4[^>]*>(.*?)<\/h4>/is',
            function( $matches ) {
                return '#### ' . $this->strip_inline_tags( $matches[1] ) . "\n\n";
            },
            $content
        );

        return $content;
    }

    /**
     * Convert paragraphs
     */
    private function convert_paragraphs( $content ) {
        // Convert <p> tags to plain text with double newlines
        $content = preg_replace_callback(
            '/<p[^>]*>(.*?)<\/p>/is',
            function( $matches ) {
                $text = $this->convert_inline_elements( $matches[1] );
                return $text . "\n\n";
            },
            $content
        );

        return $content;
    }

    /**
     * Convert lists (ul/ol)
     */
    private function convert_lists( $content ) {
        // Unordered lists
        $content = preg_replace_callback(
            '/<ul[^>]*>(.*?)<\/ul>/is',
            function( $matches ) {
                return $this->convert_list_items( $matches[1], '-' ) . "\n";
            },
            $content
        );

        // Ordered lists
        $content = preg_replace_callback(
            '/<ol[^>]*>(.*?)<\/ol>/is',
            function( $matches ) {
                return $this->convert_list_items( $matches[1], '1.' ) . "\n";
            },
            $content
        );

        return $content;
    }

    /**
     * Convert list items
     */
    private function convert_list_items( $list_content, $marker ) {
        $result = '';
        $counter = 1;

        preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $list_content, $matches );

        foreach ( $matches[1] as $item ) {
            $item_text = $this->convert_inline_elements( $item );
            $item_text = trim( strip_tags( $item_text, '<strong><em><a><code>' ) );
            $item_text = $this->convert_inline_elements( $item_text );

            if ( $marker === '1.' ) {
                $result .= $counter . '. ' . trim( $item_text ) . "\n";
                $counter++;
            } else {
                $result .= $marker . ' ' . trim( $item_text ) . "\n";
            }
        }

        return $result;
    }

    /**
     * Convert tables
     */
    private function convert_tables( $content ) {
        $content = preg_replace_callback(
            '/<figure[^>]*class="[^"]*wp-block-table[^"]*"[^>]*>(.*?)<\/figure>/is',
            function( $matches ) {
                return $this->parse_table( $matches[1] );
            },
            $content
        );

        // Also handle standalone tables
        $content = preg_replace_callback(
            '/<table[^>]*>(.*?)<\/table>/is',
            function( $matches ) {
                return $this->parse_table( '<table>' . $matches[1] . '</table>' );
            },
            $content
        );

        return $content;
    }

    /**
     * Parse HTML table to markdown
     */
    private function parse_table( $table_html ) {
        $result = "\n";

        // Extract header row
        if ( preg_match( '/<thead[^>]*>(.*?)<\/thead>/is', $table_html, $thead ) ) {
            preg_match_all( '/<th[^>]*>(.*?)<\/th>/is', $thead[1], $headers );
            if ( ! empty( $headers[1] ) ) {
                $header_cells = array_map( function( $cell ) {
                    return trim( strip_tags( $cell ) );
                }, $headers[1] );

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
                    $cell_values = array_map( function( $cell ) {
                        $text = $this->convert_inline_elements( $cell );
                        return trim( strip_tags( $text ) );
                    }, $cells[1] );
                    $result .= '| ' . implode( ' | ', $cell_values ) . " |\n";
                }
            }
        }

        return $result . "\n";
    }

    /**
     * Convert links
     */
    private function convert_links( $content ) {
        $content = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
            function( $matches ) {
                $url = $matches[1];
                $text = strip_tags( $matches[2] );
                return '[' . $text . '](' . $url . ')';
            },
            $content
        );

        return $content;
    }

    /**
     * Convert emphasis (bold, italic)
     */
    private function convert_emphasis( $content ) {
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
     */
    private function convert_blockquotes( $content ) {
        $content = preg_replace_callback(
            '/<blockquote[^>]*>(.*?)<\/blockquote>/is',
            function( $matches ) {
                $text = strip_tags( $matches[1] );
                $lines = explode( "\n", trim( $text ) );
                $quoted = array_map( function( $line ) {
                    return '> ' . trim( $line );
                }, $lines );
                return implode( "\n", $quoted ) . "\n\n";
            },
            $content
        );

        return $content;
    }

    /**
     * Convert code blocks
     */
    private function convert_code( $content ) {
        // Code blocks (pre > code)
        $content = preg_replace_callback(
            '/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is',
            function( $matches ) {
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
     */
    private function convert_horizontal_rules( $content ) {
        $content = preg_replace( '/<hr[^>]*\/?>/is', "\n---\n\n", $content );
        return $content;
    }

    /**
     * Convert inline elements within text
     */
    private function convert_inline_elements( $text ) {
        // Links
        $text = preg_replace_callback(
            '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is',
            function( $matches ) {
                $url = $matches[1];
                $link_text = strip_tags( $matches[2] );
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
     */
    private function strip_inline_tags( $text ) {
        // First convert important elements
        $text = $this->convert_inline_elements( $text );
        // Then strip any remaining HTML
        $text = strip_tags( $text );
        return trim( $text );
    }

    /**
     * Final cleanup
     */
    private function cleanup( $content ) {
        // Remove any remaining HTML tags
        $content = preg_replace( '/<[^>]+>/', '', $content );

        // Decode HTML entities
        $content = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );

        // Fix multiple newlines (max 2)
        $content = preg_replace( '/\n{3,}/', "\n\n", $content );

        // Fix spaces before newlines
        $content = preg_replace( '/[ \t]+\n/', "\n", $content );

        // Trim lines
        $lines = explode( "\n", $content );
        $lines = array_map( 'rtrim', $lines );
        $content = implode( "\n", $lines );

        return $content;
    }
}
