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
   * TODO: document
   */
  public function __construct( $id = NULL )
  {
    parent::__construct( $id );

    // define the SQL for each queue
    if( 0 == count( self::$query_list ) )
    {
      // all participants
      self::$query_list['all'] =
        ' SELECT participant.id'.
        ' FROM participant';
      
      // all participants not assigned to any qnaire
      self::$query_list['no_qnaire'] =
        ' SELECT participant.id'.
        ' FROM participant'.
        ' WHERE id NOT IN ('.
        '   SELECT participant.id'.
        '   FROM participant, sample_has_participant, sample'.
        '   WHERE participant.id = sample_has_participant.participant_id'.
        '   AND sample_has_participant.sample_id = sample.id'.
        '   AND sample.qnaire_id IS NOT NULL'.
        ' )';
      
      // the following are all for $db_qnaire:
      
      // Questionnaire
      self::$query_list['qnaire'] =
        ' SELECT participant.id'.
        ' FROM participant, sample_has_participant, sample'.
        ' WHERE participant.id = sample_has_participant.participant_id'.
        ' AND sample_has_participant.sample_id = sample.id'.
        ' AND sample.qnaire_id QNAIRE_ID_TEST';
      
      // Complete
      self::$query_list['complete'] =
        ' SELECT participant.id'.
        ' FROM participant, interview'.
        ' WHERE participant.id = interview.participant_id'.
        ' AND interview.qnaire_id QNAIRE_ID_TEST'.
        ' AND interview.completed = true';
      
      // Incomplete
      self::$query_list['incomplete'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['qnaire'],
        self::$query_list['complete'] );
      
      //   Ineligible
      self::$query_list['ineligible'] = sprintf(
        ' %s'.
        ' AND participant.status IS NOT NULL',
        self::$query_list['incomplete'] );
      
      //   Eligible
      self::$query_list['eligible'] = sprintf(
        ' %s'.
        ' AND participant.status IS NULL',
        self::$query_list['incomplete'] );
      
      //     Have an appointment
      self::$query_list['appointment'] = sprintf(
        ' SELECT participant.id'.
        ' FROM participant, appointment, sample_has_participant, sample'.
        ' WHERE participant.id = appointment.participant_id'.
        ' AND participant.id = sample_has_participant.participant_id'.
        ' AND sample_has_participant.sample_id = sample.id'.
        ' AND sample.qnaire_id QNAIRE_ID_TEST'.
        ' AND participant.id IN ( %s )',
        self::$query_list['eligible'] );
      
      //       Upcomming Appointments
      self::$query_list['upcomming'] = sprintf(
        ' %s'.
        ' AND NOW() < appointment.date - INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "appointment"'.
        '   AND name = "call pre-window" )'.
        ' MINUTE',
        self::$query_list['appointment'] );
      
      //       Assignable Appointments
      self::$query_list['assignable'] = sprintf(
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
      
      //       Missed Appointments
      self::$query_list['missed'] = sprintf(
        ' %s'.
        ' AND NOW() > appointment.date + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "appointment"'.
        '   AND name = "call post-window" )'.
        ' MINUTE',
        self::$query_list['appointment'] );
      
      //     Do not have an appointment
      self::$query_list['no_appointment'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['eligible'],
        self::$query_list['appointment'] );
      
      //       Have availability
      self::$query_list['availability'] = sprintf(
        ' SELECT participant.id'.
        ' FROM participant, availability'.
        ' WHERE participant.id = availability.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['no_appointment'] );
      
      //         Are not currently available
      self::$query_list['not_available'] = sprintf(
        ' %s'.
        ' AND NOT( '.
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
        self::$query_list['availability'] );
      
      //         Are currently available
      self::$query_list['available'] = sprintf(
        ' %s'.
        ' AND ('.
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
        self::$query_list['availability'] );
      
      //           Previously assigned
      self::$query_list['available_old'] = sprintf(
        ' SELECT participant.id'.
        ' FROM participant, interview'.
        ' WHERE participant.id = interview.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['available'] );
      
      //           Never assigned
      self::$query_list['available_old'] = sprintf(
        ' SELECT participant.id'.
        ' FROM participant'.
        ' LEFT JOIN interview'.
        ' ON participant.id = interview.participant_id'.
        ' WHERE interview.id IS NULL'.
        ' AND participant.id IN ( %s )',
        self::$query_list['available'] );
      
      //       Do not have availability
      self::$query_list['no_availability'] = sprintf(
        ' %s'.
        ' AND participant.id NOT IN ( %s )',
        self::$query_list['no_appointment'],
        self::$query_list['availability'] );
      
      //         Never Assigned
      self::$query_list['new_participant'] = sprintf(
        ' SELECT participant.id'.
        ' FROM participant, interview'.
        ' WHERE participant.id = interview.participant_id'.
        ' AND participant.id IN ( %s )',
        self::$query_list['no_availability'] );
      
      //         Previously assigned
      self::$query_list['old_participant'] = sprintf(
        ' SELECT participant.id'.
        ' FROM participant'.
        ' LEFT JOIN interview'.
        ' ON participant.id = interview.participant_id'.
        ' WHERE interview.id IS NULL'.
        ' AND participant.id IN ( %s )',
        self::$query_list['no_availability'] );
      
      //           Contacted
      self::$query_list['contacted'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND phone_call.status = "contacted"'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //             Waiting for call-back delay
      self::$query_list['contacted_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() < phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "contacted" ) MINUTE',
        self::$query_list['contacted'] );
      
      //             Ready for call-back
      self::$query_list['contacted_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "contacted" ) MINUTE',
        self::$query_list['contacted'] );
      
      //           Busy line
      self::$query_list['busy'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND phone_call.status = "busy"'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //             Waiting for call-back delay
      self::$query_list['busy_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "busy" ) MINUTE',
        self::$query_list['busy'] );
      
      //             Ready for call-back
      self::$query_list['busy_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "busy" ) MINUTE',
        self::$query_list['busy'] );
      
      //           Fax line
      self::$query_list['fax'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND phone_call.status = "fax"'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //             Waiting for call-back delay
      self::$query_list['fax_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "fax" ) MINUTE',
        self::$query_list['fax'] );
      
      //             Ready for call-back
      self::$query_list['fax_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "fax" ) MINUTE',
        self::$query_list['fax'] );
      
      //           No answer
      self::$query_list['no_answer'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND phone_call.status = "no answer"'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //             Waiting for call-back delay
      self::$query_list['no_answer_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "no answer" ) MINUTE',
        self::$query_list['no_answer'] );
      
      //             Ready for call-back
      self::$query_list['no_answer_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "no answer" ) MINUTE',
        self::$query_list['no_answer'] );
      
      //           Answering machine
      self::$query_list['machine'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND ('.
        '      phone_call.status = "machine message"'.
        '   OR phone_call.status = "machine no message" )'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //             Message was left
      self::$query_list['machine_message'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND phone_call.status = "machine message"'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //               Waiting for call-back delay
      self::$query_list['machine_message_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine message" ) MINUTE',
        self::$query_list['machine_message'] );
      
      //               Ready for call-back
      self::$query_list['machine_message_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine message" ) MINUTE',
        self::$query_list['machine_message'] );
      
      //             Message was not left
      self::$query_list['machine_no_message'] = sprintf(
        ' SELECT participant_last_assignment.participant_id'.
        ' FROM participant_last_assignment, assignment_last_phone_call, phone_call'.
        ' WHERE participant_last_assignment.assignment_id ='.
        '       assignment_last_phone_call.assignment_id'.
        ' AND assignment_last_phone_call.phone_call_id = phone_call.id'.
        ' AND phone_call.status = "machine no message"'.
        ' AND participant_id IN ( %s )',
        self::$query_list['old_participant'] );
      
      //               Waiting for call-back delay
      self::$query_list['machine_no_message_waiting'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine no message" ) MINUTE',
        self::$query_list['machine_no_message'] );
      
      //               Ready for call-back 
      self::$query_list['machine_no_message_ready'] = sprintf(
        ' %s'.
        ' AND NOW() >= phone_call.end_time + INTERVAL ('.
        '   SELECT value'.
        '   FROM setting'.
        '   WHERE category = "callback timing"'.
        '   AND name = "machine no message" ) MINUTE',
        self::$query_list['machine_no_message'] );
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

Appointment
Available
Busy
Contacted
Fax
General
General Available
Machine Message
Machine No Message
Missed
No Answer

    if( !is_null( $this->db_site ) )
    { // restrict to the site
      $mod = new modifier();
      $mod->where( 'site_id', '=', $this->db_site->id );
      $province_ids = array();
      foreach( province::select( $mod ) as $db_province ) $province_ids[] = $db_province->id;
      $modifier->where( 'site_id', '=', $this->db_site->id );
      $modifier->or_where( 'site_id', '=', NULL );
      $modifier->where( 'province_id', 'IN', $province_ids );
    }

    // get the name of the queue-view
    return static::db()->get_one(
      sprintf( "SELECT COUNT(*) FROM %s %s",
               $this->view,
               $modifier->get_sql() ) );
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

    if( !is_null( $this->db_site ) )
    { // restrict to the site
      $mod = new modifier();
      $mod->where( 'site_id', '=', $this->db_site->id );
      $province_ids = array();
      foreach( province::select( $mod ) as $db_province ) $province_ids[] = $db_province->id;
      $modifier->where( 'site_id', '=', $this->db_site->id );
      $modifier->or_where( 'province_id', 'IN', $province_ids );
    }

    // get the name of the queue-view
    $participant_ids = static::db()->get_col(
      sprintf( "SELECT id FROM %s %s",
               $this->view,
               $modifier->get_sql() ) );

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
