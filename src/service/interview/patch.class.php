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
      $this->new_method = $file['method'];
      if( 'web' == $this->new_method && is_null( $db_participant->email ) )
      {
        $this->set_data( 'Cannot assign the participant to the web version because they do not have an email address on file.' );
        $this->get_status()->set_code( 306 );
      }
    }
  }

  /**
   * Override parent method
   */
  protected function finish()
  {
    $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );

    parent::finish();

    $db_interview = $this->get_leaf_record();
    $db_script = $db_interview->get_qnaire()->get_script();

    if( !is_null( $this->new_method ) )
    {
      // try and get the respondent record from pine, if it exists
      $token = NULL;
      try
      {
        $response = $cenozo_manager->get( sprintf(
          'qnaire/%d/respondent/participant_id=%d?no_activity=1&select={"column":["token"]}',
          $db_script->pine_qnaire_id,
          $db_interview->participant_id
        ) );

        $token = $response->token;
      }
      catch( \cenozo\exception\runtime $e )
      {
        // 404 errors simply means the respondent doesn't exit
        if( false === preg_match( '/Got response code 404/', $e->get_raw_message() ) ) throw $e; 
      }

      if( 'web' == $this->new_method )
      {
        // make sure that the pine invitation/reminder mail is sent to the participant
        if( is_null( $token ) )
        {
          // create the missing respondent record
          $cenozo_manager->post(
            sprintf( 'qnaire/%d/respondent', $db_script->pine_qnaire_id ),
            array( 'participant_id' => $db_interview->participant_id )
          );
        }
        else
        {
          // resend mail for the respondent
          $cenozo_manager->patch(
            sprintf( 'respondent/token=%s?no_activity=1&action=resend_mail', $token ),
            new \stdClass
          );
        }
      }
      else if( 'phone' == $this->new_method )
      {
        if( !is_null( $token ) )
        {
          // delete any of the respondent's unsent mail
          $cenozo_manager->patch(
            sprintf( 'respondent/token=%s?no_activity=1&action=remove_mail', $token ),
            new \stdClass
          );
        }
      }
    }
  }

  /**
   * Tracks whether we're switching the participant to the web or phone method
   */
  private $new_method = NULL;
}
