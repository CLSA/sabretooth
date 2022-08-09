<?php
/**
 * delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\queue\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class delete extends \cenozo\service\delete
{
  /**
   * Extends parent method
   */
  protected function validate()
  {
    parent::validate();

    if( $this->may_continue() )
    {
      // never allow anyone to remove participants from a queue
      $this->status->set_code( 403 );
    }
  }
}
