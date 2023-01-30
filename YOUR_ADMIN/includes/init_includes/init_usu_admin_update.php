<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+. This script is loaded by admin/init_includes/init_usu_admin.php 
 * on a change (i.e. update or install) to the plugin.
 *
 * @copyright Copyright 2019-2023 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

// -----
// Version-specific updates ...
//
switch (true) {
    // -----
    // v3.0.0:
    // - Register the plugin's uninstall script on the admin's extras menu dropdown.
    //
    case version_compare(USU_VERSION, '3.0.0', '<'):
        if (!zen_page_key_exists('usuUninstall')) {
            zen_register_admin_page('usuUninstall', 'BOX_CONFIGURATION_USU_UNINSTALL', 'FILENAME_USU_UNINSTALL', '', 'extras', 'Y');
        }
    // -----
    // v3.0.10:
    // - Remove the configuration page associated with older USU versions.
    //
    case version_compare(USU_VERSION, '3.0.10', '<'):               //-Fall through from above processing
        zen_deregister_admin_pages('configUSU');
    // -----
    // v3.1.0:
    // - Remove the cache-related settings, now cached on-page.
    // - Using 'zen_cfg_read_only' for the plugin's configured version
    // - Update 'filter pages' to use a textarea field (was a simple input).
    //
    case version_compare(USU_VERSION, '3.1.0', '<'):               //-Fall through from above processing
        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key LIKE 'USU_CACHE_%'"
        );
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET set_function = 'zen_cfg_read_only('
              WHERE configuration_key = 'USU_VERSION'"
        );
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION . "
                SET set_function = 'zen_cfg_textarea('
              WHERE configuration_key = 'USU_FILTER_PAGES'"
        );

    default:                                                        //-Fall through from above processing
        break;
}

// -----
// Update the configuration table to reflect the current version, if it's not already set, and
// note the version's release date as that setting's last-modified date.
//
$db->Execute(
    "UPDATE " . TABLE_CONFIGURATION . " 
        SET configuration_value = '" . USU_CURRENT_VERSION . "',
            last_modified = '" . USU_CURRENT_UPDATE_DATE . " 00:00:00'
      WHERE configuration_key = 'USU_VERSION'
      LIMIT 1"
);

// -----
// If not an initial installation (and a USU_VERSION was previously found), let the admin know
// that the plugin's been updated.
//
if (USU_VERSION != '0.0.0') {
    $messageStack->add(sprintf(USU_UPDATED_SUCCESS, USU_VERSION, USU_CURRENT_VERSION), 'success');
}
