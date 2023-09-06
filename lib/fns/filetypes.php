<?php
namespace b2tmods\filetypes;

// Add this code to your theme's functions.php file or in a custom plugin.

// Allow uploading of Excel files
function allow_excel_upload($mime_types){
    $mime_types['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $mime_types['xls'] = 'application/vnd.ms-excel';
    return $mime_types;
}
add_filter('upload_mimes', __NAMESPACE__ . '\\allow_excel_upload');

// Add Excel to the list of allowed file types in Media Uploader
function custom_upload_mimes($existing_mimes = array()){
    $existing_mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    $existing_mimes['xls'] = 'application/vnd.ms-excel';
    return $existing_mimes;
}
add_filter('mime_types', __NAMESPACE__ . '\\custom_upload_mimes');

// Display the MIME type of uploaded files in Media Library
function display_mime_type($columns){
    $columns['media_type'] = 'MIME Type';
    return $columns;
}
function media_mime_type_value($column_name, $id){
    if($column_name === 'media_type'){
        $file = get_attached_file($id);
        $mime_type = mime_content_type($file);
        echo $mime_type;
    }
}
add_filter('manage_media_columns', __NAMESPACE__ . '\\display_mime_type');
add_action('manage_media_custom_column', __NAMESPACE__ . '\\media_mime_type_value', 10, 2);
