<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

define('TABLE_USU_CACHE', DB_PREFIX . 'usu_cache');

if (function_exists('zen_get_info_page')) {
    return;
} else {

    if (defined('IS_ADMIN_FLAG') && (IS_ADMIN_FLAG == 1)) {

        function zen_get_info_page($zf_product_id)
        {
            global $db;
            $sql = "select products_type from " . TABLE_PRODUCTS . " where products_id = '" . (int)$zf_product_id . "'";
            $zp_type = $db->Execute($sql);
            if ($zp_type->RecordCount() == 0) {
                return 'product_info';
            } else {
                $zp_product_type = $zp_type->fields['products_type'];
                $sql = "select type_handler from " . TABLE_PRODUCT_TYPES . " where type_id = '" . (int)$zp_product_type . "'";
                $zp_handler = $db->Execute($sql);
                return $zp_handler->fields['type_handler'] . '_info';
            }
        }
    }
}
