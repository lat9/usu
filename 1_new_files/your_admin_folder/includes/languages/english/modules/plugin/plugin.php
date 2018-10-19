<?php
/**
 * Part of Plugin Manager (core). This file is required for plugins
 * utilizing the Plugin Manager framework to operate.
 *
 * @copyright Copyright 2012 - 2014 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

// Common configuration options
define('PLUGIN_OPTION_VERSION_TITLE', '%1$s Version');
define('PLUGIN_OPTION_VERSION_DESCRIPTION', 'Indicates the currently installed version of %1$s.');
define('PLUGIN_CONFIG_GROUP_DESCRIPTION', 'Configuration Group for %1$s');

// Internal Messages
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_KEY', 'Invalid Admin Page Options specified. Please contact the maintainer of \'%1$s\'. Pages\'s must specify the language_key AND specify either the page_key or have the page_key as the array key.');
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY', 'Invalid Admin Page Options specified. Please contact the maintainer of \'%1$s\'. Pages\'s must specify the menu_key (unless the main_page is \'FILENAME_CONFIGURATION\' or \'FILENAME_MODULES\').');
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY_BROKEN', 'The Admin Menu \'%2$s\' does not appear to exist. This menu is part of the default Zen Cart installation. Failure to locate this menu may indicate your Zen Cart database is damaged or someone has manually removed one of the core Zen Cart menus.');
define('PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY_MISSING', 'The Admin Menu \'%2$s\' does not appear to exist. This menu is not part of the default Zen Cart installation. Please contact the maintainer of \'%1$s\'.');
define('PLUGIN_INTERNAL_FORMAT_DATABASE_RENAME_TABLE', 'Invalid Database Table Rename Options specified. Please contact the maintainer of \'%1$s\'.');
define('PLUGIN_INTERNAL_FORMAT_DATABASE_RENAME_COLUMN', 'Invalid Database Column Rename Options specified. Please contact the maintainer of \'%1$s\'.');
define('PLUGIN_INTERNAL_FORMAT_DATABASE_RENAME_INDEX', 'Invalid Database Index Rename Options specified. Please contact the maintainer of \'%1$s\'.');
define('PLUGIN_INTERNAL_ERROR_FILE_REPLACE_INTERNAL', 'An error occurred while replacing / updating content in \'%2$s\'. Please contact the maintainer of \'%1$s\'. This may indicate a bad regular expression specified inside the list of modified core files.');
define('PLUGIN_INTERNAL_ERROR_FILE_REPLACE_BACKTRACK', 'An error occurred while replacing / updating content in \'%2$s\'. Backtrack Limit exceeded. This usually indicates an overly conservative <a href="http://www.php.net/manual/en/pcre.configuration.php#ini.pcre.backtrack-limit">pcre.backtrack_limit</a> configured on your server (or restricted by mod_security). Either change the setting or manually merge the required changes.');
define('PLUGIN_INTERNAL_ERROR_FILE_REPLACE_RECURSION', 'An error occurred while replacing / updating content in \'%2$s\'. Recursion Limit exceeded. This may indicate an overly conservative <a href="http://www.php.net/manual/en/pcre.configuration.php#ini.pcre.backtrack-limit">pcre.recursion_limit</a> configured on the server OR a bad regular expression specified inside the list of modified core files.');
define('PLUGIN_INTERNAL_ERROR_FILE_REPLACE_BAD_UTF8', 'An error occurred while replacing / updating content in \'%2$s\'. The file contained an invalid UTF-8 string. Please replace the file with the original file from Zen Cart (or manually change the character encoding of the file to UTF-8).');
define('PLUGIN_INTERNAL_ERROR_FILE_RESTORE', 'Filesystem Error: Unable to restore a backup of changes made to \'%2$s\'. This file may need to be manually restored from a backup.');
define('PLUGIN_INTERNAL_ERROR_NO_ZIPARCHIVE', 'PHP Error: ZipArchive is not supported by the running copy of PHP. Please contact your hosting provider and request they either compile PHP with zip support or install the PECL &quot;zip&quot; extension.');
define('PLUGIN_INTERNAL_ERROR_CREATE_ZIP', 'Filesystem Error: Unable to create the \'%3$s\' zip file (\'%2$s\'). Please verify the webserver has access to write to file.');
define('PLUGIN_INTERNAL_ERROR_OPEN_ZIP', 'Filesystem Error: Unable to open the \'%3$s\' zip file (\'%2$s\'). Please verify the webserver has access to write to file.');
define('PLUGIN_INTERNAL_ERROR_FILE_NOT_FOUND_ZIP', 'Error: Unable to read \'%3$s\' in the zip file (\'%2$s\'). Please verify the zip file exists, can be extracted, and contains \'%3$s\'.');

// Install messages
define('PLUGIN_INSTALL_SUCCESS', '%1$s installation / upgrade completed!');
define('PLUGIN_INSTALL_ERROR', '%1$s installation / upgrade failed!');
define('PLUGIN_INSTALL_ERROR_FILE_FOUND', 'Filesystem Error: Unable to delete \'%2$s\'. Verify your web server has access to delete this file. Installation will not continue until the web server is granted access to remove the file.');
define('PLUGIN_INSTALL_ERROR_FILE_NOT_FOUND', 'Filesystem Error: Unable to access \'%2$s\'. Please make sure the file exists on the webserver and the webserver has access to read the file!');
define('PLUGIN_INSTALL_ERROR_DATABASE_TABLE', 'Database Error: Unable to create table \'%2$s\'!');
define('PLUGIN_INSTALL_ERROR_DATABASE_KEY', 'Database Error: Unable to create \'%3$s\' in table \'%2$s\'!');
define('PLUGIN_INSTALL_ERROR_ADMIN_PAGE', 'Error: Unable to register admin pages. This can be caused by a database access / configuration issue or damage to core Zen Cart files.');
define('PLUGIN_INSTALL_ERROR_AUTOLOAD', 'Filesystem Error: Unable to delete \'%2$s\'. For this module to work you must delete this file manually. <strong>Do not click around in the administrative interface until you have manually deleted the file</strong>.');
define('PLUGIN_INSTALL_ERROR_SORT_ORDER', 'Database Error: Unable to access sort_order in table \'%2$s\'!');
define('PLUGIN_INSTALL_ERROR_FILE_BACKUP', 'Filesystem Error: Unable to create a backup of \'%2$s\'.');
define('PLUGIN_INSTALL_ERROR_FILE_WRITE', 'Filesystem Error: Unable to write to \'%2$s\'. Please verify the webserver has access to write to file.<br />Installation will not continue until the web server is granted access to write and delete the file.');
define('PLUGIN_INSTALL_ERROR_FILE_CHECK', 'Filesystem Error: A file \'%2$s\' required to be modified for this plugin appears to not be modified. Please verify the modifications to the plugin including any comments.<br />Installation will not continue until the checks run successfully.');
define('PLUGIN_INSTALL_FOUND_PREVIOUS_VERSION', 'Found previous version installed. Upgrading (or re-installing) %1$s (old %2$s =&gt; new %3$s).');

// Removal messages
define('PLUGIN_REMOVE_SUCCESS', '%1$s removal completed! Do not forget to undo any modifications made by %1$s to core files');
define('PLUGIN_REMOVE_ERROR', '%1$s removal failed!');
define('PLUGIN_REMOVE_ERROR_FILE_FOUND', 'Filesystem Error: Unable to delete \'%2$s\'. Verify your web server has access to delete this file. Removal will not continue until the web server is granted access to remove the file.');
define('PLUGIN_REMOVE_ERROR_DATABASE_TABLE', 'Database Error: Unable to delete table \'%2$s\'!');
define('PLUGIN_REMOVE_ERROR_DATABASE_KEY', 'Database Error: Unable to delete \'%3$s\' in table \'%2$s\'!');
define('PLUGIN_REMOVE_ERROR_ADMIN_PAGE', 'Error: Unable to remove the admin pages. This can be caused by a database access / configuration issue or damage to core Zen Cart files.');
define('PLUGIN_REMOVE_ERROR_AUTOLOAD', 'Filesystem Error: Unable to delete \'%2$s\'. For this module to work you must delete this file manually. <strong>Do not click around in the administrative interface until you have manually deleted the file</strong>.');
define('PLUGIN_REMOVE_ERROR_FILE_WRITE', 'Filesystem Error: Unable to write to \'%2$s\'. Please verify the webserver has access to write to file.<br />Removal will not continue until the web server is granted access to write and delete the file.');
define('PLUGIN_REMOVE_WARNING_BACKUP_NOT_FOUND', 'Warning: Unable to find a backup of the file \'%2$s\' created during installation. You may need to manually replace the file with one from your backups or a <a target="_blank" href="http://sourceforge.net/projects/zencart/files/">clean copy of Zen Cart</a>.');
define('PLUGIN_REMOVE_WARNING_BACKUP_RESTORE', 'Filesystem Error: Unable to restore a backup of \'%2$s\'. You may need to manually replace the file with one from your backups or a <a target="_blank" href="http://sourceforge.net/projects/zencart/files/">clean copy of Zen Cart</a>.');

// Upgrade / Downgrade messages
define('PLUGIN_UPGRADE_WARNING_DOWNGRADE', 'The installed version of \'%1$s\' is newer than the installer. This may indicate a problem occurred when the uploading files for the %1$s plugin!<br />Please upload the currently installed version before continuing. Attempting to downgrade without first removing the newer version is not recommended and may result in damage to the store.');
define('PLUGIN_UPGRADE_WARNING_DATABASE_RENAME_TABLE', 'Database Warning: Conflicting tables (older version \'%2$s\' and newer version \'%3$s\') of %1$s exist! Data from the old version will not be accessible via the Zen Cart administrative interface.');
define('PLUGIN_UPGRADE_WARNING_DATABASE_RENAME_COLUMN', 'Database Warning: Conflicting columns in table \'%2$s\' (older version \'%3$s\' and newer version \'%4$s\') of %1$s exist! Data from the old version will not be accessible via the Zen Cart administrative interface.');
define('PLUGIN_UPGRADE_ERROR_DATABASE_RENAME_TABLE', 'Database Error: Unable to rename table from \'%2$s\' to \'%3$s\'!');
define('PLUGIN_UPGRADE_ERROR_DATABASE_RENAME_COLUMN', 'Database Error: Unable to rename column in table \'%2$s\' from \'%3$s\' to \'%4$s\'!');

// Store Configuration warnings
define('PLUGIN_CONFIG_ADMIN_WARNINGS', 'Some potential issues were found in your &quot;admin&quot; configure.php. <strong>Failure to correct these issues may result in Zen Cart not functioning as intended.</strong><br />The &quot;admin&quot; configure.php file can be found at <code>%1$s</code>.');
define('PLUGIN_CONFIG_STORE_WARNINGS', 'Some potential issues were found in your &quot;store / catalog&quot; configure.php. <strong>Failure to correct these issues may result in Zen Cart not functioning as intended.</strong><br />The &quot;store / catalog&quot; configure.php file can be found at <code>%1$s</code>.');
define('PLUGIN_CONFIG_REMOVE_TRAILING_SUBDIRECTORY', 'The path to the subdirectory must be removed from <code>%1$s</code>.');
define('PLUGIN_CONFIG_REMOVE_TRAILING_SLASH', 'The <code>%1$s</code> setting cannot end with a slash. Remove the extra <code>/</code> from the end of <code>%1$s</code>');
define('PLUGIN_CONFIG_SUBDIRECTORY_INFO', 'If the store is in a subdirectory<ul><li>The name of the subdirectory should be placed in <code>%1$s</code>.</li><li>The subdirectory should be preceded and followed by slashes, e.g. <code>/store/</code>.</li><li>The subdirectory name <strong>cannot</strong> be included in the <code>%2$s</code> setting.</li></ul>');
define('PLUGIN_CONFIG_SUBDIRECTORY_EMPTY', 'The value for <code>%1$s</code> cannot be blank. If the store installed at the website root (not in a subdirectory), set the value for <code>%1$s</code> to <code>/</code>. If the store is in a subdirectory, the name of the subdirectory should be placed in <code>%1$s</code>. The subdirectory should be preceded and followed by slashes, e.g. <code>/store/</code>.');
define('PLUGIN_CONFIG_ADD_LEADING_SLASH', 'The <code>%1$s</code> setting must start with a slash. Add a <code>/</code> to the start of <code>%1$s</code>.');
define('PLUGIN_CONFIG_ADD_TRAILING_SLASH', 'The <code>%1$s</code> setting must end with a slash. Add a <code>/</code> to the end of <code>%1$s</code>.');
define('PLUGIN_CONFIG_STORE_CONFIGURE', 'Unable to load the store\'s configure.php file <code>%1$s</code>. Verify the file exists at this location and the webserver has permissions to read the file. The path is determined by reading: <code>%2$s</code>.');
define('PLUGIN_CONFIG_NOT_WRITABLE', 'Unable to write to <code>%1$s</code>. This may cause errors with some plugins or cause backup files to not be written. Please ensure the path / file exists and can be written to by the web server.');
