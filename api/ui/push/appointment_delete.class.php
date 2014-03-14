<?php
/**
 * appointment_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: appointment delete
 */
class appointment_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
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

    // determine the interview method
    $db_participant = $this->get_record()->get_participant();
    $db_qnaire = $db_participant->get_effective_qnaire();
    $db_interview = $interview_class_name::get_unique_record(
      array( 'participant_id', 'qnaire_id' ),
      array( $db_participant->id, $db_qnaire->id ) );
    $this->db_interview_method = $db_interview->get_interview_method();
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

    // send message to IVR if interview-method is IVR
    if( 'ivr' == $this->db_interview_method->name )
    {
      $ivr_manager = lib::create( 'business\ivr_manager' );
      $ivr_manager->remove_appointment( $this->get_record()->get_participant() );
    }

    // if the owner is a participant then update their queue status
    $this->get_record()->get_participant()->update_queue_status();
  }

  /**
   * The participant's current interview's interview method (cached)
   * @var database\interview_method
   * @access private
   */
  private $db_interview_method = NULL;
}
