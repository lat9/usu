<?php
/**
 * Part of Ultimate URLs, v3.0.0+, for Zen Cart.
 *
 * @copyright Copyright 2019        Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

/**
 * This function provides a means to reset USU's URL cache, either **all** cached entries
 * or individual ones.  The function is invoked by:
 *
 * 1) Configuration->Ultimate URLs->Reset URL Cache, set to 'true'
 * 2) USU's admin initialization script to reset individual cached types.
 * 
 * Side-effect: USU's "Reset URL Cache" setting is reset to 'false'.
 *
 * @param string $value the current value for the reset URL cache option
 * @return string the value to display for the reset URL cache option
 */
function usu_reset_cache_data($value) 
{
    switch ($value) {
        case 'false':
            break;
        case 'true':
            $where_clause = "LIKE 'usu_v3_%'";
            break;
        case 'manufacturers':
            $where_clause = "= 'usu_v3_manufacturers'";
            break;
        case 'ezpages':
            $where_clause = "= 'usu_v3_ezpages'";
            break;
        case 'products':
            $where_clause = "= 'usu_v3_products'";
            break;
        case 'categories':
            $where_clause = "= 'usu_v3_categories'";
            break;
        default:
            trigger_error("Unknown value ($value) supplied to usu_reset_cache_data; the request is ignored", E_USER_NOTICE);
            break;
    }
    if (isset($where_clause)) {
        zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => 'false'), 'update', "configuration_key = 'USU_CACHE_RESET' LIMIT 1");
        $GLOBALS['db']->Execute(
            "DELETE FROM " . TABLE_USU_CACHE . "
              WHERE cache_name $where_clause"
        );
        $cache_type = ($value == 'true') ? 'global' : $value;
        $GLOBALS['messageStack']->add_session(sprintf(USU_PLUGIN_CACHE_RESET, $cache_type), 'success');
    }
    return 'false';
}

// =======================================
// ==> NOTE: All the following functions are deprecated as of v3.0.1 and will be removed in a future USU release.
// =======================================

/**
 * Checks the value of the cPath option. If the value has been changed, the
 * new value will be saved to the database and the URL cache reset.
 *
 * @param string $value the current value for the cPath option
 * @return string the value to display for the cPath option
 */
function usu_check_cpath_option($value) 
{
/*
    $value_ok = true;
    switch ($value) {
        case 'disable':
            $value = 'off';
            break;
        case 'enable-auto':
            $value = 'auto';
            break;
        default:
            $value_ok = false;
            break;
    }

    if ($value_ok) {
        zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_CPATH' LIMIT 1");
        usu_reset_cache_data('true');
    }
*/
    return $value;
}

/**
 * Checks the value of the URL format option. If the value has been changed, the
 * new value will checked for compatibility issues. If compatibility issues exist
 * with the category directory option the category directory option will be
 * updated to avoid issues. The new option will be saved to the database and the
 * URL cache reset.
 *
 * @param string $value the current value for the URL format option
 * @return string the value to display for the URL format option
 */
function usu_check_url_format_option($value) 
{
/*
    switch ($value) {
        case 'enable-parent':
            $value = 'parent';
            if (USU_CATEGORY_DIR == 'full') {
                zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => 'short'), 'update', "configuration_key = 'USU_CATEGORY_DIR' LIMIT 1");
                echo '<div><span class="alert">' . USU_PLUGIN_WARNING_CATEGORY_DIR . '</span></div>';
            }
            break;
        case 'enable-original':
            // Update with the correct setting and reset the cache
            $value = 'original';
            zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_FORMAT' LIMIT 1");
            usu_reset_cache_data('true');
            break;
        default:
            break;
    }
*/
    return $value;
}

/**
 * Checks the value of the category directory option. If the value has been
 * changed, the new value will checked for compatibility issues. If compatibility
 * issues exist with the URL format option the URL format option will be
 * updated to avoid issues. The new option will be saved to the database and the
 * URL cache reset.
 *
 * @param string $value the current value for the category directory option
 * @return string the value to display for the category directory option
 */
function usu_check_category_dir_option($value) 
{
/*
    switch ($value) {
        case 'disable':
            $value = 'off';
            zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_CATEGORY_DIR' LIMIT 1");
            usu_reset_cache_data('true');
            break;

        case 'enable-full':
            $value = 'full';
            if (USU_FORMAT == 'parent') {
                zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => 'original'), 'update', "configuration_key = 'USU_FORMAT' LIMIT 1");
                echo '<div><span class="alert">' . USU_PLUGIN_WARNING_FORMAT . '</span></div>';
            }
            break;
        case 'enable-short':
            $value = 'short';
            zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_CATEGORY_DIR' LIMIT 1");
            usu_reset_cache_data('true');
            break;
        default:
            break;
    }
*/
    return $value;
}

/**
 * Checks the value of the remove characters option. If the value has been
 * changed, the value will be updated in the database and the URL cache reset.
 *
 * @param string $value the current value for the remove characters option
 * @return string the value to display for the remove characters option
 */
function usu_check_remove_chars_option($value) 
{
/*
    switch($value) {
        case 'enable-non-alphanumerical':
        case 'enable-punctuation':
            $value = str_replace('enable-', '', $value);
            zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_REMOVE_CHARS' LIMIT 1");
            usu_reset_cache_data('true');
            break;
            
        default:
            break;
    }
*/
    return $value;
}

/**
 * Checks the value of the short words option. If the value is not a positive integer,
 * the value will be changed to 0.
 *
 * @param string $value the current value for the short words option
 * @return string the value to display for the short words option
 */
function usu_check_short_words($value) 
{
/*
    if (!ctype_digit($value) || ((int)$value) < 0) {
        zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => '0'), 'update', "configuration_key = 'USU_FILTER_SHORT_WORDS' LIMIT 1");
        
        echo '<div><span class="alert">' . sprintf(USU_PLUGIN_WARNING_SHORT_WORDS, $value) . '</span></div>';
        $value = '0';
    }
*/
    return $value;
}

/**
 * Checks the value of the various URL cache options. If the value has been
 * changed, the value will be updated in the database and the URL cache reset.
 *
 * @param string $value the current value for the URL cache option
 * @return string the value to display for the URL cache option
 */
function usu_check_cache_options($value) 
{
/*
    $temp = explode('-', $value);
    if (count($temp) < 2) {
        $temp[] = 'global';
    }
    $temp[1] = strtoupper($temp[1]);

    switch ($temp[0]) {
        case 'enable':
            $value = 'true';
            if (USU_CACHE_GLOBAL == 'false' && $temp[1] != 'GLOBAL') {
                zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_CACHE_GLOBAL' LIMIT 1");
                echo '<div><span class="alert">' . sprintf(USU_PLUGIN_WARNING_CONFIG_ADJUSTED, usu_get_configuration_title('USU_CACHE_' . $temp[1]), usu_get_configuration_title('USU_CACHE_GLOBAL')) . '</span></div>';
                unset($text, $option_text);
            }
            zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_CACHE_{$temp[1]}' LIMIT 1");
            usu_reset_cache_data('true');
            break;

        case 'disable':
            $value = 'false';
            if ($temp[1] == 'GLOBAL') {
                echo '<div><span class="alert">' . USU_PLUGIN_WARNING_GLOBAL_DISABLED . '</span></div>';
            }
            zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', "configuration_key = 'USU_CACHE_{$temp[1]}' LIMIT 1");
            usu_reset_cache_data('true');
            break;

        default:
            break;
    }
*/
    return $value;
}

/**
 * Sets the HTML to display for changing the cPath option.
 *
 * @param string $value the current value for the cPath option
 * @return string the HTML to display
 */
function usu_set_cpath_option($value) 
{
/*
    if ($value == 'auto') {
        $options = array(
            'auto',
            'disable'
        );
    } else {
        $value = 'off';
        $options = array(
            'enable-auto',
            'off'
        );
    }
*/
    $options = array('auto', 'off');
    return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the URL format option.
 *
 * @param string $value the current value for the URL format option
 * @return string the HTML to display
 */
function usu_set_url_format_option($value) 
{
/*
    if ($value == 'original') {
        $options = array(
            'original',
            'enable-parent'
        );
    } else {
        $value = 'parent';
        $options = array(
            'enable-original',
            'parent'
        );
    }
*/
    $options = array('original', 'parent');
    return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the category directory option.
 *
 * @param string $value the current value for the category directory option
 * @return string the HTML to display
 */
function usu_set_category_dir_option($value) 
{
/*
    if ($value == 'off') {
        $options = array(
            'off',
            'enable-short',
            'enable-full'
        );
    } elseif ($value == 'full') {
        $options = array(
            'disable',
            'enable-short',
            'full'
        );
    } else {
        $value = 'short';
        $options = array(
            'disable',
            'short',
            'enable-full'
        );
    }
*/
    $options = array('off', 'short', 'full');
    return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the remove characters option.
 *
 * @param string $value the current value for the remove characters option
 * @return string the HTML to display
 */
function usu_set_remove_chars_option($value) 
{
/*
    if ($value == 'non-alphanumerical') {
        $options = array(
            'non-alphanumerical',
            'enable-punctuation'
        );
    } else {
        $options = array(
            'enable-non-alphanumerical',
            'punctuation'
        );
    }
*/
    $options = array('non-alphanumerical', 'punctuation');
    return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the global cache option.
 *
 * @param string $value the current value for the global cache option
 * @return string the HTML to display
 */
function usu_set_global_cache_option($value) 
{
/*
    if ($value == 'true') {
        $options = array(
            'true',
            'disable'
        );
    } else {
        $options = array(
            'enable',
            'false'
        );
    }
*/
    $options = array('true', 'false');
    return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the various cache options.
 *
 * @param string $value the current value for the various cache options
 * @return string the HTML to display
 */
function usu_set_cache_options($cache, $value) 
{
/*
    $cache = strtolower($cache);
    $key = 'USU_CACHE_' . strtoupper($cache);
    if (constant($key) == 'true') {
        $options = array(
            'true',
            'disable-' . $cache
        );
    } else {
        $options = array(
            'enable-' . $cache,
            'false'
        );
    }
*/
    $options = array('true', 'false');
    return zen_cfg_select_option($options, $value);
}

/**
 * Retrieve the current configuration title stored in the database for the
 * specified configuration option.
 *
 * @param string $key the configuration key for the option
 * @param string $default text to use if the key cannot be found
 * @return string the configuration title
 */
function usu_get_configuration_title($key, $default = null) 
{
    if ($default === null) {
        $default = $key;
    }

    $option_text = $GLOBALS['db']->Execute(
        "SELECT configuration_title 
           FROM " . TABLE_CONFIGURATION . "
          WHERE configuration_key = '$key'
          LIMIT 1"
    );
    if (!$option_text->EOF) {
        $default = $option_text->fields['configuration_title'];
    }
    return $default;
}
