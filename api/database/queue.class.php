<?php
/**
 * queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * queue: record
 *
 * @package sabretooth\database
 */
class queue extends record
{
  /**
   * Constructor
   * 
   * The constructor either creates a new object which can then be insert into the database by
   * calling the {@link save} method, or, if an primary key is provided then the row with the
   * requested primary id will be loaded.
   * This method overrides the parent constructor because of custom sql required by each queue.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $id The primary key for this object.
   * @throws exception\runtime
   * @access public
   */
  public function __construct( $id = NULL )
  {
    parent::__construct( $id );

    // define the SQL for each queue
    if( 0 == count( self::$query_list ) )
    {
      // all participants
      self::$query_list['all'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant'.
        // needed to simplify modifier concatenation in list and count methods
        ' WHERE true'; 
      
      // Completed all qnaires
      self::$query_list['finished'] = sprintf(
        ' %s'.
        // no current_qnaire_id means no qnaires left to complete
        ' AND current_qnaire_id IS NULL',
        self::$query_list['all'] );

      // Ineligible
      self::$query_list['ineligible'] = sprintf(
        ' %s'.
        // current_qnaire_id is the either the next qnaire to work on or the one in progress
        ' AND current_qnaire_id IS NOT NULL'.
        // ineligible means either inactive or with a "final" status
        ' AND ('.
        '   participant.active = false'.
        '   OR participant.status IS NOT NULL'.
        '   OR phone_number_count = 0'.
        '   OR last_consent IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )',
        self::$query_list['all'] );
      
      // Inactive
      self::$query_list['inactive'] = sprintf(
        ' %s'.
        ' AND participant.active = false',
        self::$query_list['all'] );
      
      // refused consent
      self::$query_list['refused consent'] = sprintf(
        ' %s'.
        ' AND participant.active = true'.
        ' AND last_consent IN( "verbal deny", "written deny", "retract", "withdraw" )',
        self::$query_list['all'] );

      // No phone number
      self::$query_list['sourcing required'] = sprintf(
        ' %s'.
        ' AND participant.active = true'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )'.
        ' AND phone_number_count = 0',
        self::$query_list['all'] );

      // this is needed below
      $status_sql = sprintf( 
        ' %s'.
        ' AND participant.active = true'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )'.
        ' AND phone_number_count > 0',
        self::$query_list['all'] );
      
      // Deceased
      self::$query_list['deceased'] = sprintf(
        ' %s'.
        ' AND participant.status = "deceased"',
        $status_sql );
      
      // Deaf
      self::$query_list['deaf'] = sprintf(
        ' %s'.
        ' AND participant.status = "deaf"',
        $status_sql );
      
      // Language barrier
      self::$query_list['language barrier'] = sprintf(
        ' %s'.
        ' AND participant.status = "language barrier"',
        $status_sql );
      
      // Mentally unfit
      self::$query_list['mentally unfit'] = sprintf(
        ' %s'.
        ' AND participant.status = "mentally unfit"',
        $status_sql );
      
      // Other
      self::$query_list['other'] = sprintf(
        ' %s'.
        ' AND participant.status = "other"',
        $status_sql );
      
      // Eligible
      self::$query_list['eligible'] = sprintf(
        ' %s'.
        // current_qnaire_id is the either the next qnaire to work on or the one in progress
        ' AND current_qnaire_id IS NOT NULL'.
        // active participant who does not have a "final" status
        ' AND participant.active = true'.
        ' AND participant.status IS NULL'.
        ' AND phone_number_count != 0'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )',
        self::$query_list['all'] );

      // Active qnaire
      self::$query_list['qnaire'] = sprintf(
        ' %s'.
        // current_qnaire_id is always set whether the qnaire is ready to start or not
        ' AND participant.current_qnaire_id <QNAIRE_TEST>',
        self::$query_list['eligible'] );

      // Waiting for qnaire
      self::$query_list['qnaire waiting'] = sprintf(
        ' %s'.
        // the current qnaire cannot start before start_qnaire_date
        ' AND participant.start_qnaire_date IS NOT NULL'.
        ' AND DATE( participant.start_qnaire_date ) > DATE( UTC_TIMESTAMP() )'.
        ' AND participant.current_qnaire_id <QNAIRE_TEST>',
        self::$query_list['eligible'] );
      
      // Currently assigned
      self::$query_list['assigned'] = sprintf(
        ' %s'.
        ' AND participant.assigned = true',
        self::$query_list['qnaire'] );
      
      // Not currently assigned
      self::$query_list['not assigned'] = sprintf(
        ' %s'.
        // the qnaire is ready to start if the start_qnaire_date is null or we have
        // reached that date
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
        ' AND participant.assigned = false',
        self::$query_list['qnaire'] );
      
      // Have an appointment
      self::$query_list['appointment'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, appointment'.
        // from 'eligible'
        ' WHERE current_qnaire_id IS NOT NULL'.
        ' AND participant.active = true'.
        ' AND participant.status IS NULL'.
        ' AND phone_number_count != 0'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )'.
        // from 'qnaire'
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        // from 'not assigned'
        ' AND participant.assigned = false'.
        // link to appointment table and make sure the appointment hasn't been assigned
        // (by design, there can only ever one unassigned appointment per participant)
        ' AND appointment.participant_id = participant.id'.
        ' AND appointment.assignment_id IS NULL';

      // Upcoming Appointments
      self::$query_list['upcoming appointment'] = sprintf(
        ' %s'.
        // appointment time (which is in UTC) is in the future
        ' AND UTC_TIMESTAMP() < appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE',
        self::$query_list['appointment'] );
      
      // Assignable Appointments
      self::$query_list['assignable appointment'] = sprintf(
        ' %s'.
        ' AND UTC_TIMESTAMP() >= appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE'.
        ' AND UTC_TIMESTAMP() <= appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE',
        self::$query_list['appointment'] );
      
      // Missed Appointments
      self::$query_list['missed appointment'] = sprintf(
        ' %s'.
        ' AND UTC_TIMESTAMP() > appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE',
        self::$query_list['appointment'] );
      
      // Do not have an appointment
      self::$query_list['no appointment'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant'.
        // left join to unassigned appointments
        ' LEFT JOIN appointment'.
        ' ON appointment.participant_id = participant.id'.
        ' AND appointment.assignment_id IS NULL'.
        // from 'eligible'
        ' WHERE current_qnaire_id IS NOT NULL'.
        ' AND participant.active = true'.
        ' AND participant.status IS NULL'.
        ' AND phone_number_count != 0'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )'.
        // from 'qnaire'
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        // from 'not assigned'
        ' AND participant.assigned = false'.
        // make sure there is no appointment (the left-join above only links to unassigned
        // appointments, which by design there can only be one of per participant, so if
        // the appointment is null then the participant has no pending appointments)
        ' AND appointment.id IS NULL';

      // No appointment, never assigned
      self::$query_list['new participant'] = sprintf(
        ' %s'.
        // If there is a start_qnaire_date then the current qnaire has never been started,
        // the exception is for participants who have never been assigned
        ' AND ('.
        '   participant.start_qnaire_date IS NOT NULL'.
        '   OR participant.last_assignment_id IS NULL'.
        ' )',
        self::$query_list['no appointment'] );
      
      // this is needed below
      $availability_sql =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant'.
        ' LEFT JOIN participant_available'.
        ' ON participant_available.participant_id = participant.id'.
        // from 'no appointment'
        ' LEFT JOIN appointment'.
        ' ON appointment.participant_id = participant.id'.
        ' AND appointment.assignment_id IS NULL'.
        ' WHERE current_qnaire_id IS NOT NULL'.
        ' AND participant.active = true'.
        ' AND participant.status IS NULL'.
        ' AND phone_number_count != 0'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )'.
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        ' AND participant.assigned = false'.
        ' AND appointment.id IS NULL'.
        // from 'new participant'
        ' AND ('.
        '   participant.start_qnaire_date IS NOT NULL'.
        '   OR participant.last_assignment_id IS NULL'.
        ' )';

      // Does not have availability
      self::$query_list['new participant always available'] = sprintf(
        ' %s'.
        // make sure no availability exists
        ' AND participant_available.available IS NULL',
        $availability_sql );
      
      // Are currently available
      self::$query_list['new participant available'] = sprintf(
        ' %s'.
        ' AND participant_available.available = true',
        $availability_sql );
      
      // Are not currently available
      self::$query_list['new participant not available'] = sprintf(
        ' %s'.
        ' AND participant_available.available = false',
        $availability_sql );

      // No appointment, previously assigned
      self::$query_list['old participant'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM assignment_last_phone_call, phone_call, participant_for_queue AS participant'.
        ' LEFT JOIN participant_available'.
        ' ON participant_available.participant_id = participant.id'.
        // from 'no appointment'
        ' LEFT JOIN appointment'.
        ' ON appointment.participant_id = participant.id'.
        ' AND appointment.assignment_id IS NULL'.
        ' WHERE current_qnaire_id IS NOT NULL'.
        ' AND participant.active = true'.
        ' AND participant.status IS NULL'.
        ' AND phone_number_count != 0'.
        ' AND ('.
        '   last_consent IS NULL'.
        '   OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ' )'.
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        ' AND participant.assigned = false'.
        ' AND appointment.id IS NULL'.
        // straight join the assignment_last_phone_call and phone_call tables
        ' AND assignment_last_phone_call.assignment_id = participant.last_assignment_id'.
        ' AND phone_call.id = assignment_last_phone_call.phone_call_id'.
        // if there is no start_qnaire_date then the current qnaire has been started
        ' AND participant.start_qnaire_date IS NULL';
       
      // now add the sql for each call back status
      $phone_call_statuses = array( 'contacted',
                                    'busy',
                                    'no answer',
                                    'machine message',
                                    'machine no message',
                                    'fax',
                                    'not reached' );

      foreach( $phone_call_statuses as $phone_call_status )
      {
        // Main phone call status grouping
        self::$query_list[$phone_call_status] = sprintf(
          ' %s'.
          ' AND phone_call.status = "%s"',
          self::$query_list['old participant'],
          $phone_call_status );
        
        // Waiting for call-back delay
        self::$query_list[$phone_call_status.' waiting'] = sprintf(
          ' %s'.
          ' AND UTC_TIMESTAMP() < phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE',
          self::$query_list[$phone_call_status],
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        
        // Ready for call-back
        self::$query_list[$phone_call_status.' ready'] = sprintf(
          ' %s'.
          ' AND UTC_TIMESTAMP() >= phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE',
          self::$query_list[$phone_call_status],
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        
        // Do not have availability
        self::$query_list[$phone_call_status.' always available'] = sprintf(
          ' %s'.
          ' AND participant_available.available IS NULL',
          self::$query_list[$phone_call_status.' ready'] );
  
        // Are not currently available
        self::$query_list[$phone_call_status.' not available'] = sprintf(
          ' %s'.
          ' AND participant_available.available = false',
          self::$query_list[$phone_call_status.' ready'] );
  
        // Are currently available
        self::$query_list[$phone_call_status.' available'] = sprintf(
          ' %s'.
          ' AND participant_available.available = true',
          self::$query_list[$phone_call_status.' ready'] );
      }
    }
  }

  /**
   * Returns the number of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @return int
   * @access public
   */
  public function get_participant_count( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = new modifier();

    // restrict to the site
    if( !is_null( $this->db_site ) ) $modifier->where( 'base_site_id', '=', $this->db_site->id );
    
    return static::db()->get_one(
      sprintf( '%s %s',
               $this->get_sql( 'COUNT( DISTINCT participant.id )' ),
               $modifier->get_sql( true ) ) );
  }

  /**
   * Returns a list of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @return array( participant )
   * @access public
   */
  public function get_participant_list( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = new modifier();

    // restrict to the site
    if( !is_null( $this->db_site ) ) $modifier->where( 'base_site_id', '=', $this->db_site->id );

    $participant_ids = static::db()->get_col(
      sprintf( '%s %s',
               $this->get_sql( 'DISTINCT participant.id' ),
               $modifier->get_sql( true ) ) );

    $participants = array();
    foreach( $participant_ids as $id ) $participants[] = new participant( $id );
    return $participants;
  }

  /**
   * The qnaire to restrict the queue to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param qnaire $db_qnaire
   * @access public
   */
  public function set_qnaire( $db_qnaire = NULL )
  {
    $this->db_qnaire = $db_qnaire;
  }

  /**
   * The site to restrict the queue to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site
   * @access public
   */
  public function set_site( $db_site = NULL )
  {
    $this->db_site = $db_site;
  }
  
  /**
   * Get whether this queue is related to an appointment
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function from_appointment()
  {
    return in_array( $this->name, array( 'appointment',
                                         'upcoming appointment',
                                         'assignable appointment',
                                         'missed appointment' ) );
  }

  /**
   * Get the query for this queue.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $select_participant_sql The text to put in place of the first occurance of
   *               <SELECT_PARTICIPANT>
   * @return string
   * @access protected
   */
  protected function get_sql( $select_participant_sql )
  {
    $sql = self::$query_list[ $this->name ];
    $sql = preg_replace( '/\<SELECT_PARTICIPANT\>/', $select_participant_sql, $sql, 1 );
    $sql = str_replace( '<SELECT_PARTICIPANT>', 'participant.id', $sql );
    $qnaire_test_sql = is_null( $this->db_qnaire ) ? 'IS NOT NULL' : '= '.$this->db_qnaire->id;
    $sql = str_replace( '<QNAIRE_TEST>', $qnaire_test_sql, $sql );

    // fill in the settings
    $setting_manager = bus\setting_manager::self();
    $setting = $setting_manager->get_setting( 'appointment', 'call pre-window' );
    $sql = str_replace( '<APPOINTMENT_PRE_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'appointment', 'call post-window' );
    $sql = str_replace( '<APPOINTMENT_POST_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'contacted' );
    $sql = str_replace( '<CALLBACK_CONTACTED>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'busy' );
    $sql = str_replace( '<CALLBACK_BUSY>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'fax' );
    $sql = str_replace( '<CALLBACK_FAX>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'not reached' );
    $sql = str_replace( '<CALLBACK_NOT_REACHED>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'no answer' );
    $sql = str_replace( '<CALLBACK_NO_ANSWER>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'machine message' );
    $sql = str_replace( '<CALLBACK_MACHINE_MESSAGE>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback timing', 'machine no message' );
    $sql = str_replace( '<CALLBACK_MACHINE_NO_MESSAGE>', $setting, $sql );
    return $sql;
  }
  
  /**
   * The qnaire to restrict the queue to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var qnaire $db_qnaire
   */
  protected $db_qnaire = NULL;

  /**
   * The site to restrict the queue to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var site
   */
  protected $db_site = NULL;

  /**
   * The queries for each queue
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var associative array
   */
  protected static $query_list = array();
}
?>
