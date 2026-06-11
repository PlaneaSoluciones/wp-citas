# wp-citas

WordPress plugin for managing and displaying quotes with authors, themes, and categories.

This is a maintained fork of [VR-Frases](https://wordpress.org/plugins/vr-frases/) by [Vicente Ruiz](https://www.vruiz.net), originally published on WordPress.org. The original plugin is GPLv2 — this fork continues under the same license. All original copyright notices are preserved in the source files.

## Features

- Full CRUD for quotes, authors, classes (categories) and themes (tags)
- AJAX admin interface with inline quick-edit and bulk operations
- CSV/TXT bulk import with duplicate detection
- 4 shortcodes: `[vrfrases]`, `[randomfrase]`, `[frasescount]`, `[autorescount]`
- Frontend multi-theme display (standard, dark, elegant, classic, minimalist) with user preferences stored in cookies
- Wikipedia author lookup from the admin
- Sidebar widget

## Installation

1. Download or clone this repo into `wp-content/plugins/vr-frases/`
2. Activate from WordPress admin → Plugins
3. Go to VR-Frases → Settings and configure the page slug
4. Create a page with that slug and add the `[vrfrases]` shortcode

## Requirements

- WordPress 5.5+
- PHP 7.4+

## License

GPLv2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)
