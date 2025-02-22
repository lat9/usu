<?php
/**
 * Part of Ultimate URLs for Zen Cart, v4.0.0+.
 *
 * @copyright Copyright 2019, 2025  Cindy Merkin (vinosdefrutastropicales.com)
 * @copyright Copyright 2013 - 2015 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
$ultimate_urls = 'Ultimate URLs';
return [
    'BOX_CONFIGURATION_USU' => $ultimate_urls,
    'BOX_CONFIGURATION_USU_UNINSTALL' => 'Uninstall Ultimate URLs',

// Messages used on the configuration page
    'USU_PLUGIN_WARNING_SHORT_WORDS' => 'The value entered for the <em>Filter short words</em> setting (<b>%s</b>) is not a positive integer; the setting has been defaulted to <b>0</b>.',
    'USU_PLUGIN_WARNING_CATEGORY_DIR' => 'The setting for <em>Display categories as directories</em> has been changed to <code>short</code>, since its <code>full</code> setting is incompatible with <em>Format of alternate URLs</em> setting of <code>parent</code>.',
    'USU_PLUGIN_WARNING_FORMAT' => 'The setting for <em>Format of alternate URLs</em> has been changed to <code>original</code>, since its <code>parent</code> setting is incompatible with <em>Display categories as directories</em> setting of <code>full</code>.',

    'USU_INSTALLED_SUCCESS' => $ultimate_urls . ', v%s, has been successfully installed.',
    'USU_UPDATED_SUCCESS' => $ultimate_urls . ' has been successfully updated from v%1$s to v%2$s.',
];
