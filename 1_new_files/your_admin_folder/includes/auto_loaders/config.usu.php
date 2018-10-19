<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */

if (!defined('IS_ADMIN_FLAG')) {
	die('Illegal Access');
}

$autoLoadConfig[0][] = array(
	'autoType'=>'class',
	'loadFile'=>'usu.php'
);

// must be 110 since 100 is where messageStack is instantiated
$autoLoadConfig[120][] = array(
	'autoType'=>'classInstantiate',
	'className'=>'usu',
	'objectName'=>'altURLs'
);