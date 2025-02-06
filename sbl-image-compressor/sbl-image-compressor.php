<?php
/**
 * Plugin Name: SBL Image Compressor
 * Plugin URI:  https://example.com/sbl-image-compressor
 * Description: Automatically compresses images upon upload to optimize performance.
 * Version:     1.0.0
 * Author:      Subtain Ali Chohan
 * Author URI:  https://example.com
 * License:     GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compress uploaded images
 *
 * @param array $upload The uploaded file information.
 * @return array Modified file information.
 */
function sbl_compress_uploaded_image($upload) {
    // Check if the uploaded file is an image
    $file_type = wp_check_filetype($upload['file']);
    if (strpos($file_type['type'], 'image') === false) {
        return $upload;
    }

    // Ensure Imagick is available
    if (!class_exists('Imagick')) {
        return $upload;
    }

    try {
        $image = new Imagick($upload['file']);
        
        // Set image compression settings
        $image->setImageCompression(Imagick::COMPRESSION_JPEG);
        $image->setImageCompressionQuality(80); // Adjust quality (0-100)
        
        // Strip image metadata to reduce size
        $image->stripImage();
        
        // Save the optimized image
        $image->writeImage($upload['file']);
        
        // Cleanup
        $image->clear();
        $image->destroy();
    } catch (Exception $e) {
        error_log('SBL Image Compressor Error: ' . $e->getMessage());
    }

    return $upload;
}
add_filter('wp_handle_upload', 'sbl_compress_uploaded_image');

/**
 * Add menu page for image upload
 */
function sbl_image_compressor_menu() {
    add_menu_page(
        'SBL Image Compressor',
        'Image Compressor',
        'manage_options',
        'sbl-image-compressor',
        'sbl_image_compressor_page',
        'dashicons-image-filter',
        100
    );
}
add_action('admin_menu', 'sbl_image_compressor_menu');

/**
 * Display the image upload page
 */
function sbl_image_compressor_page() {
    include plugin_dir_path(__FILE__) . 'views/image-compressor-page.php';
}

/**
 * Handle image upload and compression
 */
function sbl_handle_uploaded_images() {
    if (isset($_POST['sbl_upload_image']) && !empty($_FILES['sbl_compress_image']['name'][0])) {
        foreach ($_FILES['sbl_compress_image']['tmp_name'] as $key => $tmp_name) {
            $file_path = wp_upload_dir()['path'] . '/' . $_FILES['sbl_compress_image']['name'][$key];
            move_uploaded_file($tmp_name, $file_path);
            
            // Compress image
            $upload = ['file' => $file_path];
            sbl_compress_uploaded_image($upload);
        }
        echo '<div class="updated"><p>Images uploaded and compressed successfully!</p></div>';
    }
}
add_action('admin_init', 'sbl_handle_uploaded_images');
