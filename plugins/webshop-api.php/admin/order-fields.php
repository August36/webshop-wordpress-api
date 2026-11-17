<?php

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
