<?php
/**
 * Part of Ultimate URLs, v3.1.0+, for Zen Cart.
 *
 * @copyright Copyright 2019-2023 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit('Illegal Access');
}

// -----
// Define the plugin's current version and release date.  On an install or upgrade, the
// last_modified date for the USU_VERSION configuration setting is updated to reflect
// the current update-date.
//
define('USU_CURRENT_VERSION', '3.1.0');
define('USU_CURRENT_UPDATE_DATE', '2023-02-03');

// -----
// Wait until an admin is logged in before seeing if any initialization steps need to be performed.
// That ensures that "someone" will see the plugin's installation/update messages!
//
if (!isset($_SESSION['admin_id'])) {
    return;
}

// -----
// Locate (or create) the plugin's "Configuration Group", recording the configuration group's ID
// for use by the install/update processing.
//
$configurationGroupTitle = 'Ultimate URLs';
$check = $db->Execute(
    "SELECT configuration_group_id
       FROM " . TABLE_CONFIGURATION_GROUP . "
      WHERE configuration_group_title = 'Ultimate SEO'
      LIMIT 1"
);
if (!$check->EOF) {
    $cgi = $check->fields['configuration_group_id'];
    $db->Execute(
        "UPDATE " . TABLE_CONFIGURATION_GROUP . "
            SET configuration_group_title = '$configurationGroupTitle'
          WHERE configuration_group_id = $cgi
          LIMIT 1"
    );
} else {
    $check = $db->Execute(
        "SELECT configuration_group_id
           FROM " . TABLE_CONFIGURATION_GROUP . "
          WHERE configuration_group_title = '$configurationGroupTitle'
          LIMIT 1"
    );
    if (!$check->EOF) {
        $cgi = $check->fields['configuration_group_id'];
    } else {
        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . " 
                (configuration_group_title, configuration_group_description, sort_order, visible) 
             VALUES 
                ('$configurationGroupTitle', '$configurationGroupTitle Settings', '1', '1')"
        );
        $cgi = $db->Insert_ID(); 
        $db->Execute(
            "UPDATE " . TABLE_CONFIGURATION_GROUP . " 
                SET sort_order = $cgi 
              WHERE configuration_group_id = $cgi
              LIMIT 1"
        ); 
    }
}
unset($check);

// -----
// Perform initial installation processes if not previously installed OR if the
// previously-installed version was less than 3.0.1 (correcting install/upgrade issues
// possibly introduced by v3.0.0).
//
if (!defined('USU_VERSION') || (USU_VERSION !== USU_CURRENT_VERSION && version_compare(USU_VERSION, '3.0.1', '<'))) {
    require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_install.php';

    if (!defined('USU_VERSION')) {
        $messageStack->add(sprintf(USU_INSTALLED_SUCCESS, USU_CURRENT_VERSION), 'success');
        define('USU_VERSION', '0.0.0');
    }
}

// -----
// If a change in USU version is seen (including the initial installation), bring in
// the module's configuration-update processing.
//
if (USU_VERSION !== USU_CURRENT_VERSION) {
    require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_update.php';
}

// -----
// Perform some checks for option-dependent USU configuration changes and values.  For versions of USU
// prior to v3.0.1, these checks were performed within various 'usu_check_*' functions associated
// with a configuration key's 'use_function' settings.
//
// Notes:
// 1) $current_page is set by init_languages at CP 70.
//
$usu_current_page = pathinfo($current_page, PATHINFO_FILENAME);
if ($usu_current_page === FILENAME_CONFIGURATION && isset($_GET['gID']) && $_GET['gID'] === $cgi && isset($_GET['action']) && $_GET['action'] === 'save') {
    require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_config_changes.php';
}
