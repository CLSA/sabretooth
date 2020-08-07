<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\interview;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\patch
{
  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    // only allow switching to the web version if the participant has an email address
    $file = $this->get_file_as_array();
    $db_participant = $this->get_leaf_record()->get_participant();
    if( array_key_exists( 'method', $file ) )
    {
      if( 'web' == $file['method'] )
      {
        $is_eligible = false;
        $db_current_queue = $db_participant->get_current_queue();
        $db_queue = $db_current_queue;
        while( !is_null( $db_queue ) )
        {
          if( 'eligible' == $db_queue->name )
          {
            $is_eligible = true;
            break;
          }
          else $db_queue = $db_queue->get_parent_queue();
        }

        if( !$is_eligible )
        {
          $this->set_data( sprintf(
            'Cannot assign the participant to the web version because they are in the "%s" queue. '.
            'Participants must be eligible to answer the questionnaire in order to be changed to the web version.',
            $db_current_queue->title
          ) );
          $this->get_status()->set_code( 306 );
        }
        else if( is_null( $db_participant->email ) )
        {
          $this->set_data( 'Cannot assign the participant to the web version because they do not have an email address on file.' );
          $this->get_status()->set_code( 306 );
        }
      }
    }
  }
}
