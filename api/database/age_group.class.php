<?php
/**
 * age_group.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * age_group: record
 *
 * @package sabretooth\database
 */
class age_group extends \cenozo\database\record {}

// define the lower as the primary unique key
age_group::set_primary_unique_key( 'uq_lower' );
?>
