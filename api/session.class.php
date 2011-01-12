<?php
/**
 * session.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 */

namespace sabretooth\business;
require_once $SETTINGS[ 'api_path' ].'/base_object.class.php';

/**
 * session: handles all session-based information
 *
 * The session class is used to track all information from the time a user logs into the system
 * until they log out.
 * @package sabretooth\business
 */
abstract class session extends \sabretooth\base_object {}
?>
