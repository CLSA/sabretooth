<?php
/**
 * participant_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant view
 * 
 * @package sabretooth\ui
 */
class participant_view extends \cenozo\ui\widget\base_view
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
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'uid', 'constant', 'Unique ID' );
    $this->add_item( 'source_id', 'enum', 'Source' );
    $this->add_item( 'first_name', 'string', 'First Name' );
    $this->add_item( 'last_name', 'string', 'Last Name' );
    $this->add_item( 'language', 'enum', 'Preferred Language' );
    $this->add_item( 'status', 'enum', 'Condition' );
    $this->add_item( 'site_id', 'enum', 'Prefered Site' );
    $this->add_item( 'prior_contact_date', 'constant', 'Prior Contact Date' );
    $this->add_item( 'current_qnaire_name', 'constant', 'Current Questionnaire' );
    $this->add_item( 'start_qnaire_date', 'constant', 'Delay Questionnaire Until' );
    
    try
    {
      // create the address sub-list widget
      $this->address_list = lib::create( 'ui\widget\address_list', $args );
      $this->address_list->set_parent( $this );
      $this->address_list->set_heading( 'Addresses' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->address_list = NULL;
    }

    try
    {
      // create the phone sub-list widget
      $this->phone_list = lib::create( 'ui\widget\phone_list', $args );
      $this->phone_list->set_parent( $this );
      $this->phone_list->set_heading( 'Phone numbers' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->phone_list = NULL;
    }

    try
    {
      // create the appointment sub-list widget
      $this->appointment_list = lib::create( 'ui\widget\appointment_list', $args );
      $this->appointment_list->set_parent( $this );
      $this->appointment_list->set_heading( 'Appointments' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->appointment_list = NULL;
    }

    try
    {
      // create the availability sub-list widget
      $this->availability_list = lib::create( 'ui\widget\availability_list', $args );
      $this->availability_list->set_parent( $this );
      $this->availability_list->set_heading( 'Availability' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->availability_list = NULL;
    }

    try
    {
      // create the consent sub-list widget
      $this->consent_list = lib::create( 'ui\widget\consent_list', $args );
      $this->consent_list->set_parent( $this );
      $this->consent_list->set_heading( 'Consent information' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->consent_list = NULL;
    }

    try
    {
      // create the interview sub-list widget
      $this->interview_list = lib::create( 'ui\widget\interview_list', $args );
      $this->interview_list->set_parent( $this );
      $this->interview_list->set_heading( 'Interview history' );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->interview_list = NULL;
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
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $source_class_name = lib::get_class_name( 'database\source' );

    $sources = array();
    foreach( $source_class_name::select() as $db_source )
      $sources[$db_source->id] = $db_source->name;
    $languages = $participant_class_name::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $statuses = $participant_class_name::get_enum_values( 'status' );
    $statuses = array_combine( $statuses, $statuses );
    $sites = array();
    $site_class_name = lib::get_class_name( 'database\site' );
    foreach( $site_class_name::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    $db_site = $this->get_record()->get_site();
    $site_id = is_null( $db_site ) ? '' : $db_site->id;
    
    $start_qnaire_date = $this->get_record()->start_qnaire_date;
    if( is_null( $this->get_record()->current_qnaire_id ) )
    {
      $current_qnaire_name = '(none)';
      $start_qnaire_date = '(not applicable)';
    }
    else
    {
      $db_current_qnaire = lib::create( 'database\qnaire', $this->get_record()->current_qnaire_id );
      $current_qnaire_name = $db_current_qnaire->name;
      $start_qnaire_date = util::get_formatted_date( $start_qnaire_date, 'immediately' );
    }
    
    // set the view's items
    $this->set_item( 'active', $this->get_record()->active, true );
    $this->set_item( 'uid', $this->get_record()->uid );
    $this->set_item( 'source_id', $this->get_record()->source_id, false, $sources );
    $this->set_item( 'first_name', $this->get_record()->first_name );
    $this->set_item( 'last_name', $this->get_record()->last_name );
    $this->set_item( 'language', $this->get_record()->language, false, $languages );
    $this->set_item( 'status', $this->get_record()->status, false, $statuses );
    $this->set_item( 'site_id', $site_id, false, $sites );
    $this->set_item( 'prior_contact_date', $this->get_record()->prior_contact_date );
    $this->set_item( 'current_qnaire_name', $current_qnaire_name );
    $this->set_item( 'start_qnaire_date', $start_qnaire_date );

    $this->finish_setting_items();

    if( !is_null( $this->address_list ) )
    {
      $this->address_list->finish();
      $this->set_variable( 'address_list', $this->address_list->get_variables() );
    }

    if( !is_null( $this->phone_list ) )
    {
      $this->phone_list->finish();
      $this->set_variable( 'phone_list', $this->phone_list->get_variables() );
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

    if( !is_null( $this->interview_list ) )
    {
      $this->interview_list->finish();
      $this->set_variable( 'interview_list', $this->interview_list->get_variables() );
    }
  }
  
  /**
   * Overrides the interview list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @interview protected
   */
  public function determine_interview_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->get_record()->id );
    $class_name = lib::get_class_name( 'database\interview' );
    return $class_name::count( $modifier );
  }

  /**
   * Overrides the interview list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @interview protected
   */
  public function determine_interview_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_id', '=', $this->get_record()->id );
    $class_name = lib::get_class_name( 'database\interview' );
    return $class_name::select( $modifier );
  }

  /**
   * The participant list widget.
   * @var address_list
   * @access protected
   */
  protected $address_list = NULL;
  
  /**
   * The participant list widget.
   * @var phone_list
   * @access protected
   */
  protected $phone_list = NULL;
  
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
  
  /**
   * The participant list widget.
   * @var interview_list
   * @access protected
   */
  protected $interview_list = NULL;
}
?>
