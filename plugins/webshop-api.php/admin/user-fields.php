<?php

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
