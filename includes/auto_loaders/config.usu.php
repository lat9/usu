<?php
/**
 * Part of Ultimate URLs, v3.1.0+, for Zen Cart.
 *
 * @copyright Copyright 2019-2021   Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// sessions are started at 70
$autoLoadConfig[71][] = [
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
// Unfortunately, the session-based language initialization isn't loaded until CP 110
// (by default), so we'll need to bring that in prior to the observer's load so
// that those values are available for the possible redirect prior to the current
// main page's header processing.  Note that the init_languages.php file will be
// re-loaded at CP 110, but will do no 'harm'.
//
$autoLoadConfig[99][] = [
    'autoType'=>'init_script',
    'loadFile'=> 'init_languages.php'
];
$autoLoadConfig[99][] = [
    'autoType' => 'class',
    'loadFile' => 'observers/UsuObserver.php'
];
$autoLoadConfig[99][] = [
    'autoType' => 'classInstantiate',
    'className' => 'UsuObserver',
    'objectName' => 'usu'
];
