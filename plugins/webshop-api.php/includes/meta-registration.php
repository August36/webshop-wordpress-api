<?php

// ==========================================
// 2. REGISTER POST META
// ==========================================

add_action('init', function () {

    // PRODUCT META
    register_post_meta('product', 'price', [
        'type' => 'number',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_post_meta('product', 'rating', [
        'type' => 'number',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_post_meta('product', 'brand', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    // ORDER META
    register_post_meta('shop_order', 'user_id', [
        'type' => 'integer',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_post_meta('shop_order', 'order_date', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_post_meta('shop_order', 'total_amount', [
        'type' => 'number',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_post_meta('shop_order', 'order_status', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    // ATTRIBUTE META
    register_post_meta('product_attribute', 'data_type', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

});

// ==========================================
// 3. USER META
// ==========================================

add_action('init', function () {

    register_meta('user', 'first_name', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_meta('user', 'last_name', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_meta('user', 'phone', [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
    ]);

    register_meta('user', 'is_employee', [
        'type' => 'boolean',
        'single' => true,
        'show_in_rest' => true,
    ]);

});
