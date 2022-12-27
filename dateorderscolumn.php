<?php

// add action
add_action('manage_shop_order_posts_custom_column', 'wooproddel_admin_order_delivery_date_column', 20, 1);

// callback function
function wooproddel_admin_order_delivery_date_column($column) {
    global $post;
    $order = wc_get_order( $post->ID );
    if( $column == 'delivery_date' ) {
        $delivery_date = get_post_meta($post->ID, '_wooproddel_delivery_date', true );
		
		// Sanitize and escape data
		$delivery_date = esc_html( wp_strip_all_tags( $delivery_date ) );
		
		// Validate data
		if ( ! empty( $delivery_date ) ) {
			// Escape all variables and options
			echo esc_html( $delivery_date );
		}
    }
}

// add filter
add_filter('manage_edit-shop_order_columns', 'wooproddel_admin_order_delivery_date_column_header');

// callback function
function wooproddel_admin_order_delivery_date_column_header($columns) {
	// Internationalize text strings
    $columns['delivery_date'] = __('Delivery Date', 'wooproddel');
    return $columns;
}

?>