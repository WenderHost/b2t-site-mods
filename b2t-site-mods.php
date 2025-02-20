<?php
/**
 * Plugin Name:     B2T Site Mods
 * Plugin URI:      https://github.com/WenderHost/b2t-site-mods
 * Description:     Various extensions to the B2T Training site.
 * Author:          Michael Wender
 * Author URI:      https://wenmarkdigital.com
 * Text Domain:     b2t-site-mods
 * Domain Path:     /languages
 * Version:         1.4.4
 * @package         B2t_Site_Mods
 */

/**
 * Setup constants.
 *
 * @var        string  $css_dir Either `css` or `dist` depending on Site URL.
 */
$css_dir = ( stristr( site_url(), '.local' ) || SCRIPT_DEBUG )? 'css' : 'dist' ;
define( 'B2TMODS_CSS_DIR', $css_dir );
define( 'B2TMODS_DEV_ENV', stristr( site_url(), '.local' ) );
define( 'B2TMODS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'B2TMODS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load required files
 */
$required_files = array_diff( scandir( B2TMODS_PLUGIN_PATH . 'lib/fns' ), [ '.', '..' ] );
foreach( $required_files as $file ){
  require_once B2TMODS_PLUGIN_PATH . 'lib/fns/' . $file;
}

/**
 * Enhanced logging.
 *
 * @param      mixed  $message  The log message, can be a string or an array.
 */
if( ! function_exists( 'uber_log' ) ){
  function uber_log( $message = null, $varname = '' ){
    static $counter = 1;

    $bt = debug_backtrace();
    $caller = array_shift( $bt );

    //date('h:i:sa', current_time('timestamp') )
    if( 1 == $counter )
      error_log( "\n\n" . str_repeat('-', 25 ) . ' STARTING DEBUG [' . current_time( 'h:i:sa' ) . '] ' . str_repeat('-', 25 ) . "\n\n" );

    if( is_array( $message ) || is_object( $message ) ){
      $message_str = "🔔 $varname is Array: \n";
      foreach ($message as $key => $value) {
        if( is_array( $value ) || is_object( $value ) )
          $value = print_r( $value, true );
        $message_str.= "-- 👉 " . $key . ' = ' . $value . "\n";
      }
      $message = $message_str;
    }

    error_log( "\n" . $counter . '. ' . basename( $caller['file'] ) . '::' . $caller['line'] . "\n" . $message . "\n---\n" );
    $counter++;
  }
}