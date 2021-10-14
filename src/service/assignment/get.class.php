<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\assignment;
use cenozo\lib, cenozo\log;

class get extends \cenozo\service\assignment\get
{
  /** 
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    if( $this->get_argument( 'update_data', false ) )
    {
      $db_assignment = $this->get_leaf_record();
      if( !is_null( $db_assignment ) ) $db_assignment->get_interview()->update_progress();
    }
  }
}
