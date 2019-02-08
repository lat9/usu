<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

/**
 * Checks the value of the reset URL cache option. If the value is true
 * the value will be updated in the database to "false" and the URL cache reset.
 *
 * @param string $value the current value for the reset URL cache option
 * @return string the value to display for the reset URL cache option
 */
function usu_reset_cache_data($value) {
	global $db;
	switch($value){
		case 'false':
			// Do nothing
			break;
		case 'true':
			// Update the value to false to disable
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => 'false'), 'update', '`configuration_key`=\'USU_CACHE_RESET\'');

		default:
			// Reset the cache
			$db->Execute("DELETE FROM " . TABLE_USU_CACHE . " WHERE cache_name LIKE 'usu_v3_%'");

	}

	return 'false';
}

/**
 * Checks the value of the cPath option. If the value has been changed, the
 * new value will be saved to the database and the URL cache reset.
 *
 * @param string $value the current value for the cPath option
 * @return string the value to display for the cPath option
 */
function usu_check_cpath_option($value) {
	switch($value) {
		case 'disable':
			$value = 'off';
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CPATH\'');
			usu_reset_cache_data('true');
			break;

		case 'enable-auto':
			$value = substr($value, 7);
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CPATH\'');
			usu_reset_cache_data('true');

		default:
	}

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
function usu_check_url_format_option($value) {
	switch($value) {
		case 'enable-parent':
			if(USU_CATEGORY_DIR == 'full') {
				zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => 'short'), 'update', '`configuration_key`=\'USU_CATEGORY_DIR\'');
				echo '<div><span class="alert">' . sprintf(USU_PLUGIN_WARNING_CONFIG_ADJUSTED, usu_get_configuration_title('USU_FORMAT'), usu_get_configuration_title('USU_CATEGORY_DIR'), 'short') . '</span></div>';
			}
		case 'enable-original':
			// Update with the correct setting and reset the cache
			$value = substr($value, 7);
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_FORMAT\'');
			usu_reset_cache_data('true');

		default:
	}

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
function usu_check_category_dir_option($value) {
	switch($value) {
		case 'disable':
			$value = 'off';
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CATEGORY_DIR\'');
			usu_reset_cache_data('true');
			break;

		case 'enable-full':
			if(USU_FORMAT == 'parent') {
				zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => 'original'), 'update', '`configuration_key`=\'USU_FORMAT\'');
				echo '<div><span class="alert">' . sprintf(USU_PLUGIN_WARNING_CONFIG_ADJUSTED, usu_get_configuration_title('USU_CATEGORY_DIR'), usu_get_configuration_title('USU_FORMAT'), 'original') . '</span></div>';
			}
		case 'enable-short':
			$value = substr($value, 7);
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CATEGORY_DIR\'');
			usu_reset_cache_data('true');

		default:
	}

	return $value;
}

/**
 * Checks the value of the remove characters option. If the value has been
 * changed, the value will be updated in the database and the URL cache reset.
 *
 * @param string $value the current value for the remove characters option
 * @return string the value to display for the remove characters option
 */
function usu_check_remove_chars_option($value) {
	switch($value) {
		case 'enable-non-alphanumerical':
		case 'enable-punctuation':
			$value = substr($value, 7);
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_REMOVE_CHARS\'');
			usu_reset_cache_data('true');

		default:
	}

	return $value;
}

/**
 * Checks the value of the short words option. If the value is not an
 * integer, the value will be changed to 0.
 *
 * @param string $value the current value for the short words option
 * @return string the value to display for the short words option
 */
function usu_check_short_words($value) {
	if(is_int($value)) {
		$value = '0';
		zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_FILTER_SHORT_WORDS\'');
		echo '<div><span class="alert">' . sprintf(USU_PLUGIN_WARNING_CONFIG_INVALID, usu_get_configuration_title('USU_FILTER_SHORT_WORDS'), $value) . '</span></div>';
	}

	return $value;
}

/**
 * Checks the value of the various URL cache options. If the value has been
 * changed, the value will be updated in the database and the URL cache reset.
 *
 * @param string $value the current value for the URL cache option
 * @return string the value to display for the URL cache option
 */
function usu_check_cache_options($value) {
	$temp = explode('-', $value);
	if(sizeof($temp) < 2) $temp[] = 'global';
	$temp[1] = strtoupper($temp[1]);

	switch($temp[0]) {
		case 'enable':
			$value = 'true';
			if(USU_CACHE_GLOBAL == 'false' && $temp[1] != 'GLOBAL') {
				zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CACHE_GLOBAL\'');
				echo '<div><span class="alert">' . sprintf(USU_PLUGIN_WARNING_CONFIG_ADJUSTED, usu_get_configuration_title('USU_CACHE_' . $temp[1]), usu_get_configuration_title('USU_CACHE_GLOBAL')) . '</span></div>';
				unset($text, $option_text);
			}
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CACHE_' . $temp[1] . '\'');
			usu_reset_cache_data('true');
			break;

		case 'disable':
			$value = 'false';
			if($temp[1] == 'GLOBAL') {
				echo '<div><span class="alert">' . USU_PLUGIN_WARNING_GLOBAL_DISABLED . '</span></div>';
			}
			zen_db_perform(TABLE_CONFIGURATION, array('configuration_value' => $value), 'update', '`configuration_key`=\'USU_CACHE_' . $temp[1] . '\'');
			usu_reset_cache_data('true');
			break;

		default:
	}

	return $value;
}

/**
 * Sets the HTML to display for changing the cPath option.
 *
 * @param string $value the current value for the cPath option
 * @return string the HTML to display
 */
function usu_set_cpath_option($value) {
	$options = array();
	if($value == 'auto') {
		$options = array(
			'auto',
			'disable'
		);
	}
	else {
		$options = array(
			'enable-auto',
			'off'
		);
	}
	return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the URL format option.
 *
 * @param string $value the current value for the URL format option
 * @return string the HTML to display
 */
function usu_set_url_format_option($value) {
	$options = array();
	if($value == 'original') {
		$options = array(
			'original',
			'enable-parent'
		);
	}
	else {
		$options = array(
			'enable-original',
			'parent'
		);
	}
	return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the category directory option.
 *
 * @param string $value the current value for the category directory option
 * @return string the HTML to display
 */
function usu_set_category_dir_option($value) {
	$options = array();
	if($value == 'off') {
		$options = array(
			'off',
			'enable-short',
			'enable-full'
		);
	}
	else if($value == 'full') {
		$options = array(
			'disable',
			'enable-short',
			'full'
		);
	}
	else {
		$options = array(
			'disable',
			'short',
			'enable-full'
		);
	}
	return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the remove characters option.
 *
 * @param string $value the current value for the remove characters option
 * @return string the HTML to display
 */
function usu_set_remove_chars_option($value) {
	$options = array();
	if($value == 'non-alphanumerical') {
		$options = array(
			'non-alphanumerical',
			'enable-punctuation'
		);
	}
	else {
		$options = array(
			'enable-non-alphanumerical',
			'punctuation'
		);
	}
	return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the global cache option.
 *
 * @param string $value the current value for the global cache option
 * @return string the HTML to display
 */
function usu_set_global_cache_option($value) {
	$options = array();

	if($value == 'true') {
		$options = array(
			'true',
			'disable'
		);
	}
	else {
		$options = array(
			'enable',
			'false'
		);
	}

	return zen_cfg_select_option($options, $value);
}

/**
 * Sets the HTML to display for changing the various cache options.
 *
 * @param string $value the current value for the various cache options
 * @return string the HTML to display
 */
function usu_set_cache_options($cache, $value) {
	$options = array();

	$cache = strtolower($cache);
	$key = 'USU_CACHE_' . strtoupper($cache);
	if(constant($key) == 'true') {
		$options = array(
			'true',
			'disable-' . $cache
		);
	}
	else {
		$options = array(
			'enable-' . $cache,
			'false'
		);
	}

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
function usu_get_configuration_title($key, $default = null) {
	global $db;

	if($default === null) $default = $key;

	$option_text = $db->Execute(
		'SELECT `configuration_title` FROM `' . TABLE_CONFIGURATION . '` ' .
		'WHERE `configuration_key`=\'' . $key . '\''
	);
	if(!$option_text->EOF) {
		$default = $option_text->fields['configuration_title'];
	}
	return $default;
}
