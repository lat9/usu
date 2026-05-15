<?php
/**
 * Part of Ultimate URLs, v3.1.0+, for Zen Cart.
 *
 * Last updated: v4.0.1
 *
 * @copyright Copyright 2019-2026 Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// sessions are started at 70
//
// Re-load (or initially load) the languages' initialization script to
// prevent PHP warnings for missing language elements coming from the
// Product class.  The 'base' load point was changed to 75 for zc222; versions
// prior loaded at 110.
//
// USU will load (or reload for zc222) prior to its first use and earlier
// ZC versions will reload at 110 with no harm done.
//
// This load can be removed from this file once Zen Cart versions prior to 2.2.2
// are no longer supported.
//
$autoLoadConfig[75][] = [
    'autoType'=>'init_script',
    'loadFile'=> 'init_languages.php'
];
$autoLoadConfig[75][] = [
    'autoType' => 'init_script',
    'loadFile' => 'init_usu.php'
];

// -----
// Initial load at 99, since init_sanitize is loaded at CP 100 and, if an invalid
// products_id variable is found, will attempt to redirect to that product's base
// page in that case.
//
// The zen_href_link observation needs to be present if that condition exists to
// prevent a redirect-loop.
//
$autoLoadConfig[99][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/UsuObserver.php'
];
$autoLoadConfig[99][] = [
    'autoType' => 'classInstantiate',
    'className' => 'UsuObserver',
    'objectName' => 'usu'
];
