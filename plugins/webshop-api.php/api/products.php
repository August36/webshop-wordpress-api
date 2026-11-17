<?php

// ==========================================
// 9. CUSTOM REST API ENDPOINT
// ==========================================

add_action('rest_api_init', function () {

    register_rest_route('myshop/v1', '/products', [
        'methods' => 'GET',
        'callback' => 'get_products',
        'permission_callback' => '__return_true',
    ]);

        // Hent ét bestemt produkt
    register_rest_route('myshop/v1', '/products/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_product',
        'permission_callback' => '__return_true',
    ]);

});


function get_products() {

    $products = get_posts([
        'post_type' => 'product',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    $data = [];

    foreach ($products as $product) {

        $attribute_values = get_post_meta(
            $product->ID,
            'attribute_values',
            true
        );

        $attributes = [];

        foreach ((array) $attribute_values as $attribute_id => $value) {

            $attribute = get_post($attribute_id);

            if (!$attribute || $attribute->post_type !== 'product_attribute') {
                continue;
            }

            $attributes[] = [
                'AttributesID' => (int) $attribute_id,
                'AttributesName' => $attribute->post_title,
                'Value' => $value['value'] ?? '',
                'MinValue' => $value['min'] ?? '',
                'MaxValue' => $value['max'] ?? '',
            ];
        }

        $data[] = [
            'ProductID' => $product->ID,
            'Name' => $product->post_title,
            'Rating' => get_post_meta($product->ID, 'rating', true),
            'Price' => get_post_meta($product->ID, 'price', true),
            'Description' => $product->post_content,
            'Brand' => get_post_meta($product->ID, 'brand', true),
            'Attributes' => $attributes,
        ];
    }

    return $data;
}

function get_product($request) {

    // Hent produktets ID fra URL'en
    $id = (int) $request['id'];

    // Find produktet i WordPress
    $product = get_post($id);

    // Kontroller om produktet findes
    if (!$product || $product->post_type !== 'product' || $product->post_status !== 'publish') {
        return new WP_Error(
            'product_not_found',
            'Product not found',
            ['status' => 404]
        );
    }

    // Hent produktets attributter
    $attribute_values = get_post_meta($product->ID, 'attribute_values', true);

    $attributes = [];

    foreach ((array) $attribute_values as $attribute_id => $value) {

        $attribute = get_post($attribute_id);

        if (!$attribute || $attribute->post_type !== 'product_attribute') {
            continue;
        }

        $attributes[] = [
            'AttributesID' => (int) $attribute_id,
            'AttributesName' => $attribute->post_title,
            'Value' => $value['value'] ?? '',
            'MinValue' => $value['min'] ?? '',
            'MaxValue' => $value['max'] ?? '',
        ];
    }

    // Returner produktet
    return [
        'ProductID' => $product->ID,
        'Name' => $product->post_title,
        'Rating' => get_post_meta($product->ID, 'rating', true),
        'Price' => get_post_meta($product->ID, 'price', true),
        'Description' => $product->post_content,
        'Brand' => get_post_meta($product->ID, 'brand', true),
        'Attributes' => $attributes,
    ];
}
