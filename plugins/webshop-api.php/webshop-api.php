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

// Load the existing plugin sections from separate files.
require_once __DIR__ . '/includes/post-types.php';
require_once __DIR__ . '/includes/meta-registration.php';

require_once __DIR__ . '/admin/product-fields.php';
require_once __DIR__ . '/admin/attribute-fields.php';
require_once __DIR__ . '/admin/order-fields.php';
require_once __DIR__ . '/admin/user-fields.php';

require_once __DIR__ . '/api/products.php';
