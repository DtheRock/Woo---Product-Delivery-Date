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
        $sortable = array(
            'order_id'        => array( 'order_id', true ),
            'customer_name'   => array( 'customer_name', true ),
            'order_date'      => array( 'order_date', true ),
            'delivery_date'   => array( 'delivery_date', true ),
            'two_days'        => array( 'two_days', true )
        );
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();

        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'delivery_date';
        $order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
        $this->items = self::get_orders_with_delivery_dates( $per_page, $current_page, $orderby, $order );
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
        if ( $diff->invert==1) {
            return '<p style="background-color:red; color:white; text-align: center; max-width:50%;">'.esc_html__('Past Order', 'customize-product-delivery-date').'</p>';
        } 
        if ( $diff->days <= 2) {
            return '<p style="background-color:#FFA07A; text-align: center; max-width:50%;">'.esc_html__('Yes', 'customize-product-delivery-date').'</p>';
        }elseif ( $diff->days > 2 ) {
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

   private static function get_orders_with_delivery_dates( $per_page = 20, $page_number = 1, $orderby, $order ) {
        $is_activated = get_option( 'wooproddel_activation', '' );
        global $wpdb;
        $sql = "SELECT p.ID as order_id, p.post_date as order_date, pm.meta_value as delivery_date, CONCAT(pm2.meta_value, ' ', pm3.meta_value) as customer_name,
				CASE WHEN DATEDIFF(pm.meta_value, CURRENT_DATE()) <= 2 THEN 'Yes' ELSE 'No' END AS two_days
                FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID
                LEFT JOIN {$wpdb->prefix}postmeta pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_billing_first_name'
                LEFT JOIN {$wpdb->prefix}postmeta pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_billing_last_name'
                WHERE p.post_type = %s
                AND pm.meta_key like %s
                ORDER BY $orderby $order";
        $sql .= " LIMIT %d OFFSET %d";
        
        $resultorders = $wpdb->get_results( $wpdb->prepare( 
          $sql, 
          'shop_order', 
          '_wooproddel_delivery_date'.'', 
          $per_page, 
          ( $page_number - 1 ) * $per_page ), ARRAY_A );
        
        
        $sql2 ="SELECT oi.order_item_id as order_item_id, p.ID as order_id, p.post_date as order_date, oim.meta_value as delivery_date, CONCAT(pm2.meta_value, ' ', pm3.meta_value) as customer_name, 
                CASE WHEN DATEDIFF(pm.meta_value, CURRENT_DATE()) <= 2 THEN 'Yes' ELSE 'No' END AS two_days 
                        FROM {$wpdb->prefix}posts p 
                        INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID 
                        INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = p.ID 
                        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id 
                        LEFT JOIN {$wpdb->prefix}postmeta pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_billing_first_name' 
                        LEFT JOIN {$wpdb->prefix}postmeta pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_billing_last_name' 
                        WHERE p.post_type = %s 
                        AND oim.meta_key like %s
                        AND p.post_status != 'wc-split'
                        GROUP BY order_item_id
                        ORDER BY $orderby $order";
        $sql2 .= " LIMIT %d OFFSET %d";
        $resultproducts = $wpdb->get_results( $wpdb->prepare( 
          $sql2, 
          'shop_order', 
          'Delivery Date'.'', 
          $per_page, 
          ( $page_number - 1 ) * $per_page ), ARRAY_A );
        if ( woopdd_fs()->is_plan('pro') && $is_activated == 1 ) {
            $result = array_merge($resultorders, $resultproducts);
            
        }else{
            $result = $resultorders;
        }
        

		
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
                AND pm.meta_key LIKE %s";
        $sql2 = "SELECT COUNT(DISTINCT oim.order_item_id) FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID
                INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = p.ID 
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oim.order_item_id = oi.order_item_id 
                WHERE p.post_type = %s
                AND p.post_status != 'wc-split'
                AND oim.meta_key LIKE %s";
        $resultorders = $wpdb->get_var( $wpdb->prepare( $sql, 'shop_order', '_wooproddel_delivery_date' ) );
        $resultproducts = $wpdb->get_var( $wpdb->prepare( $sql2, 'shop_order', 'Delivery Date' ) );
       $result = intval($resultorders) + intval($resultproducts);
       return $result;
       echo $result;
    }
}