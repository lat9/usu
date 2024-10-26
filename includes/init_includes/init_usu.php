<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2019 - 2024 Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// Remove the configured extension if present
if (isset($_GET['main_page'])) {
    if (defined('USU_END') && USU_END !== '' && is_string($_GET['main_page'])) {
        $pos = strrpos($_GET['main_page'], USU_END);
        if ($pos !== false) {
            $_GET['main_page'] = substr($_GET['main_page'], 0, $pos);
        }
    }

    if ($_GET['main_page'] === FILENAME_PRODUCT_INFO && isset($_GET['products_id'])) {
        // Retrieve the product type handler from the database
        $_GET['main_page'] = zen_get_info_page((int)$_GET['products_id']);
    }
}
