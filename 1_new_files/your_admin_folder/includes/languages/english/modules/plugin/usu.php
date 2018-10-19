<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

define('USU_DESCRIPTION', 'Provides alternative URLs for Zen Cart');

// Some warning and error messages
define('USU_PLUGIN_INSTALL_ERROR_CORE_FILE_NOT_FOUND', 'Unable to read a core Zen Cart file <code>%1$s</code>. Verify the file exists at this location and the webserver has permissions to read the file. The path is determined by reading: <code>%2$s</code>.');
define('USU_PLUGIN_INSTALL_ERROR_QUERY_FACTORY', 'The version of Zen Cart\'s mysql query_factory has a known bug present and you have enabled Zen Cart\'s SQL Cache. Either <a href="http://www.zen-cart.com/showthread.php?200484-Query-Factory">patch the bug</a> or set <code>define(\'SQL_CACHE_METHOD\', \'none\');</code> in your Zen Cart configure.php files.');

define('USU_PLUGIN_INSTALL_ERROR_ZENCART_VERSION', 'A Zen Cart %2$s installation has been detected. Installation of %1$s under Zen Cart %2$s is not supported.');

// Configuration Language Files
define('USU_ENABLED_TITLE', 'Enable alternate URLs?');
define('USU_ENABLED_DESCRIPTION', 'This is a global setting and can be used to enable or disable this module completely.');
define('USU_DEBUG_TITLE', 'Enable debug log?');
define('USU_DEBUG_DESCRIPTION', 'When enabled, additional debugging information is saved to log files.<br /><br />Enabling debugging may result in the creation of numerous log files and may adversely affect server performance. Only enable this if absolutely necessary!');
define('USU_CPATH_TITLE', 'Generate cPath parameters');
define('USU_CPATH_DESCRIPTION', 'By default Zen Cart generates a cPath parameter for product pages. These are used to keep linked products in the correct category. In automatic mode the cPath will only be added if needed.');
define('USU_END_TITLE', 'Alternate URLs end with');
define('USU_END_DESCRIPTION', 'If you want your URLs to end with a certain suffix add one here. Common suffixes are \'.html\', \'.htm\'. Leave this field blank to have no suffix added to generated URLs.');
define('USU_FORMAT_TITLE', 'Format of alternate URLs');
define('USU_FORMAT_DESCRIPTION', 'You can select from a list of commonly generated formats.<br /><b>Original:</b><br /><i>Categories:</i> category-name-c-34<br /><i>Products:</i> product-name-p-54<br /><br /><b>Category Parent:</b><br /><i>Categories:</i> parent-category-name-c-34<br /><i>Products:</i> parent-product-name-p-54');
define('USU_CATEGORY_DIR_TITLE', 'Display categories as directories');
define('USU_CATEGORY_DIR_DESCRIPTION', 'You can select from a list of commonly generated formats.<br /><b>Off:</b> disables displaying categories as directories<br /><br /><b>Short:</b> use the settings from \'Format of rewritten URLs\'<b>Full:</b> uses full category paths<br /><br />');

define('USU_REMOVE_CHARS_TITLE', 'Remove problematic characters');
define('USU_REMOVE_CHARS_DESCRIPTION', 'This allows you remove certain problematic characters from the generated URLs.<br /><br /><i>non-alphanumerical:</i> removes all non-alphanumerical characters<br /><i>punctuation:</i> removes all punctuation characters');
define('USU_FILTER_PCRE_TITLE', 'Enter PCRE filter rules');
define('USU_FILTER_PCRE_DESCRIPTION', 'This setting uses PCRE rules to filter URLs.<br><br>This filter is run before character conversions and stripping of special characters. If you want a dash - in your URLS, use a single space. To escape a character in the regular expression use \\\\ instead of a single \\.<br /><br />The format <b>MUST</b> be in the form: <b>find1=>replace1,find2=>replace2</b>. ');
define('USU_FILTER_SHORT_WORDS_TITLE', 'Filter short words');
define('USU_FILTER_SHORT_WORDS_DESCRIPTION', 'This setting will filter words less than or equal to the value from the URL.');
define('USU_FILTER_PAGES_TITLE', 'Limit alternate URLS to the following pages');
define('USU_FILTER_PAGES_DESCRIPTION', 'You can limit the pages which will be rewritten by specifying them here. If no pages are specified all pages will be rewritten. <br><br>The format is comma delimited and <b>MUST</b> be in the form: <b>page1,page2,page3</b> or <b>page1, page2, page3</b>');

define('USU_ENGINE_TITLE', 'Choose URL Engine');
define('USU_ENGINE_DESCRIPTION', 'Choose which URL Engine to use.');
define('USU_REDIRECT_TITLE', 'Enable automatic redirects?');
define('USU_REDIRECT_DESCRIPTION', 'This will activate the automatic redirect code and send 301 headers for old to new URLs.');

define('USU_CACHE_GLOBAL_TITLE', 'Enable caches to save queries?');
define('USU_CACHE_GLOBAL_DESCRIPTION', 'This is a global setting and will turn off both memory and SQL caching of URLs completely.');
define('USU_CACHE_PRODUCTS_TITLE', 'Enable product cache?');
define('USU_CACHE_PRODUCTS_DESCRIPTION', 'This will turn off SQL caching for the products.');
define('USU_CACHE_CATEGORIES_TITLE', 'Enable categories cache?');
define('USU_CACHE_CATEGORIES_DESCRIPTION', 'This will turn off SQL caching for the categories.');
define('USU_CACHE_MANUFACTURERS_TITLE', 'Enable manufacturers cache?');
define('USU_CACHE_MANUFACTURERS_DESCRIPTION', 'This will turn off SQL caching for the manufacturers.');
define('USU_CACHE_EZ_PAGES_TITLE', 'Enable ez pages cache?');
define('USU_CACHE_EZ_PAGES_DESCRIPTION', 'This will turn off SQL caching for ez pages.');
define('USU_CACHE_RESET_TITLE', 'Reset URL Cache');
define('USU_CACHE_RESET_DESCRIPTION', 'This will reset the SQL cache data for URLs');