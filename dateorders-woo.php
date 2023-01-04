<?php

/*
Plugin Name: Product Delivery Date
Plugin URI: https://github.com/DtheRock/Woo---Product-Delivery-Date
Description: This plugin allows customers to customize the delivery date for their products during the checkout process. 
The plugin also allows the admin to set a minimum and maximum delivery time. There is also an admin page to view all orders with delivery dates.
Version: 1.1.0
Author: ITCS 
Author URI: https://itcybersecurity.gr/
License: GPLv2 or later
Text Domain: customize-product-delivery-date
*/
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'woopdd_fs' ) ) {
    woopdd_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    if ( !function_exists( 'woopdd_fs' ) ) {
        
        if ( !function_exists( 'woopdd_fs' ) ) {
            // Create a helper function for easy SDK access.
            function woopdd_fs()
            {
                global  $woopdd_fs ;
                
                if ( !isset( $woopdd_fs ) ) {
                    // Include Freemius SDK.
                    require_once dirname( __FILE__ ) . '/freemius/start.php';
                    $woopdd_fs = fs_dynamic_init( array(
                        'id'             => '11758',
                        'slug'           => 'product-delivery-date',
                        'type'           => 'plugin',
                        'public_key'     => 'pk_e99f6f750154ae9af91df25d8ecae',
                        'is_premium'     => false,
                        'premium_suffix' => 'Professional',
                        'has_addons'     => false,
                        'has_paid_plans' => true,
                        'menu'           => array(
                        'slug'   => 'wooproddel_settings',
                        'parent' => array(
                        'slug' => 'woocommerce',
                    ),
                    ),
                        'is_live'        => true,
                    ) );
                }
                
                return $woopdd_fs;
            }
            
            // Init Freemius.
            woopdd_fs();
            // Signal that SDK was initiated.
            do_action( 'woopdd_fs_loaded' );
        }
    
    }
    $is_activated = get_option( 'wooproddel_activation', '' );
    include_once plugin_dir_path( __FILE__ ) . 'dateorderstable.php';
    include_once plugin_dir_path( __FILE__ ) . 'dateorderscolumn.php';
    
    if ( woopdd_fs()->is_plan( 'pro' ) && $is_activated == 1 ) {
        include_once plugin_dir_path( __FILE__ ) . 'dateordersproduct.php';
        include_once plugin_dir_path( __FILE__ ) . 'splitorders.php';
        include_once plugin_dir_path( __FILE__ ) . 'emailsettingspage.php';
    }
    
    
    if ( woopdd_fs()->is_plan( 'pro' ) ) {
        // Activation checkbox callback
        function wooproddel_activation_callback()
        {
            //getting the option value and sanitizing it
            $value = get_option( 'wooproddel_activation', '' );
            echo  '<input type="checkbox" name="wooproddel_activation" value="1" ' . checked( esc_attr( $value ), 1, false ) . '/>' ;
        }
        
        // Notification checkbox callback
        function wooproddel_notification_callback()
        {
            //getting the option value and sanitizing it
            $value = get_option( 'wooproddel_notification', '' );
            echo  '<input type="checkbox" name="wooproddel_notification" value="1" ' . checked( esc_attr( $value ), 1, false ) . '/>' ;
        }
    
    } else {
        // Activation checkbox callback
        function wooproddel_activation_callback()
        {
            //getting the option value and sanitizing it
            $value = get_option( 'wooproddel_activation', '' );
            echo  '<input type="checkbox" name="wooproddel_activation" value="1" ' . checked( esc_attr( $value ), 1, false ) . 'disabled/>' ;
        }
        
        // Notification checkbox callback
        function wooproddel_notification_callback()
        {
            //getting the option value and sanitizing it
            $value = get_option( 'wooproddel_notification', '' );
            echo  '<input type="checkbox" name="wooproddel_notification" value="1" ' . checked( esc_attr( $value ), 1, false ) . 'disabled/>' ;
        }
    
    }
    
    function wooproddel_checkoutdates_callback()
    {
        //getting the option value and sanitizing it
        $value = get_option( 'wooproddel_checkoutdates', '' );
        echo  '<input type="checkbox" name="wooproddel_checkoutdates" value="1" ' . checked( esc_attr( $value ), 1, false ) . '/>' ;
    }
    
    function wooproddelpluginstyles()
    {
        wp_enqueue_style( 'wooproddelpluginstyles', plugin_dir_url( __FILE__ ) . 'css/styles.css' );
        //escaping the inline styles
        $inline_styles = wp_strip_all_tags( '.cp-orders-with-delivery-dates-table th,
                       .cp-orders-with-delivery-dates-table td {
                           display: block;
                       }
                       .delivery-date-within-two-days {
                                background-color: #FFA500;
                            }
                       .cp-orders-with-delivery-dates-table th {
                           font-size: 14px;
                       }' );
        wp_add_inline_style( 'wooproddelpluginstyles', $inline_styles );
    }
    
    add_action( 'wp_enqueue_scripts', 'wooproddelpluginstyles' );
    add_action( 'woocommerce_checkout_process', 'wooproddel_date_validation' );
    function wooproddel_date_validation()
    {
        // Get the min and max delivery time
        $min_delivery_time = get_option( 'wooproddel_min_delivery_time' );
        $max_delivery_time = get_option( 'wooproddel_max_delivery_time' );
        // Get the delivery date
        //sanitizing the input fields
        $delivery_date = sanitize_text_field( $_POST['wooproddel_delivery_date'] );
        // Calculate the min and max date
        $min_date = date( 'Y-m-d', strtotime( '+' . intval( $min_delivery_time ) . ' days' ) );
        $max_date = date( 'Y-m-d', strtotime( '+' . intval( $max_delivery_time ) . ' days' ) );
        // Check if the date is within the range
        if ( !empty($delivery_date) && $delivery_date < $min_date || $delivery_date > $max_date ) {
            // Add an error
            //internationalizing the error message
            wc_add_notice( esc_html__( 'Please select a delivery date between ' . esc_html( $min_date ) . ' and ' . esc_html( $max_date ), 'customize-product-delivery-date' ), 'error' );
        }
    }
    
    // Add the delivery date field to the checkout page
    add_action( 'woocommerce_after_checkout_billing_form', 'wooproddel_add_delivery_date_field' );
    function wooproddel_add_delivery_date_field( $checkout )
    {
        // Get the min and max delivery time
        $min_delivery_time = get_option( 'wooproddel_min_delivery_time' );
        $max_delivery_time = get_option( 'wooproddel_max_delivery_time' );
        // Calculate the min and max date
        $min_date = date( 'Y-m-d', strtotime( '+' . intval( $min_delivery_time ) . ' days' ) );
        $max_date = date( 'Y-m-d', strtotime( '+' . intval( $max_delivery_time ) . ' days' ) );
        $is_checkoutdates = get_option( 'wooproddel_checkoutdates', '' );
        echo  '<div id="wooproddel_delivery_date_field"><h3>' . '</h3>' ;
        woocommerce_form_field( 'wooproddel_delivery_date', array(
            'type'     => 'date',
            'class'    => array( 'my-field-class form-row-wide' ),
            'label'    => esc_html__( 'Select a delivery date', 'customize-product-delivery-date' ),
            'required' => false,
        ), $checkout->get_value( 'wooproddel_delivery_date' ) );
        if ( $is_checkoutdates == 1 ) {
            echo  '<p>' . esc_html__( 'Please select a delivery date between ' . esc_html( $min_date ) . ' and ' . esc_html( $max_date ) ) . '</p>' ;
        }
        echo  '</div>' ;
    }
    
    // Save the delivery date to the order meta data
    add_action( 'woocommerce_checkout_update_order_meta', 'wooproddel_save_delivery_date' );
    function wooproddel_save_delivery_date( $order_id )
    {
        if ( !empty($_POST['wooproddel_delivery_date']) ) {
            //sanitizing the input fields
            update_post_meta( $order_id, '_wooproddel_delivery_date', sanitize_text_field( $_POST['wooproddel_delivery_date'] ) );
        }
    }
    
    // Display the delivery date on the order view page
    add_action( 'woocommerce_admin_order_data_after_billing_address', 'wooproddel_display_delivery_date' );
    function wooproddel_display_delivery_date( $order )
    {
        //getting the post meta and sanitizing it
        $delivery_date = get_post_meta( $order->id, '_wooproddel_delivery_date', true );
        if ( $delivery_date ) {
            //escaping the output
            echo  '<p><strong>' . esc_html__( 'Delivery Date', 'customize-product-delivery-date' ) . ':</strong> ' . esc_html( $delivery_date ) . '</p>' ;
        }
    }
    
    // Add the settings page
    add_action( 'admin_menu', 'wooproddel_add_settings_page' );
    function wooproddel_add_settings_page()
    {
        add_submenu_page(
            'woocommerce',
            esc_html__( 'Delivery Date Settings', 'customize-product-delivery-date' ),
            esc_html__( 'Delivery Date', 'customize-product-delivery-date' ),
            'manage_options',
            'wooproddel_settings',
            'wooproddel_settings_page_callback'
        );
    }
    
    // Settings page callback
    function wooproddel_settings_page_callback()
    {
        //escaping the output
        echo  '<h1>' . esc_html__( 'Delivery Date Settings', 'customize-product-delivery-date' ) . '</h1>' ;
        echo  '<form method="post" action="options.php">' ;
        settings_fields( 'wooproddel_settings' );
        do_settings_sections( 'wooproddel_settings' );
        submit_button();
        echo  '</form>' ;
    }
    
    
    if ( woopdd_fs()->is_plan( 'pro' ) && $is_activated == 1 ) {
        // Add extra tab to the settings page
        add_action( 'admin_menu', 'wooproddel_add_settings_tab' );
        function wooproddel_add_settings_tab()
        {
            add_submenu_page(
                'woocommerce',
                esc_html__( 'Order Delivery Date Settings Tab
', 'customize-product-delivery-date' ),
                esc_html__( 'Order Delivery Date Settings Tab
', 'customize-product-delivery-date' ),
                'manage_options',
                'wooproddelpluginsettingstab',
                'wooproddelpluginsettingstabcallback'
            );
        }
        
        // Plugin settings tab callback
        function wooproddelpluginsettingstabcallback()
        {
            echo  "<h1>Order Delivery Date Settings</h1>" ;
            echo  "<p>You can setup your settings here.</p>" ;
            split_order_settings_page();
        }
    
    }
    
    // Register settings
    add_action( 'admin_init', 'wooproddel_register_settings' );
    function wooproddel_register_settings()
    {
        register_setting( 'wooproddel_settings', 'wooproddel_min_delivery_time', 'intval' );
        register_setting( 'wooproddel_settings', 'wooproddel_max_delivery_time', 'intval' );
        register_setting( 'wooproddel_settings', 'wooproddel_messages', 'wp_kses_post' );
        register_setting( 'wooproddel_settings', 'wooproddel_activation', 'intval' );
        register_setting( 'wooproddel_settings', 'wooproddel_notification', 'intval' );
        register_setting( 'wooproddel_settings', 'wooproddel_checkoutdates', 'intval' );
        add_settings_section(
            'wooproddel_settings_section',
            esc_html__( 'Delivery Date Settings', 'customize-product-delivery-date' ),
            'wooproddel_settings_section_callback',
            'wooproddel_settings'
        );
        add_settings_field(
            'wooproddel_min_delivery_time',
            esc_html__( 'Minimum Delivery Time (days)', 'customize-product-delivery-date' ),
            'wooproddel_min_delivery_time_callback',
            'wooproddel_settings',
            'wooproddel_settings_section'
        );
        add_settings_field(
            'wooproddel_max_delivery_time',
            esc_html__( 'Maximum Delivery Time (days)', 'customize-product-delivery-date' ),
            'wooproddel_max_delivery_time_callback',
            'wooproddel_settings',
            'wooproddel_settings_section'
        );
        add_settings_field(
            'wooproddel_messages',
            esc_html__( 'Messages', 'customize-product-delivery-date' ),
            'wooproddel_messages_callback',
            'wooproddel_settings',
            'wooproddel_settings_section'
        );
        // Add activation checkbox
        add_settings_field(
            'wooproddel_activation',
            esc_html__( 'Activate Product Delivery Date', 'customize-product-delivery-date' ),
            'wooproddel_activation_callback',
            'wooproddel_settings',
            'wooproddel_settings_section'
        );
        // Add notification checkbox
        add_settings_field(
            'wooproddel_notification',
            esc_html__( 'Email Notification for Order Spliting', 'customize-product-delivery-date' ),
            'wooproddel_notification_callback',
            'wooproddel_settings',
            'wooproddel_settings_section'
        );
        // Add checkout dates checkbox
        add_settings_field(
            'wooproddel_checkoutdates',
            esc_html__( 'Show the available dates in the checkout page', 'customize-product-delivery-date' ),
            'wooproddel_checkoutdates_callback',
            'wooproddel_settings',
            'wooproddel_settings_section'
        );
    }
    
    // Settings section callback
    function wooproddel_settings_section_callback()
    {
        //escaping the output
        echo  '<p>' . esc_html__( 'Configure the delivery date settings', 'customize-product-delivery-date' ) . '</p>' ;
    }
    
    // Minimum delivery time callback
    function wooproddel_min_delivery_time_callback()
    {
        //getting the option value and sanitizing it
        $value = get_option( 'wooproddel_min_delivery_time', 0 );
        echo  '<input type="number" name="wooproddel_min_delivery_time" value="' . esc_attr( $value ) . '" />' ;
    }
    
    // Maximum delivery time callback
    function wooproddel_max_delivery_time_callback()
    {
        //getting the option value and sanitizing it
        $value = get_option( 'wooproddel_max_delivery_time', 0 );
        echo  '<input type="number" name="wooproddel_max_delivery_time" value="' . esc_attr( $value ) . '" />' ;
    }
    
    // Messages callback
    function wooproddel_messages_callback()
    {
        //getting the option value and sanitizing it
        $value = get_option( 'wooproddel_messages', '' );
        echo  '<textarea name="wooproddel_messages" rows="5" cols="50">' . wp_kses_post( $value ) . '</textarea>' ;
    }
    
    // Add delivery date to WooCommerce order email notifications
    add_filter(
        'woocommerce_email_order_meta_fields',
        'wooproddel_add_delivery_date_email_notification',
        10,
        3
    );
    function wooproddel_add_delivery_date_email_notification( $fields, $sent_to_admin, $order )
    {
        //getting the post meta and sanitizing it
        $delivery_date = get_post_meta( $order->id, '_wooproddel_delivery_date', true );
        if ( $delivery_date ) {
            $fields['_wooproddel_delivery_date'] = array(
                'label' => esc_html__( 'Delivery Date', 'customize-product-delivery-date' ),
                'value' => sanitize_text_field( $delivery_date ),
            );
        }
        return $fields;
    }
    
    // Create a custom admin page to display orders with delivery dates
    add_action( 'admin_menu', 'wooproddel_add_admin_page' );
    function wooproddel_add_admin_page()
    {
        add_submenu_page(
            'woocommerce',
            esc_html__( 'Orders with Delivery Dates', 'customize-product-delivery-date' ),
            esc_html__( 'Orders with Delivery Dates', 'customize-product-delivery-date' ),
            'manage_options',
            'wooproddel_orders_with_delivery_dates',
            'wooproddel_orders_with_delivery_dates_callback'
        );
    }
    
    // Custom admin page callback
    function wooproddel_orders_with_delivery_dates_callback()
    {
        //escaping the output
        echo  '<h1>' . esc_html__( 'Orders with Delivery Dates', 'customize-product-delivery-date' ) . '</h1>' ;
        $orders_with_delivery_dates_table = new wooproddelclass_Orders_With_Delivery_Dates_Table();
        $orders_with_delivery_dates_table->prepare_items();
        $orders_with_delivery_dates_table->display();
    }
    
    // Display the message at the checkout page
    add_action( 'woocommerce_after_checkout_billing_form', 'wooproddel_display_message' );
    function wooproddel_display_message( $checkout )
    {
        // Get the message
        //getting the option value and sanitizing it
        $message = get_option( 'wooproddel_messages' );
        // Display the message if it is set
        if ( !empty($message) ) {
            //escaping the output
            echo  '<p>' . wp_kses_post( $message ) . '</p>' ;
        }
    }
    
    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wooproddel_plugin_action_links' );
    function wooproddel_plugin_action_links( $links )
    {
        $links[] = '<a href="' . esc_url( get_admin_url( null, 'admin.php?page=wooproddel_settings' ) ) . '">Settings</a>';
        return $links;
    }

}
