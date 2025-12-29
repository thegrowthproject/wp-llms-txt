# TGP LLMs.txt

WordPress plugin that generates `llms.txt` and `llms-full.txt` endpoints for AI/LLM consumption.

## What it does

- **`/llms.txt`** - Index of all pages with markdown URLs
- **`/llms-full.txt`** - Complete site content in a single markdown file
- **`.md` endpoints** - Every page/post available as markdown (e.g., `/about.md`)
- **Copy buttons** - Frontend buttons to copy page content as markdown

## Installation

1. Upload the `tgp-llms-txt` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Visit `/llms.txt` on your site

## Endpoints

| Endpoint | Description |
|----------|-------------|
| `/llms.txt` | Index listing all pages with links to markdown versions |
| `/llms-full.txt` | Full site content concatenated as markdown |
| `/page-slug.md` | Individual page as markdown with frontmatter |

## Block

The plugin includes a Gutenberg block "LLM Buttons" that adds copy-to-clipboard functionality for the current page's markdown content.

## License

GPL v2 or later
