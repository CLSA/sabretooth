<?php
/**
 * widget.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\interface
 */

namespace sabretooth\interface;
require_once $SETTINGS[ 'api_path' ].'\base_object.class.php';

/**
 * widget: abstract widget class
 * 
 * The web-interface for sabretooth is organized into small, reusable widgets, each of which extend
 * this class.
 * @package sabretooth\interface
 */
abstract class widget extends \sabretooth\base_object {}
?>
