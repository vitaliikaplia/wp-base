# WordPress Base Template

This is a clean custom WordPress starter theme developed for building modern and fully customizable WordPress websites from scratch. It includes a clean structure, essential files, and integrations to kickstart your development process.

## Features

- **Fully integrated with Timber v2.3.0:** Use the power of Twig templating engine to build your WordPress theme.
- **Custom Structure:** Organized into `assets`, `core`, and `views` directories for better maintainability.
- **Composer Integration:** Manage dependencies with Composer (`composer.json` and `composer.lock` included).
- **Prepros Config:** Ready for use with Prepros for compiling assets.
- **Custom Templates:** Includes default WordPress template files (`404.php`, `archive.php`, `author.php`, `single.php`, etc.).
- **Modern Design Preview:** Includes a `screenshot.png` for WordPress theme preview.

## Folder Structure

```
wp-base/
├── .gitignore
├── 404.php
├── archive.php
├── author.php
├── composer.json
├── composer.lock
├── footer.php
├── functions.php
├── header.php
├── index.php
├── page.php
├── prepros.config
├── screenshot.png
├── search.php
├── single.php
├── style.css
├── assets/            # Assets like CSS, JS, and images
├── core/              # Core theme functionalities
├── views/             # Twig template parts
```

## Features, tools and options

- Header fields options
- Footer fields options
- Cookies popup
- Favorite colors
- WEBP image converter & big images resizer
- Timber HTML cache
- HTML minify
- Enhanced mail sending logic with logging and Twig templates for emails.
- SMTP settings
- Header & Footer html code editor
- Maintenance mode feature
- Custom Gutenberg blocks: easy to add new ACF blocks and block categories with custom fields and twig templates
- A feature to parse all pages as Gutenberg blocks.
- Automatic loading of block styles on the front end when a block is used.
- `get_pattern()` helper function for easily including modular templates.
- Custom options framework with different field types allows to create custom options pages and fields
- Conditional logic for custom options fields.
- Geolocation features
- Disable all updates feature
- Disable customizer feature
- Disable src set feature
- Remove default image sizes feature
- Disable core privacy tools feature
- CYR3LAT feature (transliteration of cyrillic characters in slugs and filenames)
- Disable DNS prefetch feature
- Disable Rest API for anonymous users feature
- Disable WordPress Emojis feature
- Disable Embeds feature
- Disable default dashboard widgets feature
- Customizable WordPress dashboard menu.
- Hide admin top bar for all users on front-end feature
- Disable default WordPress admin email verification feature
- Disable comments feature for all post types
- Delete child media files on parent post delete feature
- Hide ACF from menu feature
- Disable Gutenberg editor everywhere feature
- Disable Gutenberg editor for Blog posts feature
- Google maps API key option (for ACF)
- Lorem ipsum posts generator tool
- Localization ready (with provided `.po` and `.mo` files).
- and more, and more...

## Requires

- PHP 8.0 or higher.
- WordPress 6.7 or higher.
- [Advanced Custom Fields Pro](https://www.advancedcustomfields.com/pro/)
- Composer
- [Prepros](https://prepros.io/) (to compile css and js files)

## Getting Started

1. Open `functions.php` to configure theme-specific settings.
2. Modify files under `views/` for template parts and layouts.
3. Use the `assets/` directory to manage your CSS, JavaScript, and images.
4. Replace `screenshot.png` with your custom theme preview image.
5. And remember: **Code is Poetry**. 

## License

This theme is licensed under the [GNU General Public License v2 or later](https://www.gnu.org/licenses/gpl-2.0.html). You are free to modify and redistribute this theme as long as you comply with the GPL.

## Contributions

Contributions are welcome! Feel free to fork this repository and submit a pull request with improvements or bug fixes.

Author: [Vitalii Kaplia](https://vitaliikaplia.com/)