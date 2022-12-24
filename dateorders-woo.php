<?php
/*
Plugin Name: Product Delivery Date
Plugin URI: https://github.com/DtheRock/Woo---Product-Delivery-Date
Description: This plugin allows customers to customize the delivery date for their products during the checkout process. 
The plugin also allows the admin to set a minimum and maximum delivery time. There is also an admin page to view all orders with delivery dates.
Version: 1.0.0
Author: ITCS 
Author URI: https://itcybersecurity.gr/
License: GPLv2 or later
Text Domain: customize-product-delivery-date
*/

include_once(plugin_dir_path(__FILE__) . 'dateorderstable.php');
include_once(plugin_dir_path(__FILE__) . 'dateorderscolumn.php');

function pluginstyles() {
    wp_enqueue_style( 'pluginstyles', plugin_dir_url( __FILE__ ) . 'css/styles.css' );

    //escaping the inline styles 
    $inline_styles = esc_attr('.cp-orders-with-delivery-dates-table th,
                       .cp-orders-with-delivery-dates-table td {
                           display: block;
                       }
                       .delivery-date-within-two-days {
                                background-color: #FFA500;
                            }
                       .cp-orders-with-delivery-dates-table th {
                           font-size: 14px;
                       }');
    wp_add_inline_style( 'pluginstyles', $inline_styles );
}
add_action( 'wp_enqueue_scripts', 'pluginstyles' );
add_action( 'woocommerce_checkout_process', 'cpdd_date_validation' );
function cpdd_date_validation() {
    // Get the min and max delivery time
    $min_delivery_time = get_option( 'cpdd_min_delivery_time' );
    $max_delivery_time = get_option( 'cpdd_max_delivery_time' );

    // Get the delivery date
    //sanitizing the input fields
    $delivery_date = sanitize_text_field($_POST['cpdd_delivery_date']);

    // Calculate the min and max date
    $min_date = date('Y-m-d', strtotime('+' . $min_delivery_time . ' days'));
    $max_date = date('Y-m-d', strtotime('+' . $max_delivery_time . ' days'));

    // Check if the date is within the range
    if (  !empty( $delivery_date ) && $delivery_date < $min_date || $delivery_date > $max_date ) {
        // Add an error
        //internationalizing the error message
        wc_add_notice( __('Please select a delivery date between ' . $min_date . ' and ' . $max_date, 'customize-product-delivery-date'), 'error' );
    }
}

// Add the delivery date field to the checkout page
add_action( 'woocommerce_after_checkout_billing_form', 'cpdd_add_delivery_date_field' );
function cpdd_add_delivery_date_field( $checkout ) {
    echo '<div id="cpdd_delivery_date_field"><h3>' . __('', 'customize-product-delivery-date') . '</h3>';

    woocommerce_form_field( 'cpdd_delivery_date', array(
        'type'          => 'date',
        'class'         => array('my-field-class form-row-wide'),
        //internationalizing the label 
        'label'         => __('Select a delivery date', 'customize-product-delivery-date'),
        'required'      => false,
    ), $checkout->get_value( 'cpdd_delivery_date' ));

    echo '</div>';
}

// Save the delivery date to the order meta data
add_action( 'woocommerce_checkout_update_order_meta', 'cpdd_save_delivery_date' );
function cpdd_save_delivery_date( $order_id ) {
    if ( ! empty( $_POST['cpdd_delivery_date'] ) ) {
        //sanitizing the input fields
        update_post_meta( $order_id, '_cpdd_delivery_date', sanitize_text_field( $_POST['cpdd_delivery_date'] ) );
    }
}

// Display the delivery date on the order view page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'cpdd_display_delivery_date' );
function cpdd_display_delivery_date( $order ){
    $delivery_date = get_post_meta( $order->id, '_cpdd_delivery_date', true );
    /*if ( $delivery_date ) {
        //escaping the output
        echo '<p><strong>' . esc_html__('Delivery Date', 'customize-product-delivery-date') . ':</strong> ' . esc_html($delivery_date) . '</p>';
    }*/
}

// Add the settings page
add_action( 'admin_menu', 'cpdd_add_settings_page' );
function cpdd_add_settings_page() {
    add_submenu_page(
        'woocommerce',
        __('Delivery Date Settings', 'customize-product-delivery-date'),
        __('Delivery Date', 'customize-product-delivery-date'),
        'manage_options',
        'cpdd_settings',
        'cpdd_settings_page_callback'
    );
}

// Settings page callback
function cpdd_settings_page_callback() {
   // echo '<h1>' . __('Delivery Date Settings', 'customize-product-delivery-date') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'cpdd_settings' );
    do_settings_sections( 'cpdd_settings' );
    submit_button();
    echo '</form>';
}

// Register settings
add_action( 'admin_init', 'cpdd_register_settings' );
function cpdd_register_settings() {
    register_setting( 'cpdd_settings', 'cpdd_min_delivery_time' );
    register_setting( 'cpdd_settings', 'cpdd_max_delivery_time' );
    register_setting( 'cpdd_settings', 'cpdd_messages' );

    add_settings_section(
        'cpdd_settings_section',
        __('Delivery Date Settings', 'customize-product-delivery-date'),
        'cpdd_settings_section_callback',
        'cpdd_settings'
    );

    add_settings_field(
        'cpdd_min_delivery_time',
        __('Minimum Delivery Time (days)', 'customize-product-delivery-date'),
        'cpdd_min_delivery_time_callback',
        'cpdd_settings',
        'cpdd_settings_section'
    );

    add_settings_field(
        'cpdd_max_delivery_time',
        __('Maximum Delivery Time (days)', 'customize-product-delivery-date'),
        'cpdd_max_delivery_time_callback',
        'cpdd_settings',
        'cpdd_settings_section'
    );

    add_settings_field(
        'cpdd_messages',
        __('Messages', 'customize-product-delivery-date'),
        'cpdd_messages_callback',
        'cpdd_settings',
        'cpdd_settings_section'
    );
}

// Settings section callback
function cpdd_settings_section_callback() {
    //escaping the output
    echo '<p>' . esc_html__('Configure the delivery date settings', 'customize-product-delivery-date') . '</p>';
}

// Minimum delivery time callback
function cpdd_min_delivery_time_callback() {
    //getting the option value and sanitizing it
    $value = sanitize_text_field(get_option( 'cpdd_min_delivery_time', 0 ));
    echo '<input type="number" name="cpdd_min_delivery_time" value="' . esc_attr( $value ) . '" />';
}

// Maximum delivery time callback
function cpdd_max_delivery_time_callback() {
    //getting the option value and sanitizing it
    $value = sanitize_text_field(get_option( 'cpdd_max_delivery_time', 0 ));
    echo '<input type="number" name="cpdd_max_delivery_time" value="' . esc_attr( $value ) . '" />';
}

// Messages callback
function cpdd_messages_callback() {
    //getting the option value and sanitizing it
    $value = sanitize_text_field(get_option( 'cpdd_messages', '' ));
    echo '<textarea name="cpdd_messages" rows="5" cols="50">' . esc_textarea( $value ) . '</textarea>';
}

// Add delivery date to WooCommerce order email notifications
add_filter( 'woocommerce_email_order_meta_fields', 'cpdd_add_delivery_date_email_notification', 10, 3 );
function cpdd_add_delivery_date_email_notification( $fields, $sent_to_admin, $order ) {
    //getting the post meta and sanitizing it
    $delivery_date = sanitize_text_field(get_post_meta( $order->id, '_cpdd_delivery_date', true ));
    if ( $delivery_date ) {
        $fields['_cpdd_delivery_date'] = array(
            'label' => __('Delivery Date', 'customize-product-delivery-date'),
            'value' => $delivery_date
        );
    }
    return $fields;
}

// Create a custom admin page to display orders with delivery dates
add_action( 'admin_menu', 'cpdd_add_admin_page' );
function cpdd_add_admin_page() {
    add_submenu_page(
        'woocommerce',
        __('Orders with Delivery Dates', 'customize-product-delivery-date'),
        __('Orders with Delivery Dates', 'customize-product-delivery-date'),
        'manage_options',
        'cpdd_orders_with_delivery_dates',
        'cpdd_orders_with_delivery_dates_callback'
    );
}

// Custom admin page callback
function cpdd_orders_with_delivery_dates_callback() {
    //escaping the output
    echo '<h1>' . esc_html__('Orders with Delivery Dates', 'customize-product-delivery-date') . '</h1>';
    $orders_with_delivery_dates_table = new CP_Orders_With_Delivery_Dates_Table();
    $orders_with_delivery_dates_table->prepare_items();
    $orders_with_delivery_dates_table->display();
}

// Display the message at the checkout page
add_action( 'woocommerce_after_checkout_billing_form', 'cpdd_display_message' );
function cpdd_display_message( $checkout ) {
    // Get the message
    //getting the option value and sanitizing it
    $message = sanitize_text_field(get_option( 'cpdd_messages' ));

    // Display the message if it is set
    if ( !empty( $message ) ) {
        //escaping the output
        echo '<p>' . esc_html($message) . '</p>';
    }
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'cpdd_plugin_action_links' );

?>