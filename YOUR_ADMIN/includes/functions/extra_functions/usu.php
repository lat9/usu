<?php
/**
 * Part of Ultimate URLs, v3.1.0+, for Zen Cart.
 *
 * @copyright Copyright 2019, 2023  Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

// -----
// Note, these two functions are present storefront-only in zc157, but are both storefront and
// admin for zc158 and later.
//
// Using the zc158 implementation if the function doesn't exist; the following definition is also
// present in zc158 and later.
//
if (!defined('TOPMOST_CATEGORY_PARENT_ID')) {
    define('TOPMOST_CATEGORY_PARENT_ID', '0');
}
if (!function_exists('zen_product_in_category')) {
    function zen_product_in_category($product_id, $cat_id)
    {
        global $db;
        $in_cat = false;
        $sql = "SELECT categories_id
                FROM " . TABLE_PRODUCTS_TO_CATEGORIES . "
                WHERE products_id = " . (int)$product_id;
        $categories = $db->Execute($sql);

        foreach ($categories as $category) {
            if ($category['categories_id'] == $cat_id) {
                return true;
            }
            $sql = "SELECT parent_id
                        FROM " . TABLE_CATEGORIES . "
                        WHERE categories_id = " . (int)$category['categories_id'];

            $parent_categories = $db->Execute($sql);

            foreach ($parent_categories as $parent) {
                if ($parent['parent_id'] != TOPMOST_CATEGORY_PARENT_ID) {
                    if (!$in_cat) {
                        $in_cat = zen_product_in_parent_category($product_id, $cat_id, $parent['parent_id']);
                    }
                    if ($in_cat) {
                        return $in_cat;
                    }
                }
            }
        }
        return $in_cat;
    }
}

if (!function_exists('zen_product_in_parent_category')) {
    function zen_product_in_parent_category($product_id, $cat_id, $parent_cat_id)
    {
        global $db;

        $in_cat = false;
        if ($cat_id == $parent_cat_id) {
            return true;
        }
        $sql = "SELECT parent_id
                    FROM " . TABLE_CATEGORIES . "
                    WHERE categories_id = " . (int)$parent_cat_id;

        $results = $db->Execute($sql);

        foreach ($results as $result) {
            if ($result['parent_id'] != TOPMOST_CATEGORY_PARENT_ID && !$in_cat) {
                $in_cat = zen_product_in_parent_category($product_id, $cat_id, $result['parent_id']);
            }
            if ($in_cat) {
                return $in_cat;
            }
        }
        return $in_cat;
    }
}
