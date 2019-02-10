<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2019 Cindy Merkin (vinosdefrutastropicales.com)
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
define('USU_CURRENT_VERSION', '3.0.0-beta1');
define('USU_CURRENT_UPDATE_DATE', '2019-02-09');

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
// Note: This section also detects if an old version of 'Ultimate SEO' is installed, setting
// a flag for use by the plugin's upgrade processing if so detected.
//
$configurationGroupTitle = 'Ultimate URLs';
$ultimate_seo_found = false;
$is_new_install = false;
$check = $db->Execute(
    "SELECT configuration_group_id
       FROM " . TABLE_CONFIGURATION_GROUP . "
      WHERE configuration_group_title = 'Ultimate SEO'
      LIMIT 1"
);
if (!$check->EOF) {
    $cgi = $check->fields['configuration_group_id'];
    $ultimate_seo_found = true;
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
        $is_new_install = true;
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
// Perform initial installation processes.
//
if (!defined('USU_VERSION')) {
    require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_install.php';
    $messageStack->add(sprintf(USU_INSTALLED_SUCCESS, USU_CURRENT_VERSION), 'success');
    
    define('USU_VERSION', '0.0.0');
}

// -----
// If a change in USU version is seen (including the initial installation), bring in
// the module's configuration-update processing.
//
if (USU_VERSION != USU_CURRENT_VERSION) {
    require DIR_WS_INCLUDES . 'init_includes/init_usu_admin_update.php';
}
