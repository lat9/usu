<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+.
 *
 * @copyright Copyright 2019, 2023  Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
require 'includes/application_top.php';

// -----
// If the admin has confirmed the removal of "Ultimate SEO URLs" ...
//
if (isset($_POST['action']) && $_POST['action'] === 'uninstall') {
    // -----
    // Honor the admin's choice of 'only database settings'.
    //
    if (!isset($_POST['db_only'])) {
        // -----
        // Build up a list of files to be removed.
        //
        $files_to_remove = [
            'storefront' => [
                'auto_loaders/config.seo.php',
                'auto_loaders/config.ultimate_seo.php',
                'auto_loaders/config.usu.php',
                'classes/seo.url.php',
                'classes/seo.install.php',
                'classes/usu.php',
                'classes/observers/UsuObserver.php',
                'extra_datafiles/seo.php',
                'extra_datafiles/usu.php',
                'init_includes/init_seo_config.php',
                'init_includes/init_usu.php',
            ],
           'admin_includes' => [
                'reset_seo_cache.php',
                'auto_loaders/config.seo.php',
                'auto_loaders/config.usu.php',
                'classes/usu_plugin.php',
                'classes/observers/UsuAdminObserver.php',
                'extra_datafiles/seo.php',
                'extra_datafiles/usu.php',
                'functions/extra_functions/seo.php',
                'functions/extra_functions/usu.php',
                'init_includes/init_seo_config.php',
                'init_includes/init_usu_admin.php',
                'init_includes/init_usu_admin_config_changes.php',
                'init_includes/init_usu_admin_install.php',
                'init_includes/init_usu_admin_update.php',
                'init_includes/init_usu_admin_update_from_ultimate_seo.php',
                'init_includes/init_usu_install.php',
                'languages/english/usu_configuration.php',
                'languages/english/usu_uninstall.php',
                'languages/english/extra_definitions/seo.php',
                'languages/english/extra_definitions/usu.php',
                'languages/english/modules/plugins/usu.php',
            ],
            'admin_root' => [
                'usu_uninstall.php',
            ],
        ];

        // -----
        // Remove those files ...
        //
        foreach ($files_to_remove as $key => $file_list) {
            switch ($key) {
                case 'storefront':
                    $directory = DIR_FS_CATALOG . DIR_WS_INCLUDES;
                    break;
                case 'admin_includes':
                    $directory = DIR_FS_ADMIN . DIR_WS_INCLUDES;
                    break;
                default:
                    $directory = DIR_FS_ADMIN;
                    break;
            }
            foreach ($file_list as $current_file) {
                if (file_exists($directory . $current_file)) {
                    unlink($directory . $current_file);
                }
            }
        }
    }

    // -----
    // Remove the "Ultimate SEO URLs" database elements, as well as any legacy 'Ultimate SEO' elements.
    //
    $db->Execute(
        "DELETE FROM " . TABLE_CONFIGURATION . "
          WHERE configuration_key LIKE 'USU_%'
             OR configuration_key LIKE 'SEO_%'"
    );
    $db->Execute(
        "DELETE FROM " . TABLE_CONFIGURATION_GROUP . "
          WHERE configuration_group_title IN ('Ultimate URLs', 'Ultimate SEO')"
    );
    $db->Execute(
        "DELETE FROM " . TABLE_ADMIN_PAGES . "
          WHERE page_key IN ('configUltimateSEO', 'usuUninstall')"
    );
    $db->Execute(
        "DROP TABLE " . TABLE_USU_CACHE
    );

    // -----
    // Set a message notifying the admin of the removal, note the change in the activity
    // log and redirect back to the admin dashboard.
    //
    $messageStack->add_session(TEXT_MESSAGE_USU_REMOVED, 'success');
    zen_record_admin_activity(TEXT_MESSAGE_USU_REMOVED, 'warning');
    zen_redirect(zen_href_link(FILENAME_DEFAULT));
}

// -----
// Set up the next-action to be performed on form-submittal and the message to display on the
// current page.  On initial entry, the admin is questioned as to whether to remove IH; on the
// first form-submittal, the admin is asked to confirm their removal request and on the next
// form-submittal, the file/configuration removal is actually performed.
//
if (!isset($_POST['action']) || $_POST['action'] !== 'confirm') {
    $next_action = 'confirm';
    $current_message = TEXT_ARE_YOU_SURE;
} else {
    $next_action = 'uninstall';
    $current_message = TEXT_CONFIRMATION;
}
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
    <!-- header //-->
    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
    <!-- header_eof //-->
    <h1><?php echo HEADING_TITLE; ?></h1>
    <?php echo zen_draw_form('remove', FILENAME_USU_UNINSTALL) . zen_draw_hidden_field('action', $next_action);?>
       <p><?php echo $current_message; ?></p>
<?php
if ($next_action === 'confirm') {
?>
        <p><?php echo zen_draw_checkbox_field('db_only') . LABEL_DATABASE_ONLY; ?></p>
<?php
} else {
?>
        <p><?php echo (!isset($_POST['db_only'])) ? TEXT_DB_AND_FILES : (TEXT_ONLY_DB_SETTINGS . zen_draw_hidden_field('db_only', '1')); ?></p>
<?php
}
?>
        <p><a href="<?php echo zen_href_link(FILENAME_DEFAULT); ?>"><?php echo zen_image_button('button_cancel.gif', IMAGE_CANCEL); ?></a>&nbsp;&nbsp;<?php echo zen_image_submit('button_go.gif', IMAGE_GO); ?></p>
    <?php echo '</form>'; ?>
    <!-- footer //-->
    <?php require DIR_WS_INCLUDES . 'footer.php'; ?>
    <!-- footer_eof //-->
</body>
</html>
<?php 
require DIR_WS_INCLUDES . 'application_bottom.php';
