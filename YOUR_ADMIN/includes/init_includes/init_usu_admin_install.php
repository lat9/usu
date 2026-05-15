<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+. This script is loaded by admin/init_includes/init_usu_admin.php 
 * on an initial installation of the plugin or an upgrade from a version prior to v3.0.1.
 *
 * Last updated: v4.0.1
 *
 * @copyright Copyright 2019, 2026 Cindy Merkin (vinosdefrutastropicales.com)
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
// The language-text associated with each of the above options is present in this array.
//
$titles_descriptions = [
    'USU_ENABLED_TITLE' => 'Enable alternate URLs?',
    'USU_ENABLED_DESCRIPTION' => 'This is a global setting that enables (<b>true</b>) or disables (<b>false</b>) the alternate URLs\' generation.',

    'USU_DEBUG_TITLE' => 'Enable debug log?',
    'USU_DEBUG_DESCRIPTION' => 'When enabled, additional debugging information is saved to log files (<code>/logs/usu-{adm-}yyyymmmdd-hhmmss.log</code>.<br><br>Enabling debugging may result in the creation of numerous log files and may adversely affect server performance. Only enable this if absolutely necessary!',

    'USU_CPATH_TITLE' => 'Generate cPath parameters',
    'USU_CPATH_DESCRIPTION' => 'By default Zen Cart generates a cPath parameter for product pages. These are used to keep linked products in the correct category. In automatic mode the cPath will only be added if needed.',

    'USU_END_TITLE' => 'Alternate URLs end with',
    'USU_END_DESCRIPTION' => 'If you want your URLs to end with a certain suffix add one here. Common suffixes are \'.html\', \'.htm\'. Leave this field blank to have no suffix added to generated URLs.',

    'USU_FORMAT_TITLE' => 'Format of alternate URLs',
    'USU_FORMAT_DESCRIPTION' => 'You can select from a list of commonly generated formats.<br><br><b>Original:</b><ul><li><i>Categories:</i> category-name-c-34</li><li><i>Products:</i> product-name-p-54</li></ul><b>Category Parent:</b><ul><li><i>Categories:</i> parent-category-name-c-34</li><li><i>Products:</i> parent-product-name-p-54</li></ul>',

    'USU_CATEGORY_DIR_TITLE' => 'Display categories as directories',
    'USU_CATEGORY_DIR_DESCRIPTION' => 'You can select from a list of commonly generated formats.<br><b>Off:</b> disables displaying categories as directories<br><br><b>Short:</b> use the settings from \'Format of alternate URLs\'<br><br><b>Full:</b> uses full category paths<br><br>',

    'USU_REMOVE_CHARS_TITLE' => 'Remove problematic characters',
    'USU_REMOVE_CHARS_DESCRIPTION' => 'This allows you remove certain problematic characters from the generated URLs.<br><br><i>non-alphanumerical:</i> removes all non-alphanumerical characters<br><i>punctuation:</i> removes all punctuation characters',

    'USU_FILTER_PCRE_TITLE' => 'Enter PCRE filter rules',
    'USU_FILTER_PCRE_DESCRIPTION' => 'This setting uses PCRE rules to filter URLs.<br><br>This filter is run before character conversions and stripping of special characters. If you want a dash - in your URLS, use a single space. To escape a character in the regular expression use \\\\ instead of a single \\.<br><br>The format <b>MUST</b> be in the form: <b>find1=>replace1,find2=>replace2</b>. ',

    'USU_FILTER_SHORT_WORDS_TITLE' => 'Filter short words',
    'USU_FILTER_SHORT_WORDS_DESCRIPTION' => 'This setting will filter &quot;short&quot; words, i.e. those with length less than or equal to the value specified, from any generated URLs.  Use the value <b>0</b> to include <em>all</em> words.',

    'USU_FILTER_PAGES_TITLE' => 'Limit alternate URLS to the following pages',
    'USU_FILTER_PAGES_DESCRIPTION' => 'You can limit the pages which will be rewritten by specifying them here. If no pages are specified all pages will be rewritten.<br><br>The format is a comma-delimited list (intervening spaces are OK) and <b>must</b> be in the form: <b>page1,page2,page3</b> or <b>page1, page2, page3</b>',

    'USU_ENGINE_TITLE' => 'Choose URL Engine',
    'USU_ENGINE_DESCRIPTION' => 'Choose which URL Engine to use.',

    'USU_REDIRECT_TITLE' => 'Enable automatic redirects?',
    'USU_REDIRECT_DESCRIPTION' => 'This will activate the automatic redirect code and send 301 headers for old to new URLs.',

    'USU_VERSION_TITLE' => 'Plugin Version',
    'USU_VERSION_DESCRIPTION' => 'The currently-installed version of <em>USU</em>.',
];

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
        'configuration_title' => $titles_descriptions[$key . '_TITLE'],
        'configuration_description' => $titles_descriptions[$key . '_DESCRIPTION'],
    ];
    $usu_config = array_merge($default_data, $data, $key_data);
    $default_data['sort_order'] += 10;
    zen_db_perform(TABLE_CONFIGURATION, $usu_config);
}

// -----
// Record the plugin's configuration-access in the admin pages.
//
zen_register_admin_page('configUltimateSEO', 'BOX_CONFIGURATION_USU', 'FILENAME_CONFIGURATION', "gID=$cgi", 'configuration', 'Y');

