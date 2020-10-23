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
    if( $this->get_argument( 'repopulate', false ) ) $db_participant->repopulate_queue();
    else
    {
      $interview_mail = $this->get_argument( 'interview_mail', false );
      if( !is_null( $interview_mail ) )
      {
        $db_effective_interview = $db_participant->get_effective_interview();
        if( !is_null( $db_effective_interview ) && !is_null( $db_effective_interview->id ) )
        {
          if( 'resend' == $interview_mail ) $db_effective_interview->resend_mail();
          else if( 'remove' == $interview_mail ) $db_effective_interview->remove_mail();
        }
      }
    }
  }
}
