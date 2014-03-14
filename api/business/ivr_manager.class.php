<?php
/**
 * ivr_client.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Manages Vocantas communications via SOAP.
 */
class ivr_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct()
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $this->enabled = true === $setting_manager->get_setting( 'ivr', 'enabled' );
    $this->host = $setting_manager->get_setting( 'ivr', 'host' );
    $this->service = $setting_manager->get_setting( 'ivr', 'service' );
    $this->username = $setting_manager->get_setting( 'ivr', 'username' );
    $this->password = $setting_manager->get_setting( 'ivr', 'password' );
  }

  /**
   * Initializes the ivr manager
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime, exception\ivr
   * @access public
   */
  public function initialize()
  {
    if( !$this->enabled ) return;

    $this->client = new \SoapClient( sprintf( '%s/%s', $this->host, $this->service ) );
  }

  /**
   * Whether IVR is enabled.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_enabled() { return $this->enabled; }

  public function set_appointment( $db_participant, $db_phone, $datetime )
  {
    if( !$this->enabled ) return;
    if( is_null( $this->client ) ) $this->initialize();

    $survey_manager = lib::create( 'business\survey_manager' );

    // sanity checks
    if( is_null( $db_participant ) )
      throw lib::create( 'exception\argument', 'db_participant', $db_participant, __METHOD__ );
    if( is_null( $db_phone ) )
      throw lib::create( 'exception\argument', 'db_phone', $db_phone, __METHOD__ );

    // convert strings to datetime objects
    if( is_string( $datetime ) )
      $datetime = 0 < strlen( $datetime ) ? util::get_datetime_object( $datetime ) : NULL;

    // validate that input is a datetime object or NULL
    if( !( is_null( $datetime ) ||
           ( is_object( $datetime ) && 'DateTime' == get_class( $datetime ) ) ) )
      throw lib::create( 'exception\argument', 'datetime', $datetime, __METHOD__ );

    // get the participant's current interview
    $db_interview = NULL;
    $db_assignment = $db_participant->get_current_assignment();
    if( is_null( $db_assignment ) )
      $db_assignment = $db_participant->get_last_finished_assignment();
    $db_interview = is_null( $db_assignment ) ? NULL : $db_assignment->get_interview();

    // build the parameter array for the operation
    $parameters = array(
      'Id' => $db_participant->uid,
      'Phone' => preg_replace( '/[^0-9]*/', '', $db_phone->number ),
      'Call_DateTime' => $datetime->format( \DateTime::ISO8601 ),
      'First_Initial' => substr( $db_participant->first_name, 0, 1 ),
      'Last_Initial' => substr( $db_participant->last_name, 0, 1 ),
      'Last_Interview_Date' =>
        $survey_manager::get_attribute( $db_participant, $db_interview, 'last interview date' ),
      'CCT_PARK_TRM' => 
        $survey_manager::get_attribute( $db_participant, $db_interview, 'CCT_PARK_TRM' ),
      'Participant_Type' => 
        $survey_manager::get_attribute( $db_participant, $db_interview, 'cohort' ),
      'SDC_MRTL_TRM' => 
        $survey_manager::get_attribute( $db_participant, $db_interview, 'SDC_MRTL_TRM' ),
      'Age' =>
        $survey_manager::get_attribute( $db_participant, $db_interview, 'age' ),
      'Language' =>  $db_participant->language
    );

    // TODO: handle result
    $result = $this->client->InsertParticipant( $parameters );
  }

  public function remove_appointment( $db_participant )
  {
    if( !$this->enabled ) return;
    if( is_null( $this->client ) ) $this->initialize();

    // build the parameter array for the operation
    $parameters = array(
      'Id' => $db_participant->uid
    );

    // TODO: handle result
    $result = $this->client->DeleteParticipant( $parameters );
  }

  /**
   * The SOAP client object
   * @var \SoapClient
   * @access private
   */
  private $client = NULL;

  /**
   * Whether IVR is enabled.
   * @var boolean
   * @access private
   */
  private $enabled = false;

  /**
   * The path to the service to use to interact with the IVR server.
   * @var string
   * @access private
   */
  private $service = '';

  /**
   * Which username to use when connecting to the client
   * @var string
   * @access private
   */
  private $username = '';

  /**
   * Which password to use when connecting to the client
   * @var string
   * @access private
   */
  private $password = '';
}
