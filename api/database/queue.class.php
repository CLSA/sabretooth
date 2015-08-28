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
   * Returns whether a queue is enabled or not for a given site and qnaire.
   * @auther Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   * @return boolean
   */
  public function get_enabled( $db_site, $db_qnaire )
  {
    $queue_state_class_name = lib::get_class_name( 'database\queue_state' );
    $db_queue_state = $queue_state_class_name::get_unique_record(
      array( 'queue_id', 'site_id', 'qnaire_id' ),
      array( $this->id, $db_site->id, $db_qnaire->id ) );
    return is_null( $db_queue_state );
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

    // make sure the queue_list_cache has been created and loop through it
    static::create_queue_list_cache();
    foreach( array_keys( self::$queue_list_cache ) as $queue )
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
  }

  /**
   * Re-populates a queue's participant list
   * 
   * This method is used to pupulate all non-time-specific queues.
   * Only non time-specific queues are affected by this function, to populate time-specific
   * queues use the repopulate_time_specific() method instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant If provided then only that participant will
   *        be affected by the operation.
   * @access public
   * @static
   */
  static public function repopulate( $db_participant = NULL )
  {
    if( static::$debug ) $total_time = util::get_elapsed_time();  

    // block with a semaphore
    $semaphore = lib::create( 'business\semaphore', __METHOD__ );
    $semaphore->acquire();

    // make sure the temporary table exists
    static::create_participant_for_queue( $db_participant );

    // delete queue_has_participant records
    $sql = is_null( $db_participant )
         ? 'TRUNCATE queue_has_participant'
         : sprintf( 'DELETE FROM queue_has_participant WHERE participant_id = %s',
                    static::db()->format_string( $db_participant->id ) );
    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( $sql );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'time_specific', '=', false );
    $modifier->order( 'id' );
    foreach( static::select_objects( $modifier ) as $db_queue )
    {
      if( static::$debug ) $queue_time = util::get_elapsed_time();

      $columns = sprintf(
        'DISTINCT participant_for_queue.id, %s, '.
        'participant_site_id, '.
        'effective_qnaire_id, '.
        'start_qnaire_date',
        static::db()->format_string( $db_queue->id ) );
  
      static::db()->execute( sprintf(
        'INSERT INTO queue_has_participant( '.
          'participant_id, queue_id, site_id, qnaire_id, start_qnaire_date ) %s',
        $db_queue->get_sql( $columns ) ) );

      if( static::$debug ) log::debug( sprintf(
        '(Queue) "%s" build time: %0.2f', $db_queue->name, util::get_elapsed_time() - $queue_time ) );
    }
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total queue build time: %0.2f', util::get_elapsed_time() - $time ) );

    $semaphore->release();
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total repopulate() time: %0.2f', util::get_elapsed_time() - $total_time ) );
  }

  /**
   * Re-populates all time-specific queue
   * 
   * This method is used to populate queues which are dependent on the exact time.
   * Only time-specific queues are affected by this function, to populate non time-specific
   * queues use the repopulate() static method instead.
   * @param database\participant $db_participant If provided then only that participant will
   *        be affected by the operation.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  static public function repopulate_time_specific( $db_participant = NULL )
  {
    if( static::$debug ) $total_time = util::get_elapsed_time();  

    // block with a semaphore
    $semaphore = lib::create( 'business\semaphore', __METHOD__ );
    $semaphore->acquire();

    // delete queue_has_participant records
    $delete_mod = lib::create( 'database\modifier' );
    $delete_mod->where( 'queue_id', 'IN', '( SELECT id FROM queue WHERE time_specific = true )', false );
    if( !is_null( $db_participant ) ) $delete_mod->where( 'participant_id', '=', $db_participant->id );
    $sql = 'DELETE FROM queue_has_participant '.$delete_mod->get_sql();
    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( $sql );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'time_specific', '=', true );
    $modifier->order( 'id' );
    foreach( static::select_objects( $modifier ) as $db_queue )
    {
      if( static::$debug ) $queue_time = util::get_elapsed_time();

      // sql used by all insert statements below
      $base_sql = sprintf(
        'INSERT INTO queue_has_participant( '.
          'participant_id, queue_id, site_id, qnaire_id, start_qnaire_date ) '.
        'SELECT queue_has_participant.participant_id, %s, queue_has_participant.site_id, '.
        'queue_has_participant.qnaire_id, start_qnaire_date '.
        'FROM queue_has_participant',
        static::db()->format_string( $db_queue->id ) );
      
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'queue_has_participant.queue_id', '=', $db_queue->parent_queue_id );
      if( !is_null( $db_participant ) )
        $modifier->where( 'queue_has_participant.participant_id', '=', $db_participant->id );

      if( in_array( $db_queue->name,
        array( 'outside calling time', 'callback', 'new participant', 'old participant' ) ) )
      {
        // create temporary table containing all participants in queue not belonging to sibling tables
        $sub_sel = lib::create( 'database\select' );
        $sub_sel->add_column( 'participant_id' );
        $sub_sel->from( 'queue_has_participant' );
        $sub_mod = lib::create( 'database\modifier' );
        $sub_mod->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
        $sub_mod->where( 'queue.id', '=', $db_queue->parent_queue_id );
        $sub_mod->or_where( 'queue.parent_queue_id', '=', $db_queue->parent_queue_id );
        $sub_mod->group( 'participant_id' );
        $sub_mod->having( 'COUNT(*)', '=', 1 );
        $sql = sprintf(
          'CREATE TEMPORARY TABLE IF NOT EXISTS sub_queue_has_participant %s %s',
          $sub_sel->get_sql(),
          $sub_mod->get_sql() );
        static::db()->execute( 'DROP TABLE IF EXISTS sub_queue_has_participant' );
        static::db()->execute( $sql );
        static::db()->execute(
          'ALTER TABLE sub_queue_has_participant ADD INDEX fk_participant_id ( participant_id )' );

        $modifier->left_join( 'setting', 'queue_has_participant.site_id', 'setting.site_id' );
        $modifier->join( 'sub_queue_has_participant',
          'queue_has_participant.participant_id', 'sub_queue_has_participant.participant_id' );

        $modifier->join( 'participant_first_address',
          'queue_has_participant.participant_id', 'participant_first_address.participant_id' );
        $modifier->join( 'address', 'participant_first_address.address_id', 'address.id' );

        $localtime = localtime( time(), true );
        $offset = $localtime['tm_isdst']
                ? 'address.timezone_offset + address.daylight_savings'
                : 'address.timezone_offset';
        $left = sprintf( 'TIME( UTC_TIMESTAMP() + INTERVAL ( %s )*60 MINUTE )', $offset );

        $modifier->where_bracket( true, false, 'outside calling time' == $db_queue->name );
        $modifier->where( 'calling_start_time', '<=', $left, false );
        $modifier->where( 'calling_end_time', '>', $left, false );
        $modifier->where_bracket( false );

        if( 'outside calling time' != $db_queue->name )
        {
          // we need to join to the interview table
          $join_mod = lib::create( 'database\modifier' );
          $join_mod->where( 'queue_has_participant.participant_id', '=', 'interview.participant_id', false );
          $join_mod->where( 'queue_has_participant.qnaire_id', '=', 'interview.qnaire_id', false );
          $modifier->join_modifier( 'interview', $join_mod, 'left' );

          // link to callback table
          // (by design, there can only ever one unassigned callback per participant)
          $join_mod = lib::create( 'database\modifier' );
          $join_mod->where( 'interview.id', '=', 'callback.interview_id', false );
          $join_mod->where( 'callback.assignment_id', '=', NULL );

          if( 'callback' == $db_queue->name )
          {
            $modifier->join_modifier( 'callback', $join_mod );
          }
          else
          {
            // Make sure there is no unassigned callback
            $modifier->join_modifier( 'callback', $join_mod, 'left' );
            $modifier->where( 'callback.id', '=', NULL );

            if( 'new participant' == $db_queue->name )
            {
              // If there is a start_qnaire_date then the current qnaire has never been
              // started, the exception is for participants who have never been assigned
              $modifier->left_join( 'participant_last_interview',
                'queue_has_participant.participant_id', 'participant_last_interview.participant_id' );
              $modifier->left_join( 'interview_last_assignment',
                'participant_last_interview.interview_id', 'interview_last_assignment.interview_id' );
              $modifier->left_join( 'assignment',
                'interview_last_assignment.assignment_id', 'assignment.id' );

              $modifier->where_bracket( true );
              $modifier->where( 'queue_has_participant.start_qnaire_date', '!=', NULL );
              $modifier->or_where( 'assignment.id', '=', NULL );
              $modifier->where_bracket( false );
            }
            else // old participant
            {
              // if there is no start_qnaire_date then the current qnaire has been started
              $modifier->where( 'start_qnaire_date', '=', NULL );

              // make sure the current interview's qnaire matches the effective qnaire,
              // otherwise this participant has never been assigned
              $modifier->join( 'participant_last_interview',
                'queue_has_participant.participant_id', 'participant_last_interview.participant_id' );
              $modifier->left_join( 'interview',
                'participant_last_interview.interview_id', 'current_interview.id', 'current_interview' );
              $modifier->where( 'queue_has_participant.qnaire_id', '=', 'current_interview.qnaire_id', false );
            }
          }
        }

        static::db()->execute( sprintf( '%s %s', $base_sql, $modifier->get_sql() ) );
      }
      else if( in_array( $db_queue->name,
        array( 'contacted', 'busy', 'fax', 'no answer', 'not reached', 'hang up', 'soft refusal' ) ) )
      {
        $modifier->join( 'participant_last_interview',
          'queue_has_participant.participant_id', 'participant_last_interview.participant_id' );
        $modifier->join( 'interview_last_assignment',
          'participant_last_interview.interview_id', 'interview_last_assignment.interview_id' );
        $modifier->join( 'assignment_last_phone_call',
          'interview_last_assignment.assignment_id', 'assignment_last_phone_call.assignment_id' );
        $modifier->join( 'phone_call', 'assignment_last_phone_call.phone_call_id', 'phone_call.id' );
        if( 'not reached' == $db_queue->name )
        {
          $modifier->where( 'phone_call.status', 'IN',
            array( 'machine message', 'machine no message', 'disconnected', 'wrong number', 'not reached' ) );
        }
        else
        {
          $modifier->where( 'phone_call.status', '=', $db_queue->name );
        }

        static::db()->execute( sprintf( '%s %s', $base_sql, $modifier->get_sql() ) );
      }
      // populate appointment upcomming/assignable/missed queues
      else if( ' appointment' == substr( $db_queue->name, -12 ) )
      {
        $modifier->left_join( 'setting', 'queue_has_participant.site_id', 'setting.site_id' );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'queue_has_participant.participant_id', '=', 'interview.participant_id', false );
        $join_mod->where( 'queue_has_participant.qnaire_id', '=', 'interview.qnaire_id', false );
        $modifier->join_modifier( 'interview', $join_mod );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'interview.id', '=', 'appointment.interview_id', false );
        $join_mod->where( 'appointment.assignment_id', '=', NULL );
        $modifier->join_modifier( 'appointment', $join_mod );

        $pre_call = 'appointment.datetime - INTERVAL IFNULL( pre_call_window, 0 ) MINUTE';
        $post_call = 'appointment.datetime + INTERVAL IFNULL( post_call_window, 0 ) MINUTE';
        if( 'upcoming appointment' == $db_queue->name )
        {
          $modifier->where( 'UTC_TIMESTAMP()', '<', $pre_call, false );
        }
        else if( 'assignable appointment' == $db_queue->name )
        {
          $modifier->where( 'UTC_TIMESTAMP()', '>=', $pre_call, false );
          $modifier->where( 'UTC_TIMESTAMP()', '<=', $post_call, false );
        }
        else if( 'missed appointment' == $db_queue->name )
        {
          $modifier->where( 'UTC_TIMESTAMP()', '>', $post_call, false );
        }

        static::db()->execute( sprintf( '%s %s', $base_sql, $modifier->get_sql() ) );
      }
      // populate callback upcoming/assignable queues
      else if( ' callback' == substr( $db_queue->name, -9 ) )
      {
        $modifier->left_join( 'setting', 'queue_has_participant.site_id', 'setting.site_id' );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'queue_has_participant.participant_id', '=', 'interview.participant_id', false );
        $join_mod->where( 'queue_has_participant.qnaire_id', '=', 'interview.qnaire_id', false );
        $modifier->join_modifier( 'interview', $join_mod );

        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'interview.id', '=', 'callback.interview_id', false );
        $join_mod->where( 'callback.assignment_id', '=', NULL );
        $modifier->join_modifier( 'callback', $join_mod );

        $pre_call = 'callback.datetime - INTERVAL IFNULL( pre_call_window, 0 ) MINUTE';
        $test = 'upcoming callback' == $db_queue->name ? '<' : '>=';
        $modifier->where( 'UTC_TIMESTAMP()', $test, $pre_call, false );

        static::db()->execute( sprintf( '%s %s', $base_sql, $modifier->get_sql() ) );
      }
      // populate "last call waiting" queues
      else if( ' waiting' == substr( $db_queue->name, -8 ) || ' ready' == substr( $db_queue->name, -6 ) )
      {
        $modifier->left_join( 'setting', 'queue_has_participant.site_id', 'setting.site_id' );

        $modifier->join( 'participant_last_interview',
          'queue_has_participant.participant_id', 'participant_last_interview.participant_id', false );
        $modifier->join( 'interview_last_assignment',
          'participant_last_interview.interview_id', 'interview_last_assignment.interview_id', false );
        $modifier->join( 'assignment_last_phone_call',
          'interview_last_assignment.assignment_id', 'assignment_last_phone_call.assignment_id', false );
        $modifier->join( 'phone_call', 'phone_call.id', 'assignment_last_phone_call.phone_call_id', false );

        $after_call = sprintf(
          'phone_call.end_datetime + INTERVAL IFNULL( %s_wait, 0 ) MINUTE',
          str_replace( ' ', '_', substr( $db_queue->name, 0, strrpos( $db_queue->name, ' ' ) ) ) );
        $test = ' waiting' == substr( $db_queue->name, -8 ) ? '<' : '>=';
        $modifier->where( 'UTC_TIMESTAMP()', $test, $after_call, false );

        static::db()->execute( sprintf( '%s %s', $base_sql, $modifier->get_sql() ) );
      }
      else
      {
        $semaphore->release();
        throw lib::create( 'exception\runtime',
          sprintf( 'No rules to populate time-specific queue "%s"', $db_queue->name ),
          __METHOD__ );
      }

      if( static::$debug ) log::debug( sprintf(
        '(Queue) "%s" build time: %0.2f', $db_queue->name, util::get_elapsed_time() - $queue_time ) );
    }
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total queue build time: %0.2f', util::get_elapsed_time() - $time ) );

    $semaphore->release();
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total repopulate_time_specific() time: %0.2f', util::get_elapsed_time() - $total_time ) );
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
   * @return associative array
   * @throws exception\argument
   * @access protected
   * @static
   */
  protected static function get_query_parts( $queue )
  {
    // make sure the queue_list_cache has been created
    static::create_queue_list_cache();

    // start by getting the queue and parent queue objects from the cache
    $db_queue = self::$queue_list_cache[$queue]['object'];
    if( is_null( $db_queue ) ) // invalid queue name
      throw lib::create( 'exception\runtime',
        sprintf( 'Cannot find queue named "%s"', $queue ), __METHOD__ );
    $db_parent_queue = self::$queue_list_cache[$queue]['parent'];

    // if this is a time-specific queue then return a query which will return no rows
    if( $db_queue->time_specific )
      return array(
        'from' => array( 'participant_for_queue' ),
        'join' => array( // always join to the participant site table
          'LEFT JOIN participant_for_queue_participant_site '.
          'ON participant_for_queue_participant_site.id = participant_for_queue.id ' ),
        'where' => array( 'false' ) );

    $participant_class_name = lib::get_class_name( 'database\participant' );

    // get the parent queue's query parts
    if( !is_null( $db_parent_queue ) ) $parts = self::get_query_parts( $db_parent_queue->name );

    // now determine the sql parts for the given queue
    if( 'all' == $queue )
    {
      // NOTE: when updating this query database\participant::get_queue_data()
      //       should also be updated as it performs a very similar query
      $parts = array(
        'from' => array( 'participant_for_queue' ),
        'join' => array( // always join to the participant site table
          'LEFT JOIN participant_for_queue_participant_site '.
          'ON participant_for_queue_participant_site.id = participant_for_queue.id ' ),
        'where' => array() );
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
        $parts['where'][] =
          '( '.
            'participant_active = false '.
            'OR participant_state_id IS NOT NULL '.
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
        $parts['where'][] = 'IFNULL( last_consent_accept, 1 ) = 1';
        $parts['where'][] = 'participant_state_id IS NOT NULL';
      }
      else if( 'eligible' == $queue )
      {
        // active participant who does not have a "final" state and has at least one phone number
        $parts['where'][] = 'participant_active = true';
        $parts['where'][] = 'participant_state_id IS NULL';
        $parts['where'][] = 'IFNULL( last_consent_accept, 1 ) = 1';
      }
      else if( 'qnaire' == $queue )
      {
        // no additional parts needed
      }
      // we must process all of the qnaire queue's direct children as a whole
      else if( in_array( $queue,
        array( 'qnaire waiting', 'assigned', 'appointment', 'quota disabled' ) ) )
      {
        if( 'qnaire waiting' == $queue )
        {
          // the current qnaire cannot start before start_qnaire_date
          $parts['where'][] = 'IFNULL( start_qnaire_date, UTC_TIMESTAMP() ) > UTC_TIMESTAMP()';
        }
        else
        {
          // the qnaire is ready to start if the start_qnaire_date is null or we have reached that date
          $parts['where'][] = 'IFNULL( start_qnaire_date, UTC_TIMESTAMP() ) <= UTC_TIMESTAMP()';

          if( 'assigned' == $queue )
          {
            // participants who are currently assigned
            $parts['where'][] =
              '( current_assignment_id IS NOT NULL AND current_assignment_end_datetime IS NULL )';
          }
          else
          {
            // participants who are NOT currently assigned
            $parts['where'][] =
              '( current_assignment_id IS NULL OR current_assignment_end_datetime IS NOT NULL )';

            if( 'appointment' == $queue )
            {
              // link to appointment table and make sure the appointment hasn't been assigned
              // (by design, there can only ever be one unassigned appointment per interview)
              $parts['from'][] = 'appointment';
              $parts['where'][] =
                'appointment.interview_id = participant_for_queue.current_interview_id';
              $parts['where'][] = 'appointment.assignment_id IS NULL';
            }
            else
            {
              // Make sure there is no unassigned appointment.  By design there can only be one of
              // per interview, so if the appointment is null then the interview has no pending
              // appointments.
              $parts['join'][] =
                'LEFT JOIN appointment '.
                'ON appointment.interview_id = participant_for_queue.current_interview_id '.
                'AND appointment.assignment_id IS NULL';
              $parts['where'][] = 'appointment.id IS NULL';

              // join to the first_address table based on participant id
              $parts['join'][] =
                'LEFT JOIN participant_for_queue_first_address '.
                'ON participant_for_queue_first_address.id = participant_for_queue.id ';

              // join to the quota table based on site, region, sex and age group
              $parts['join'][] = 
                'LEFT JOIN quota '.
                'ON quota.site_id = participant_site_id '.
                'AND quota.region_id = primary_region_id '.
                'AND quota.sex = participant_sex '.
                'AND quota.age_group_id = participant_age_group_id '.
                'LEFT JOIN qnaire_has_quota '.
                'ON quota.id = qnaire_has_quota.quota_id '.
                'AND effective_qnaire_id = qnaire_has_quota.qnaire_id';

              if( 'quota disabled' == $queue )
              {
                // who belong to a quota which is disabled (row in qnaire_has_quota found)
                $parts['where'][] = 'qnaire_has_quota.quota_id IS NOT NULL';
                // and who are not marked to override quota
                $parts['where'][] = 'participant_override_quota = false';
                $parts['where'][] = 'source_override_quota = false';
              }
            }
          }
        }
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
    // start by making sure the query list has been generated
    if( 0 == count( self::$query_list ) ) self::generate_query_list();

    $sql = self::$query_list[ $this->name ];
    $sql = preg_replace( '/\<SELECT_PARTICIPANT\>/', $select_participant_sql, $sql, 1 );
    $sql = str_replace( '<SELECT_PARTICIPANT>', 'participant_for_queue.id', $sql );

    return $sql;
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
    $application_id = lib::create( 'business\session' )->get_application()->id;

    if( static::$participant_for_queue_created ) return;

    // build first_qnaire_event_type table
    $sql = 
      'CREATE TEMPORARY TABLE IF NOT EXISTS first_qnaire_event_type '.
      'SELECT qnaire.id AS qnaire_id, '.
             'IF( qnaire_has_event_type.qnaire_id IS NULL, 0, count(*) ) AS total, '.
             'GROUP_CONCAT( qnaire_has_event_type.event_type_id ) AS list '.
      'FROM qnaire '.
      'LEFT JOIN qnaire_has_event_type ON qnaire.id = qnaire_has_event_type.qnaire_id '.
      'GROUP BY qnaire.id';
    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue' );
    static::db()->execute( $sql );
    static::db()->execute( 'ALTER TABLE first_qnaire_event_type ADD INDEX fk_qnaire_id ( qnaire_id )' );

    // build next_qnaire_event_type table
    $sql = 
      'CREATE TEMPORARY TABLE IF NOT EXISTS next_qnaire_event_type '.
      'SELECT qnaire.id AS qnaire_id, '.
             'IF( qnaire_has_event_type.qnaire_id IS NULL, 0, count(*) ) AS total, '.
             'GROUP_CONCAT( qnaire_has_event_type.event_type_id ) AS list '.
      'FROM qnaire '.
      'LEFT JOIN qnaire_has_event_type ON qnaire.id = qnaire_has_event_type.qnaire_id '.
      'GROUP BY qnaire.id';
    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue' );
    static::db()->execute( $sql );
    static::db()->execute( 'ALTER TABLE next_qnaire_event_type ADD INDEX fk_qnaire_id ( qnaire_id )' );

    // build participant_for_queue table
    $sql = sprintf( 'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue '.
                    static::$participant_for_queue_sql,
                    static::db()->format_string( $application_id ) );
    if( !is_null( $db_participant ) )
      $sql .= sprintf( ' WHERE participant.id = %s ',
                       static::db()->format_string( $db_participant->id ) );

    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE participant_for_queue '.
        'ADD INDEX fk_id ( id ), '.
        'ADD INDEX fk_participant_sex ( participant_sex ), '.
        'ADD INDEX fk_participant_age_group_id ( participant_age_group_id ), '.
        'ADD INDEX fk_participant_active ( participant_active ), '.
        'ADD INDEX fk_participant_state_id ( participant_state_id ), '.
        'ADD INDEX fk_effective_qnaire_id ( effective_qnaire_id ), '.
        'ADD INDEX fk_last_consent_accept ( last_consent_accept ), '.
        'ADD INDEX fk_current_assignment_id ( current_assignment_id ), '.
        'ADD INDEX dk_primary_region_id ( primary_region_id )' );
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Building queue_has_participant temp table: %0.2f', util::get_elapsed_time() - $time ) );

    // build participant_for_queue_participant_site
    $sql = sprintf(
      'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_participant_site '.
      'SELECT participant_id AS id, participant_site.site_id AS participant_site_id, '.
             'calling_start_time, calling_end_time '.
      'FROM participant_site '.
      'LEFT JOIN setting ON participant_site.site_id = setting.site_id '.
      'WHERE application_id = %s ',
      static::db()->format_string( $application_id ) );
    if( !is_null( $db_participant ) )
      $sql .= sprintf( 'AND participant_id = %s ',
                       static::db()->format_string( $db_participant->id ) );

    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue_participant_site' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE participant_for_queue_participant_site '.
        'ADD INDEX dk_participant_id_site_id ( id, participant_site_id )' );
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Building participant_for_queue_participant_site temp table: %0.2f',
      util::get_elapsed_time() - $time ) );

    // build participant_for_queue_first_address table
    $sql = sprintf(
      'CREATE TEMPORARY TABLE IF NOT EXISTS participant_for_queue_first_address '.
      'SELECT participant.id AS id, '.
             'address.timezone_offset AS first_address_timezone_offset, '.
             'address.daylight_savings AS first_address_daylight_savings '.
      'FROM participant_first_address '.
      'JOIN application_has_participant '.
      'ON participant_first_address.participant_id = application_has_participant.participant_id '.
      'AND application_has_participant.application_id = %s '.
      'AND application_has_participant.datetime IS NOT NULL '.
      'LEFT JOIN participant ON participant_first_address.participant_id = participant.id '.
      'LEFT JOIN address '.
      'ON participant_first_address.address_id = address.id ',
      static::db()->format_string( $application_id ) );
    if( !is_null( $db_participant ) )
      $sql .= sprintf( 'WHERE participant.id = %s ',
                       static::db()->format_string( $db_participant->id ) );

    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( 'DROP TABLE IF EXISTS participant_for_queue_first_address' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE participant_for_queue_first_address '.
        'ADD INDEX dk_id ( id ), '.
        'ADD INDEX dk_first_address_timezone_offset ( first_address_timezone_offset ), '.
        'ADD INDEX dk_first_address_daylight_savings ( first_address_daylight_savings )' );
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Building participant_for_queue_first_address temp table: %0.2f',
      util::get_elapsed_time() - $time ) );

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
      $queue_mod->where( 'time_specific', '=', false );
      $queue_mod->order( 'id' );
      $queue_list = array();
      foreach( static::select_objects( $queue_mod ) as $db_queue )
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
              self::$queue_list_cache[$db_queue->name]['parent'] = $queue_cache['object'];
              break;
            }
          }
        }
      }
    }
  }

  /**
   * Whether or not to show debug information
   * @var boolean
   * @access public
   * @static
   */
  public static $debug = false;

  /**
   * Whether the participant_for_queue temporary table has been created.
   * @var boolean
   * @access protected
   * @static
   */
  protected static $participant_for_queue_created = false;

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
participant.active AS participant_active,
participant.sex AS participant_sex,
participant.age_group_id AS participant_age_group_id,
participant.state_id AS participant_state_id,
participant.override_quota AS participant_override_quota,
source.override_quota AS source_override_quota,
primary_region.id AS primary_region_id,
last_consent.accept AS last_consent_accept,
current_interview.id AS current_interview_id,
current_qnaire.id AS current_qnaire_id,
current_assignment.id AS current_assignment_id,
current_assignment.end_datetime AS current_assignment_end_datetime,
IF
(
  current_qnaire.id IS NULL,
  first_qnaire.id,
  IF( current_interview.end_datetime IS NOT NULL, next_qnaire.id, current_qnaire.id )
) AS effective_qnaire_id,
(
  IF
  (
    current_interview.id IS NULL,
    IF
    (
      first_qnaire_event_type.total,
      IFNULL( first_event.datetime, UTC_TIMESTAMP() ) + INTERVAL first_qnaire.delay WEEK,
      NULL
    ),
    IF
    (
      current_interview.end_datetime IS NOT NULL,
      GREATEST
      (
        IFNULL( next_event.datetime, "" ),
        IFNULL( prev_assignment.end_datetime, "" )
      ) + INTERVAL next_qnaire.delay WEEK,
      NULL
    )
  )
) AS start_qnaire_date

FROM participant
JOIN application_has_participant
ON participant.id = application_has_participant.participant_id
AND application_has_participant.datetime IS NOT NULL
JOIN application
ON application_has_participant.application_id = application.id
AND application.id = %s

JOIN source
ON participant.source_id = source.id

LEFT JOIN participant_primary_address
ON participant.id = participant_primary_address.participant_id
LEFT JOIN address AS primary_address
ON participant_primary_address.address_id = primary_address.id
LEFT JOIN region AS primary_region
ON primary_address.region_id = primary_region.id

JOIN participant_last_consent
ON participant.id = participant_last_consent.participant_id
LEFT JOIN consent AS last_consent
ON last_consent.id = participant_last_consent.consent_id

LEFT JOIN participant_last_interview AS participant_current_interview
ON participant.id = participant_current_interview.participant_id
LEFT JOIN interview AS current_interview
ON participant_current_interview.interview_id = current_interview.id
LEFT JOIN qnaire AS current_qnaire
ON current_interview.qnaire_id = current_qnaire.id
LEFT JOIN interview_last_assignment AS interview_current_assignment
ON current_interview.id = interview_current_assignment.interview_id
LEFT JOIN assignment AS current_assignment
ON interview_current_assignment.assignment_id = current_assignment.id

CROSS JOIN qnaire AS first_qnaire
ON first_qnaire.rank = 1
LEFT JOIN first_qnaire_event_type
ON first_qnaire.id = first_qnaire_event_type.qnaire_id
LEFT JOIN event AS first_event
ON participant.id = first_event.participant_id
AND IF(
  first_qnaire_event_type.total,
  first_event.event_type_id IN( first_qnaire_event_type.list ),
  false
)

LEFT JOIN qnaire AS next_qnaire
ON next_qnaire.rank = ( current_qnaire.rank + 1 )
LEFT JOIN next_qnaire_event_type
ON next_qnaire.id = next_qnaire_event_type.qnaire_id
LEFT JOIN event AS next_event
ON participant.id = next_event.participant_id
AND IF(
  next_qnaire_event_type.total,
  next_event.event_type_id IN( next_qnaire_event_type.list ),
  false
)

LEFT JOIN qnaire AS prev_qnaire
ON next_qnaire.prev_qnaire_id = prev_qnaire.id
LEFT JOIN interview AS prev_interview
ON prev_interview.qnaire_id = prev_qnaire.id
AND prev_interview.participant_id = participant.id
LEFT JOIN interview_last_assignment AS interview_prev_assignment
ON prev_interview.id = interview_prev_assignment.interview_id
LEFT JOIN assignment AS prev_assignment
ON interview_prev_assignment.assignment_id = prev_assignment.id
SQL;
}
