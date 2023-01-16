<?php
/**
 * Part of Ultimate URLs for Zen Cart. This script is loaded by admin/init_includes/init_usu_admin_install.php
 * to clean up and/or migrate the settings from an installation of 'Ultimate SEO'.
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
$db->Execute("DROP TABLE IF EXISTS " . DB_PREFIX . 'seo_cache');

// -----
// Remove the Ultimate SEO entry from the admin menu options, if it's present.
//
if (zen_page_key_exists('UltimateSEO')) {
    zen_deregister_admin_pages('UltimateSEO');
}

// -----
// See if any 'Ultimate SEO' settings are in effect, carrying over those pre-existing settings
// into the to-be-installed 'Ultimate URLs' settings.
//
$url_format_value = ((defined('SEO_ADD_PRODUCT_CAT') && SEO_ADD_PRODUCT_CAT === 'true') || (defined('SEO_URL_FORMAT') && SEO_URL_FORMAT !== 'enable-original')) ? 'parent' : 'original';
$usu_install_config['FORMAT']['configuration_value'] = $url_format_value;

if (defined('SEO_URL_CATEGORY_DIR')) {
    $usu_install_config['CATEGORY_DIR']['configuration_value'] = (SEO_URL_CATEGORY_DIR === 'disabled') ? 'off' : ((SEO_URL_CATEGORY_DIR === 'enable-short') ? 'short' : 'full');
}

$pcre = (defined('SEO_URLS_FILTER_CHARS') && zen_not_null(SEO_URLS_FILTER_CHARS)) ? SEO_URLS_FILTER_CHARS : '';
$pcre_suffix = (defined('SEO_URLS_FILTER_PCRE') && SEO_URLS_FILTER_PCRE !== '') ? SEO_URLS_FILTER_PCRE : '';
if ($pcre !== '' || $pcre_suffix !== '') {
    $pcre_separator = ($pcre !== '' && $pcre_suffix !== '') ? ', ' : '';
    $usu_install_config['FILTER_PCRE']['configuration_value'] = $pcre . $pcre_separator . $pcre_suffix;
}

if (defined('SEO_REMOVE_ALL_SPEC_CHARS')) {
    $usu_install_config['REMOVE_CHARS']['configuration_value'] = (SEO_REMOVE_ALL_SPEC_CHARS === 'true') ? 'non-alphanumerical' : 'punctuation';
}
if (defined('SEO_URL_REMOVE_CHARS')) {
    $usu_install_config['REMOVE_CHARS']['configuration_value'] = (SEO_URL_REMOVE_CHARS === 'enable-non-alphanumerical') ? 'non-alphanumerical' : 'punctuation';
}

if (defined('SEO_ADD_CAT_PARENT')) {
    $usu_install_config['CATEGORY_DIR']['configuration_value'] = (SEO_ADD_CAT_PARENT === 'false') ? 'off' : 'full';
}

if (defined('SEO_ADD_CPATH_TO_PRODUCT_URLS')) {
    $usu_install_config['CPATH']['configuration_value'] = (SEO_ADD_CPATH_TO_PRODUCT_URLS === 'false') ? 'off' : 'auto';
}
if (defined('SEO_URL_CPATH')) {
    $usu_install_config['CPATH']['configuration_value'] = (SEO_URL_CPATH === 'enable-auto') ? 'auto' : 'off';
}

if (defined('SEO_URL_END')) {
    $usu_install_config['END']['configuration_value'] = SEO_URL_END;
}

if (defined('SEO_URLS_FILTER_SHORT_WORDS')) {
    $usu_install_config['FILTER_SHORT_WORDS']['configuration_value'] = SEO_URLS_FILTER_SHORT_WORDS;
}

if (defined('SEO_URLS_ONLY_IN') && SEO_URLS_ONLY_IN !== '') {
    $usu_install_config['FILTER_PAGES']['configuration_value'] = SEO_URLS_ONLY_IN;
}

// -----
// Check for and carry-over any true/false settings.
//
$true_false_updates = [
    'SEO_ENABLED' => 'ENABLED',
    'SEO_USE_REDIRECT' => 'REDIRECT',
    'SEO_USE_CACHE_GLOBAL' => 'CACHE_GLOBAL',
    'SEO_USE_CACHE_PRODUCTS' => 'CACHE_PRODUCTS',
    'SEO_USE_CACHE_CATEGORIES' => 'CACHE_CATEGORIES',
    'SEO_USE_CACHE_MANUFACTURERS' => 'CACHE_MANUFACTURERS',
    'SEO_USE_CACHE_EZ_PAGES' => 'CACHE_EZ_PAGES',
];
foreach ($true_false_updates as $seo_key => $usu_key) {
    if (defined($seo_key)) {
        $usu_install_config[$usu_key]['configuration_value'] = constant($seo_key);
    }
}

// -----
// Identify 'Ultimate SEO' configuration settings that are always removed; those starting
// with SEO_ are removed as well.
//
$config_remove = [
    'SEO_URLS_FILTER_CHARS',
    'USE_SEO_CACHE_ARTICLES',
    'USE_SEO_CACHE_INFO_PAGES',
    'SEO_URLS_USE_W3C_VALID',
    'SEO_REMOVE_ALL_SPEC_CHARS',
];
$db->Execute(
    "DELETE FROM " . TABLE_CONFIGURATION . "
      WHERE configuration_key IN ('" . implode("', '", $config_remove) . "')
         OR configuration_key LIKE 'SEO_%'"
);
