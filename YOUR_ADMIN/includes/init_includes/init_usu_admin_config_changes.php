<?php
/**
 * Part of Ultimate URLs, v3.0.1+, for Zen Cart.
 *
 * @copyright Copyright 2019 Cindy Merkin (vinosdefrutastropicales.com)
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
        // Some of the settings, when *changed*, require that USU's global cache be reset.
        //
        case 'USU_CPATH':
        case 'USU_REMOVE_CHARS':
            if ($usu_new_value != $usu_current_value) {
                $usu_cache_reset = 'true';
            }
            break;
        // -----
        // The 'Format of alternate URLs' setting of 'parent' is incompatible with the 'Display categories as directories' setting
        // of 'full'.  If that condition is found, modify the second setting to use the compatible 'short' value.
        //
        // In all cases, if the setting has changed, reset USU's global cache.
        //
        case 'USU_FORMAT':
            if ($usu_new_value == 'parent' && USU_CATEGORY_DIR == 'full') {
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = 'short'
                      WHERE configuration_key = 'USU_CATEGORY_DIR'
                      LIMIT 1"
                );
                $messageStack->add_session(USU_PLUGIN_WARNING_CATEGORY_DIR, 'error');
            }
            if ($usu_new_value != $usu_current_value) {
                $usu_cache_reset = 'true';
            }
            break;
        // -----
        // The 'Display categories as directories' setting of 'full' is (as above) incompatible with the 'Format of alternate URLs'
        // setting of 'parent'.  If that condition is found, modify the format setting to use 'original'.
        //
        // In all cases, if the setting has changed, reset USU's global cache.
        //
        case 'USU_CATEGORY_DIR':
            if ($usu_new_value == 'full' && USU_FORMAT == 'parent') {
                $db->Execute(
                    "UPDATE " . TABLE_CONFIGURATION . "
                        SET configuration_value = 'original'
                      WHERE configuration_key = 'USU_FORMAT'
                      LIMIT 1"
                );
                $messageStack->add_session(USU_PLUGIN_WARNING_FORMAT, 'error');
            }
            if ($usu_new_value != $usu_current_value) {
                $usu_cache_reset = 'true';
            }
            break;
        // -----
        // If the global cache setting is disabled, notify the admin that this will override the enabling of any individual cache-types.
        //
        case 'USU_CACHE_GLOBAL':
            if ($usu_new_value == 'false') {
                $messageStack->add_session(USU_PLUGIN_WARNING_GLOBAL_DISABLED, 'warning');
            }
            if ($usu_new_value != $usu_current_value) {
                $usu_cache_reset = 'true';
            }
            break;
        // -----
        // For any individual cache-types, if that individual cache's setting is changed to 'true' (i.e. enabled) while
        // the global cache is disabled, let the admin know that the setting won't be used.
        //
        case 'USU_CACHE_PRODUCTS':
        case 'USU_CACHE_CATEGORIES':
        case 'USU_CACHE_MANUFACTURERS':
        case 'USU_CACHE_EZ_PAGES':
            if ($usu_new_value == 'true' && USU_CACHE_GLOBAL == 'false') {
                $messageStack->add_session(USU_PLUGIN_WARNING_GLOBAL_DISABLED, 'warning');
            }
            if ($usu_new_value != $usu_current_value) {
                $usu_cache_reset = strtolower(str_replace(array('USU_CACHE_', '_'), '', $usu_check->fields['configuration_key']));
            }
            break;
        // -----
        // If the admin has requested that the overall USU cache be reset, ensure that the value
        // to be stored back for that setting is now 'false' and indicate that the cache should
        // be fully reset.
        //
        case 'USU_CACHE_RESET':
            $usu_cache_reset = 'true';
            $_POST['configuration_value'] = 'false';
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
    usu_reset_cache_data($usu_cache_reset);
}
