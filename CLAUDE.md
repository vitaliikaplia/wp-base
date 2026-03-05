# CLAUDE.md

## Project Overview

WordPress Base Template (wp-base) — custom starter theme built with Timber/Twig, ACF Gutenberg blocks, and a custom dashboard options framework.

## Tech Stack

- PHP 8.3+, WordPress 6.7+
- Timber v2 / Twig for templating
- ACF Pro for Gutenberg blocks and custom fields
- Prepros for SCSS/JS compilation (not webpack/vite)
- Composer (vendor directory: `core/vendor/`)

## Key Constants (core/init.php)

- `TEXTDOMAIN` = `'wp-base'` — always use this for translations: `__("Label", TEXTDOMAIN)`
- `LANG_SUFFIX` — language suffix for multilingual options (e.g. `"_uk"`, `"_en_US"`)
- `TIMBER_VIEWS` = `'views'`
- `SVG_SPRITE_URL` — URL to SVG sprite with cache-busting version

## Project Structure

- `functions.php` — only loads `core/init.php`
- `core/init.php` — constants, helpers, bootstrapping, auto-loads all PHP from `includes/`, `cache/`, `ajax/`
- `core/gutenberg.php` — block registration, categories, rendering
- `core/acf.php` — ACF configuration
- `core/acf-json/` — ACF field group JSON files (auto-sync)
- `core/includes/back/` — admin logic (options, CPTs, taxonomies, system features)
- `core/includes/front/` — frontend helpers
- `views/` — all Twig templates
- `assets/scss/` → `assets/css/` — compiled by Prepros

## Gutenberg Blocks

### Block naming convention

- Registration name: `acf/{category}-{blockname}` (e.g. `acf/main-hero`)
- CSS class: `.{category}-{blockname}` (e.g. `.main-hero`)
- Twig template: `views/blocks/{category}/{blockname}.twig`
- SCSS: `assets/scss/blocks/{category}/{blockname}.scss`
- Compiled CSS: `assets/css/blocks/{category}/{blockname}.min.css`
- Preview image: `assets/block-preview/{category}/{blockname}.png`

### Block categories

- `main` — main content blocks (hero, text)
- `logical` — logical/utility blocks (pattern)

### Creating a new block (checklist)

1. Add to `get_custom_gutenberg_blocks_array()` in `core/gutenberg.php`
2. Create Twig template extending `block-base.twig` (or `block-simple-base.twig` for minimal)
3. Create SCSS file with `@import "../../mixins";`
4. Add Prepros config entry in `prepros.config` → `files` array
5. Create/modify ACF JSON field group in `core/acf-json/` with location `acf/{category}-{blockname}`
6. Optionally add preview image

### Block rendering context

Twig templates receive: `block`, `block_name`, `block_class`, `fields`, `is_preview`, `is_admin`, `is_example`

### Block styles auto-loading

Frontend: styles load only when block is present (`has_block()` check). Editor: all block styles registered on init.

## ACF JSON Files

- Labels must be in **Ukrainian** (Підзаголовок, Заголовок, Опис, Кнопки, etc.)
- After modifying, update the `modified` timestamp: `date +%s`
- Field key format: `field_[hex]`
- Field name format: `field_{context}_{property}` (e.g. `field_hero_title`)
- Location rule: `"param": "block", "value": "acf/main-hero"`

## Dashboard Options Framework

### Options structure (core/includes/back/dashboard-options.php)

```php
'section_slug' => [
    'label' => __('Section Label', TEXTDOMAIN),
    'title' => __('Title', TEXTDOMAIN),
    'fields' => [
        [
            'type'  => 'text|textarea|number|password|range|select|select-multiple|checkbox|color|code|mce|link|nav-menu',
            'name'  => 'option_name',
            'label' => __('Label', TEXTDOMAIN),
        ],
    ],
]
```

### Tabs

Use `tab_start` / `tab_end` fields to group options into tabs.

### Conditional logic

```php
'conditional_logic' => [
    'action' => 'show',  // or 'hide'
    'rules' => [
        ['field' => 'field_name', 'operator' => '==', 'value' => 'expected_value'],
    ],
],
```

### Localization

Fields with `'localize' => true` store separate values per language using `LANG_SUFFIX`.

## Custom Post Types

Registered in `core/includes/back/custom-post-types.php`:
- `redirect-rules` — URL redirects (under Settings menu)
- `patterns` — reusable content blocks
- `mail-log` — email logging (read-only)

## SCSS Conventions

- Block SCSS imports: `@import "../../mixins";`
- Mobile breakpoint: `@media (max-width: 768px)`
- Use hardcoded color values in block styles (CSS variables from `_variables.scss` are mostly commented out)
- Button classes: `.btn`, `.btn.primary`, `.btn.secondary`

## Prepros Config

Block entry format in `prepros.config` → `files`:
```json
{
    "file": "assets/scss/blocks/main/blockname.scss",
    "config": {
        "customOutput": "assets/css/blocks/main/blockname.min.css",
        "tasks": { "minify-css": { "enable": true } }
    }
}
```

## Communication

- User communicates in Ukrainian
- Commit messages in English
- Code comments in Ukrainian (for inline) or English (for documentation)
