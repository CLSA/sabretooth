<?php
/**
 * ivr_appointment_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: ivr_appointment new
 *
 * Create a new ivr_appointment.
 */
class ivr_appointment_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'ivr_appointment', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    $interview_class_name = lib::get_class_name( 'database\interview' );
  }
      
  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // make sure the datetime column isn't blank
    if( !array_key_exists( 'datetime', $columns ) || 0 == strlen( $columns['datetime'] ) )
      throw lib::create( 'exception\notice', 'The date/time cannot be left blank.', __METHOD__ );
    
    $db_participant = lib::create( 'database\participant', $columns['participant_id'] );
    $db_qnaire = $db_participant->get_effective_qnaire();

    // make sure the participant has a qnaire to answer
    if( is_null( $db_qnaire ) )
    {
      throw lib::create( 'exception\notice',
        'Unable to create an IVR appointment because the participant has completed all '.
        'questionnaires.',
        __METHOD__ );
    }

    // make sure that IVR_appointments have a phone number
    if( array_key_exists( 'phone_id', $columns ) )
    {
      if( NULL == $columns['phone_id'] )
        throw lib::create( 'exception\notice',
          'This participant\'s interview uses the IVR system so a phone number '.
          'must be provided for all IVR appointments.',
          __METHOD__ );
    }
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // send message to IVR
    $record = $this->get_record();
    $ivr_manager = lib::create( 'business\ivr_manager' );
    $ivr_manager->set_appointment(
      $record->get_participant(),
      $record->get_phone(),
      $record->datetime );

    // if the owner is a participant then update their queue status
    $this->get_record()->get_participant()->update_queue_status();
  }
}
