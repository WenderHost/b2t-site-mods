<?php

namespace b2tmods\emails;
use function b2tmods\templates\{render_template};

/**
 * Filter the email content to use a custom HTML template.
 *
 * @param array $args The original email arguments including 'to', 'subject', 'message', 'headers', 'attachments'.
 * @return array Filtered email arguments with custom HTML template.
 */
function wp_custom_email_template( $args ) {

  // If already an HTML email, return:
  if ( strpos( $args['message'], '<!DOCTYPE html>' ) === 0 )
      return $args;  

  // Extracting the email data.
  $to = $args['to'];
  $subject = $args['subject'];
  $message = $args['message'];
  $headers = $args['headers'];
  $attachments = $args['attachments'];

  // Ensure $headers is an array.
  if ( !is_array( $headers ) ) {
    $headers = array();
  }

  // Check and set content type to HTML if not already set.
  $has_html_content_type = false;
  foreach ( $headers as $header ) {
    if ( strpos( strtolower( $header ), 'content-type: text/html' ) !== false ) {
      $has_html_content_type = true;
      break;
    }
  }
  if ( !$has_html_content_type ) {
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
  }

  $heading = str_replace( '[' . get_bloginfo( 'name' ) . ']', '', $subject );    
  $heading_array = explode( ' ', trim( $heading ) );
  if( 2 == count( $heading_array ) ){
    for ($i=0; $i < count( $heading_array ); $i++) { 
      if( $i == ( count( $heading_array ) - 1 ) ){
        $new_heading.= ' <span style="color: #EC6B17;">' . $heading_array[$i] . '</span>';
      } else {
        $new_heading.= ' ' . $heading_array[$i];  
      }
    }      
    $heading = trim( $new_heading );
  }

  // Prepare data for the template.
  $data = array(
    'title' => $subject,
    'heading'  => $heading,
    'content' => nl2br( $message ),
    'logo' => B2TMODS_PLUGIN_URL . 'lib/img/b2t-logo_600x200.png',
    'year' => date( 'Y' ),
  );

  // Retrieve our handlebars template:
  $email = render_template( 'email', $data );

  // Replace the original message with the template.
  $args['message'] = $email;
  $args['headers'] = $headers;

  return $args;
}

// Add the filter to wp_mail.
add_filter( 'wp_mail', __NAMESPACE__ . '\\wp_custom_email_template' );

/**
 * Filters the content of the password change notification email sent to the admin.
 *
 * @param array  $wp_password_change_notification_email {
 *     Email details including 'to', 'subject', 'message', and 'headers'.
 * }
 * @param WP_User $user      The user whose password was changed.
 * @param string  $blogname  The site title.
 *
 * @return array Modified email content.
 */
function password_change_email_content( $wp_password_change_notification_email, $user, $blogname ){
  $wp_password_change_notification_email['message'] = "<strong>Admin Notice:</strong><br><br>The password was changed for user: {$user->user_login}<br><br>Regards,<br>The B2T WordPress Site";

  return $wp_password_change_notification_email;
}
add_filter( 'wp_password_change_notification_email', __NAMESPACE__ . '\\password_change_email_content', 10, 3 );

/**
 * Filters the password change confirmation email sent to the user.
 *
 * @param array  $pass_change_email {
 *     Email details including 'to', 'subject', 'message', and 'headers'.
 * }
 * @param WP_User $user        The user whose password was changed.
 * @param array   $userdata    An array of user data.
 *
 * @return array Modified email content.
 */
function custom_password_change_email( $pass_change_email, $user, $userdata ) {
  $pass_change_email['message'] = "This notice confirms that the password was reset for the following account on B2T Training:<br><br>Username: {$user->user_login}<br><br>If you did not change your password, or encounter any other issues, please <a href=\"mailto:info@b2ttraining.com\">contact us</a> as soon as possible.";

  return $pass_change_email;  
}
add_filter( 'password_change_email', __NAMESPACE__ . '\\custom_password_change_email', 10, 3 );

// Ensure the password change email gets sent to the user.
add_filter( 'send_password_change_email', '__return_true', PHP_INT_MAX );


