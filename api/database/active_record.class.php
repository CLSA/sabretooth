<?php
/**
 * active_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 */

namespace sabretooth\database;
require_once API_PATH.'/base_object.class.php';

/**
 * active_record: abstract database table object
 *
 * The active_record class represents tables in the database.  Each table has its own class which
 * extends this class.
 * @package sabretooth\database
 */
abstract class active_record extends \sabretooth\base_object {}
?>
