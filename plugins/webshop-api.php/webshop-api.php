
<?php
/**
 * Plugin Name: Webshop API
 * Description: Custom REST API for our webshop project.
 * Version: 1.0
 * Author: Webshop Group
 */

if (!defined('ABSPATH')) {
    exit;
}


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


// ==========================================
// 4. PRODUCT META BOX
// ==========================================

add_action('add_meta_boxes', function () {

    add_meta_box(
        'product_details',
        'Product Details',
        'render_product_fields',
        'product',
        'normal',
        'default'
    );

});


function render_product_fields($post) {

    $price = get_post_meta($post->ID, 'price', true);
    $rating = get_post_meta($post->ID, 'rating', true);
    $brand = get_post_meta($post->ID, 'brand', true);

    wp_nonce_field('save_product', 'product_nonce');

    ?>

    <p>
        <label>Price:</label><br>
        <input type="number" name="price"
            value="<?php echo esc_attr($price); ?>"
            step="0.01">
    </p>

    <p>
        <label>Rating:</label><br>
        <input type="number" name="rating"
            value="<?php echo esc_attr($rating); ?>"
            step="0.1" min="0" max="5">
    </p>

    <p>
        <label>Brand:</label><br>
        <input type="text" name="brand"
            value="<?php echo esc_attr($brand); ?>">
    </p>

    <?php

    // Attribute values belonging to this product
    $attributes = get_posts([
        'post_type' => 'product_attribute',
        'numberposts' => -1,
        'post_status' => 'publish',
    ]);

    $values = get_post_meta($post->ID, 'attribute_values', true);

    if (!is_array($values)) {
        $values = [];
    }

    foreach ($attributes as $attribute) {

        $attribute_id = $attribute->ID;
        $value = $values[$attribute_id] ?? [];

        ?>

        <hr>

        <strong>
            <?php echo esc_html($attribute->post_title); ?>
        </strong>

        <p>
            <label>Value:</label><br>
            <input type="text"
                name="attribute_values[<?php echo esc_attr($attribute_id); ?>][value]"
                value="<?php echo esc_attr($value['value'] ?? ''); ?>">
        </p>

        <p>
            <label>Min Value:</label><br>
            <input type="number" step="0.001"
                name="attribute_values[<?php echo esc_attr($attribute_id); ?>][min]"
                value="<?php echo esc_attr($value['min'] ?? ''); ?>">
        </p>

        <p>
            <label>Max Value:</label><br>
            <input type="number" step="0.001"
                name="attribute_values[<?php echo esc_attr($attribute_id); ?>][max]"
                value="<?php echo esc_attr($value['max'] ?? ''); ?>">
        </p>

        <?php
    }
}


// ==========================================
// 5. SAVE PRODUCT DATA
// ==========================================

add_action('save_post_product', function ($post_id) {

    if (
        !isset($_POST['product_nonce']) ||
        !wp_verify_nonce($_POST['product_nonce'], 'save_product')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'price' => 'sanitize_text_field',
        'rating' => 'sanitize_text_field',
        'brand' => 'sanitize_text_field',
    ];

    foreach ($fields as $field => $sanitizer) {
        if (isset($_POST[$field])) {
            update_post_meta(
                $post_id,
                $field,
                $sanitizer(wp_unslash($_POST[$field]))
            );
        }
    }

    if (isset($_POST['attribute_values']) &&
        is_array($_POST['attribute_values'])) {

        $values = [];

        foreach ($_POST['attribute_values'] as $attribute_id => $value) {

            $attribute_id = absint($attribute_id);

            if (!$attribute_id ||
                get_post_type($attribute_id) !== 'product_attribute') {
                continue;
            }

            $values[$attribute_id] = [
                'value' => sanitize_text_field(
                    $value['value'] ?? ''
                ),
                'min' => sanitize_text_field(
                    $value['min'] ?? ''
                ),
                'max' => sanitize_text_field(
                    $value['max'] ?? ''
                ),
            ];
        }

        update_post_meta($post_id, 'attribute_values', $values);
    }

});


// ==========================================
// 6. ATTRIBUTE META BOX
// ==========================================

add_action('add_meta_boxes', function () {

    add_meta_box(
        'attribute_details',
        'Attribute Details',
        'render_attribute_fields',
        'product_attribute',
        'normal',
        'default'
    );

});


function render_attribute_fields($post) {

    $data_type = get_post_meta($post->ID, 'data_type', true);

    wp_nonce_field('save_attribute', 'attribute_nonce');

    ?>

    <label>Data Type:</label>

    <select name="data_type">
        <option value="varchar" <?php selected($data_type, 'varchar'); ?>>
            Varchar
        </option>

        <option value="decimal" <?php selected($data_type, 'decimal'); ?>>
            Decimal
        </option>

    </select>

    <?php
}


// Save attribute
add_action('save_post_product_attribute', function ($post_id) {

    if (
        !isset($_POST['attribute_nonce']) ||
        !wp_verify_nonce($_POST['attribute_nonce'], 'save_attribute')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['data_type'])) {
        $allowed = ['varchar', 'decimal'];
        $type = sanitize_text_field(wp_unslash($_POST['data_type']));

        if (in_array($type, $allowed, true)) {
            update_post_meta($post_id, 'data_type', $type);
        }
    }

});


// ==========================================
// 7. ORDER META BOX
// ==========================================

add_action('add_meta_boxes', function () {

    add_meta_box(
        'order_details',
        'Order Details',
        'render_order_fields',
        'shop_order',
        'normal',
        'default'
    );

});


function render_order_fields($post) {

    $user_id = get_post_meta($post->ID, 'user_id', true);
    $order_date = get_post_meta($post->ID, 'order_date', true);
    $total_amount = get_post_meta($post->ID, 'total_amount', true);
    $status = get_post_meta($post->ID, 'order_status', true);
    $items = get_post_meta($post->ID, 'order_items', true);

    wp_nonce_field('save_order', 'order_nonce');

    ?>

    <p>
        <label>User:</label><br>

        <select name="user_id">
            <option value="">Select user</option>

            <?php
            $users = get_users();

            foreach ($users as $user) {
                ?>
                <option value="<?php echo esc_attr($user->ID); ?>"
                    <?php selected($user_id, $user->ID); ?>>
                    <?php echo esc_html($user->user_email); ?>
                </option>
                <?php
            }
            ?>

        </select>
    </p>

    <p>
        <label>Order Date:</label><br>
        <input type="datetime-local" name="order_date"
            value="<?php echo esc_attr($order_date); ?>">
    </p>

    <p>
        <label>Total Amount:</label><br>
        <input type="number" step="0.01" name="total_amount"
            value="<?php echo esc_attr($total_amount); ?>">
    </p>

    <p>
        <label>Status:</label><br>

        <select name="order_status">
            <?php
            $statuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];

            foreach ($statuses as $option) {
                ?>
                <option value="<?php echo esc_attr($option); ?>"
                    <?php selected($status, $option); ?>>
                    <?php echo esc_html($option); ?>
                </option>
                <?php
            }
            ?>
        </select>
    </p>

    <p>
        <label>Order Items (JSON):</label><br>

        <textarea name="order_items" rows="6" style="width:100%"><?php
            echo esc_textarea(
                is_array($items)
                    ? wp_json_encode($items, JSON_PRETTY_PRINT)
                    : ''
            );
        ?></textarea>
    </p>

    <p>
        Use JSON format, for example:
        <code>[{"product_id":7,"amount":2}]</code>
    </p>

    <?php
}


// Save order
add_action('save_post_shop_order', function ($post_id) {

    if (
        !isset($_POST['order_nonce']) ||
        !wp_verify_nonce($_POST['order_nonce'], 'save_order')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = [
        'user_id' => 'absint',
        'order_date' => 'sanitize_text_field',
        'total_amount' => 'sanitize_text_field',
        'order_status' => 'sanitize_text_field',
    ];

    foreach ($fields as $field => $sanitizer) {
        if (isset($_POST[$field])) {
            update_post_meta(
                $post_id,
                $field,
                $sanitizer(wp_unslash($_POST[$field]))
            );
        }
    }

    if (isset($_POST['order_items'])) {

        $items = json_decode(
            wp_unslash($_POST['order_items']),
            true
        );

        if (is_array($items)) {

            $valid_items = [];

            foreach ($items as $item) {

                $product_id = absint($item['product_id'] ?? 0);
                $amount = absint($item['amount'] ?? 0);

                if (
                    $product_id &&
                    get_post_type($product_id) === 'product' &&
                    $amount > 0
                ) {
                    $valid_items[] = [
                        'product_id' => $product_id,
                        'amount' => $amount,
                    ];
                }
            }

            update_post_meta($post_id, 'order_items', $valid_items);
        }
    }

});


// ==========================================
// 8. USER PROFILE FIELDS
// ==========================================

add_action('show_user_profile', 'render_webshop_user_fields');
add_action('edit_user_profile', 'render_webshop_user_fields');

function render_webshop_user_fields($user) {

    ?>

    <h3>Webshop User Details</h3>

    <table class="form-table">

        <tr>
            <th><label for="phone">Phone</label></th>
            <td>
                <input type="text" name="phone" id="phone"
                    value="<?php echo esc_attr(get_user_meta($user->ID, 'phone', true)); ?>">
            </td>
        </tr>

        <tr>
            <th><label for="is_employee">Is Employee</label></th>
            <td>
                <input type="checkbox" name="is_employee" id="is_employee"
                    value="1"
                    <?php checked(get_user_meta($user->ID, 'is_employee', true), '1'); ?>>
            </td>
        </tr>

    </table>

    <?php
}


add_action('personal_options_update', 'save_webshop_user_fields');
add_action('edit_user_profile_update', 'save_webshop_user_fields');

function save_webshop_user_fields($user_id) {

    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    if (isset($_POST['phone'])) {
        update_user_meta(
            $user_id,
            'phone',
            sanitize_text_field(wp_unslash($_POST['phone']))
        );
    }

    update_user_meta(
        $user_id,
        'is_employee',
        isset($_POST['is_employee']) ? '1' : '0'
    );

}


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
