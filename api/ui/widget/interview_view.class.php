<?php
/**
 * interview_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget interview view
 * 
 * @package sabretooth\ui
 */
class interview_view extends base_view
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
    parent::__construct( 'interview', 'view', $args );

    // create an associative array with everything we want to display about the interview
    $this->add_item( 'uid', 'constant', 'UID' );
    $this->add_item( 'participant', 'constant', 'Participant' );
    $this->add_item( 'qnaire', 'constant', 'Questionnaire' );
    $this->add_item( 'completed', 'boolean', 'Completed',
      'Warning: force-completing an interview cannot be undone!' );

    try
    {
      // create the assignment sub-list widget
      $this->assignment_list = new assignment_list( $args );
      $this->assignment_list->set_parent( $this );
      $this->assignment_list->set_heading( 'Assignments associated with this interview' );
    }
    catch( exc\permission $e )
    {
      $this->assignment_list = NULL;
    }
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
       
    $db_participant = $this->get_record()->get_participant();
    $participant = sprintf( '%s, %s', $db_participant->last_name, $db_participant->first_name );

    // set the view's items
    $this->set_item( 'uid', $db_participant->uid );
    $this->set_item( 'participant', $participant );
    $this->set_item( 'qnaire', $this->get_record()->get_qnaire()->name );
    $this->set_item( 'completed', $this->get_record()->completed, true );

    $this->finish_setting_items();

    // finish the child widgets
    if( !is_null( $this->assignment_list ) )
    {
      $this->assignment_list->finish();
      $this->set_variable( 'assignment_list', $this->assignment_list->get_variables() );
    }
  }
  
  /**
   * The interview list widget.
   * @var assignment_list
   * @access protected
   */
  protected $assignment_list = NULL;
}
?>
