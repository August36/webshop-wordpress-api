<?php

// ==========================================
// 1. ORDER ENDPOINTS
// ==========================================

add_action('rest_api_init', function () {

    // Get all orders for user with UID
    register_rest_route('api', '/orders/(?P<uid>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_user_orders',
        'permission_callback' => 'is_user_logged_in',
    ]);

    // Create a new order for user with UID
    register_rest_route('api', '/orders/(?P<uid>\d+)', [
        'methods' => 'POST',
        'callback' => 'create_order',
        'permission_callback' => 'is_user_logged_in',
    ]);

    // Add item to existing order
    register_rest_route('api', '/orders/(?P<order_id>\d+)/items', [
        'methods' => 'POST',
        'callback' => 'add_order_item',
        'permission_callback' => 'is_user_logged_in',
    ]);

});


// ==========================================
// 2. GET USER ORDERS
// ==========================================

function get_user_orders($request) {

    $uid = absint($request['uid']);
    $current_user_id = get_current_user_id();

    // Check if user is trying to access their own orders
    if ((int) $uid !== $current_user_id) {
        return new WP_REST_Response([
            'message' => 'Unauthorized access'
        ], 403);
    }

    // Find orders belonging to user
    $orders = get_posts([
        'post_type' => 'shop_order',
        'post_status' => 'publish',
        'numberposts' => -1,
        'meta_key' => 'user_id',
        'meta_value' => $uid,
    ]);

    $result = [];

    foreach ($orders as $order) {

        $order_items_meta = get_post_meta($order->ID, 'order_items', true);
        $order_items = [];

        if (is_array($order_items_meta)) {
            foreach ($order_items_meta as $item) {
                $order_items[] = [
                    'product_id' => (int) $item['product_id'],
                    'amount' => (int) $item['amount'],
                ];
            }
        }

        $result[] = [
            'order_id' => $order->ID,
            'uid' => (int) $uid,
            'order_date' => get_post_meta($order->ID, 'order_date', true),
            'total_amount' => (float) get_post_meta($order->ID, 'total_amount', true),
            'status' => get_post_meta($order->ID, 'order_status', true),
            'orderItems' => $order_items,
        ];
    }

    return new WP_REST_Response([
        'orderItems' => $result
    ], 200);
}

// ==========================================
// 3. CREATE ORDER
// ==========================================

function create_order($request) {

    $uid = absint($request['uid']);
    $current_user_id = get_current_user_id();

    // Check if user is trying to create order for themselves
    if ((int) $uid !== $current_user_id) {
        return new WP_REST_Response([
            'message' => 'Unauthorized access'
        ], 403);
    }

    $data = $request->get_json_params();

    // Validate required fields
    if (empty($data['product_id']) || empty($data['quantity'])) {
        return new WP_REST_Response([
            'message' => 'Product ID and quantity are required.'
        ], 400);
    }

    $product_id = absint($data['product_id']);
    $quantity = absint($data['quantity']);

    // Validate product
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product' || $product->post_status !== 'publish') {
        return new WP_REST_Response([
            'message' => 'Invalid product.'
        ], 400);
    }

    // Get product price
    $price = (float) get_post_meta($product_id, 'price', true);
    $total_amount = $price * $quantity;

    // Create order post
    $order_id = wp_insert_post([
        'post_type' => 'shop_order',
        'post_title' => 'Order - User ' . $uid,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($order_id)) {
        return new WP_REST_Response([
            'message' => $order_id->get_error_message()
        ], 400);
    }

    // Save order information
    update_post_meta($order_id, 'user_id', $uid);
    update_post_meta($order_id, 'order_date', current_time('mysql'));
    update_post_meta($order_id, 'total_amount', round($total_amount, 2));
    update_post_meta($order_id, 'order_status', 'pending');
    update_post_meta($order_id, 'order_items', [
        [
            'product_id' => $product_id,
            'amount' => $quantity,
        ]
    ]);

    return new WP_REST_Response([
        'order' => [
            'order_id' => $order_id,
            'uid' => $uid,
            'product_id' => $product_id,
            'quantity' => $quantity,
        ]
    ], 201);
}

// ==========================================
// 4. ADD ITEM TO ORDER
// ==========================================

function add_order_item($request) {

    $order_id = absint($request['order_id']);
    $current_user_id = get_current_user_id();

    // Get order
    $order = get_post($order_id);
    if (!$order || $order->post_type !== 'shop_order') {
        return new WP_REST_Response([
            'message' => 'Order not found'
        ], 404);
    }

    // Check if order belongs to current user
    $order_uid = absint(get_post_meta($order_id, 'user_id', true));
    if ($order_uid !== $current_user_id) {
        return new WP_REST_Response([
            'message' => 'Unauthorized access'
        ], 403);
    }

    $data = $request->get_json_params();

    // Validate required fields
    if (empty($data['product_id']) || !isset($data['quantity'])) {
        return new WP_REST_Response([
            'message' => 'Product ID and quantity are required.'
        ], 400);
    }

    $product_id = absint($data['product_id']);
    $quantity = absint($data['quantity']);

    // Validate product
    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product' || $product->post_status !== 'publish') {
        return new WP_REST_Response([
            'message' => 'Invalid product.'
        ], 400);
    }

    // Get current order items
    $order_items = (array) get_post_meta($order_id, 'order_items', true);

    // Check if product already in order
    $found = false;
    foreach ($order_items as $key => $item) {
        if ((int) $item['product_id'] === $product_id) {
            if ($quantity === 0) {
                unset($order_items[$key]);
            } else {
                $order_items[$key]['amount'] = $quantity;
            }
            $found = true;
            break;
        }
    }

    // If not found and quantity > 0, add new item
    if (!$found && $quantity > 0) {
        $order_items[] = [
            'product_id' => $product_id,
            'amount' => $quantity,
        ];
    }

    // Recalculate total
    $total_amount = 0;
    foreach ($order_items as $item) {
        $price = (float) get_post_meta($item['product_id'], 'price', true);
        $total_amount += $price * $item['amount'];
    }

    // Update order
    update_post_meta($order_id, 'order_items', array_values($order_items));
    update_post_meta($order_id, 'total_amount', round($total_amount, 2));

    return new WP_REST_Response([
        'orderItem' => [
            'product_id' => $product_id,
            'amount' => $quantity,
        ]
    ], $quantity === 0 ? 200 : 201);
}