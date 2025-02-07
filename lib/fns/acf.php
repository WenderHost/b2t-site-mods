<?php
namespace b2tmods\acf;

/**
 * Changes the save point for ACF JSON files to the plugin's `/lib/acf-json/` directory.
 *
 * This function hooks into the `acf/settings/save_json` filter to set the directory
 * where ACF saves JSON files when exporting or updating field groups.
 *
 * @param string $path The default path for saving ACF JSON files.
 * @return string The updated path for saving ACF JSON files.
 */
function acf_json_save_point( $path ) {
  // Update the path to the ACF JSON directory inside your plugin.
  $path = B2TMODS_PLUGIN_PATH . 'lib/acf-json';
  return $path;
}
// Download Group Options
add_filter( 'acf/settings/save_json/key=group_63f2a2beb826b', __NAMESPACE__ . '\\acf_json_save_point' );
// My Account Options
add_filter( 'acf/settings/save_json/key=group_641e00728ebd7', __NAMESPACE__ . '\\acf_json_save_point' );

/**
 * Adds the plugin's `/lib/acf-json/` directory as a load point for ACF JSON files.
 *
 * This function hooks into the `acf/settings/load_json` filter to add a custom directory
 * where ACF will look for JSON files when loading field groups.
 *
 * @param array $paths An array of directories where ACF looks for JSON files.
 * @return array The modified array of directories for loading ACF JSON files.
 */
function acf_json_load_point( $paths ) {
  // Add the custom path to load ACF JSON files.
  $paths[] = B2TMODS_PLUGIN_PATH . 'lib/acf-json';

  return $paths;
}
// Download Group Options
add_filter( 'acf/settings/load_json/key=group_63f2a2beb826b', __NAMESPACE__ . '\\acf_json_load_point' );
// My Account Options
add_filter( 'acf/settings/load_json/key=group_641e00728ebd7', __NAMESPACE__ . '\\acf_json_load_point' );