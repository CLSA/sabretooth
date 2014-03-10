<?php
/**
 * qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * qnaire: record
 */
class qnaire extends \cenozo\database\has_rank
{
  /**
   * Allow access to default interview method object
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\interview_method
   * @access public
   */
  public function get_default_interview_method()
  {
    return is_null( $this->default_interview_method_id ) ?
      NULL : lib::create( 'database\interview_method', $this->default_interview_method_id );
  }
}
