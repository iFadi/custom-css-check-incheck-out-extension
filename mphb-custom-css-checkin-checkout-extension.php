<?php
/**
 * Plugin Name: MPHB Custom CSS Check-in/Check-out Extension
 * Plugin URI: https://kolibri-visions.de
 * Description: Customizes the booking calendar of the MotoPress Hotel Booking plugin, providing additional visual for certain dates.
 * Version: 1.0.0
 * Author: Fadi Asbih <fadi.asbih@kolibri-visions.de>, Khaled El-Kailani <khaledelkailani@kolibri-visions.de>
 * Author URI: https://asbih.com/
 * License: MIT License
 * License URI: https://opensource.org/licenses/MIT
 **/



/**
 * Fetch check-in and check-out dates from the database where they are identical 
 * and associated with the same iCal description.
 *
 * @return array $results An associative array of the fetched dates.
 */
function fetch_check_in_out_dates() {
    global $wpdb;
    $query = "
        SELECT A.post_id AS post_id_A, B.post_id AS post_id_B, A.meta_value AS check_in_date, B.meta_value AS check_out_date
        FROM {$wpdb->prefix}postmeta A
        JOIN {$wpdb->prefix}postmeta B ON A.meta_value = B.meta_value AND A.post_id != B.post_id
        JOIN {$wpdb->prefix}postmeta C ON A.post_id = C.post_id
        JOIN {$wpdb->prefix}postmeta D ON B.post_id = D.post_id
        WHERE A.meta_key = 'mphb_check_in_date' 
        AND B.meta_key = 'mphb_check_out_date'
        AND C.meta_key = 'mphb_ical_description'
        AND D.meta_key = 'mphb_ical_description'
        AND C.meta_value = D.meta_value;
    ";
    // Fetch results from the database.
    $results = $wpdb->get_results($query, ARRAY_A);
    
$all_data = array();
    foreach ($results as $result) {
        $additional_data_A = fetch_additional_postmeta_data($result['post_id_A']);
        error_log('All Data A: ' . print_r($additional_data_A, true));
        
        $sync_id = extract_meta_value($additional_data_A, '_mphb_sync_id');

if (!empty($sync_id)) {
    $sync_id_data = fetch_sync_id_data($sync_id);
    error_log('Sync ID Data: ' . print_r($sync_id_data, true));

    // Additional checks and explicit logging
    if (!empty($sync_id_data) && is_array($sync_id_data)) {
        error_log('Explicit Room ID: ' . $sync_id_data[0]['room_id']);
        $room_id = $sync_id_data[0]['room_id'];

        if (!empty($room_id)) {
            $room_type_id = fetch_room_type_id($room_id);
            
            $result['additional_data_A'] = $additional_data_A;
            $result['sync_id_data'] = $sync_id_data;
            $result['room_type_id'] = $room_type_id;
            
            $all_data[] = $result;
        } else {
            error_log("No room_id found for sync_id: " . $sync_id);
        }
    } else {
        error_log("Sync ID Data is empty or not an array for sync_id: " . $sync_id);
    }
} else {
    error_log("No _mphb_sync_id found for post_id: " . $result['post_id_A']);
}
    }
    return $all_data;
}

function extract_meta_value($array, $key) {
    foreach ($array as $item) {
        if (isset($item['meta_key']) && $item['meta_key'] == $key) {
            return $item['meta_value'];
        }
    }
    return '';
}

function fetch_additional_postmeta_data($post_id) {
    global $wpdb;
    $query = $wpdb->prepare("
        SELECT meta_key, meta_value
        FROM {$wpdb->prefix}postmeta
        WHERE post_id = %d
    ", $post_id);
    return $wpdb->get_results($query, ARRAY_A);
}

function fetch_sync_id_data($sync_id) {
    global $wpdb;
    $query = $wpdb->prepare("
        SELECT *
        FROM {$wpdb->prefix}mphb_sync_urls
        WHERE sync_id = %s
    ", $sync_id);
    $results = $wpdb->get_results($query, ARRAY_A);

    // Log the SQL query and results for debugging
    error_log("SQL Query: " . $query);
    error_log("Results: " . print_r($results, true));

    return $results;
}


function fetch_room_type_id($room_id) {
    global $wpdb;
    $query = $wpdb->prepare("
        SELECT meta_value
        FROM {$wpdb->prefix}postmeta
        WHERE post_id = %d AND meta_key = 'mphb_room_type_id'
    ", $room_id);
    return $wpdb->get_var($query);
}

/**
 * Enqueue custom JavaScript and CSS for enhancing the booking functionality.
 */
function enqueue_custom_booking_styles_scripts() {
    // Get the current URL path
    $current_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

    // Define the base path where scripts and styles should be enqueued
    $base_path_to_include = '/unterkunft/';

    // Check if the current path starts with the base path
    if ( strpos( $current_path, $base_path_to_include ) === 0 ) {
        // Dynamic versioning for cache-busting
        $script_ver = filemtime( plugin_dir_path( __FILE__ ) . 'js/mphb-custom-css-checkin-checkout-extension-script.js' );
        $style_ver  = filemtime( plugin_dir_path( __FILE__ ) . 'css/mphb-custom-css-checkin-checkout-extension.css' );

        // Fetch the current post ID
        $current_post_id = get_the_ID();

        // Enqueue custom JavaScript with dynamic versioning.
        wp_enqueue_script('mphb-custom-css-checkin-checkout-extension-script', plugin_dir_url(__FILE__) . 'js/mphb-custom-css-checkin-checkout-extension-script.js', array('jquery'), $script_ver, true);

        // Localize script to pass PHP variables to JavaScript.
        wp_localize_script('mphb-custom-css-checkin-checkout-extension-script', 'bookingData', array('dates' => fetch_check_in_out_dates()));

        
        // Enqueue custom CSS with dynamic versioning.
        wp_enqueue_style('mphb-custom-css-checkin-checkout-extension', plugin_dir_url(__FILE__) . 'css/mphb-custom-css-checkin-checkout-extension.css', array(), $style_ver, 'all');
    }
}

add_action('wp_enqueue_scripts', 'enqueue_custom_booking_styles_scripts', 999);
