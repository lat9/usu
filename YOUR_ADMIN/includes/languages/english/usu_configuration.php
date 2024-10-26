<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+.
 *
 * Note: For versions prior to v3.0.0, these language values were present in /admin/includes/languages/english/modules/plugin/usu.php.
 *
 * @copyright Copyright 2019, 2023  Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
// -----
// These _TITLE/_DESCRIPTION values are recorded in the database for an initial installation of the USU plugin.
//
define('USU_ENABLED_TITLE', 'Enable alternate URLs?');
define('USU_ENABLED_DESCRIPTION', 'This is a global setting that enables (<b>true</b>) or disables (<b>false</b>) the alternate URLs\' generation.');

define('USU_DEBUG_TITLE', 'Enable debug log?');
define('USU_DEBUG_DESCRIPTION', 'When enabled, additional debugging information is saved to log files (<code>/logs/usu-{adm-}yyyymmmdd-hhmmss.log</code>.<br><br>Enabling debugging may result in the creation of numerous log files and may adversely affect server performance. Only enable this if absolutely necessary!');

define('USU_CPATH_TITLE', 'Generate cPath parameters');
define('USU_CPATH_DESCRIPTION', 'By default Zen Cart generates a cPath parameter for product pages. These are used to keep linked products in the correct category. In automatic mode the cPath will only be added if needed.');

define('USU_END_TITLE', 'Alternate URLs end with');
define('USU_END_DESCRIPTION', 'If you want your URLs to end with a certain suffix add one here. Common suffixes are \'.html\', \'.htm\'. Leave this field blank to have no suffix added to generated URLs.');

define('USU_FORMAT_TITLE', 'Format of alternate URLs');
define('USU_FORMAT_DESCRIPTION', 'You can select from a list of commonly generated formats.<br><br><b>Original:</b><ul><li><i>Categories:</i> category-name-c-34</li><li><i>Products:</i> product-name-p-54</li></ul><b>Category Parent:</b><ul><li><i>Categories:</i> parent-category-name-c-34</li><li><i>Products:</i> parent-product-name-p-54</li></ul>');

define('USU_CATEGORY_DIR_TITLE', 'Display categories as directories');
define('USU_CATEGORY_DIR_DESCRIPTION', 'You can select from a list of commonly generated formats.<br><b>Off:</b> disables displaying categories as directories<br><br><b>Short:</b> use the settings from \'Format of alternate URLs\'<br><br><b>Full:</b> uses full category paths<br><br>');

define('USU_REMOVE_CHARS_TITLE', 'Remove problematic characters');
define('USU_REMOVE_CHARS_DESCRIPTION', 'This allows you remove certain problematic characters from the generated URLs.<br><br><i>non-alphanumerical:</i> removes all non-alphanumerical characters<br><i>punctuation:</i> removes all punctuation characters');

define('USU_FILTER_PCRE_TITLE', 'Enter PCRE filter rules');
define('USU_FILTER_PCRE_DESCRIPTION', 'This setting uses PCRE rules to filter URLs.<br><br>This filter is run before character conversions and stripping of special characters. If you want a dash - in your URLS, use a single space. To escape a character in the regular expression use \\\\ instead of a single \\.<br><br>The format <b>MUST</b> be in the form: <b>find1=>replace1,find2=>replace2</b>. ');

define('USU_FILTER_SHORT_WORDS_TITLE', 'Filter short words');
define('USU_FILTER_SHORT_WORDS_DESCRIPTION', 'This setting will filter &quot;short&quot; words, i.e. those with length less than or equal to the value specified, from any generated URLs.  Use the value <b>0</b> to include <em>all</em> words.');

define('USU_FILTER_PAGES_TITLE', 'Limit alternate URLS to the following pages');
define('USU_FILTER_PAGES_DESCRIPTION', 'You can limit the pages which will be rewritten by specifying them here. If no pages are specified all pages will be rewritten.<br><br>The format is a comma-delimited list (intervening spaces are OK) and <b>must</b> be in the form: <b>page1,page2,page3</b> or <b>page1, page2, page3</b>');

define('USU_ENGINE_TITLE', 'Choose URL Engine');
define('USU_ENGINE_DESCRIPTION', 'Choose which URL Engine to use.');

define('USU_REDIRECT_TITLE', 'Enable automatic redirects?');
define('USU_REDIRECT_DESCRIPTION', 'This will activate the automatic redirect code and send 301 headers for old to new URLs.');

define('USU_VERSION_TITLE', 'Plugin Version');
define('USU_VERSION_DESCRIPTION', 'The currently-installed version of <em>USU</em>.');
