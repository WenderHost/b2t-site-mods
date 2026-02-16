**Project Summary**
This is a site-specific WordPress plugin for b2ttraining.com. It layers WooCommerce UI tweaks, email templating, ACF-managed content, file upload rules, and Cloudflare Turnstile validation on top of core WP and WooCommerce.

**Primary Entry Points**
- `b2t-site-mods.php` defines constants and auto-loads every file in `lib/fns/`.
- Feature code lives in `lib/fns/*.php` (each file is namespaced).
- WooCommerce template overrides live in `woocommerce/templates/`.

**Major Feature Areas**
- Email HTML templating via Handlebars + LightnCandy (`lib/fns/emails.php`, `lib/fns/templates.php`, `hbs-templates/`).
- WooCommerce layout and UX changes (`lib/fns/woocommerce.php`, `woocommerce/templates/`).
- Cloudflare Turnstile validation on registration (`lib/fns/cloudflare-turnstile.php`).
- ACF JSON sync and options-driven content (`lib/fns/acf.php`, `lib/acf-json/`).
- File upload MIME tweaks for Excel (`lib/fns/filetypes.php`).

**Runtime Dependencies And Assumptions**
- WooCommerce is active.
- ACF Pro is active (options page `b2t-text-field-options` must exist).
- Cloudflare Turnstile constants in `wp-config.php`:
  - `CLOUDFLARE_TURNSTILE_SITE_KEY`
  - `CLOUDFLARE_TURNSTILE_SECRET_KEY`
- LightnCandy is installed (Composer `vendor/` is committed here).
- Theme or other plugins provide:
  - `truncate_post()` function
  - `b2t-scripts` script handle
  - `Andalu_Woo_Courses_Single` actions referenced for removal

**Templating Workflow**
- Edit `hbs-templates/*.hbs` for Handlebars templates.
- `hbs-templates/compiled/*.php` is generated; do not hand-edit.
- MJML sources in `mjml-templates/` are the source for `email.hbs`.

**WooCommerce Template Overrides**
- The plugin adds a template search path via `woocommerce_locate_template`.
- Place overrides in `woocommerce/templates/` using WooCommerce-relative paths.

**ACF JSON**
- JSON is saved/loaded from `lib/acf-json/`.
- Only the field groups listed in `lib/fns/acf.php` are targeted by the save/load filters.

**Tooling**
- `npm run start`: runs Grunt default (i18n + readme).
- `npm run i18n`: generates POT via `grunt-wp-i18n`.
- `npm run readme`: converts `readme.txt` to `README.md`.

**Conventions**
- Keep feature code in `lib/fns/` and rely on auto-loading via `scandir()`.
- Avoid editing `vendor/` unless updating Composer dependencies.
- Keep namespacing consistent within each file (some namespaces are legacy).
