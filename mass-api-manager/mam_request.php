<?php
$path = '../../../';

include_once $path . '/wp-config.php';
include_once $path . '/wp-load.php';
include_once $path . '/wp-includes/wp-db.php';
include_once $path . '/wp-includes/pluggable.php';

$kind = $_POST['kind'];
if($kind == 'save_gy_key'){
    $key = $_POST['key'];
    global $wpdb;

    $wpdb->insert($wpdb->prefix . 'mam_auth', array(
        'user_id' => get_current_user_id(), 
        'api_key' => $key
    ));
} else if($kind == 'create_camp'){
    $camp_name = $_POST['camp_name'];
    $keyword_list = $_POST['keyword_list'];
    global $wpdb;

    $wpdb->insert($wpdb->prefix . 'mam_campaign', array(
        'user_id' => get_current_user_id(), 
        'name' => $camp_name, 
        'keywords' => $keyword_list
    ));

    echo $wpdb->insert_id;
}
