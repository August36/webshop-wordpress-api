<?php

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
