# WordPress Base Template

A custom WordPress starter theme for building modern and fully customizable websites from scratch. Built with Timber/Twig templating, ACF Gutenberg blocks, and a comprehensive dashboard options framework.

## Requirements

- PHP 8.3+
- WordPress 6.7+
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- Composer
- [Prepros](https://prepros.io/) (for SCSS/JS compilation)

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
│   │   ├── _variables.scss        # CSS custom properties
│   │   ├── _mixins.scss           # Utility mixins
│   │   ├── _reset.scss            # CSS reset
│   │   ├── _extend.scss           # .typo, .btn, utility classes
│   │   ├── style.scss             # Main entry point
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
@import "../../mixins";

.main-my-block{
    .customBlock{
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

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
- Timber v2 with Twig templating engine
- Custom Gutenberg blocks with ACF (hero, text, pattern)
- Automatic block styles loading on frontend
- Parse all pages as Gutenberg block patterns
- `get_pattern()` helper for reusable templates
- Rich text formatting with `.typo` class

### Email
- Enhanced mail sending logic with logging
- Twig-based email templates
- SMTP settings (host, port, auth, encryption)

### Performance & Optimization
- Timber HTML cache
- HTML minification
- WEBP image converter & big images resizer

### Admin & Dashboard
- Custom dashboard options framework with conditional logic
- Customizable WordPress admin menu
- Header & Footer HTML code editors
- Favorite colors
- Cookies popup settings
- Maintenance mode
- Lorem ipsum posts generator

### Redirect Rules
- Custom post type for managing 301/302 URL redirects
- Dashboard widget with latest rules overview
- Duplicate and self-redirect detection
- Transient caching for performance
- Auto-publish on restore from trash (no draft state)

### Security & Cleanup
- Disable Gutenberg editor (for blog / everywhere)
- Disable all updates
- Disable customizer
- Disable srcset
- Remove default image sizes
- Disable core privacy tools
- Disable application passwords
- CYR2LAT (transliteration of Cyrillic in slugs and filenames)
- Disable DNS prefetch
- Disable REST API for anonymous users
- Disable WordPress emojis
- Disable embeds
- Disable default dashboard widgets
- Hide admin top bar on frontend
- Disable admin email verification
- Disable comments for all post types
- Delete child media on parent post delete
- Hide ACF from admin menu

### Integrations
- Google Maps API key (for ACF)
- Geolocation features (GeoIP2)
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
