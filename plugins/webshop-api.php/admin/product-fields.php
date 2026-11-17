<?php

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
