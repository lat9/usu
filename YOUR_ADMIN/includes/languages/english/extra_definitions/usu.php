<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2019        Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
define('BOX_CONFIGURATION_USU', 'Ultimate URLs');
define('BOX_CONFIGURATION_USU_UNINSTALL', 'Uninstall Ultimate URLs');

// Messages used on the configuration page
define('USU_PLUGIN_WARNING_GLOBAL_DISABLED', 'The global USU cache has been disabled. This is not recommended and overrides USU\'s caching of <em>all</em> URL types.');

define('USU_PLUGIN_WARNING_SHORT_WORDS', 'The value entered for the <em>Filter short words</em> setting (<b>%s</b>) is not a positive integer; the setting has been defaulted to <b>0</b>.');
define('USU_PLUGIN_WARNING_CATEGORY_DIR', 'The setting for <em>Display categories as directories</em> has been changed to <code>short</code>, since its <code>full</code> setting is incompatible with <em>Format of alternate URLs</em> setting of <code>parent</code>.');
define('USU_PLUGIN_WARNING_FORMAT', 'The setting for <em>Format of alternate URLs</em> has been changed to <code>original</code>, since its <code>parent</code> setting is incompatible with <em>Display categories as directories</em> setting of <code>full</code>.');

define('USU_PLUGIN_CACHE_RESET', 'The USU cache (%s) has been reset.');

// General warning messages
define('USU_PLUGIN_WARNING_TABLE', 'WARNING: The database table \'%s\' is missing!<ul><li>The SQL caches for \'Ultimate URLs\' have been disabled to prevent errors.</li><li>This may lead to degraded performance when loading pages.</li><li><b>Recommendation:</b> run the installer included with \'Ultimate URLs\'.</li></ul>');

define('USU_INSTALLED_SUCCESS', BOX_CONFIGURATION_USU . ', v%s, has been successfully installed.');
define('USU_UPDATED_SUCCESS', BOX_CONFIGURATION_USU . ' has been successfully updated from v%1$s to v%2$s.');