
<?php

// ==========================================
// 1. ORDER ENDPOINTS
// ==========================================

add_action('rest_api_init', function () {

    // Create a new order
    register_rest_route('myshop/v1', '/orders', [
        'methods' => 'POST',
        'callback' => 'create_order',
        'permission_callback' => 'is_user_logged_in',
    ]);

    // Get all orders for current user
    register_rest_route('myshop/v1', '/orders', [
        'methods' => 'GET',
        'callback' => 'get_user_orders',
        'permission_callback' => 'is_user_logged_in',
    ]);

    // Get a specific order
    register_rest_route('myshop/v1', '/orders/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_single_order',
        'permission_callback' => 'is_user_logged_in',
    ]);

});


// ==========================================
// 2. CREATE ORDER
// ==========================================

function create_order($request) {

    $data = $request->get_json_params();

    // Check if order contains items
    if (empty($data['items']) || !is_array($data['items'])) {
        return new WP_Error(
            'missing_items',
            'Order must contain at least one product.',
            ['status' => 400]
        );
    }

    // Get currently logged-in user
    $user_id = get_current_user_id();

    $order_items = [];
    $total_amount = 0;

    // Validate products and calculate total
    foreach ($data['items'] as $item) {

        $product_id = absint($item['product_id'] ?? 0);
        $amount = absint($item['amount'] ?? 0);

        if (
            !$product_id ||
            get_post_type($product_id) !== 'product' ||
            get_post_status($product_id) !== 'publish' ||
            !$amount
        ) {
            return new WP_Error(
                'invalid_product',
                'Invalid product or amount.',
                ['status' => 400]
            );
        }

        // Get product price
        $price = (float) get_post_meta($product_id, 'price', true);

        $total_amount += $price * $amount;

        $order_items[] = [
            'product_id' => $product_id,
            'amount' => $amount,
        ];
    }

    // Create order post
    $order_id = wp_insert_post([
        'post_type' => 'shop_order',
        'post_title' => 'Order - User ' . $user_id,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($order_id)) {
        return $order_id;
    }

    // Save order information
    update_post_meta($order_id, 'user_id', $user_id);
    update_post_meta($order_id, 'order_date', current_time('mysql'));
    update_post_meta($order_id, 'total_amount', round($total_amount, 2));
    update_post_meta($order_id, 'order_status', 'Pending');
    update_post_meta($order_id, 'order_items', $order_items);

    // Return created order
    return new WP_REST_Response([
        'OrderID' => $order_id,
        'UserID' => $user_id,
        'TotalAmount' => round($total_amount, 2),
        'Status' => 'Pending',
        'Items' => $order_items,
    ], 201);

}

// ==========================================
// 3. GET USER ORDERS
// ==========================================

function get_user_orders() {

    $user_id = get_current_user_id();

    // Find orders belonging to current user
    $orders = get_posts([
        'post_type' => 'shop_order',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_key' => 'user_id',
        'meta_value' => $user_id,
    ]);

    $result = [];

    foreach ($orders as $order) {

        $result[] = [
            'OrderID' => $order->ID,
            'UserID' => $user_id,
            'OrderDate' => get_post_meta($order->ID, 'order_date', true),
            'TotalAmount' => get_post_meta($order->ID, 'total_amount', true),
            'Status' => get_post_meta($order->ID, 'order_status', true),
            'Items' => get_post_meta($order->ID, 'order_items', true),
        ];
    }

    return $result;
}

// ==========================================
// 4. GET SINGLE ORDER
// ==========================================

function get_single_order($request) {

    $order_id = absint($request['id']);
    $user_id = get_current_user_id();

    // Get order
    $order = get_post($order_id);

    // Check if order exists
    if (!$order || $order->post_type !== 'shop_order') {
        return new WP_Error(
            'order_not_found',
            'Order not found.',
            ['status' => 404]
        );
    }

    // Check if order belongs to current user
    if ((int) get_post_meta($order_id, 'user_id', true) !== $user_id) {
        return new WP_Error(
            'forbidden',
            'You are not allowed to access this order.',
            ['status' => 403]
        );
    }

    // Return order information
    return [
        'OrderID' => $order_id,
        'UserID' => $user_id,
        'OrderDate' => get_post_meta($order_id, 'order_date', true),
        'TotalAmount' => get_post_meta($order_id, 'total_amount', true),
        'Status' => get_post_meta($order_id, 'order_status', true),
        'Items' => get_post_meta($order_id, 'order_items', true),
    ];
}