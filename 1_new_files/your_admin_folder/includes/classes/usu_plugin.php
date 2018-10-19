<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

@require_once DIR_FS_ADMIN . DIR_WS_CLASSES . 'plugin.php';

/**
 * Plugin subclass providing installation, upgrade, and removal information
 * for the Ultimate URLs Zen Cart plugin.
 */
class usu_plugin extends plugin {
	public function getVersion() { return '2.215'; }
	public function getUniqueKey() { return 'USU'; }
	public function getUniqueName() { return BOX_CONFIGURATION_USU; }
	public function getDescription() { return USU_DESCRIPTION; }
	public function getDefaultConfiguration() {
		return array(
			'ENABLED' => array('configuration_value' => 'false', 'use_function' => '', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
			'DEBUG' => array('configuration_value' => 'false', 'use_function' => '', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
			'CPATH' => array('configuration_value' => 'auto', 'use_function' => 'usu_check_cpath_option', 'set_function' => 'usu_set_cpath_option('),
			'END' => array('configuration_value' => '.html', 'use_function' => '', 'set_function' => ''),
			'FORMAT' => array('configuration_value' => 'original', 'use_function' => 'usu_check_url_format_option', 'set_function' => 'usu_set_url_format_option('),
			'CATEGORY_DIR' => array('configuration_value' => 'short', 'use_function' => 'usu_check_category_dir_option', 'set_function' => 'usu_set_category_dir_option('),
			'REMOVE_CHARS' => array('configuration_value' => 'punctuation', 'use_function' => 'usu_check_remove_chars_option', 'set_function' => 'usu_set_remove_chars_option('),
			'FILTER_PCRE' => array('configuration_value' => '', 'use_function' => '', 'set_function' => ''),
			'FILTER_SHORT_WORDS' => array('configuration_value' => '0', 'use_function' => 'usu_check_short_words', 'set_function' => ''),
			'FILTER_PAGES' => array('configuration_value' => 'index, product_info, product_music_info, document_general_info, document_product_info, product_free_shipping_info, products_new, products_all, shopping_cart, featured_products, specials, contact_us, conditions, privacy, reviews, shippinginfo, faqs_all, site_map, gv_faq, discount_coupon, page, page_2, page_3, page_4', 'use_function' => '', 'set_function' => ''),
			'ENGINE' => array('configuration_value' => 'rewrite', 'use_function' => '', 'set_function' => 'zen_cfg_select_option(array(\'rewrite\'),'),
			'REDIRECT' => array('configuration_value' => 'false', 'use_function' => '', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
			'CACHE_GLOBAL' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_global_cache_option('),
			'CACHE_PRODUCTS' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'products\','),
			'CACHE_CATEGORIES' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'categories\','),
			'CACHE_MANUFACTURERS' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'manufacturers\','),
			'CACHE_EZ_PAGES' => array('configuration_value' => 'true', 'use_function' => 'usu_check_cache_options', 'set_function' => 'usu_set_cache_options(\'ez_pages\','),
			'CACHE_RESET' => array('configuration_value' => 'false', 'use_function' => 'usu_reset_cache_data', 'set_function' => 'zen_cfg_select_option(array(\'true\', \'false\'),'),
		);
	}
	/* Add the configuration admin page.
	 * @see plugin::getAdminPages()
	*/
	public function getAdminPages() {
		return array(
			'configUSU' => array(
				'language_key' => 'BOX_CONFIGURATION_USU',
				'main_page' => 'FILENAME_CONFIGURATION',
			)
		);
	}
	public function getNewFiles() {
		return array(
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/usu.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'auto_loaders/config.usu.php',
			DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'extra_functions/usu.php',
			DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/extra_definitions/usu.php',

			DIR_FS_CATALOG . DIR_WS_CLASSES . 'usu.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'auto_loaders/config.usu.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_datafiles/usu.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'init_includes/init_usu.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'init_includes/overrides/init_canonical.php'
		);
	}
	public function getObsoleteFiles() {
		return array(
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'reset_seo_cache.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'auto_loaders/config.seo.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'extra_datafiles/seo.php',
			DIR_FS_ADMIN . DIR_WS_INCLUDES . 'init_includes/init_seo_config.php',
			DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'extra_functions/seo.php',
			DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/extra_definitions/seo.php',

			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'auto_loaders/config.ultimate_seo.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'extra_datafiles/seo.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'auto_loaders/config.seo.php',
			DIR_FS_CATALOG . DIR_WS_INCLUDES . 'init_includes/init_seo_config.php',
			DIR_FS_CATALOG . DIR_WS_CLASSES . 'seo.url.php',
			DIR_FS_CATALOG . DIR_WS_CLASSES . 'seo.install.php'
		);
	}

	public function processFileChanges($install = true, $files = null) {
		global $messageStack;
		$success = parent::processFileChanges($install, $files);

		// Handle the case where query_factory has not been patched to fix the
		// handling of Zen Cart's SQL Cache and Zen Cart's SQL Cache is enabled.
		// Note: the patches are now included with Zen Cart >= 1.5.2
		$zcversion = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
		if(version_compare($zcversion, '1.5.2', '<') && defined('SQL_CACHE_METHOD') && SQL_CACHE_METHOD != 'none') {
			$file = @file_get_contents(DIR_FS_CATALOG . DIR_WS_CLASSES . 'db/mysql/query_factory.php');
			if($file === false) {
				$success = false;

				$messageStack->add(
					sprintf(
						USU_PLUGIN_INSTALL_ERROR_CORE_FILE_NOT_FOUND,
						DIR_FS_CATALOG . DIR_WS_CLASSES . 'db/mysql/query_factory.php',
						'DIR_FS_CATALOG . DIR_WS_CLASSES'
					),
					'warning'
				);
			}
			else if(!preg_match('~function\sRecordCount\(\)\s?\{\s+if\s?\(\$this->is_cached\).+?\}~s', $file)) {
				if($install) $success = false;

				$messageStack->add(
					sprintf(
						USU_PLUGIN_INSTALL_ERROR_QUERY_FACTORY,
						DIR_FS_CATALOG . DIR_WS_CLASSES . 'db/mysql/query_factory.php',
						'DIR_FS_CATALOG . DIR_WS_CLASSES'
					),
					'warning'
				);
			}
		}

		return $success;
	}
	public function handleDatabaseChanges($install = true) {
		global $db, $messageStack;

		$success = true;
		if($install) {
			if(!$this->dbTableExists(TABLE_USU_CACHE)) {
				$db->Execute(
					'CREATE TABLE `' . TABLE_USU_CACHE . '` ( ' .
						'`cache_id` VARCHAR(32) NOT NULL default \'\', ' .
						'`cache_language_id` TINYINT(1) NOT NULL default \'0\', ' .
						'`cache_name` VARCHAR(255) NOT NULL default \'\', ' .
						'`cache_data` MEDIUMBLOB NOT NULL, ' .
						'`cache_global` TINYINT(1) NOT NULL default \'1\', ' .
						'`cache_gzip` TINYINT(1) NOT NULL default \'1\', ' .
						'`cache_method` VARCHAR(20) NOT NULL default \'RETURN\', ' .
						'`cache_date` DATETIME NOT NULL default \'0000-00-00 00:00:00\', ' .
						'`cache_expires` DATETIME NOT NULL default \'0000-00-00 00:00:00\', ' .
						'PRIMARY KEY (`cache_id`,`cache_language_id`), ' .
						'KEY `cache_id` (`cache_id`), ' .
						'KEY `cache_language_id` (`cache_language_id`), ' .
						'KEY `cache_global` (`cache_global`) ' .
					')'
				);
			}
			if(!$this->dbTableExists(TABLE_USU_CACHE)) {
				$messageStack->add(sprintf(
					PLUGIN_INSTALL_ERROR_DATABASE_TABLE,
					$this->getUniqueName(),
					TABLE_USU_CACHE
				));
				$success = false;
			}
		}
		else if($this->dbTableExists(TABLE_USU_CACHE)) {
			$db->Execute('DROP TABLE IF EXISTS ' . TABLE_USU_CACHE);
			if($this->dbTableExists(TABLE_USU_CACHE)) {
				$messageStack->add(sprintf(
					PLUGIN_REMOVE_ERROR_DATABASE_TABLE,
					$this->getUniqueName(),
					TABLE_USU_CACHE
				));
				$success = false;
			}
		}

		return $success;
	}
	public function handleUpgradeFrom($version) {
		global $db, $messageStack;

		$success = true;
		$default = $this->getDefaultConfiguration();

		// Handle the upgrades. This can be quite complex.
		switch($version) {
			case '2.150':
				if(defined('SEO_REMOVE_ALL_SPEC_CHARS')) {
					$data = $default['REMOVE_CHARS'];
					$data['configuration_key'] = 'USU_REMOVE_CHARS';
					$data['configuration_value'] = (SEO_REMOVE_ALL_SPEC_CHARS == 'true' ? 'non-alphanumerical' : 'punctuation');
					$this->updateConfigurationOption('SEO_REMOVE_ALL_SPEC_CHARS', $data);
				}
				if(defined('SEO_ADD_PRODUCT_CAT')) {
					$data = $default['FORMAT'];
					$data['configuration_key'] = 'USU_FORMAT';
					$data['configuration_value'] = (SEO_ADD_PRODUCT_CAT == 'true' ? 'parent' : 'original');
					if(defined('SEO_ADD_CAT_PARENT') && SEO_ADD_CAT_PARENT == 'true') $data['configuration_value'] = 'original';
					$this->updateConfigurationOption('SEO_ADD_PRODUCT_CAT', $data);
				}
				if(defined('SEO_ADD_CAT_PARENT')) {
					$data = $default['CATEGORY_DIR'];
					$data['configuration_key'] = 'USU_CATEGORY_DIR';
					$data['configuration_value'] = (SEO_ADD_CAT_PARENT == 'false' ? 'off' : 'full');
					$this->updateConfigurationOption('SEO_ADD_CAT_PARENT', $data);
				}
				if(defined('SEO_ADD_CPATH_TO_PRODUCT_URLS')) {
					$data = $default['CPATH'];
					$data['configuration_key'] = 'USU_CPATH';
					$data['configuration_value'] = (SEO_ADD_CPATH_TO_PRODUCT_URLS == 'false' ? 'off' : 'auto');
					$this->updateConfigurationOption('SEO_ADD_CPATH_TO_PRODUCT_URLS', $data);
				}

				// Remove older options which are no longer used
				$old_options = array(
					'USE_SEO_CACHE_ARTICLES' , 'USE_SEO_CACHE_INFO_PAGES',
					'SEO_URLS_USE_W3C_VALID',
				);
				foreach($old_options as $option) {
					if(defined($option)) {
						$this->removeConfigurationOption($option);
					}
				}

				// Zen Cart 1.5.x specific changes
				if(function_exists('zen_deregister_admin_pages') && zen_page_key_exists('UltimateSEO')) {
					zen_deregister_admin_pages('UltimateSEO');
					if(zen_page_key_exists('UltimateSEO')) {
						$messageStack->add(SEO_UNINSTALL_ERROR_ADMIN_PAGES);
						$success = false;
					}
				}

				// Update the configuration title if it exists
				$check = $db->Execute(
					'SELECT `configuration_group_id` FROM `' . TABLE_CONFIGURATION_GROUP . '` ' .
					'WHERE `configuration_group_title` = \'SEO URLs\''
				);
				if(!$check->EOF) {
					zen_db_perform(
						TABLE_CONFIGURATION_GROUP,
						array('configuration_group_title' => $this->getUniqueName()),
						'update',
						'`configuration_group_id`=\'' . $check->fields['configuration_group_id'] .'\''
					);
				}

			case '2.210':
				// Enforce full and parent are not compatible
				if(defined('SEO_URL_CATEGORY_DIR') && defined('SEO_URL_FORMAT') && SEO_URL_CATEGORY_DIR == 'full' && SEO_URL_FORMAT == 'parent') {
					$data = $default['FORMAT'];
					$data['configuration_value'] = 'original';
					$this->updateConfigurationOption('USU_FORMAT', $data);
				}

			case '2.211':
				// Remove older option which is no longer used (if present)
				if(defined('SEO_REMOVE_ALL_SPEC_CHARS')) {
					$this->removeConfigurationOption('SEO_REMOVE_ALL_SPEC_CHARS');
				}

				if(defined('SEO_URLS_REMOVE_CHARS')) {
					$data = $default['REMOVE_CHARS'];
					$data['configuration_key'] = 'USU_REMOVE_CHARS';
					$data['configuration_value'] = (SEO_URLS_REMOVE_CHARS == 'alphanumerical' ? 'non-alphanumerical' : 'punctuation');
					$this->updateConfigurationOption('SEO_URLS_REMOVE_CHARS', $data);
				}

			case '2.212':
				// All the settings changed names after this version.
				// We will need to handle all the changes to not lose settings.
				if(defined('SEO_ENABLED')) {
					$data = $default['ENABLED'];
					$data['configuration_key'] = 'USU_ENABLED';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_ENABLED', $data);
				}
				if(defined('SEO_URL_CPATH')) {
					$data = $default['CPATH'];
					$data['configuration_key'] = 'USU_CPATH';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URL_CPATH', $data);
				}
				if(defined('SEO_URL_END')) {
					$data = $default['END'];
					$data['configuration_key'] = 'USU_END';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URL_END', $data);
				}
				if(defined('SEO_URL_FORMAT')) {
					$data = $default['FORMAT'];
					$data['configuration_key'] = 'USU_FORMAT';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URL_FORMAT', $data);
				}
				if(defined('SEO_URL_CATEGORY_DIR')) {
					$data = $default['CATEGORY_DIR'];
					$data['configuration_key'] = 'USU_CATEGORY_DIR';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URL_CATEGORY_DIR', $data);
				}
				if(defined('SEO_URLS_REMOVE_CHARS')) {
					$data = $default['REMOVE_CHARS'];
					$data['configuration_key'] = 'USU_REMOVE_CHARS';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URLS_REMOVE_CHARS', $data);
				}
				$pcre = '';
				if(defined('SEO_URLS_FILTER_CHARS')) {
					if(zen_not_null(SEO_URLS_FILTER_CHARS)) {
						$pcre = SEO_URLS_FILTER_CHARS . ',';
					}
					$this->removeConfigurationOption('SEO_URLS_FILTER_CHARS');
				}
				if(defined('SEO_URLS_FILTER_PCRE')) {
					$data = $default['FILTER_PCRE'];
					$data['configuration_key'] = 'USU_FILTER_PCRE';
					$data['configuration_value'] = $pcre . SEO_URLS_FILTER_PCRE;
					$this->updateConfigurationOption('SEO_URLS_FILTER_PCRE', $data);
				}
				if(defined('SEO_URLS_FILTER_SHORT_WORDS')) {
					$data = $default['FILTER_SHORT_WORDS'];
					$data['configuration_key'] = 'USU_FILTER_SHORT_WORDS';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URLS_FILTER_SHORT_WORDS', $data);
				}
				if(defined('SEO_URLS_ONLY_IN')) {
					$data = $default['FILTER_PAGES'];
					$data['configuration_key'] = 'USU_FILTER_PAGES';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URLS_ONLY_IN', $data);
				}
				if(defined('SEO_REWRITE_TYPE')) {
					$data = $default['ENGINE'];
					$data['configuration_key'] = 'USU_ENGINE';
					$this->updateConfigurationOption('SEO_REWRITE_TYPE', $data);
				}
				if(defined('SEO_USE_REDIRECT')) {
					$data = $default['REDIRECT'];
					$data['configuration_key'] = 'USU_REDIRECT';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_USE_REDIRECT', $data);
				}
				if(defined('SEO_USE_CACHE_GLOBAL')) {
					$data = $default['CACHE_GLOBAL'];
					$data['configuration_key'] = 'USU_CACHE_GLOBAL';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_USE_CACHE_GLOBAL', $data);
				}
				if(defined('SEO_USE_CACHE_PRODUCTS')) {
					$data = $default['CACHE_PRODUCTS'];
					$data['configuration_key'] = 'USU_CACHE_PRODUCTS';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_USE_CACHE_PRODUCTS', $data);
				}
				if(defined('SEO_USE_CACHE_CATEGORIES')) {
					$data = $default['CACHE_CATEGORIES'];
					$data['configuration_key'] = 'USU_CACHE_CATEGORIES';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_USE_CACHE_CATEGORIES', $data);
				}
				if(defined('SEO_USE_CACHE_MANUFACTURERS')) {
					$data = $default['CACHE_MANUFACTURERS'];
					$data['configuration_key'] = 'USU_CACHE_MANUFACTURERS';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_USE_CACHE_MANUFACTURERS', $data);
				}
				if(defined('SEO_USE_CACHE_EZ_PAGES')) {
					$data = $default['CACHE_EZ_PAGES'];
					$data['configuration_key'] = 'USU_CACHE_EZ_PAGES';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_USE_CACHE_EZ_PAGES', $data);
				}
				if(defined('SEO_URLS_CACHE_RESET')) {
					$data = $default['CACHE_RESET'];
					$data['configuration_key'] = 'USU_CACHE_RESET';
					unset($data['configuration_value']);
					$this->updateConfigurationOption('SEO_URLS_CACHE_RESET', $data);
				}

				// Zen Cart 1.5.x specific changes
				if(function_exists('zen_deregister_admin_pages') && zen_page_key_exists('configUltimateSEO')) {
					zen_deregister_admin_pages('configUltimateSEO');
					if(zen_page_key_exists('configUltimateSEO')) {
						$messageStack->add(SEO_UNINSTALL_ERROR_ADMIN_PAGES);
						$success = false;
					}
				}

				// Update the configuration title if an old one exists
				$check = $db->Execute(
					'SELECT `configuration_group_id` FROM `' . TABLE_CONFIGURATION_GROUP . '` ' .
					'WHERE `configuration_group_title` = \'Ultimate SEO\''
				);
				if(!$check->EOF) {
					zen_db_perform(
						TABLE_CONFIGURATION_GROUP,
						array('configuration_group_title' => $this->getUniqueName()),
						'update',
						'`configuration_group_id`=\'' . $check->fields['configuration_group_id'] .'\''
					);
				}

				// Remove old versions of the cache
				if($this->dbTableExists(DB_PREFIX . 'seo_cache')) {
					$db->Execute('DROP TABLE `' . DB_PREFIX . 'seo_cache`');
				}

			default:
				// Remove beta versions of the cache
				if(version_compare($version, '2.215', '<') && $this->dbTableExists(TABLE_USU_CACHE)) {
					$db->Execute('DROP TABLE `' . TABLE_USU_CACHE . '`');
				}

		}
		return $success;
	}

	/* (non-PHPdoc)
	 * Adds a check for older versions (before USU_VERSION was used)
	 *
	 * @see plugin::getCurrentVersion()
	 */
	public function getCurrentVersion() {
		$retval = parent::getCurrentVersion();

		// Scan for older unsupported versions and treat as 2.150
		if($retval == '-1' && defined('SEO_ENABLED')) {
			$retval = '2.150';
		}

		return $retval;
	}

	protected function checkZenCartVersion() {
		global $messageStack;

		$success = true;

		// Check if the installed version of Zen Cart is supported
		$zc_version = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
		if(version_compare($zc_version, '1.3.9h', '<') || version_compare($zc_version, '1.6.0-dev', '>=')) {
			$messageStack->add(
				sprintf(
					USU_PLUGIN_INSTALL_ERROR_ZENCART_VERSION,
					$this->getUniqueName(),
					$zc_version
				),
				'error'
			);

			$success = false;
		}

		return $success;
	}

	/* (non-PHPdoc)
	 * Starts by disabling the plugin if enabled
	 *
	 * @see plugin::install()
	 */
	public function install() {
		global $messageStack;

		// Start by disabling the module
		if(defined('SEO_ENABLED')) {
			zen_db_perform(
				TABLE_CONFIGURATION, array('configuration_value' => 'false'),
				'update', '`configuration_key`=\'SEO_ENABLED\''
			);
		}
		else if(defined('USU_ENABLED')) {
			zen_db_perform(
				TABLE_CONFIGURATION, array('configuration_value' => 'false'),
				'update', '`configuration_key`=\'USU_ENABLED\''
			);
		}

		// Halt on any configuration issues as they will have a negative impact.
		$success = $this->checkZenCartVersion() && $this->checkConfigure(true);

		if($success) { $success = parent::install(); }

		// Enable the module if successful
		if($success) {
			zen_db_perform(
				TABLE_CONFIGURATION, array('configuration_value' => 'true'),
				'update', '`configuration_key`=\'USU_ENABLED\''
			);
		}

		return $success;
	}

	public function getModifiedCoreFiles($install = true) {
		$retval = array();
		if($install) {
			$retval = array(
				// First one removes older versions, last one updates to new
				DIR_FS_CATALOG . DIR_WS_FUNCTIONS . 'html_output.php' => array(
					'search' => array(
						'|(?://\sUltimate\sSEO\sURLs\s+//\sThe\sHTML\shref\slink\swrapper\sfunction)?\s*?(function zen_href_link[^)]+\)\s?\{)(.*?)(.\s*/\*\s+\*\sThe\sHTML\shref\slink.*?\*/\s*)(function\soriginal_zen_href_link[^{]+{)|s',
						'|((?:\s+)function\szen_href_link(?:[^;]+);)((?:\s+)if\s?\(!zen_not_null\(\$page\)\))|s'
					),
					'replace' => array(
						'$3$1',
						'$1' . PHP_EOL .
						'    // START alternative URLs patch' . PHP_EOL .
						'    global $altURLs;' . "\n\t" . '$link = null;' . PHP_EOL .
						'    if(isset($altURLs)) {' . PHP_EOL .
						'      $link = $altURLs->href_link($page, $parameters, $connection, $add_session_id, $static, $use_dir_ws_catalog);' . PHP_EOL .
						'      if($link !== null) return $link;' . PHP_EOL .
						'    }' . PHP_EOL .
						'    // END alternative URLs patch' . PHP_EOL .
						'$2'
					)
				),
				// First one removes older versions, last one updates to new
				DIR_FS_ADMIN . DIR_WS_FUNCTIONS . 'html_output.php' => array(
					'search' => array(
						'|(?://\sUltimate\sSEO\sURLs\s+//\sThe\sHTML\shref\slink\swrapper\sfunction)?\s*?(function zen_catalog_href_link[^)]+\)\s?\{)(.*?)(.\s*)(function\soriginal_zen_catalog_href_link[^{]+{)|s',
						'|((?:\s+)function\szen_catalog_href_link(?:[^{]+){)((?:\s+)if\s?\(\s?\$connection\s?==\s?\'NONSSL\'\s?\))|s'
					),
					'replace' => array(
						'$3$1',
						'$1' . PHP_EOL .
						'    // START alternative URLs patch' . PHP_EOL .
						'    global $altURLs;' . PHP_EOL .
						'    $link = null;' . PHP_EOL .
						'    if(isset($altURLs)) {' . PHP_EOL .
						'      $link = $altURLs->href_link($page, $parameters, $connection, $add_session_id, $static, $use_dir_ws_catalog);' . PHP_EOL .
						'      if($link !== null) return $link;' . PHP_EOL .
						'    }' . PHP_EOL .
						'    // END alternative URLs patch' . PHP_EOL .
						'$2'
					)
				),
				// First one updates older versions, last one updates to new
				DIR_FS_ADMIN . 'categories.php' => array(
					'search' => array(
						'~(\$action\s=\s\(isset\(\$_GET\[\'action\'\][^;]+;(?:[^i]+.[^i]+if\s\(isset\(\$_GET\[\'(?:page|product_type|cID)\'\][^;]+;){0,3})(?:.+?reset_seo_cache\.php.+?)(\s*if\s\(!isset\(\$_SESSION\[\'categories_products_sort_order\'\])~s',
						'~(\$action\s=\s\(isset\(\$_GET\[\'action\'\][^;]+;(?:[^i]+.[^i]+if\s\(isset\(\$_GET\[\'(?:page|product_type|cID)\'\][^;]+;){0,3})(?!.+?usu_reset_cache_data.+?).+?(\s*if\s\(!isset\(\$_SESSION\[\'categories_products_sort_order\'\])~s'
					),
					'replace' => array(
						'$1$2',
						'$1' . PHP_EOL .
						'  // If the action will affect the cache entries' . PHP_EOL .
						'  if (preg_match("/(insert|update|setflag)/i", $action)) {' . PHP_EOL .
						'    usu_reset_cache_data(\'true\');' . PHP_EOL .
						'  }' . PHP_EOL .
						'$2'
					)
				),
				// First one removes older versions, last one updates to new
				DIR_FS_ADMIN . 'product.php' => array(
					'search' => array(
						'~(\$action\s?=\s?\(isset\(\$_GET\[\'action\'\][^;]+;)(?:.+?reset_seo_cache\.php.+?)(\s*if\s?\(zen_not_null\(\$action\)\)\s?\{)~s',
						'~(\$action\s?=\s?\(isset\(\$_GET\[\'action\'\][^;]+;)(?!.+?usu_reset_cache_data.+?).+?(\s*if\s?\(zen_not_null\(\$action\)\)\s?\{)~s'
					),
					'replace' => array(
						'$1$2',
						'$1' . PHP_EOL .
						'  // If the action will affect the cache entries' . PHP_EOL .
						'  if (preg_match("/(insert|update|setflag)/i", $action)) {' . PHP_EOL .
						'    usu_reset_cache_data(\'true\');' . PHP_EOL .
						'  }' . PHP_EOL .
						'$2'
					)
				),
			);

			// Apply a patch to query_cache (if present)
			if(file_exists(DIR_FS_CATALOG . DIR_WS_CLASSES . 'query_cache.php')) {
				// Run code to remove changes from old versions if present
				$retval[DIR_FS_CATALOG . DIR_WS_CLASSES . 'query_cache.php'] = array(
					'search' => array('~\s+function\sremoveFromCache\s?\(.+?}~s'),
					'replace' => array('')
				);

				// Note: This patch is included with Zen Cart >= 1.5.3
				$zcversion = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
				if(version_compare($zcversion, '1.5.3', '<')) {
					$retval[DIR_FS_CATALOG . DIR_WS_CLASSES . 'query_cache.php']['search'][] = '~(function\sisSelectStatement\(.+?\})(?:.+)~s';
					$retval[DIR_FS_CATALOG . DIR_WS_CLASSES . 'query_cache.php']['replace'][] = '$1' . PHP_EOL .
						PHP_EOL .
						'    function reset($query) {' . PHP_EOL .
						'      if (\'ALL\' == $query) {' . PHP_EOL .
						'        $this->queries = array();' . PHP_EOL .
						'        return FALSE;' . PHP_EOL .
						'      }' . PHP_EOL .
						'      unset ($this->queries[$query]);' . PHP_EOL .
						'    }' . PHP_EOL .
						'}' .PHP_EOL .
						PHP_EOL; // EXTRA EOL in Zen Cart 1.5.3
				}
			}
		}
		else {
			$retval = array(
				// First one removes old versions, second removes new versions
				DIR_FS_ADMIN . 'categories.php' => array(
					'search' => array(
						'~(\$action\s=\s\(isset\(\$_GET\[\'action\'\][^;]+;(?:[^i]+.[^i]+if\s\(isset\(\$_GET\[\'(?:page|product_type|cID)\'\][^;]+;){0,3})(?:.+?reset_seo_cache\.php.+?)(\s*if\s\(!isset\(\$_SESSION\[\'categories_products_sort_order\'\])~s',
						'~(\$action\s=\s\(isset\(\$_GET\[\'action\'\][^;]+;(?:[^i]+.[^i]+if\s\(isset\(\$_GET\[\'(?:page|product_type|cID)\'\][^;]+;){0,3})(?:.+?usu_reset_cache_data.+?)(\s*if\s\(!isset\(\$_SESSION\[\'categories_products_sort_order\'\])~s'
					),
					'replace' => '$1$2',
				),
				// First one removes old versions, second removes new versions
				DIR_FS_ADMIN . 'product.php' => array(
					'search' => array(
						'~(\$action\s?=\s?\(isset\(\$_GET\[\'action\'\]\)[^;]+;)(?:.+?reset_seo_cache\.php.+?)(\s*if\s?\(zen_not_null\(\$action\)\)\s?\{)~s',
						'~(\$action\s?=\s?\(isset\(\$_GET\[\'action\'\]\)[^;]+;)(?:.+?usu_reset_cache_data.+?)(\s*if\s?\(zen_not_null\(\$action\)\)\s?\{)~s'
					),
					'replace' => '$1$2',
				),
				// We do not remove the other modifications as they cause no
				// issues under Zen Cart 1.3.x or 1.5.x when left in place.
			);
		}

		return $retval;
	}

	protected function getZipArchivePath($type = null) {
		$paths = array(
			'install' => '3_install',
			'remove' => '4_uninstall'
		);
		$paths = array_merge(parent::getZipArchivePath(), $paths);

		if($type !== null) {
			if(array_key_exists($type, $paths)) return $paths[$type];
			return '';
		}
		return $paths;
	}

	protected function addAdditionalFilesToZipArchive($zip) {
		// Add the license files if present
		$license = realpath(DIR_FS_CATALOG . '../LICENSE');
		if(file_exists($license)) $zip->addFile($license, 'LICENSE');
		$license = realpath(DIR_FS_CATALOG . '../COPYING');
		if(file_exists($license)) $zip->addFile($license, 'COPYING');

		// Add readme files if present
		$readme = realpath(DIR_FS_CATALOG . '../readme');
		if(is_dir($readme)) {
			if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
				$files = new RecursiveDirectoryIterator($readme, FilesystemIterator::CURRENT_AS_SELF);
			}
			else {
				$files = new RecursiveDirectoryIterator($readme);
			}
			$files = new RecursiveIteratorIterator($files);
			foreach($files as $file) {
				if($file->isDot()) continue;
				$local = $this->getZipArchivePath('readme') . substr($file->getPathname(), strlen($readme));
				$local = str_replace('\\', '/', $local);
				$zip->addFile($file->getPathname(), $local);
			}
		}

		// Add the .htaccess example
		$htaccess = realpath(DIR_FS_CATALOG . '.htaccess');
		if(file_exists($htaccess)) $zip->addFile($htaccess, '2_htaccess/.htaccess');

		// Supported version and my version
		$versions = array('1.3.9h', '1.5.0', '1.5.1', '1.5.2-BETA', '1.5.3', '1.5.4');
		$my_version = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR .
				(zen_not_null(PROJECT_VERSION_PATCH1) ? '-' . PROJECT_VERSION_PATCH1 : '') .
				(zen_not_null(PROJECT_VERSION_PATCH2) ? '-' . PROJECT_VERSION_PATCH2 : '');

		// Add modified files from previous versions if present
		foreach($versions as $version) {
			if($version == $my_version) continue;
			$path = 'modified_files/' . $version;
			$folder = realpath(DIR_FS_CATALOG . '../' . $path);
			if(is_dir($folder)) {
				if(version_compare(PHP_VERSION, '5.3.0') >= 0) {
					$files = new RecursiveDirectoryIterator($folder, FilesystemIterator::CURRENT_AS_SELF);
				}
				else {
					$files = new RecursiveDirectoryIterator($folder);
				}
				$files = new RecursiveIteratorIterator($files);
				foreach($files as $file) {
					if($file->isDot()) continue;
					$local = $path . substr($file->getPathname(), strlen($folder));
					$local = str_replace('\\', '/', $local);
					$zip->addFile($file->getPathname(), $local);
				}
			}
		}

		return true;
	}
}
