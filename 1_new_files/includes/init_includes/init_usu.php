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

// Remove the configured extension if present
if(isset($_GET['main_page'])) {
	if(defined('USU_END') && USU_END != '') {
		$pos = strrpos($_GET['main_page'], USU_END);
		if($pos !== false) {
			$_GET['main_page'] = substr($_GET['main_page'], 0, $pos);
		}
	}

	if($_GET['main_page'] == FILENAME_PRODUCT_INFO) {
		// Retrieve the product type handler from the database
		$type = $db->Execute(
			'SELECT `pt`.`type_handler` ' .
			'FROM `'. TABLE_PRODUCTS .'` AS `p` ' .
			'LEFT JOIN `'. TABLE_PRODUCT_TYPES .'` AS `pt` ON `pt`.`type_id` = `p`.`products_type` ' .
			'WHERE `p`. `products_id` = \'' . (int)$_GET['products_id'] . '\' LIMIT 1'
		);
		if(!$type->EOF) {
			$_GET['main_page'] = $type->fields['type_handler'] . '_info';
		}
	}
}