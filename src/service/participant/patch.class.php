<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\participant\patch
{
  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $db_participant = $this->get_leaf_record();

    // update the participant's queue, if requested
    if( $this->get_argument( 'repopulate', false ) )
    {
      $old_queue_web = 'web version' == $db_participant->get_current_queue()->name;
      $db_participant->repopulate_queue();
      $new_queue_web = 'web version' == $db_participant->get_current_queue( true )->name;

      $db_effective_interview = $db_participant->get_effective_interview( false );
      if( !is_null( $db_effective_interview ) && !is_null( $db_effective_interview->id ) )
      {
        // resend or remove mail depending on the consent status
        if( !$old_queue_web && $new_queue_web ) $db_effective_interview->resend_mail();
        else if( $old_queue_web && !$new_queue_web ) $db_effective_interview->remove_mail();
      }
    }
  }
}
