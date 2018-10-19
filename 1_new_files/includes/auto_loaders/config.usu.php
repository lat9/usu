<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2012 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

if (!defined('IS_ADMIN_FLAG')) {
	die('Illegal Access');
}

$autoLoadConfig[0][] = array(
	'autoType'=>'class',
	'loadFile'=>'usu.php'
);

// sessions are started at 70
$autoLoadConfig[71][] = array(
	'autoType'=>'init_script',
	'loadFile'=> 'init_usu.php'
);

// must be 120 since 110 is where the language components are established for the session
$autoLoadConfig[120][] = array(
	'autoType'=>'classInstantiate',
	'className'=>'usu',
	'objectName'=>'altURLs'
);