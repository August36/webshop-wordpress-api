<?php

// ==========================================
// 1. CUSTOM POST TYPES
// ==========================================

add_action('init', function () {

    // PRODUCT
    register_post_type('product', [
        'labels' => [
            'name' => 'Products',
            'singular_name' => 'Product',
        ],
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor'],
    ]);

    // ORDER
    register_post_type('shop_order', [
        'labels' => [
            'name' => 'Orders',
            'singular_name' => 'Order',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => ['title'],
    ]);

    // ATTRIBUTES
    register_post_type('product_attribute', [
        'labels' => [
            'name' => 'Attributes',
            'singular_name' => 'Attribute',
        ],
        'public' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor'],
    ]);

});
