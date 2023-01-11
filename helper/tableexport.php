<?PHP
add_action('admin_post_wooproddel_export_to_excel', 'wooproddel_export_to_excel');

function wooproddel_export_to_excel(){
    header('Content-Type: application/vnd.ms-excel; charset=ISO-8859-1');
    header('Content-Disposition: attachment; filename="wooproddel-calendar-'.date('Y-m-d_H-i',time()).'.xls"');
    wooproddel_data_for_excel();
    exit();
}

// Now I am calling the function

//wooproddel_data_for_excel();

//the above function prints the data only. But the data is not formatted. 
// For formatting the data I wrote the below function.

function wooproddel_data_for_excel(){
    global $wpdb;
    $sql = "SELECT p.ID as order_id, p.post_date as order_date, pm.meta_value as delivery_date, CONCAT(pm2.meta_value, ' ', pm3.meta_value) as customer_name, 
            CASE WHEN DATEDIFF(pm.meta_value, CURRENT_DATE()) <= 2 THEN 'Yes' ELSE 'No' END AS two_days 
                        FROM {$wpdb->prefix}posts p 
                        INNER JOIN {$wpdb->prefix}postmeta pm ON pm.post_id = p.ID 
                        LEFT JOIN {$wpdb->prefix}postmeta pm2 ON pm2.post_id = p.ID AND pm2.meta_key = '_billing_first_name' 
                        LEFT JOIN {$wpdb->prefix}postmeta pm3 ON pm3.post_id = p.ID AND pm3.meta_key = '_billing_last_name' 
                        WHERE p.post_type = %s 
                        AND pm.meta_key like %s 
                        GROUP BY order_id";
    $sql2 = "SELECT oi.order_item_id as order_item_id,  oi.order_item_name as product_name, p.ID as order_id, p.post_date as order_date, oim.meta_value as delivery_date, CONCAT(pm2.meta_value, ' ', pm3.meta_value) as customer_name, 
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
                            GROUP BY order_id, oi.order_item_id";
    $resultorders = $wpdb->get_results( 
        $wpdb->prepare( 
            $sql, 
            'shop_order', 
            '_wooproddel_delivery_date'.'' 
        ), ARRAY_A );
                    
    $resultproducts = $wpdb->get_results( 
        $wpdb->prepare( 
            $sql2, 
            'shop_order', 
            'Delivery Date'.'' 
        ), ARRAY_A );
    
    $ordersarray = array_merge($resultorders,$resultproducts);
    // echo '<pre>';
    // print_r($ordersarray);
    // echo '</pre>';

    // Array of data for the CSV file
    $csvdata_array = array();
    // Add the header row to the array
    $csvdata_array[] = array(
        'Order ID',
        'Order Status',
        'Customer Name',
        'Order Date',
        'Delivery Date',
        'Product Delivery Date',
        'Product Name',
        'Order Item ID',
        'Within 2 Days?'
    );

    // Loop through the orders
    foreach ( $ordersarray as $order ) {
        // Sanitize the data
        $order_id = esc_html( $order['order_id'] );
        $order_status = esc_html( get_post_status( $order['order_id'] ) );
        $order_date = esc_html( $order['order_date'] );
        $customer_name = mb_convert_encoding(esc_html($order['customer_name']), "UTF-8", mb_detect_encoding(esc_html($order['customer_name']), "UTF-8, ISO-8859-7, ISO-8859-15", true));
        $delivery_date = esc_html( get_post_meta( $order['order_id'], '_wooproddel_delivery_date', true ) );
        $product_delivery_date = esc_html( $order['delivery_date'] );
        $product_name = esc_html( $order['product_name'] );
        $order_item_id = esc_html( $order['order_item_id'] );
        // Check if the delivery date is within 2 days
        $two_days_from_now = strtotime( '+2 days' );
        $within_two_days = '';
        // Check if the delivery date is within 2 days
        $two_days_from_now = strtotime( '+2 days' );
        $within_two_days = '';
         if ( $two_days_from_now >= strtotime( $delivery_date ) && strtotime( $delivery_date ) >= strtotime( '-2 days' ) ) {
        $within_two_days = 'Yes';
        } elseif ( strtotime( $delivery_date ) < strtotime( '-2 days' ) ) {
            $within_two_days = 'Past Order';
        } if ( $two_days_from_now >= strtotime( $product_delivery_date ) && strtotime( $product_delivery_date ) >= strtotime( '-2 days' ) ) {
        $within_two_days = 'Yes';
        } elseif ( strtotime( $product_delivery_date ) < strtotime( '-2 days' ) ) {
            $within_two_days = 'Past Order';
        }
        else {
            $within_two_days = 'No';
        }
        // Add the data row to the array
            $csvdata_array[] = array(
                $order_id,
                $order_status,
                $customer_name,
                $order_date,
                $delivery_date,
                $product_delivery_date,
                $product_name,
                $order_item_id,
                $within_two_days
            );
    }

    // Create the CSV file
    $csv_file_name = 'wooproddel-calendar-' . date('Y-m-d_H-i',time()) . '.csv';
    $csv_file_path = WP_CONTENT_DIR . '/uploads/' . $csv_file_name;
    $csv_file = fopen($csv_file_path, 'w');
    // Output each row of the CSV file
    foreach ( $csvdata_array as $row ) {
        fputcsv($csv_file, $row);
    }
    fclose($csv_file);
    unlink($csv_file_path);

    // Output the CSV file
    header('Content-Description: File Transfer');
    header('Content-Type: application/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $csv_file_name . '";');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    $output = fopen('php://output', 'w');
    foreach ( $csvdata_array as $row ) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();

}

?>