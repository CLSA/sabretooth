<?php
/**
 * operator_view_assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget operator view_assignment
 * 
 * @package sabretooth\ui
 */
class operator_view_assignment extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'operator', 'view_assignment', $args );
    $this->set_heading( 'Current Assignment' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    
    // see if this user has an open assignment
    $session = \sabretooth\session::self();
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'end_time', '=', NULL );
    $assignment_list = $session->get_user()->get_assignment_list( $modifier );

    // sanity check
    if( 1 < count( $assignment_list ) )
      \sabretooth\log::crit(
        sprintf( 'Current operator (id: %d, name: %s), has more than one active assignment!',
                 $session->get_user()->id,
                 $session->get_user()->name ) );
    
    if( 1 == count( $assignment_list ) )
    {
      $db_assignment = current( $assignment_list );
      $db_participant = new \sabretooth\database\participant(
        $db_assignment->get_interview()->participant_id );
      
      $name = sprintf( "%s, %s", $db_participant->last_name, $db_participant->first_name );
      $status = $db_participant->status ? $db_participant->status : 'Normal';
      $language = 'None';
      if( 'en' == $db_participant->language ) $language = 'English';
      else if( 'fr' == $db_participant->language ) $language = 'French';

      $has_consent = 'No';
      $db_consent = $db_participant->get_current_consent();
      if( !is_null( $db_consent ) )
      {
        if( 'verbal accept' == $db_consent->event || 'written accept' == $db_consent->event )
          $has_consent = 'Yes';
      }
      
      $last_call = 'never called';
      $db_contact = $db_participant->get_last_phone_call();
      if( !is_null( $db_contact ) ) $last_call = $db_contact->status;

      $this->set_variable( 'participant_id', $db_participant->id );
      $this->set_variable( 'participant_name', $name );
      $this->set_variable( 'participant_language', $language );
      $this->set_variable( 'participant_status', $status );
      $this->set_variable( 'participant_consent', $has_consent );
      $this->set_variable( 'participant_last_call', $last_call );
    }
  }
}
?>
