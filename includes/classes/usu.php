<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+. Originally derived from Ultimate SEO URLs
 * v2.1 for osCommerce by Chemo.
 *
 * @copyright Copyright 2019-2023 Cindy Merkin (vinosdefrutastropicales.com), @prosela
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
class usu extends base
{
    public
        $canonical = null;

    protected
        $enabled,
        $cache,
        $languages_id,
        $parameters_valid,
        $reg_anchors,
        $uri,
        $real_uri,
        $redirect_uri,
        $first_access,
        $debug,
        $logfile,
        $logpage;

    protected static
        $unicodeEnabled;

    private
        $filter_pcre,
        $filter_char,
        $filter_page;

    public function __construct($languages_id = '')
    {
        global $sniffer, $messageStack;

        $this->enabled = (defined('USU_ENABLED') && USU_ENABLED === 'true');
        if ($this->enabled === false) {
            return;
        }

        if ($languages_id === '') {
            $languages_id = $_SESSION['languages_id'];
        }
        $this->languages_id = (int)$languages_id;

        $this->cache = [
            'products' => [],
            'categories' => [],
            'manufacturers' => [],
            'ezpages' => [],
        ];

        $this->reg_anchors = [
            'products_id' => '-p-',
            'cPath' => '-c-',
            'manufacturers_id' => '-m-',
            'pID' => '-pi-',
            'products_id_review' => '-pr-',
            'products_id_review_info' => '-pri-',
            'products_id_review_write' => '-prw-',
            'id' => '-ezp-',
            'pid' => '-aaq-',
        ];

        if (null === self::$unicodeEnabled) {
            self::$unicodeEnabled = (preg_match('/\pL/u', 'a')) ? true : false;
        }

        $this->filter_pcre = defined('USU_FILTER_PCRE') ? $this->expand(USU_FILTER_PCRE) : 'false';
        $this->filter_page = (defined('USU_FILTER_PAGES') && USU_FILTER_PAGES !== '') ? explode(',', str_replace(' ', '', USU_FILTER_PAGES)) : [];

        // Start logging
        $this->debug = false;
        if (defined('USU_DEBUG') && USU_DEBUG === 'true') {
            $this->debug = true;
            if (IS_ADMIN_FLAG === true) {
                $this->logfile = DIR_FS_LOGS . '/usu-adm-' . date('Ymd-His') . '.log';
                $this->logpage = $_SERVER['SCRIPT_NAME'];
            } else {
                $this->logfile = DIR_FS_LOGS . '/usu-' . date('Ymd-His') . '.log';
                $this->logpage = (isset($_GET['main_page'])) ? $_GET['main_page'] : 'index';
            }
        }

        // -----
        // Give a watching observer the opportunity to add additional 'anchors'
        // to the mix.  The array, on return should be an associative array
        // where the array element's key is the $_GET variable name and the associated
        // value identifies the query-string element to which it maps (just like the
        // as-shipped values above).
        //
        $additional_anchors = [];
        $this->notify('NOTIFY_USU_ADDITIONAL_ANCHORS', '', $additional_anchors);
        if (!empty($additional_anchors) && is_array($additional_anchors)) {
            $this->log('--> Additional anchors supplied via observer:' . json_encode($additional_anchors));
            $this->reg_anchors = array_merge($this->reg_anchors, $additional_anchors);
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
    public function href_link($page = '', $parameters = '', $connection = 'NONSSL', $add_session_id = true, $search_engine_safe = true, $static = false, $use_dir_ws_catalog = true) 
    {
        // Do not create an alternate URI when disabled
        if ($this->enabled === false) {
            return null;
        }

        // -----
        // If this is the first href-link generated for the current page's rendering, include some information
        // regarding which page (storefront vs. admin) that the request is being generated for.
        //
        if (!isset($this->first_access)) {
            $this->first_access = true;
            $this->log("=====> URL Generation Log Started, for page: {$this->uri}.");
        }

        $this->log(PHP_EOL . 'Request sent to href_link(' . $page . ', ' . $parameters . ', ' . $connection . ', ' . $add_session_id . ', ' . $search_engine_safe . ', ' . $static . ', ' . $use_dir_ws_catalog . ')');

        // If the request was for a real file (which called application_top)
        // We should not create an alternate URI (such as ipn_main_handler.php).
        if ($page !== '' && $page !== FILENAME_DEFAULT && $this->is_physical_file($page)) {
            $this->log('Request was to a physical file, URI not generated!');
            return null;
        }

        // Much of the code in Zen Cart creates the dynamic link by itself before
        // passing the code to zen_href_link and then just claims the link is
        // static and passes no params. Much of the code also has the bad habit
        // of claiming a link is "static" when it is not. So we ignore the value
        // of "static" if the page starts with "index.php?"
        if (strpos($page, 'index.php?') !== false) {
            // If we find the main_page parse the URL
            $result = [];
            if (preg_match('/[?&]main_page=([^&]*)/', $page, $result) === 1) {
                $temp = parse_url($page);

                // Adjust the page and parameters to be correct. This mainly
                // fixes the handling of EZ-Pages (but may fix additional pages).
                $page = $result[1];

                $temp['query'] = preg_replace('/main_page=' . $result[1] . '/', '', $temp['query']);
                $parameters = $temp['query'] . ($parameters !== '' ? ('&' . $parameters) : '');
            }
        }

        // Remove the end from the page if it is present
        $pos = strrpos($page, USU_END);
        if ($pos !== false) {
            $page = substr($page, 0, $pos);
        }
        unset($pos);

        // Do not rewrite if page is not in the list of pages to rewrite
        if (!$this->filter_page($page)) {
            $this->log("Page ($page) was not in the list of pages to rewrite, URI not generated!");
            return null;
        }

        // The base URL for the request
        if (IS_ADMIN_FLAG === true) {
            if ($connection === 'SSL' && ENABLE_SSL_CATALOG === 'true') {
                $link = HTTPS_CATALOG_SERVER;
                if ($use_dir_ws_catalog) {
                    $link .= DIR_WS_HTTPS_CATALOG;
                }
            } else {
                $link = HTTP_CATALOG_SERVER;
                if ($use_dir_ws_catalog) {
                    $link .= DIR_WS_CATALOG;
                }
            }
        } elseif ($connection === 'SSL' && ENABLE_SSL === 'true') {
            $link = HTTPS_SERVER;
            if ($use_dir_ws_catalog) {
                $link .= DIR_WS_HTTPS_CATALOG;
            }
        } else {
            $link = HTTP_SERVER;
            if ($use_dir_ws_catalog) {
                $link .= DIR_WS_CATALOG;
            }
        }

        // -----
        // Indicate that, so far, no issues have been found with the link's
        // parameters.  That might be changed during the parse_parameters method's
        // processing, if an invalid products_id, cPath or EZ-Pages' id parameter is
        // found.
        //
        $this->parameters_valid = true;

        // We start with no separator, so define one.
        $separator = '?';
        if ($parameters !== '') {
            $link .= $this->parse_parameters($page, $parameters, $separator);
        } else {
            $link .= ($page !== FILENAME_DEFAULT && $page !== '') ? ($page . USU_END) : '';
        }

        // -----
        // If the parameters supplied for the link aren't valid, don't regenerate a USU-formatted
        // link.
        //
        if (!$this->parameters_valid) {
            return null;
        }

        $link = $this->add_sid($link, $add_session_id, $connection, $separator);

        // -----
        // As of v3.0.9, the USU class is instantiated _prior to_ the init_sanitize.php load to prevent
        // a redirect-loop if an invalid/deleted product is associated with a 'products_id' variable in
        // the requested link.
        //
        // Unfortunately, that initialization file is loaded _prior to_ the language-file loads,
        // which is where the CHARSET definition occurs.  We'll need to default to a 'utf-8' character
        // set if that initialization processing is the point at which this href_link is being
        // requested.
        //
        $charset = (defined('CHARSET')) ? CHARSET : 'utf-8';
        $generated_url = htmlspecialchars($link, ENT_QUOTES, $charset, false);
        $this->log("Generated URL: $generated_url");
        return $generated_url;
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
    protected function add_sid($link, $add_session_id, $connection, $separator) 
    {
        global $request_type, $http_domain, $https_domain, $session_started;

        $_sid = '';
        if ($add_session_id === true && $session_started && SESSION_FORCE_COOKIE_USE === 'False') {
            if (defined('SID') && !empty(constant('SID'))) {
                $_sid = constant('SID');
            } else {
                $ssl_enabled = (IS_ADMIN_FLAG === true) ? ENABLE_SSL_CATALOG : ENABLE_SSL;
                if (($request_type === 'NONSSL' && $connection === 'SSL' && $ssl_enabled === 'true') || ($request_type === 'SSL' && $connection === 'NONSSL')) {
                    if ($http_domain !== $https_domain) {
                        $_sid = zen_session_name() . '=' . zen_session_id();
                    }
                }
            }
        }

        switch (true) {
            case (!isset($_SESSION['customer_id']) && defined('ENABLE_PAGE_CACHE') && ENABLE_PAGE_CACHE === 'true' && class_exists('page_cache')):
                $return = $link . $separator . '<zensid>';
                break;
            case (!empty($_sid)):
                $return = $link . $separator . $_sid;
                break;
            default:
                $return = $link;
                break;
        }
        return $return;
    }

    /**
     * Parses the parameters for a page to generate a valid url for the page.
     *
     * @param string $page the name of the page
     * @param string $params any parameters for the page
     * @param string $separator the separator to use between the link and this parameter (if needed)
     * @return Ambiguous <string, unknown>
     */
    protected function parse_parameters($page, $params, &$separator) 
    {
        // -----
        // Strip any leading 'amp;' and change any '&amp;' to '&'.
        //
        if (strpos($params, 'amp;') === 0) {
            $params = substr($params, 4);
        }
        $params = str_replace('&amp;', '&', $params);

        // We always need cPath to be first, so find and extract
        $cPath = false;
        if (1 === preg_match('/(?:^|&)c[Pp]ath=([^&]+)/', $params, $path)) {
            $params = str_replace($path[0], '', $params);
            $cPath = $path[1];
        }

        // Cleanup parameters and convert to initial array
        $params = trim($params, "?& \t\n\r\0\x0B");
        $params_array = [];
        if (!empty($params) && is_string($params)) {
            $params_array = explode('&', $params);
        }

        // Add the cPath to the start of the parameters if present
        if ($cPath !== false) {
            array_unshift($params_array, 'cPath=' . $cPath);
        }

        $this->log('Parsing Parameters for ' . $page . ', cPath = ('. json_encode($cPath) . PHP_EOL . var_export($params_array, true));

        $link_params = [];
        foreach ($params_array as $valuepair) {
            // -----
            // No '=' separating the key from its value?  Use an empty string for the value
            //
            if (strpos($valuepair, '=') === false) {
                $key = $value_pair;
                $value = '';
            } else {
                $vp_array = explode('=', $valuepair);
                $key = $vp_array[0];
                $value = $vp_array[1];
            }

            // -----
            // Determine if the to-be-generated URL is for one of the 'encoded' pages.
            //
            switch ($key) {
                // -----
                // If the 'products_id' variable is present, it could be for a product's details page or
                // a product's reviews listing or detailed review.
                //
                case 'products_id':
                    // -----
                    // Make sure if uprid is passed it is converted to the correct pid
                    //
                    $prid = (int)zen_get_prid($value);

                    // -----
                    // If a cPath was supplied for the link, grab the immediate parent 'category'.
                    //
                    $cID = null;
                    if ($cPath !== false) {
                        $cID = strrpos($cPath, '_');
                        if ($cID !== false) {
                            $cID = substr($cPath, $cID + 1);
                        } else {
                            $cID = $cPath;
                        }
                    }

                    // -----
                    // Now, check for various pages whose URLs are encoded by USU.
                    //
                    $url_created = true;
                    switch (true) {
                        // -----
                        // A product's details' page, e.g. product_info.
                        //
                        case ($page === $this->getInfoPage($prid)):
                            $url = $this->make_url($page, $this->get_product_name($prid, $cID), $key, $prid, USU_END);

                            // -----
                            // Note: The (string) cast is needed, otherwise the (now integer) $prid will be a match
                            // to its uprid (if supplied)!
                            //
                            if (((string)$prid) != $value) {
                                $link_params[] = $valuepair;
                            }
                            break;

                        // -----
                        // A listing of a product's reviews.
                        //
                        case ($page === FILENAME_PRODUCT_REVIEWS):
                            $url = $this->make_url($page, $this->get_product_name($prid, $cID), 'products_id_review', $prid, USU_END);
                            break;

                        // -----
                        // The display of a product's review details.
                        //
                        case ($page === FILENAME_PRODUCT_REVIEWS_INFO):
                            $url = $this->make_url($page, $this->get_product_name($prid, $cID), 'products_id_review_info', $prid, USU_END);
                            break;

                        // -----
                        // The display of a product's review write page.
                        //
                        case ($page === FILENAME_PRODUCT_REVIEWS_WRITE):
                            $url = $this->make_url($page, $this->get_product_name($prid, $cID), 'products_id_review_write', $prid, USU_END);
                            break;

                        // -----
                        // Anything else, just add the parameter to the link's parameters.
                        //
                        default:
                            $url_created = false;
                            $link_params[] = $valuepair;
                            break;
                    }

                    // -----
                    // If a product-specific URL was created and the store's configuration indicates that no cPath parameter should
                    // be included, remove it from the current link parameters.
                    //
                    if ($url_created === true && $cPath !== false && (USU_CATEGORY_DIR !== 'off' || USU_CPATH !== 'auto')) {
                        unset($link_params[0]);
                    }
                    break;

                // -----
                // A 'cPath' parameter is normally included on listing pages (for the categories' listings).
                //
                case 'cPath':
                    switch (true) {
                        case ($value === ''):
                            // Do nothing if cPath is empty
                            break;

                        case ($page === FILENAME_DEFAULT):
                            // Change $value to the actual category id
                            $tmp = strrpos($value, '_');
                            if ($tmp !== false) {
                                $value = substr($value, $tmp + 1);
                            }
                            $value = (int)$value;

                            $category = $this->get_category_name($value);
                            if (USU_CATEGORY_DIR === 'off') {
                                $url = $this->make_url($page, $category, $key, $value, USU_END);
                            } else {
                                $url = $this->make_url($page, $category, $key, $value, '/');
                            }
                            unset($category);
                            break;

                        default:
                            $link_params[] = $valuepair;
                            break;
                    }
                    break;

                case 'manufacturers_id':
                    switch (true) {
                        case ($page === FILENAME_DEFAULT && !$this->is_cPath_string($params) && !$this->is_product_string($params)):
                            $url = $this->make_url($page, $this->get_manufacturer_name($value), $key, $value, USU_END);
                            break;

                        // -----
                        // If the current 'page' requested is a 'product_[something_]info', don't add the parameter.
                        //
                        case (preg_match('/product_(\S+_)?info/', $page)):
                            break;

                        default:
                            $link_params[] = $valuepair;
                            break;
                    }
                    break;

                case 'pID':
                    switch (true) {
                        case ($page === FILENAME_POPUP_IMAGE):
                            $url = $this->make_url($page, $this->get_product_name($value), $key, $value, USU_END);
                            break;

                        default:
                            $link_params[] = $valuepair;
                            break;
                    }
                    break;

                case 'pid':
                    switch (true) {
                        case ($page === FILENAME_ASK_A_QUESTION):
                            $url = $this->make_url($page, $this->get_product_name($value), $key, $value, USU_END);
                            break;

                        default:
                            $link_params[] = $valuepair;
                            break;
                    }
                    break;

                case 'id':    // EZ-Pages
                    switch (true) {
                        case ($page === FILENAME_EZPAGES):
                            $url = $this->make_url($page, $this->get_ezpages_name($value), $key, $value, USU_END);
                            break;

                        default:
                            $link_params[] = $valuepair;
                            break;
                    }
                    break;

                default:
                    $link_params[] = $valuepair;
                    break;
            }
        }

        $url = isset($url) ? $url : $page . USU_END;
        if (!empty($link_params)) {
            $url .= $separator . zen_output_string(implode('&', $link_params));
            $separator = '&';
        }
        return $url;
    }

    protected function getInfoPage($products_id)
    {
        global $db;

        // -----
        // Quick return if the zen_get_info_page function exists, noting that when
        // run in the zc157 and earlier admin that it isn't!
        //
        if (function_exists('zen_get_info_page')) {
            return zen_get_info_page($products_id);
        }

        // -----
        // If the function doesn't exist, emulate its output.
        //
        $check = $db->Execute(
            "SELECT pt.type_handler
               FROM " . TABLE_PRODUCTS . " p
                    INNER JOIN " . TABLE_PRODUCT_TYPES . " pt
                        ON pt.type_id = p.products_type
              WHERE p.products_id = " . (int)$products_id . "
              LIMIT 1"
        );
        return ($check->EOF) ? 'product_info' : ($check->fields['type_handler'] . '_info');
    }

    /**
     * Convert an array of query parameters to a URI query string. This is safe
     * for use under 5.2+ with optimizations for PHP 5.4+.
     *
     * @param array the array of query parameters
     */
    protected function build_query($parameters)
    {
        $compile = [];
        foreach ($parameters as $key => $value) {
            if ($key !== null && $key !== '') {
                // Prior to PHP 5.3, tildes might be encoded per RFC 1738
                // This should not impact functionality for 99% of users.
                $compile[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        $parameters = implode('&', $compile);
        unset($compile);

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
     * @return string the final generated url
     */
    protected function make_url($page, $link, $anchor_type, $id, $extension = USU_END)
    {
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
    protected function get_product_name($pID, $cID = null)
    {
        global $db;

        $pID = (int)$pID;
        // Handle generating the product name
        switch (true) {
            case (defined('PRODUCT_NAME_' . $pID)):
                $return = constant('PRODUCT_NAME_' . $pID);
                break;

            case isset($this->cache['products'][$pID]):
                $return = $this->cache['products'][$pID];
                break;

            default:
                $pName = $this->filter(zen_get_products_name($pID));

                // -----
                // If the products's name wasn't found, indicate that there's a link
                // parameter issue so that the requested URL won't be generated.
                //
                if ($pName === '') {
                    $this->parameters_valid = false;
                } else {
                    if (USU_FORMAT === 'parent' && USU_CATEGORY_DIR === 'off') {
                        $masterCatID = (int)zen_get_products_category_id($pID);
                        $category_id = ($cID !== null) ? $cID : $masterCatID;
                        $pName = $this->get_category_name($category_id, 'original') . '-' . $pName;
                    }
                    $this->cache['products'][$pID] = $pName;
                }
                $return = $pName;
                break;
        }

        // Add the category
        $category = '';
        if (USU_CATEGORY_DIR !== 'off') {
            if (empty($cID)) {
                $masterCatID = (int)zen_get_products_category_id($pID);
                $category = $this->get_category_name($masterCatID) . $this->reg_anchors['cPath'] . $masterCatID . '/';
            } else {
                if (zen_product_in_category($pID, $cID)) {
                    $category = $this->get_category_name($cID) . $this->reg_anchors['cPath'] . $cID . '/';
                }
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
    protected function get_product_canonical($pID)
    {
        global $db;

        $retval = null;
        $pID = (int)$pID;
        // Only need to add the canonicals if different paths exist
        if (USU_CATEGORY_DIR !== 'off') {
            // -----
            // Selecting the specified product's master-category-id (which defines its canonical
            // path for the link) as well as the product's name.  Using 'INNER JOIN's since the
            // product's name must be present and the product 'should' map to its master-category
            // in the products_to_categories table.
            //
            // If either don't map, we'll return 'null' which will result in the product's canonical
            // link being generated without any specific category information.
            //
            $pName = zen_get_products_name($pID);
            if (!empty($pName)) {
                $masterID = (int)zen_get_products_category_id($pID);
                $retval = $this->get_category_name($masterID) . $this->reg_anchors['cPath'] . $masterID . '/';

                // Get the product name
                switch (true) {
                    case (defined('PRODUCT_NAME_' . $pID)):
                        $retval .= constant('PRODUCT_NAME_' . $pID);
                        break;

                    case isset($this->cache['products'][$pID]):
                        $retval .= $this->cache['products'][$pID];
                        break;

                    default:
                        if (USU_FORMAT === 'parent' && USU_CATEGORY_DIR === 'off') {
                            $pName = $this->get_category_name($masterID, 'original') . '-' . $pName;
                        }

                        $retval .= $pName;
                        unset($pName);
                        break;
                }
            }
        }
        return $retval;
    }

    /**
     * Function to get the category name. Use evaluated cache, per page cache,
     * or database query in that order of precedent
     * @param integer $cID NOTE: passed by reference
     * @return string category name
     */
    protected function get_category_name(&$cID, $format = USU_FORMAT)
    {
        global $db;

        $single_cID = (int)$cID;
        $full_cPath = $this->get_full_cPath($cID, $single_cID); // full cPath needed for uniformity
        switch (true) {
            case (defined('CATEGORY_NAME_' . $full_cPath) && $format === USU_FORMAT):
                $return = constant('CATEGORY_NAME_' . $full_cPath);
                break;

            case ($format === USU_FORMAT && isset($this->cache['categories'][$full_cPath])):
                $return = $this->cache['categories'][$full_cPath];
                break;

            default:
                $cName = '';
                if (USU_CATEGORY_DIR === 'full') {
                    $path = [];
                    $this->get_parent_categories_path($path, $single_cID);
                    if (count($path) > 0) {
                        $cName = implode('/', $path);
                        $cut = strrpos($cName, $this->reg_anchors['cPath']);
                        if ($cut !== false) {
                            $cName = substr($cName, 0, $cut);
                        }
                        unset($cut);
                    }
                    unset($path);
                } elseif ($format === 'parent') {
                    $sql =
                        "SELECT c.categories_id, c.parent_id, cd.categories_name AS cName, cd2.categories_name AS cNameParent
                           FROM " . TABLE_CATEGORIES_DESCRIPTION . " AS cd, " . TABLE_CATEGORIES . " AS c
                                LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " AS cd2
                                    ON c.parent_id = cd2.categories_id 
                                   AND cd2.language_id = {$this->languages_id}
                          WHERE c.categories_id = $single_cID
                            AND cd.categories_id = c.categories_id
                            AND cd.language_id = {$this->languages_id}
                          LIMIT 1";
                    $result = $db->Execute($sql, false, true, 43200);
                    if (!$result->EOF) {
                        $cName = !empty($result->fields['cNameParent']) ? $this->filter($result->fields['cNameParent'] . ' ' . $result->fields['cName']) : $this->filter($result->fields['cName']);
                    }
                } else {
                    $sql =
                        "SELECT categories_name AS cName
                           FROM " . TABLE_CATEGORIES_DESCRIPTION . "
                          WHERE categories_id = $single_cID
                            AND language_id = {$this->languages_id}
                          LIMIT 1";
                    $result = $db->Execute($sql, false, true, 43200);
                    if (!$result->EOF) {
                        $cName = $this->filter($result->fields['cName']);
                    }
                }

                // -----
                // If the category's name wasn't found, indicate that there's a link
                // parameter issue so that the requested URL won't be generated.
                //
                if ($cName === '') {
                    $this->parameters_valid = false;
                } else {
                    $this->cache['categories'][$full_cPath] = $cName;
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
    protected function get_manufacturer_name($mID)
    {
        global $db;

        $mID = (int)$mID;
        switch (true) {
            case (defined('MANUFACTURER_NAME_' . $mID)):
                $return = constant('MANUFACTURER_NAME_' . $mID);
                break;

            case isset($this->cache['manufacturers'][$mID]):
                $return = $this->cache['manufacturers'][$mID];
                break;

            default:
                $sql =
                    "SELECT manufacturers_name as `mName`
                       FROM " . TABLE_MANUFACTURERS . "
                      WHERE manufacturers_id = $mID
                      LIMIT 1";
                $result = $db->Execute($sql, false, true, 43200);
                $mName = ($result->EOF) ? '' : $this->filter($result->fields['mName']);

                // -----
                // If the manufacturer's name wasn't found, indicate that there's a link
                // parameter issue so that the requested URL won't be generated.
                //
                if ($mName === '') {
                    $this->parameters_valid = false;
                } else {
                    $this->cache['manufacturers'][$mID] = $mName;
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
    protected function get_ezpages_name($ezpID)
    {
        global $db;

        $ezpID = (int)$ezpID;
        switch (true) {
            case (defined('EZPAGES_NAME_' . $ezpID)):
                $return = constant('EZPAGES_NAME_' . $ezpID);
                break;

            case isset($this->cache['ezpages'][$ezpID]):
                $return = $this->cache['ezpages'][$ezpID];
                break;

            default:
                $sql =
                    "SELECT pages_title AS ezpName
                       FROM " . TABLE_EZPAGES_CONTENT . "
                      WHERE pages_id = $ezpID
                        AND languages_id = {$this->languages_id}
                      LIMIT 1";
                $sql = $db->bindVars($sql, ':ezpage:', $ezpID, 'integer');
                $result = $db->Execute($sql, false, true, 43200);
                $ezpName = ($result->EOF) ? '' : $this->filter($result->fields['ezpName']);

                // -----
                // If the EZ-Page's name wasn't found, indicate that there's a link
                // parameter issue so that the requested URL won't be generated.
                //
                if ($ezpName === '') {
                    $this->parameters_valid = false;
                } else {
                    $this->cache['ezpages'][$ezpID] = $ezpName;
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
    protected function get_full_cPath($cID, &$original)
    {
        if (strpos($cID, '_') !== false) {
            $temp = explode('_', $cID);
            $original = array_pop($temp);
            return $cID;
        } else {
            $c = [];
            zen_get_parent_categories($c, $cID);
            $c = array_reverse($c);
            $c[] = $cID;
            $original = $cID;
            $cID = implode('_', $c);
            return $cID;
        }
    } # end function

    /**
     * Recursion function to retrieve parent categories from category ID.
     *
     * @author Andrew Ballanger
     * @param mixed $path Passed by reference
     * @param integer $categories_id
     */
    protected function get_parent_categories_path(&$path, $categories_id, &$cPath = [])
    {
        global $db;

        $categories_id = (int)$categories_id;
        $sql = 
            "SELECT c.parent_id AS p_id, cd.categories_name AS name
               FROM " . TABLE_CATEGORIES . " AS c
                    LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " AS cd
                        ON c.categories_id = cd.categories_id
                       AND cd.language_id = {$this->languages_id}
              WHERE c.categories_id = $categories_id";
        $parent = $db->Execute($sql, false, true, 43200);

        if (!$parent->EOF) {
            // Recurse if the parent id is not empty or equal the passed categories id
            if ($parent->fields['p_id'] !== '0' && (int)$parent->fields['p_id'] !== $categories_id) {
                $this->get_parent_categories_path($path, $parent->fields['p_id'], $cPath);
            }

            // Add category id to cPath and name to path
            $cPath[] = $categories_id;
            $path[] = $this->filter($parent->fields['name']) . $this->reg_anchors['cPath'] . implode('_', $cPath);
        }
    }

    protected function is_attribute_string($params)
    {
        return (preg_match('/products_id=([0-9]+):([a-zA-z0-9]{32})/', $params)) ? true : false;
    }

    protected function is_product_string($params)
    {
        return (strpos($params, 'products_id=') !== false);
    }

    protected function is_cPath_string($params)
    {
        return (strpos($params, 'cPath=') !== false);
    }

    /**
     * Function to filter a string and remove punctuation and white space.
     *
     * @param string $string input text
     * @return string filtered text
     */
    protected function filter($string)
    {
        $retval = trim(zen_clean_html($string));

        // First filter using PCRE Rules
        if (is_array($this->filter_pcre)) {
            $retval = preg_replace(array_keys($this->filter_pcre), array_values($this->filter_pcre), $retval);
        }

        // Next run character filters over the string
        $pattern = '';
        // Remove Special Characters from the strings
        switch (USU_REMOVE_CHARS) {
            case 'punctuation':
                // Remove all punctuation
                if (!self::$unicodeEnabled) {
                    // POSIX named classes are not supported by preg_replace
                    $pattern = '/[!"#$%&\'()*+,.\/:;<=>?@[\\\]^_`{|}~]/';
                } else {
                    // Each language's punctuation.
                    $pattern = '/[\p{P}\p{S}]/u';
                }
                break;

            case 'non-alphanumerical':
            default:
                // Remove all non alphanumeric characters
                if (!self::$unicodeEnabled) {
                    // POSIX named classes are not supported by preg_replace
                    $pattern = '/[^a-zA-Z0-9\s]/';
                } else {
                    // Each language's alphabet.
                    $pattern = '/[^\p{L}\p{N}\s]/u';
                }
                break;
        }
        if (function_exists('mb_strtolower')) {
            $retval = mb_strtolower($retval);
        } else {
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
    protected function expand(string $regexp)
    {
        if ($regexp !== '') {
            if ($data = explode(',', $regexp)) {
                $container = [];
                foreach ($data as $index => $valuepair) {
                    $p = explode('=>', $valuepair);

                    // Add the necessary regexp start / end characters
                    if (preg_match('|^\|.*?\|$|', $p[0]) === 0) {
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
    protected function short_name($str, $limit = 3)
    {
        if (defined('USU_FILTER_SHORT_WORDS')) {
            $limit = USU_FILTER_SHORT_WORDS;
        }
        $limit = (int)$limit;

        $str = (string)$str;
        if (empty($str)) {
            return $str;
        }

        $foo = explode('-', $str);
        $container = [];
        foreach ($foo as $index => $value) {
            if (strlen($value) > $limit) {
                $container[] = $value;
            }
        }
        return implode('-', $container);
    }

    /**
     * Determines if the page is in the list of pages where alternative URLs
     * should be generated. If the list is empty all pages should utilize
     * alternative URLs.
     *
     * @param string $page the Zen Cart page (main_page=xxxx)
     * @return bool true if an alternative URL should be created, false otherwise.
     */
    protected function filter_page($page)
    {
        return (count($this->filter_page) === 0 || in_array($page, $this->filter_page));
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
    public function canonical()
    {
        global $db, $request_type;

        $this->uri = ltrim($_SERVER['REQUEST_URI']);
        $this->real_uri = ltrim(basename($_SERVER['SCRIPT_NAME']) . ($_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : ''), '/' );
        $this->canonical = null;

        if (isset($_GET['main_page']) && $this->filter_page($_GET['main_page']) === true && isset($_GET['products_id'])) {
            $product_page = $this->getInfoPage((int)$_GET['products_id']);
            if ($_GET['main_page'] === $product_page) {
                // Only add the canonical if one is found
                $this->canonical = $this->get_product_canonical((int)$_GET['products_id']);
                if ($this->canonical !== null) {
                    $this->canonical = $this->make_url(
                        $product_page,
                        $this->canonical,
                        'products_id', (int)$_GET['products_id'],
                        USU_END
                    );
                    $this->canonical = ($request_type === 'SSL' ? (HTTPS_SERVER . DIR_WS_HTTPS_CATALOG) : (HTTP_SERVER . DIR_WS_CATALOG)) . htmlspecialchars($this->canonical, ENT_QUOTES, CHARSET, false);
                }
            }
            unset($product_page);
        }

        // Redirect if enabled and necessary
        if (defined('USU_REDIRECT') && USU_REDIRECT === 'true') {
            if ($this->needs_redirect() === true) {
                $this->redirect();
            }
        }
    }

    /**
     * Determines if the requested URI should generate a redirect to another URI.
     *
     * @return true if a redirect is needed, false otherwise.
     */
    protected function needs_redirect()
    {
        global $request_type;

        // If the current language of the user does not match the language
        // in use by this module, do not redirect.
        if ($_SESSION['languages_id'] !== $this->languages_id) {
            $this->log('NO REDIRECT: Language of the URI did not match the current language.');
            return false;
        }

        // If we are in the admin we should never redirect
        if (IS_ADMIN_FLAG === true) {
            $this->log('NO REDIRECT: Request was for an administrative page.');
            return false;
        }

        // We should also avoid redirects with post content
        if (!empty($_POST)) {
            $this->log('NO REDIRECT: Content was present in $_POST.');
            return false;
        }

        // If the request was for a real file (which called application_top)
        // We should avoid issuing a redirect.
        if ($this->is_physical_file($this->real_uri) === true) {
            $this->log('NO REDIRECT: Request was for a physical file (not virtual).');
            return false;
        }

        // -----
        // Form a string of all $_GET parameters (without the 'main_page'), then change
        // any occurrence of 'cpath' to its 'cPath' form (sometimes broken in previous
        // Zen Cart versions of the shopping cart) and lop off any trailing '&'.
        //
        $params = zen_get_all_get_params(['main_page']);
        $params = str_replace('cpath=', 'cPath=', $params);
        $params = rtrim($params, '&');
        $this->log('Params from $_GET: ' . $params);

        // Determine the alternative URL for the request
        $this->redirect_uri = $this->href_link($_GET['main_page'], $params, $request_type, false, true, false, true);
        if ($this->redirect_uri === null) {
            $this->redirect_uri = zen_href_link($_GET['main_page'], $params, $request_type, false, true, false, true);
        }
        $this->redirect_uri = parse_url($this->redirect_uri);
        $parsed_uri = parse_url($this->uri);

        // If the passed URI is seriously malformed, issue a redirect
        // Outside of hacking attempts, this is rarely encountered
        if ($parsed_uri === false) {
            $this->log('WARNING: Unable to parse URI, may be a hacking attempt.');
            return true;
        }

        // We need to redirect if the paths do not match
        if (!isset($parsed_uri['path']) || ($parsed_uri['path'] !== $this->redirect_uri['path'] && rawurldecode($parsed_uri['path']) !== $this->redirect_uri['path'])) {
            if ($this->canonical !== null) {
                $canonical = parse_url($this->canonical);
                if (!isset($parsed_uri['path']) || $parsed_uri['path'] !== $canonical['path']) {
                    $this->log('Generated path for the canonical did not match the requested URI.');
                    return true;
                }
            } else {
                // -----
                // If the requested URI contained invalid per-page parameters, e.g. an invalid 'id'
                // for an EZ-page request, the request is not redirected.
                //
                $this->log('Generated path did not match the requested URI.' . PHP_EOL . json_encode($parsed_uri) . PHP_EOL . json_encode($this->redirect_uri));
                return $this->parameters_valid;
            }
        } else {
            // See if the parameters match. We do not care about order.
            $params = (isset($this->redirect_uri['query'])) ? explode('&', str_replace('&amp;', '&', $this->redirect_uri['query'])) : [];
            asort($params);
            $old_params = (isset($parsed_uri['query'])) ? explode('&', $parsed_uri['query']) : [];
            asort($old_params);
            if (count($params) !== count($old_params)) {
                $this->log('Number of parameters did not match the requested URI: ' . implode('&', $params) . ' vs. ' . implode('&', $old_params));
                return true;
            } else {
                for ($i = 0, $n = count($params); $i < $n; $i++) {
                    if (urldecode($params[$i]) !== urldecode($old_params[$i])) {
                        $this->log('The value of parameters did not match the requested URI. Alternate URL param: ' . urldecode($params[$i]) . ', requested URI param: ' . urldecode($old_params[$i]));
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
    protected function redirect()
    {
        if (USU_REDIRECT === 'true') {
            $new_url = $this->redirect_uri['path'] . (array_key_exists('query', $this->redirect_uri) ? '?' . str_replace('&amp;', '&', $this->redirect_uri['query']) : '');

            $this->log('REDIRECT: Issued a redirect to: ' . $new_url);
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $new_url);
            exit;
        }
    }

    /**
     * Checks to see if the requested URI matches a physical file.
     *
     * @param $uri the requested URI.
     * @return true if a physical file (or directory), false otherwise.
     */
    protected function is_physical_file($uri)
    {
        // Search for the first appearance of ? or #
        $real_file = strpos($uri, '?');
        if ($real_file === false) {
            $real_file = strpos($uri, '#');
        }

        // Remove trailing content from ? or #
        if ($real_file !== false) {
            $real_file = substr($uri, 0, $real_file);
        } else {
            $real_file = $uri;
        }

        // Do not count the front controller (index.php) as a real file
        return ($real_file !== 'index.php' && file_exists(DIR_FS_CATALOG . $real_file));
    }

    /**
     * Logs the requested string to a session-specific file.
     *
     * @param string $string the string to log.
     * @param bool $eol true to add an End Of Line character to the string,
     *         false otherwise.
     */
    protected function log($message)
    {
        if ($this->debug === true) {
            error_log(((string)$message) . PHP_EOL, 3, $this->logfile);
        }
    }
}
