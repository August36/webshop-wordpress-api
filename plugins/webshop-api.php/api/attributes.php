<?php

// ==========================================
// ATTRIBUTES VALUES ENDPOINTS
// ==========================================

add_action('rest_api_init', function () {

    // Get all attributes values
    register_rest_route('api', '/attributes-values', [
        'methods' => 'GET',
        'callback' => 'get_attributes_values',
        'permission_callback' => '__return_true',
    ]);

    // Get single attributes value
    register_rest_route('api', '/attributes-values/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'get_attributes_value',
        'permission_callback' => '__return_true',
    ]);

    // Create attributes value
    register_rest_route('api', '/attributes-values', [
        'methods' => 'POST',
        'callback' => 'create_attributes_value',
        'permission_callback' => 'webshop_can_manage_catalog',
    ]);

    // Update attributes value
    register_rest_route('api', '/attributes-values/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'update_attributes_value',
        'permission_callback' => 'webshop_can_manage_catalog',
    ]);

    // Delete attributes value
    register_rest_route('api', '/attributes-values/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'delete_attributes_value',
        'permission_callback' => 'webshop_can_manage_catalog',
    ]);

});


// ==========================================
// GET ALL ATTRIBUTES VALUES
// ==========================================

function get_attributes_values() {

    $attributes = get_posts([
        'post_type' => 'product_attribute',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    $result = [];

    foreach ($attributes as $attribute) {
        $result[] = [
            'attributes_id' => $attribute->ID,
            'attributes_name' => $attribute->post_title,
            'description' => $attribute->post_content,
            'data_type' => get_post_meta($attribute->ID, 'data_type', true),
        ];
    }

    return new WP_REST_Response([
        'attributesValues' => $result
    ], 200);
}

// ==========================================
// GET SINGLE ATTRIBUTES VALUE
// ==========================================

function get_attributes_value($request) {

    $id = absint($request['id']);
    $attribute = get_post($id);

    if (!$attribute || $attribute->post_type !== 'product_attribute') {
        return new WP_REST_Response([
            'message' => 'Attribute value not found'
        ], 404);
    }

    return new WP_REST_Response([
        'attributesValue' => [
            'attributes_id' => $attribute->ID,
            'attributes_name' => $attribute->post_title,
            'description' => $attribute->post_content,
            'data_type' => get_post_meta($attribute->ID, 'data_type', true),
        ]
    ], 200);
}

// ==========================================
// CREATE ATTRIBUTES VALUE
// ==========================================

function create_attributes_value($request) {

    $data = $request->get_json_params();

    if (empty($data['attributes_name']) || empty($data['data_type'])) {
        return new WP_REST_Response([
            'message' => 'Attributes name and data type are required.'
        ], 400);
    }

    $attribute_id = wp_insert_post([
        'post_type' => 'product_attribute',
        'post_title' => sanitize_text_field($data['attributes_name']),
        'post_content' => sanitize_text_field($data['description'] ?? ''),
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($attribute_id)) {
        return new WP_REST_Response([
            'message' => $attribute_id->get_error_message()
        ], 400);
    }

    update_post_meta($attribute_id, 'data_type', sanitize_text_field($data['data_type']));

    return new WP_REST_Response([
        'attributesValue' => [
            'attributes_id' => $attribute_id,
            'attributes_name' => $data['attributes_name'],
            'description' => $data['description'] ?? '',
            'data_type' => $data['data_type'],
        ]
    ], 201);
}

// ==========================================
// UPDATE ATTRIBUTES VALUE
// ==========================================

function update_attributes_value($request) {

    $id = absint($request['id']);
    $data = $request->get_json_params();

    $attribute = get_post($id);
    if (!$attribute || $attribute->post_type !== 'product_attribute') {
        return new WP_REST_Response([
            'message' => 'Attribute value not found'
        ], 404);
    }

    if (empty($data['attributes_name']) || empty($data['data_type'])) {
        return new WP_REST_Response([
            'message' => 'Attributes name and data type are required.'
        ], 400);
    }

    wp_update_post([
        'ID' => $id,
        'post_title' => sanitize_text_field($data['attributes_name']),
        'post_content' => sanitize_text_field($data['description'] ?? ''),
    ]);

    update_post_meta($id, 'data_type', sanitize_text_field($data['data_type']));

    return new WP_REST_Response([
        'attributesValue' => [
            'attributes_id' => $id,
            'attributes_name' => $data['attributes_name'],
            'description' => $data['description'] ?? '',
            'data_type' => $data['data_type'],
        ]
    ], 200);
}

// ==========================================
// DELETE ATTRIBUTES VALUE
// ==========================================

function delete_attributes_value($request) {

    $id = absint($request['id']);
    $attribute = get_post($id);

    if (!$attribute || $attribute->post_type !== 'product_attribute') {
        return new WP_REST_Response([
            'message' => 'Attribute value not found'
        ], 404);
    }

    wp_delete_post($id, true);

    return new WP_REST_Response([
        'message' => 'Attribute value deleted successfully'
    ], 200);
}
