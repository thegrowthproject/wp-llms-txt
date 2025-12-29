# TGP LLMs.txt

A WordPress plugin that makes your site readable by AI systems.

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

## Plugin Structure

```
tgp-llms-txt/
├── tgp-llms-txt.php              # Main plugin file, bootstraps everything
├── includes/
│   ├── class-endpoint-handler.php    # Routes requests to .md and llms.txt
│   ├── class-llms-txt-generator.php  # Generates the /llms.txt index
│   ├── class-markdown-converter.php  # Converts Gutenberg HTML to markdown
│   ├── class-frontmatter.php         # Generates YAML frontmatter for .md files
│   └── class-ui-buttons.php          # Adds copy-to-clipboard functionality
└── blocks/
    └── llm-buttons/                  # Gutenberg block for copy buttons
        ├── block.json
        ├── index.js
        ├── view.js
        ├── render.php
        ├── style.css
        └── editor.css
```

### Core Classes

**`TGP_Endpoint_Handler`**
Intercepts requests early in the WordPress lifecycle. Matches URL patterns (`*.md`, `llms.txt`) and routes them to the appropriate handler. Uses both `init` hook (priority 0) for direct matching and rewrite rules for WordPress-standard routing.

**`TGP_LLMs_Txt_Generator`**
Builds the `/llms.txt` index file. Queries all published pages and posts, groups posts by category, and formats everything according to the llmstxt.org specification.

**`TGP_Markdown_Converter`**
The heavy lifter. Converts WordPress Gutenberg block content to clean markdown:
- Strips `<!-- wp:block -->` comments
- Converts headings, paragraphs, lists, tables, blockquotes
- Handles inline elements (bold, italic, links, code)
- Cleans up whitespace and entities

**`TGP_Frontmatter`**
Generates YAML frontmatter for individual `.md` endpoints. Includes title, URL, date, author, and excerpt metadata.

**`TGP_UI_Buttons`**
Provides JavaScript functionality for copying page content as markdown. Powers the Gutenberg block.

### Gutenberg Block

The `llm-buttons` block adds a "Copy as Markdown" button to any page. When clicked, it fetches the `.md` version of the current page and copies it to the clipboard.

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

- WordPress 5.0+ (Gutenberg required)
- PHP 7.4+

## License

GPL v2 or later
