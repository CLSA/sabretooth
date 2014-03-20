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
   * Override parent get_record_list() method to dynamically populate time-specific queues
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param modifier $modifier A modifier to apply to the list or count.
   * @param boolean $inverted Whether to invert the count (count records NOT in the joining table).
   * @param boolean $count If true then this method returns the count instead of list of records.
   * @param boolean $distinct Whether to use the DISTINCT sql keyword
   * @return array( record ) | int
   * @access protected
   */
  public function get_record_list(
    $record_type, $modifier = NULL, $inverted = false, $count = false, $distinct = true )
  {
    // if we're getting a participant list/count for a time-specific column, populate it first
    if( 'participant' == $record_type ) $this->populate_time_specific();

    // if the queue's site has been set, add its restriction to the query
    if( !is_null( $this->db_site ) )
    {
      $service_id = lib::create( 'business\session' )->get_service()->id;
      if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'queue_has_participant.site_id', '=', $this->db_site->id );
    }

    // now call the parent method as usual
    return parent::get_record_list( $record_type, $modifier, $inverted, $count, $distinct );
  }

  /**
   * Generates the query list.
   * 
   * This method is called internally by the {@link repopulate} method in order to generate
   * the proper SQL to complete the repopulate of queues.
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
      'condition',
      'eligible',
      'qnaire',
      'qnaire waiting',
      'assigned',
      'ivr_appointment',
      'appointment',
      'upcoming appointment',
      'assignable appointment',
      'missed appointment',
      'quota disabled',
      'outside calling time',
      'callback',
      'upcoming callback',
      'assignable callback',
      'new participant',
      'old participant' );

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
    foreach( $phone_call_status_list as $phone_call_status )
    {
      $queue_list = array(
        'phone call status',
        'phone call status waiting',
        'phone call status ready' );

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
   * Re-populates a queue's participant list
   * 
   * This method is used to pupulate all non-time-specific queues.
   * Only non time-specific queues are affected by this function, to populate time-specific
   * queues use the populate_time_specific() method instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant If provided then only that participant will
   *        be affected by the operation.
   * @access public
   * @static
   */
  static public function repopulate( $db_participant = NULL )
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();

    // block with a semaphore
    $session->acquire_semaphore();

    // make sure the temporary table exists
    static::create_participant_for_queue( $db_participant );

    // make sure the queue list cache exists
    static::create_queue_list_cache();

    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'id' );
    foreach( static::select( $modifier ) as $db_queue )
    {
      $columns = sprintf(
        'DISTINCT participant_for_queue.id, %s, '.
        'IFNULL( service_has_participant_preferred_site_id, primary_region_site_id ), '.
        'effective_qnaire_id, '.
        'start_qnaire_date',
        $database_class_name::format_string( $db_queue->id ) );
  
      $sql = sprintf(
        'DELETE FROM queue_has_participant WHERE queue_id = %s ',
        $database_class_name::format_string( $db_queue->id ) );
      if( !is_null( $db_participant ) )
        $sql .= sprintf( ' AND participant_id = %s ',
                         $database_class_name::format_string( $db_participant->id ) );
      static::db()->execute( $sql );
      
      // only populate queues which are not time-specific
      if( !$db_queue->time_specific )
        static::db()->execute( sprintf(
          'INSERT INTO queue_has_participant( '.
            'participant_id, queue_id, site_id, qnaire_id, start_qnaire_date ) %s',
          $db_queue->get_sql( $columns ) ) );
    }

    $session->release_semaphore();
  }

  /**
   * Re-populates a time-specific queue
   * 
   * This method is used to populate queues which are dependent on the exact time.
   * Only time-specific queues are affected by this function, to populate non time-specific
   * queues use the repopulate() static method instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function populate_time_specific()
  {
    // do nothing if this isn't a time-specific queue
    if( !$this->time_specific ) return;

    $database_class_name = lib::get_class_name( 'database\database' );
    $session = lib::create( 'business\session' );
    $db_user = $session->get_user();

    // block with a semaphore
    $session->acquire_semaphore();

    // make sure the queue list cache exists and get the queue's parent
    static::create_queue_list_cache();
    $db_parent_queue = self::$queue_list_cache[$this->name]['parent'];

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

    // fill in the settings
    $setting_manager = lib::create( 'business\setting_manager' );

    static::db()->execute( sprintf(
      'DELETE FROM queue_has_participant WHERE queue_id = %s',
      $database_class_name::format_string( $this->id ) ) );

    // populate appointment upcomming/assignable/missed queues
    if( ' appointment' == substr( $this->name, -12 ) )
    {
      $appointment_pre_window =
        $setting_manager->get_setting( 'appointment', 'call pre-window', $this->db_site );
      $appointment_post_window =
        $setting_manager->get_setting( 'appointment', 'call post-window', $this->db_site );

      $sql = sprintf(
        'INSERT INTO queue_has_participant( '.
          'participant_id, queue_id, site_id, qnaire_id, start_qnaire_date ) '.
        'SELECT DISTINCT queue_has_participant.participant_id, %s, site_id, qnaire_id, start_qnaire_date '.
        'FROM queue_has_participant '.
        'JOIN appointment ON queue_has_participant.participant_id = appointment.participant_id '.
        'AND appointment.assignment_id IS NULL '.
        'WHERE queue_id = %s AND ',
        $database_class_name::format_string( $this->id ),
        $database_class_name::format_string( $db_parent_queue->id ) );

      if( 'upcoming appointment' == $this->name )
      {
        $sql .= sprintf(
          $check_time ? '%s < appointment.datetime - INTERVAL %d MINUTE'
                      : 'DATE( %s ) < DATE( appointment.datetime )',
          $viewing_date,
          $appointment_pre_window );
      }
      else if( 'assignable appointment' == $this->name )
      {
        $sql .= sprintf(
          $check_time ? '%s >= appointment.datetime - INTERVAL %d MINUTE AND '.
                        '%s <= appointment.datetime + INTERVAL %d MINUTE'
                      : 'DATE( %s ) = DATE( appointment.datetime )',
          $viewing_date,
          $appointment_pre_window,
          $viewing_date,
          $appointment_post_window );
      }
      else if( 'missed appointment' == $this->name )
      {
        $sql .= sprintf(
          $check_time ? '%s > appointment.datetime + INTERVAL %d MINUTE'
                      : 'DATE( %s ) > DATE( appointment.datetime )',
          $viewing_date,
          $appointment_post_window );
      }

      static::db()->execute( $sql );
    }
    // populate callback upcoming/assignable queues
    else if( ' callback' == substr( $this->name, -9 ) )
    {
      $callback_pre_window =
        $setting_manager->get_setting( 'callback', 'call pre-window', $this->db_site );
    
      $sql = sprintf(
        'INSERT INTO queue_has_participant( '.
          'participant_id, queue_id, site_id, qnaire_id, start_qnaire_date ) '.
        'SELECT DISTINCT queue_has_participant.participant_id, %s, site_id, qnaire_id, start_qnaire_date '.
        'FROM queue_has_participant '.
        'JOIN callback ON queue_has_participant.participant_id = callback.participant_id '.
        'AND callback.assignment_id IS NULL '.
        'WHERE queue_id = %s AND ',
        $database_class_name::format_string( $this->id ),
        $database_class_name::format_string( $db_parent_queue->id ) );

      if( 'upcoming callback' == $this->name )
      {
        $sql .= sprintf(
          $check_time ? '%s < callback.datetime - INTERVAL %d MINUTE'
                      : 'DATE( %s ) < DATE( callback.datetime )',
          $viewing_date,
          $callback_pre_window );
      }
      else if( 'assignable callback' == $this->name )
      {
        $sql .= sprintf(
          $check_time ? '%s >= callback.datetime - INTERVAL %d MINUTE'
                      : 'DATE( %s ) = DATE( callback.datetime )',
          $viewing_date,
          $callback_pre_window );
      }

      static::db()->execute( $sql );
    }
    // populate "last call waiting" queues
    else if( ' waiting' == substr( $this->name, -8 ) || ' ready' == substr( $this->name, -6 ) )
    {
      $call_type = ' waiting' == substr( $this->name, -8 )
                 ? substr( $this->name, 0, -8 )
                 : substr( $this->name, 0, -6 );
      $callback_timing =
        $setting_manager->get_setting( 'callback timing', $call_type, $this->db_site );

      $sql = sprintf(
        'INSERT INTO queue_has_participant( '.
          'participant_id, queue_id, site_id, qnaire_id, start_qnaire_date ) '.
        'SELECT DISTINCT queue_has_participant.participant_id, %s, site_id, qnaire_id, start_qnaire_date '.
        'FROM queue_has_participant '.
        'JOIN participant_last_interview '.
        'ON queue_has_participant.participant_id = participant_last_interview.participant_id '.
        'JOIN interview_last_assignment '.
        'ON participant_last_interview.interview_id = interview_last_assignment.interview_id '.
        'JOIN assignment_last_phone_call '.
        'ON interview_last_assignment.assignment_id = assignment_last_phone_call.assignment_id '.
        'JOIN phone_call ON phone_call.id = assignment_last_phone_call.phone_call_id '.
        'WHERE queue_id = %s AND ',
        $database_class_name::format_string( $this->id ),
        $database_class_name::format_string( $db_parent_queue->id ) );

      if( ' waiting' == substr( $this->name, -8 ) )
      {
        $sql .= sprintf(
          $check_time ? '%s < phone_call.end_datetime + INTERVAL %d MINUTE' :
                        'DATE( %s ) < DATE( phone_call.end_datetime + INTERVAL %d MINUTE )',
          $viewing_date,
          $callback_timing );
      }
      else // ' ready' == substr( $this->name, -6 )
      {
        $sql .= sprintf(
          $check_time ? '%s >= phone_call.end_datetime + INTERVAL %d MINUTE' :
                        'DATE( %s ) >= DATE( phone_call.end_datetime + INTERVAL %d MINUTE )',
          $viewing_date,
          $callback_timing );
      }

      static::db()->execute( $sql );
    }
    else
    {
      $session->release_semaphore();

      throw lib::create( 'exception\runtime',
        sprintf( 'No rules to populate time-specific queue "%s"', $this->name ),
        __METHOD__ );
    }

    $session->release_semaphore();
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

    // reset the query list
    self::$query_list = array();
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
   * @param string $queue The name of the queue to get the query parts for
   * @param string $phone_call_status The name of which phone call status to get the query parts
   *               for (or NULL when the queue type is not based on phone call status)
   * @return associative array
   * @throws exception\argument
   * @access protected
   * @static
   */
  protected static function get_query_parts( $queue, $phone_call_status = NULL )
  {
    // start by getting the queue and parent queue objects from the cache
    $queue_name = is_null( $phone_call_status )
                ? $queue
                : str_replace( 'phone call status', $phone_call_status, $queue );
    $db_queue = self::$queue_list_cache[$queue_name]['object'];
    if( is_null( $db_queue ) ) // invalid queue name
      throw lib::create( 'exception\runtime',
        sprintf( 'Cannot find queue named "%s"', $queue_name ), __METHOD__ );
    $db_parent_queue = self::$queue_list_cache[$queue_name]['parent'];

    // if this is a time-specific queue then return a query which will return no rows
    if( $db_queue->time_specific )
      return array(
        'from' => array( 'participant_for_queue' ),
        'join' => array(),
        'where' => array( 'false' ) );

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

    // an array containing all of the qnaire queue's direct children queues
    $qnaire_children = array(
      'qnaire waiting', 'assigned', 'ivr_appointment', 'appointment', 'quota disabled',
      'outside calling time', 'callback', 'new participant', 'old participant' );

    // join to the queue_restriction table based on site, city, region or postcode
    $first_address_join =
      'LEFT JOIN participant_for_queue_first_address '.
      'ON participant_for_queue_first_address.person_id = participant_person_id ';

    // join to the quota table based on site, region, gender and age group
    $quota_join =
      'LEFT JOIN quota '.
      'ON quota.site_id = primary_region_site_id '.
      'AND quota.region_id = primary_region_id '.
      'AND quota.gender = participant_gender '.
      'AND quota.age_group_id = participant_age_group_id '.
      'LEFT JOIN quota_state '.
      'ON quota.id = quota_state.quota_id';

    // checks to make sure a participant is within calling time hours
    if( $check_time )
    {
      $localtime = localtime( time(), true );
      $offset = $localtime['tm_isdst']
              ? 'first_address_timezone_offset + first_address_daylight_savings'
              : 'first_address_timezone_offset';
      $calling_time_sql = sprintf(
        '( '.
          'TIME( %s + INTERVAL %s*60 MINUTE ) >= "<CALLING_START_TIME>" AND '.
          'TIME( %s + INTERVAL %s*60 MINUTE ) < "<CALLING_END_TIME>" '.
        ')',
        $viewing_date,
        $offset,
        $viewing_date,
        $offset );
    }

    // get the parent queue's query parts
    if( is_null( $phone_call_status ) )
    {
      if( !is_null( $db_parent_queue ) ) $parts = self::get_query_parts( $db_parent_queue->name );
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
        'where' => array( '<SITE_TEST>' ) );
    }
    else if( 'finished' == $queue )
    {
      // no effective_qnaire_id means no qnaires left to complete
      $parts['where'][] = 'effective_qnaire_id IS NULL';
    }
    else
    {
      // effective_qnaire_id is the either the next qnaire to work on or the one in progress
      $parts['where'][] = 'effective_qnaire_id IS NOT NULL';
      if( 'ineligible' == $queue )
      {
        // ineligible means either inactive or with a "final" state
        $parts['join'][] = 
          'JOIN participant_for_queue_phone_count '.
          'ON participant_for_queue_phone_count.person_id = participant_person_id';
        $parts['where'][] =
          '( '.
            'participant_active = false '.
            'OR participant_state_id IS NOT NULL '.
            'OR phone_count = 0 '.
            'OR last_consent_accept = 0 '.
          ')';
      }
      else if( 'inactive' == $queue )
      {
        $parts['where'][] = 'participant_active = false';
      }
      else if( 'refused consent' == $queue )
      {
        $parts['where'][] = 'participant_active = true';
        $parts['where'][] = 'last_consent_accept = 0';
      }
      else if( 'condition' == $queue )
      {
        $parts['where'][] = 'participant_active = true';
        $parts['where'][] =
          '( '.
            'last_consent_accept IS NULL '.
            'OR last_consent_accept = 1 '.
          ')';
        $parts['where'][] = 'participant_state_id IS NOT NULL';
      }
      else if( 'eligible' == $queue )
      {
        // active participant who does not have a "final" state and has at least one phone number
        $parts['join'][] = 
          'JOIN participant_for_queue_phone_count '.
          'ON participant_for_queue_phone_count.person_id = participant_person_id';
        $parts['where'][] = 'participant_active = true';
        $parts['where'][] = 'participant_state_id IS NULL';
        $parts['where'][] = 'phone_count > 0';
        $parts['where'][] =
          '( '.
            'last_consent_accept IS NULL OR '.
            'last_consent_accept = 1 '.
          ')';
      }
      else if( 'qnaire' == $queue )
      {
        // no additional parts needed
      }
      // we must process all of the qnaire queue's direct children as a whole
      else if( in_array( $queue, $qnaire_children ) )
      {
        if( 'qnaire waiting' == $queue )
        {
          // the current qnaire cannot start before start_qnaire_date
          $parts['where'][] = 'start_qnaire_date IS NOT NULL';
          $parts['where'][] = sprintf( 'start_qnaire_date > DATE( %s )',
                                       $viewing_date );
        }
        else
        {
          // the qnaire is ready to start if the start_qnaire_date is null or we have reached that date
          $parts['where'][] = sprintf(
            '( '.
              'start_qnaire_date IS NULL OR '.
              'start_qnaire_date <= DATE( %s ) '.
            ')',
            $viewing_date );

          if( 'assigned' == $queue )
          {
            // participants who are currently assigned
            $parts['where'][] =
              '( last_assignment_id IS NOT NULL AND last_assignment_end_datetime IS NULL )';
          }
          else
          {
            // participants who are NOT currently assigned
            $parts['where'][] =
              '( last_assignment_id IS NULL OR last_assignment_end_datetime IS NOT NULL )';

            if( 'ivr_appointment' == $queue )
            {
              // link to ivr_appointment table and make sure the ivr_appointment completed status
              // hasn't been set (by design, there can only ever be one unset ivr_appointment per
              // participant)
              $parts['from'][] = 'ivr_appointment';
              $parts['where'][] = 'ivr_appointment.participant_id = participant_for_queue.id';
              $parts['where'][] = 'ivr_appointment.completed IS NULL';
            }
            else if( 'appointment' == $queue )
            {
              // link to appointment table and make sure the appointment hasn't been assigned
              // (by design, there can only ever be one unassigned appointment per participant)
              $parts['from'][] = 'appointment';
              $parts['where'][] = 'appointment.participant_id = participant_for_queue.id';
              $parts['where'][] = 'appointment.assignment_id IS NULL';
            }
            else
            {
              // Make sure there is no unassigned appointment.  By design there can only be one of
              // per participant, so if the appointment is null then the participant has no pending
              // appointments.
              $parts['join'][] =
                'LEFT JOIN appointment '.
                'ON appointment.participant_id = participant_for_queue.id '.
                'AND appointment.assignment_id IS NULL';
              $parts['where'][] = 'appointment.id IS NULL';

              $parts['join'][] = $first_address_join;
              $parts['join'][] = $quota_join;

              if( 'quota disabled' == $queue )
              {
                // who belong to a quota which is disabled
                $parts['where'][] = 'quota_state.disabled = true';
                // and who are not marked to override quota
                $parts['where'][] = 'participant_override_quota = false';
                $parts['where'][] = 'source_override_quota = false';
              }
              else
              {
                // who belong to a quota which is not disabled or doesn't exist or is overridden
                $parts['where'][] =
                  '( quota_state.disabled IS NULL OR '.
                    'quota_state.disabled = false OR '.
                    'participant_override_quota = true OR '.
                    'source_override_quota = true )';
                
                if( 'outside calling time' == $queue )
                {
                  // outside of the calling time
                  $parts['where'][] = $check_time
                                    ? 'NOT '.$calling_time_sql
                                    : 'NOT true'; // purposefully a negative tautology
                }
                else
                {
                  // within the calling time
                  $parts['where'][] = $check_time
                                    ? $calling_time_sql
                                    : 'true'; // purposefully a tautology

                  if( 'callback' == $queue )
                  {
                    // link to callback table and make sure the callback hasn't been assigned
                    // (by design, there can only ever one unassigned callback per participant)
                    $parts['from'][] = 'callback';
                    $parts['where'][] = 'callback.participant_id = participant_for_queue.id';
                    $parts['where'][] = 'callback.assignment_id IS NULL';
                  }
                  else
                  {
                    // Make sure there is no unassigned callback.  By design there can only be one of
                    // per participant, so if the callback is null then the participant has no pending
                    // callbacks.
                    $parts['join'][] =
                      'LEFT JOIN callback '.
                      'ON callback.participant_id = participant_for_queue.id '.
                      'AND callback.assignment_id IS NULL';
                    $parts['where'][] = 'callback.id IS NULL';

                    if( 'new participant' == $queue )
                    {
                      // If there is a start_qnaire_date then the current qnaire has never been
                      // started, the exception is for participants who have never been assigned
                      $parts['where'][] =
                        '('.
                          'start_qnaire_date IS NOT NULL OR '.
                          'last_assignment_id IS NULL '.
                        ')';
                    }
                    else // old participant
                    {
                      // if there is no start_qnaire_date then the current qnaire has been started
                      $parts['where'][] = 'start_qnaire_date IS NULL';
                      // add the last phone call's information
                      $parts['from'][] = 'phone_call';
                      $parts['from'][] = 'assignment_last_phone_call';
                      $parts['where'][] =
                        'assignment_last_phone_call.assignment_id = last_assignment_id';
                      $parts['where'][] =
                        'phone_call.id = assignment_last_phone_call.phone_call_id';
                      // make sure the current interview's qnaire matches the effective qnaire,
                      // otherwise this participant has never been assigned
                      $parts['where'][] = 'current_interview_qnaire_id = effective_qnaire_id';
                    }
                  }
                }
              }
            }
          }
        }
      }
      else if( 'phone call status' == $queue )
      {
        // phone call status has been included (all remaining queues require it)
        if( is_null( $phone_call_status ) )
          throw lib::create( 'exception\argument',
            'phone_call_status', $phone_call_status, __METHOD__ );

        $parts['where'][] = 'not reached' == $phone_call_status
                          ? 'phone_call.status IN ( "machine message","machine no message",'.
                            '"disconnected","wrong number","not reached" )'
                          : sprintf( 'phone_call.status = "%s"', $phone_call_status );
      }
      else // we should never get here
      {
        throw lib::create( 'exception\argument', 'queue', $queue, __METHOD__ );
      }
    }

    return $parts;
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
    $database_class_name = lib::get_class_name( 'database\database' );

    // start by making sure the query list has been generated
    if( 0 == count( self::$query_list ) ) self::generate_query_list();

    $site_test_sql = is_null( $this->db_site )
                   ? 'true'
                   : sprintf( 'IFNULL( service_has_participant_preferred_site_id, '.
                              'primary_region_site_id ) = %s',
                              $database_class_name::format_string( $db_site->id ) );
    $sql = self::$query_list[ $this->name ];
    $sql = preg_replace( '/\<SELECT_PARTICIPANT\>/', $select_participant_sql, $sql, 1 );
    $sql = str_replace( '<SELECT_PARTICIPANT>', 'participant_for_queue.id', $sql );
    $sql = str_replace( '<SITE_TEST>', $site_test_sql, $sql );

    // fill in the settings
    $setting_manager = lib::create( 'business\setting_manager' );
    $setting = $setting_manager->get_setting( 'calling', 'start time', $this->db_site );
    $sql = str_replace( '<CALLING_START_TIME>', $setting.':00', $sql );
    $setting = $setting_manager->get_setting( 'calling', 'end time', $this->db_site );
    $sql = str_replace( '<CALLING_END_TIME>', $setting.':00', $sql );

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
   * @param database\participant $db_participant If provided then only that participant will
   *        be affected by the operation.
   * @access protected
   * @static
   */
  protected static function create_participant_for_queue( $db_participant = NULL )
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $service_id = lib::create( 'business\session' )->get_service()->id;

    if( static::$participant_for_queue_created ) return;

    // build participant_for_queue table
    $sql = sprintf( 'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue '.
                    static::$participant_for_queue_sql,
                    $database_class_name::format_string( $service_id ) );
    if( !is_null( $db_participant ) )
      $sql .= sprintf( ' AND participant.id = %s ',
                       $database_class_name::format_string( $db_participant->id ) );

    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE participant_for_queue '.
        'ADD INDEX fk_id ( id ), '.
        'ADD INDEX fk_participant_person_id ( participant_person_id ), '.
        'ADD INDEX fk_participant_gender ( participant_gender ), '.
        'ADD INDEX fk_participant_age_group_id ( participant_age_group_id ), '.
        'ADD INDEX fk_participant_active ( participant_active ), '.
        'ADD INDEX fk_participant_state_id ( participant_state_id ), '.
        'ADD INDEX fk_service_has_participant_preferred_site_id ( '.
          'service_has_participant_preferred_site_id ), '.
        'ADD INDEX fk_effective_qnaire_id ( effective_qnaire_id ), '.
        'ADD INDEX fk_last_consent_accept ( last_consent_accept ), '.
        'ADD INDEX fk_last_assignment_id ( last_assignment_id ), '.
        'ADD INDEX dk_primary_region_id ( primary_region_id ), '.
        'ADD INDEX dk_primary_region_site_id ( primary_region_site_id )' );

    // build participant_for_queue_phone_count table
    $sql = sprintf(
      'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_phone_count '.
      'SELECT participant.person_id, IF( phone.id IS NULL, 0, COUNT(*) ) phone_count '.
      'FROM participant '.
      'JOIN service_has_participant ON participant.id = service_has_participant.participant_id '.
      'AND service_has_participant.service_id = %s '.
      'LEFT JOIN phone ON participant.person_id = phone.person_id '.
      'AND phone.active AND phone.number IS NOT NULL ',
      $database_class_name::format_string( $service_id ) );
    if( !is_null( $db_participant ) )
      $sql .= sprintf( 'WHERE participant.id = %s ',
                       $database_class_name::format_string( $db_participant->id ) );
    $sql .= 'GROUP BY participant.person_id ';

    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue_phone_count' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE participant_for_queue_phone_count '.
        'ADD INDEX dk_person_id ( person_id ), '.
        'ADD INDEX dk_phone_count ( phone_count )' );

    // build participant_for_queue_first_address table
    $sql = 
      'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_first_address '.
      'SELECT person_first_address.person_id, '.
             'address.city AS first_address_city, '.
             'address.region_id AS first_address_region_id, '.
             'address.postcode AS first_address_postcode, '.
             'address.timezone_offset AS first_address_timezone_offset, '.
             'address.daylight_savings AS first_address_daylight_savings '.
      'FROM person_first_address '.
      'LEFT JOIN address '.
      'ON person_first_address.address_id = address.id ';
    if( !is_null( $db_participant ) )
      $sql .= sprintf( 'WHERE person_first_address.person_id = %s ',
                       $database_class_name::format_string( $db_participant->person_id ) );

    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue_first_address' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE participant_for_queue_first_address '.
        'ADD INDEX dk_person_id ( person_id ), '.
        'ADD INDEX dk_first_address_city ( first_address_city ), '.
        'ADD INDEX dk_first_address_region_id ( first_address_region_id ), '.
        'ADD INDEX dk_first_address_postcode ( first_address_postcode ), '.
        'ADD INDEX dk_first_address_timezone_offset ( first_address_timezone_offset ), '.
        'ADD INDEX dk_first_address_daylight_savings ( first_address_daylight_savings )' );

    static::$participant_for_queue_created = true;
  }

  /**
   * Creates the queue_list_cache needed by all queues.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   * @static
   */
  protected static function create_queue_list_cache()
  {
    if( 0 == count( self::$queue_list_cache ) )
    {
      $queue_mod = lib::create( 'database\modifier' );
      $queue_mod->order( 'id' );
      foreach( static::select( $queue_mod ) as $db_queue )
      {
        self::$queue_list_cache[$db_queue->name] =
          array( 'object' => $db_queue,
                 'parent' => NULL );

        if( !is_null( $db_queue->parent_queue_id ) )
        { // this queue has a parent, find and index it
          foreach( array_reverse( self::$queue_list_cache ) as $queue_cache )
          { // search in reverse order, most parent's aren't far from their child
            if( $db_queue->parent_queue_id == $queue_cache['object']->id )
            {
              self::$queue_list_cache[$db_queue->name]['parent'] = &$queue_cache['object'];
              break;
            }
          }
        }
      }
    }
  }

  /**
   * Whether the participant_for_queue temporary table has been created.
   * @var boolean
   * @access protected
   * @static
   */
  protected static $participant_for_queue_created = false;

  /**  
   * The site to restrict the queue to.
   * @var site
   * @access protected
   */
  protected $db_site = NULL;

  /**
   * The date (YYYY-MM-DD) with respect to check all queue states.
   * @var string
   * @access protected
   * @static
   */
  protected static $viewing_date = NULL;

  /**
   * Whether or not calling times are enabled.
   * @var boolean
   * @access protected
   * @static
   */
  protected static $calling_times_enabled = NULL;

  /**
   * The queries for each queue
   * @var associative array of strings
   * @access protected
   * @static
   */
  protected static $query_list = array();

  /**
   * A cache of all queues and their parents used by get_query_parts()
   * @var array
   * @access private
   * @static
   */
  private static $queue_list_cache = array();

  /**
   * A string containing the SQL used to create the participant_for_queue data
   * @var string
   * @access protected
   * @static
   */
  protected static $participant_for_queue_sql = <<<'SQL'
SELECT participant.id,
participant.person_id AS participant_person_id,
participant.active AS participant_active,
participant.gender AS participant_gender,
participant.age_group_id AS participant_age_group_id,
participant.state_id AS participant_state_id,
participant.override_quota AS participant_override_quota,
source.override_quota AS source_override_quota,
service_has_participant.preferred_site_id AS service_has_participant_preferred_site_id,
primary_region.id AS primary_region_id,
primary_region_site.site_id primary_region_site_id,
last_consent.accept AS last_consent_accept,
current_interview.qnaire_id AS current_interview_qnaire_id,
last_assignment.id AS last_assignment_id,
last_assignment.end_datetime AS last_assignment_end_datetime,
IF
(
  current_interview.id IS NULL,
  ( SELECT id FROM qnaire WHERE rank = 1 ),
  IF( current_interview.completed, next_qnaire.id, current_qnaire.id )
) as effective_qnaire_id,
(
  IF
  (
    current_interview.id IS NULL,
    IF
    (
      ( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = first_qnaire.id ),
      IFNULL( first_event.datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire.delay WEEK,
      NULL
    ),
    IF
    (
      current_interview.completed,
      GREATEST
      (
        IFNULL( next_event.datetime, "" ),
        IFNULL( next_prev_assignment.end_datetime, "" )
      ) + INTERVAL next_qnaire.delay WEEK,
      NULL
    )
  )
) AS start_qnaire_date
FROM participant
JOIN service_has_participant
ON participant.id = service_has_participant.participant_id
AND service_has_participant.datetime IS NOT NULL
AND service_id = %s
JOIN source
ON participant.source_id = source.id
LEFT JOIN person_primary_address
ON participant.person_id = person_primary_address.person_id
LEFT JOIN address AS primary_address
ON person_primary_address.address_id = primary_address.id
LEFT JOIN region AS primary_region
ON primary_address.region_id = primary_region.id
LEFT JOIN region_site AS primary_region_site
ON primary_region.id = primary_region_site.region_id
AND primary_region_site.service_id = service_has_participant.service_id
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
CROSS JOIN qnaire AS first_qnaire
ON first_qnaire.rank = 1
LEFT JOIN event first_event
ON participant.id = first_event.participant_id
AND first_event.event_type_id IN
(
  SELECT event_type_id
  FROM qnaire_has_event_type
  WHERE qnaire_id = first_qnaire.id
)
LEFT JOIN event next_event
ON participant.id = next_event.participant_id
AND next_event.event_type_id IN
(
  SELECT event_type_id
  FROM qnaire_has_event_type
  WHERE qnaire_id = next_qnaire.id
)
WHERE
(
  current_qnaire.rank IS NULL
  OR current_qnaire.rank =
  (
    SELECT MAX( qnaire.rank )
    FROM interview
    JOIN qnaire ON qnaire.id = interview.qnaire_id
    WHERE interview.participant_id = current_interview.participant_id
    GROUP BY current_interview.participant_id
  )
)
AND
(
  next_prev_assignment.end_datetime IS NULL
  OR next_prev_assignment.end_datetime =
  (
    SELECT MAX( assignment.end_datetime )
    FROM interview
    JOIN assignment ON assignment.interview_id = interview.id
    WHERE interview.qnaire_id = next_prev_qnaire.id
    AND assignment.id = next_prev_assignment.id
    GROUP BY next_prev_assignment.interview_id
  )
)
SQL;
}
