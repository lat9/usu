<?php
/**
 * Part of Ultimate URLs for Zen Cart. Originally derived from Ultimate SEO URLs
 * v2.1 for osCommerce by Chemo.
 *
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @copyright Portions Copyright 2003 - 2015 Zen Cart Development Team
 * @copyright Portions Copyright 2005 Joshua Dechant
 * @copyright Portions Copyright 2005 Bobby Easland
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

/**
 * Provides methods for generating and handling alternative URLs in Zen Cart.
 *
 * This handles: canonical URLs, 301 redirects, multilingual URLs, filtering
 * product / category / ez-page names, non-cookie sessions (zenid), and caching
 * of generated URLs.
 */
class usu {
	public $canonical;

	protected $cache;
	protected $languages_id;
	protected $reg_anchors;
	protected $cache_file;
	protected $uri;
	protected $real_uri;
	protected $redirect_uri;
	protected static $unicodeEnabled;

	private $filter_pcre;
	private $filter_char;
	private $filter_page;

	function __construct($languages_id='') {
		if ($languages_id == '') $languages_id = $_SESSION['languages_id'];
		$this->languages_id = (int)$languages_id;

		$this->cache = false;

		$this->reg_anchors = array(
			'products_id' => '-p-',
			'cPath' => '-c-',
			'manufacturers_id' => '-m-',
			'pID' => '-pi-',
			'products_id_review' => '-pr-',
			'products_id_review_info' => '-pri-',
			'id' => '-ezp-',
		);

		if(null === self::$unicodeEnabled) {
			self::$unicodeEnabled = (@preg_match('/\pL/u', 'a')) ? true : false;
		}

		$this->filter_pcre = defined('USU_FILTER_PCRE') ? $this->expand(USU_FILTER_PCRE) : 'false';
		$this->filter_page = defined('USU_FILTER_PAGES') && zen_not_null(USU_FILTER_PAGES) ? explode(',', str_replace(' ', '', USU_FILTER_PAGES)) : array();

		if(defined('USU_CACHE_GLOBAL') && USU_CACHE_GLOBAL == 'true') {
			// Prepare in memory cache
			$this->cache = array(
				'PRODUCTS' => array(),
				'CATEGORIES' => array(),
				'MANUFACTURERS' => array(),
				'EZPAGES' => array()
			);

			// Handle the SQL cache options if the table exists
			global $sniffer;
			if($sniffer->table_exists(TABLE_USU_CACHE)) {
				// Cleanup the SQL caches
				$this->cache_file = 'usu_v3_';
				$this->cache_gc(); // Cleanup Cache

				// Load or generate enabled SQL caches
				if(USU_CACHE_PRODUCTS == 'true') $this->generate_products_cache();
				if(USU_CACHE_CATEGORIES == 'true') $this->generate_categories_cache();
				if(USU_CACHE_MANUFACTURERS == 'true') $this->generate_manufacturers_cache();
				if(USU_CACHE_EZ_PAGES == 'true') $this->generate_ezpages_cache();
			}
			else if(IS_ADMIN_FLAG) {
				// Message Stack will be available when loaded from the admin
				global $messageStack;
				$messageStack->add(
					sprintf(
						USU_PLUGIN_WARNING_TABLE,
						TABLE_USU_CACHE
					),
					'warning'
				);
			}
		}

		// Determine canonical (if needed)
		$this->canonical();

		// Start logging
		$this->logstream = false;
		if(defined('USU_DEBUG') && USU_DEBUG == 'true') {
			$date = new DateTime();

			// The base path for the log file is determined now to ensure it
			// will be available when this instance is garbage collected.
			$this->logfile = (defined('DIR_FS_LOGS') ? DIR_FS_LOGS : DIR_FS_SQL_CACHE . '/logs') .
				'/ultimate_urls/' . (IS_ADMIN_FLAG ? 'adm-' . ltrim(basename($_SERVER['SCRIPT_NAME'], '.php')) : $_GET['main_page']) .
				$date->format('-Y-M-d');

			// Open a new log stream
			$this->logstream = fopen('php://temp', 'r+');

			// Log startup information
			$this->log('============================================================');
			$this->log('===== URL Generation Log Started ' . $date->format('Y-M-d\TH:i:s'));
			$this->log('============================================================');
			$this->log('Real URI:      ' . $this->real_uri);
			$this->log('Request URI:   ' . $this->uri);
			$this->log('Canonical URI: ' . $this->canonical);
			$this->log(PHP_EOL, false);
		}

		// Redirect if enabled and neccessary
		if(defined('USU_REDIRECT') && USU_REDIRECT == 'true') {
			$this->log('============================================================');
			$this->log('===== Redirect Check Requested.');
			$this->log('============================================================');

			if($this->needs_redirect()) {
				$this->redirect();
			}
			$this->log(PHP_EOL, false);
		}

		// Minor performance increase when not enabled
		if($this->logstream !== false) {
			$this->log('============================================================');
			$this->log('===== Generated Alternative URLs');
			$this->log('============================================================');
			$this->log(PHP_EOL, false);
		}
	}

	/**
	 * Generates the link to the requested page suitable for use in an href
	 * paramater.
	 *
	 * @param string $page the name of the page
	 * @param string $parameters any paramaters for the page
	 * @param string $connection 'NONSSL' or 'SSL' the type of connection to use
	 * @param bool $add_session_id true if a session id be added to the url, false otherwise
	 * @param bool $search_engine_safe true if we should use search engine safe urls, false otherwise
	 * @param bool $static true if this is a static page (no paramaters)
	 * @param bool $use_dir_ws_catalog true if we should use the DIR_WS_CATALOG / DIR_WS_HTTPS_CATALOG from the configuration
	 * @return NULL|string
	 */
	public function href_link($page = '', $parameters = '', $connection = 'NONSSL', $add_session_id = true, $search_engine_safe = true, $static = false, $use_dir_ws_catalog = true) {

		// Do not create an alternate URI when disabled
		if(!defined('USU_ENABLED') || USU_ENABLED == 'false') {
			return null;
		}

		$this->log(PHP_EOL, false);
		$this->log(
			'Request sent to href_link(' . var_export($page, true) . ', ' .
			var_export($parameters, true) . ', ' . var_export($connection, true) . ', ' .
			var_export($add_session_id, true) . ', ' . var_export($search_engine_safe, true) . ', ' .
			var_export($static, true) . ', ' . var_export($use_dir_ws_catalog, true) . ')'
		);

		// If the request was for a real file (which called application_top)
		// We should not create an alternate URI (such as ipn_main_handler.php).
		if(zen_not_null($page) && $page != FILENAME_DEFAULT && $this->is_physical_file($page)) {
			$this->log('Request was to a physical file, URI not generated!');
			return null;
		}

		// Much of the code in Zen Cart creates the dynamic link by itself before
		// passing the code to zen_href_link and then just claims the link is
		// static and passes no params. Much of the code also has the bad habit
		// of claiming a link is "static" when it is not. So we ignore the value
		// of "static" if the page starts with "index.php?"
		if(strstr($page, 'index.php?') !== false) {

			// If we find the main_page parse the URL
			$result = array();
			if(preg_match('/[?&]main_page=([^&]*)/', $page, $result) === 1) {
				$temp = parse_url($page);

				// Adjust the page and parameters to be correct. This mainly
				// fixes the handling of EZ-Pages (but may fix additional pages).
				$page = $result[1];

				$temp['query'] = preg_replace('/main_page=' . $result[1] . '/', '', $temp['query']);
				$parameters = $temp['query'] . ($parameters != '' ? '&' . $parameters : '');
			}
		}

		// Remove the end from the page if it is present
		$pos = strrpos($page, USU_END);
		if($pos !== false) {
			$page = substr($page, 0, $pos);
		}
		unset($pos);

		// Do not rewrite if page is not in the list of pages to rewrite
		if(!$this->filter_page($page)) {
			$this->log('Page was not in the list of pages to rewrite, URI not generated!');
			return null;
		}

		// The base URL for the request
		if(IS_ADMIN_FLAG === true) {
			if($connection == 'SSL' && ENABLE_SSL == 'true') {
				$link = HTTPS_CATALOG_SERVER;
				if($use_dir_ws_catalog) $link .= DIR_WS_HTTPS_CATALOG;
			}
			else {
				$link = HTTP_CATALOG_SERVER;
				if($use_dir_ws_catalog) $link .= DIR_WS_CATALOG;
			}
		}
		else if($connection == 'SSL' && ENABLE_SSL == 'true') {
			$link = HTTPS_SERVER;
			if($use_dir_ws_catalog) $link .= DIR_WS_HTTPS_CATALOG;
		}
		else {
			$link = HTTP_SERVER;
			if($use_dir_ws_catalog) $link .= DIR_WS_CATALOG;
		}

		// We start with no separator, so define one.
		$separator = '?';
		if (zen_not_null($parameters)) {
			$link .= $this->parse_parameters($page, $parameters, $separator);
		} else {
			$link .= (($page != FILENAME_DEFAULT && $page != '') ? $page . USU_END : '');
		}

		$link = $this->add_sid($link, $add_session_id, $connection, $separator);

		$this->log('Generated URL: ' . htmlspecialchars($link, ENT_QUOTES, CHARSET, false));
		return htmlspecialchars($link, ENT_QUOTES, CHARSET, false);
	}

	/**
	 * Adds the sid to the end of the URL if needed. If a page cache has been
	 * enabled and no customer is logged in the sid is replaced with '<zinsid>'.
	 *
	 * @param string $link current URL.
	 * @param bool $add_session_id true if a session id be added to the url, false otherwise
	 * @param string $connection 'NONSSL' or 'SSL' the type of connection to use
	 * @param string $separator the separator to use between the link and this paramater (if added)
	 * @return string
	 */
	protected function add_sid($link, $add_session_id, $connection, $separator) {
		global $request_type, $http_domain, $https_domain, $session_started;

		$_sid = null;
		if(($add_session_id == true) && ($session_started) && (SESSION_FORCE_COOKIE_USE == 'False')) {
			if(defined('SID') && zen_not_null(constant('SID'))) {
				$_sid = constant('SID');
			}
			else if((($request_type == 'NONSSL') && ($connection == 'SSL') && (ENABLE_SSL == 'true')) || (($request_type == 'SSL') && ($connection == 'NONSSL'))) {
				if($http_domain != $https_domain) {
					$_sid = zen_session_name() . '=' . zen_session_id();
				}
			}
		}

		switch(true){
			case (!isset($_SESSION['customer_id']) && defined('ENABLE_PAGE_CACHE') && ENABLE_PAGE_CACHE == 'true' && class_exists('page_cache')):
				$return = $link . $separator . '<zensid>';
				break;
			case (zen_not_null($_sid)):
				$return = $link . $separator . $_sid;
				break;
			default:
				$return = $link;
				break;
		}
		return $return;
	}

	/**
	 * Parses the paramaters for a page to generate a valid url for the page.
	 *
	 * @param string $page the name of the page
	 * @param string $params any paramaters for the page
	 * @param string $separator the separator to use between the link and this paramater (if needed)
	 * @return Ambigous <string, unknown>
	 */
	protected function parse_parameters($page, $params, &$separator) {
		// We always need cPath to be first, so find and extract
		$cPath = array();
		if(1 === preg_match('/(?:^|&)c[Pp]ath=([^&]+)/', $params, $cPath)) {
			$params = str_replace($cPath[0], '', $params);
			$cPath = $cPath[1];
		}

		// Cleanup parameters and convert to initial array
		$params = trim($params, "?& \t\n\r\0\x0B");
		$p = array();
		if(zen_not_null($params)) {
			$p = @explode('&', $params);
		}

		// Add the cPath to the start of the parameters if present
		if(is_string($cPath)) array_unshift($p, 'cPath=' . $cPath);

		$this->log('Parsing Parameters for ' . $page);
		$this->log(var_export($p, true));

		$container = array();
		foreach ($p as $index => $valuepair){
			$p2 = @explode('=', $valuepair);

			switch ($p2[0]){
				case 'products_id':
					// Make sure if uprid is passed it is converted to the correct pid
					$p2[1] = zen_get_prid($p2[1]);

					switch(true) {
						// Handle product urls (check page against handler)
						case ($page == zen_get_info_page($p2[1])):

							// If a cPath was present we need to determine the immediate parent cid
							$cID = null;
							if(array_key_exists('cPath', $container)) {
								$cID = strrpos($container['cPath'], '_');
								if($cID !== false)
								{
									$cID = substr($container['cPath'], $cID+1);
								}
								else {
									$cID = $container['cPath'];
								}

								if(USU_CATEGORY_DIR != 'off' || USU_CPATH != 'auto') {
									unset($container['cPath']);
								}
							}
							$url = $this->make_url($page, $this->get_product_name($p2[1], $cID), $p2[0], $p2[1], USU_END, $separator);
							break;
						case ($page == FILENAME_PRODUCT_REVIEWS):
							if(array_key_exists('cPath', $container) && (USU_CATEGORY_DIR != 'off' || USU_CPATH != 'auto')) unset($container['cPath']);
							$url = $this->make_url($page, $this->get_product_name($p2[1], $cID), 'products_id_review', $p2[1], USU_END, $separator);
							break;
						case ($page == FILENAME_PRODUCT_REVIEWS_INFO):
							if(array_key_exists('cPath', $container) && (USU_CATEGORY_DIR != 'off' || USU_CPATH != 'auto')) unset($container['cPath']);
							$url = $this->make_url($page, $this->get_product_name($p2[1], $cID), 'products_id_review_info', $p2[1], USU_END, $separator);
							break;
						default:
							$container[$p2[0]] = $p2[1];
							break;
					}
					break;

				case 'cPath':
					switch(true){
						case ($p2[1] == ''):
							// Do nothing if cPath is empty
							break;

						case ($page == FILENAME_DEFAULT):
							// Change $p2[1] to the actual category id
							$tmp = strrpos($p2[1], '_');
							if($tmp !== false)
							{
								$p2[1] = substr($p2[1], $tmp+1);
							}

							$category = $this->get_category_name($p2[1]);
							if(USU_CATEGORY_DIR == 'off') {
								$url = $this->make_url($page, $category, $p2[0], $p2[1], USU_END, $separator);
							}
							else {
								$url = $this->make_url($page, $category, $p2[0], $p2[1], '/', $separator);
							}
							unset($category);
							break;

						default:
							$container[$p2[0]] = $p2[1];
							break;
						}
					break;

				case 'manufacturers_id':
					switch(true){
						case ($page == FILENAME_DEFAULT && !$this->is_cPath_string($params) && !$this->is_product_string($params)):
							$url = $this->make_url($page, $this->get_manufacturer_name($p2[1]), $p2[0], $p2[1], USU_END, $separator);
							break;
						case ($page == FILENAME_PRODUCT_INFO):
							break;
						default:
							$container[$p2[0]] = $p2[1];
							break;
						}
					break;

				case 'pID':
					switch(true){
						case ($page == FILENAME_POPUP_IMAGE):
						$url = $this->make_url($page, $this->get_product_name($p2[1]), $p2[0], $p2[1], USU_END, $separator);
						break;
					default:
						$container[$p2[0]] = $p2[1];
						break;
					}
					break;

				case 'id':	// EZ-Pages
					switch(true){
						case ($page == FILENAME_EZPAGES):
							$url = $this->make_url($page, $this->get_ezpages_name($p2[1]), $p2[0], $p2[1], USU_END, $separator);
							break;
						default:
							$container[$p2[0]] = $p2[1];
							break;
						}
					break;

				default:
					$container[$p2[0]] = $p2[1];
					break;
			}
		}

		$url = isset($url) ? $url : $page . USU_END;
		if(sizeof($container) > 0) {
			if($imploded_params = $this->build_query($container)) {
				$url .= $separator . zen_output_string($imploded_params);
				$separator = '&';
			}
		}

		return $url;
	}

	/**
	 * Convert an array of query parameters to a URI query string. This is safe
	 * for use under 5.2+ with optimizations for PHP 5.4+.
	 *
	 * @param array the array of query parameters
	 */
	protected function build_query($parameters) {
		if(version_compare(PHP_VERSION, '5.4.0') >= 0) {
			$parameters = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
		}
		else {
			$compile = array();
			foreach($parameters as $key => $value) {
				if($key !== null && $key != '') {
					// Prior to PHP 5.3, tildes might be encoded per RFC 1738
					// This should not impact functionality for 99% of users.
					$compile[] = rawurlencode($key) . '=' . rawurlencode($value);
				}
			}
			$parameters = implode('&', $compile);
			unset($compile);
		}
		return $parameters;
	}

	/**
	 * Generates the url for the given page and paramaters.
	 *
	 * @param string $page the page for the link
	 * @param string $link the name to use for the url
	 * @param string $anchor_type the last paramater parsed type (products_id, cPath, etc.)
	 * @param string $id the last paramater parsed id (or cPath)
	 * @param string $extension Default =
	 * @param string $separator NOTE: passed by reference
	 * @return string the final generated url
	 */
	protected function make_url($page, $link, $anchor_type, $id, $extension = USU_END, &$separator){
		// In the future there may be additional methods here in the switch
		switch (USU_ENGINE){
			case 'rewrite':
				return $link . $this->reg_anchors[$anchor_type] . $id . $extension;
				break;
			default:
				break;
		}
	}

	/**
	 * Function to get the product name. Use evaluated cache, per page cache,
	 * or database query in that order of precedent.
	 *
	 * @param integer $pID
	 * @return string product name
	 */
	public function get_product_name($pID, $cID = null) {
		global $db;

		// Handle generating the product name
		switch(true) {
			case (defined('PRODUCT_NAME_' . $pID)):
				$return = constant('PRODUCT_NAME_' . $pID);
				break;

			case (is_array($this->cache) && isset($this->cache['PRODUCTS'][$pID])):
				$return = $this->cache['PRODUCTS'][$pID];
				break;

			default:
				if(USU_FORMAT == 'parent') {
					$sql = 'SELECT `pd`.`products_name` AS `pName`, `ptc`.`categories_id` AS `c_id`, `p`.`master_categories_id` AS `master_id` ' .
						'FROM `' . TABLE_PRODUCTS_DESCRIPTION . '` AS `pd` ' .
						'LEFT JOIN `' . TABLE_PRODUCTS . '` AS `p` ' .
						'ON `pd`.`products_id`=`p`.`products_id` ' .
						'LEFT JOIN `' . TABLE_PRODUCTS_TO_CATEGORIES . '` AS `ptc` ' .
						'ON `pd`.`products_id`=`ptc`.`products_id` ' .
						'WHERE `pd`.`products_id`=\':pid:\' ' .
						($cID !== null ? 'AND `ptc`.`categories_id` = \':cid:\'' : '' ) .
						'AND `language_id`=\':language:\' LIMIT 1';
					$sql = $db->bindVars($sql, ':cid:', $cID, 'integer');
				}
				else {
					$sql = 'SELECT `pd`.`products_name` as `pName` ' .
						'FROM `' . TABLE_PRODUCTS_DESCRIPTION . '` AS `pd` ' .
						'WHERE `products_id`=\':pid:\' ' .
						'AND `language_id`=\':language:\' LIMIT 1';
				}
				$sql = $db->bindVars($sql, ':pid:', $pID, 'integer');
				$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
				$result = $db->Execute($sql, false, true, 43200);
				$pName = $this->filter($result->fields['pName']);

				if(USU_FORMAT == 'parent' && USU_CATEGORY_DIR == 'off') {
					$pName = $this->get_category_name($cID !== null ? $cID : (int)$result->fields['master_id'], 'original') . '-' . $pName;
				}

				if(is_array($this->cache)) {
					$this->cache['PRODUCTS'][$pID] = $pName;
				}
				$return = $pName;
				break;
		}

		// Add the category
		if(USU_CATEGORY_DIR != 'off') {
			$sql = 'SELECT `ptc`.`categories_id` AS `c_id`, `p`.`master_categories_id` AS `master_id` ' .
				'FROM `' . TABLE_PRODUCTS . '` AS `p` ' .
				'LEFT JOIN `' . TABLE_PRODUCTS_TO_CATEGORIES . '` AS `ptc` ' .
				'ON `p`.`products_id`=`ptc`.`products_id` ' .
				'WHERE `p`.`products_id`=\':pid:\' ' .
				($cID !== null ? 'AND `ptc`.`categories_id` = \':cid:\'' : '' ) .
				'LIMIT 1';
			$sql = $db->bindVars($sql, ':cid:', $cID, 'integer');
			$sql = $db->bindVars($sql, ':pid:', $pID, 'integer');

			$result = $db->Execute($sql, false, true, 43200);
			$category = '';
			if(!$result->EOF) {
				$cID = ($cID !== null ? $cID : (int)$result->fields['master_id']);
				$category = $this->get_category_name($cID) .
					$this->reg_anchors['cPath'] . $cID . '/';
			}

			$return = $category . $return;
		}

		return $return;
	}

	/**
	 * Function to get the product canonical. Use evaluated cache, per page cache,
	 * or database query in that order of precedent.
	 *
	 * @param integer $pID
	 * @return string product canonical
	 */
	public function get_product_canonical($pID) {
		global $db;

		$retval = null;
		// Only need to add the canonicals if different paths exist
		if(USU_CATEGORY_DIR != 'off') {
			$sql = 'SELECT `ptc`.`categories_id` AS `c_id`, `p`.`master_categories_id` AS `master_id` ' .
				'FROM `' . TABLE_PRODUCTS . '` AS `p` ' .
				'LEFT JOIN `' . TABLE_PRODUCTS_TO_CATEGORIES . '` AS `ptc` ' .
				'ON `p`.`products_id`=`ptc`.`products_id` ' .
				'WHERE `p`.`products_id`=\':pid:\' LIMIT 1';
			$sql = $db->bindVars($sql, ':pid:', $pID, 'integer');
			$result = $db->Execute($sql, false, true, 43200);
			if(!$result->EOF) {
				$cID = (int)$result->fields['c_id'];
				$masterID = (int)$result->fields['master_id'];

				// Master category and product category do not match. This
				// product will need a canonical.
				if($cID != $masterID) {
					$retval = $this->get_category_name($masterID) .
					$this->reg_anchors['cPath'] . $masterID . '/';

					// Get the product name
					switch(true) {
						case (USU_CACHE_GLOBAL == 'true' && defined('PRODUCT_NAME_' . $pID)):
							$retval .= constant('PRODUCT_NAME_' . $pID);
							break;

						case (USU_CACHE_GLOBAL == 'true' && isset($this->cache['PRODUCTS'][$pID])):
							$retval .= $this->cache['PRODUCTS'][$pID];
							break;

						default:
							if(USU_FORMAT == 'parent') {
								$sql = 'SELECT `pd`.`products_name` AS `pName`, `ptc`.`categories_id` AS `c_id`, `p`.`master_categories_id` AS `master_id` ' .
									'FROM `' . TABLE_PRODUCTS_DESCRIPTION . '` AS `pd` ' .
									'LEFT JOIN `' . TABLE_PRODUCTS . '` AS `p` ' .
									'ON `pd`.`products_id`=`p`.`products_id` ' .
									'LEFT JOIN `' . TABLE_PRODUCTS_TO_CATEGORIES . '` AS `ptc` ' .
									'ON `pd`.`products_id`=`ptc`.`products_id` ' .
									'WHERE `pd`.`products_id`=\':pid:\' ' .
									'AND `language_id`=\':language:\' LIMIT 1';
							}
							else {
								$sql = 'SELECT `pd`.`products_name` as `pName` ' .
									'FROM `' . TABLE_PRODUCTS_DESCRIPTION . '` AS `pd` ' .
									'WHERE `products_id`=\':pid:\' ' .
									'AND `language_id`=\':language:\' LIMIT 1';
							}
							$sql = $db->bindVars($sql, ':pid:', $pID, 'integer');
							$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
							$result = $db->Execute($sql, false, true, 43200);
							$pName = $this->filter($result->fields['pName']);

							if(USU_FORMAT == 'parent' && USU_CATEGORY_DIR == 'off') {
								$cID = (int)$result->fields['c_id'];
								$pName = $this->get_category_name($cID, 'original') . '-' . $pName;
							}

							$retval .= $pName;
							unset($pName);
							break;
					}
				}
			}
			unset($sql, $result);
		}

		return $retval;
	}

	/**
	 * Function to get the category name. Use evaluated cache, per page cache,
	 * or database query in that order of precedent
	 * @param integer $cID NOTE: passed by reference
	 * @return string category name
	 */
	public function get_category_name(&$cID, $format = USU_FORMAT){
		global $db;

		$single_cID = $cID;
		$full_cPath = $this->get_full_cPath($cID, $single_cID); // full cPath needed for uniformity
		switch(true){
			case (defined('CATEGORY_NAME_' . $full_cPath) && $format == USU_FORMAT):
				$return = constant('CATEGORY_NAME_' . $full_cPath);
				break;

			case (is_array($this->cache) && isset($this->cache['CATEGORIES'][$full_cPath]) && $format == USU_FORMAT):
				$return = $this->cache['CATEGORIES'][$full_cPath];
				break;

			default:
				$cName = '';
				if(USU_CATEGORY_DIR == 'full') {
					$path = array();
					$this->get_parent_categories_path($path, $single_cID);
					if(sizeof($path) > 0) {
						$cName = implode('/', $path);
						$cut = strrpos($cName, $this->reg_anchors['cPath']);
						if($cut !== false) $cName = substr($cName, 0, $cut);
						unset($cut);
					}
					unset($path);
				}
				else if($format == 'parent') {
					$sql = 'SELECT `c`.`categories_id`, `c`.`parent_id`, `cd`.`categories_name` AS `cName`, `cd2`.`categories_name` AS `pName` ' .
						'FROM `' . TABLE_CATEGORIES_DESCRIPTION . '` AS `cd`, `' . TABLE_CATEGORIES . '` AS `c` ' .
						'LEFT JOIN `' . TABLE_CATEGORIES_DESCRIPTION . '` AS `cd2` ' .
						'ON `c`.`parent_id`=`cd2`.`categories_id` AND `cd2`.`language_id`=\':language:\' ' .
						'WHERE `c`.`categories_id`=\':cid:\' ' .
						'AND `cd`.`categories_id`=\':cid:\' ' .
						'AND `cd`.`language_id`=\':language:\' ' .
						'LIMIT 1';
					$sql = $db->bindVars($sql, ':cid:', $single_cID, 'integer');
					$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
					$result = $db->Execute($sql, false, true, 43200);
					if(!$result->EOF) {
						$cName = zen_not_null($result->fields['pName']) ? $this->filter($result->fields['pName'] . ' ' . $result->fields['cName']) : $this->filter($result->fields['cName']);
					}
				}
				else {
					$sql = 'SELECT `categories_name` AS `cName` ' .
						'FROM `' . TABLE_CATEGORIES_DESCRIPTION . '` ' .
						'WHERE `categories_id`=\':cid:\' ' .
						'AND `language_id`=\':language:\' ' .
						'LIMIT 1';
					$sql = $db->bindVars($sql, ':cid:', $single_cID, 'integer');
					$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
					$result = $db->Execute($sql, false, true, 43200);
					if(!$result->EOF) $cName = $this->filter($result->fields['cName']);
				}

				if(is_array($this->cache)) {
					$this->cache['CATEGORIES'][$full_cPath] = $cName;
				}
				$return = $cName;
				break;
		}
		$cID = $full_cPath;
		return $return;
	}

	/**
	 * Function to get the manufacturer name. Use evaluated cache, per page cache,
	 * or database query in that order of precedent
	 * @param integer $mID
	 * @return string manufacturer name
	 */
	public function get_manufacturer_name($mID) {
		global $db;

		switch(true){
			case (defined('MANUFACTURER_NAME_' . $mID)):
				$return = constant('MANUFACTURER_NAME_' . $mID);
				break;

			case (is_array($this->cache) && isset($this->cache['MANUFACTURERS'][$mID])):
				$return = $this->cache['MANUFACTURERS'][$mID];
				break;

			default:
				$sql = 'SELECT `manufacturers_name` as `mName` ' .
					'FROM `' . TABLE_MANUFACTURERS . '` ' .
					'WHERE `manufacturers_id` = \':mid:\' LIMIT 1';
				$sql = $db->bindVars($sql, ':mid:', $mID, 'integer');
				$result = $db->Execute($sql, false, true, 43200);
				$mName = $this->filter($result->fields['mName']);

				if(is_array($this->cache)) {
					$this->cache['MANUFACTURERS'][$mID] = $mName;
				}
				$return = $mName;
				break;
		}
		return $return;
	}

	/**
	 * Function to get the expage name. Use evaluated cache, per page cache,
	 * or database query in that order of precedent
	 * @param integer $ezpID
	 * @return string expage name
	 */
	public function get_ezpages_name($ezpID) {
		global $db;

		switch(true){
			case (defined('EZPAGES_NAME_' . $ezpID)):
				$return = constant('EZPAGES_NAME_' . $ezpID);
				break;

			case (is_array($this->cache) && isset($this->cache['EZPAGES'][$ezpID])):
				$return = $this->cache['EZPAGES'][$ezpID];
				break;

			default:
				if(defined('TABLE_EZPAGES_TEXT')) {
					$sql = 'SELECT `et`.`pages_title` AS `ezpName` ' .
						'FROM `' . TABLE_EZPAGES . '` AS `e`, `' . TABLE_EZPAGES_TEXT . '` AS `et` ' .
						'WHERE `e`.`pages_id`=\':ezpage:\' ' .
						'AND `e`.`pages_id` = `et`.`pages_id` ' .
						'AND `et`.`languages_id` = \':language:\' ' .
						'LIMIT 1';
					$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
				}
				else {
					$sql = 'SELECT `pages_title` AS `ezpName` ' .
						'FROM `' . TABLE_EZPAGES . '` ' .
						'WHERE `pages_id`=\':ezpage:\' ' .
						'LIMIT 1';
				}
				$sql = $db->bindVars($sql, ':ezpage:', $ezpID, 'integer');
				$result = $db->Execute($sql, false, true, 43200);
				$ezpName = $this->filter($result->fields['ezpName']);

				if(is_array($this->cache)) {
					$this->cache['EZPAGES'][$ezpID] = $ezpName;
				}
				$return = $ezpName;
				break;
		}
		return $return;
	}

/**
 * Function to retrieve full cPath from category ID
 * @author Bobby Easland
 * @version 1.1
 * @param mixed $cID Could contain cPath or single category_id
 * @param integer $original Single category_id passed back by reference
 * @return string Full cPath string
 */
	public function get_full_cPath($cID, &$original){
		if ( is_numeric(strpos($cID, '_')) ){
			$temp = @explode('_', $cID);
			$original = $temp[sizeof($temp)-1];
			return $cID;
		} else {
			$c = array();
			$this->get_parent_categories_id($c, $cID);
			$c = array_reverse($c);
			$c[] = $cID;
			$original = $cID;
			$cID = sizeof($c) > 1 ? implode('_', $c) : $cID;
			return $cID;
		}
	} # end function

/**
 * Recursion function to retrieve parent categories from category ID
 * @author Bobby Easland
 * @version 1.0
 * @param mixed $categories Passed by reference
 * @param integer $categories_id
 */
	public function get_parent_categories_id(&$categories, $categories_id) {
		global $db;

		$sql = 'SELECT `parent_id` FROM `' . TABLE_CATEGORIES . '` ' .
			'WHERE `categories_id`=\':cid:\'';
		$sql = $db->bindVars($sql, ':cid:', $categories_id, 'integer');
		$parent_categories = $db->Execute($sql);

		while (!$parent_categories->EOF) {
			if ($parent_categories->fields['parent_id'] == 0) return true;

			$categories[sizeof($categories)] = $parent_categories->fields['parent_id'];

			if ($parent_categories->fields['parent_id'] != $categories_id) {
				$this->get_parent_categories_id($categories, $parent_categories->fields['parent_id']);
			}

			$parent_categories->MoveNext();
		}
	}

	/**
	 * Recursion function to retrieve parent categories from category ID.
	 *
	 * @author Andrew Ballanger
	 * @param mixed $path Passed by reference
	 * @param integer $categories_id
	 */
	public function get_parent_categories_path(&$path, $categories_id, &$cPath = array()) {
		global $db;

		$sql = 'SELECT `c`.`parent_id` AS `p_id`, `cd`.`categories_name` AS `name` ' .
			'FROM `' . TABLE_CATEGORIES . '` AS `c` ' .
			'LEFT JOIN `' . TABLE_CATEGORIES_DESCRIPTION . '` AS `cd` ' .
			'ON `c`.`categories_id`=`cd`.`categories_id` ' .
			'AND `cd`.`language_id`=\':language:\'' .
			'WHERE `c`.`categories_id`=\':cid:\'';
		$sql = $db->bindVars($sql, ':cid:', $categories_id, 'integer');
		$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
		$parent = $db->Execute($sql, false, true, 43200);

		if(!$parent->EOF) {

			// Recurse if the parent id is not empty or equal the passed categories id
			if ($parent->fields['p_id'] != 0 && $parent->fields['p_id'] != $categories_id) {
				$this->get_parent_categories_path($path, $parent->fields['p_id'], $cPath);
			}

			// Add category id to cPath and name to path
			$cPath[sizeof($cPath)] = $categories_id;
			$path[sizeof($path)] = $this->filter($parent->fields['name']) . '-c-' .
			(sizeof($cPath) > 1 ? implode('_', $cPath) : $categories_id);
		}
	}

	protected function is_attribute_string($params){
		if (preg_match('/products_id=([0-9]+):([a-zA-z0-9]{32})/', $params)) {
			return true;
		}

		return false;
	}

	protected function is_product_string($params) {
		if (preg_match('/products_id=/i', $params)) {
			return true;
		}

		return false;
	}

	protected function is_cPath_string($params) {
		if (preg_match('/cPath=/i', $params)) {
			return true;
		}

		return false;
	}

	/**
	 * Function to filter a string and remove punctuation and white space.
	 *
	 * @param string $string input text
	 * @return string filtered text
	 */
	public function filter($string){
		$retval = $string;

		// First filter using PCRE Rules
		if(is_array($this->filter_pcre))
		{
			$retval = preg_replace(
				array_keys($this->filter_pcre),
				array_values($this->filter_pcre),
				$retval
			);
		}

		// Next run character filters over the string
		$pattern = '';
		// Remove Special Characters from the strings
		switch(USU_REMOVE_CHARS) {
			default:
			case 'non-alphanumerical':
				// Remove all non alphanumeric characters
				if(!self::$unicodeEnabled) {
					// POSIX named classes are not supported by preg_replace
					$pattern = '/[^a-zA-Z0-9\s]/';
				} else {
					// Each language's alphabet.
					$pattern = '/[^\p{L}\p{N}\s]/u';
				}
				break;
			case 'punctuation':
				// Remove all punctuation
				if(!self::$unicodeEnabled) {
					// POSIX named classes are not supported by preg_replace
					$pattern = '/[!"#$%&\'()*+,.\/:;<=>?@[\\\]^_`{|}~]/';
				} else {
					// Each language's punctuation.
					$pattern = '/[\p{P}\p{S}]/u';
				}

				break;
		}
		if(function_exists('mb_strtolower')) {
			$retval = mb_strtolower($retval);
		}
		else {
			$retval = strtolower($retval);
		}
		$retval = preg_replace($pattern, '', $retval);

		// Replace any remaining whitespace with a -
		$retval = preg_replace('/\s/', '-', $retval);

		// return the short filtered and urlencoded name
		return rawurlencode($this->short_name($retval));
	}

	/**
	 * Function to convert regexp settings (PCRE) to an array
	 *
	 * @param string $regexp the regexp string from the database
	 * @return mixed
	 */
	public function expand($regexp) {
		if(zen_not_null($regexp)) {
			if($data = @explode(',', $regexp)) {
				$container = array();
				foreach($data as $index => $valuepair) {
					$p = @explode('=>', $valuepair);

					// Add the neccessary regexp start / end characters
					if(preg_match('|^\|.*?\|$|', $p[0]) === 0) {
						$p[0] = '|' . str_replace('|', '\|', $p[0]) . '|';
					}

					$container[trim($p[0])] = trim($p[1]);
				}
				return $container;
			} else {
				return 'false';
			}
		} else {
			return 'false';
		}
	}

/**
 * Function to return the short word filtered string
 * @author Bobby Easland
 * @version 1.0
 * @param string $str
 * @param integer $limit
 * @return string Short word filtered
 */
	public function short_name($str, $limit=3){
		if(defined('USU_FILTER_SHORT_WORDS')) $limit = (int)USU_FILTER_SHORT_WORDS;
		$foo = @explode('-', $str);
		$container = array();
		foreach($foo as $index => $value){
			switch (true){
				case ( strlen($value) <= $limit ):
					continue;
				default:
					$container[] = $value;
					break;
			}
		} # end foreach
		$container = ( sizeof($container) > 1 ? implode('-', $container) : $str );
		return $container;
	}

	/**
	 * Function to generate EZ-Pages cache entries
	 */
	public function generate_ezpages_cache() {
		global $db;

		$is_cached = false;
		$is_expired = false;
		$this->is_cached($this->cache_file . 'ezpages', $is_cached, $is_expired);
		if (!$is_cached || $is_expired) {
			if(defined('TABLE_EZPAGES_TEXT')) {
				$sql = 'SELECT `e`.`pages_id` AS `id`, `et`.`pages_title` AS `name` ' .
					'FROM `' . TABLE_EZPAGES . '` AS `e`, `' . TABLE_EZPAGES_TEXT . '` AS `et` ' .
					'WHERE `e`.`pages_id` = `et`.`pages_id` ' .
					'AND `et`.`languages_id` = \':language:\'';
				$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
			}
			else {
				$sql = 'SELECT `pages_id` AS `id`, `pages_title` AS `name` ' .
					'FROM `' . TABLE_EZPAGES . '`';
			}

			$ezpages = $db->Execute($sql, false, true, 43200);
			while (!$ezpages->EOF) {
				$this->cache['EZPAGES'][(int)$ezpages->fields['id']] = $this->filter($ezpages->fields['name']);
				$ezpages->MoveNext();
			}
			$this->save_cache($this->cache_file . 'ezpages', $this->cache['EZPAGES'], 1 , 1);
		} else {
			$this->cache['EZPAGES'] = $this->get_cache($this->cache_file . 'ezpages');
		}
	}

	/**
	 * Function to generate products cache entries
	 */
	public function generate_products_cache() {
		global $db;

		$is_cached = false;
		$is_expired = false;
		$this->is_cached($this->cache_file . 'products', $is_cached, $is_expired);
		if(!$is_cached || $is_expired) {
			if(USU_FORMAT == 'parent') {
				$sql = 'SELECT `p`.`products_id` AS `id`, `ptc`.`categories_id` AS `c_id`, `p`.`master_categories_id` AS `master_id`, `pd`.`products_name` AS `name` ' .
					'FROM `' . TABLE_PRODUCTS  . '` AS `p` ' .
					'LEFT JOIN `' . TABLE_PRODUCTS_DESCRIPTION . '` AS `pd` ' .
					'ON p.products_id=pd.products_id ' .
					'LEFT JOIN `' . TABLE_PRODUCTS_TO_CATEGORIES . '` AS `ptc` ' .
					'ON `p`.`products_id`=`ptc`.`products_id` ' .
					'WHERE `p`.`products_status`=\'1\' ' .
					'AND `pd`.`language_id`=\':language:\'';
			}
			else {
				$sql = 'SELECT `p`.`products_id` AS `id`, `pd`.`products_name` AS `name` ' .
					'FROM `' . TABLE_PRODUCTS  . '` AS `p` ' .
					'LEFT JOIN `' . TABLE_PRODUCTS_DESCRIPTION . '` AS `pd` ' .
					'ON `p`.`products_id`=`pd`.`products_id` ' .
					'WHERE `p`.`products_status`=\'1\' ' .
					'AND `pd`.`language_id`=\':language:\'';
			}
			$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
			$product = $db->Execute($sql, false, true, 43200);
			while (!$product->EOF) {
				$pName = $this->filter($product->fields['name']);

				if(USU_FORMAT == 'parent' && USU_CATEGORY_DIR == 'off') {
					$cID = (int)$product->fields['c_id'];
					$pName = $this->get_category_name($cID, 'original') . '-' . $pName;
				}

				$this->cache['PRODUCTS'][(int)$product->fields['id']] = $pName;
				$product->MoveNext();
			}

			$this->save_cache($this->cache_file . 'products', $this->cache['PRODUCTS'], 1 , 1);
			unset($cID, $pName, $sql, $product);
		}
		else {
			$this->cache['PRODUCTS'] = $this->get_cache($this->cache_file . 'products');
		}
	}

	/**
	 * Function to generate manufacturers cache entries
	 */
	public function generate_manufacturers_cache() {
		global $db;

		$is_cached = false;
		$is_expired = false;
		$this->is_cached($this->cache_file . 'manufacturers', $is_cached, $is_expired);
		if ( !$is_cached || $is_expired ) { // it's not cached so create it
			$sql = 'SELECT `m`.`manufacturers_id` AS `id`, `m`.`manufacturers_name` AS `name` ' .
		        'FROM `' . TABLE_MANUFACTURERS . '` AS `m` ' .
				'LEFT JOIN `' . TABLE_MANUFACTURERS_INFO . '` AS `md` ' .
				'ON `m`.`manufacturers_id`=`md`.`manufacturers_id` ' .
				'AND `md`.`languages_id`=\':language:\'';
			$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
			$manufacturers = $db->Execute($sql, false, true, 43200);
			while (!$manufacturers->EOF) {
				$this->cache['MANUFACTURERS'][(int)$manufacturers->fields['id']] = $this->filter($manufacturers->fields['name']);
				$manufacturers->MoveNext();
			}
			$this->save_cache($this->cache_file . 'manufacturers', $this->cache['MANUFACTURERS'], 1 , 1);
		}
		else {
			$this->cache['MANUFACTURERS'] = $this->get_cache($this->cache_file . 'manufacturers');
		}
	}

	/**
	 * Function to generate categories cache entries
	 */
	public function generate_categories_cache() {
		global $db;

		$is_cached = false;
		$is_expired = false;
		$this->is_cached($this->cache_file . 'categories', $is_cached, $is_expired);
		if(!$is_cached || $is_expired) { // it's not cached so create it
			if(USU_FORMAT == 'parent' || USU_CATEGORY_DIR == 'short') {
				$sql = 'SELECT `c`.`categories_id` AS `id`, `c`.`parent_id`, `cd`.`categories_name` AS `cName`, `cd2`.`categories_name` as `pName` ' .
					'FROM `' . TABLE_CATEGORIES . '` AS `c` ' .
					'LEFT JOIN `' . TABLE_CATEGORIES_DESCRIPTION . '` AS `cd2` ' .
					'ON `c`.`parent_id`=`cd2`.`categories_id` AND `cd2`.`language_id`=\':language:\'' .
					', `' . TABLE_CATEGORIES_DESCRIPTION . '` AS `cd` ' .
					'WHERE `c`.`categories_id`=\'cd.categories_id\' ' .
					'AND `cd`.`language_id`=\':language:\'';
			}
			else {
				$sql = 'SELECT `categories_id` AS `id`, `categories_name` AS `cName` ' .
					'FROM `' . TABLE_CATEGORIES_DESCRIPTION . '` ' .
					'WHERE `language_id`=\':language:\'';
			}
			$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
			$category = $db->Execute($sql, false, true, 43200);
			while (!$category->EOF) {
				$cName = '';
				$single_cID = 0;
				$cPath = $this->get_full_cPath($category->fields['id'], $single_cID);
				if(USU_CATEGORY_DIR == 'full') {
					$path = array();
					$this->get_parent_categories_path($path, $single_cID);
					if(sizeof($path) > 0) {
						$cName = implode('/', $path);
						$cut = strrpos($cName, $this->reg_anchors['cPath']);
						if($cut !== false) $cName = substr($cName, 0, $cut);
						unset($cut);
					}
					unset($path);
				}
				else if (USU_FORMAT == 'parent') {
					$cName = zen_not_null($category->fields['pName']) ? $this->filter($category->fields['pName'] . ' ' . $category->fields['cName']) : $this->filter($category->fields['cName']);
				}
				else {
					$cName = $this->filter($category->fields['cName']);
				}

				$this->cache['CATEGORIES'][$cPath] = $cName;
				$category->MoveNext();
			}
			$this->save_cache($this->cache_file . 'categories', $this->cache['CATEGORIES'], 1 , 1);
		}
		else {
			$this->cache['CATEGORIES'] = $this->get_cache($this->cache_file . 'categories');
		}
	}

	/**
	 * Function to store cached data in the database. The value will be
	 * serialized before processing. Compression will be applied if requested.
	 *
	 * @param string $name name identifying the cache
	 * @param mixed $value data to be stored.
	 * @param integer $gzip Enables compression
	 * @param integer $global Sets whether cache record is global is scope
	 * @param string $expires Sets the expiration
	 */
	public function save_cache($name, $value, $gzip=1, $global=0, $expires = '+30 days'){
		global $db;

		// Serialize and Compress
		$value = serialize($value);
		if($gzip === 1) {
			$value = @gzdeflate($value, 7);
		}

		$now = new DateTime();
		$sql_data_array = array(
			'cache_id' => md5($name),
			'cache_language_id' => (int)$this->languages_id,
			'cache_name' => $name,
			'cache_data' => $value,
			'cache_global' => (int)$global,
			'cache_gzip' => (int)$gzip,
			'cache_date' => $now->format("Y-m-d H:i:s")
		);
		if($now->modify($expires) === false) {
			// Fallback to 30 days in the future
			$now->modify('+30 days');
		}
		$sql_data_array['cache_expires'] = $now->format("Y-m-d H:i:s");

		$is_cached = false;
		$is_expired = false;
		$this->is_cached($name, $is_cached, $is_expired);
		$cache_check = ( $is_cached ? 'true' : 'false' );
		switch ( $cache_check ) {
			case 'true':
				zen_db_perform(TABLE_USU_CACHE, $sql_data_array, 'update', "cache_id='".md5($name)."'");
				break;
			case 'false':
				// This code avoids a potential race condition by overwriting
				// the existing database cache entry if the insert fails.
				$sql = 'INSERT INTO `' . TABLE_USU_CACHE . '` (';
				$sql2 = ') VALUES (';
				$sql3 = ') ON DUPLICATE KEY UPDATE ';
				foreach($sql_data_array as $name => $value) {
					$sql .= '`' . $name . '`,';
					$sql2 .= '\'' . zen_db_input($value) . '\',';
					$sql3 .= '`' . $name . '`=\'' . zen_db_input($value) . '\',';
				}
				$sql = substr($sql, 0, -1) . substr($sql2, 0, -1) . substr($sql3, 0, -1);
				unset($sql2, $sql3);
				$db->Execute($sql);
				break;
			default:
				break;
		}

		unset($value, $expires, $sql_data_array);
	}

	/**
	 * Function to retrieve cached data from the database.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function get_cache($name = 'GLOBAL') {
		global $db;

		$global = ($name == 'GLOBAL' ? true : false);

		$sql = 'SELECT `cache_id`, `cache_name`, `cache_data`, ' .
				'`cache_global`, `cache_gzip`, `cache_date`, `cache_expires` ' .
			'FROM `' . TABLE_USU_CACHE . '` ' .
			'WHERE `cache_language_id` = \':language:\'';
		$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');

		if($global) {
			$sql .= 'AND `cache_global` = \'1\'';
		}
		else {
			$sql .= 'AND `cache_id` = :id:';
			$sql = $db->bindVars($sql, ':id:', md5($name), 'string');
		}

		$cache = $db->Execute($sql);
		if(!$cache->EOF) {
			$container = array();
			$now = new DateTime();
			$now = $now->format('Y-m-d H:i:s');
			while(!$cache->EOF) {
				if($cache->fields['cache_expires'] <= $now) {
					// Remove item from the cache
					$sql = 'DELETE FROM `' . TABLE_USU_CACHE . '` WHERE `cache_id` = :id:';
					$sql = $db->bindVars($sql, ':id:', $cache->fields['cache_id'], 'string');
					$db->Execute($sql);

					$container[$cache->fields['cache_name']] = false;
				}
				else {
					$cache_data = $cache->fields['cache_data'];
					if($cache->fields['cache_gzip'] == '1') {
						$cache_data = @gzinflate($cache_data);
					}
					$cache_data = unserialize($cache_data);
					$container[$cache->fields['cache_name']] = $cache_data;
				}
				$cache->MoveNext();
			}
			unset($cache_data);
			if(count($container) == 1) {
				return $container[$cache_name];
			}
			else if($global) {
				return array(
					'GLOBAL' => $container
				);
			}
			else {
				return $container;
			}
		}

		return false;
	}

	/**
	 * Function to perform basic garbage collection for database cache system
	 */
	public function cache_gc() {
		global $db;

		$now = new DateTime();
		$sql = 'DELETE FROM `' . TABLE_USU_CACHE . '` WHERE `cache_expires` <= :date:';
		$sql = $db->bindVars($sql, ':date:', $now->format('Y-m-d H:i:s'), 'string');
		$db->Execute($sql);
	}

	/**
	 * Function to check if the cache is in the database and expired
	 * @author Bobby Easland
	 * @version 1.0
	 * @param string $name
	 * @param boolean $is_cached NOTE: passed by reference
	 * @param boolean $is_expired NOTE: passed by reference
	 */
	public function is_cached($name, &$is_cached, &$is_expired) {
		global $db, $queryCache;

		$sql = 'SELECT `cache_expires` FROM `' . TABLE_USU_CACHE . '` ' .
			'WHERE `cache_id`=:id: AND `cache_language_id`=\':language:\' LIMIT 1';
		$sql = $db->bindVars($sql, ':id:', md5($name), 'string');
		$sql = $db->bindVars($sql, ':language:', $this->languages_id, 'integer');
		$cache = $db->Execute($sql);
		$is_cached = ($cache->RecordCount() > 0 ? true : false);

		// Fix for query_cache (clear the Zen Cart in memory cache)
		if(isset($queryCache) && $queryCache->inCache($sql)) {
			$queryCache->reset($sql);
		}

		if($is_cached) {
			$is_expired = ($cache->fields['cache_expires'] <= date("Y-m-d H:i:s") ? true : false);
			unset($cache);
		}
	}

	/**
	 * Determines if the page is in the list of pages where alternative URLs
	 * should be generated. If the list is empty all pages should utilize
	 * alternative URLs.
	 *
	 * @param string $page the Zen Cart page (main_page=xxxx)
	 * @return bool true if an alternative URL should be created, false otherwise.
	 */
	protected function filter_page($page) {
		return count($this->filter_page) == 0 || in_array($page, $this->filter_page);
	}

	/**
	 * Reads, Parses, and Transforms the original URI found in $_SERVER. Uses
	 * the information to determine if a special canonical URI will be needed.
	 * Currently these are only needed when a linked product is encountered.
	 *
	 * The request URI will be placed in $this->uri
	 * The real URI will be placed in $this->real_uri
	 * The canonical URI will be placed in $this->canonical if a special
	 * canonical is needed, otherwise $this->canonical will be null.
	 */
	protected function canonical() {
		global $db, $request_type;

		$this->uri = ltrim($_SERVER['REQUEST_URI']);
		$this->real_uri = ltrim(basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] != '' ? '?' . $_SERVER['QUERY_STRING'] : ''), '/' );
		$this->canonical = null;

		if($this->filter_page($_GET['main_page']) && array_key_exists('products_id', $_GET)) {
			// Retrieve the product type handler from the database
			$sql = 'SELECT `pt`.`type_handler` ' .
				'FROM `'. TABLE_PRODUCTS .'` AS `p` ' .
				'LEFT JOIN `'. TABLE_PRODUCT_TYPES .'` AS `pt` ON `pt`.`type_id` = `p`.`products_type` ' .
				'WHERE `p`. `products_id` = \':pid:\' LIMIT 1';
			$sql = $db->bindVars($sql, ':pid:', $_GET['products_id'], 'integer');
			$type = $db->Execute($sql);
			if(!$type->EOF) {
				// Validate this was a request to the product page
				$product_page = $type->fields['type_handler'] . '_info';
				if($_GET['main_page'] == $product_page) {
					// Only add the canonical if one is found
					$this->canonical = $this->get_product_canonical((int)$_GET['products_id']);
					if($this->canonical !== null) {
						$separator = '?';
						$this->canonical = $this->make_url(
							$product_page,
							$this->canonical,
							'products_id', (int)$_GET['products_id'],
							USU_END,
							$separator
						);
						$this->canonical = ($request_type == 'SSL' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG) . htmlspecialchars($this->canonical, ENT_QUOTES, CHARSET, false);
						unset($separator);
					}
				}
			}
			unset($type, $product_page, $separator);
		}
	}

	/**
	 * Determines if the requested URI should generate a redirect to another URI.
	 *
	 * @return true if a redirect is needed, false otherwise.
	 */
	protected function needs_redirect() {
		global $request_type;

		// If the current language of the user does not match the language
		// in use by this module, do not redirect.
		if($_SESSION['languages_id'] != $this->languages_id) {
			$this->log('NO REDIRECT: Language of the URI did not match the current language.');
			$this->log(PHP_EOL, false);
			return false;
		}

		// If we are in the admin we should never redirect
		if(IS_ADMIN_FLAG == 'true') {
			$this->log('NO REDIRECT: Request was for an administrative page.');
			return false;
		}

		// We should also avoid redirects with post content
		if(count($_POST) > 0) {
			$this->log('NO REDIRECT: Content was present in $_POST.');
			return false;
		}

		// If the request was for a real file (which called application_top)
		// We should avoid issuing a redirect.
		if($this->is_physical_file($this->real_uri)) {
			$this->log('NO REDIRECT: Request was for a physical file (not virtual).');
			return false;
		}

		// Build the params for zen_href_link
		$params = array();
		foreach($_GET as $key => $value) {
			if($key == 'main_page') continue;

			// Fix the case sensitivity, shopping cart sometimes breaks this
			if($key == 'cpath') $key = 'cPath';

			$params[] = $key . '=' . $value;
		}
		$params = (sizeof($params) > 0 ? implode('&', $params) : '');
		$this->log('Params from $_GET: ' . $params);

		// Determine the alternative URL for the request
		$this->redirect_uri = $this->href_link($_GET['main_page'], $params, $request_type, false, true, false, true);
		if($this->redirect_uri === null) $this->redirect_uri = zen_href_link($_GET['main_page'], $params, $request_type, false, true, false, true);
		$this->redirect_uri = @parse_url($this->redirect_uri);
		$parsed_uri = @parse_url($this->uri);

		// If the passed URI is seriously malformed, issue a redirect
		// Outside of hacking attempts, this is rarely encountered
		if($parsed_uri === false) {
			$this->log('WARNING: Unable to parse URI, may be a hacking attempt.');
			return true;
		}

		// We need to redirect if the paths do not match
		if($parsed_uri['path'] != $this->redirect_uri['path'] && rawurldecode($parsed_uri['path']) != $this->redirect_uri['path']) {
			if($this->canonical !== null) {
				$canonical = parse_url($this->canonical);
				if($parsed_uri['path'] != $canonical['path']) {
					$this->log('Generated path for the canonical did not match the requested URI.');
					return true;
				}
			}
			else {
				$this->log('Generated path did not match the requested URI.');
				return true;
			}
		}

		// See if the parameters match. We do not care about order.
		else {
			$params = explode('&', str_replace('&amp;', '&', $this->redirect_uri['query']));
			asort($params);
			$old_params = explode('&', $parsed_uri['query']);
			asort($old_params);
			if(count($params) != count($old_params)) {
				$this->log('Number of paramaters did not match the requested URI.');
				return true;
			}
			else {
				for($i=0,$n=count($params);$i<$n;$i++) {
					if(urldecode($params[$i]) != urldecode($old_params[$i])) {
						$this->log('The value of paramaters did not match the requested URI.');
						$this->log('Alternate URI param: ' . urldecode($params[$i]));
						$this->log('Requested URI param: ' . urldecode($old_params[$i]));
						return true;
					}
				}
			}
		}

		$this->log('NO REDIRECT: Alternative URI matched the requested URI.');
		return false;
	}

	/**
	 * Issue a 301 redirect to the browser.
	 */
	protected function redirect() {
		$new_url = $this->redirect_uri['path'] . (array_key_exists('query', $this->redirect_uri) ? '?' . str_replace('&amp;', '&', $this->redirect_uri['query']) : '');

		$this->log('REDIRECT: Issued a redirect to: ' . $new_url);
		switch(USU_REDIRECT){
			case 'true':
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . $new_url);
				break;
			default:
		}
	}

	/**
	 * Checks to see if the requested URI matches a physical file.
	 *
	 * @param $uri the requested URI.
	 * @return true if a physical file (or directory), false otherwise.
	 */
	protected function is_physical_file($uri) {
		// Search for the first appearance of ? or #
		$real_file = strpos($uri, '?');
		if($real_file === false) {
			$real_file = strpos($uri, '#');
		}

		// Remove trailing content from ? or #
		if($real_file !== false) {
			$real_file = substr($uri, 0, $real_file);
		}
		else {
			$real_file = $uri;
		}

		// Do not count the front controller (index.php) as a real file
		return $real_file != 'index.php' &&
			file_exists(DIR_FS_CATALOG . $real_file);
	}

	/**
	 * Logs the requested string to a temporary stream (file). The stream
	 * will not be written to a file or closed until this instance is garbage
	 * collected or the running PHP process ends.
	 *
	 * In the event of a PHP fatal error, this log may be lost.
	 *
	 * @param string $string the string to log.
	 * @param bool $eol true to add an End Of Line character to the string,
	 * 		false otherwise.
	 *
	 * @return bool true if the log was written, false otherwise.
	 */
	protected function log($string, $eol = true) {
		$retval = false;

		// The logstream is initialized in the constructor if enabled
		if($this->logstream !== false) {
			$retval = (fwrite($this->logstream, ((string) $string) . ($eol ? PHP_EOL : '')) !== false);
		}

		return $retval;
	}

	/**
	 * Called when this instance is garbage collected of the running PHP process
	 * ends. If a log stream has been opened, this will write the stream to a
	 * file and close the stream.
	 */
	function __destruct() {
		if($this->logstream !== false) {

			// Create any needed directories
			if(is_dir(dirname($this->logfile)) || mkdir(dirname($this->logfile), 0777, true)) {

				// Give each request it's own file
				$i = 0;
				while(file_exists($this->logfile . '_' . $i . '.log')) {
					$i++;
				}
				$this->logfile .= '_' . $i . '.log';

				// Write the log to a file
				rewind($this->logstream);
				file_put_contents($this->logfile, stream_get_contents($this->logstream));
			}

			fclose($this->logstream);
		}
	}

}
