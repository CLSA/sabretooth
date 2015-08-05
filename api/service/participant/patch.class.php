<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

class patch extends \cenozo\service\participant\patch
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    if( array_key_exists( 'requeue', $this->get_file_as_array() ) )
    { // repopulate the queue for this participant instead of patching the participant record
      $queue_class_name = lib::get_class_name( 'database\queue' );
      $queue_class_name::repopulate( $this->get_leaf_record() );
    }
    else parent::execute();
  }
}
