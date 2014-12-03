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
   * @param string $subject The subject being viewed.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( $subject, $name, $args );
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

    // items common to all sub-classes
    $this->add_item( 'datetime', 'datetime', 'Date' );
    
    // create the site calendar widget
    $this->calendar = lib::create( 'ui\widget\site_calendar', $this->arguments );
    $this->calendar->set_parent( $this );
    $this->calendar->set_variable( 'default_view', 'basicWeek' );

    // get and store the participant and interview objects
    $subject = $this->parent->get_subject();
    if( 'appointment' == $subject || 'ivr_appointment' == $subject )
    {
      $this->db_interview = $this->get_record()->get_interview();
      $this->db_participant = $this->db_interview->get_participant();
    }
    else if( 'interview' == $subject )
    {
      $this->db_interview = $this->parent->get_record();
      $this->db_participant = $this->db_interview->get_participant();
    }
    else if( 'participant' == $subject )
    {
      $this->db_participant = $this->parent->get_record();
      $this->db_interview = $this->db_participant->get_effective_interview();
    }
  }

  /**
   * Validate the operation.  If validation fails this method will throw a notice exception.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws excpetion\argument, exception\permission
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure the subject is either participant or interview
    $subject = $this->parent->get_subject();
    if( 'appointment' != $subject &&
        'ivr_appointment' != $subject &&
        'interview' != $subject &&
        'participant' != $subject )
      throw lib::create( 'exception\runtime',
        'Appointment widget must have a parent with participant or interview as the subject.',
        __METHOD__ );

    // make sure the interview isn't null (can happen if effective interview is null)
    if( is_null( $this->db_interview ) )
      throw lib::create( 'exception\notice',
        'Cannot create appointment since the participant has no interviews to complete.',
        __METHOD__ );
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

    // need to add the participant's timezone information as information to the date item
    $record = $this->get_record();
    $db_site = lib::create( 'business\session' )->get_site();
    $db_address = is_null( $record->phone_id ) ? NULL : $record->get_phone()->get_address();
    if( is_null( $db_address ) ) $db_address = $this->db_participant->get_first_address();
    $time_diff = is_null( $db_address ) ? NULL : $db_address->get_time_diff();
    if( is_null( $time_diff ) )
      $note = 'The participant\'s time zone is not known.';
    else if( 0 == $time_diff )
      $note = sprintf( 'The participant is in the same time zone as the %s site.',
                       $db_site->name );
    else if( 0 < $time_diff )
      $note = sprintf( 'The participant\'s time zone is %s hours ahead of %s\'s time.',
                       $time_diff,
                       $db_site->name );
    else if( 0 > $time_diff )
      $note = sprintf( 'The participant\'s time zone is %s hours behind of %s\'s time.',
                       abs( $time_diff ),
                       $db_site->name );
    $this->add_item( 'datetime', 'datetime', 'Date', $note );

    if( ( $this->get_editable() || 'add' == $this->get_name() ) )
    {
      try
      {
        $this->calendar->process();
        $date_obj = util::get_datetime_object();
        $this->calendar->set_heading(
          sprintf( '%s (Times are in %s)',
                   $this->calendar->get_heading(),
                   $date_obj->format( 'T' ) ) );
        $this->calendar->execute();
        $this->set_variable( 'calendar', $this->calendar->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }
  }

  /**
   * Site calendar used to help find appointment availability
   * @var calendar $calendar
   * @access protected
   */
  protected $calendar = NULL;

  /**
   * The participant that the appointment belongs to
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;

  /**
   * The interview that the appointment belongs to
   * @var database\interview $db_interview
   * @access protected
   */
  protected $db_interview = NULL;
}
