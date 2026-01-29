<?php
/**
 * Plugin Name:     B2T Site Mods
 * Plugin URI:      https://github.com/WenderHost/b2t-site-mods
 * Description:     Various extensions to the B2T Training site.
 * Author:          Michael Wender
 * Author URI:      https://wenmarkdigital.com
 * Text Domain:     b2t-site-mods
 * Domain Path:     /languages
 * Version:         1.5.5
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
