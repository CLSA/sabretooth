<?php
/**
 * quota.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * quota: record
 */
class quota extends \cenozo\database\quota {}

quota::add_extending_table( 'state' );
