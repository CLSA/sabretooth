<?php
/**
 * participant_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget participant view
 * 
 * @package sabretooth\ui
 */
class participant_view extends base_view
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
    parent::__construct( 'participant', 'view', $args );
    
    // create an associative array with everything we want to display about the participant
    $this->add_item( 'first_name', 'string', 'First Name' );
    $this->add_item( 'last_name', 'string', 'Last Name' );
    $this->add_item( 'language', 'enum', 'Language' );
    $this->add_item( 'hin', 'string', 'Health Insurance Number' );
    $this->add_item( 'status', 'enum', 'Condition' );
    $this->add_item( 'site_id', 'enum', 'Prefered Site' );
    
    try
    {
      // create the sample sub-list widget
      $this->sample_list = new sample_list( $args );
      $this->sample_list->set_parent( $this );
      $this->sample_list->set_heading( 'Samples the participant belongs to' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->sample_list = NULL;
    }

    try
    {
      // create the contact sub-list widget
      $this->contact_list = new contact_list( $args );
      $this->contact_list->set_parent( $this );
      $this->contact_list->set_heading( 'Contact information' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->contact_list = NULL;
    }

    try
    {
      // create the appointment sub-list widget
      $this->appointment_list = new appointment_list( $args );
      $this->appointment_list->set_parent( $this );
      $this->appointment_list->set_heading( 'Appointments' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->appointment_list = NULL;
    }

    try
    {
      // create the availability sub-list widget
      $this->availability_list = new availability_list( $args );
      $this->availability_list->set_parent( $this );
      $this->availability_list->set_heading( 'Availability' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->availability_list = NULL;
    }

    try
    {
      // create the consent sub-list widget
      $this->consent_list = new consent_list( $args );
      $this->consent_list->set_parent( $this );
      $this->consent_list->set_heading( 'Consent information' );
    }
    catch( \sabretooth\exception\permission $e )
    {
      $this->consent_list = NULL;
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

    // create enum arrays
    $languages = \sabretooth\database\participant::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $statuses = \sabretooth\database\participant::get_enum_values( 'status' );
    $statuses = array_combine( $statuses, $statuses );
    $sites = array();
    foreach( \sabretooth\database\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    $db_site = $this->get_record()->get_site();
    $site_id = is_null( $db_site ) ? '' : $db_site->id;
    
    // set the view's items
    $this->set_item( 'first_name', $this->get_record()->first_name );
    $this->set_item( 'last_name', $this->get_record()->last_name );
    $this->set_item( 'language', $this->get_record()->language, false, $languages );
    $this->set_item( 'hin', $this->get_record()->hin );
    $this->set_item( 'status', $this->get_record()->status, false, $statuses );
    $this->set_item( 'site_id', $site_id, false, $sites );

    $this->finish_setting_items();

    if( !is_null( $this->sample_list ) )
    {
      $this->sample_list->finish();
      $this->set_variable( 'sample_list', $this->sample_list->get_variables() );
    }

    if( !is_null( $this->contact_list ) )
    {
      $this->contact_list->finish();
      $this->set_variable( 'contact_list', $this->contact_list->get_variables() );
    }

    if( !is_null( $this->appointment_list ) )
    {
      $this->appointment_list->finish();
      $this->set_variable( 'appointment_list', $this->appointment_list->get_variables() );
    }

    if( !is_null( $this->availability_list ) )
    {
      $this->availability_list->finish();
      $this->set_variable( 'availability_list', $this->availability_list->get_variables() );
    }

    if( !is_null( $this->consent_list ) )
    {
      $this->consent_list->finish();
      $this->set_variable( 'consent_list', $this->consent_list->get_variables() );
    }
  }
  
  /**
   * The participant list widget.
   * @var sample_list
   * @access protected
   */
  protected $sample_list = NULL;
  
  /**
   * The participant list widget.
   * @var contact_list
   * @access protected
   */
  protected $contact_list = NULL;
  
  /**
   * The participant list widget.
   * @var appointment_list
   * @access protected
   */
  protected $appointment_list = NULL;
  
  /**
   * The participant list widget.
   * @var availability_list
   * @access protected
   */
  protected $availability_list = NULL;
  
  /**
   * The participant list widget.
   * @var consent_list
   * @access protected
   */
  protected $consent_list = NULL;
}
?>
