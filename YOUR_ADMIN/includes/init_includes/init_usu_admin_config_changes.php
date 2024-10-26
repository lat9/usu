<?php
/**
 * Part of Ultimate URLs, v3.1.0+, for Zen Cart.
 *
 * @copyright Copyright 2019, 2023 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
// -----
// This module, loaded by init_usu_admin on detection of an update to a USU configuration setting,
// inspects the to-be-updated value and performs various dependency- and value-checks assocated with the change.
//
// Assumptions:
//
// 1) Loaded by init_usu_admin.php, which sets the $cgi value to be that associated with the 'Ultimate URLs' group.
// 2) Only loaded during pre-processing for a 'save' action on the 'configuration' page.
//
$usu_check = $db->Execute(
    "SELECT configuration_key, configuration_value
       FROM " . TABLE_CONFIGURATION . "
      WHERE configuration_group_id = $cgi
        AND configuration_id = " . (int)$_GET['cID'] . "
      LIMIT 1"
);
if (!$usu_check->EOF) {
    $usu_current_value = $usu_check->fields['configuration_value'];
    $usu_new_value = $_POST['configuration_value'];
    $usu_cache_reset = 'false';
    switch ($usu_check->fields['configuration_key']) {
        // -----
        // The 'Filter short words' value must be a positive integer; if not, reset to '0' with a message to the admin user.
        //
        case 'USU_FILTER_SHORT_WORDS':
            if (!ctype_digit($usu_new_value) || ((int)$usu_new_value) < 0) {
                $_POST['configuration_value'] = '0';
                $messageStack->add_session(sprintf(USU_PLUGIN_WARNING_SHORT_WORDS, htmlspecialchars($usu_new_value, ENT_COMPAT, CHARSET, true)), 'error');
            }
            break;
        // -----
        // The 'Format of alternate URLs' setting of 'parent' is incompatible with the 'Display categories as directories' setting
        // of 'full'.  If that condition is found, modify the second setting to use the compatible 'short' value.
        //
        // In all cases, if the setting has changed, reset USU's global cache.
        //
        case 'USU_FORMAT':
            if ($usu_new_value === 'parent' && USU_CATEGORY_DIR === 'full') {
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = 'short'
                      WHERE configuration_key = 'USU_CATEGORY_DIR'
                      LIMIT 1"
                );
                $messageStack->add_session(USU_PLUGIN_WARNING_CATEGORY_DIR, 'error');
            }
            break;
        // -----
        // The 'Display categories as directories' setting of 'full' is (as above) incompatible with the 'Format of alternate URLs'
        // setting of 'parent'.  If that condition is found, modify the format setting to use 'original'.
        //
        // In all cases, if the setting has changed, reset USU's global cache.
        //
        case 'USU_CATEGORY_DIR':
            if ($usu_new_value === 'full' && USU_FORMAT === 'parent') {
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = 'original'
                      WHERE configuration_key = 'USU_FORMAT'
                      LIMIT 1"
                );
                $messageStack->add_session(USU_PLUGIN_WARNING_FORMAT, 'error');
            }
            break;
        // -----
        // The 'Enter PCRE Filter Rules' value requires that the filters be entered as
        // find1=>replace1[,find2=>replace2]...
        //
        // Unfortunately, the Zen Cart admin sanitization is changing the => to =&gt; which
        // breaks the URL handling on the storefront.  If that setting is being updated, make
        // sure that any =&gt; values are properly changed back to =>.
        //
        case 'USU_FILTER_PCRE':
            $_POST['configuration_value'] = str_replace('=&gt;', '=>', $_POST['configuration_value']);
            break;
        // -----
        // For all other settings, no action required.
        //
        default:
            break;
    }
}
