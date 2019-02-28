<?php
/**
 * Part of Ultimate URLs, v3.0.0+, for Zen Cart.
 *
 * @copyright Copyright 2019        Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

// sessions are started at 70
$autoLoadConfig[71][] = array(
    'autoType' => 'init_script',
    'loadFile' => 'init_usu.php'
);

// Using 120 since 110 is where the language components are established for the session
// and 161 is where the canonical link is determined
$autoLoadConfig[120][] = array(
    'autoType' => 'class',
    'loadFile' => 'observers/UsuObserver.php'
);
$autoLoadConfig[120][] = array(
    'autoType' => 'classInstantiate',
    'className' => 'UsuObserver',
    'objectName' => 'usu'
);
