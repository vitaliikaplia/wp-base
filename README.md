# WordPress Base Template

A custom WordPress starter theme for building modern and fully customizable websites from scratch. Built with Timber/Twig templating, ACF Gutenberg blocks, and a comprehensive dashboard options framework.

Live demo: [wp-base.kaplia.top](https://wp-base.kaplia.top/)

## Requirements

- PHP 8.3+
- WordPress 6.7+
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- Composer (installs Timber, GeoIP2 and libphonenumber into `core/vendor/`)
- Mini Prepros — PhpStorm plugin for SCSS/JS compilation (see [Build Process](#build-process))
- **WP-LOC** — for multilingual projects only (see [Multilingual](#multilingual--wp-loc)); single-language sites need nothing

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
│   ├── init.php                   # Constants, helpers, bootstrapping (auto-loads includes/, cache/, ajax/)
│   ├── timber.php                 # Timber/Twig setup + custom Twig filters & functions
│   ├── gutenberg.php              # Block registration, categories, rendering
│   ├── acf.php                    # ACF configuration
│   ├── debugging.php              # write_log() + the pr() dump helper (WP_DEBUG only)
│   ├── encrypt-decrypt.php        # Authenticated AES-256-CBC helper for connector secrets
│   ├── acf-json/                  # ACF field group JSON (auto-sync)
│   ├── ajax/                      # AJAX endpoints (auto-loaded)
│   ├── cache/                     # Timber HTML cache (auto-loaded)
│   ├── geo/                       # GeoIP2 database
│   ├── lang/                      # Translation files (.po/.mo — en, ru, uk)
│   ├── vendor/                    # Composer dependencies
│   └── includes/                  # Everything here is auto-loaded by init.php
│       ├── back/                  # Admin: options, CPTs, taxonomies, system features,
│       │                          #   custom ACF field types, svg-sanitizer.php
│       └── front/                 # Frontend: helpers, rendering
├── assets/
│   ├── scss/                      # SCSS source files (@use, no @import except fonts)
│   │   ├── style.scss             # Frontend entry point (@use partials below)
│   │   ├── _main.scss             # Body/main layout, sticky footer, cookie popup, .inner
│   │   ├── _fonts.scss            # Web-font import
│   │   ├── _variables.scss        # CSS custom-property color tokens (--color-*)
│   │   ├── _reset.scss            # CSS reset
│   │   ├── _animation.scss        # Keyframes / animation helpers
│   │   ├── _wp.scss               # WordPress core-markup styles
│   │   ├── _extend.scss           # .typo, .btn, utility classes
│   │   ├── _header.scss           # Header chrome (logo · centered menu · socials grid)
│   │   ├── _footer.scss           # Footer chrome (socials · menu · copyright grid)
│   │   ├── _page-404.scss         # 404 page
│   │   ├── _page-password.scss    # Password-protected page
│   │   ├── _page-external-link.scss  # External-link interstitial
│   │   ├── dashboard.scss         # Standalone entry: admin dashboard styles
│   │   ├── dashboard/             # Dashboard partials (_icons, _redirect-rules)
│   │   ├── gutenberg.scss         # Standalone entry: block-editor styles
│   │   ├── login.scss             # Standalone entry: wp-login page
│   │   ├── tinymce.scss           # Standalone entry: classic editor
│   │   ├── custom-options.scss    # Standalone entry: options-framework UI
│   │   ├── plugins/               # Third-party CSS (_select2.min)
│   │   └── blocks/                # Per-block styles (compiled standalone)
│   │       ├── main/              # Main blocks (hero, text, iframe)
│   │       └── logical/           # Logical blocks (pattern)
│   ├── css/                       # Compiled & minified CSS
│   ├── js/                        # JavaScript files (jquery.min.js is bundled, 3.7.1)
│   ├── svg/                       # Theme icon sprite + logo
│   └── block-preview/             # Block preview images for editor
├── views/                         # Twig templates
│   ├── base.twig                  # Root layout (base-clean.twig = chrome-less variant)
│   ├── index.twig / page.twig / single.twig / author.twig / blog.twig  # Template hierarchy
│   ├── 404.twig / password.twig / external-link.twig                   # Utility pages
│   ├── block-base.twig            # Base block template (with wrapper)
│   ├── block-simple-base.twig     # Simple block template (no wrapper)
│   ├── blocks/                    # Block templates
│   │   ├── main/                  # Main blocks (hero.twig, text.twig, iframe.twig)
│   │   └── logical/               # Logical blocks (pattern.twig)
│   ├── overall/                   # Layout partials (html-header, header, footer, cookie, picture-tag)
│   ├── dashboard/                 # Dashboard options field templates
│   └── email/                     # Email templates
├── functions.php                  # Loads core/init.php — the only bootstrap
├── index.php                      # WP template hierarchy → Timber::render(*.twig)
├── page.php / single.php          # Password-gated, then page.twig / single.twig
├── archive.php / author.php / search.php / 404.php
├── composer.json / composer.lock
├── prepros.config                 # Build configuration (read by Mini Prepros)
├── style.css                      # Theme metadata
├── README.md / AGENTS.md          # This file / conventions for AI agents
└── screenshot.png
```

The root PHP files are thin WordPress template-hierarchy entry points: each builds a Timber context and renders a Twig template, passing `TIMBER_CACHE_TIME`. `page.php` and `single.php` render `password.twig` first when the post is password-protected.

Several of them pass a candidate list and Timber uses the first template that exists:

| Entry point | Candidates | Ships |
|---|---|---|
| `index.php` (home) | `front-page.twig` → `home.twig` → `index.twig` | only `index.twig` |
| `archive.php` | `archive.twig` → `index.twig` | only `index.twig` |
| `search.php` | `search.twig` → `archive.twig` → `index.twig` | only `index.twig` |

So archives, search and the blog home all render through `index.twig` out of the box — drop in the more specific template when a project needs one.

## Gutenberg Block System

The theme uses ACF blocks with Timber/Twig rendering. Only registered custom blocks are allowed in the editor (whitelist approach).

### Built-in Blocks

| Block | Category | Description |
|---|---|---|
| `hero` | main | Hero section with subtitle, title, description, and action buttons |
| `text` | main | Rich text content area with title and `.typo` formatting |
| `iframe` | main | Embed / iframe block that renders textarea-provided HTML via Twig `raw` output |
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

**Step 4.** Add build config entry in `prepros.config` → `files` array:

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

ACF JSON labels should be Ukrainian, the `modified` value should be refreshed with `date +%s`, and field keys should use the `field_[hex]` format for new groups. Field `name` values are the keys consumed in Twig through `fields`.

**Step 6.** *(Optional)* Add preview image at `assets/block-preview/main/my-block.png`.

Block styles are loaded automatically on the frontend only when the block is present on the page (`has_block()` check).

## Patterns System

The theme includes a custom post type `patterns` with a taxonomy `pattern_categories` for reusable content blocks. Patterns can be:

- Inserted via the **Pattern** Gutenberg block using `get_pattern()` helper
- Registered as native WordPress block patterns (auto-registered from all `patterns` posts)

When `parse_all_pages_blocks_as_gutenberg_patterns` option is enabled, all blocks from published pages are also registered as patterns.

## Dashboard Options

A custom options framework with support for multiple field types:

| Field Type | Description | Saved as |
|---|---|---|
| `text` | Text input | sanitized text |
| `textarea` | Textarea | sanitized text (newlines kept) |
| `number` | Number input | numeric |
| `password` | Password input | sanitized text |
| `range` | Range slider | numeric |
| `select` | Dropdown select | sanitized text |
| `select-multiple` | Multi-select dropdown | array of sanitized text |
| `checkbox` | Checkbox toggle | `'1'` or `''` |
| `color` | Color picker | sanitized text |
| `code` | Code editor | **raw** (holds custom HTML/JS by design) |
| `mce` | TinyMCE rich text editor | **raw** (holds custom HTML by design) |
| `link` | URL link input with title | url / title / target |
| `nav-menu` | Navigation menu selector | sanitized text |

Features:
- Conditional logic (show/hide fields based on other field values)
- Tab-based grouping
- Localization support via WP-LOC (see [Multilingual](#multilingual--wp-loc))
- Per-type sanitization on save (`custom_option_sanitize_callback()`); `code` and `mce` are intentionally exempt

## Security Model

The theme is a framework for sites you build for other people, so the defaults are deliberately conservative. What this means in practice:

- **SVG uploads are off by default.** An SVG is a script-capable document. Turn on *Allow SVG uploads* (Security tab) and every uploaded file is still rewritten through `core/includes/back/svg-sanitizer.php` — scripts, event handlers, `foreignObject`, entity declarations and `javascript:` URIs are stripped, and anything unparseable is rejected. The same sanitizer runs when an SVG is rendered inline (`|svg`) and when a sprite is imported in the icon manager, so files uploaded before the option existed are cleaned on the way out too.
- **Proxy IP headers are not trusted by default.** `get_user_ip()` returns `REMOTE_ADDR` unless *Trust proxy IP headers* is enabled (or `TRUST_PROXY_HEADERS` is defined in `wp-config.php`). Enable it only behind Cloudflare or another reverse proxy — otherwise visitors can forge their own IP in logs and geolocation.
- **Twig autoescaping is off** (the Timber v2 default). Menus, `wp_head()` and block HTML have to pass through unescaped, so escaping is explicit: use `|esc_url` in `href`/`src`, `|esc_attr` in any other attribute, `|esc_html` for text that must not carry markup, and `|css_value` for anything interpolated into an inline `<style>`.
- **The `iframe` block filters raw HTML** for users without the `unfiltered_html` capability (`core/includes/back/acf-filter-raw-html.php`). Administrators and editors on a single-site install keep saving arbitrary markup; on multisite, or where a role plugin removed that capability, the value is passed through `wp_kses` with an embed-friendly whitelist.
- **Connector secrets are encrypted with a random IV and an HMAC** (`custom_encrypt()` / `custom_decrypt()`). Values written by the previous static-IV implementation still decrypt and are upgraded on the next save.
- **The mail-log preview is inert.** Logged email bodies can contain whatever a visitor typed into a form, so the preview is served with `Content-Security-Policy: sandbox` and displayed in a `sandbox=""` iframe.
- Every AJAX endpoint and admin form action checks both a capability and a nonce.

## Multilingual — WP-LOC

Multilingual support is built on **WP-LOC**, a lightweight multilingual plugin. WPML is no longer supported: every WPML-specific code path was removed from the theme, and the integration is written against WP-LOC's native API.

WP-LOC does ship an `icl_*` / `wpml_*` compatibility layer, but that exists for third-party code — **the theme does not use it**. Anything theme code needs goes through `core/includes/back/system-multilingual.php`, which degrades to sensible single-language answers when no multilingual plugin is installed. That means the theme runs unchanged on a single-language project, and no call site needs its own `function_exists()` guard.

### Language identifiers

WP-LOC keeps the URL slug separate from the database/API language code. Ukrainian is slug `ua`, code `uk`, locale `uk`. The theme helpers return the **slug**; `BLOGINFO_LANGUAGE` holds the locale.

### Theme helpers

| Helper | Returns |
|---|---|
| `theme_is_multilingual()` | whether WP-LOC is actually running |
| `theme_current_language()` | current language slug, falling back to the WP locale |
| `theme_default_language()` | the no-prefix default language |
| `theme_current_locale()` | current language as a WP locale |
| `theme_context_language()` | admin language behind wp-admin, frontend language elsewhere — use this for pickers and admin lists |
| `theme_languages()` | switcher entries (`code`, `locale`, `name`, `flag`, `url`, `active`); empty on single-language sites |
| `theme_translated_post_id($id, $lang = null)` | translated post ID, or the original when untranslated |
| `theme_translated_id($id, $element_type, $lang = null)` | same for any WP-LOC element type (`post_attachment`, `tax_category`, …) |
| `theme_post_language($id)` / `theme_term_language($id, $tax)` | the language an object belongs to |
| `theme_attachment_meta($id, $key)` | attachment meta, falling back to the default-language sibling |
| `theme_switch_language($lang)` / `theme_restore_language($prev)` | temporarily switch language + WP locale for background work |

### What the theme wires up

- **Language switcher** in the header, from the `languages` Timber context variable — renders nothing when the site is single-language. Styled in `_header.scss`, restyle per project.
- **Localized dashboard options** — any field flagged `'localize' => true` is registered through `wp_loc_multilingual_options`, and WP-LOC stores/reads it per language. `blogname`, `blogdescription`, `page_on_front` and `page_for_posts` are handled by WP-LOC itself.
- **Per-language caching** — `LANG_SUFFIX` namespaces every theme transient (`general_fields_ua`, `redirect_rules_en`, …). It is deliberately *not* used to build option names; WP-LOC owns that and resolves both suffix forms.
- **Language-scoped admin UI** — the `nav_menu` ACF field and the patterns grid only list content in the language being edited. Objects with no registered language stay visible, so single-language sites are never filtered down to nothing.
- **Transactional email in the recipient's language** — `send_email(['language' => 'en', …])` switches language and locale around template rendering, then restores.
- **Attachments** — images are used as given (WP-LOC resolves frontend lookups to the current-language record, and alt text is per-language on purpose); only `webp_url`, written once at upload, falls back to the default-language sibling.
- **Yoast OG locale** reads the current language from WP-LOC.

hreflang tags, `<html lang="">`, canonical URLs and query filtering are the plugin's job — the theme does not duplicate them.

### Twig

WP-LOC registers `wp_loc_language_switcher()`, `wp_loc_languages()`, `wp_loc_translate()` and `wp_loc_translations()`, and adds `current_language` to the Timber context. The theme adds `languages`:

```twig
{% if languages %}
  {% for language in languages %}
    <a href="{{ language.url|esc_url }}"{{ language.active ? ' aria-current="true"' : '' }}>{{ language.name|esc_html }}</a>
  {% endfor %}
{% endif %}
```

## Features & Options

### Content & Templating
- Timber v2 with Twig templating (Block API v3 ACF blocks: `hero`, `text`, `iframe`, `pattern`)
- Per-block CSS auto-loaded on the frontend only when the block is present (`has_block()`)
- `iframe` block uses a textarea ACF field (`html_code`) and renders editor-provided HTML with `|raw`, filtered by capability
- Reusable patterns: `patterns` CPT + `get_pattern()` helper, optional page-blocks-as-patterns, patterns admin grid (live search + remembered view), WP 7 pattern-metadata stripping
- Rich text with the `.typo` class

### Twig API

Added by this theme in `core/timber.php`:

| Filter | Purpose |
|---|---|
| `picture` / `picture_eager` / `picture_src` | WEBP-aware `<picture>` markup (`picture_eager` skips lazy-loading, for above-the-fold images) |
| `svg` | Inline, sanitized SVG from an ACF image field |
| `css_value` | Guard for values interpolated into an inline `<style>` |
| `ceil` | Round up |
| `pr` / `log` | Debug helpers (`pr` is a no-op unless `WP_DEBUG`) |

Timber already provides the WordPress escapers — `esc_url`, `esc_attr`, `esc_html`, `esc_js`, `wp_kses`, `wp_kses_post` — plus `shortcodes`, `wpautop`, `resize`, `excerpt` and others. Do not re-register them; Twig throws `Filter "…" is already registered` and every page 500s.

| Function | Purpose |
|---|---|
| `picture()` | `<picture>` markup |
| `icon()` / `managed_icon()` | Sprite icon from the theme or uploads sprite |
| `get_pattern()` | Render a pattern by ID |
| `get_option()` / `wp_editor()` / `checked()` | WordPress passthroughs |
| `get_user_ip()` / `get_session_info()` | Visitor info |
| `fix_phone_format()` / `nice_phone_format()` | libphonenumber helpers |

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
- Theme palette parsed from the `--color-*` custom properties in `_variables.scss` and fed to the WordPress editor palette, the ACF color picker and the TinyMCE text-color grid

### Communications
- Email: Twig templates, logging (`mail-log`), SMTP settings, dark-mode-safe (light-locked) template
- SMS: SMS-Fly (REST API v2, API key) / TurboSMS (bearer token) with logging (`sms-log`)
- Telegram bot messaging
- Phone helpers via libphonenumber (`fix_phone_format`, `nice_phone_format`, …)
- "Send test" dashboard widget to verify Email / SMS / Telegram over AJAX
- All three go over HTTPS through the WP HTTP API with certificate verification and a bounded timeout

### SEO & PWA
- Schema.org JSON-LD `@graph` (Organization / WebSite / WebPage / Article), option-driven
- Web manifest generation (icons, colors, short name / description) from the favicon system
- Yoast SEO i18n breadcrumb / title fixes

### Frontend
- Outbound external-link interstitial (opt-in): `/?go=` "you are leaving" countdown page, restricted to `http`/`https` targets
- Headroom.js hide-on-scroll sticky header
- Cookie consent popup (configured in the options framework)
- Test header / footer chrome wired to the Header / Footer ACF options: a 3-column grid (logo · centered menu · socials + CTA) with a sticky footer (body flex column, `main` grows to push the footer down)

### Performance & Optimization
- Timber HTML cache, HTML minification, WEBP converter & big-image resizer

### Admin & Dashboard
- Tabbed dashboard options framework with conditional logic + WP-LOC localization
- Customizable admin menu, header/footer code editors, maintenance mode, lorem-ipsum post generator
- ACF field-group order column, WYSIWYG `delay=0`, encrypt/decrypt helper for connector secrets
- Hides the WordPress 7 admin-bar command palette

### Redirect Rules
- 301/302 URL redirect manager (`redirect-rules` CPT) with dashboard widget, duplicate/self-redirect detection, transient caching, and auto-publish on restore from trash
- An invalid rule is parked as a draft with an admin notice, never deleted

### Security & Cleanup (option-gated toggles, grouped into tabs)
- Disable: all updates, customizer, srcset, default image sizes, core privacy tools, application passwords, DNS prefetch, REST API for anonymous users, emojis, embeds, comments, blog tags, Connectors admin page
- Allow SVG uploads (sanitized); trust proxy IP headers
- CYR2LAT transliteration; allow `.m3u/.m3u8/.ts` uploads; delete child media on parent delete; hide admin top bar / ACF; disable admin email verification; disable default dashboard widgets
- WordPress 7 frontend tuning: block-library styles, global styles, block-support styles, Font Library output + manager, image auto-sizes, speculation rules
- Disable Gutenberg (for blog / everywhere)

### Integrations
- Google Maps API key, Telegram / SMS credentials, geolocation (GeoIP2), hardened session/IP helpers
- WP-LOC multilingual integration (native API, no WPML)
- Localization ready (en, ru, uk translations)

## Build Process

Assets are compiled by **Mini Prepros** — a local (non-public) PhpStorm plugin that reads this project's existing `prepros.config` and runs the whole compile/watch loop inside the IDE, so there is no separate build app to keep open:

- **SCSS** → minified CSS (`assets/scss/` → `assets/css/`)
- **JS** → concatenated & minified (`assets/js/` → `assets/js/*.min.js`)

### Everyday use

Open the project in PhpStorm and press **Start** in the `Mini Prepros` tool window (it can also auto-start on project open — Settings → Mini Prepros). That single action is the whole workflow:

1. It finds the config on its own — `prepros.config`, `prepros-6.config`, `prepros.config.json` or `prepros.json`.
2. It compiles **every** configured rule immediately, in bulk and silently.
3. It then watches sources, SCSS partials, JS directive dependencies and the config itself, recompiling only what a change affects. Balloon notifications appear only for these post-start changes, batched as `Compiled N files`.

Editing `prepros.config` while the watcher runs needs no restart — the new rules are re-read into memory and applied. **The config file is treated as read-only**: Mini Prepros never rewrites, reformats, normalizes or adds rules to it, so a new block's entry has to be added by hand (see [Creating a New Block](#creating-a-new-block), step 4).

Two behaviours worth knowing:

- **SCSS partials (`_name.scss`) are never compiled on their own.** Touching one recompiles every SCSS rule, which is what makes `_variables.scss` edits propagate everywhere.
- **JS `//@prepros-prepend` directives are expanded before minification.** The bundle entry points (`assets/js/custom.js`, `plugins.js`, `dashboard.js`, `gutenberg.js`, `custom-options.js`) are manifests containing nothing but directives; the referenced files are inlined in order, then minified. A bare path resolves relative to the manifest, a leading `/` relative to the project root, and nesting works. `-append`, `-include` and `-import` are supported too.

### Terminal / one-shot builds

The plugin bundles a Node runner that can be invoked directly — useful for CI or a quick rebuild without the IDE:

```bash
node /path/to/mini-prepros/runner/dist/index.js --project . --once
```

`--dry-run` prints the resolved rules and any warnings (for example a config entry whose source file no longer exists) without writing anything; `--watch` is the default and keeps watching.

Mini Prepros intentionally covers only the SCSS/JS compile loop — proxy browsing, browser sync, uploads, exports and image optimization are out of scope.

## License

No public license is declared for this starter theme (`composer.json` uses `"license": "none"`).

## Author

[Vitalii Kaplia](https://vitaliikaplia.com/)
