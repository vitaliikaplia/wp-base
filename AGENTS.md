# AGENTS.md

## Project Overview

WordPress Base Template (wp-base) — custom starter theme built with Timber/Twig, ACF Gutenberg blocks, and a custom dashboard options framework.

## ⚠️ Destructive / bulk operations — pre-flight checklist (read before acting)

Any operation that is **irreversible** or touches **many items at once** — bulk DB writes to post/pattern content, `wp_update_post` / `wp_insert_post` over multiple posts, mass file rewrites, deletes — MUST follow this, no exceptions:

1. **Back up first.** Snapshot exactly what you are about to change (export the rows / copy the files). Patterns and posts written from the CLI get **no revisions** — there is **no undo** unless you make one.
2. **Dry-run on ONE item** — apply the change to a single item, do **not** write, inspect the result.
3. **Verify the RENDERED result, not a proxy.** Check what a human sees — `do_blocks()` output, the actual text/HTML — not "the structure is present" or "the field changed". Shallow verification is how corruption slips through.
4. **Then apply**, and re-verify the rendered output across a sample afterwards.

**Content writes specifically:** `wp_update_post` / `wp_insert_post` run `wp_unslash()` internally, so **always** pass `wp_slash(serialize_blocks(...))`. Skip it and the block-comment JSON escapes are stripped (`<`→`u003c`, `\r\n`→`rn`) and every WYSIWYG/HTML field is destroyed — silently for blocks with no escaped chars, visibly (raw `u003ch2…` / stray `rn`) for the rest. Also: call `kses_remove_filters()` in CLI scripts (no user → block comments get stripped) and preserve `post_author` (CLI writes default it to `0`).

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

- `main` — main content blocks (hero, text, iframe)
- `logical` — logical/utility blocks (pattern)

### Creating a new block (checklist)

1. Add to `get_custom_gutenberg_blocks_array()` in `core/gutenberg.php`
2. Create Twig template extending `block-base.twig` (or `block-simple-base.twig` for minimal)
3. Create SCSS file with no imports; style only the block wrapper selector `.{category}-{blockname}`
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
- Field key format: `field_[hex]`; some legacy groups still use descriptive keys like `field_hero_title`
- Field `name` values are the actual template-facing keys in `fields` (e.g. `title`, `content`, `html_code`); use context prefixes only when needed to avoid collisions
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
- `sms-log` — SMS logging (read-only)

## Custom ACF field types

Registered from `core/includes/back/acf-field-*.php` (auto-loaded), usable in any field group / block:
- `nav_menu` — navigation menu picker
- `color_select` — pick a colour from the theme palette (parsed from `_variables.scss`); the picker binds via document-level delegation so it works in the Gutenberg block inspector
- `working_hours` — weekly opening-hours picker (day keys `Mo`…`Su`)
- `icon_select` — sprite-icon picker (theme + uploads managed icons)

## Ported subsystems (where things live)

- Managed SVG icons: `core/includes/back/dashboard-icons.php` + `core/includes/front/managed-icons.php` + `core/ajax/icons.php`; `icon()` / `managed_icon()` Twig helpers; theme sprite `assets/svg/sprite.svg` (read-only) + an uploads sprite. Admin page: Appearance → Icons.
- Communications: `system-send-email.php` / `system-send-sms.php` (SMS-Fly, TurboSMS) / `system-telegram.php`, `core/includes/front/phone-functions.php` (libphonenumber), and the "Send test" widget (`dashboard-widget-send-test.php`). Logs: `mail-log` / `sms-log` CPTs.
- SEO / PWA: `core/includes/front/schema.php` (JSON-LD, option-driven), `system-favicon.php` (icons + web manifest), `core/includes/front/wpseo-fix.php` (Yoast i18n).
- Render helpers: `core/includes/front/render-picture-tag.php` (`picture`/`picture_src`) and `render-svg-tag.php` (`svg`), registered in `core/timber.php`.
- External-link interstitial: `core/includes/front/external-links.php` (+ `views/external-link.twig`), opt-in.
- Colours: `core/includes/back/system-color-presets.php` parses `--color-*` tokens → editor / ACF / TinyMCE palettes.
- Misc helpers: `core/encrypt-decrypt.php` (openssl), `core/includes/back/acf-order-column.php`, `acf-wysiwyg-delay.php`.

Prefix convention: new code is **unprefixed** (matches the base's existing global functions), not `insight_`/`skyta_`/`hw_`.

## SCSS Conventions

- Pull partials in with `@use`, never `@import`. `@import` is reserved for genuinely external CSS (e.g. the web-font URL in `_fonts.scss`). `@use` rules must come before any other rule in a file (see `gutenberg.scss`).
- No SCSS mixins. Use native CSS properties and let Prepros' autoprefixer add vendor prefixes; the legacy `_mixins.scss` prefix helpers were removed.
- Block SCSS files need no import — each only styles `.{category}-{block-name}` and is compiled standalone by Prepros.
- Mobile breakpoint: `@media (max-width: 768px)`
- CSS units: prefer `px` and `%`; `vw`, `vh`, and `clamp()` are allowed for fluid sizing. Avoid `em`/`rem` unless explicitly required.
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
