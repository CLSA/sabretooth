<?php
/**
 * queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

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
        ' WHERE true'; // needed to simplify modifier concatenation in list and count methods
      
      // all participants not assigned to any qnaire
      self::$query_list['no_qnaire'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant'.
        ' WHERE id NOT IN ('.
        '   SELECT participant.id'.
        '   FROM participant_for_queue AS participant, sample_has_participant, sample'.
        '   WHERE participant.id = sample_has_participant.participant_id'.
        '   AND sample_has_participant.sample_id = sample.id'.
        '   AND sample.qnaire_id IS NOT NULL'.
        ' )';
      
      // Questionnaire
      self::$query_list['qnaire'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, sample_has_participant, sample'.
        ' WHERE participant.id = sample_has_participant.participant_id'.
        ' AND sample_has_participant.sample_id = sample.id'.
        ' AND sample.qnaire_id <QNAIRE_TEST>';
      
      // Complete
      self::$query_list['complete'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, interview'.
        ' WHERE participant.id = interview.participant_id'.
        ' AND interview.qnaire_id <QNAIRE_TEST>'.
        ' AND interview.completed = true';
      
      // Incomplete
      self::$query_list['incomplete'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['qnaire'],
        self::$query_list['complete'] );
      
      // Ineligible
      self::$query_list['ineligible'] = sprintf(
        ' %s'.
        ' AND participant.status IS NOT NULL',
        self::$query_list['incomplete'] );
      
      // Eligible
      self::$query_list['eligible'] = sprintf(
        ' %s'.
        ' AND participant.status IS NULL',
        self::$query_list['incomplete'] );

      // Currently assigned
      self::$query_list['assigned'] = sprintf(
        ' %s'.
        ' AND participant.assigned = true',
        self::$query_list['eligible'] );
      
      // Not currently assigned
      self::$query_list['not_assigned'] = sprintf(
        ' %s'.
        ' AND participant.assigned = false',
        self::$query_list['eligible'] );
      
      // Have an appointment
      self::$query_list['appointment'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, appointment, sample_has_participant, sample'.
        ' WHERE participant.id = appointment.participant_id'.
        ' AND participant.id = sample_has_participant.participant_id'.
        ' AND sample_has_participant.sample_id = sample.id'.
        ' AND sample.qnaire_id <QNAIRE_TEST>'.
        ' AND participant.id IN ( %s )',
        self::$query_list['not_assigned'] );
      
      // Upcoming Appointments
      self::$query_list['upcoming_appointment'] = sprintf(
        ' %s'.
        ' AND NOW() < appointment.date - INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "appointment"'.
        '   AND name = "call pre-window" )'.
        ' MINUTE',
        self::$query_list['appointment'] );
      
      // Assignable Appointments
      self::$query_list['assignable_appointment'] = sprintf(
        ' %s'.
        ' AND NOW() >= appointment.date - INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "appointment"'.
        '   AND name = "call pre-window" )'.
        ' MINUTE'.
        ' AND NOW() <= appointment.date + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "appointment"'.
        '   AND name = "call post-window" )'.
        ' MINUTE',
        self::$query_list['appointment'] );
      
      // Missed Appointments
      self::$query_list['missed_appointment'] = sprintf(
        ' %s'.
        ' AND NOW() > appointment.date + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "appointment"'.
        '   AND name = "call post-window" )'.
        ' MINUTE',
        self::$query_list['appointment'] );
      
      // Do not have an appointment
      self::$query_list['no_appointment'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['not_assigned'],
        self::$query_list['appointment'] );
      
      // No appointment, never assigned
      self::$query_list['new_participant'] = sprintf(
        ' %s'.
        ' AND participant.last_assignment_id IS NULL',
        self::$query_list['no_appointment'] );
      
      // Have availability
      self::$query_list['new_participant_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['new_participant'] );
      
      // Do not have availability
      self::$query_list['new_participant_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['new_participant'],
        self::$query_list['new_participant_availability'] );

      // Are currently available
      self::$query_list['new_participant_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['new_participant_availability'] );
      
      // Are not currently available
      self::$query_list['new_participant_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['new_participant_availability'],
        self::$query_list['new_participant_available'] );

      // No appointment, previously assigned
      self::$query_list['old_participant'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, assignment_last_phone_call, phone_call'.
        ' WHERE participant.last_assignment_id = assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND participant.id IN( %s )',
        self::$query_list['no_appointment'] );

      // Contacted
      self::$query_list['contacted'] = sprintf(
        ' %s'.
        ' AND phone_call.status = "contacted"',
        self::$query_list['old_participant'] );
      
      // Waiting for call-back delay
      self::$query_list['contacted_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "contacted" ) MINUTE',
        self::$query_list['contacted'] );
      
      // Ready for call-back
      self::$query_list['contacted_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "contacted" ) MINUTE',
        self::$query_list['contacted'] );
      
      // Have availability
      self::$query_list['contacted_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['contacted_ready'] );
      
      // Do not have availability
      self::$query_list['contacted_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['contacted_ready'],
        self::$query_list['contacted_availability'] );

      // Are currently available
      self::$query_list['contacted_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['contacted_availability'] );
      
      // Are not currently available
      self::$query_list['contacted_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['contacted_availability'],
        self::$query_list['contacted_available'] );

      // Busy line
      self::$query_list['busy'] = sprintf(
        ' %s'.
        ' AND phone_call.status = "busy"',
        self::$query_list['old_participant'] );
      
      // Waiting for call-back delay
      self::$query_list['busy_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "busy" ) MINUTE',
        self::$query_list['busy'] );
      
      // Ready for call-back
      self::$query_list['busy_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "busy" ) MINUTE',
        self::$query_list['busy'] );
      
      // Have availability
      self::$query_list['busy_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['busy_ready'] );
      
      // Do not have availability
      self::$query_list['busy_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['busy_ready'],
        self::$query_list['busy_availability'] );

      // Are currently available
      self::$query_list['busy_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['busy_availability'] );
      
      // Are not currently available
      self::$query_list['busy_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['busy_availability'],
        self::$query_list['busy_available'] );

      // Fax line
      self::$query_list['fax'] = sprintf(
        ' %s'.
        ' AND phone_call.status = "fax"',
        self::$query_list['old_participant'] );
      
      // Waiting for call-back delay
      self::$query_list['fax_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "fax" ) MINUTE',
        self::$query_list['fax'] );
      
      // Ready for call-back
      self::$query_list['fax_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "fax" ) MINUTE',
        self::$query_list['fax'] );
      
      // Have availability
      self::$query_list['fax_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['fax_ready'] );
      
      // Do not have availability
      self::$query_list['fax_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['fax_ready'],
        self::$query_list['fax_availability'] );

      // Are currently available
      self::$query_list['fax_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['fax_availability'] );
      
      // Are not currently available
      self::$query_list['fax_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['fax_availability'],
        self::$query_list['fax_available'] );

      // No answer
      self::$query_list['no_answer'] = sprintf(
        ' %s'.
        ' AND phone_call.status = "no answer"',
        self::$query_list['old_participant'] );
      
      // Waiting for call-back delay
      self::$query_list['no_answer_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "no answer" ) MINUTE',
        self::$query_list['no_answer'] );
      
      // Ready for call-back
      self::$query_list['no_answer_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "no answer" ) MINUTE',
        self::$query_list['no_answer'] );
      
      // Have availability
      self::$query_list['no_answer_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['no_answer_ready'] );
      
      // Do not have availability
      self::$query_list['no_answer_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['no_answer_ready'],
        self::$query_list['no_answer_availability'] );

      // Are currently available
      self::$query_list['no_answer_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['no_answer_availability'] );
      
      // Are not currently available
      self::$query_list['no_answer_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['no_answer_availability'],
        self::$query_list['no_answer_available'] );

      // Message was left
      self::$query_list['machine_message'] = sprintf(
        ' %s'.
        ' AND phone_call.status = "machine message"',
        self::$query_list['old_participant'] );
      
      // Waiting for call-back delay
      self::$query_list['machine_message_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine message" ) MINUTE',
        self::$query_list['machine_message'] );
      
      // Ready for call-back
      self::$query_list['machine_message_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine message" ) MINUTE',
        self::$query_list['machine_message'] );
      
      // Have availability
      self::$query_list['machine_message_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['machine_message_ready'] );
      
      // Do not have availability
      self::$query_list['machine_message_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['machine_message_ready'],
        self::$query_list['machine_message_availability'] );

      // Are currently available
      self::$query_list['machine_message_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['machine_message_availability'] );
      
      // Are not currently available
      self::$query_list['machine_message_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['machine_message_availability'],
        self::$query_list['machine_message_available'] );

      // Message was not left
      self::$query_list['machine_no_message'] = sprintf(
        ' %s'.
        ' AND phone_call.status = "machine no message"',
        self::$query_list['old_participant'] );
      
      // Waiting for call-back delay
      self::$query_list['machine_no_message_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine no message" ) MINUTE',
        self::$query_list['machine_no_message'] );
      
      // Ready for call-back 
      self::$query_list['machine_no_message_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine no message" ) MINUTE',
        self::$query_list['machine_no_message'] );

      // Have availability
      self::$query_list['machine_no_message_availability'] = sprintf(
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['machine_no_message_ready'] );
      
      // Do not have availability
      self::$query_list['machine_no_message_no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['machine_no_message_ready'],
        self::$query_list['machine_no_message_availability'] );

      // Are currently available
      self::$query_list['machine_no_message_available'] = sprintf(
        ' %s'.
        ' AND ( '.
        '   CASE DAYOFWEEK( NOW() )'.
        '     WHEN 1 THEN availability.sunday'.
        '     WHEN 2 THEN availability.monday'.
        '     WHEN 3 THEN availability.tuesday'.
        '     WHEN 4 THEN availability.wednesday'.
        '     WHEN 5 THEN availability.thursday'.
        '     WHEN 6 THEN availability.friday'.
        '     WHEN 7 THEN availability.saturday'.
        '     ELSE 0'.
        '   END = 1'.
        '   AND availability.start_time < NOW()'.
        '   AND availability.end_time > NOW()'.
        ' )',
        self::$query_list['machine_no_message_availability'] );
      
      // Are not currently available
      self::$query_list['machine_no_message_not_available'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN( %s )',
        self::$query_list['machine_no_message_availability'],
        self::$query_list['machine_no_message_available'] );
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
    
    $qnaire_test_sql = is_null( $this->db_qnaire ) ? 'IS NOT NULL' : '= '.$this->db_qnaire->id;
    // TODO: remove me
    if( false !== strpos( $this->name, 'busy' ) )
      \sabretooth\log::print_r( 
          sprintf( '%s %s',
                   $this->get_sql( 'COUNT( DISTINCT participant.id )', $qnaire_test_sql ),
                   $modifier->get_sql( true ) ), $this->name );
    /////////////////////////////////////
    return static::db()->get_one(
      sprintf( '%s %s',
               $this->get_sql( 'COUNT( DISTINCT participant.id )', $qnaire_test_sql ),
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
    
    $qnaire_test_sql = is_null( $this->db_qnaire ) ? 'IS NOT NULL' : '= '.$this->db_qnaire->id;
    $participant_ids = static::db()->get_col(
      sprintf( '%s %s',
               $this->get_sql( 'DISTINCT participant.id', $qnaire_test_sql ),
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
   * Get the query for this queue.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $select_participant_sql The text to put in place of the first occurance of
   *               <SELECT_PARTICIPANT>
   * @param string $qnaire_test_sql The text to put in place of <QNAIRE_TEST>
   * @return string
   * @access protected
   */
  public function get_sql( $select_participant_sql, $qnaire_test_sql )
  {
    $sql = self::$query_list[ $this->name ];
    $sql = preg_replace( '/\<SELECT_PARTICIPANT\>/', $select_participant_sql, $sql, 1 );
    $sql = str_replace( '<SELECT_PARTICIPANT>', 'participant.id', $sql );
    $sql = str_replace( '<QNAIRE_TEST>', $qnaire_test_sql, $sql );
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
