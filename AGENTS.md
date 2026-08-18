# AGENTS.md

## Project Overview

WordPress Base Template (wp-base) — custom starter theme built with Timber/Twig, ACF Gutenberg blocks, and a custom dashboard options framework. Public demo: https://wp-base.kaplia.top/

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
- WP-LOC for multilingual projects (not WPML)
- ACF Pro for Gutenberg blocks and custom fields
- Mini Prepros (PhpStorm plugin) for SCSS/JS compilation — not webpack/vite
- Composer (vendor directory: `core/vendor/`)
- jQuery 3.7.1 is bundled in `assets/js/jquery.min.js` and replaces the WordPress copy on the frontend

## Multilingual — WP-LOC only

The theme targets **WP-LOC**. WPML support was removed completely; do not reintroduce it.

**Never call `icl_*` / `wpml_*` / `$sitepress` / `ICL_LANGUAGE_CODE` from theme code.** WP-LOC provides those shims, but they exist for third-party plugins, not for us. Every multilingual need goes through `core/includes/back/system-multilingual.php`:

| Helper | Use it for |
|---|---|
| `theme_is_multilingual()` | guard anything multilingual-only |
| `theme_current_language()` | current language **slug** (`ua`) |
| `theme_current_locale()` | current **locale** (`uk`) |
| `theme_context_language()` | admin lists and pickers — admin language in wp-admin, frontend language elsewhere |
| `theme_default_language()` | the no-prefix language |
| `theme_languages()` | switcher entries; empty on single-language sites |
| `theme_translated_post_id()` / `theme_translated_id()` | resolve an ID into another language |
| `theme_translated_ids()` / `theme_attachment_ids()` | every language sibling of an element, for writing a value that belongs to the FILE rather than to the translation |
| `theme_post_language()` / `theme_term_language()` | which language an object belongs to |
| `theme_attachment_meta()` | attachment meta with a default-language fallback |
| `theme_switch_language()` / `theme_restore_language()` | background/transactional rendering in another language |

Rules that are easy to get wrong:

- **Slug ≠ language code ≠ locale.** Ukrainian is slug `ua`, code `uk`, locale `uk`. The helpers return slugs. Never build an option name from a slug by hand — WP-LOC owns option suffixing and resolves both forms.
- **`LANG_SUFFIX` is for transients only.** It namespaces this theme's cache keys (`general_fields_ua`). Any new per-language cache must include it.
- **Do not re-implement localized options.** Flag the field `'localize' => true`; `system-multilingual.php` registers it via `wp_loc_multilingual_options` on `init`. The theme previously ran its own `pre_option` + `$wpdb` filter — that was removed as duplicate work, do not bring it back.
- **Every helper must degrade.** The theme is a starter and most projects are single-language, so helpers return sensible single-language values with no plugin installed. Keep new helpers guarded the same way; call sites should not need `function_exists()`.
- **Meta that describes the FILE goes on every sibling.** WP-LOC gives each language its own attachment record over one physical file, so `webp_url` / `avif_url` are not per-language values. Write them through `set_shadow_image_meta()`, which fans out over `theme_attachment_ids()`; read them through `theme_attachment_meta()`. Writing only the uploaded record leaves every other language serving the heavy original while the shadow sits unused on disk.
- **Objects with no registered language stay visible.** When filtering an admin list by language, treat "no language" as "show it" — otherwise single-language sites and pre-WP-LOC content vanish.
- **Do not duplicate the plugin.** hreflang, `<html lang>`, canonical URLs, query filtering, routing and the admin-bar switcher are WP-LOC's job.
- WP-LOC's ACF `nav_menu` filter works on `$field['choices']`. The theme's own `nav_menu` field builds its list in `render_field()`, so it filters by language itself — if you rework that field, keep the filter.

## Translations — REQUIRED whenever you add a user-facing string

Every `__()` / `_e()` / `_n()` string is written in English in the source and translated in `core/lang/`. **Adding a translatable string is not finished until the catalogs are updated and recompiled** — otherwise the admin shows a stray English label next to Ukrainian ones.

Workflow:

1. Extract with `xgettext`, not `wp i18n make-pot`. The theme passes the `TEXTDOMAIN` **constant** as the domain argument, which WP-CLI cannot resolve — `make-pot` silently returns almost nothing.

   ```bash
   find . -name "*.php" -not -path "./core/vendor/*" -not -path "./.git/*" | sort > /tmp/files.txt
   xgettext --from-code=UTF-8 --language=PHP \
     -k__ -k_e -k_n:1,2 -kesc_html__ -kesc_attr__ -kesc_html_e -kesc_attr_e \
     -f /tmp/files.txt -o /tmp/theme.pot
   ```

2. Diff the POT's msgids against `core/lang/uk.po` and add **only the genuinely new ones**. Do not run `msgmerge --update` on these files: it re-sorts every entry and rewrites all `#:` references, producing a ~2000-line diff over a handful of real changes.

3. **A wrapped `msgstr ""` is not an empty translation.** gettext wraps long values as

   ```
   msgid "Email address that will be used as sender"
   msgstr ""
   "Адреса електронної пошти, яка буде використовуватися…"
   ```

   Matching on `msgstr ""` alone treats these as untranslated and clobbers them. Use `msgattrib --untranslated` to find genuinely empty entries.

4. Translate into `uk.po` and `ru_RU.po`. `en_US.po` keeps empty `msgstr` values — the source strings are already English and gettext falls back to the msgid.

5. Plural strings need all three Slavic forms (`msgstr[0..2]`).

6. Recompile, and let `--check` validate plural forms and printf placeholders:

   ```bash
   for l in uk ru_RU en_US; do msgfmt --check -o core/lang/$l.mo core/lang/$l.po; done
   ```

7. Commit the `.po` **and** the `.mo` — WordPress reads the `.mo`.

No `.pot` file is kept in the repo; generate it in a temp directory when needed.

## Key Constants (core/init.php)

- `TEXTDOMAIN` = `'wp-base'` — always use this for translations: `__("Label", TEXTDOMAIN)`
- `LANG_SUFFIX` — current language slug prefixed with `_` (e.g. `"_ua"`), or the WP locale with no plugin. **Transient namespacing only** — never build option names with it, see [Multilingual](#multilingual--wp-loc-only)
- `BLOGINFO_LANGUAGE` — current language as a WP locale (`uk`, `en_US`)
- `PAGE_ON_FRONT` / `PAGE_FOR_POSTS` — plain `get_option()`; WP-LOC localizes both itself
- `TIMBER_VIEWS` = `'views'`
- `JQUERY_VERSION` — version string for the bundled jQuery; bump it together with the file
- `SVG_SPRITE_URL` — URL to SVG sprite with cache-busting version
- `TRUST_PROXY_HEADERS` — optional, define in `wp-config.php` to override the dashboard toggle

## Project Structure

- `functions.php` — only loads `core/init.php`
- `core/init.php` — constants, helpers, bootstrapping, auto-loads all PHP from `includes/`, `cache/`, `ajax/`
- `core/gutenberg.php` — block registration, categories, rendering
- `core/acf.php` — ACF configuration
- `core/debugging.php` — `write_log()` and the `pr()` dump helper (both no-ops unless `WP_DEBUG`)
- `core/encrypt-decrypt.php` — authenticated AES-256-CBC helper for connector secrets
- `core/includes/back/svg-sanitizer.php` — shared SVG sanitizer (auto-loaded with the rest of `includes/`)
- `core/includes/back/system-multilingual.php` — the entire WP-LOC integration surface
- `core/acf-json/` — ACF field group JSON files (auto-sync)
- `core/includes/back/` — admin logic (options, CPTs, taxonomies, system features)
- `core/includes/front/` — frontend helpers
- `views/` — all Twig templates
- `assets/scss/` → `assets/css/` — compiled by Mini Prepros

Files dropped into `core/includes/`, `core/cache/` or `core/ajax/` are `require_once`-d automatically — no registration step. Everything is required before any hook fires, so definition order between those files never matters.

## Security Rules (read before touching anything user-facing)

These are not aspirational; the code currently upholds them and a change that breaks one is a regression.

### Escaping — Twig autoescaping is OFF

Timber v2 defaults to `autoescape => false`, and this theme keeps it that way (menus, `wp_head()`, block HTML and pattern output all have to pass through raw). Escaping is therefore **explicit and manual**:

| Context | Use |
|---|---|
| `href` / `src` | `\|esc_url` |
| any other attribute | `\|esc_attr` |
| text that must not carry markup | `\|esc_html` |
| inline `<style>` value | `\|css_value` |
| inside a `<script>` string | `\|esc_js` |
| untrusted rich text | `\|wp_kses_post` |

When adding a block template, every field that lands in an attribute needs one of these. Text nodes are left raw on purpose so editors can use inline markup.

**Timber already registers all the WordPress escapers** (`esc_url`, `esc_attr`, `esc_html`, `esc_js`, `wp_kses`, `wp_kses_post`) along with `shortcodes`, `wpautop`, `resize`, `excerpt` and more — see `core/vendor/timber/timber/src/Twig.php`. Re-registering any of them in `add_to_twig()` makes Twig throw `LogicException: Filter "…" is already registered` and takes down **every** page. Before adding a filter there, check that list. `css_value` is the only escaper this theme adds, because Timber has no CSS-context equivalent.

### Capabilities and nonces

Every AJAX handler, `admin_post_` action and admin form POST checks **both**:

```php
if(!current_user_can('manage_options')){ wp_send_json_error(..., 403); }
check_ajax_referer('action_name', 'nonce');
```

The dashboard options pages go through the WordPress Settings API (`settings_fields()` + `options.php`), which supplies this automatically — do not hand-roll a save handler for them.

### Options are sanitized on save

`custom_option_sanitize_callback()` in `dashboard-options.php` maps each field type to a sanitizer and is wired through `register_setting()`. `code` and `mce` are deliberately exempt — those fields exist to hold custom HTML/JS. If you add a field type, add its case there too.

### SVG is never trusted

`core/includes/back/svg-sanitizer.php` is the single source of truth. `sanitize_svg_markup()` / `sanitize_svg_file()` / `sanitize_svg_node()` are used by the upload filter, the inline `|svg` renderer and the icon-sprite importer. It strips `script`/`foreignObject`/`iframe`/`object`/`embed`, `on*` attributes, non-http(s) URI schemes (entity- and whitespace-obfuscated `javascript:` included), dangerous `style` bodies, and SMIL that animates an `href`; it rejects documents declaring XML entities. Uploads are additionally gated behind the `allow_svg_upload` option and are **off by default**.

Do not add a second, ad-hoc SVG cleaner. Extend the shared one.

### Raw-HTML fields

The iFrame block renders `fields.html_code` with `|raw`. `core/includes/back/acf-filter-raw-html.php` filters that value through `wp_kses` on save for users without `unfiltered_html`. Any new field rendered with `|raw` must be added to `raw_html_field_keys()`.

### Visitor IP

`get_user_ip()` returns `REMOTE_ADDR` unless `trust_proxy_headers()` is true. Never read `HTTP_X_FORWARDED_FOR` / `HTTP_CF_CONNECTING_IP` directly.

### Stored secrets

Use `custom_encrypt()` / `custom_decrypt()` (`core/encrypt-decrypt.php`). AES-256-CBC, a **random IV per message**, encrypt-then-MAC, output prefixed `v2:`. Do not reintroduce a fixed IV or drop the HMAC — a tampered ciphertext must fail closed, not decrypt to garbage. `custom_decrypt_legacy()` reads values from the old static-IV format; keep it until every stored secret has been re-saved.

### Logged email bodies are untrusted

A `mail-log` post contains whatever a visitor typed into a form. The preview route sends `Content-Security-Policy: sandbox` + `X-Content-Type-Options: nosniff`, and the meta box embeds it with `sandbox=""`. Both are load-bearing — dropping either turns a contact-form submission into stored XSS in an admin session.

### Never destroy user data on a validation failure

`save_redirect_rules()` used to `wp_delete_post($post_id, true)` whenever a rule failed validation, so one typo permanently deleted the rule being edited. It now calls `reject_redirect_rule()`: values are kept, the post is parked as a `draft` (only published rules are served), and the reason surfaces as an admin notice. Apply the same shape anywhere else validation runs on `save_post`.

### Outbound HTTP

Use `wp_remote_get()` / `wp_remote_post()` with an explicit `timeout`, never raw curl or `file_get_contents()`. Never disable `sslverify`.

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
4. Add an entry to `prepros.config` → `config.files` **by hand** — Mini Prepros reads that file and never writes to it
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
            'type'  => 'text|textarea|number|password|range|select|select-multiple|checkbox|color|code|mce|link|nav-menu|regenerate_images',
            'name'  => 'option_name',
            'label' => __('Label', TEXTDOMAIN),
        ],
    ],
]
```

### Tabs

Use `tab_start` / `tab_end` fields to group options into tabs.

### Defaults

A `range` field must declare `'default' => '60'` whenever its PHP side has a fallback. A range input always posts a value: rendered with an empty `value` the browser parks the thumb at the midpoint of min/max, so the first save of that tab writes ~50 over whatever the PHP fallback was. The template only substitutes the default when nothing is stored — a deliberate `0` survives.

### Action fields

`regenerate_images` is not a setting — it renders a button and stores nothing. It is skipped when settings are registered (alongside `tab_start` / `tab_end`), so it never creates an option. Its `name` picks the format: `regenerate_webp` / `regenerate_avif`.

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
- Communications: `system-send-email.php` / `system-send-sms.php` (SMS-Fly REST API v2, TurboSMS) / `system-telegram.php`, `core/includes/front/phone-functions.php` (libphonenumber), and the "Send test" widget (`dashboard-widget-send-test.php`). Logs: `mail-log` / `sms-log` CPTs. There is no `?send_test_email=1` trigger any more — use the widget.
- SEO / PWA: `core/includes/front/schema.php` (JSON-LD, option-driven), `system-favicon.php` (icons + web manifest), `core/includes/front/wpseo-fix.php` (Yoast i18n).
- Render helpers: `core/includes/front/render-picture-tag.php` (`picture`/`picture_eager`/`picture_src`) and `render-svg-tag.php` (`svg`), registered in `core/timber.php`.
- Shadow images (WEBP / AVIF): `core/includes/back/system-resize-images.php` converts on upload and `core/ajax/regenerate-images.php` re-encodes the library in batches from the options page. See [Shadow images](#shadow-images--webp--avif).
- External-link interstitial: `core/includes/front/external-links.php` (+ `views/external-link.twig`), opt-in, `http`/`https` targets only.
- Colours: `core/includes/back/system-color-presets.php` parses `--color-*` tokens → editor / ACF / TinyMCE palettes.
- Multilingual: `core/includes/back/system-multilingual.php` (WP-LOC helpers, localized-option registration, language switching); switcher markup in `views/overall/header.twig` + `_header.scss`; WP-LOC admin-bar switcher styling in `dashboard.scss`.
- Misc helpers: `core/encrypt-decrypt.php` (openssl), `core/includes/back/acf-order-column.php`, `acf-wysiwyg-delay.php`.

Prefix convention: new code is **unprefixed** (matches the base's existing global functions), not `insight_`/`skyta_`/`hw_`.

## Shadow images — WEBP / AVIF

Each original keeps optional shadow copies beside it, named `{filename}-{ext}.{webp|avif}` so `photo.png` and `photo.jpg` never collide. Both formats are option-gated, independent of each other, and off by default.

| Option | Meaning |
|---|---|
| `enable_webp_convert` / `enable_avif_convert` | the two toggles; either, both or neither |
| `webp_convert_quality` | WEBP quality (fallback 90) |
| `avif_convert_quality_photo` | AVIF quality for JPEG sources (fallback 60) |
| `avif_convert_quality_graphics` | AVIF quality for PNG / GIF sources (fallback 55) |

Per attachment the converters store `webp_path` / `webp_url` and `avif_path` / `avif_url`. Read them with `theme_attachment_meta()`, never with a bare `get_post_meta()`.

- **Ladder order is AVIF, then WEBP, then `<img>`.** The browser takes the first `<source>` type it understands and never looks further, so whatever AVIF points at is what every modern browser downloads. Both `picture-tag.twig` and the `the_content` wrapper in `system-resize-images.php` follow this order.
- **`| picture_src` stays WEBP on purpose.** It returns a bare URL for CSS backgrounds, `og:image` and hand-written `src` — contexts with no fallback rung — so the URL has to be one every browser can decode. Use `| picture` wherever markup allows.
- **Two Imagick quality setters, always.** WEBP reads `setImageCompressionQuality()` and ignores `setCompressionQuality()`; AVIF is the other way round. Drop the second and every AVIF quality produces a byte-identical file — measured here: 80 741 B at both q40 and q80, against 24 972 B / 439 478 B once both setters are called. `set_image_encoder_quality()` calls both; never bypass it.
- **AVIF quality is split photo/graphics.** Flat graphics compress roughly twice as hard as photographs at the same visual fidelity, so one number would have to be set for the worst case. Defaults: 60 photo, 55 graphics — declared both as the PHP fallback in `avif_quality_for()` and as the field's `'default'`, which have to stay in step (see [Defaults](#defaults)).
- **The theme's AVIF quality also governs resized derivatives** through a `wp_editor_set_quality` filter. Without it WordPress cuts them at its own default of 82, and since AVIF is the first rung that is the difference between the ladder helping and hurting — on one 768px cut, 2 920 B at q55 versus WEBP's 3 224 B, but 4 829 B at q82 versus 4 312 B. WEBP derivatives are deliberately left on WordPress's default so existing projects do not silently re-cut.
- **The AVIF rung is dropped when a resized AVIF could not be produced.** `ImageHelper::_operate()` returns the source URL unchanged on failure, and whether Imagick can resize AVIF is a property of the server build — so `render_picture_tag()` compares the result against the input and omits the rung rather than advertising a full-size file where a cut-down one was asked for.
- **Known limitation:** a resized derivative is cut from the shadow, not from the original, so it is encoded twice. Full-size AVIF measures 46–58 % lighter than WEBP; at resized widths the two are roughly level. Fixing it means cutting every width from the original, which is a larger change than this subsystem.
- **Regeneration runs in batches** of 10 attachments per request (`regenerate_images_batch_size` filter), so a large library never hits `max_execution_time`. On a multilingual site it converts only the default-language record of each file and skips the duplicates — one file, one encode — while the meta write still covers every sibling.
- **Meta is written to every language sibling** — see [Multilingual](#multilingual--wp-loc-only).

## SCSS Conventions

- Pull partials in with `@use`, never `@import`. `@import` is reserved for genuinely external CSS (e.g. the web-font URL in `_fonts.scss`). `@use` rules must come before any other rule in a file (see `gutenberg.scss`).
- No SCSS mixins. Use native CSS properties and let the build's autoprefixer add vendor prefixes; the legacy `_mixins.scss` prefix helpers were removed.
- Block SCSS files need no import — each only styles `.{category}-{block-name}` and is compiled standalone.
- Mobile breakpoint: `@media (max-width: 768px)`
- CSS units: prefer `px` and `%`; `vw`, `vh`, and `clamp()` are allowed for fluid sizing. Avoid `em`/`rem` unless explicitly required.
- Button classes: `.btn`, `.btn.primary`, `.btn.secondary`

## Build — Mini Prepros

Mini Prepros is a **local, non-public PhpStorm plugin** (plus a bundled Node runner) that replaces the compile/watch half of the Prepros desktop app. It reads this project's existing `prepros.config` — the config format did not change.

What matters when working in this repo:

- **One button does everything.** Start in the `Mini Prepros` tool window (or enable auto-start on project open) compiles every configured rule in bulk, then keeps watching. There is no per-file build command.
- **`prepros.config` is read-only to the tool.** It is never rewritten, reformatted or auto-extended. A new SCSS/JS output only exists once you add its entry by hand.
- **Config edits apply live.** The running watcher re-reads the config in memory; no stop/start.
- **SCSS partials are never compiled directly.** A change to any `_partial.scss` recompiles all SCSS rules.
- **JS entry files are directive manifests.** `assets/js/custom.js` and friends contain only `//@prepros-prepend` lines; the runner expands them (recursively, bare paths relative to the manifest, leading `/` relative to the project root) *before* minifying. Editing a file under `assets/js/custom/` recompiles the bundle that includes it.
- **Never hand-edit `assets/css/*.min.css` or `assets/js/*.min.js`.** Edit the source and recompile.

Block entry format in `prepros.config` → `config.files`:
```json
{
    "file": "assets/scss/blocks/main/blockname.scss",
    "config": {
        "customOutput": "assets/css/blocks/main/blockname.min.css",
        "tasks": { "minify-css": { "enable": true } }
    }
}
```

For a terminal rebuild (CI, or when PhpStorm is not open):

```bash
node /path/to/mini-prepros/runner/dist/index.js --project . --once
```

Use `--dry-run` first to see the resolved rules and any warnings (a config entry pointing at a deleted source shows up here). Compile-writing runs touch tracked files — treat them like any other write to the repo.

## Communication

- User communicates in Ukrainian
- Commit messages in English
- Code comments in Ukrainian (for inline) or English (for documentation)
