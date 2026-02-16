**Overview**
This plugin ("B2T Site Mods") provides site-specific extensions for b2ttraining.com, primarily around WooCommerce UX adjustments, email templating, ACF-managed content, file upload rules, and Cloudflare Turnstile validation.

**Versioning And Requirements**
- Plugin version: 1.5.2 (from `b2t-site-mods.php` and `README.md`).
- WordPress: requires at least 6.5.0, tested up to 6.7 (from `README.md` / `readme.txt`).
- PHP: requires 8.2 (from `README.md` / `readme.txt`).

**Entry Points And Bootstrap**
- Main plugin file: `b2t-site-mods.php`.
- Bootstrapping pattern: defines constants and `require_once` loads every PHP file under `lib/fns/` via `scandir()`.
- Constants:
  - `B2TMODS_CSS_DIR`: `css` for local/SCRIPT_DEBUG, `dist` otherwise.
  - `B2TMODS_DEV_ENV`: truthy when `site_url()` contains `.local`.
  - `B2TMODS_PLUGIN_PATH`, `B2TMODS_PLUGIN_URL`: used for paths/URLs.

**Key Dependencies**
- WooCommerce: extensive hooks and a template override.
- ACF Pro: JSON save/load for field groups, and `get_field()` usage.
- Cloudflare Turnstile: registration CAPTCHA validation.
- LightnCandy (Composer): Handlebars-like rendering for HTML templates.
- External/theme-level functions/classes assumed to exist:
  - `truncate_post()` in `lib/fns/woocommerce.php`.
  - `b2t-scripts` script handle enqueued for course products.
  - `Andalu_Woo_Courses_Single` actions referenced for removal.

**Templating System**
- `lib/fns/templates.php` provides `render_template()` using LightnCandy.
- Templates live in `hbs-templates/` and compile to `hbs-templates/compiled/`.
- Compilation happens at runtime if compiled template is missing or older than `.hbs`.
- `hbs-templates/email.hbs` appears generated from the MJML source in `mjml-templates/`.
- `hbs-templates/alert.hbs` defines an Elementor-style alert component.

**Email Customization**
- `lib/fns/emails.php` filters `wp_mail` to wrap plain emails in the HTML template.
- Ensures `Content-Type: text/html` is set in headers if not already.
- Skips templating for SolidWP (iThemes Security Pro) emails via `debug_backtrace()`.
- Customizes password change notifications:
  - Admin notice via `wp_password_change_notification_email`.
  - User notice via `password_change_email`.
  - Forces user password change email to send (`send_password_change_email` filter).

**Cloudflare Turnstile**
- `lib/fns/cloudflare-turnstile.php`:
  - Enqueues the Turnstile JS only on WooCommerce account page and when site key is defined.
  - Adds server-side validation on `woocommerce_register_post` with the secret key.
  - Uses `$_SERVER['REMOTE_ADDR']` as the remote IP for verification.
- Template injection for the widget is in the WooCommerce login/register override template.

**WooCommerce Customizations**
- Template override:
  - `lib/fns/woocommerce.php` registers `woocommerce_locate_template`.
  - Custom template at `woocommerce/templates/myaccount/form-login.php` (version 9.2.0).
- UI and behavior tweaks:
  - Removes breadcrumbs (`woocommerce_before_main_content`).
  - Hides Downloads tab in My Account menu.
  - Reorders and relabels My Account orders columns, adds custom "Order/Items" column.
  - Outputs line items and quantities for each order in My Account.
  - Reverses review display order on product pages.
  - Removes product image lightbox support.
  - Adds custom login/register form footer notes (see ACF options below).
- Shop page category listing:
  - Rebuilds category list on the main shop page via `woocommerce_product_loop_start`.
  - Excludes "uncategorized" and uses category thumbnails.
- Course product view:
  - For products with `product_type` of `course`, modifies the single product page layout.
  - Removes several actions (images, title, data tabs, custom tables).
  - Adds a related posts section using `related_posts` meta (likely an ACF repeater).
  - Logs to `error_log()` during related posts resolution.
- The `pre_get_posts` filter to exclude categories exists but is commented out.

**ACF Integration**
- `lib/fns/acf.php`:
  - Saves and loads ACF JSON to `lib/acf-json/`.
  - Explicitly targets field groups `group_63f2a2beb826b` and `group_641e00728ebd7`.
- Field groups:
  - `Download` (group_63f2a2beb826b): "Description" and "URL" fields on `post` post type.
  - `B2T Text Fields` (group_641e00728ebd7): options page `b2t-text-field-options` with:
    - WooCommerce Log In group: "Log In Form End" and "Register Form End".
    - My Account group: "Certification Program Tab" and "Class Exams Tab".
    - Class Registration group: "Shipping Address Note".

**File Type Handling**
- `lib/fns/filetypes.php`:
  - Allows `.xlsx` and `.xls` uploads (`upload_mimes`, `mime_types`).
  - Adds a Media Library column showing MIME type for each attachment.

**Tooling And Build Notes**
- `Gruntfile.js` includes tasks for:
  - Generating a POT file (`grunt-wp-i18n`).
  - Converting `readme.txt` to `README.md`.
- `package.json` defines `npm run i18n`, `npm run readme`, `npm run start`.
- `composer.json` declares LightnCandy; autoload points to `src/` (no `src/` exists).

**Notable Observations**
- Namespacing in `lib/fns/woocommerce.php` is `b2tmods\\woocommere` (spelling is consistent in-file but nonstandard).
- The plugin defines `B2TMODS_CSS_DIR` and `B2TMODS_DEV_ENV`, but no CSS assets are present here.
- Some behavior relies on theme or other plugin functions/classes not included in this repo.
