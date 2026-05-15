<?php
/**
 * Part of Ultimate URLs for Zen Cart, v3.1.0+.
 *
 * @copyright Copyright 2019 - 2026 Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
$define = [
    'BOX_CONFIGURATION_USU' => 'Ultimate URLs');
    'BOX_CONFIGURATION_USU_UNINSTALL' => 'Uninstall Ultimate URLs');

// Messages used on the configuration page
    'USU_PLUGIN_WARNING_SHORT_WORDS' => 'The value entered for the <em>Filter short words</em> setting (<b>%s</b>) is not a positive integer; the setting has been defaulted to <b>0</b>.',
    'USU_PLUGIN_WARNING_CATEGORY_DIR' => 'The setting for <em>Display categories as directories</em> has been changed to <code>short</code>, since its <code>full</code> setting is incompatible with <em>Format of alternate URLs</em> setting of <code>parent</code>.',
    'USU_PLUGIN_WARNING_FORMAT' => 'The setting for <em>Format of alternate URLs</em> has been changed to <code>original</code>, since its <code>parent</code> setting is incompatible with <em>Display categories as directories</em> setting of <code>full</code>.',
];

$define['USU_INSTALLED_SUCCESS'] = $define['BOX_CONFIGURATION_USU'] . ', v%s, has been successfully installed.';
$define['USU_UPDATED_SUCCESS'] = $define['BOX_CONFIGURATION_USU'] . ' has been successfully updated from v%1$s to v%2$s.';

return $define;
