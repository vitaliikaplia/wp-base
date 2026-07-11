# WordPress Base Template

A custom WordPress starter theme for building modern and fully customizable websites from scratch. Built with Timber/Twig templating, ACF Gutenberg blocks, and a comprehensive dashboard options framework.

## Requirements

- PHP 8.3+
- WordPress 6.7+
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- Composer
- [Prepros](https://prepros.io/) (for SCSS/JS compilation)

## WordPress 7 Readiness

This starter theme is prepared for WordPress 7 and keeps the new core features configurable instead of disabling them by default. The dashboard options include fine-tuning controls for WP 7 frontend output, Font Library manager, image auto sizes, global styles, block support styles, and speculation rules.

## Installation

```bash
composer install
```

Activate the theme in WordPress admin, then configure settings under the custom dashboard options page.

## Directory Structure

```
wp-base/
├── core/                          # Core theme logic
│   ├── init.php                   # Constants, helpers, bootstrapping
│   ├── gutenberg.php              # Block registration, categories, rendering
│   ├── acf.php                    # ACF configuration
│   ├── acf-json/                  # ACF field group JSON (auto-sync)
│   ├── lang/                      # Translation files (.po/.mo)
│   ├── vendor/                    # Composer dependencies
│   └── includes/
│       ├── back/                  # Admin: options, post types, taxonomies
│       └── front/                 # Frontend: helpers, rendering
├── assets/
│   ├── scss/                      # SCSS source files
│   │   ├── _variables.scss        # CSS custom-property color tokens
│   │   ├── _reset.scss            # CSS reset
│   │   ├── _extend.scss           # .typo, .btn, utility classes
│   │   ├── style.scss             # Main entry point (@use partials)
│   │   └── blocks/                # Block-specific styles
│   │       ├── main/              # Main blocks (hero, text)
│   │       └── logical/           # Logical blocks (pattern)
│   ├── css/                       # Compiled & minified CSS
│   ├── js/                        # JavaScript files
│   └── block-preview/             # Block preview images for editor
├── views/                         # Twig templates
│   ├── block-base.twig            # Base block template (with wrapper)
│   ├── block-simple-base.twig     # Simple block template (no wrapper)
│   ├── blocks/                    # Block templates
│   │   ├── main/                  # Main blocks (hero.twig, text.twig)
│   │   └── logical/               # Logical blocks (pattern.twig)
│   ├── overall/                   # Layout templates (header, footer, etc.)
│   ├── dashboard/                 # Dashboard options field templates
│   └── email/                     # Email templates
├── functions.php                  # Theme setup & includes
├── composer.json
├── prepros.config                 # Prepros build configuration
└── style.css                      # Theme metadata
```

## Gutenberg Block System

The theme uses ACF blocks with Timber/Twig rendering. Only registered custom blocks are allowed in the editor (whitelist approach).

### Built-in Blocks

| Block | Category | Description |
|---|---|---|
| `hero` | main | Hero section with subtitle, title, description, and action buttons |
| `text` | main | Rich text content area with title and `.typo` formatting |
| `iframe` | main | Embed / iframe content block |
| `pattern` | logical | Reusable pattern block (references a Pattern post) |

### Block Categories

| Slug | Title |
|---|---|
| `main` | Main blocks |
| `logical` | Logical blocks |

### Creating a New Block

**Step 1.** Add block definition to `get_custom_gutenberg_blocks_array()` in `core/gutenberg.php`:

```php
array(
    "name" => "my-block",
    "label" => __("My Block", TEXTDOMAIN),
    "category" => "main",
    'defaults' => array(
        'field_5es3eaf348ca151aff27' => array('desktop_tablet','mobile')
    )
),
```

**Step 2.** Create Twig template `views/blocks/main/my-block.twig`:

```twig
{% extends "block-base.twig" %}

{% block content %}
    {% if fields.title %}
    <h2>{{ fields.title }}</h2>
    {% endif %}
{% endblock %}
```

Two base templates available:
- `block-base.twig` — full wrapper with responsive options, spacing, background color, anchor
- `block-simple-base.twig` — minimal wrapper (used by pattern block)

**Step 3.** Create SCSS file `assets/scss/blocks/main/my-block.scss`:

```scss
.main-my-block{
    .customBlock{
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

Block SCSS needs no import — it only styles `.{category}-{block-name}` and is compiled standalone. (The theme uses Sass `@use`, not `@import`, and has no mixin layer.)

CSS class naming convention: `.{category}-{block-name}`

**Step 4.** Add Prepros config entry in `prepros.config` → `files` array:

```json
{
    "file": "assets/scss/blocks/main/my-block.scss",
    "config": {
        "tasks": { "minify-css": { "enable": true } },
        "customOutput": "assets/css/blocks/main/my-block.min.css"
    }
}
```

**Step 5.** Create ACF field group via WordPress admin → ACF → Field Groups. Set location rule to `Block` → `is equal to` → `acf/main-my-block`. ACF JSON auto-syncs to `core/acf-json/`.

**Step 6.** *(Optional)* Add preview image at `assets/block-preview/main/my-block.png`.

Block styles are loaded automatically on the frontend only when the block is present on the page (`has_block()` check).

## Patterns System

The theme includes a custom post type `patterns` with a taxonomy `pattern_categories` for reusable content blocks. Patterns can be:

- Inserted via the **Pattern** Gutenberg block using `get_pattern()` helper
- Registered as native WordPress block patterns (auto-registered from all `patterns` posts)

When `parse_all_pages_blocks_as_gutenberg_patterns` option is enabled, all blocks from published pages are also registered as patterns.

## Dashboard Options

A custom options framework with support for multiple field types:

| Field Type | Description |
|---|---|
| `text` | Text input |
| `textarea` | Textarea |
| `number` | Number input |
| `password` | Password input |
| `range` | Range slider |
| `select` | Dropdown select |
| `select-multiple` | Multi-select dropdown |
| `checkbox` | Checkbox toggle |
| `color` | Color picker |
| `code` | Code editor |
| `mce` | TinyMCE rich text editor |
| `link` | URL link input with title |
| `nav-menu` | Navigation menu selector |

Features:
- Conditional logic (show/hide fields based on other field values)
- Tab-based grouping
- Localization support (WPML compatible)

## Features & Options

### Content & Templating
- Timber v2 with Twig templating (Block API v3 ACF blocks: `hero`, `text`, `iframe`, `pattern`)
- Per-block CSS auto-loaded on the frontend only when the block is present (`has_block()`)
- Reusable patterns: `patterns` CPT + `get_pattern()` helper, optional page-blocks-as-patterns, patterns admin grid (live search + remembered view), WP 7 pattern-metadata stripping
- Rich text with the `.typo` class
- Timber render helpers exposed to Twig: `picture` / `picture_src` / `svg` filters and `picture()` / `icon()` functions (WEBP-aware `<picture>`, inline sanitized SVG)

### Managed SVG icons
- Admin icon manager (Appearance → Icons): browse the theme's default sprite and upload your own into `uploads`
- `icon()` Twig helper + a custom `icon_select` ACF field that works inside the Gutenberg block inspector
- Bundled social icons: Facebook, Instagram, TikTok, LinkedIn, X, YouTube, Telegram, WhatsApp, Threads, Pinterest, Viber

### Custom ACF field types
- `color_select` — pick from the theme color palette
- `working_hours` — weekly opening-hours picker
- `icon_select` — sprite icon picker
- `nav_menu` — navigation menu selector

### Colors
- Theme palette parsed from the `--color-*` custom properties in `_variables.scss` and fed to the WordPress editor palette, the ACF color picker and the TinyMCE text-color grid (replaces the old ACF "favorite colors")

### Communications
- Email: Twig templates, logging (`mail-log`), SMTP settings, dark-mode-safe (light-locked) template
- SMS: SMS-Fly / TurboSMS providers with logging (`sms-log`)
- Telegram bot messaging
- Phone helpers via libphonenumber (`fix_phone_format`, `nice_phone_format`, …)
- "Send test" dashboard widget to verify Email / SMS / Telegram over AJAX

### SEO & PWA
- Schema.org JSON-LD `@graph` (Organization / WebSite / WebPage / Article), option-driven
- Web manifest generation (icons, colors, short name / description) from the favicon system
- Yoast SEO i18n breadcrumb / title fixes

### Frontend
- Outbound external-link interstitial (opt-in): `/?go=` "you are leaving" countdown page
- Headroom.js hide-on-scroll sticky header
- Cookie consent popup (configured in the options framework)
- Test header / footer chrome wired to the Header / Footer ACF options

### Performance & Optimization
- Timber HTML cache, HTML minification, WEBP converter & big-image resizer

### Admin & Dashboard
- Tabbed dashboard options framework with conditional logic + WPML localization
- Customizable admin menu, header/footer code editors, maintenance mode, lorem-ipsum post generator
- ACF field-group order column, WYSIWYG `delay=0`, encrypt/decrypt helper for connector secrets
- Hides the WordPress 7 admin-bar command palette

### Redirect Rules
- 301/302 URL redirect manager (`redirect-rules` CPT) with dashboard widget, duplicate/self-redirect detection, transient caching, and auto-publish on restore from trash

### Security & Cleanup (option-gated toggles, grouped into tabs)
- Disable: all updates, customizer, srcset, default image sizes, core privacy tools, application passwords, DNS prefetch, REST API for anonymous users, emojis, embeds, comments, blog tags, Connectors admin page
- CYR2LAT transliteration; allow `.m3u/.m3u8/.ts` uploads; delete child media on parent delete; hide admin top bar / ACF; disable admin email verification; disable default dashboard widgets
- WordPress 7 frontend tuning: block-library styles, global styles, block-support styles, Font Library output + manager, image auto-sizes, speculation rules
- Disable Gutenberg (for blog / everywhere)

### Integrations
- Google Maps API key, Telegram / SMS credentials, geolocation (GeoIP2), hardened session/IP helpers
- Localization ready (en, ru, uk translations)

## Build Process

This project uses [Prepros](https://prepros.io/) for asset compilation:

- **SCSS** → minified CSS (`assets/scss/` → `assets/css/`)
- **JS** → concatenated & minified (`assets/js/` → `assets/js/*.min.js`)

Open the project folder in Prepros — it will detect `prepros.config` automatically.

## License

This theme is licensed under the [GNU General Public License v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

[Vitalii Kaplia](https://vitaliikaplia.com/)
