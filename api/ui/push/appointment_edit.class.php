<?php
/**
 * appointment_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: appointment edit
 *
 * Edit a appointment.
 */
class appointment_edit extends \cenozo\ui\push\base_edit
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
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure there is a slot available for the appointment
    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'datetime', $columns ) )
    {
      $db_participant = $this->get_record()->get_participant();
      $db_qnaire = $db_participant->get_effective_qnaire();
      $db_interview = $interview_class_name::get_unique_record(
        array( 'participant_id', 'qnaire_id' ),
        array( $db_participant->id, $db_qnaire->id ) );
      $this->db_interview_method = $db_interview->get_interview_method();

      // validate the appointment time if the interview is operator-based
      if( 'operator' == $this->db_interview_method->name )
      {
        $this->get_record()->datetime = $columns['datetime'];
        if( !$this->get_record()->validate_date() )
          throw lib::create( 'exception\notice',
            'There are no operators available during that time.', __METHOD__ );
      }
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
