<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+. This script is loaded by admin/init_includes/init_usu_admin.php 
 * on an initial installation of the plugin or an upgrade from a version prior to v3.0.1.
 *
 * @copyright Copyright 2019, 2023 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// -----
// Defining an array containing the plugin's configuration values.  Note that all the configuration
// settings start with USU_.
//
// Note: The default values might be modified if the store has a previous installation of 'Ultimate SEO' or a version
// of 'Ultimate URLs' prior to v3.0.0.
//
$usu_install_config = [
    'VERSION' => ['configuration_value' => '0.0.0', 'set_function' => 'trim('],
    'ENABLED' => ['configuration_value' => 'false', 'values' => ['false', 'true']],
    'DEBUG' => ['configuration_value' => 'false', 'values' => ['false', 'true']],
    'CPATH' => ['configuration_value' => 'auto', 'values' => ['auto', 'off']],
    'END' => ['configuration_value' => '.html'],
    'FORMAT' => ['configuration_value' => 'original', 'values' => ['original', 'parent']],
    'CATEGORY_DIR' => ['configuration_value' => 'short', 'values' => ['short', 'full', 'off']],
    'REMOVE_CHARS' => ['configuration_value' => 'punctuation', 'values' => ['punctuation', 'non-alphanumerical']],
    'FILTER_PCRE' => ['configuration_value' => ''],
    'FILTER_SHORT_WORDS' => ['configuration_value' => '0'],
    'FILTER_PAGES' => ['configuration_value' => 'index, product_info, product_music_info, document_general_info, document_product_info, product_free_shipping_info, products_new, products_all, shopping_cart, featured_products, specials, contact_us, conditions, privacy, reviews, shippinginfo, faqs_all, site_map, gv_faq, discount_coupon, page, page_2, page_3, page_4'],
    'ENGINE' => ['configuration_value' => 'rewrite', 'values' => ['rewrite']],
    'REDIRECT' => ['configuration_value' => 'false', 'values' => ['false', 'true']],
];

// -----
// We'll log the values for the to-be-created database configuration after each of the checking steps.
//
$usu_install_filename = DIR_FS_LOGS . '/usu-install-' . date('Ymd-His') . '.log';
error_log('Original configuration values: ' . PHP_EOL . json_encode($usu_install_config) . PHP_EOL, 3, $usu_install_filename);

// -----
// Check for any previous installation of 'Ultimate SEO', modifying the to-be-initialized settings for 'Ultimate URLs'
// if any are found.  The processing brought in will update the $usu_install_config default values as appropriate and
// remove all configuration settings associated with 'Ultimate SEO'.
//
require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_update_from_ultimate_seo.php';
error_log(PHP_EOL . 'After Ultimate SEO check: ' . PHP_EOL . json_encode($usu_install_config) . PHP_EOL, 3, $usu_install_filename);

// -----
// Check for the presence of each of the USU_* configuration settings, using those previous settings as an overriding
// value for the regeneration of USU's configuration.
//
foreach ($usu_install_config as $usu_key => $values) {
    if ($usu_key == 'VERSION') {
        continue;
    }
    if (defined("USU_$usu_key")) {
        $usu_install_config[$usu_key]['configuration_value'] = constant("USU_$usu_key");
    }
}
error_log(PHP_EOL . 'After Ultimate URLs check: ' . PHP_EOL . json_encode($usu_install_config) . PHP_EOL, 3, $usu_install_filename);

// -----
// Now, sanitize each of the selection-related settings to ensure that valid values are present.  If the possible values for
// a setting are indicated, check that the current setting is "in range"; if not, default that setting to the first
// value in the list.
//
foreach ($usu_install_config as $usu_key => $values) {
    if ($usu_key === 'FILTER_SHORT_WORDS') {
        if (!ctype_digit($values['configuration_value']) || ((int)$values['configuration_value']) < 0) {
            $usu_install_config[$usu_key]['configuration_value'] = '0';
        }
    } elseif (!isset($values['values'])) {
        continue;
    } else {
        if (!in_array($values['configuration_value'], $values['values'])) {
            $usu_install_config[$usu_key]['configuration_value'] = $values['values'][0];
        }
        if ($usu_key === 'CATEGORY_DIR' && $values['configuration_value'] === 'full') {
            $usu_install_config['FORMAT']['configuration_value'] = 'original';
        }
    }
}
error_log(PHP_EOL . 'After sanitization: ' . PHP_EOL . json_encode($usu_install_config) . PHP_EOL, 3, $usu_install_filename);

// -----
// Remove any previous USU configuration; the required updates will be performed next, using defaults based
// on the previous checks.
//
$db->Execute(
    "DELETE FROM " . TABLE_CONFIGURATION . "
      WHERE configuration_key LIKE 'USU_%'"
);
$db->Execute(
    "DROP TABLE IF EXISTS " . TABLE_USU_CACHE
);
if (zen_page_key_exists('configUltimateSEO')) {
    zen_deregister_admin_pages('configUltimateSEO');
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
$default_data = [
    'configuration_group_id' => $cgi,
    'sort_order' => 0,
    'date_added' => 'now()'
];
foreach ($usu_install_config as $key => $data) {
    // -----
    // If the setting has selectable values, build up the 'set_function' value based on the array of
    // possible values, e.g. 'zen_cfg_select_option([\'true\', \'false\'],'
    //
    if (isset($data['values'])) {
        $data['set_function'] = "zen_cfg_select_option(['" . implode("', '", $data['values']) . "'],";
        unset($data['values']);
    }
    
    // -----
    // Now, create the setting's key, title and description (the title and description are sourced
    // from the 'usu_configuration.php' that was previously loaded.
    //
    $key = "USU_$key";
    $key_data = [
        'configuration_key' => $key,
        'configuration_title' => constant($key . '_TITLE'),
        'configuration_description' => constant($key . '_DESCRIPTION')
    ];
    $usu_config = array_merge($default_data, $data, $key_data);
    $default_data['sort_order'] += 10;
    zen_db_perform(TABLE_CONFIGURATION, $usu_config);
}

// -----
// Record the plugin's configuration-access in the admin pages.
//
zen_register_admin_page('configUltimateSEO', 'BOX_CONFIGURATION_USU', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y', $cgi);

