# WordPress Base Template

wp-base is the starter theme every new client site begins from. A project starts as a copy of it and then lives in its own repository, so this one stays a clean, general-purpose base.

What it gives you out of the box:

- Twig templates (Timber v2) and Gutenberg blocks built on ACF Pro
- A settings framework for the WordPress dashboard: tabs, conditional fields, per-language values
- Ready-made features that stay off until you switch them on: SVG icon manager, email / SMS / Telegram with logs, Schema.org markup, WEBP and AVIF images, redirects, security and cleanup toggles
- Multilingual sites through WP-LOC; a single-language site needs nothing extra

Live demo: [wp-base.kaplia.top](https://wp-base.kaplia.top/)

## Getting started

You need PHP 8.3+, WordPress 6.7+ and ACF Pro.

```bash
composer install
```

Activate the theme, then configure it from its settings pages in the dashboard. Styles and scripts are compiled with Mini Prepros from `prepros.config`.

## Documentation

Conventions, architecture, decisions and open tasks are kept in the project's shared Memo (project #22), not in this repository. AI agents start from [AGENTS.md](AGENTS.md).

## License

No public license is declared (`"license": "none"` in `composer.json`).

## Author

[Vitalii Kaplia](https://vitaliikaplia.com/)
