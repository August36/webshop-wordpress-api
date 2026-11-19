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

add_filter('rest_endpoints', function ($endpoints) {
    unset($endpoints['/jwt-auth/v1/token']);
    return $endpoints;
});

function webshop_can_manage_catalog() {
    return current_user_can('manage_options');
}

add_filter('jwt_auth_validate_decoded_token', function ($result, $token) {
    if (is_wp_error($result)) {
        return $result;
    }

    $user_id = (int) $token->data->user->id;
    $session = $token->webshop_session ?? null;
    if (!is_string($session) || !WP_Session_Tokens::get_instance($user_id)->verify($session)) {
        return new WP_Error('invalid_session', 'Token has expired or been revoked.', ['status' => 401]);
    }

    return $result;
}, 10, 2);


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

    if (!is_array($data) || !isset($data['email'], $data['password']) ||
        !is_string($data['email']) || !is_string($data['password']) ||
        !is_email($data['email']) || $data['password'] === '') {
        return new WP_REST_Response([
            'message' => 'A valid email and password are required.'
        ], 400);
    }

    if (!class_exists('Jwt_Auth_Public')) {
        return new WP_Error('jwt_unavailable', 'JWT authentication is not available.', ['status' => 503]);
    }

    $user = get_user_by('email', $data['email']);
    if (!$user) {
        return new WP_REST_Response(['message' => 'Invalid credentials'], 401);
    }

    $jwt_request = new WP_REST_Request('POST');
    $jwt_request->set_param('username', $user->user_login);
    $jwt_request->set_param('password', $data['password']);
    $add_session = function ($token, $user) {
        $token['webshop_session'] = WP_Session_Tokens::get_instance($user->ID)->create($token['exp']);
        return $token;
    };
    add_filter('jwt_auth_token_before_sign', $add_session, 10, 2);
    try {
        $jwt = (new Jwt_Auth_Public('jwt-auth', '1'))->generate_token($jwt_request);
    } finally {
        remove_filter('jwt_auth_token_before_sign', $add_session, 10);
    }

    if (is_wp_error($jwt)) {
        if (strpos($jwt->get_error_code(), '[jwt_auth] ') === 0) {
            return new WP_REST_Response(['message' => 'Invalid credentials'], 401);
        }
        return $jwt;
    }

    if (!is_array($jwt) || empty($jwt['token']) || !is_string($jwt['token'])) {
        return new WP_Error('jwt_invalid_response', 'Unable to create an authentication token.', ['status' => 500]);
    }

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
        'token' => $jwt['token']
    ], 200);

}

// ==========================================
// 4. LOGOUT USER
// ==========================================

function logout_user($request) {

    if (!class_exists('Jwt_Auth_Public')) {
        return new WP_Error('jwt_unavailable', 'JWT authentication is not available.', ['status' => 503]);
    }

    $header = $request->get_header('Authorization');
    if (!$header) {
        return new WP_Error('missing_token', 'A bearer token is required to log out.', ['status' => 400]);
    }

    $token = (new Jwt_Auth_Public('jwt-auth', '1'))->validate_token($request, $header);
    if (is_wp_error($token)) {
        return $token;
    }
    if ((int) $token->data->user->id !== get_current_user_id()) {
        return new WP_Error('invalid_session', 'Token does not belong to the current user.', ['status' => 403]);
    }

    WP_Session_Tokens::get_instance(get_current_user_id())->destroy($token->webshop_session);

    return new WP_REST_Response([
        'message' => 'Logged out successfully'
    ], 200);

}