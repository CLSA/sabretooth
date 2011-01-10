<?php
/**
 * operation.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 */

namespace sabretooth\business;
require_once $SETTINGS[ 'api_path' ].'\base_object.class.php';

/**
 * operation: abstract class for all operations
 *
 * All operation classes extend this base operation class.  Their purpose is to execute
 * business-logic, organized into 'operations'.  Each operation is a class in of itself,
 * which extends this class.
 * @package sabretooth\business
 */
abstract class operation extends \sabretooth\base_object {}
?>
