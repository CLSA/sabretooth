<?php
/**
 * queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * queue: record
 */
class queue extends \cenozo\database\record
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
  }
  
  /**
   * Generates the query list.
   * 
   * This method is called internally by {@link get_participant_list} and
   * {@link get_participant_count} in order to generate the proper SQL to complete those
   * methods.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   * @static
   */
  protected static function generate_query_list()
  {
    // define the SQL for each queue
    $queue_list = array(
      'all',
      'finished',
      'ineligible',
      'inactive',
      'refused consent',
      'sourcing required' );

    // add the participant final status types
    $class_name = lib::get_class_name( 'database\participant' );
    $queue_list = array_merge( $queue_list, $class_name::get_enum_values( 'status' ) );
    
    // finish the queue list
    $queue_list = array_merge( $queue_list, array(
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
      'quota disabled',
      'new participant',
      'new participant outside calling time',
      'new participant within calling time',
      'new participant available',
      'new participant always available',
      'new participant not available',
      'old participant' ) );
     
    foreach( $queue_list as $queue )
    {
      $parts = self::get_query_parts( $queue );
      
      $from_sql = '';
      $first = true;
      // reverse order to make sure the join works
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
        sprintf( 'SELECT <SELECT_PARTICIPANT> %s %s %s',
                 $from_sql,
                 $join_sql,
                 $where_sql );
    }
    
    // now add the sql for each call back status
    $class_name = lib::get_class_name( 'database\phone_call' ); 
    foreach( $class_name::get_enum_values( 'status' ) as $phone_call_status )
    {
      // ignore statuses which result in deactivating phone numbers
      if( 'disconnected' != $phone_call_status && 'wrong number' != $phone_call_status )
      {
        $queue_list = array(
          'phone call status',
          'phone call status waiting',
          'phone call status ready',
          'phone call status outside calling time',
          'phone call status within calling time',
          'phone call status available',
          'phone call status always available',
          'phone call status not available' );

        foreach( $queue_list as $queue )
        {
          $parts = self::get_query_parts( $queue, $phone_call_status );
          
          $from_sql = '';
          $first = true;
          // reverse order to make sure the join works
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
            sprintf( 'SELECT <SELECT_PARTICIPANT> %s %s %s',
                     $from_sql,
                     $join_sql,
                     $where_sql );
        }
      }
    }
  }

  /**
   * Returns the number of participants currently in the queue.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier Modifications to the queue.
   * @param boolean $use_cache Whether to use the cached value (if one exists)
   * @return int
   * @access public
   */
  public function get_participant_count( $modifier = NULL, $use_cache = true )
  {
    $site_id = is_null( $this->db_site ) ? 0 : $this->db_site->id;
    $qnaire_id = !$this->qnaire_specific || is_null( $this->db_qnaire )
               ? 0 : $this->db_qnaire->id;
    if( $use_cache &&
        array_key_exists( $this->name, self::$participant_count_cache ) &&
        array_key_exists( $qnaire_id, self::$participant_count_cache[$this->name] ) &&
        array_key_exists( $site_id, self::$participant_count_cache[$this->name][$qnaire_id] ) )
      return self::$participant_count_cache[$this->name][$qnaire_id][$site_id];

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

    // restrict to the site
    if( !is_null( $this->db_site ) ) $modifier->where(
      'IFNULL( participant.site_id, primary_region.site_id )', '=', $this->db_site->id );
    
    if( !array_key_exists( $this->name, self::$participant_count_cache ) )
      self::$participant_count_cache[$this->name] = array();
    if( !array_key_exists( $qnaire_id, self::$participant_count_cache[$this->name] ) )
      self::$participant_count_cache[$this->name][$qnaire_id] = array();
    $db_parent = $this->parent_queue_id ? new static( $this->parent_queue_id ) : NULL;
    $parent = is_null( $db_parent ) ? 'NULL' : $db_parent->name;
    self::$participant_count_cache[$this->name][$qnaire_id][$site_id] =
      (integer) static::db()->get_one( sprintf( '%s %s',
        $this->get_sql( 'COUNT( DISTINCT participant.id )' ),
        $modifier->get_sql( true ) ) );

    // if the value is 0 then update all child counts with 0 to save processing time
    if( 0 == self::$participant_count_cache[$this->name][$qnaire_id][$site_id] )
      static::set_child_count_cache_to_zero( $this, $qnaire_id, $site_id );

    return self::$participant_count_cache[$this->name][$qnaire_id][$site_id];
  }

  /**
   * A recursive method to set the count cache for all child queues to 0.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\queue $db_queue
   * @param int $qnaire_id The qnaire id being processed.
   * @param int $site_id The site id being processed.
   * @static
   * @access private
   */
  private static function set_child_count_cache_to_zero( $db_queue, $qnaire_id, $site_id )
  {
    $queue_mod = lib::create( 'database\modifier' );
    $queue_mod->where( 'parent_queue_id', '=', $db_queue->id );
    foreach( static::select( $queue_mod ) as $db_child_queue )
    {
      if( !array_key_exists( $db_child_queue->name, self::$participant_count_cache ) )
        self::$participant_count_cache[$db_child_queue->name] = array();
      if( !array_key_exists( $qnaire_id, self::$participant_count_cache[$db_child_queue->name] ) )
        self::$participant_count_cache[$db_child_queue->name][$qnaire_id] = array();
      self::$participant_count_cache[$db_child_queue->name][$qnaire_id][$site_id] = 0;
      self::set_child_count_cache_to_zero( $db_child_queue, $qnaire_id, $site_id );
    }
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
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

    // restrict to the site
    if( !is_null( $this->db_site ) ) $modifier->where(
      'IFNULL( participant.site_id, primary_region.site_id )', '=', $this->db_site->id );

    $participant_ids = static::db()->get_col(
      sprintf( '%s %s',
               $this->get_sql( 'DISTINCT participant.id' ),
               $modifier->get_sql( true ) ) );

    $participants = array();
    foreach( $participant_ids as $id ) $participants[] = lib::create( 'database\participant', $id );
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
   * @throws exception\argument
   * @access protected
   * @static
   */
  protected static function get_query_parts( $queue, $phone_call_status = NULL )
  {
    // determine what date/time to view the queues
    if( is_null( self::$viewing_date ) )
    {
      $viewing_date = 'UTC_TIMESTAMP()';
      $check_time = true;
    }
    else
    {
      // put double quotes around the date since it is being inserted into sql below
      $viewing_date = sprintf( '"%s"', self::$viewing_date );
      $check_time = false;
    }

    $class_name = lib::get_class_name( 'database\participant' );
    $participant_status_list = $class_name::get_enum_values( 'status' );

    $phone_count = 
      '( '.
      '  SELECT COUNT( DISTINCT phone.id ) '.
      '  FROM phone '.
      '  WHERE phone.participant_id = participant.id '.
      '  AND phone.active '.
      '  AND phone.number IS NOT NULL '.
      ')';

    // first a list of commonly used elements
    $status_where_list = array(
      'participant.active = true',
      '('.
      '  consent.event IS NULL'.
      '  OR consent.event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
      ')',
      $phone_count.' > 0' );
    
    // join to the quota table based on region, gender and age group
    $quota_join = 
      'LEFT JOIN quota '.
      'ON quota.region_id = primary_region.id '.
      'AND quota.gender = participant.gender '.
      'AND quota.age_group_id = participant.age_group_id';
    
    // join to the queue_restriction table based on site, city, region or postcode
    $restriction_join = 
      'LEFT JOIN queue_restriction '.
      'ON queue_restriction.site_id = IFNULL( participant.site_id, primary_region.site_id ) '.
      'OR queue_restriction.city = first_address.city '.
      'OR queue_restriction.region_id = first_address.region_id '.
      'OR queue_restriction.postcode = first_address.postcode';

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
      '    queue_restriction.site_id != IFNULL( participant.site_id, primary_region.site_id )'.
      '  )'.
      // tests to see if the city is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.city IS NOT NULL AND'.
      '    queue_restriction.city != first_address.city'.
      '  )'.
      // tests to see if the region is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.region_id IS NOT NULL AND'.
      '    queue_restriction.region_id != first_address.region_id'.
      '  )'.
      // tests to see if the postcode is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.postcode IS NOT NULL AND'.
      '    queue_restriction.postcode != first_address.postcode'.
      '  )'.
      ')';
    
    // checks a participant's availability
    $check_availability_sql = sprintf(
      '( SELECT MAX( '.
      '    CASE DAYOFWEEK( %s ) '.
      '      WHEN 1 THEN availability.sunday '.
      '      WHEN 2 THEN availability.monday '.
      '      WHEN 3 THEN availability.tuesday '.
      '      WHEN 4 THEN availability.wednesday '.
      '      WHEN 5 THEN availability.thursday '.
      '      WHEN 6 THEN availability.friday '.
      '      WHEN 7 THEN availability.saturday '.
      '      ELSE 0 END ',
      $viewing_date );

    if( $check_time )
    {
      $check_availability_sql .= sprintf(
        '* IF( IF( TIME( %s ) < availability.start_time, '.
        '        24*60*60 + TIME_TO_SEC( TIME( %s ) ), '.
        '        TIME_TO_SEC( TIME( %s ) ) ) >= '.
        '    TIME_TO_SEC( availability.start_time ), 1, 0 ) '.
        '* IF( IF( TIME( %s ) < availability.start_time, '.
        '        24*60*60 + TIME_TO_SEC( TIME( %s ) ), '.
        '        TIME_TO_SEC( TIME( %s ) ) ) < '.
        '    IF( availability.end_time < availability.start_time, '.
        '        24*60*60 + TIME_TO_SEC( availability.end_time ), '.
        '        TIME_TO_SEC( availability.end_time ) ), 1, 0 ) ',
        $viewing_date,
        $viewing_date,
        $viewing_date,
        $viewing_date,
        $viewing_date,
        $viewing_date );
    }
    
    // finish the check availability sql
    $check_availability_sql .=
      ') '.
      'FROM availability '.
      'WHERE availability.participant_id = participant.id )';

    $current_qnaire_id =
      '( '.
      '  IF '.
      '  ( '.
      '    current_interview.id IS NULL, '.
      '    ( SELECT id FROM qnaire WHERE rank = 1 ), '.
      '    IF( current_interview.completed, next_qnaire.id, current_qnaire.id ) '.
      '  ) '.
      ')';

    $start_qnaire_date =
      '( '.
      '  IF '.
      '  ( '.
      '    current_interview.id IS NULL, '.
      '    IF '.
      '    ( '.
      '      participant.prior_contact_date IS NULL, '.
      '      NULL, '.
      '      participant.prior_contact_date + INTERVAL '.
      '      ( SELECT delay FROM qnaire WHERE rank = 1 ) WEEK '.
      '    ), '.
      '    IF '.
      '    ( '.
      '      current_interview.completed, '.
      '      IF '.
      '      ( '.
      '        next_qnaire.id IS NULL, '.
      '        NULL, '.
      '        IF '.
      '        ( '.
      '          next_prev_assignment.end_datetime IS NULL, '.
      '          participant.prior_contact_date, '.
      '          next_prev_assignment.end_datetime '.
      '        ) + INTERVAL next_qnaire.delay WEEK '.
      '      ), '.
      '      NULL '.
      '    ) '.
      '  ) '.
      ')';

    // checks to make sure a participant is within calling time hours
    if( $check_time )
    {
      $localtime = localtime( time(), true );
      $offset = $localtime['tm_isdst']
              ? 'first_address.timezone_offset + first_address.daylight_savings'
              : 'first_address.timezone_offset';
      $calling_time_sql = sprintf(
        '('.
        '  TIME( %s + INTERVAL %s HOUR ) >= "<CALLING_START_TIME>" AND'.
        '  TIME( %s + INTERVAL %s HOUR ) < "<CALLING_END_TIME>"'.
        ')',
        $viewing_date,
        $offset,
        $viewing_date,
        $offset );
    }

    // now determine the sql parts for the given queue
    if( 'all' == $queue )
    {
      // NOTE: when updating this query database\participant::get_queue_data()
      //       should also be updated as it performs a very similar query
      $parts = array(
        'from' => array( 'participant' ),
        'join' => array(
          'LEFT JOIN participant_primary_address '.
          'ON participant.id = participant_primary_address.participant_id',
          'LEFT JOIN address AS primary_address '.
          'ON participant_primary_address.address_id = primary_address.id',
          'LEFT JOIN region AS primary_region '.
          'ON primary_address.region_id = primary_region.id',
          'LEFT JOIN participant_first_address '.
          'ON participant.id = participant_first_address.participant_id',
          'LEFT JOIN address AS first_address '.
          'ON participant_first_address.address_id = first_address.id',
          'LEFT JOIN participant_last_consent '.
          'ON participant.id = participant_last_consent.participant_id',
          'LEFT JOIN consent '.
          'ON consent.id = participant_last_consent.consent_id',
          'LEFT JOIN interview AS current_interview '.
          'ON current_interview.participant_id = participant.id',
          'LEFT JOIN interview_last_assignment '.
          'ON current_interview.id = interview_last_assignment.interview_id '.
          'LEFT JOIN assignment '.
          'ON interview_last_assignment.assignment_id = assignment.id',
          'LEFT JOIN qnaire AS current_qnaire '.
          'ON current_qnaire.id = current_interview.qnaire_id',
          'LEFT JOIN qnaire AS next_qnaire '.
          'ON next_qnaire.rank = ( current_qnaire.rank + 1 )',
          'LEFT JOIN qnaire AS next_prev_qnaire '.
          'ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id',
          'LEFT JOIN interview AS next_prev_interview '.
          'ON next_prev_interview.qnaire_id = next_prev_qnaire.id '.
          'AND next_prev_interview.participant_id = participant.id',
          'LEFT JOIN assignment next_prev_assignment '.
          'ON next_prev_assignment.interview_id = next_prev_interview.id' ),
        'where' => array(
          '( '.
          '  current_qnaire.rank IS NULL OR '.
          '  current_qnaire.rank = '.
          '  ( '.
          '    SELECT MAX( qnaire.rank ) '.
          '    FROM interview, qnaire '.
          '    WHERE qnaire.id = interview.qnaire_id '.
          '    AND current_interview.participant_id = interview.participant_id '.
          '    GROUP BY current_interview.participant_id '.
          '  ) '.
          ')',
          '( '.
          '  next_prev_assignment.end_datetime IS NULL OR '.
          '  next_prev_assignment.end_datetime = '.
          '  ( '.
          '    SELECT MAX( assignment.end_datetime ) '.
          '    FROM interview, assignment '.
          '    WHERE interview.qnaire_id = next_prev_qnaire.id '.
          '    AND interview.id = assignment.interview_id '.
          '    AND next_prev_assignment.id = assignment.id '.
          '    GROUP BY next_prev_assignment.interview_id '.
          '  ) '.
          ') ' ) );
      return $parts;
    }
    else if( 'finished' == $queue )
    {
      $parts = self::get_query_parts( 'all' );
      // no current_qnaire_id means no qnaires left to complete
      $parts['where'][] = $current_qnaire_id.' IS NULL';
      return $parts;
    }
    else if( 'ineligible' == $queue )
    {
      $parts = self::get_query_parts( 'all' );
      // ineligible means either inactive or with a "final" status
      $parts['where'][] =
        '('.
        '  participant.active = false'.
        '  OR participant.status IS NOT NULL'.
        '  OR '.$phone_count.' = 0'.
        '  OR consent.event IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      return $parts;
    }
    else if( 'inactive' == $queue )
    {
      $parts = self::get_query_parts( 'all' );
      $parts['where'][] = 'participant.active = false';
      return $parts;
    }
    else if( 'refused consent' == $queue )
    {
      $parts = self::get_query_parts( 'all' );
      $parts['where'][] = 'participant.active = true';
      $parts['where'][] =
        'consent.event IN( "verbal deny", "written deny", "retract", "withdraw" )';
      return $parts;
    }
    else if( 'sourcing required' == $queue )
    {
      $parts = self::get_query_parts( 'all' );
      $parts['where'][] = 'participant.active = true';
      $parts['where'][] =
        '('.
        '  consent.event IS NULL'.
        '  OR consent.event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      $parts['where'][] = $phone_count.' = 0';

      return $parts;
    }
    else if( in_array( $queue, $participant_status_list ) )
    {
      $parts = self::get_query_parts( 'all' );
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant.status = "'.$queue.'"'; // queue name is same as status name
      return $parts;
    }
    else if( 'eligible' == $queue )
    {
      $parts = self::get_query_parts( 'all' );
      // current_qnaire_id is the either the next qnaire to work on or the one in progress
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      // active participant who does not have a "final" status and has at least one phone number
      $parts['where'][] = 'participant.active = true';
      $parts['where'][] = 'participant.status IS NULL';
      $parts['where'][] = $phone_count.' > 0';
      $parts['where'][] =
        '('.
        '  consent.event IS NULL OR'.
        '  consent.event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      return $parts;
    }
    else if( 'qnaire' == $queue )
    {
      $parts = self::get_query_parts( 'eligible' );
      $parts['where'][] = $current_qnaire_id.' <QNAIRE_TEST>';
      return $parts;
    }
    else if( 'restricted' == $queue )
    {
      $parts = self::get_query_parts( 'qnaire' );
      // make sure to only include participants who are restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = 'NOT '.$check_restriction_sql;
      return $parts;
    }
    else if( 'qnaire waiting' == $queue )
    {
      $parts = self::get_query_parts( 'qnaire' );
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = $check_restriction_sql;
      // the current qnaire cannot start before start_qnaire_date
      $parts['where'][] = $start_qnaire_date.' IS NOT NULL';
      $parts['where'][] = sprintf( 'DATE( '.$start_qnaire_date.' ) > DATE( %s )',
                                   $viewing_date );
      return $parts;
    }
    else if( 'assigned' == $queue )
    {
      $parts = self::get_query_parts( 'qnaire' );
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = $check_restriction_sql;
      // assigned participants
      $parts['where'][] = '( assignment.id IS NOT NULL AND assignment.end_datetime IS NULL )';
      return $parts;
    }
    else if( 'not assigned' == $queue )
    {
      $parts = self::get_query_parts( 'qnaire' );
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = $check_restriction_sql;
      // the qnaire is ready to start if the start_qnaire_date is null or we have reached that date
      $parts['where'][] = sprintf(
        '('.
        '  '.$start_qnaire_date.' IS NULL OR'.
        '  DATE( '.$start_qnaire_date.' ) <= DATE( %s )'.
        ')',
        $viewing_date );
      $parts['where'][] = '( assignment.id IS NULL OR assignment.end_datetime IS NOT NULL )';
      return $parts;
    }
    else if( 'appointment' == $queue )
    {
      $parts = self::get_query_parts( 'not assigned' );
      // link to appointment table and make sure the appointment hasn't been assigned
      // (by design, there can only ever one unassigned appointment per participant)
      $parts['from'][] = 'appointment';
      $parts['where'][] = 'appointment.participant_id = participant.id';
      $parts['where'][] = 'appointment.assignment_id IS NULL';
      return $parts;
    }
    else if( 'upcoming appointment' == $queue )
    {
      $parts = self::get_query_parts( 'appointment' );
      // appointment time (in UTC) is in the future
      $parts['where'][] = sprintf(
        $check_time ? '%s < appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE'
                    : 'DATE( %s ) < DATE( appointment.datetime )',
        $viewing_date );
      return $parts;
    }
    else if( 'assignable appointment' == $queue )
    {
      $parts = self::get_query_parts( 'appointment' );
      // appointment time (in UTC) is in the calling window
      $parts['where'][] = sprintf(
        $check_time ? '%s >= appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE AND '.
                      '%s <= appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE'
                    : 'DATE( %s ) = DATE( appointment.datetime )',
        $viewing_date,
        $viewing_date );
      return $parts;
    }
    else if( 'missed appointment' == $queue )
    {
      $parts = self::get_query_parts( 'appointment' );
      // appointment time (in UTC) is in the past
      $parts['where'][] = sprintf(
        $check_time ? '%s > appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE'
                    : 'DATE( %s ) > DATE( appointment.datetime )',
        $viewing_date );
      return $parts;
    }
    else if( 'no appointment' == $queue )
    {
      $parts = self::get_query_parts( 'not assigned' );
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
    else if( 'quota disabled' == $queue )
    {
      $parts = self::get_query_parts( 'no appointment' );
      // who belong to a quota which is disabled
      $parts['join'][] = $quota_join;
      $parts['where'][] = 'quota.disabled = true';
      return $parts;
    }
    else if( 'new participant' == $queue )
    {
      $parts = self::get_query_parts( 'no appointment' );
      // who belong to a quota which is not disabled or doesn't exist
      $parts['join'][] = $quota_join;
      $parts['where'][] = '( quota.disabled IS NULL OR quota.disabled = false )';
      // If there is a start_qnaire_date then the current qnaire has never been started,
      // the exception is for participants who have never been assigned
      $parts['where'][] =
        '('.
        '  '.$start_qnaire_date.' IS NOT NULL OR'.
        '  assignment.id IS NULL'.
        ')';
      return $parts;
    }
    else if( 'new participant outside calling time' == $queue )
    {
      $parts = self::get_query_parts( 'new participant' );
      $parts['where'][] = $check_time
                        ? 'NOT '.$calling_time_sql
                        : 'NOT true'; // purposefully a negative tautology
      return $parts;
    }
    else if( 'new participant within calling time' == $queue )
    {
      $parts = self::get_query_parts( 'new participant' );
      $parts['where'][] = $check_time
                        ? $calling_time_sql
                        : 'true'; // purposefully a tautology
      return $parts;
    }
    else if( 'new participant available' == $queue )
    {
      $parts = self::get_query_parts( 'new participant within calling time' );
      // make sure the participant has availability and is currently available
      $parts['where'][] = $check_availability_sql.' = true';
      return $parts;
    }
    else if( 'new participant always available' == $queue )
    {
      $parts = self::get_query_parts( 'new participant within calling time' );
      // make sure the participant doesn't specify availability
      $parts['where'][] = $check_availability_sql.' IS NULL';
      return $parts;
    }
    else if( 'new participant not available' == $queue )
    {
      $parts = self::get_query_parts( 'new participant within calling time' );
      // make sure the participant has availability and is currently not available
      $parts['where'][] = $check_availability_sql.' = false';
      return $parts;
    }
    else if( 'old participant' == $queue )
    {
      $parts = self::get_query_parts( 'no appointment' );
      // who belong to a quota which is not disabled or doesn't exist
      $parts['join'][] = $quota_join;
      $parts['where'][] = '( quota.disabled IS NULL OR quota.disabled = false )';
      // add the last phone call's information
      $parts['from'][] = 'phone_call';
      $parts['from'][] = 'assignment_last_phone_call';
      $parts['where'][] =
        'assignment_last_phone_call.assignment_id = assignment.id';
      $parts['where'][] =
        'phone_call.id = assignment_last_phone_call.phone_call_id';
      // if there is no start_qnaire_date then the current qnaire has been started
      $parts['where'][] = $start_qnaire_date.' IS NULL';
      return $parts;
    }
    else
    {
      // make sure a phone call status has been included (all remaining queues require it)
      if( is_null( $phone_call_status ) )
        throw lib::create( 'exception\argument',
          'phone_call_status', $phone_call_status, __METHOD__ );

      if( 'phone call status' == $queue )
      {
        $parts = self::get_query_parts( 'old participant' );
        $parts['where'][] =
          sprintf( 'phone_call.status = "%s"', $phone_call_status );
        return $parts;
      }
      else if( 'phone call status waiting' == $queue )
      {
        $parts = self::get_query_parts(
          'phone call status', $phone_call_status );
        $parts['where'][] = sprintf(
          $check_time ? '%s < phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE' :
                        'DATE( %s ) < '.
                        'DATE( phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE )',
          $viewing_date,
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        return $parts;
      }
      else if( 'phone call status ready' == $queue )
      {
        $parts = self::get_query_parts(
          'phone call status', $phone_call_status );
        $parts['where'][] = sprintf(
          $check_time ? '%s >= phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE' :
                        'DATE( %s ) >= '.
                        'DATE( phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE )',
          $viewing_date,
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        return $parts;
      }
      else if( 'phone call status outside calling time' == $queue )
      {
        $parts = self::get_query_parts( 'phone call status ready', $phone_call_status );
        $parts['where'][] = $check_time
                          ? 'NOT '.$calling_time_sql
                          : 'NOT true'; // purposefully a negative tautology
        return $parts;
      }
      else if( 'phone call status within calling time' == $queue )
      {
        $parts = self::get_query_parts( 'phone call status ready', $phone_call_status );
        $parts['where'][] = $check_time
                          ? $calling_time_sql
                          : 'true'; // purposefully a tautology
        return $parts;
      }
      else if( 'phone call status available' == $queue )
      {
        $parts = self::get_query_parts(
          'phone call status within calling time', $phone_call_status );
        // make sure the participant has availability and is currently available
        $parts['where'][] = $check_availability_sql.' = true';
        return $parts;
      }
      else if( 'phone call status always available' == $queue )
      {
        $parts = self::get_query_parts(
          'phone call status within calling time', $phone_call_status );
        // make sure the participant doesn't specify availability
        $parts['where'][] = $check_availability_sql.' IS NULL';
        return $parts;
      }
      else if( 'phone call status not available' == $queue )
      {
        $parts = self::get_query_parts(
          'phone call status within calling time', $phone_call_status );
        // make sure the participant has availability and is currently not available
        $parts['where'][] = $check_availability_sql.' = false';
        return $parts;
      }
      else // invalid queue name
      {
        throw lib::create( 'exception\argument', 'queue', $queue, __METHOD__ );
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
    // start by making sure the query list has been generated
    if( 0 == count( self::$query_list ) ) self::generate_query_list();

    $sql = self::$query_list[ $this->name ];
    $sql = preg_replace( '/\<SELECT_PARTICIPANT\>/', $select_participant_sql, $sql, 1 );
    $sql = str_replace( '<SELECT_PARTICIPANT>', 'participant.id', $sql );
    $qnaire_test_sql = is_null( $this->db_qnaire ) ? 'IS NOT NULL' : '= '.$this->db_qnaire->id;
    $sql = str_replace( '<QNAIRE_TEST>', $qnaire_test_sql, $sql );

    // fill in the settings
    $setting_manager = lib::create( 'business\setting_manager' );
    $setting = $setting_manager->get_setting( 'appointment', 'call pre-window', $this->db_site );
    $sql = str_replace( '<APPOINTMENT_PRE_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'appointment', 'call post-window', $this->db_site );
    $sql = str_replace( '<APPOINTMENT_POST_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'calling', 'start time', $this->db_site );
    $sql = str_replace( '<CALLING_START_TIME>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'calling', 'end time', $this->db_site );
    $sql = str_replace( '<CALLING_END_TIME>', $setting, $sql );

    // fill in all callback timing settings
    $setting_mod = lib::create( 'database\modifier' ); 
    $setting_mod->where( 'category', '=', 'callback timing' );
    $class_name = lib::get_class_name( 'database\setting' );
    foreach( $class_name::select( $setting_mod ) as $db_setting )
    {
      $setting = $setting_manager->get_setting(
        'callback timing', $db_setting->name, $this->db_site );
      $template = sprintf( '<CALLBACK_%s>',
                           str_replace( ' ', '_', strtoupper( $db_setting->name ) ) );
      $sql = str_replace( $template, $setting, $sql );
    }
    return $sql;
  }
  
  /**
   * The date (YYYY-MM-DD) with respect to check all queue states.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $date
   * @access public
   * @static
   */
  public static function set_viewing_date( $date = NULL )
  {
    // validate the input
    $datetime_obj = util::get_datetime_object( $date );
    if( $date != $datetime_obj->format( 'Y-m-d' ) )
      log::err( 'The selected viewing date ('.$date.') may not be valid.' );
    
    self::$viewing_date = $datetime_obj->format( 'Y-m-d' );
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
   * The date (YYYY-MM-DD) with respect to check all queue states.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var string
   * @static
   */
  protected static $viewing_date = NULL;

  /**
   * Whether or not calling times are enabled.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var boolean
   * @static
   */
  protected static $calling_times_enabled = NULL;

  /**
   * The queries for each queue
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var associative array of strings
   * @static
   */
  protected static $query_list = array();

  /**
   * A cache of participant counts for each queue and each qnaire
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var associative array of integers
   * @static
   */
  protected static $participant_count_cache = array();
}
?>
