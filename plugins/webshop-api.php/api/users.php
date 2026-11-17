<?php

// ==========================================
// 1. USER ENDPOINTS
// ==========================================

add_action('rest_api_init', function () {

    // Register a new user
    register_rest_route('myshop/v1', '/users/register', [
        'methods' => 'POST',
        'callback' => 'register_user',
        'permission_callback' => '__return_true',
    ]);

});


// ==========================================
// 2. REGISTER USER
// ==========================================

function register_user($request) {

    $data = $request->get_json_params();

    // Check required fields
    if (
        empty($data['username']) ||
        empty($data['email']) ||
        empty($data['password'])
    ) {
        return new WP_Error(
            'missing_fields',
            'Username, email and password are required.',
            ['status' => 400]
        );
    }

    // Sanitize input
    $username = sanitize_user($data['username']);
    $email = sanitize_email($data['email']);

    // Validate email
    if (!is_email($email)) {
        return new WP_Error(
            'invalid_email',
            'Invalid email address.',
            ['status' => 400]
        );
    }

    // Check if username or email already exists
    if (username_exists($username) || email_exists($email)) {
        return new WP_Error(
            'user_exists',
            'Username or email already exists.',
            ['status' => 409]
        );
    }

    // Create user
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $data['password'],
        'first_name' => sanitize_text_field($data['first_name'] ?? ''),
        'last_name' => sanitize_text_field($data['last_name'] ?? ''),
        'role' => 'customer',
    ]);

    // Check for errors
    if (is_wp_error($user_id)) {
        return $user_id;
    }

    // Save additional user information
    update_user_meta(
        $user_id,
        'phone',
        sanitize_text_field($data['phone'] ?? '')
    );

    // New users are not employees by default
    update_user_meta($user_id, 'is_employee', false);

    // Return created user
    return new WP_REST_Response([
        'UID' => $user_id,
        'Username' => $username,
        'Email' => $email,
        'Firstname' => $data['first_name'] ?? '',
        'Lastname' => $data['last_name'] ?? '',
        'Phone' => $data['phone'] ?? '',
        'IsEmployee' => false,
    ], 201);

}