<?php
/**
 * Part of Ultimate URLs for Zen Cart. This script is loaded by admin/init_includes/init_usu_admin_upgrade.php when a previous
 * installation of 'Ultimate SEO' is detected.
 *
 * @copyright Copyright 2019 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

// -----
// If the "Ultimate SEO URLs" plugin is installed, in an 'older' form (i.e. pre-v2.215), there are
// some database tables and configuration values to be changed. 
//
if ($sniffer->table_exists(DB_PREFIX . 'seo_cache')) {
    $db->Execute("DROP TABLE " . DB_PREFIX . 'seo_cache');
}

// -----
// Remove the Ultimate SEO entry from the admin menu options.
//
if (zen_page_key_exists('UltimateSEO')) {
    zen_deregister_admin_pages('UltimateSEO');
}

// -----
// Create an array of configuration values to be modified to upgrade the SEO settings
// to their USU counterparts.  
//
// Notes:
// - Some of the 'migrated' settings need some special handling to make this a tad more readable.
// - If no 'value' element is identified in the array element, the SEO_ configuration setting is simply
//   transferred to its USU_ counterpart.
//
$add_product_cat_value = (defined('SEO_ADD_PRODUCT_CAT') && SEO_ADD_PRODUCT_CAT == 'true') ? 'parent' : 'original';
if (defined('SEO_ADD_CAT_PARENT') && SEO_ADD_CAT_PARENT == 'true') {
    $add_product_cat_value = 'original';
}

$pcre = '';
if (defined('SEO_URLS_FILTER_CHARS') && zen_not_null(SEO_URLS_FILTER_CHARS)) {
    $pcre_suffix = (defined('SEO_URLS_FILTER_PCRE') && SEO_URLS_FILTER_PCRE != '') ? ',' : '';
    $pcre = SEO_URLS_FILTER_CHARS . $pcre_suffix;
}

$config_updates = array(
    'SEO_REMOVE_ALL_SPEC_CHARS' => array(
        'new_name' => 'REMOVE_CHARS',
        'value' => (defined('SEO_REMOVE_ALL_SPEC_CHARS') && SEO_REMOVE_ALL_SPEC_CHARS == 'true') ? 'non-alphanumerical' : 'punctuation',
    ),
    'SEO_ADD_PRODUCT_CAT' => array(
        'new_name' => 'FORMAT',
        'value' => $add_product_cat_value,
    ),
    'SEO_ADD_CAT_PARENT' => array(
        'new_name' => 'CATEGORY_DIR',
        'value' => (defined('SEO_ADD_CAT_PARENT') && SEO_ADD_CAT_PARENT == 'false') ? 'off' : 'full',
    ),
    'SEO_ENABLED' => array(
        'new_name' => 'ENABLED',
    ),
    'SEO_ADD_CPATH_TO_PRODUCT_URLS' => array(
        'new_name' => 'CPATH',
        'value' => (defined('SEO_ADD_CPATH_TO_PRODUCT_URLS') && SEO_ADD_CPATH_TO_PRODUCT_URLS == 'false') ? 'off' : 'auto',
    ),
    'SEO_URL_CPATH' => array(
        'new_name' => 'CPATH',
    ),
    'SEO_URL_END' => array(
        'new_name' => 'END',
    ),
    'SEO_URL_FORMAT' => array(
        'new_name' => 'FORMAT',
        'value' => (defined('SEO_URL_CATEGORY_DIR') && defined('SEO_URL_FORMAT') && SEO_URL_CATEGORY_DIR == 'full' && SEO_URL_FORMAT == 'parent') ? 'original' : 'parent',
    ),
    'SEO_URL_CATEGORY_DIR' => array(
        'new_name' => 'CATEGORY_DIR',
    ),
    'SEO_URLS_REMOVE_CHARS' => array(
        'new_name' => 'REMOVE_CHARS',
        'value' => (defined('SEO_URLS_REMOVE_CHARS') && SEO_URLS_REMOVE_CHARS == 'alphanumerical') ? 'non-alphanumerical' : 'punctuation',
    ),
    'SEO_URLS_FILTER_PCRE' => array(
        'new_name' => 'FILTER_PCRE',
        'value' => $pcre . (defined('SEO_URLS_FILTER_PCRE') && SEO_URLS_FILTER_PCRE != '') ? SEO_URLS_FILTER_PCRE : '',
    ),
    'SEO_URLS_FILTER_SHORT_WORDS' => array(
        'new_name' => 'FILTER_SHORT_WORDS',
    ),
    'SEO_URLS_ONLY_IN' => array(
        'new_name' => 'FILTER_PAGES',
    ),
    'SEO_REWRITE_TYPE' => array(
        'new_name' => 'ENGINE',
        'value' => 'rewrite',
    ),
    'SEO_USE_REDIRECT' => array(
        'new_name' => 'REDIRECT',
    ),
    'SEO_USE_CACHE_GLOBAL' => array(
        'new_name' => 'CACHE_GLOBAL',
    ),
    'SEO_USE_CACHE_PRODUCTS' => array(
        'new_name' => 'CACHE_PRODUCTS',
    ),
    'SEO_USE_CACHE_CATEGORIES' => array(
        'new_name' => 'CACHE_CATEGORIES',
    ),
    'SEO_USE_CACHE_MANUFACTURERS' => array(
        'new_name' => 'CACHE_MANUFACTURERS',
    ),
    'SEO_USE_CACHE_EZ_PAGES' => array(
        'new_name' => 'CACHE_EZ_PAGES',
    ),
    'SEO_URLS_CACHE_RESET' => array(
        'new_name' => 'CACHE_RESET',
    ),
);

// -----
// Identify old configuration settings that are always removed.  This list
// might be updated by the following loop if other SEO_* type settings are
// detected.
//
$config_remove = array(
    'SEO_URLS_FILTER_CHARS',
    'USE_SEO_CACHE_ARTICLES',
    'USE_SEO_CACHE_INFO_PAGES',
    'SEO_URLS_USE_W3C_VALID',
    'SEO_REMOVE_ALL_SPEC_CHARS',
);

// -----
// Loop through the SEO_* configuration settings, modifying the to-be-created USU_
// settings' configuration values if an old setting is present.
//
foreach ($config_updates as $old_key => $values) {
    if (defined($old_key)) {
        $usu_install_config[$values['new_name']]['configuration_value'] = (isset($values['value'])) ? $values['value'] : constant($old_key);
        $config_remove[] = $old_key;
    }
}

// -----
// Remove no-longer-used settings.
//
$db->Execute(
    "DELETE FROM " . TABLE_CONFIGURATION . "
      WHERE configuration_key IN ('" . implode("', '", $config_remove) . "')"
);
