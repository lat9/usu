<?php
/**
 * Part of Ultimate URLs, v3.0.0+, for Zen Cart.
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
define('USU_CURRENT_VERSION', '3.0.0-beta2');
define('USU_CURRENT_UPDATE_DATE', '2019-02-15');

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

// -----
// Handle cache-resets for Categories, Products, EZ-Pages and Manufacturers.  There's
// no current notification available for EZ-Pages or manufacturers and the zc156 restructuring
// has broken the categories' and products' notifications, so we'll "punt" here.
//
// Notes:
// 1) $current_page is set by init_languages at CP 70.
// 2) FILENAME_CATEGORY_PRODUCT_LISTING is defined, starting with zc156.
//
if (!defined('FILENAME_CATEGORY_PRODUCT_LISTING')) {
    define('FILENAME_CATEGORY_PRODUCT_LISTING', 'category_product_listing');
}
$usu_current_page = pathinfo($current_page, PATHINFO_FILENAME);
$usu_action_pages = array(
    FILENAME_EZPAGES_ADMIN,
    FILENAME_MANUFACTURERS,
    FILENAME_PRODUCT,
    FILENAME_CATEGORIES,
    FILENAME_CATEGORY_PRODUCT_LISTING,
);
if (in_array($usu_current_page, $usu_action_pages) && !empty($_GET['action'])) {
    switch ($usu_current_page) {
        case FILENAME_EZPAGES_ADMIN:
            if (in_array($_GET['action'], array('update', 'deleteconfirm'))) {
                usu_reset_cache_data('ezpages');
            }
            break;
            
        case FILENAME_MANUFACTURERS:
            if (in_array($_GET['action'], array('save', 'deleteconfirm'))) {
                usu_reset_cache_data('manufacturers');
            }
            break;
            
        case FILENAME_PRODUCT:
            if ($_GET['action'] == 'update_product') {
                usu_reset_cache_data('products');
            }
            break;
            
        case FILENAME_CATEGORIES:
        case FILENAME_CATEGORY_PRODUCT_LISTING:
            switch ($_GET['action']) {
                case 'setflag':
                    usu_reset_cache_data('products');
                    break;
                    
                case 'update_category':
                case 'update_category_status':
                    usu_reset_cache_data('categories');
                    break;
                    
                default:
                    break;
            }
            break;

        default:
            break;
    }
}
