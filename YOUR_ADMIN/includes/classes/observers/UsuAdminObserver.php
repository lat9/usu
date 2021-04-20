<?php
/**
 * Part of Ultimate URLs for Zen Cart.
 *
 * @copyright Copyright 2019-2021 Cindy Merkin (vinosdefrutastropicales.com)
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    die('Illegal Access');
}

class UsuAdminObserver extends base 
{
    public function __construct() 
    {
        $this->enabled = (defined('USU_ENABLED') && USU_ENABLED == 'true');
        if ($this->enabled) {
            $this->attach (
                $this, 
                array( 
                    /* From /admin/includes/functions/html_output.php */
                   'NOTIFY_SEFU_INTERCEPT_ADMCATHREF', 
                )
            );
        }
    }
  
    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7, &$p8) 
    {
        // -----
        // Moved here (was in constructor) so that any action detected by USU's admin initialization
        // file that 'should' result in a cache-clearing will actually 'stick'.  When in the constructor,
        // the action that would result in a cache-related change wouldn't have been completed when
        // the class-constructor attempts to re-build any missing caches.
        //
        if (!class_exists('usu')) {
            require DIR_FS_CATALOG . DIR_WS_CLASSES . 'usu.php';
        }
        if (!isset($this->usu)) {
            $this->usu = new usu();
        }

        switch ($eventID) {
            // -----
            // Issued at the top of the zen_catalog_href_link function to allow us to override the href-link
            // returned.
            //
            // On entry:
            //
            // $p1 ... n/a
            // $p2 ... A reference to the to-be-returned $link; set this to a non-null value to override.
            // $p3 ... A reference to the page associated with the to-be-generated link
            // $p4 ... A reference to the parameters associated with the to-be-generated link
            // $p5 ... A reference to the (input) connection type (either 'SSL' or 'NONSSL') to be used for the link
            //
            case 'NOTIFY_SEFU_INTERCEPT_ADMCATHREF':
                $p2 = $this->usu->href_link($p3, $p4, $p5);
                break;

            default:
                break;
        }
    }
 }
