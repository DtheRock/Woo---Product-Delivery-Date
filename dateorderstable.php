<?php
// WP_List_Table class
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class wooproddelclass_Orders_With_Delivery_Dates_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct( array(
            'singular' => esc_html__('Order with Delivery Date', 'customize-product-delivery-date'),
            'plural'   => esc_html__('Orders with Delivery Dates', 'customize-product-delivery-date'),
            'ajax'     => false
        ));
    }

    public function get_columns() {
        $columns = array(
            'order_id'        => esc_html__('Order ID', 'customize-product-delivery-date'),
            'customer_name'   => esc_html__('Customer Name', 'customize-product-delivery-date'),
            'order_date'      => esc_html__('Order Date', 'customize-product-delivery-date'),
            'delivery_date'   => esc_html__('Delivery Date', 'customize-product-delivery-date'),
            'two_days'        => esc_html__('Within 2 days?', 'customize-product-delivery-date')
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
                return esc_html( $item[ $column_name ] );
            default:
                return print_r( $item, true );
        }
    }

    public function column_two_days( $item ) {
        $delivery_date = new DateTime( sanitize_text_field( $item['delivery_date'] ) );
        $now = new DateTime();
        $diff = date_diff( $now, $delivery_date );
        if ( $diff->days < 2 ) {
            return '<p style="background-color:#FFA07A; text-align: center; max-width:50%;">'.esc_html__('Yes', 'customize-product-delivery-date').'</p>';
        } else {
            return '<p style="background-color:green; text-align: center; max-width:50%;">'.esc_html__('No', 'customize-product-delivery-date').'</p>';
        }
    }

    public function column_delivery_date( $item ) {
        $delivery_date = new DateTime( sanitize_text_field( $item['delivery_date'] ) );
		return esc_html( $delivery_date->format('Y-m-d') );
    }

    public function column_order_date( $item ) {
        $order_date = new DateTime( sanitize_text_field( $item['order_date'] ) );
		return esc_html( $order_date->format('Y-m-d') );
    }

    public function row_class( $item ) {
        $delivery_date = new DateTime( sanitize_text_field( $item['delivery_date'] ) );
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
                WHERE p.post_type = %s
                AND pm.meta_key = %s
                ORDER BY delivery_date ASC";
        $sql .= " LIMIT %d";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
        $result = $wpdb->get_results( $wpdb->prepare( $sql, 'shop_order', '_wooproddel_delivery_date', $per_page ), ARRAY_A );
		
		// Escape/Sanitize all the data before returning.
		foreach ( $result as &$item ) {
			foreach ( $item as $key => &$value ) {
				$value = sanitize_text_field( $value );
			}
		}
		
        return $result;
    }

    public static function record_count() {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID
                WHERE p.post_type = %s
                AND pm.meta_key = %s";
        return $wpdb->get_var( $wpdb->prepare( $sql, 'shop_order', '_wooproddel_delivery_date' ) );
    }
}


?>