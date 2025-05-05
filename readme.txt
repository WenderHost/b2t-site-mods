=== B2T Site Mods ===
Contributors: TheWebist
Tags: comments, spam
Requires at least: 6.5.0
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 1.4.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Various extensions to the B2T Training website.

== Description ==

Long description coming at some point in the future when I get around to it.

== Changelog ==

= 1.4.5 =
* Don't apply template to SolidWP emails.

= 1.4.4 =
* Correcting logo reference in MJML template (i.e. using `{{logo}}` instead of full path to logo).

= 1.4.3 =
* BUGFIX: Recompiling `email.hbs` so dynamic logo insertion is added to compiled template.

= 1.4.2 =
* Updating `custom_password_change_email()` to reference the passed `$user` as an array.
* Removing the signature from the email copy in `password_change_email_content()`.

= 1.4.1 =
* BUGFIX: Setting `{{logo}}` variable inside email template.
* Only setting last word of email heading to B2T orange if exactly two words in the heading.

= 1.4.0 =
* Adding global email template.

= 1.3.0 =
* Now pulling My Account text from ACF Options Page fields.
* Updating spelling of "Log In".
* Initial setup for Handlebars templating.

= 1.2.0 =
* Adding Excel files to allowed file types.

= 1.1.1 =
* Adjusting the placement of the Login form note.

= 1.1.0 =
* Modifying text on the WooCommerce My Account Login and Register forms.

= 1.0.0 =
* First release.
