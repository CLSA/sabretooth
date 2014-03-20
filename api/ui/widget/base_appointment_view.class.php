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
    $site_name = lib::create( 'business\session' )->get_site()->name;
    $db_address = is_null( $record->phone_id ) ? NULL : $record->get_phone()->get_address();
    if( is_null( $db_address ) ) $db_address = $this->db_participant->get_first_address();
    $time_diff = is_null( $db_address ) ? NULL : $db_address->get_time_diff();
    if( is_null( $time_diff ) )
      $note = 'The participant\'s time zone is not known.';
    else if( 0 == $time_diff )
      $note = sprintf( 'The participant is in the same time zone as the %s site.',
                       $site_name );
    else if( 0 < $time_diff )
      $note = sprintf( 'The participant\'s time zone is %s hours ahead of %s\'s time.',
                       $time_diff,
                       $site_name );
    else if( 0 > $time_diff )
      $note = sprintf( 'The participant\'s time zone is %s hours behind of %s\'s time.',
                       abs( $time_diff ),
                       $site_name );
    $this->add_item( 'datetime', 'datetime', 'Date', $note );

    if( ( $this->get_editable() || 'add' == $this->get_name() ) )
    {
      try
      {
        $this->calendar->process();
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
   * The participant that the appointment belongs to (set by implementing classes)
   * @var database\participant $db_participant
   * @access protected
   */
  protected $db_participant = NULL;
}
