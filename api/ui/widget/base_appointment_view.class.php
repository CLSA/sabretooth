<?php
/**
 * base_appointment_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * base class for appointment view/add classes
 */
abstract class base_appointment_view extends \cenozo\ui\widget\base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $name, $args )
  {
    parent::__construct( 'appointment', $name, $args );
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
    
    // create the site calendar widget
    $this->site_calendar = lib::create( 'ui\widget\site_calendar', $this->arguments );
    $this->site_calendar->set_parent( $this );
    $this->site_calendar->set_variable( 'default_view', 'basicWeek' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    
    // get the interview method, start by trying to get an interview
    $db_interview_method = NULL;
    $db_interview = NULL;
    $db_assignment = $this->db_participant->get_current_assignment();
      $db_assignment = $this->db_participant->get_last_finished_assignment();
    if( !is_null( $db_assignment ) )
    {
      $db_interview_method = $db_assignment->get_interview()->get_interview_method();
    }
    else // otherwise get the default method for the first interview
    {
      $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', 1 );
      $db_interview_method = $db_qnaire->get_default_interview_method();
    }

    if( ( $this->get_editable() || 'add' == $this->get_name() ) &&
        'operator' == $db_interview_method->name )
    {
      try
      {
        $this->site_calendar->process();
        $this->set_variable( 'site_calendar', $this->site_calendar->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }
  }

  /**
   * Site calendar used to help find appointment availability
   * @var site_calendar $site_calendar
   * @access protected
   */
  protected $site_calendar = NULL;

  /**
   * The participant that the appointment belongs to (set by implementing classes)
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;
}
