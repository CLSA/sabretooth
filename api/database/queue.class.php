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
   * @access public
   */
  public function __construct( $id = NULL )
  {
    parent::__construct( $id );

    // define the SQL for each queue
    if( 0 == count( self::$query_list ) )
    {
      $queue_list = array(
        'all',
        'finished',
        'ineligible',
        'inactive',
        'refused consent',
        'sourcing required',
        'deceased',
        'deaf',
        'language barrier',
        'mentally unfit',
        'other',
        'eligible',
        'qnaire',
        'restricted',
        'qnaire waiting',
        'assigned',
        'not assigned',
        'appointment',
        'upcoming appointment',
        'assignable appointment',
        'missed appointment',
        'no appointment',
        'new participant',
        'new participant always available',
        'new participant available',
        'new participant not available',
        'old participant' );
       
      foreach( $queue_list as $queue )
      {
        $parts = $this->get_query_parts( $queue );
        
        $select_sql = '';
        $first = true;
        foreach( $parts['select'] as $select )
        {
          $select_sql .= sprintf( $first ? 'SELECT %s' : ', %s', $select );
          $first = false;
        }
        
        $from_sql = '';
        $first = true;
        // reverse order to make sure join to participant_for_queue table works
        foreach( array_reverse( $parts['from'] ) as $from )
        {
          $from_sql .= sprintf( $first ? 'FROM %s' : ', %s', $from );
          $first = false;
        }
        
        $join_sql = '';
        foreach( $parts['join'] as $join ) $join_sql .= ' '.$join;
        
        $where_sql = 'WHERE true';
        foreach( $parts['where'] as $where ) $where_sql .= ' AND '.$where;

        self::$query_list[$queue] =
          sprintf( '%s %s %s %s', $select_sql, $from_sql, $join_sql, $where_sql );
      }
      
      // now add the sql for each call back status
      foreach( phone_call::get_enum_values( 'status' ) as $phone_call_status )
      {
        // ignore statuses which result in deactivating phone numbers
        if( 'disconnected' != $phone_call_status && 'wrong number' != $phone_call_status )
        {
          $queue_list = array(
            'phone call status',
            'phone call status waiting',
            'phone call status ready',
            'phone call status always available',
            'phone call status not available',
            'phone call status available' );

          foreach( $queue_list as $queue )
          {
            $parts = $this->get_query_parts( $queue, $phone_call_status );
            
            $select_sql = '';
            $first = true;
            foreach( $parts['select'] as $select )
            {
              $select_sql .= sprintf( $first ? 'SELECT %s' : ', %s', $select );
              $first = false;
            }
            
            $from_sql = '';
            $first = true;
            // reverse order to make sure join to participant_for_queue table works
            foreach( array_reverse( $parts['from'] ) as $from )
            {
              $from_sql .= sprintf( $first ? 'FROM %s' : ', %s', $from );
              $first = false;
            }
            
            $join_sql = '';
            foreach( $parts['join'] as $join ) $join_sql .= ' '.$join;
            
            $where_sql = 'WHERE true';
            foreach( $parts['where'] as $where ) $where_sql .= ' AND '.$where;
            
            $queue_name = str_replace( 'phone call status', $phone_call_status, $queue );
            self::$query_list[$queue_name] =
              sprintf( '%s %s %s %s', $select_sql, $from_sql, $join_sql, $where_sql );
          }
        }
      }
    }
  }

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
   *
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
      
      // Here is where we test whether a participant is restricted base on the queue_restriction
      // table.
      $check_restriction_sql =
        ' ('.
        // tests to see if all restrictions are null (meaning, no restriction)
        '   ('.
        '     queue_restriction.site_id IS NULL AND'.
        '     queue_restriction.city IS NULL AND'.
        '     queue_restriction.region_id IS NULL AND'.
        '     queue_restriction.postcode IS NULL'.
        '   )'.
        // tests to see if the site is being restricted but the participant isn't included
        '   OR ('.
        '     queue_restriction.site_id IS NOT NULL AND'.
        '     queue_restriction.site_id != participant.base_site_id'.
        '   )'.
        // tests to see if the city is being restricted but the participant isn't included
        '   OR ('.
        '     queue_restriction.city IS NOT NULL AND'.
        '     queue_restriction.city != participant.city'.
        '   )'.
        // tests to see if the region is being restricted but the participant isn't included
        '   OR ('.
        '     queue_restriction.region_id IS NOT NULL AND'.
        '     queue_restriction.region_id != participant.region_id'.
        '   )'.
        // tests to see if the postcode is being restricted but the participant isn't included
        '   OR ('.
        '     queue_restriction.postcode IS NOT NULL AND'.
        '     queue_restriction.postcode != participant.postcode'.
        '   )'.
        ' )';
      
      // Restricted
      self::$query_list['restricted'] =
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant'.
        // left join to queue_restriction
        ' LEFT JOIN queue_restriction'.
        ' ON queue_restriction.site_id = participant.base_site_id'.
        ' OR queue_restriction.city = participant.city'.
        ' OR queue_restriction.region_id = participant.region_id'.
        ' OR queue_restriction.postcode = participant.postcode'.
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
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        // make sure to only include participants who are not free of restrictions
        ' AND NOT '.$check_restriction_sql;

      // this is needed below
      $not_restricted_sql = 
        ' SELECT <SELECT_PARTICIPANT>'.
        ' FROM participant_for_queue AS participant'.
        // left join to queue_restriction
        ' LEFT JOIN queue_restriction'.
        ' ON queue_restriction.site_id = participant.base_site_id'.
        ' OR queue_restriction.city = participant.city'.
        ' OR queue_restriction.region_id = participant.region_id'.
        ' OR queue_restriction.postcode = participant.postcode'.
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
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        // make sure to only include participants who are free of restrictions
        ' AND '.$check_restriction_sql;

      // Waiting for qnaire
      self::$query_list['qnaire waiting'] = 
        $not_restricted_sql.
        // the current qnaire cannot start before start_qnaire_date
        ' AND participant.start_qnaire_date IS NOT NULL'.
        ' AND DATE( participant.start_qnaire_date ) > DATE( UTC_TIMESTAMP() )';
      
      // Currently assigned
      self::$query_list['assigned'] =
        $not_restricted_sql.
        // assigned participants
        ' AND participant.assigned = true',
        self::$query_list['qnaire'];
      
      // Not currently assigned
      self::$query_list['not assigned'] =
        $not_restricted_sql.
        // the qnaire is ready to start if the start_qnaire_date is null or we have
        // reached that date
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
        ' AND participant.assigned = false',
        self::$query_list['qnaire'];
      
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
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        // from 'not assigned'
        $not_restricted_sql.
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
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
        ' AND participant.current_qnaire_id <QNAIRE_TEST>'.
        // from 'not assigned'
        $not_restricted_sql.
        ' AND ( '.
        '   participant.start_qnaire_date IS NULL'.
        '   OR DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ' )'.
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
        $not_restricted_sql.
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
        $not_restricted_sql.
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
      foreach( phone_call::get_enum_values( 'status' ) as $phone_call_status )
      {
        // ignore statuses which result in deactivating phone numbers
        if( 'disconnected' != $phone_call_status && 'wrong number' != $phone_call_status )
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
  }
  */

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
   * Gets the parts of the query for a particular queue.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return associative array
   * @access protected
   */
  protected function get_query_parts( $queue, $phone_call_status = NULL )
  {
    // first a list of commonly used elements
    $status_where_list = array(
      'participant.active = true',
      '('.
      '  last_consent IS NULL'.
      '  OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
      ')',
      'phone_number_count > 0' );
    
    // join to the queue_restriction table based on site, city, region or postcode
    $restriction_join = 
      'LEFT JOIN queue_restriction '.
      'ON queue_restriction.site_id = participant.base_site_id '.
      'OR queue_restriction.city = participant.city '.
      'OR queue_restriction.region_id = participant.region_id '.
      'OR queue_restriction.postcode = participant.postcode';
    
    // checks to see if participant is not restricted
    $check_restriction_sql =
      '('.
      // tests to see if all restrictions are null (meaning, no restriction)
      '  ('.
      '    queue_restriction.site_id IS NULL AND'.
      '    queue_restriction.city IS NULL AND'.
      '    queue_restriction.region_id IS NULL AND'.
      '    queue_restriction.postcode IS NULL'.
      '  )'.
      // tests to see if the site is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.site_id IS NOT NULL AND'.
      '    queue_restriction.site_id != participant.base_site_id'.
      '  )'.
      // tests to see if the city is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.city IS NOT NULL AND'.
      '    queue_restriction.city != participant.city'.
      '  )'.
      // tests to see if the region is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.region_id IS NOT NULL AND'.
      '    queue_restriction.region_id != participant.region_id'.
      '  )'.
      // tests to see if the postcode is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.postcode IS NOT NULL AND'.
      '    queue_restriction.postcode != participant.postcode'.
      '  )'.
      ')';

    // now determine the sql parts for the given queue
    if( 'all' == $queue )
    {
      $parts = array(
        'select' => array( '<SELECT_PARTICIPANT>' ),
        'from' => array( 'participant_for_queue AS participant' ),
        'join' => array(),
        'where' => array() );
      return $parts;
    }
    else if( 'finished' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      // no current_qnaire_id means no qnaires left to complete
      $parts['where'][] = 'current_qnaire_id IS NULL';
      return $parts;
    }
    else if( 'ineligible' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      // current_qnaire_id is the either the next qnaire to work on or the one in progress
      $parts['where'][] = 'current_qnaire_id IS NOT NULL';
      // ineligible means either inactive or with a "final" status
      $parts['where'][] =
        '('.
        '  participant.active = false'.
        '  OR participant.status IS NOT NULL'.
        '  OR phone_number_count = 0'.
        '  OR last_consent IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      return $parts;
    }
    else if( 'inactive' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'][] = 'participant.active = false';
      return $parts;
    }
    else if( 'refused consent' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'][] = 'participant.active = true';
      $parts['where'][] =
        'last_consent IN( "verbal deny", "written deny", "retract", "withdraw" )';
      return $parts;
    }
    else if( 'sourcing required' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'][] = 'participant.active = true';
      $parts['where'][] =
        '('.
        '  last_consent IS NULL'.
        '  OR last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      $parts['where'][] = 'phone_number_count = 0';

      return $parts;
    }
    else if( 'deceased' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant.status = "deceased"';
      return $parts;
    }
    else if( 'deaf' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant.status = "deaf"';
      return $parts;
    }
    else if( 'language barrier' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant.status = "language barrier"';
      return $parts;
    }
    else if( 'mentally unfit' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant.status = "mentally unfit"';
      return $parts;
    }
    else if( 'other' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant.status = "other"';
      return $parts;
    }
    else if( 'eligible' == $queue )
    {
      $parts = $this->get_query_parts( 'all' );
      // current_qnaire_id is the either the next qnaire to work on or the one in progress
      $parts['where'][] = 'current_qnaire_id IS NOT NULL';
      // active participant who does not have a "final" status and has at least one phone number
      $parts['where'][] = 'participant.active = true';
      $parts['where'][] = 'participant.status IS NULL';
      $parts['where'][] = 'phone_number_count > 0';
      $parts['where'][] =
        '('.
        '  last_consent IS NULL OR'.
        '  last_consent NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      return $parts;
    }
    else if( 'qnaire' == $queue )
    {
      $parts = $this->get_query_parts( 'eligible' );
      $parts['where'][] = 'participant.current_qnaire_id <QNAIRE_TEST>';
      return $parts;
    }
    else if( 'restricted' == $queue )
    {
      $parts = $this->get_query_parts( 'qnaire' );
      // make sure to only include participants who are restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = 'NOT '.$check_restriction_sql;
      return $parts;
    }
    else if( 'qnaire waiting' == $queue )
    {
      $parts = $this->get_query_parts( 'qnaire' );
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = ''.$check_restriction_sql;
      // the current qnaire cannot start before start_qnaire_date
      $parts['where'][] = 'participant.start_qnaire_date IS NOT NULL';
      $parts['where'][] = 'DATE( participant.start_qnaire_date ) > DATE( UTC_TIMESTAMP() )';
      return $parts;
    }
    else if( 'assigned' == $queue )
    {
      $parts = $this->get_query_parts( 'qnaire' );
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = ''.$check_restriction_sql;
      // assigned participants
      $parts['where'][] = 'participant.assigned = true';
      return $parts;
    }
    else if( 'not assigned' == $queue )
    {
      $parts = $this->get_query_parts( 'qnaire' );
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = ''.$check_restriction_sql;
      // the qnaire is ready to start if the start_qnaire_date is null or we have reached that date
      $parts['where'][] =
        '('.
        '  participant.start_qnaire_date IS NULL OR'.
        '  DATE( participant.start_qnaire_date ) <= DATE( UTC_TIMESTAMP() )'.
        ')';
      $parts['where'][] = 'participant.assigned = false';
      return $parts;
    }
    else if( 'appointment' == $queue )
    {
      $parts = $this->get_query_parts( 'not assigned' );
      // link to appointment table and make sure the appointment hasn't been assigned
      // (by design, there can only ever one unassigned appointment per participant)
      $parts['from'][] = 'appointment';
      $parts['where'][] = 'appointment.participant_id = participant.id';
      $parts['where'][] = 'appointment.assignment_id IS NULL';
      return $parts;
    }
    else if( 'upcoming appointment' == $queue )
    {
      $parts = $this->get_query_parts( 'appointment' );
      // appointment time (in UTC) is in the future
      $parts['where'][] =
        'UTC_TIMESTAMP() < appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE';
      return $parts;
    }
    else if( 'assignable appointment' == $queue )
    {
      $parts = $this->get_query_parts( 'appointment' );
      // appointment time (in UTC) is in the calling window
      $parts['where'][] =
        'UTC_TIMESTAMP() >= appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE';
      $parts['where'][] =
        'UTC_TIMESTAMP() <= appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE';
      return $parts;
    }
    else if( 'missed appointment' == $queue )
    {
      $parts = $this->get_query_parts( 'appointment' );
      // appointment time (in UTC) is in the past
      $parts['where'][] =
        'UTC_TIMESTAMP() > appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE';
      return $parts;
    }
    else if( 'no appointment' == $queue )
    {
      $parts = $this->get_query_parts( 'not assigned' );
      // make sure there is no unassigned appointment.  By design there can only be one of per
      // participant, so if the appointment is null then the participant has no pending
      // appointments.
      $parts['join'][] =
        'LEFT JOIN appointment '.
        'ON appointment.participant_id = participant.id '.
        'AND appointment.assignment_id IS NULL';
      $parts['where'][] = 'appointment.id IS NULL';
      return $parts;
    }
    else if( 'new participant' == $queue )
    {
      $parts = $this->get_query_parts( 'no appointment' );
      // If there is a start_qnaire_date then the current qnaire has never been started,
      // the exception is for participants who have never been assigned
      $parts['where'][] =
        '('.
        '  participant.start_qnaire_date IS NOT NULL OR'.
        '  participant.last_assignment_id IS NULL'.
        ')';
      return $parts;
    }
    else if( 'new participant always available' == $queue )
    {
      $parts = $this->get_query_parts( 'new participant' );
      // join to the participant's availability
      $parts['join'][] =
        'LEFT JOIN participant_available '.
        'ON participant_available.participant_id = participant.id';
      // make sure no availability exists
      $parts['where'][] = 'participant_available.available IS NULL';
      return $parts;
    }
    else if( 'new participant available' == $queue )
    {
      $parts = $this->get_query_parts( 'new participant' );
      // join to the participant's availability
      $parts['join'][] =
        'LEFT JOIN participant_available '.
        'ON participant_available.participant_id = participant.id';
      // make sure participant is available
      $parts['where'][] = 'participant_available.available = true';
      return $parts;
    }
    else if( 'new participant not available' == $queue )
    {
      $parts = $this->get_query_parts( 'new participant' );
      // join to the participant's availability
      $parts['join'][] =
        'LEFT JOIN participant_available '.
        'ON participant_available.participant_id = participant.id';
      // make sure participant is not available
      $parts['where'][] = 'participant_available.available = false';
      return $parts;
    }
    else if( 'old participant' == $queue )
    {
      $parts = $this->get_query_parts( 'no appointment' );
      // add the last phone call's information
      $parts['from'][] = 'phone_call';
      $parts['from'][] = 'assignment_last_phone_call';
      $parts['where'][] =
        'assignment_last_phone_call.assignment_id = participant.last_assignment_id';
      $parts['where'][] =
        'phone_call.id = assignment_last_phone_call.phone_call_id';
      // if there is no start_qnaire_date then the current qnaire has been started
      $parts['where'][] = 'participant.start_qnaire_date IS NULL';
      return $parts;
    }
    else
    {
      // make sure a phone call status has been included (all remaining queues require it)
      if( is_null( $phone_call_status ) )
        throw new exc\argument( 'phone_call_status', $phone_call_status, __METHOD__ );

      if( 'phone call status' == $queue )
      {
        $parts = $this->get_query_parts( 'old participant' );
        $parts['where'][] =
          sprintf( 'phone_call.status = "%s"', $phone_call_status );
        return $parts;
      }
      else if( 'phone call status waiting' == $queue )
      {
        $parts = $this->get_query_parts( 'phone call status', $phone_call_status );
        $parts['where'][] =
          sprintf( 'UTC_TIMESTAMP() < phone_call.end_datetime + '.
                                         'INTERVAL <CALLBACK_%s> MINUTE',
                   str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        return $parts;
      }
      else if( 'phone call status ready' == $queue )
      {
        $parts = $this->get_query_parts( 'phone call status', $phone_call_status );
        $parts['join'][] =
          'LEFT JOIN participant_available '.
          'ON participant_available.participant_id = participant.id';
        $parts['where'][] =
          sprintf( 'UTC_TIMESTAMP() >= phone_call.end_datetime + '.
                                          'INTERVAL <CALLBACK_%s> MINUTE',
                   str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        return $parts;
      }
      else if( 'phone call status always available' == $queue )
      {
        $parts = $this->get_query_parts( 'phone call status ready', $phone_call_status );
        $parts['where'][] = 'participant_available.available IS NULL';
        return $parts;
      }
      else if( 'phone call status not available' == $queue )
      {
        $parts = $this->get_query_parts( 'phone call status ready', $phone_call_status );
        $parts['where'][] = 'participant_available.available = false';
        return $parts;
      }
      else if( 'phone call status available' == $queue )
      {
        $parts = $this->get_query_parts( 'phone call status ready', $phone_call_status );
        $parts['where'][] = 'participant_available.available = true';
        return $parts;
      }
      else // invalid queue name
      {
        throw new exc\argument( 'queue', $queue, __METHOD__ );
      }
    }
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

    // fill in all callback timing settings
    $select_mod = new modifier();
    $select_mod->where( 'category', '=', 'callback timing' );
    foreach( setting::select( $select_mod ) as $db_setting )
    {
      $setting = $setting_manager->get_setting( 'callback timing', $db_setting->name );
      $template = sprintf( '<CALLBACK_%s>',
                           str_replace( ' ', '_', strtoupper( $db_setting->name ) ) );
      $sql = str_replace( $template, $setting, $sql );
    }
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
