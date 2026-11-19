<?php

// ==========================================
// 9. CUSTOM REST API ENDPOINT
// ==========================================

add_action('rest_api_init', function () {

    register_rest_route('api', '/products', [
        'methods' => 'GET',
        'callback' => 'get_products',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('api', '/products/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_product',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('api', '/products', [
        'methods' => 'POST',
        'callback' => 'create_product',
        'permission_callback' => 'webshop_can_manage_catalog',
    ]);

    register_rest_route('api', '/products/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'update_product',
        'permission_callback' => 'webshop_can_manage_catalog',
    ]);

    register_rest_route('api', '/products/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_product',
        'permission_callback' => 'webshop_can_manage_catalog',
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
                'attributes_id' => (int) $attribute_id,
                'attributes_name' => $attribute->post_title,
                'value' => $value['value'] ?? '',
                'min_value' => $value['min'] ? (float) $value['min'] : null,
                'max_value' => $value['max'] ? (float) $value['max'] : null,
            ];
        }

        $data[] = [
            'product_id' => $product->ID,
            'name' => $product->post_title,
            'rating' => (float) get_post_meta($product->ID, 'rating', true),
            'price' => (float) get_post_meta($product->ID, 'price', true),
            'description' => $product->post_content,
            'mærke' => get_post_meta($product->ID, 'brand', true),
            'attributeValues' => $attributes,
        ];
    }

    return new WP_REST_Response([
        'products' => $data
    ], 200);
}

function get_product($request) {

    $id = (int) $request['id'];
    $product = get_post($id);

    if (!$product || $product->post_type !== 'product' || $product->post_status !== 'publish') {
        return new WP_REST_Response([
            'message' => 'Product not found'
        ], 404);
    }

    $attribute_values = get_post_meta($product->ID, 'attribute_values', true);

    $attributes = [];

    foreach ((array) $attribute_values as $attribute_id => $value) {

        $attribute = get_post($attribute_id);

        if (!$attribute || $attribute->post_type !== 'product_attribute') {
            continue;
        }

        $attributes[] = [
            'attributes_id' => (int) $attribute_id,
            'attributes_name' => $attribute->post_title,
            'value' => $value['value'] ?? '',
            'min_value' => $value['min'] ? (float) $value['min'] : null,
            'max_value' => $value['max'] ? (float) $value['max'] : null,
        ];
    }

    return new WP_REST_Response([
        'product' => [
            'product_id' => $product->ID,
            'name' => $product->post_title,
            'rating' => (float) get_post_meta($product->ID, 'rating', true),
            'price' => (float) get_post_meta($product->ID, 'price', true),
            'description' => $product->post_content,
            'mærke' => get_post_meta($product->ID, 'brand', true),
            'attributeValues' => $attributes,
        ]
    ], 200);
}

function create_product($request) {

    $data = $request->get_json_params();

    if (empty($data['name']) || empty($data['price']) || empty($data['description'])) {
        return new WP_REST_Response([
            'message' => 'Name, price, and description are required.'
        ], 400);
    }

    $product_id = wp_insert_post([
        'post_type' => 'product',
        'post_title' => sanitize_text_field($data['name']),
        'post_content' => sanitize_text_field($data['description']),
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($product_id)) {
        return new WP_REST_Response([
            'message' => $product_id->get_error_message()
        ], 400);
    }

    update_post_meta($product_id, 'price', (float) $data['price']);
    update_post_meta($product_id, 'rating', isset($data['rating']) ? (float) $data['rating'] : 0);
    update_post_meta($product_id, 'brand', sanitize_text_field($data['mærke'] ?? ''));

    // Handle attribute_values if provided
    if (!empty($data['attribute_values']) && is_array($data['attribute_values'])) {
        $attribute_values = [];
        foreach ($data['attribute_values'] as $attr) {
            if (!empty($attr['attributes_id'])) {
                $attribute_values[$attr['attributes_id']] = [
                    'value' => $attr['value'] ?? '',
                    'min' => $attr['min_value'] ?? '',
                    'max' => $attr['max_value'] ?? '',
                ];
            }
        }
        update_post_meta($product_id, 'attribute_values', $attribute_values);
    }

    return new WP_REST_Response([
        'product' => [
            'product_id' => $product_id,
            'name' => $data['name'],
            'rating' => isset($data['rating']) ? (float) $data['rating'] : 0,
            'price' => (float) $data['price'],
            'description' => $data['description'],
            'mærke' => $data['mærke'] ?? '',
        ]
    ], 201);
}

function update_product($request) {

    $id = (int) $request['id'];
    $data = $request->get_json_params();

    $product = get_post($id);
    if (!$product || $product->post_type !== 'product') {
        return new WP_REST_Response([
            'message' => 'Product not found'
        ], 404);
    }

    if (empty($data['name']) || empty($data['price']) || empty($data['description'])) {
        return new WP_REST_Response([
            'message' => 'Name, price, and description are required.'
        ], 400);
    }

    wp_update_post([
        'ID' => $id,
        'post_title' => sanitize_text_field($data['name']),
        'post_content' => sanitize_text_field($data['description']),
    ]);

    update_post_meta($id, 'price', (float) $data['price']);
    update_post_meta($id, 'rating', isset($data['rating']) ? (float) $data['rating'] : 0);
    update_post_meta($id, 'brand', sanitize_text_field($data['mærke'] ?? ''));

    return new WP_REST_Response([
        'product' => [
            'product_id' => $id,
            'name' => $data['name'],
            'rating' => isset($data['rating']) ? (float) $data['rating'] : 0,
            'price' => (float) $data['price'],
            'description' => $data['description'],
            'mærke' => $data['mærke'] ?? '',
        ]
    ], 200);
}

function delete_product($request) {

    $id = (int) $request['id'];
    $product = get_post($id);

    if (!$product || $product->post_type !== 'product') {
        return new WP_REST_Response([
            'message' => 'Product not found'
        ], 404);
    }

    wp_delete_post($id, true);

    return new WP_REST_Response([
        'message' => 'Product deleted successfully'
    ], 200);
}
