<?php

namespace b2tmods\cloudflareturnstile;

/**
 * Checks if Cloudflare Turnstile is enabled.
 *
 * @since 1.0.0
 *
 * @return bool True if enabled, false if disabled.
 */
function is_turnstile_enabled() {
  return (bool) get_option( 'b2tmods_cf_turnstile_enabled', true );
}

/**
 * Checks if Cloudflare Turnstile API keys are configured.
 *
 * @since 1.0.0
 *
 * @return bool True if both site key and secret key are defined and non-empty.
 */
function are_turnstile_keys_configured() {
  $site_key_set   = defined( 'CLOUDFLARE_TURNSTILE_SITE_KEY' ) && CLOUDFLARE_TURNSTILE_SITE_KEY;
  $secret_key_set = defined( 'CLOUDFLARE_TURNSTILE_SECRET_KEY' ) && CLOUDFLARE_TURNSTILE_SECRET_KEY;
  return $site_key_set && $secret_key_set;
}

/**
 * Registers the Cloudflare Turnstile dashboard widget.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_turnstile_dashboard_widget() {
  if ( ! current_user_can( 'manage_options' ) ) {
    return;
  }

  wp_add_dashboard_widget(
    'b2tmods_cf_turnstile_widget',
    'Cloudflare Turnstile',
    __NAMESPACE__ . '\\render_turnstile_dashboard_widget'
  );
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\register_turnstile_dashboard_widget' );

/**
 * Renders the Cloudflare Turnstile dashboard widget content.
 *
 * @since 1.0.0
 *
 * @return void
 */
function render_turnstile_dashboard_widget() {
  $keys_configured = are_turnstile_keys_configured();
  $enabled         = is_turnstile_enabled();
  $nonce           = wp_create_nonce( 'b2tmods_cf_turnstile_toggle' );
  ?>
  <p>
    <strong>Status:</strong>
    <?php if ( ! $keys_configured ) : ?>
      <span style="color: red;">Disabled</span>
      <span style="font-size: 12px; color: #666;">(API keys not configured)</span>
    <?php elseif ( $enabled ) : ?>
      <span style="color: green;">Enabled</span>
    <?php else : ?>
      <span style="color: red;">Disabled</span>
    <?php endif; ?>
  </p>
  <?php if ( ! $keys_configured ) : ?>
  <p style="font-size: 12px; color: #666;">
    Turnstile requires <code>CLOUDFLARE_TURNSTILE_SITE_KEY</code> and <code>CLOUDFLARE_TURNSTILE_SECRET_KEY</code> to be defined in wp-config.php.
  </p>
  <?php else : ?>
  <p style="font-size: 12px; color: #666;">
    Turnstile protects the registration form from spam. Disable temporarily if users cannot register.
  </p>
  <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
    <input type="hidden" name="action" value="b2tmods_cf_turnstile_toggle">
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>">
    <input type="hidden" name="b2tmods_cf_turnstile_enabled" value="<?php echo $enabled ? '0' : '1'; ?>">
    <?php
    submit_button(
      $enabled ? 'Disable Turnstile' : 'Enable Turnstile',
      $enabled ? 'secondary' : 'primary',
      'submit',
      false
    );
    ?>
  </form>
  <?php endif; ?>
  <?php
}

/**
 * Handles the Cloudflare Turnstile toggle form submission.
 *
 * @since 1.0.0
 *
 * @return void
 */
function handle_turnstile_toggle() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized', 'Error', array( 'response' => 403 ) );
  }

  if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'b2tmods_cf_turnstile_toggle' ) ) {
    wp_die( 'Invalid nonce', 'Error', array( 'response' => 403 ) );
  }

  $enabled = isset( $_POST['b2tmods_cf_turnstile_enabled'] ) && '1' === $_POST['b2tmods_cf_turnstile_enabled'];
  update_option( 'b2tmods_cf_turnstile_enabled', $enabled ? 1 : 0 );

  wp_safe_redirect( admin_url( 'index.php' ) );
  exit;
}
add_action( 'admin_post_b2tmods_cf_turnstile_toggle', __NAMESPACE__ . '\\handle_turnstile_toggle' );


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
  if ( ! is_turnstile_enabled() ) {
    return;
  }

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
  var turnstileTimeoutMs = 3000;
  var turnstileLoaded = false;
  var fallbackTimer = null;

  function getRegisterButton() {
    return document.querySelector('form.woocommerce-form-register button[type="submit"], form.register button[type="submit"]');
  }

  function setDisabled(isDisabled) {
    var btn = getRegisterButton();
    if (btn) {
      btn.disabled = !!isDisabled;
    }
  }

  function getWidget() {
    return document.querySelector('form.woocommerce-form-register .cf-turnstile, form.register .cf-turnstile');
  }

  function hasRendered(widget) {
    return !!(widget && widget.querySelector('iframe'));
  }

  function getMessageEl(widget) {
    if (!widget || !widget.parentNode) {
      return null;
    }
    var existing = widget.parentNode.querySelector('.b2t-turnstile-message');
    if (existing) {
      return existing;
    }
    var msg = document.createElement('p');
    msg.className = 'b2t-turnstile-message';
    msg.setAttribute('role', 'alert');
    msg.style.marginTop = '8px';
    msg.style.color = '#b32d2e';
    msg.style.fontSize = '0.9em';
    widget.parentNode.insertBefore(msg, widget.nextSibling);
    return msg;
  }

  function showMessage(text) {
    var widget = getWidget();
    var msg = getMessageEl(widget);
    if (msg) {
      msg.textContent = text;
    }
  }

  function clearMessage() {
    var widget = getWidget();
    var msg = widget ? widget.parentNode.querySelector('.b2t-turnstile-message') : null;
    if (msg) {
      msg.textContent = '';
    }
  }

  function markTurnstileLoaded() {
    turnstileLoaded = true;
    if (fallbackTimer) {
      window.clearTimeout(fallbackTimer);
      fallbackTimer = null;
    }
  }

  // Start disabled until Turnstile gives us a token. If it never loads, re-enable.
  document.addEventListener('DOMContentLoaded', function() {
    var widget = getWidget();
    if (!widget) {
      return;
    }

    setDisabled(true);

    fallbackTimer = window.setTimeout(function() {
      if (!turnstileLoaded && typeof window.turnstile === 'undefined' && !hasRendered(widget)) {
        setDisabled(false);
        showMessage('Security check did not load. If you declined cookies or use a blocker, allow security cookies and reload to complete registration.');
      }
    }, turnstileTimeoutMs);
  });

  // Turnstile callbacks (wired via data-* attributes on the widget div).
  window.b2tTurnstileOnSuccess = function(token) {
    markTurnstileLoaded();
    clearMessage();
    setDisabled(false);
  };

  window.b2tTurnstileOnError = function() {
    markTurnstileLoaded();
    setDisabled(true);
    showMessage('Security check failed to load. Please allow security cookies and reload to continue.');
  };

  window.b2tTurnstileOnExpired = function() {
    markTurnstileLoaded();
    setDisabled(true);
  };
})();
JS;

    wp_add_inline_script( 'cf-turnstile', $inline_js, 'after' );
  }
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_turnstile_script' );

/**
 * Marks the Turnstile script as essential for Termly's auto-blocker.
 *
 * @since 1.0.0
 *
 * @param string $tag    The <script> tag for the enqueued script.
 * @param string $handle The script handle.
 * @param string $src    The script source URL.
 *
 * @return string
 */
function add_turnstile_script_categories_attribute( $tag, $handle, $src ) {
  if ( 'cf-turnstile' !== $handle ) {
    return $tag;
  }

  if ( false !== strpos( $tag, 'data-categories=' ) ) {
    return $tag;
  }

  return str_replace( '<script ', '<script data-categories="essential" ', $tag );
}

add_filter( 'script_loader_tag', __NAMESPACE__ . '\\add_turnstile_script_categories_attribute', 10, 3 );

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
  if ( ! is_turnstile_enabled() ) {
    return;
  }

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
