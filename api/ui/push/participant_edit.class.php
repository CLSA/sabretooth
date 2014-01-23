<?php
/**
 * participant_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant edit
 *
 * Edit a participant.
 */
class participant_edit extends \cenozo\ui\push\participant_edit
{
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $record = $this->get_record();
    $columns = $this->get_argument( 'columns', array() );
    
    // look for columns which will affect queue status
    $column_list = array(
      'active',
      'gender',
      'state_id',
      'override_quota' );
    foreach( $this->get_record()->get_cohort()->get_service_list() as $db_service )
      $column_list[] = sprintf( '%s_site_id', $db_service->name );

    if( array_intersect_key( $columns, array_flip( $column_list ) ) )
      $record->update_queue_status();
  }
}
