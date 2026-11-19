<?php

// ==========================================
// 1. USER ENDPOINTS
// ==========================================

add_action('rest_api_init', function () {

    // Register a new user
    register_rest_route('api', '/register', [
        'methods' => 'POST',
        'callback' => 'register_user',
        'permission_callback' => '__return_true',
    ]);

    // Login user
    register_rest_route('api', '/login', [
        'methods' => 'POST',
        'callback' => 'login_user',
        'permission_callback' => '__return_true',
    ]);

    // Logout user
    register_rest_route('api', '/logout', [
        'methods' => 'POST',
        'callback' => 'logout_user',
        'permission_callback' => 'is_user_logged_in',
    ]);

});


// ==========================================
// 2. REGISTER USER
// ==========================================

function register_user($request) {

    $data = $request->get_json_params();

    // Check required fields
    if (
        empty($data['email']) ||
        empty($data['password'])
    ) {
        return new WP_REST_Response([
            'message' => 'Email and password are required.'
        ], 400);
    }

    // Sanitize input
    $email = sanitize_email($data['email']);
    $first_name = sanitize_text_field($data['first_name'] ?? '');
    $last_name = sanitize_text_field($data['last_name'] ?? '');
    $tlf = sanitize_text_field($data['tlf'] ?? '');

    // Validate email
    if (!is_email($email)) {
        return new WP_REST_Response([
            'message' => 'Invalid email address.'
        ], 400);
    }

    // Check if email already exists
    if (email_exists($email)) {
        return new WP_REST_Response([
            'message' => 'Email already exists.'
        ], 409);
    }

    // Create user with email as username
    $user_id = wp_insert_user([
        'user_login' => $email,
        'user_email' => $email,
        'user_pass' => $data['password'],
        'first_name' => $first_name,
        'last_name' => $last_name,
        'role' => 'customer',
    ]);

    // Check for errors
    if (is_wp_error($user_id)) {
        return new WP_REST_Response([
            'message' => $user_id->get_error_message()
        ], 400);
    }

    // Save additional user information
    update_user_meta($user_id, 'tlf', $tlf);
    update_user_meta($user_id, 'is_employee', false);

    // Get the created user
    $user = get_userdata($user_id);

    // Return created user
    return new WP_REST_Response([
        'user' => [
            'userID' => $user_id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'tlf' => $tlf,
            'is_employee' => false,
        ]
    ], 201);

}

// ==========================================
// 3. LOGIN USER
// ==========================================

function login_user($request) {

    $data = $request->get_json_params();

    // Check required fields
    if (empty($data['email']) || empty($data['password'])) {
        return new WP_REST_Response([
            'message' => 'Email and password are required.'
        ], 400);
    }

    // Sanitize input
    $email = sanitize_email($data['email']);
    $password = $data['password'];

    // Get user by email
    $user = get_user_by('email', $email);

    // Check if user exists and password is valid
    if (!$user || !wp_check_password($password, $user->user_pass, $user->ID)) {
        return new WP_REST_Response([
            'message' => 'Invalid credentials'
        ], 401);
    }

    // Generate a simple token (in production, use JWT or OAuth2)
    $token = bin2hex(random_bytes(32));

    // Store token in user meta
    update_user_meta($user->ID, 'api_token', $token);

    // Return user and token
    return new WP_REST_Response([
        'user' => [
            'userID' => $user->ID,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email,
            'tlf' => get_user_meta($user->ID, 'tlf', true),
            'is_employee' => (bool) get_user_meta($user->ID, 'is_employee', true),
        ],
        'token' => $token
    ], 200);

}

// ==========================================
// 4. LOGOUT USER
// ==========================================

function logout_user($request) {

    $user_id = get_current_user_id();

    // Delete the token
    delete_user_meta($user_id, 'api_token');

    return new WP_REST_Response([
        'message' => 'Logged out successfully'
    ], 200);

}