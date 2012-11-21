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
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' ); 

    // define the SQL for each queue
    $queue_list = array(
      'all',
      'finished',
      'ineligible',
      'inactive',
      'refused consent',
      'sourcing required' );

    // add the participant final status types
    $queue_list = array_merge( $queue_list, $participant_class_name::get_enum_values( 'status' ) );
    
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
      'callback',
      'upcoming callback',
      'assignable callback',
      'no appointment',
      'quota disabled',
      'outside calling time',
      'new participant',
      'new participant available',
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
    
    // now add the sql for each call back status, grouping machine message, machine no message,
    // not reached, disconnected and wrong number into a single "not reached" category
    $phone_call_status_list = $phone_call_class_name::get_enum_values( 'status' );
    $remove_list = array(
      'machine message',
      'machine no message',
      'disconnected',
      'wrong number' );
    $phone_call_status_list = array_diff( $phone_call_status_list, $remove_list );
    foreach( $phone_call_class_name::get_enum_values( 'status' ) as $phone_call_status )
    {
      $queue_list = array(
        'phone call status',
        'phone call status waiting',
        'phone call status available',
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
    // make sure the temporary table exists
    static::create_participant_for_queue();

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
      'IFNULL( participant_site_id, primary_region_site_id )', '=', $this->db_site->id );
    
    if( !array_key_exists( $this->name, self::$participant_count_cache ) )
      self::$participant_count_cache[$this->name] = array();
    if( !array_key_exists( $qnaire_id, self::$participant_count_cache[$this->name] ) )
      self::$participant_count_cache[$this->name][$qnaire_id] = array();
    $db_parent = $this->parent_queue_id ? new static( $this->parent_queue_id ) : NULL;
    $parent = is_null( $db_parent ) ? 'NULL' : $db_parent->name;

    if( 'callback' == $this->name )
      log::debug( sprintf( '%s %s',
        $this->get_sql( 'COUNT( DISTINCT participant_for_queue.id )' ),
        $modifier->get_sql( true ) ) );
    self::$participant_count_cache[$this->name][$qnaire_id][$site_id] =
      (integer) static::db()->get_one( sprintf( '%s %s',
        $this->get_sql( 'COUNT( DISTINCT participant_for_queue.id )' ),
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
    // make sure the temporary table exists
    static::create_participant_for_queue();

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );

    // restrict to the site
    if( !is_null( $this->db_site ) ) $modifier->where(
      'IFNULL( participant_site_id, primary_region_site_id )', '=', $this->db_site->id );

    $participant_ids = static::db()->get_col(
      sprintf( '%s %s',
               $this->get_sql( 'DISTINCT participant_for_queue.id' ),
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
   * Get whether this queue is related to a callback
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function from_callback()
  {
    return in_array( $this->name, array( 'callback',
                                         'upcoming callback',
                                         'assignable callback' ) );
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

    $participant_class_name = lib::get_class_name( 'database\participant' );
    $participant_status_list = $participant_class_name::get_enum_values( 'status' );

    $phone_count = 
      '( '.
      '  SELECT COUNT( DISTINCT phone.id ) '.
      '  FROM phone '.
      '  WHERE phone.participant_id = participant_for_queue.id '.
      '  AND phone.active '.
      '  AND phone.number IS NOT NULL '.
      ')';

    // first a list of commonly used elements
    $status_where_list = array(
      'participant_active = true',
      '('.
      '  last_consent_event IS NULL'.
      '  OR last_consent_event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
      ')',
      $phone_count.' > 0' );
    
    // join to the quota table based on region, gender and age group
    $quota_join = 
      'LEFT JOIN quota '.
      'ON quota.region_id = primary_region_id '.
      'AND quota.gender = participant_gender '.
      'AND quota.age_group_id = participant_age_group_id';
    
    // join to the queue_restriction table based on site, city, region or postcode
    $restriction_join = 
      'LEFT JOIN queue_restriction '.
      'ON queue_restriction.site_id = IFNULL( participant_site_id, primary_region_site_id ) '.
      'OR queue_restriction.city = first_address_city '.
      'OR queue_restriction.region_id = first_address_region_id '.
      'OR queue_restriction.postcode = first_address_postcode';

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
      '    queue_restriction.site_id != IFNULL( participant_site_id, primary_region_site_id )'.
      '  )'.
      // tests to see if the city is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.city IS NOT NULL AND'.
      '    queue_restriction.city != first_address_city'.
      '  )'.
      // tests to see if the region is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.region_id IS NOT NULL AND'.
      '    queue_restriction.region_id != first_address_region_id'.
      '  )'.
      // tests to see if the postcode is being restricted but the participant isn't included
      '  OR ('.
      '    queue_restriction.postcode IS NOT NULL AND'.
      '    queue_restriction.postcode != first_address_postcode'.
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
      'WHERE availability.participant_id = participant_for_queue.id )';

    $current_qnaire_id =
      '( '.
      '  IF '.
      '  ( '.
      '    current_interview_id IS NULL, '.
      '    ( SELECT id FROM qnaire WHERE rank = 1 ), '.
      '    IF( current_interview_completed, next_qnaire_id, current_qnaire_id ) '.
      '  ) '.
      ')';

    $start_qnaire_date =
      '( '.
      '  IF '.
      '  ( '.
      '    current_interview_id IS NULL, '.
      '    IF '.
      '    ( '.
      '      participant_prior_contact_date IS NULL, '.
      '      NULL, '.
      '      participant_prior_contact_date + INTERVAL '.
      '      ( SELECT delay FROM qnaire WHERE rank = 1 ) WEEK '.
      '    ), '.
      '    IF '.
      '    ( '.
      '      current_interview_completed, '.
      '      IF '.
      '      ( '.
      '        next_qnaire_id IS NULL, '.
      '        NULL, '.
      '        IF '.
      '        ( '.
      '          next_prev_assignment_end_datetime IS NULL, '.
      '          participant_prior_contact_date, '.
      '          next_prev_assignment_end_datetime '.
      '        ) + INTERVAL next_qnaire_delay WEEK '.
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
              ? 'first_address_timezone_offset + first_address_daylight_savings'
              : 'first_address_timezone_offset';
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

    // get the parent queue's query parts
    if( is_null( $phone_call_status ) )
    {
      $db_queue = static::get_unique_record( 'name', $queue );
      if( is_null( $db_queue ) ) // invalid queue name
        throw lib::create( 'exception\runtime',
          sprintf( 'Cannot find queue named "%s"', $queue ), __METHOD__ );
      if( !is_null( $db_queue->parent_queue_id ) )
      {
        $db_parent = new static( $db_queue->parent_queue_id );
        $parts = self::get_query_parts( $db_parent->name );
      }
    }
    else if( 'phone call status' == $queue )
    {
      $parts = self::get_query_parts( 'old participant' );
    }
    else
    {
      $parts = self::get_query_parts( 'phone call status', $phone_call_status );
    }

    // now determine the sql parts for the given queue
    if( 'all' == $queue )
    {
      // NOTE: when updating this query database\participant::get_queue_data()
      //       should also be updated as it performs a very similar query
      $parts = array(
        'from' => array( 'participant_for_queue' ),
        'join' => array(),
        'where' => array() );
      return $parts;
    }
    else if( 'finished' == $queue )
    {
      // no current_qnaire_id means no qnaires left to complete
      $parts['where'][] = $current_qnaire_id.' IS NULL';
      return $parts;
    }
    else if( 'ineligible' == $queue )
    {
      // current_qnaire_id is the either the next qnaire to work on or the one in progress
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      // ineligible means either inactive or with a "final" status
      $parts['where'][] =
        '('.
        '  participant_active = false'.
        '  OR participant_status IS NOT NULL'.
        '  OR '.$phone_count.' = 0'.
        '  OR last_consent_event IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      return $parts;
    }
    else if( 'inactive' == $queue )
    {
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      $parts['where'][] = 'participant_active = false';
      return $parts;
    }
    else if( 'refused consent' == $queue )
    {
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      $parts['where'][] = 'participant_active = true';
      $parts['where'][] =
        'last_consent_event IN( "verbal deny", "written deny", "retract", "withdraw" )';
      return $parts;
    }
    else if( 'sourcing required' == $queue )
    {
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      $parts['where'][] = 'participant_active = true';
      $parts['where'][] =
        '('.
        '  last_consent_event IS NULL'.
        '  OR last_consent_event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      $parts['where'][] = $phone_count.' = 0';

      return $parts;
    }
    else if( in_array( $queue, $participant_status_list ) )
    {
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      $parts['where'][] = 'participant_active = true';
      $parts['where'][] =
        '('.
        '  last_consent_event IS NULL'.
        '  OR last_consent_event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      $parts['where'][] = $phone_count.' > 0';
      $parts['where'] = array_merge( $parts['where'], $status_where_list );
      $parts['where'][] = 'participant_status = "'.$queue.'"'; // queue name is same as status name
      return $parts;
    }
    else if( 'eligible' == $queue )
    {
      // current_qnaire_id is the either the next qnaire to work on or the one in progress
      $parts['where'][] = $current_qnaire_id.' IS NOT NULL';
      // active participant who does not have a "final" status and has at least one phone number
      $parts['where'][] = 'participant_active = true';
      $parts['where'][] = 'participant_status IS NULL';
      $parts['where'][] = $phone_count.' > 0';
      $parts['where'][] =
        '('.
        '  last_consent_event IS NULL OR'.
        '  last_consent_event NOT IN( "verbal deny", "written deny", "retract", "withdraw" )'.
        ')';
      return $parts;
    }
    else if( 'qnaire' == $queue )
    {
      $parts['where'][] = $current_qnaire_id.' <QNAIRE_TEST>';
      return $parts;
    }
    else if( 'restricted' == $queue )
    {
      // make sure to only include participants who are restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = 'NOT '.$check_restriction_sql;
      return $parts;
    }
    else if( 'qnaire waiting' == $queue )
    {
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
      // make sure to only include participants who are not restricted
      $parts['join'][] = $restriction_join;
      $parts['where'][] = $check_restriction_sql;
      // assigned participants
      $parts['where'][] = '( last_assignment_id IS NOT NULL AND last_assignment_end_datetime IS NULL )';
      return $parts;
    }
    else if( 'not assigned' == $queue )
    {
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
      $parts['where'][] = '( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL )';
      return $parts;
    }
    else if( 'appointment' == $queue )
    {
      // link to appointment table and make sure the appointment hasn't been assigned
      // (by design, there can only ever one unassigned appointment per participant)
      $parts['from'][] = 'appointment';
      $parts['where'][] = 'appointment.participant_id = participant_for_queue.id';
      $parts['where'][] = 'appointment.assignment_id IS NULL';
      return $parts;
    }
    else if( 'upcoming appointment' == $queue )
    {
      // appointment time (in UTC) is in the future
      $parts['where'][] = sprintf(
        $check_time ? '%s < appointment.datetime - INTERVAL <APPOINTMENT_PRE_WINDOW> MINUTE'
                    : 'DATE( %s ) < DATE( appointment.datetime )',
        $viewing_date );
      return $parts;
    }
    else if( 'assignable appointment' == $queue )
    {
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
      // appointment time (in UTC) is in the past
      $parts['where'][] = sprintf(
        $check_time ? '%s > appointment.datetime + INTERVAL <APPOINTMENT_POST_WINDOW> MINUTE'
                    : 'DATE( %s ) > DATE( appointment.datetime )',
        $viewing_date );
      return $parts;
    }
    else if( 'callback' == $queue )
    {
      // link to callback table and make sure the callback hasn't been assigned
      // (by design, there can only ever one unassigned callback per participant)
      $parts['from'][] = 'callback';
      $parts['where'][] = 'callback.participant_id = participant_for_queue.id';
      $parts['where'][] = 'callback.assignment_id IS NULL';
      return $parts;
    }
    else if( 'upcoming callback' == $queue )
    {
      // callback time (in UTC) is in the future
      $parts['where'][] = sprintf(
        $check_time ? '%s < callback.datetime - INTERVAL <CALLBACK_PRE_WINDOW> MINUTE'
                    : 'DATE( %s ) < DATE( callback.datetime )',
        $viewing_date );
      return $parts;
    }
    else if( 'assignable callback' == $queue )
    {
      // callback time (in UTC) is in the calling window
      $parts['where'][] = sprintf(
        $check_time ? '%s >= callback.datetime - INTERVAL <CALLBACK_PRE_WINDOW> MINUTE'
                    : 'DATE( %s ) = DATE( callback.datetime )',
        $viewing_date,
        $viewing_date );
      return $parts;
    }
    else if( 'no appointment' == $queue )
    {
      // Make sure there is no unassigned appointment.  By design there can only be one of per
      // participant, so if the appointment is null then the participant has no pending
      // appointments.
      $parts['join'][] =
        'LEFT JOIN appointment '.
        'ON appointment.participant_id = participant_for_queue.id '.
        'AND appointment.assignment_id IS NULL';
      $parts['where'][] = 'appointment.id IS NULL';
      // Make sure there is no unassigned callback.  By design there can only be one of per
      // participant, so if the callback is null then the participant has no pending
      // callbacks.
      $parts['join'][] =
        'LEFT JOIN callback '.
        'ON callback.participant_id = participant_for_queue.id '.
        'AND callback.assignment_id IS NULL';
      $parts['where'][] = 'callback.id IS NULL';
      return $parts;
    }
    else if( 'quota disabled' == $queue )
    {
      // who belong to a quota which is disabled
      $parts['join'][] = $quota_join;
      $parts['where'][] = 'quota.disabled = true';
      return $parts;
    }
    else if( 'outside calling time' == $queue )
    {
      // make sure we are outside of the calling time
      $parts['where'][] = $check_time
                        ? 'NOT '.$calling_time_sql
                        : 'NOT true'; // purposefully a negative tautology
      return $parts;
    }
    else if( 'new participant' == $queue )
    {
      // make sure we are within the calling time
      $parts['where'][] = $check_time
                        ? $calling_time_sql
                        : 'true'; // purposefully a tautology
      // who belong to a quota which is not disabled or doesn't exist
      $parts['join'][] = $quota_join;
      $parts['where'][] = '( quota.disabled IS NULL OR quota.disabled = false )';
      // If there is a start_qnaire_date then the current qnaire has never been started,
      // the exception is for participants who have never been assigned
      $parts['where'][] =
        '('.
        '  '.$start_qnaire_date.' IS NOT NULL OR'.
        '  last_assignment_id IS NULL'.
        ')';
      return $parts;
    }
    else if( 'new participant available' == $queue )
    {
      // make sure the participant has availability and is currently available
      $parts['where'][] = $check_availability_sql.' = true';
      return $parts;
    }
    else if( 'new participant not available' == $queue )
    {
      // make sure the participant has availability and is currently not available
      // or doesn't specify availability
      $parts['where'][] = sprintf( '( %s = false OR %s IS NULL )',
                                   $check_availability_sql,
                                   $check_availability_sql );
      return $parts;
    }
    else if( 'old participant' == $queue )
    {
      // make sure we are within the calling time
      $parts['where'][] = $check_time
                        ? $calling_time_sql
                        : 'true'; // purposefully a tautology
      // who belong to a quota which is not disabled or doesn't exist
      $parts['join'][] = $quota_join;
      $parts['where'][] = '( quota.disabled IS NULL OR quota.disabled = false )';
      // add the last phone call's information
      $parts['from'][] = 'phone_call';
      $parts['from'][] = 'assignment_last_phone_call';
      $parts['where'][] =
        'assignment_last_phone_call.assignment_id = last_assignment_id';
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
        $parts['where'][] = 'not reached' == $phone_call_status
                          ? 'phone_call.status IN ( "machine message","machine no message",'.
                            '"disconnected","wrong number","not reached" )'
                          : sprintf( 'phone_call.status = "%s"', $phone_call_status );
        return $parts;
      }
      else if( 'phone call status waiting' == $queue )
      {
        // not yet reached the callback waiting time
        $parts['where'][] = sprintf(
          $check_time ? '%s < phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE' :
                        'DATE( %s ) < '.
                        'DATE( phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE )',
          $viewing_date,
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        return $parts;
      }
      else if( 'phone call status available' == $queue )
      {
        // reached the callback waiting time
        $parts['where'][] = sprintf(
          $check_time ? '%s >= phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE' :
                        'DATE( %s ) >= '.
                        'DATE( phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE )',
          $viewing_date,
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        // make sure the participant has availability and is currently available
        $parts['where'][] = $check_availability_sql.' = true';
        return $parts;
      }
      else if( 'phone call status not available' == $queue )
      {
        $parts['where'][] = sprintf(
          $check_time ? '%s >= phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE' :
                        'DATE( %s ) >= '.
                        'DATE( phone_call.end_datetime + INTERVAL <CALLBACK_%s> MINUTE )',
          $viewing_date,
          str_replace( ' ', '_', strtoupper( $phone_call_status ) ) );
        // make sure the participant has availability and is currently not available
        // or doesn't specify availability
        $parts['where'][] = sprintf( '( %s = false OR %s IS NULL )',
                                     $check_availability_sql,
                                     $check_availability_sql );
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
    $sql = str_replace( '<SELECT_PARTICIPANT>', 'participant_for_queue.id', $sql );
    $qnaire_test_sql = is_null( $this->db_qnaire ) ? 'IS NOT NULL' : '= '.$this->db_qnaire->id;
    $sql = str_replace( '<QNAIRE_TEST>', $qnaire_test_sql, $sql );

    // fill in the settings
    $setting_manager = lib::create( 'business\setting_manager' );
    $setting = $setting_manager->get_setting( 'appointment', 'call pre-window', $this->db_site );
    $sql = str_replace( '<APPOINTMENT_PRE_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'appointment', 'call post-window', $this->db_site );
    $sql = str_replace( '<APPOINTMENT_POST_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'callback', 'call pre-window', $this->db_site );
    $sql = str_replace( '<CALLBACK_PRE_WINDOW>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'calling', 'start time', $this->db_site );
    $sql = str_replace( '<CALLING_START_TIME>', $setting, $sql );
    $setting = $setting_manager->get_setting( 'calling', 'end time', $this->db_site );
    $sql = str_replace( '<CALLING_END_TIME>', $setting, $sql );

    // fill in all callback timing settings
    $setting_mod = lib::create( 'database\modifier' ); 
    $setting_mod->where( 'category', '=', 'callback timing' );
    $setting_class_name = lib::get_class_name( 'database\setting' );
    foreach( $setting_class_name::select( $setting_mod ) as $db_setting )
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
   * Creates the participant_for_queue temporary table needed by all queues.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   * @static
   */
  protected static function create_participant_for_queue()
  {
    if( static::$participant_for_queue_created ) return;
    $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue '.
           static::$participant_for_queue_sql;
    static::db()->execute( $sql );
    static::$participant_for_queue_created = true;
  }
  
  /**
   * Whether the participant_for_queue temporary table has been created.
   * @var boolean
   * @static
   */
  protected static $participant_for_queue_created = false;
  
  /**
   * The qnaire to restrict the queue to.
   * @var qnaire
   */
  protected $db_qnaire = NULL;

  /**
   * The site to restrict the queue to.
   * @var site
   */
  protected $db_site = NULL;

  /**
   * The date (YYYY-MM-DD) with respect to check all queue states.
   * @var string
   * @static
   */
  protected static $viewing_date = NULL;

  /**
   * Whether or not calling times are enabled.
   * @var boolean
   * @static
   */
  protected static $calling_times_enabled = NULL;

  /**
   * The queries for each queue
   * @var associative array of strings
   * @static
   */
  protected static $query_list = array();

  /**
   * A cache of participant counts for each queue and each qnaire
   * @var associative array of integers
   * @static
   */
  protected static $participant_count_cache = array();

  /**
   * A string containing the SQL used to create the participant_for_queue data
   * @var string
   * @static
   */
  protected static $participant_for_queue_sql = <<<'SQL'
SELECT participant.id,
participant.active AS participant_active,
participant.uid AS participant_uid,
participant.source_id AS participant_source_id,
participant.first_name AS participant_first_name,
participant.last_name AS participant_last_name,
participant.gender AS participant_gender,
participant.date_of_birth AS participant_date_of_birth,
participant.age_group_id AS participant_age_group_id,
participant.status AS participant_status,
participant.language AS participant_language,
participant.site_id AS participant_site_id,
participant.email AS participant_email,
participant.prior_contact_date AS participant_prior_contact_date,
primary_address.id AS primary_address_id,
primary_address.participant_id AS primary_address_participant_id,
primary_address.active AS primary_address_active,
primary_address.rank AS primary_address_rank,
primary_address.address1 AS primary_address_address1,
primary_address.address2 AS primary_address_address2,
primary_address.city AS primary_address_city,
primary_address.region_id AS primary_address_region_id,
primary_address.postcode AS primary_address_postcode,
primary_address.timezone_offset AS primary_address_timezone_offset,
primary_address.daylight_savings AS primary_address_daylight_savings,
primary_address.january AS primary_address_january,
primary_address.february AS primary_address_february,
primary_address.march AS primary_address_march,
primary_address.april AS primary_address_april,
primary_address.may AS primary_address_may,
primary_address.june AS primary_address_june,
primary_address.july AS primary_address_july,
primary_address.august AS primary_address_august,
primary_address.september AS primary_address_september,
primary_address.october AS primary_address_october,
primary_address.november AS primary_address_november,
primary_address.december AS primary_address_december,
primary_address.note AS primary_address_note,
primary_region.id AS primary_region_id,
primary_region.name AS primary_region_name,
primary_region.abbreviation AS primary_region_abbreviation,
primary_region.country AS primary_region_country,
primary_region.site_id AS primary_region_site_id,
first_address.id AS first_address_id,
first_address.participant_id AS first_address_participant_id,
first_address.active AS first_address_active,
first_address.rank AS first_address_rank,
first_address.address1 AS first_address_address1,
first_address.address2 AS first_address_address2,
first_address.city AS first_address_city,
first_address.region_id AS first_address_region_id,
first_address.postcode AS first_address_postcode,
first_address.timezone_offset AS first_address_timezone_offset,
first_address.daylight_savings AS first_address_daylight_savings,
first_address.january AS first_address_january,
first_address.february AS first_address_february,
first_address.march AS first_address_march,
first_address.april AS first_address_april,
first_address.may AS first_address_may,
first_address.june AS first_address_june,
first_address.july AS first_address_july,
first_address.august AS first_address_august,
first_address.september AS first_address_september,
first_address.october AS first_address_october,
first_address.november AS first_address_november,
first_address.december AS first_address_december,
first_address.note AS first_address_note,
last_consent.id AS last_consent_id,
last_consent.participant_id AS last_consent_participant_id,
last_consent.event AS last_consent_event,
last_consent.date AS last_consent_date,
last_consent.note AS last_consent_note,
current_interview.id AS current_interview_id,
current_interview.qnaire_id AS current_interview_qnaire_id,
current_interview.participant_id AS current_interview_participant_id,
current_interview.require_supervisor AS current_interview_require_supervisor,
current_interview.completed AS current_interview_completed,
current_interview.rescored AS current_interview_rescored,
current_interview.duplicate_qnaire_id AS current_interview_duplicate_qnaire_id,
last_assignment.id AS last_assignment_id,
last_assignment.user_id AS last_assignment_user_id,
last_assignment.site_id AS last_assignment_site_id,
last_assignment.interview_id AS last_assignment_interview_id,
last_assignment.queue_id AS last_assignment_queue_id,
last_assignment.start_datetime AS last_assignment_start_datetime,
last_assignment.end_datetime AS last_assignment_end_datetime,
current_qnaire.id AS current_qnaire_id,
current_qnaire.name AS current_qnaire_name,
current_qnaire.rank AS current_qnaire_rank,
current_qnaire.prev_qnaire_id AS current_qnaire_prev_qnaire_id,
current_qnaire.delay AS current_qnaire_delay,
current_qnaire.withdraw_sid AS current_qnaire_withdraw_sid,
current_qnaire.rescore_sid AS current_qnaire_rescore_sid,
current_qnaire.description AS current_qnaire_description,
next_qnaire.id AS next_qnaire_id,
next_qnaire.name AS next_qnaire_name,
next_qnaire.rank AS next_qnaire_rank,
next_qnaire.prev_qnaire_id AS next_qnaire_prev_qnaire_id,
next_qnaire.delay AS next_qnaire_delay,
next_qnaire.withdraw_sid AS next_qnaire_withdraw_sid,
next_qnaire.rescore_sid AS next_qnaire_rescore_sid,
next_qnaire.description AS next_qnaire_description,
next_prev_qnaire.id AS next_prev_qnaire_id,
next_prev_qnaire.name AS next_prev_qnaire_name,
next_prev_qnaire.rank AS next_prev_qnaire_rank,
next_prev_qnaire.prev_qnaire_id AS next_prev_qnaire_prev_qnaire_id,
next_prev_qnaire.delay AS next_prev_qnaire_delay,
next_prev_qnaire.withdraw_sid AS next_prev_qnaire_withdraw_sid,
next_prev_qnaire.rescore_sid AS next_prev_qnaire_rescore_sid,
next_prev_qnaire.description AS next_prev_qnaire_description,
next_prev_interview.id AS next_prev_interview_id,
next_prev_interview.qnaire_id AS next_prev_interview_qnaire_id,
next_prev_interview.participant_id AS next_prev_interview_participant_id,
next_prev_interview.require_supervisor AS next_prev_interview_require_supervisor,
next_prev_interview.completed AS next_prev_interview_completed,
next_prev_interview.rescored AS next_prev_interview_rescored,
next_prev_interview.duplicate_qnaire_id AS next_prev_interview_duplicate_qnaire_id,
next_prev_assignment.id AS next_prev_assignment_id,
next_prev_assignment.user_id AS next_prev_assignment_user_id,
next_prev_assignment.site_id AS next_prev_assignment_site_id,
next_prev_assignment.interview_id AS next_prev_assignment_interview_id,
next_prev_assignment.queue_id AS next_prev_assignment_queue_id,
next_prev_assignment.start_datetime AS next_prev_assignment_start_datetime,
next_prev_assignment.end_datetime AS next_prev_assignment_end_datetime
FROM participant 
LEFT JOIN participant_primary_address
ON participant.id = participant_primary_address.participant_id
LEFT JOIN address AS primary_address
ON participant_primary_address.address_id = primary_address.id
LEFT JOIN region AS primary_region
ON primary_address.region_id = primary_region.id
LEFT JOIN participant_first_address
ON participant.id = participant_first_address.participant_id
LEFT JOIN address AS first_address
ON participant_first_address.address_id = first_address.id
JOIN participant_last_consent
ON participant.id = participant_last_consent.participant_id
LEFT JOIN consent AS last_consent
ON last_consent.id = participant_last_consent.consent_id
LEFT JOIN interview AS current_interview
ON current_interview.participant_id = participant.id
LEFT JOIN interview_last_assignment
ON current_interview.id = interview_last_assignment.interview_id
LEFT JOIN assignment AS last_assignment
ON interview_last_assignment.assignment_id = last_assignment.id
LEFT JOIN qnaire AS current_qnaire
ON current_qnaire.id = current_interview.qnaire_id
LEFT JOIN qnaire AS next_qnaire
ON next_qnaire.rank = ( current_qnaire.rank + 1 )
LEFT JOIN qnaire AS next_prev_qnaire
ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id
LEFT JOIN interview AS next_prev_interview
ON next_prev_interview.qnaire_id = next_prev_qnaire.id
AND next_prev_interview.participant_id = participant.id
LEFT JOIN assignment AS next_prev_assignment
ON next_prev_assignment.interview_id = next_prev_interview.id
WHERE
(
  current_qnaire.rank IS NULL
  OR current_qnaire.rank =
  (
    SELECT MAX( qnaire.rank )
    FROM interview, qnaire
    WHERE qnaire.id = interview.qnaire_id
    AND current_interview.participant_id = interview.participant_id
    GROUP BY current_interview.participant_id
  )
)
AND
(
  next_prev_assignment.end_datetime IS NULL
  OR next_prev_assignment.end_datetime =
  (
    SELECT MAX( assignment.end_datetime )
    FROM interview, assignment
    WHERE interview.qnaire_id = next_prev_qnaire.id
    AND interview.id = assignment.interview_id
    AND next_prev_assignment.id = assignment.id
    GROUP BY next_prev_assignment.interview_id
  )
)
SQL;
}
?>
