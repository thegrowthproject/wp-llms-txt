# WP LLMs Plugin

[![CI](https://github.com/thegrowthproject/wp-llms-txt/actions/workflows/lint.yml/badge.svg)](https://github.com/thegrowthproject/wp-llms-txt/actions/workflows/lint.yml)
![WordPress 6.5+](https://img.shields.io/badge/WordPress-6.5%2B-blue?logo=wordpress)
![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-purple?logo=php)
[![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

A WordPress plugin that makes your site readable by AI systems.

Built by [The Growth Project](https://thegrowthproject.com.au).

## The Problem

AI tools and LLMs struggle with WordPress content:

- **Gutenberg blocks are messy** — Block comments, nested divs, inline styles. None of it helps an AI understand your content.
- **No standard index** — AI crawlers don't know what pages matter or how your content is structured.
- **HTML isn't ideal** — LLMs work better with clean markdown than raw HTML.

The [llmstxt.org](https://llmstxt.org/) standard solves this. It defines a `/llms.txt` endpoint that tells AI systems what content exists and how to access it.

## The Solution

This plugin implements the llms.txt standard for WordPress:

1. **`/llms.txt`** — An index of your site's pages and posts, with links to markdown versions
2. **`/llms-full.txt`** — Your entire site content in a single markdown file
3. **`.md` endpoints** — Every page and post available as clean markdown (e.g., `/about.md`)

AI tools can now crawl your site efficiently. Your content is accessible in a format they understand.

## Usage

### Copy for LLM Button

Add a button that copies the current page content as markdown to the clipboard.

1. Open the block editor for any page or post
2. Click the **+** inserter and search for "Copy for LLM"
3. Insert the block where you want the button to appear
4. The button inherits your theme's button styles automatically

<!-- Screenshot: copy-button-inserter.png -->
*Block inserter showing "Copy for LLM" block*

When clicked, the button:
- Shows "Copying..." during fetch
- Fetches content from the `.md` endpoint
- Copies markdown to clipboard
- Shows "Copied!" confirmation

<!-- Screenshot: copy-button-states.png -->
*Button states: default, copying, copied*

### View as Markdown Button

Add a link that opens the markdown version of the current page.

1. Open the block editor for any page or post
2. Click the **+** inserter and search for "View as Markdown"
3. Insert the block where you want the link to appear

The link automatically points to the current page's `.md` URL (e.g., `/about.md`).

### LLM Buttons Pattern

For convenience, both buttons are available as a pattern:

1. Click the **+** inserter
2. Switch to the **Patterns** tab
3. Search for "LLM Buttons"
4. Insert the pattern to get both buttons in a row

<!-- Screenshot: llm-buttons-pattern.png -->
*LLM Buttons pattern with both buttons*

### Blog Filters (Bonus Blocks)

This plugin includes three blocks for filtering blog archives. These demonstrate WordPress Interactivity API patterns with shared global state.

#### Blog Category Filter

Displays category pills for filtering posts without page reload.

```
<!-- wp:tgp/blog-category-filter /-->
```

<!-- Screenshot: blog-category-filter.png -->
*Category filter pills with active state*

#### Blog Search

Search input that filters posts in real-time.

```
<!-- wp:tgp/blog-search /-->
```

#### Blog Filters (Container)

Orchestrates the filter state and provides the post list. Wrap your query loop with this block.

```
<!-- wp:tgp/blog-filters -->
    <!-- wp:tgp/blog-category-filter /-->
    <!-- wp:tgp/blog-search /-->
    <!-- Your query loop here -->
<!-- /wp:tgp/blog-filters -->
```

The three blocks share state via the WordPress Interactivity API. Changing a category or search term updates all blocks and filters the visible posts.

<!-- Screenshot: blog-filters-demo.gif -->
*Blog filters in action: category selection and search*

## GEO: Generative Engine Optimization

SEO optimizes for Google. GEO optimizes for AI.

AI assistants like ChatGPT, Claude, and Perplexity are becoming primary research tools. When someone asks "what's the best approach to systems integration?", these tools pull from sources they can read and trust.

**The shift is measurable:**
- AI-referred traffic increased 527% in early 2025 (Ahrefs)
- 60% of Google searches now end without a click — users get answers from AI summaries
- ChatGPT and Perplexity are routing millions of queries daily

**How this plugin helps with GEO:**

| GEO Factor | How Plugin Addresses It |
|------------|------------------------|
| **Structured content** | Clean markdown with clear headings, not messy HTML |
| **Machine-readable index** | `/llms.txt` tells AI what content exists |
| **Frontmatter metadata** | YAML provides title, date, author, URL context |
| **Single-file export** | `/llms-full.txt` for complete site ingestion |
| **Standard compliance** | Follows llmstxt.org specification |

If your content isn't accessible to AI systems, you're invisible to a growing segment of search. This plugin fixes that.

## Installation

1. Upload the `tgp-llms-txt` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Visit `yoursite.com/llms.txt` to verify

No configuration needed. It works out of the box.

## Endpoints

| Endpoint | Content-Type | Description |
|----------|--------------|-------------|
| `/llms.txt` | `text/plain` | Index of all pages and posts with markdown links |
| `/llms-full.txt` | `text/plain` | Complete site content concatenated |
| `/page-slug.md` | `text/markdown` | Individual page as markdown with YAML frontmatter |
| `/blog/post-slug.md` | `text/markdown` | Individual post as markdown with YAML frontmatter |

## Development

This plugin is open source and demonstrates production engineering practices.

### Documentation

| Document | Description |
|----------|-------------|
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to contribute, code style, PR process |
| [docs/TESTING.md](docs/TESTING.md) | Testing guide: PHPUnit, Jest, Playwright |
| [docs/BLOCK-DEVELOPMENT.md](docs/BLOCK-DEVELOPMENT.md) | Block development patterns and Interactivity API |
| [docs/BUTTON-STYLING.md](docs/BUTTON-STYLING.md) | Block Supports API and theme integration |

### Architecture Decisions

Significant technical decisions are documented as ADRs:

| ADR | Decision |
|-----|----------|
| [001](docs/adr/001-interactivity-api-state-vs-context.md) | State vs Context in Interactivity API |
| [002](docs/adr/002-skip-serialization-pattern.md) | Skip serialization for server-rendered blocks |
| [003](docs/adr/003-shared-helper-classes.md) | Shared PHP helper classes for block rendering |

### Testing

```bash
# Install dependencies
composer install
npm install

# Run tests
composer test        # PHPUnit (PHP logic)
npm test             # Jest (JS stores)
npm run test:e2e     # Playwright (browser tests, requires Docker)

# Linting
composer lint        # PHPCS
```

Tests run automatically on GitHub Actions for every push and pull request.

### Local Development

The plugin uses `@wordpress/env` for local development:

```bash
npm run wp-env start   # Start WordPress environment
npm run wp-env stop    # Stop environment
```

## Plugin Structure

```
tgp-llms-txt/
├── tgp-llms-txt.php              # Main plugin file
├── includes/
│   ├── class-endpoint-handler.php     # Routes .md and llms.txt requests
│   ├── class-llms-txt-generator.php   # Generates /llms.txt index
│   ├── class-markdown-converter.php   # Gutenberg to markdown conversion
│   ├── class-frontmatter.php          # YAML frontmatter generation
│   ├── class-button-block-renderer.php    # Shared button rendering logic
│   └── class-pill-block-renderer.php      # Shared pill/filter rendering
├── blocks/
│   ├── copy-button/              # "Copy for LLM" button
│   │   ├── block.json
│   │   ├── index.js              # Editor component
│   │   ├── view.js               # Interactivity API store
│   │   ├── render.php            # Server-side rendering
│   │   └── style.css
│   ├── view-button/              # "View as Markdown" link
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── render.php
│   │   └── style.css
│   ├── blog-filters/             # Filter state orchestrator
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── view.js               # Global filter state
│   │   └── render.php
│   ├── blog-category-filter/     # Category pills
│   │   ├── block.json
│   │   ├── index.js
│   │   ├── view.js
│   │   ├── render.php
│   │   └── style.css
│   └── blog-search/              # Search input
│       ├── block.json
│       ├── index.js
│       ├── view.js
│       ├── render.php
│       └── style.css
├── tests/
│   ├── php/                      # PHPUnit tests
│   ├── js/                       # Jest tests
│   └── e2e/                      # Playwright tests
└── docs/
    ├── TESTING.md
    ├── BLOCK-DEVELOPMENT.md
    ├── BUTTON-STYLING.md
    └── adr/                      # Architecture Decision Records
```

### Core Classes

**`TGP_Endpoint_Handler`**
Intercepts requests early in the WordPress lifecycle. Matches URL patterns (`*.md`, `llms.txt`) and routes them to the appropriate handler.

**`TGP_LLMs_Txt_Generator`**
Builds the `/llms.txt` index file. Queries all published pages and posts, groups posts by category, and formats everything according to the llmstxt.org specification.

**`TGP_Markdown_Converter`**
Converts WordPress Gutenberg block content to clean markdown. Handles headings, paragraphs, lists, tables, blockquotes, and inline formatting.

**`TGP_Frontmatter`**
Generates YAML frontmatter for individual `.md` endpoints. Includes title, URL, date, author, and excerpt metadata.

**`TGP_Button_Block_Renderer`** / **`TGP_Pill_Block_Renderer`**
Shared helper classes for consistent block rendering. Extract Block Supports styles, build class names, generate inline styles.

### Gutenberg Blocks

| Block | Purpose | Interactivity |
|-------|---------|---------------|
| `tgp/copy-button` | Copy page as markdown | Local context |
| `tgp/view-button` | Link to .md endpoint | None (static) |
| `tgp/blog-filters` | Filter state container | Global state provider |
| `tgp/blog-category-filter` | Category pill filters | Reads global state |
| `tgp/blog-search` | Search input | Reads/writes global state |

## How It Works

1. User visits `/about.md`
2. `TGP_Endpoint_Handler` intercepts the request at `init` (priority 0)
3. It extracts the slug (`about`) and loads the corresponding page
4. `TGP_Frontmatter` generates YAML metadata
5. `TGP_Markdown_Converter` transforms Gutenberg HTML to markdown
6. Response is served with `Content-Type: text/markdown`

The same flow applies to `/llms.txt`, except `TGP_LLMs_Txt_Generator` builds the full index instead of converting a single page.

## Caching

Responses include a `Cache-Control: public, max-age=3600` header (1 hour). For production sites with caching plugins, the `.md` and `.txt` endpoints will be cached like any other page.

## Requirements

- WordPress 6.5+ (Interactivity API required)
- PHP 8.2+

## Built By

[The Growth Project](https://thegrowthproject.com.au) — Technology delivery for founders and operators.

We build systems that work. AI implementation, DevOps, integration, platform builds.

- [AI Implementation Services](https://thegrowthproject.com.au/services/ai-implementation/)
- [All Services](https://thegrowthproject.com.au/services/)
- [Get in Touch](https://thegrowthproject.com.au/contact/)

## License

GPL v2 or later
