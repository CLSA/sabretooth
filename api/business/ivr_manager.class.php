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
    if( !$this->enabled )
      throw lib::create( 'exception\runtime',
        'Tried to invoke IVR method but it is not enabled.',
        __METHOD__ );

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

    $last_datetime = util::get_datetime_object(
      $survey_manager::get_attribute( $db_participant, $db_interview, 'last interview date' ) );

    $marital_status =
      $survey_manager::get_attribute( $db_participant, $db_interview, 'marital status' );
    if( is_null( $marital_status ) ) $marital_status = 'UNKNOWN';

    // build the parameter array for the operation
    $parameters = array(
      'Id' => $db_participant->uid,
      'Phone' => preg_replace( '/[^0-9]*/', '', $db_phone->number ),
      'Call_DateTime' => $datetime->format( \DateTime::ISO8601 ),
      'First_Initial' => substr( $db_participant->first_name, 0, 1 ),
      'Last_Initial' => substr( $db_participant->last_name, 0, 1 ),
      'Last_Interview_Date' => $last_datetime->format( 'Y-m-d' ),
      'Parkinsonism' => 
        $survey_manager::get_attribute( $db_participant, $db_interview, 'Parkinsonism' ),
      'Participant_Type' => 
        $survey_manager::get_attribute( $db_participant, $db_interview, 'cohort' ),
      'Marital_Status' => $marital_status,
      'Age' => $survey_manager::get_attribute( $db_participant, $db_interview, 'age' ),
      'Language' => $db_participant->language ? $db_participant->language : 'en'
    );

    $header = new \SoapHeader(
      $this->host,
      'CustomCredentials',
      array( 'Username' => $this->username, 'Password' => $this->password ),
      false );
    $this->client->__setSoapHeaders( array( $header ) );
    $result = $this->client->InsertParticipant( $parameters );

    // throw an exception if there was a problem
    $return_code = static::get_return_code( 'InsertParticipant', $result );
    if( 0 != $return_code )
      throw lib::create( 'exception\runtime',
        sprintf( 'IVR service returned error code %d (%s)',
                 $return_code,
                 static::get_return_code_name( $return_code ) ),
        __METHOD__ );
  }

  public function remove_appointment( $db_participant )
  {
    if( !$this->enabled )
      throw lib::create( 'exception\runtime',
        'Tried to invoke IVR method but it is not enabled.',
        __METHOD__ );

    if( is_null( $this->client ) ) $this->initialize();

    // build the parameter array for the operation
    $parameters = array(
      'Id' => $db_participant->uid
    );

    $header = new \SoapHeader(
      $this->host,
      'CustomCredentials',
      array( 'Username' => $this->username, 'Password' => $this->password ),
      false );
    $this->client->__setSoapHeaders( array( $header ) );
    $result = $this->client->DeleteParticipant( $parameters );

    // throw an exception if there was a problem
    $return_code = static::get_return_code( 'DeleteParticipant', $result );
    if( 0 != $return_code )
      throw lib::create( 'exception\runtime',
        sprintf( 'IVR service returned error code %d (%s)',
                 $return_code,
                 static::get_return_code_name( $return_code ) ),
        __METHOD__ );
  }

  /**
   * Get the return code from the result returned from a call to the IVR service
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $service_name
   * @param \stdClass $result
   * @access protected
   * @static
   */
  static protected function get_return_code( $service_name, $result )
  {
    $result_name = $service_name.'Result';

    // validate the result
    if( !is_object( $result ) ||
        !property_exists( $result, $result_name ) ||
        !is_object( $result->$result_name ) ||
        !property_exists( $result->$result_name, 'ReturnCode' ) )
      throw lib::create( 'exception\runtime',
        'Unexpected result from the IVR server.',
        __METHOD__ );

    return $result->$result_name->ReturnCode;
  }

  /**
   * Returns the user-friendly name of a return code
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $return_code
   * @access protected
   * @static
   */
  static protected function get_return_code_name( $return_code )
  {
    if( 0 == $return_code )
      return 'Success';
    else if( 100 == $return_code )
      return 'General Error';
    else if( 201 == $return_code )
      return 'Null Credentials';
    else if( 202 == $return_code )
      return 'Invalid Username';
    else if( 203 == $return_code )
      return 'Invalid Password';
    else if( 300 == $return_code )
      return 'Invalid Participant Data';
    else if( 301 == $return_code )
      return 'Invalid Participant Id';
    else if( 303 == $return_code )
      return 'Invalid Participant Phone';
    else if( 304 == $return_code )
      return 'Invalid Participant Call Date';
    else if( 305 == $return_code )
      return 'Invalid Participant Start Date';
    else if( 306 == $return_code )
      return 'Invalid Participant End Date';
    else if( 307 == $return_code )
      return 'Invalid Participant Parkinson’s Flag';
    else if( 308 == $return_code )
      return 'Invalid Participant Participant Type';
    else if( 309 == $return_code )
      return 'Invalid Participant First Initial';
    else if( 310 == $return_code )
      return 'Invalid Participant Last Initial';
    else if( 311 == $return_code )
      return 'Invalid Participant Last Interview Date';
    else if( 312 == $return_code )
      return 'Invalid Participant Marital Status';
    else if( 313 == $return_code )
      return 'Invalid Participant Language';
    else if( 315 == $return_code )
      return 'Invalid Participant Age';
    else if( 400 == $return_code )
      return 'Data Access';
    else if( 501 == $return_code )
      return 'Invalid Export Start Date';
    else if( 502 == $return_code )
      return 'Invalid Export End Date';
    else if( 504 == $return_code )
      return 'Invalid Export Call Id Format';
    else if( 505 == $return_code )
      return 'Invalid Export Participant Id';
    else if( 601 == $return_code )
      return 'Participant Not Found';
    else return 'Unknown';
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
