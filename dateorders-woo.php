<?php
/*
Plugin Name: Product Delivery Date
Description: This plugin allows customers to customize the delivery date for their products during the checkout process. 
The plugin also allows the admin to set a minimum and maximum delivery time. There is also an admin page to view all orders with delivery dates.
Version: 1.0.0
Author: ITCS 
Author URI: https://itcybersecurity.gr/
License: GPLv2 or later
Text Domain: customize-product-delivery-date
*/
function my_plugin_styles() {
    wp_enqueue_style( 'my-plugin-styles', plugin_dir_url( __FILE__ ) . 'css/my-plugin-styles.css' );

    $inline_styles = '.cp-orders-with-delivery-dates-table th,
                       .cp-orders-with-delivery-dates-table td {
                           display: block;
                       }
                       .delivery-date-within-two-days {
                                background-color: #FFA500;
                            }

                       .cp-orders-with-delivery-dates-table th {
                           font-size: 14px;
                       }';
    wp_add_inline_style( 'my-plugin-styles', $inline_styles );
}
add_action( 'wp_enqueue_scripts', 'my_plugin_styles' );
add_action( 'woocommerce_checkout_process', 'cpdd_date_validation' );
function cpdd_date_validation() {
    // Get the min and max delivery time
    $min_delivery_time = get_option( 'cpdd_min_delivery_time' );
    $max_delivery_time = get_option( 'cpdd_max_delivery_time' );

    // Get the delivery date
    $delivery_date = $_POST['cpdd_delivery_date'];

    // Calculate the min and max date
    $min_date = date('Y-m-d', strtotime('+' . $min_delivery_time . ' days'));
    $max_date = date('Y-m-d', strtotime('+' . $max_delivery_time . ' days'));

    // Check if the date is within the range
    if (  !empty( $delivery_date ) && $delivery_date < $min_date || $delivery_date > $max_date ) {
        // Add an error
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
        'label'         => __('Select a delivery date', 'customize-product-delivery-date'),
        'required'      => false,
    ), $checkout->get_value( 'cpdd_delivery_date' ));

    echo '</div>';
}

// Save the delivery date to the order meta data
add_action( 'woocommerce_checkout_update_order_meta', 'cpdd_save_delivery_date' );
function cpdd_save_delivery_date( $order_id ) {
    if ( ! empty( $_POST['cpdd_delivery_date'] ) ) {
        update_post_meta( $order_id, '_cpdd_delivery_date', sanitize_text_field( $_POST['cpdd_delivery_date'] ) );
    }
}

// Display the delivery date on the order view page
add_action( 'woocommerce_admin_order_data_after_billing_address', 'cpdd_display_delivery_date' );
function cpdd_display_delivery_date( $order ){
    $delivery_date = get_post_meta( $order->id, '_cpdd_delivery_date', true );
    /*if ( $delivery_date ) {
        echo '<p><strong>' . __('Delivery Date', 'customize-product-delivery-date') . ':</strong> ' . $delivery_date . '</p>';
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
    echo '<p>' . __('Configure the delivery date settings', 'customize-product-delivery-date') . '</p>';
}

// Minimum delivery time callback
function cpdd_min_delivery_time_callback() {
    $value = get_option( 'cpdd_min_delivery_time', 0 );
    echo '<input type="number" name="cpdd_min_delivery_time" value="' . esc_attr( $value ) . '" />';
}

// Maximum delivery time callback
function cpdd_max_delivery_time_callback() {
    $value = get_option( 'cpdd_max_delivery_time', 0 );
    echo '<input type="number" name="cpdd_max_delivery_time" value="' . esc_attr( $value ) . '" />';
}

// Messages callback
function cpdd_messages_callback() {
    $value = get_option( 'cpdd_messages', '' );
    echo '<textarea name="cpdd_messages" rows="5" cols="50">' . esc_textarea( $value ) . '</textarea>';
}

// Add delivery date to WooCommerce order email notifications
add_filter( 'woocommerce_email_order_meta_fields', 'cpdd_add_delivery_date_email_notification', 10, 3 );
function cpdd_add_delivery_date_email_notification( $fields, $sent_to_admin, $order ) {
    $delivery_date = get_post_meta( $order->id, '_cpdd_delivery_date', true );
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
    echo '<h1>' . __('Orders with Delivery Dates', 'customize-product-delivery-date') . '</h1>';
    $orders_with_delivery_dates_table = new CP_Orders_With_Delivery_Dates_Table();
    $orders_with_delivery_dates_table->prepare_items();
    $orders_with_delivery_dates_table->display();
}

// WP_List_Table class
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CP_Orders_With_Delivery_Dates_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct( array(
            'singular' => __('Order with Delivery Date', 'customize-product-delivery-date'),
            'plural'   => __('Orders with Delivery Dates', 'customize-product-delivery-date'),
            'ajax'     => false
        ));
    }

    public function get_columns() {
        $columns = array(
            'order_id'        => __('Order ID', 'customize-product-delivery-date'),
            'customer_name'   => __('Customer Name', 'customize-product-delivery-date'),
            'order_date'      => __('Order Date', 'customize-product-delivery-date'),
            'delivery_date'   => __('Delivery Date', 'customize-product-delivery-date'),
            'two_days'        => __('Within 2 days?', 'customize-product-delivery-date')
        );
        return $columns;
    }

    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();
        $per_page = 10;
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));
        $this->items = self::get_orders_with_delivery_dates( $per_page, $current_page );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'order_id':
            case 'customer_name':
            case 'order_date':
            case 'delivery_date':
            case 'two_days':
                return $item[ $column_name ];
            default:
                return print_r( $item, true );
        }
    }

    public function column_two_days( $item ) {
        $delivery_date = new DateTime( $item['delivery_date'] );
        $now = new DateTime();
        $diff = date_diff( $now, $delivery_date );
        if ( $diff->days < 2 ) {
            return __('Yes', 'customize-product-delivery-date');
        } else {
            return __('No', 'customize-product-delivery-date');
        }
    }

    public function column_delivery_date( $item ) {
        $delivery_date = new DateTime( $item['delivery_date'] );
        return $delivery_date->format('Y-m-d');
    }

    public function column_order_date( $item ) {
        $order_date = new DateTime( $item['order_date'] );
        return $order_date->format('Y-m-d');
    }

    public function row_class( $item ) {
        $delivery_date = new DateTime( $item['delivery_date'] );
        $now = new DateTime();
        $diff = date_diff( $now, $delivery_date );
        if ( $diff->days < 2 ) {
            return 'delivery-date-within-two-days';
        } else {
            return '';
        }
    }

    private static function get_orders_with_delivery_dates( $per_page = 10, $page_number = 1 ) {
        global $wpdb;
        $sql = "SELECT p.ID as order_id, p.post_date as order_date, pm.meta_value as delivery_date, u.display_name as customer_name
                FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID
                INNER JOIN {$wpdb->prefix}users u ON u.ID = p.post_author
                WHERE p.post_type = 'shop_order'
                AND pm.meta_key = '_cpdd_delivery_date'
                ORDER BY delivery_date ASC";
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        return $result;
    }

    public static function record_count() {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID
                WHERE p.post_type = 'shop_order'
                AND pm.meta_key = '_cpdd_delivery_date'";
        return $wpdb->get_var( $sql );
    }
}

function cpdd_plugin_action_links( $links ) {
    $links[] = '<a href="' .
        admin_url( 'admin.php?page=cpdd_settings' ) .
        '">' . __('Settings', 'customize-product-delivery-date') . '</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'cpdd_plugin_action_links' );

?>