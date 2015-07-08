<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\queue;
use cenozo\lib, cenozo\log, sabretooth\util;

class patch extends \cenozo\service\patch
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    if( $this->get_argument( 'repopulate', false ) )
    { // repopulate the queue instead of patching the queue record
      $uid = $this->get_argument( 'uid', NULL );
      if( is_null( $uid ) && 3 > lib::create( 'business\session' )->get_role()->tier )
      {
        $this->status->set_code( 403 );
      }
      else
      {
        $participant_class_name = lib::get_class_name( 'database\queue' );
        $queue_class_name = lib::get_class_name( 'database\queue' );

        if( !is_null( $uid ) )
        {
          $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
          if( is_null( $db_participant ) ) $queue_class_name::repopulate( $db_participant );
        }
        else $queue_class_name::repopulate();
      }
    }
    else parent::execute();
  }

  /**
   * TODO: document
   */
  private $original_select = NULL;
}
