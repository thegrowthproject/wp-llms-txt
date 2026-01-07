# Hooks Reference

This document lists all filters and actions provided by the WP LLMs.txt plugin for customization.

## Filters

### `tgp_llms_txt_description`

Filter the custom description text in the llms.txt output.

**Since:** 1.2.0

**Parameters:**
- `$description` (string) Custom description text. Default empty.

**Example:**

```php
add_filter( 'tgp_llms_txt_description', function( $description ) {
    return 'AI-optimized content from our knowledge base. Contains technical documentation, tutorials, and API references.';
} );
```

---

### `tgp_llms_txt_contact_url`

Filter the contact page URL path in the llms.txt output.

**Since:** 1.2.0

**Parameters:**
- `$contact_path` (string) The contact page path. Default `/contact/`.

**Example:**

```php
add_filter( 'tgp_llms_txt_contact_url', function( $path ) {
    return '/support/contact-us/';
} );
```

---

### `tgp_llms_txt_pages`

Filter the key pages included in the llms.txt output.

**Since:** 1.2.0

**Parameters:**
- `$pages` (array) Array of page slug => description pairs.

**Default Pages:**
- `about` - About page
- `services` - Services overview
- `contact` - Contact information

**Example:**

```php
add_filter( 'tgp_llms_txt_pages', function( $pages ) {
    // Add custom pages
    $pages['documentation'] = 'API documentation and developer guides';
    $pages['pricing'] = 'Pricing plans and features';

    // Remove default pages
    unset( $pages['services'] );

    return $pages;
} );
```

---

### `tgp_llms_txt_posts_limit`

Filter the maximum number of posts to include in the llms.txt output.

**Since:** 1.3.4

**Parameters:**
- `$limit` (int) Maximum number of posts. Default 50. Use -1 for unlimited.

**Example:**

```php
// Limit to 20 most recent posts
add_filter( 'tgp_llms_txt_posts_limit', function( $limit ) {
    return 20;
} );

// Include all posts (not recommended for large sites)
add_filter( 'tgp_llms_txt_posts_limit', function( $limit ) {
    return -1;
} );
```

---

### `tgp_llms_txt_exclude_categories`

Filter the categories to exclude from the llms.txt output.

**Since:** 1.3.4

**Parameters:**
- `$exclude_categories` (array) Array of category slugs to exclude. Default empty.

**Example:**

```php
// Exclude internal and draft categories
add_filter( 'tgp_llms_txt_exclude_categories', function( $categories ) {
    return [ 'internal', 'drafts', 'uncategorized' ];
} );
```

---

### `tgp_llms_txt_rate_limit`

Filter the rate limit for LLMs.txt endpoints (requests per minute).

**Since:** 1.3.0

**Parameters:**
- `$limit` (int) The maximum requests per minute. Default 100.
- `$ip` (string) The client IP address.

**Example:**

```php
// Increase rate limit for specific IPs
add_filter( 'tgp_llms_txt_rate_limit', function( $limit, $ip ) {
    $whitelisted = [ '192.168.1.100', '10.0.0.1' ];

    if ( in_array( $ip, $whitelisted, true ) ) {
        return 1000; // Higher limit for trusted IPs
    }

    return $limit;
}, 10, 2 );
```

---

### `tgp_llms_txt_allowed_update_hosts`

Filter the allowed download hosts for plugin updates.

**Since:** 1.3.0

**Parameters:**
- `$allowed_hosts` (array) Array of allowed hostnames.
- `$download_host` (string) The host from the download URL.

**Example:**

```php
add_filter( 'tgp_llms_txt_allowed_update_hosts', function( $hosts, $download_host ) {
    $hosts[] = 'my-custom-update-server.com';
    return $hosts;
}, 10, 2 );
```

---

## Actions

### `tgp_llms_txt_error`

Fired when an error occurs in the plugin.

**Since:** 1.3.0

**Parameters:**
- `$message` (string) The error message.
- `$context` (array) Additional context data.

**Example:**

```php
// Log errors to external service
add_action( 'tgp_llms_txt_error', function( $message, $context ) {
    error_log( sprintf(
        '[LLMs.txt Error] %s | Context: %s',
        $message,
        wp_json_encode( $context )
    ) );
}, 10, 2 );

// Send critical errors to Slack
add_action( 'tgp_llms_txt_error', function( $message, $context ) {
    wp_remote_post( 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK', [
        'body' => wp_json_encode( [
            'text' => "LLMs.txt Error: {$message}",
            'attachments' => [
                [ 'text' => wp_json_encode( $context ) ]
            ]
        ] ),
        'headers' => [ 'Content-Type' => 'application/json' ],
    ] );
}, 10, 2 );
```

---

### `tgp_llms_txt_warning`

Fired when a warning occurs in the plugin (e.g., rate limit exceeded).

**Since:** 1.3.0

**Parameters:**
- `$message` (string) The warning message.
- `$context` (array) Additional context data.

**Example:**

```php
// Track rate limit violations
add_action( 'tgp_llms_txt_warning', function( $message, $context ) {
    if ( strpos( $message, 'Rate limit exceeded' ) !== false ) {
        // Log to analytics or monitoring service
        do_action( 'my_analytics_event', 'rate_limit_exceeded', $context );
    }
}, 10, 2 );
```

---

## Cache Integration

The rate limiter uses WordPress object cache (`wp_cache_*` functions) with the group `tgp_llms_txt`. When a persistent object cache plugin (Redis, Memcached) is installed, rate limiting data is automatically stored there.

**Cache Group:** `tgp_llms_txt`

To flush rate limit data programmatically:

```php
// Flush all rate limit data
wp_cache_flush_group( 'tgp_llms_txt' );
```

Note: `wp_cache_flush_group()` requires WordPress 6.1+ and a compatible object cache plugin.
