<?php

namespace b2tmods\cloudflareturnstile;

/**
 * Enqueues the Cloudflare Turnstile JavaScript on the WooCommerce account page.
 *
 * Loads the Turnstile client-side script from Cloudflare only when the
 * `CLOUDFLARE_TURNSTILE_SITE_KEY` constant is defined and the current
 * request is for the WooCommerce account page.
 *
 * @since 1.0.0
 *
 * @return void
 */
function enqueue_turnstile_script() {
  if ( defined( 'CLOUDFLARE_TURNSTILE_SITE_KEY' ) && CLOUDFLARE_TURNSTILE_SITE_KEY && is_account_page() ) {
    wp_enqueue_script(
      'cf-turnstile',
      'https://challenges.cloudflare.com/turnstile/v0/api.js',
      [],
      null,
      true
    );
  }
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_turnstile_script' );

/**
 * Validates the Cloudflare Turnstile CAPTCHA during WooCommerce registration.
 *
 * This function checks for the presence of a Turnstile response token
 * in the registration form and verifies it against Cloudflare's API
 * using the secret key defined in wp-config.php. If the token is missing
 * or invalid, an error is added to the WooCommerce validation errors.
 *
 * @since 1.0.0
 *
 * @param string    $username           The entered username.
 * @param string    $email              The entered email address.
 * @param WP_Error  $validation_errors  Object to which validation errors are added.
 *
 * @return void
 */
function validate_turnstile_on_register( $username, $email, $validation_errors ) {
  // Skip validation if no constant
  if ( ! defined( 'CLOUDFLARE_TURNSTILE_SECRET_KEY' ) || ! CLOUDFLARE_TURNSTILE_SECRET_KEY ) {
    return;
  }

  if ( empty( $_POST['cf-turnstile-response'] ) ) {
    $validation_errors->add( 'turnstile_error', __( 'Please complete the CAPTCHA.', 'woocommerce' ) );
    return;
  }

  $response = sanitize_text_field( $_POST['cf-turnstile-response'] );

  $remote_post = wp_remote_post(
    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    [
      'body' => [
        'secret'   => CLOUDFLARE_TURNSTILE_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'],
      ],
    ]
  );

  $success = false;

  if ( ! is_wp_error( $remote_post ) ) {
    $decoded = json_decode( wp_remote_retrieve_body( $remote_post ), true );
    $success = ! empty( $decoded['success'] );
  }

  if ( ! $success ) {
    $validation_errors->add( 'turnstile_error', __( 'CAPTCHA verification failed. Please try again.', 'woocommerce' ) );
  }
}
add_action( 'woocommerce_register_post', __NAMESPACE__ . '\\validate_turnstile_on_register', 10, 3 );
