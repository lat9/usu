<?php
/**
 * Part of Ultimate URLs for Zen Cart. This script is loaded by admin/init_includes/init_usu_admin.php 
 * on an initial installation of the plugin or on an upgrade from a version that didn't register its
 * version in the database.
 *
 * @copyright Copyright 2019 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// Defining an array containing the plugin's configuration values.  Note that all the configuration
// settings start with USU_.
//
// Note: The default values might be modified if the store has a previous installation of 'Ultimate SEO'.
//
$usu_install_config = array(
    'VERSION' => array('configuration_value' => '0.0.0', 'set_function' => 'trim('),
    'ENABLED' => array('configuration_value' => 'false', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
    'DEBUG' => array('configuration_value' => 'false', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
    'CPATH' => array('configuration_value' => 'auto', 'use_function' => 'usu_check_cpath_option', 'set_function' => 'usu_set_cpath_option('),
    'END' => array('configuration_value' => '.html'),
    'FORMAT' => array('configuration_value' => 'original', 'use_function' => 'usu_check_url_format_option', 'set_function' => 'usu_set_url_format_option('),
    'CATEGORY_DIR' => array('configuration_value' => 'short', 'use_function' => 'usu_check_category_dir_option', 'set_function' => 'usu_set_category_dir_option('),
    'REMOVE_CHARS' => array('configuration_value' => 'punctuation', 'use_function' => 'usu_check_remove_chars_option', 'set_function' => 'usu_set_remove_chars_option('),
    'FILTER_PCRE' => array('configuration_value' => ''),
    'FILTER_SHORT_WORDS' => array('configuration_value' => '0', 'use_function' => 'usu_check_short_words'),
    'FILTER_PAGES' => array('configuration_value' => 'index, product_info, product_music_info, document_general_info, document_product_info, product_free_shipping_info, products_new, products_all, shopping_cart, featured_products, specials, contact_us, conditions, privacy, reviews, shippinginfo, faqs_all, site_map, gv_faq, discount_coupon, page, page_2, page_3, page_4'),
    'ENGINE' => array('configuration_value' => 'rewrite', 'set_function' => 'zen_cfg_select_option(array(\'rewrite\'),'),
    'REDIRECT' => array('configuration_value' => 'false', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
    'CACHE_GLOBAL' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_global_cache_option('),
    'CACHE_PRODUCTS' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'products\','),
    'CACHE_CATEGORIES' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'categories\','),
    'CACHE_MANUFACTURERS' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'manufacturers\','),
    'CACHE_EZ_PAGES' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'ez_pages\','),
    'CACHE_RESET' => array('configuration_value' => 'false', 'use_function' => 'usu_reset_cache_data', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
);

// -----
// If the main installation checking has determined that we're upgrading from 'Ultimate SEO',
// bring in some special handling to transition the old settings' values into the
// above array and remove the SEO_* configuration settings and database elements.
//
if ($ultimate_seo_found) {
    require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_update_from_ultimate_seo.php';
}

// -----
// The language-text associated with each of the above options is present in a language file
// that is loaded only upon the plugin's initial installation.
//
require DIR_WS_LANGUAGES . 'english/usu_configuration.php';

// -----
// Loop, creating each of the default configuration values, after setting some common
// values.
//
// Note: The configuration-group-id ($cgi) was previously determined by the plugin's base
// initialization script.
//
$default_data = array(
    'configuration_group_id' => $cgi,
    'sort_order' => 0,
    'date_added' => 'now()'
);
foreach ($usu_install_config as $key => $data) {
    $key = "USU_$key";
    $key_data = array(
        'configuration_key' => $key,
        'configuration_title' => constant($key . '_TITLE'),
        'configuration_description' => constant($key . '_DESCRIPTION')
    );
    $usu_config = array_merge($default_data, $data, $key_data);
    $default_data['sort_order'] += 10;
    
    if (defined($key)) {
        unset($usu_config['date_added']);
        $usu_config['last_modified'] = 'now()';
        zen_db_perform(TABLE_CONFIGURATION, $usu_config, 'update', "configuration_key = '$key' LIMIT 1");
    } else {
        zen_db_perform(TABLE_CONFIGURATION, $usu_config);
    }
}

// -----
// Record the plugin's configuration-access in the admin pages.
//
zen_register_admin_page('configUltimateSEO', 'BOX_CONFIGURATION_USU', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y', $cgi);

// -----
// Create the plugin's link-cache table.
//
$db->Execute(
    "CREATE TABLE `" . TABLE_USU_CACHE . "` (
        `cache_id` VARCHAR(32) NOT NULL default '',
        `cache_language_id` TINYINT(1) NOT NULL default '0',
        `cache_name` VARCHAR(255) NOT NULL default '',
        `cache_data` MEDIUMBLOB NOT NULL,
        `cache_global` TINYINT(1) NOT NULL default 1,
        `cache_gzip` TINYINT(1) NOT NULL default 1,
        `cache_method` VARCHAR(20) NOT NULL default 'RETURN',
        `cache_date` DATETIME NOT NULL default '0001-01-01 00:00:00',
        `cache_expires` DATETIME NOT NULL default '0001-01-01 00:00:00',
        PRIMARY KEY (`cache_id`,`cache_language_id`),
        KEY `cache_id` (`cache_id`),
        KEY `cache_language_id` (`cache_language_id`),
        KEY `cache_global` (`cache_global`)
    )"
);
