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
      array(),
      null,
      array(
        'in_footer' => true,
        'strategy'  => 'defer',
      )
    );

    $inline_js = <<<JS
(function() {
  function setDisabled(isDisabled) {
    var btn = document.querySelector('form.woocommerce-form-register button[type="submit"], form.register button[type="submit"]');
    if (btn) {
      btn.disabled = !!isDisabled;
    }
  }

  // Start disabled until Turnstile gives us a token.
  document.addEventListener('DOMContentLoaded', function() {
    setDisabled(true);
  });

  // Turnstile callbacks (wired via data-* attributes on the widget div).
  window.b2tTurnstileOnSuccess = function(token) {
    setDisabled(false);
  };

  window.b2tTurnstileOnError = function() {
    setDisabled(true);
  };

  window.b2tTurnstileOnExpired = function() {
    setDisabled(true);
  };
})();
JS;

    wp_add_inline_script( 'cf-turnstile', $inline_js, 'after' );
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
  if ( ! defined( 'CLOUDFLARE_TURNSTILE_SECRET_KEY' ) || ! CLOUDFLARE_TURNSTILE_SECRET_KEY ) {
    return;
  }

  if ( empty( $_POST['cf-turnstile-response'] ) ) {
    // This usually means Turnstile never ran / was blocked / or submit happened too fast.
    $validation_errors->add(
      'turnstile_error',
      __( 'Please complete the anti-spam check and try again. If this keeps happening, disable ad blockers/privacy extensions for this site.', 'woocommerce' )
    );
    return;
  }

  $response = sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) );

  $body = array(
    'secret'   => CLOUDFLARE_TURNSTILE_SECRET_KEY,
    'response' => $response,
  );

  // REMOTE_ADDR can be missing or unhelpful behind proxies; it's optional for Turnstile.
  if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
    $body['remoteip'] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
  }

  $remote_post = wp_remote_post(
    'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    array(
      'timeout' => 10,
      'body'    => $body,
    )
  );

  if ( is_wp_error( $remote_post ) ) {
    error_log( 'Turnstile verify wp_error: ' . $remote_post->get_error_message() );
    $validation_errors->add( 'turnstile_error', __( 'Anti-spam verification could not be completed. Please try again.', 'woocommerce' ) );
    return;
  }

  $decoded = json_decode( wp_remote_retrieve_body( $remote_post ), true );
  $success = ! empty( $decoded['success'] );

  if ( ! $success ) {
    // Log error codes for you (not the user).
    $codes = array();
    if ( ! empty( $decoded['error-codes'] ) && is_array( $decoded['error-codes'] ) ) {
      $codes = $decoded['error-codes'];
    }
    error_log(
      'Turnstile verify failed. Codes: ' . wp_json_encode( $codes ) . ' Payload: ' . wp_json_encode( $decoded )
    );

    $validation_errors->add( 'turnstile_error', __( 'Anti-spam verification failed. Please try again.', 'woocommerce' ) );
  }
}
add_action( 'woocommerce_register_post', __NAMESPACE__ . '\\validate_turnstile_on_register', 10, 3 );
