# ADR 004: Self-Hosted Plugin Updates

## Status

Accepted

## Context

This plugin is distributed via GitHub, not the WordPress.org plugin directory. WordPress only checks wordpress.org for updates by default, so users would need to manually download and install new versions.

We needed a way to:

1. Notify users when updates are available
2. Allow one-click updates from wp-admin
3. Avoid external dependencies or paid services
4. Automate the release process

## Decision

Use native WordPress hooks to implement self-hosted updates, with a JSON manifest hosted on GitHub.

### Components

| Component | Location |
|-----------|----------|
| `TGP_Plugin_Updater` class | `includes/class-plugin-updater.php` |
| Update manifest | `update-manifest.json` (repo root) |
| Release workflow | `.github/workflows/release.yml` |

### WordPress Hooks Used

```php
// Check for updates (runs every 12 hours or on manual check)
add_filter( 'site_transient_update_plugins', [ $this, 'check_for_update' ] );

// Provide plugin info for "View details" modal
add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );
```

### Update Flow

```
┌─────────────────────────────────────────────────────────────┐
│ 1. GitHub Release Created (e.g., v1.3.0)                    │
├─────────────────────────────────────────────────────────────┤
│ • release.yml builds tgp-llms-txt.zip                       │
│ • Uploads zip to release assets                             │
│ • Updates update-manifest.json with new version             │
│ • Commits manifest back to main branch                      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. WordPress Checks for Updates                             │
├─────────────────────────────────────────────────────────────┤
│ • Triggered every 12 hours or manually                      │
│ • Our hook fetches update-manifest.json from GitHub         │
│ • Compares manifest version vs installed version            │
│ • If newer → adds to update transient                       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. User Sees Update in wp-admin                             │
├─────────────────────────────────────────────────────────────┤
│ • "Update available" badge on Plugins page                  │
│ • "View details" shows plugin info from manifest            │
│ • "Update Now" downloads zip from GitHub release            │
└─────────────────────────────────────────────────────────────┘
```

### Manifest Structure

```json
{
  "name": "TGP LLMs.txt",
  "slug": "tgp-llms-txt",
  "version": "1.2.0",
  "download_url": "https://github.com/.../releases/download/v1.2.0/tgp-llms-txt.zip",
  "requires": "6.5",
  "tested": "6.7",
  "requires_php": "8.2",
  "last_updated": "2026-01-02",
  "sections": {
    "description": "...",
    "changelog": "..."
  }
}
```

### Caching

The updater implements its own 12-hour cache (`tgp_llms_txt_update_data` transient) to avoid hitting GitHub on every WordPress update check.

## Consequences

### Positive

1. **Zero dependencies** - Uses only native WordPress hooks
2. **Automatic** - Release workflow updates manifest automatically
3. **Familiar UX** - Updates appear in standard WordPress UI
4. **Free** - No paid update service needed

### Negative

1. **GitHub dependency** - If GitHub is down, updates won't work
2. **Public repo required** - Manifest and zip must be publicly accessible
3. **Manual changelog** - Must update `sections.changelog` in manifest (or automate from CHANGELOG.md)

### Alternatives Considered

| Option | Pros | Cons |
|--------|------|------|
| Git Updater plugin | Feature-rich, handles private repos | External dependency, requires user to install another plugin |
| Plugin Update Checker library | Well-maintained, widely used | Adds ~50KB vendor code |
| WP Packages Update Server | Full control, private repo support | Requires hosting your own server |

## Testing Updates

To force a fresh update check:

```php
// In wp-admin or via WP-CLI
delete_site_transient( 'update_plugins' );
delete_transient( 'tgp_llms_txt_update_data' );
// Then visit wp-admin/plugins.php
```

## Related

- `TGP_Plugin_Updater` class implementation
- `.github/workflows/release.yml`
- `update-manifest.json`
