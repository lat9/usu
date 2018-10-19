<?php

if (!defined('IS_ADMIN_FLAG')) { die('Illegal Access'); }

@require_once DIR_FS_ADMIN . DIR_WS_CLASSES . 'usu_plugin.php';
$plugin = new usu_plugin();
$plugin->install();
