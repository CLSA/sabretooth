<?php
/**
 * queue.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * queue: record
 */
class queue extends \cenozo\database\record
{
  /**
   * Returns the queue's parent queue
   * 
   * @return database\queue
   * @access public
   */
  public function get_parent_queue()
  {
    return is_null( $this->parent_queue_id ) ? NULL : new static( $this->parent_queue_id );
  }

  /**
   * Adds a participant to the list of those to run repopulate() on during shutdown
   * 
   * @param database\participant $db_participant If NULL then all participants will be included
   * @access public
   * @static
   */
  public static function delayed_repopulate( $db_participant = NULL )
  {
    if( 'all' != static::$delayed_repopulate_list )
    {
      if( is_null( $db_participant ) ) static::$delayed_repopulate_list = 'all';
      else if( !array_key_exists( $db_participant->uid, static::$delayed_repopulate_list ) )
        static::$delayed_repopulate_list[$db_participant->uid] = $db_participant;
    }
  }

  /**
   * Adds a participant to the list of those to run repopulate_time() on during shutdown
   * 
   * @param database\participant $db_participant If NULL then all participants will be included
   * @access public
   * @static
   */
  public static function delayed_repopulate_time( $db_participant = NULL )
  {
    if( 'all' != static::$delayed_repopulate_time_list )
    {
      if( is_null( $db_participant ) ) static::$delayed_repopulate_time_list = 'all';
      else if( !array_key_exists( $db_participant->uid, static::$delayed_repopulate_time_list ) )
        static::$delayed_repopulate_time_list[$db_participant->uid] = $db_participant;
    }
  }

  /**
   * Executes all delayed repopulate() and repopulate_time() methods
   * 
   * @access public
   * @static
   */
  public static function execute_delayed()
  {
    static::$temporary_tables_created = false; // force rebuild temporary tables
    if( 'all' == static::$delayed_repopulate_list )
      static::repopulate();
    else foreach( static::$delayed_repopulate_list as $db_participant )
      static::repopulate( $db_participant );
    static::$delayed_repopulate_list = array();

    if( 'all' == static::$delayed_repopulate_time_list )
      static::repopulate_time();
    else foreach( static::$delayed_repopulate_time_list as $db_participant )
      static::repopulate_time( $db_participant );
    static::$delayed_repopulate_time_list = array();
  }

  /**
   * Returns the time since the last time repopulate() was called
   * 
   * @return datetime
   * @access public
   * @static
   */
  public static function get_interval_since_last_repopulate()
  {
    $select = lib::create( 'database\select' );
    $select->add_column( 'MIN( update_timestamp )', 'update_datetime', false, 'timestamp' );
    $select->from( 'queue_has_participant' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'queue_id', 'IN', 'SELECT id FROM queue WHERE time_specific = false', false );

    $datetime = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return $datetime ? util::get_interval( $datetime ) : NULL;
  }

  /**
   * Retuns the time since the last repopulate_time() was called
   * 
   * @return datetime
   * @access public
   * @static
   */
  public static function get_interval_since_last_repopulate_time()
  {
    $select = lib::create( 'database\select' );
    $select->add_column( 'MIN( update_timestamp )', 'update_datetime', false, 'timestamp' );
    $select->from( 'queue_has_participant' );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'queue_id', 'IN', 'SELECT id FROM queue WHERE time_specific = true', false );

    $datetime = static::db()->get_one( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
    return $datetime ? util::get_interval( $datetime ) : NULL;
  }

  /**
   * Generates the query list.
   * 
   * This method is called internally by the {@link repopulate} method in order to generate
   * the proper SQL to complete the repopulate of queues.
   * @access protected
   * @static
   */
  protected static function generate_query_object_list()
  {
    if( 0 == count( self::$query_object_list ) )
    {
      // make sure the queue_list_cache has been created and loop through it
      static::create_queue_list_cache();
      foreach( array_keys( self::$queue_list_cache ) as $queue )
      {
        $select = lib::create( 'database\select' );
        $modifier = lib::create( 'database\modifier' );

        // build the select and modifier and store the resulting sql
        self::prepare_queue_query( $queue, $select, $modifier );
        self::$query_object_list[$queue] = array( 'select' => $select, 'modifier' => $modifier );
      }
    }
  }

  /**
   * Re-populates a queue's participant list
   * 
   * This method is used to pupulate all non-time-specific queues.
   * Only non time-specific queues are affected by this function, to populate time-specific
   * queues use the repopulate_time() method instead.
   * @param database\participant $db_participant If provided then only that participant will be affected by the operation.
   * @access public
   * @static
   */
  public static function repopulate( $db_participant = NULL )
  {
    $interview_class_name = lib::get_class_name( 'database\interview' );

    if( static::$debug ) $total_time = util::get_elapsed_time();

    // block with a semaphore
    $semaphore = lib::create( 'business\semaphore', __METHOD__ );
    $semaphore->acquire();

    // update all web-based interviews
    $qnaire_list = $interview_class_name::process_completed_web_interviews( $db_participant );

    // make sure the temporary table exists
    static::build_temporary_tables( $db_participant );

    // delete queue_has_participant records
    $sql = is_null( $db_participant )
         ? 'TRUNCATE queue_has_participant'
         : sprintf( 'DELETE FROM queue_has_participant WHERE participant_id = %s',
                    static::db()->format_string( $db_participant->id ) );
    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( $sql );

    // re-generate the query object list
    self::generate_query_object_list();

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'time_specific', '=', false );
    $modifier->order( 'id' );
    foreach( static::select_objects( $modifier ) as $db_queue )
    {
      if( static::$debug ) $queue_time = util::get_elapsed_time();

      $queue_sel = static::$query_object_list[ $db_queue->name ]['select'];
      $queue_mod = static::$query_object_list[ $db_queue->name ]['modifier'];
      if( !is_null( $db_participant ) ) $queue_mod->where( 'temp_participant.id', '=', $db_participant->id );

      $queue_sel->set_distinct( true );
      $queue_sel->add_table_column( 'temp_participant', 'id' );
      $queue_sel->add_constant( $db_queue->id );
      $queue_sel->add_column( 'participant_site_id', NULL, false );
      $queue_sel->add_column( 'effective_qnaire_id', NULL, false );
      $queue_sel->add_column( 'start_qnaire_date', NULL, false );
      $queue_sel->add_constant( NULL, 'create_timestamp' );

      static::db()->execute( sprintf(
        'REPLACE INTO queue_has_participant( '.
          "participant_id, queue_id, site_id, qnaire_id, start_qnaire_date, create_timestamp )\n%s %s",
        $queue_sel->get_sql(), $queue_mod->get_sql() ) );

      if( static::$debug ) log::debug( sprintf(
        '(Queue) "%s" build time%s: %0.2f',
        $db_queue->name,
        is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
        util::get_elapsed_time() - $queue_time ) );
    }

    // send all invitiations to new web-based interviews
    foreach( $qnaire_list as $qnaire_id => $uid_list )
    {
      // only include participants who are now in the web version queue
      $select = lib::create( 'database\select' );
      $select->from( 'queue_has_participant' );
      $select->add_table_column( 'participant', 'uid' );
      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->join( 'participant', 'queue_has_participant.participant_id', 'participant.id' );
      $modifier->where( 'participant.uid', 'IN', $uid_list );
      $modifier->where( 'queue.name', '=', 'web version' );
      $uid_list = static::db()->get_col( sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() ) );
      if( 0 < count( $uid_list ) )
      {
        $db_qnaire = lib::create( 'database\qnaire', $qnaire_id );
        $db_qnaire->launch_web_interviews( NULL, $uid_list );
      }
    }

    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total queue build time%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $time ) );

    $semaphore->release();

    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total repopulate() time%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $total_time ) );
  }

  /**
   * Re-populates all time-specific queue
   * 
   * This method is used to populate queues which are dependent on the exact time.
   * Only time-specific queues are affected by this function, to populate non time-specific
   * queues use the repopulate() static method instead.
   * @param database\participant $db_participant If provided then only that participant will be affected by the operation.
   * @access public
   */
  public static function repopulate_time( $db_participant = NULL )
  {
    if( static::$debug ) $total_time = util::get_elapsed_time();

    // block with a semaphore
    $semaphore = lib::create( 'business\semaphore', __METHOD__ );
    $semaphore->acquire();

    // delete queue_has_participant records
    $delete_mod = lib::create( 'database\modifier' );
    $delete_mod->where( 'queue_id', 'IN', 'SELECT id FROM queue WHERE time_specific = true', false );
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

      $select = lib::create( 'database\select' );
      $select->add_column( 'participant_id' );
      $select->add_constant( $db_queue->id );
      $select->add_column( 'site_id' );
      $select->add_column( 'qnaire_id' );
      $select->add_column( 'start_qnaire_date' );
      $select->add_constant( NULL, 'create_timestamp' );
      $select->from( 'queue_has_participant' );

      // sql used by all insert statements below
      $base_sql = sprintf(
        'REPLACE INTO queue_has_participant( '.
          "participant_id, queue_id, site_id, qnaire_id, start_qnaire_date, create_timestamp )\n%s",
        $select->get_sql() );

      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'participant', 'queue_has_participant.participant_id', 'participant.id' );
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

          if( 'callback' == $db_queue->name )
          {
            // Make sure there is a callback
            $modifier->where( 'participant.callback', '!=', NULL );
          }
          else
          {
            // Make sure there is no callback
            $modifier->where( 'participant.callback', '=', NULL );

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
        $join_mod->where( 'appointment.outcome', '=', NULL );
        $modifier->join_modifier( 'appointment', $join_mod );

        $modifier->join( 'vacancy', 'appointment.start_vacancy_id', 'vacancy.id' );

        $pre_call = 'vacancy.datetime - INTERVAL IFNULL( pre_call_window, 0 ) MINUTE';
        $post_call = 'vacancy.datetime + INTERVAL IFNULL( post_call_window, 0 ) MINUTE';
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
        $modifier->where(
          'UTC_TIMESTAMP()',
          'upcoming callback' == $db_queue->name ? '<' : '>=',
          'participant.callback',
          false );

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
        '(Queue) "%s" build time%s: %0.2f',
        $db_queue->name,
        is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
        util::get_elapsed_time() - $queue_time ) );
    }
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total queue build time%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $time ) );

    $semaphore->release();
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Total repopulate_time() time%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $total_time ) );
  }

  /**
   * Get whether this queue is related to an appointment
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
   * Prepares select and modifier objects according to the query generated for a particular queue.
   * @param string $queue The name of the queue
   * @param database\select A select object which will be set according to the given queue
   * @param database\modifier A modifier object which will be set according to the given queue
   * @throws exception\argument
   * @access protected
   * @static
   */
  protected static function prepare_queue_query( $queue, $select, $modifier )
  {
    // make sure the queue_list_cache has been created
    static::create_queue_list_cache();

    // make sure the stratum_id_list_cache has been created
    static::create_stratum_id_list_cache();

    // start by getting the queue and parent queue objects from the cache
    $db_queue = self::$queue_list_cache[$queue]['object'];
    if( is_null( $db_queue ) ) // invalid queue name
      throw lib::create( 'exception\runtime',
        sprintf( 'Cannot find queue named "%s"', $queue ), __METHOD__ );
    $db_parent_queue = self::$queue_list_cache[$queue]['parent'];

    // if this is a time-specific queue then return a query which will return no rows
    if( $db_queue->time_specific )
    {
      $modifier->where( 'true', '=', false );
      return;
    }

    // get the parent queue's details
    if( !is_null( $db_parent_queue ) ) self::prepare_queue_query( $db_parent_queue->name, $select, $modifier );

    // now process given the queue
    if( 'all' == $queue )
    {
      // NOTE: when updating this query database\participant::get_queue_data()
      //       should also be updated as it performs a very similar query
      $select->from( 'temp_participant' );
      $modifier->left_join(
        'temp_participant_participant_site', 'temp_participant_participant_site.id', 'temp_participant.id' );
      return;
    }

    if( 'finished' == $queue )
    {
      // no effective_qnaire_id means no qnaires left to complete
      $modifier->where( 'effective_qnaire_id', '=', NULL );
      return;
    }

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'qnaire_has_hold_type.qnaire_id', '=', 'effective_qnaire_id', false );
    $join_mod->where( 'qnaire_has_hold_type.hold_type_id', '=', 'last_hold_type_id', false );
    $modifier->join_modifier( 'qnaire_has_hold_type', $join_mod, 'left' );

    // effective_qnaire_id is the either the next qnaire to work on or the one in progress
    $modifier->where( 'effective_qnaire_id', '!=', NULL );
    if( 'ineligible' == $queue )
    {
      // ineligible means either not enrolled, not participating, in a hold, trace or proxy (unless the hold-type is overridden)
      $modifier->where_bracket( true );
      $modifier->where( 'participant_exclusion_id', '!=', NULL );
      $modifier->or_where( 'study_consent_accept', '=', false );

      $modifier->where_bracket( true, true );
      $modifier->where( 'qnaire_has_hold_type.hold_type_id', '=', NULL );
      $modifier->where( 'last_hold_type_type', '!=', NULL );
      $modifier->where_bracket( false );

      $modifier->or_where( 'last_trace_type_name', '!=', NULL );
      $modifier->or_where( 'last_proxy_type_name', '!=', NULL );
      $modifier->where_bracket( false );
      return;
    }

    if( 'not enrolled' == $queue )
    {
      $modifier->where( 'participant_exclusion_id', '!=', NULL );
      return;
    }

    if( 'not participating' == $queue )
    {
      $modifier->where( 'participant_exclusion_id', '=', NULL );
      $modifier->where( 'study_consent_accept', '=', false );
      return;
    }

    if( 'final hold' == $queue )
    {
      $modifier->where( 'participant_exclusion_id', '=', NULL );
      $modifier->where( 'study_consent_accept', '=', true );
      $modifier->where( 'qnaire_has_hold_type.hold_type_id', '=', NULL );
      $modifier->where( 'last_hold_type_type', '=', 'final' );
      return;
    }

    if( 'tracing' == $queue )
    {
      $modifier->where( 'participant_exclusion_id', '=', NULL );
      $modifier->where( 'study_consent_accept', '=', true );
      $modifier->where_bracket( true );
      $modifier->where( 'qnaire_has_hold_type.hold_type_id', '!=', NULL );
      $modifier->or_where( 'IFNULL( last_hold_type_type, "" )', '!=', 'final' );
      $modifier->where_bracket( false );
      $modifier->where( 'last_trace_type_name', '!=', NULL );
      return;
    }

    if( 'temporary hold' == $queue )
    {
      $modifier->where( 'participant_exclusion_id', '=', NULL );
      $modifier->where( 'study_consent_accept', '=', true );
      $modifier->where( 'last_trace_type_name', '=', NULL );
      $modifier->where( 'qnaire_has_hold_type.hold_type_id', '=', NULL );
      $modifier->where( 'last_hold_type_type', '=', 'temporary' );
      return;
    }

    if( 'proxy' == $queue )
    {
      $modifier->where( 'participant_exclusion_id', '=', NULL );
      $modifier->where( 'study_consent_accept', '=', true );
      $modifier->where( 'last_trace_type_name', '=', NULL );
      $modifier->where_bracket( true );
      $modifier->where( 'qnaire_has_hold_type.hold_type_id', '!=', NULL );
      $modifier->or_where( 'last_hold_type_type', '=', NULL );
      $modifier->where_bracket( false );
      $modifier->where( 'last_proxy_type_name', '!=', NULL );
      return;
    }

    if( 'eligible' == $queue )
    {
      // enrolled participant who is not in a hold, trace or proxy
      $modifier->where( 'participant_exclusion_id', '=', NULL );
      $modifier->where( 'study_consent_accept', '=', true );
      $modifier->where_bracket( true );
      $modifier->where( 'qnaire_has_hold_type.hold_type_id', '!=', NULL );
      $modifier->or_where( 'last_hold_type_type', '=', NULL );
      $modifier->where_bracket( false );
      $modifier->where( 'last_trace_type_name', '=', NULL );
      $modifier->where( 'last_proxy_type_name', '=', NULL );
      return;
    }

    if( 'qnaire' == $queue )
    {
      // no additional modifications needed
      return;
    }

    if( 'qnaire waiting' == $queue )
    {
      // the current qnaire cannot start before start_qnaire_date
      $modifier->where( 'IFNULL( start_qnaire_date > UTC_TIMESTAMP(), false )', '=', true );
      return;
    }

    // the qnaire is ready to start if the start_qnaire_date is null or we have reached that date
    $modifier->where( 'IFNULL( start_qnaire_date <= UTC_TIMESTAMP(), true )', '=', true );

    if( 'assigned' == $queue )
    {
      // participants who are currently assigned
      $modifier->where( 'current_assignment_id', '!=', NULL );
      $modifier->where( 'current_assignment_end_datetime', '=', NULL );
      return;
    }

    // participants who are NOT currently assigned
    $modifier->where_bracket( true );
    $modifier->where( 'current_assignment_id', '=', NULL );
    $modifier->or_where( 'current_assignment_end_datetime', '!=', NULL );
    $modifier->where_bracket( false );

    if( 'appointment' == $queue )
    {
      // link to appointment table and make sure the appointment dosn't have an outcome
      // (by design, there can only ever be one appointment with no outcome per interview)
      $modifier->join( 'appointment', 'appointment.interview_id', 'temp_participant.current_interview_id' );
      $modifier->where( 'appointment.outcome', '=', NULL );
      return;
    }

    // Make sure there is no appointment without an outcome.  By design there can only be one
    // per interview, so if the appointment is null then the interview has no pending appointments.
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'appointment.interview_id', '=', 'temp_participant.current_interview_id', false );
    $join_mod->where( 'appointment.outcome', '=', NULL );
    $modifier->join_modifier( 'appointment', $join_mod, 'left' );
    $modifier->where( 'appointment.id', '=', NULL );

    // join to the first_address table based on participant id
    $modifier->left_join(
      'temp_participant_first_address', 'temp_participant_first_address.id', 'temp_participant.id' );

    if( 'web version' == $queue )
    {
      // make sure there is no active address
      $modifier->where( 'IFNULL( current_interview_method, IFNULL( previous_interview_method, "phone" ) )', '=', 'web' );
      return;
    }

    // make sure there is an active address
    $modifier->where( 'IFNULL( current_interview_method, IFNULL( previous_interview_method, "phone" ) )', '!=', 'web' );

    if( 'no active address' == $queue )
    {
      // make sure there is no active address
      $modifier->where( 'temp_participant_first_address.address_id', '=', NULL );
      return;
    }

    // make sure there is an active address
    $modifier->where( 'temp_participant_first_address.address_id', '!=', NULL );

    // join to the stratum table (if the participant belongs to one)
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'temp_participant.id', '=', 'stratum_has_participant.participant_id', false );
    $join_mod->where( 'stratum_has_participant.stratum_id', 'IN', self::$stratum_id_list_cache );
    $modifier->join_modifier( 'stratum_has_participant', $join_mod, 'left' );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'stratum_has_participant.stratum_id', '=', 'qnaire_has_stratum.stratum_id', false );
    $join_mod->where( 'effective_qnaire_id', '=', 'qnaire_has_stratum.qnaire_id', false );
    $modifier->join_modifier( 'qnaire_has_stratum', $join_mod, 'left' );

    if( 'no site' == $queue )
    {
      // make sure the participant has no site
      $modifier->where( 'participant_site_id', '=', NULL );
      return;
    }

    // make sure the participant has a site
    $modifier->where( 'participant_site_id', '!=', NULL );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'qnaire_has_collection.qnaire_id', '=', 'effective_qnaire_id', false );
    $modifier->join_modifier( 'qnaire_has_collection', $join_mod, 'left' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'qnaire_has_collection.collection_id', '=', 'collection_has_participant.collection_id', false );
    $join_mod->where( 'temp_participant.id', '=', 'collection_has_participant.participant_id', false );
    $modifier->join_modifier( 'collection_has_participant', $join_mod, 'left' );

    if( 'collection disabled' == $queue )
    {
      // make sure there is a row in collection_has_participant
      $modifier->where( 'collection_has_participant.participant_id', '!=', NULL );
      return;
    }

    // make sure there is no row in qnaire_has_collection
    $modifier->where( 'collection_has_participant.participant_id', '=', NULL );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'qnaire_has_site.qnaire_id', '=', 'effective_qnaire_id', false );
    $join_mod->where( 'qnaire_has_site.site_id', '=', 'participant_site_id', false );
    $modifier->join_modifier( 'qnaire_has_site', $join_mod, 'left' );

    if( 'qnaire disabled' == $queue )
    {
      // make sure there is a row in qnaire_has_site
      $modifier->where( 'qnaire_has_site.qnaire_id', '!=', NULL );
      return;
    }

    // make sure there is no row in qnaire_has_site
    $modifier->where( 'qnaire_has_site.qnaire_id', '=', NULL );

    if( 'stratum disabled' == $queue )
    {
      // who belong to a stratum which is disabled (row in qnaire_has_stratum found)
      $modifier->where( 'qnaire_has_stratum.stratum_id', '!=', NULL );
      // and who are not marked to override stratum
      $modifier->where( 'participant_override_stratum', '=', false );
      $modifier->where( 'source_override_stratum', '=', false );
      return;
    }

    // we should never get here
    throw lib::create( 'exception\argument', 'queue', $queue, __METHOD__ );
  }

  /**
   * Creates the temp_participant temporary table needed by all queues.
   * 
   * @param database\participant $db_participant If provided then only that participant will be affected by the operation.
   * @access protected
   * @static
   */
  protected static function build_temporary_tables( $db_participant = NULL )
  {
    $application_id = lib::create( 'business\session' )->get_application()->id;

    if( static::$temporary_tables_created ) return;

    // build temp_participant table
    $sql = sprintf( 'CREATE TEMPORARY TABLE IF NOT EXISTS temp_participant '.
                    static::$temp_participant_sql,
                    static::db()->format_string( $application_id ) );
    if( !is_null( $db_participant ) )
      $sql .= sprintf( ' WHERE participant.id = %s ',
                       static::db()->format_string( $db_participant->id ) );

    if( static::$debug ) $time = util::get_elapsed_time();
    static::db()->execute( 'DROP TABLE IF EXISTS temp_participant' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE temp_participant '.
        'ADD INDEX fk_id ( id ), '.
        'ADD INDEX fk_participant_sex ( participant_sex ), '.
        'ADD INDEX fk_participant_exclusion_id ( participant_exclusion_id ), '.
        'ADD INDEX fk_last_hold_type_type ( last_hold_type_type ), '.
        'ADD INDEX fk_last_trace_type_name ( last_trace_type_name ), '.
        'ADD INDEX fk_last_proxy_type_name ( last_proxy_type_name ), '.
        'ADD INDEX fk_effective_qnaire_id ( effective_qnaire_id ), '.
        'ADD INDEX fk_current_assignment_id ( current_assignment_id ), '.
        'ADD INDEX dk_primary_region_id ( primary_region_id )' );
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Building temp_participant temp table%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $time ) );

    // build temp_participant_participant_site
    $sql = sprintf(
      'CREATE TEMPORARY TABLE IF NOT EXISTS temp_participant_participant_site '.
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
    static::db()->execute( 'DROP TABLE IF EXISTS temp_participant_participant_site' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE temp_participant_participant_site '.
        'ADD INDEX dk_participant_id_site_id ( id, participant_site_id )' );
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Building temp_participant_participant_site temp table%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $time ) );

    // build temp_participant_first_address table
    $sql = sprintf(
      'CREATE TEMPORARY TABLE IF NOT EXISTS temp_participant_first_address '.
      'SELECT participant.id AS id, '.
             'address.id AS address_id, '.
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
    static::db()->execute( 'DROP TABLE IF EXISTS temp_participant_first_address' );
    static::db()->execute( $sql );

    if( is_null( $db_participant ) )
      static::db()->execute(
        'ALTER TABLE temp_participant_first_address '.
        'ADD INDEX dk_id ( id ), '.
        'ADD INDEX dk_first_address_timezone_offset ( first_address_timezone_offset ), '.
        'ADD INDEX dk_first_address_daylight_savings ( first_address_daylight_savings )' );
    if( static::$debug ) log::debug( sprintf(
      '(Queue) Building temp_participant_first_address temp table%s: %0.2f',
      is_null( $db_participant ) ? '' : ' for '.$db_participant->uid,
      util::get_elapsed_time() - $time ) );

    static::$temporary_tables_created = true;
  }

  /**
   * Creates the queue_list_cache needed by all queues.
   * 
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
   * Creates the stratum_id_list_cache needed by all queues.
   * 
   * @access protected
   * @static
   */
  protected static function create_stratum_id_list_cache()
  {
    if( is_null( self::$stratum_id_list_cache ) )
    {
      self::$stratum_id_list_cache = array();
      $db_study_phase = lib::create( 'business\session' )->get_application()->get_study_phase();
      if( !is_null( $db_study_phase ) )
      {
        $stratum_sel = lib::create( 'database\select' );
        $stratum_sel->add_column( 'id' );
        foreach( $db_study_phase->get_study( $stratum_sel )->get_stratum_list() as $stratum )
          self::$stratum_id_list_cache[] = $stratum['id'];
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
   * A list of all participants to repopulate() during shutdown
   * @var array|"all" $delayed_repopulate_list
   * @access protected
   * @static
   */
  protected static $delayed_repopulate_list = array();

  /**
   * A list of all participants to repopulate_time() during shutdown
   * @var array|"all" $delayed_repopulate_time_list
   * @access protected
   * @static
   */
  protected static $delayed_repopulate_time_list = array();

  /**
   * Whether the temporary tables has been created.
   * @var boolean
   * @access protected
   * @static
   */
  protected static $temporary_tables_created = false;

  /**
   * The select and modifier objects for queue
   * @var associative array of ( 'select' => object, 'modifier' => object )
   * @access protected
   * @static
   */
  protected static $query_object_list = array();

  /**
   * A cache of all queues and their parents used by prepare_queue_query()
   * @var array
   * @access private
   * @static
   */
  private static $queue_list_cache = array();

  /**
   * A cache of all strata belonging to this application's study
   * @var array
   * @access private
   * @static
   */
  private static $stratum_id_list_cache = NULL;

  /**
   * A string containing the SQL used to create the temp_participant data
   * @var string
   * @access protected
   * @static
   */
  protected static $temp_participant_sql = <<<'SQL'
SELECT participant.id,
participant.exclusion_id AS participant_exclusion_id,
participant.sex AS participant_sex,
participant.override_stratum AS participant_override_stratum,
IFNULL(
  study_consent.accept,
  IF(
    current_qnaire.id IS NULL,
    first_qnaire.allow_missing_consent,
    IF(
      current_interview.end_datetime IS NOT NULL,
      next_qnaire.allow_missing_consent,
      current_qnaire.allow_missing_consent
    )
  )
) AS study_consent_accept,
IFNULL( source.override_stratum, false ) AS source_override_stratum,
primary_region.id AS primary_region_id,
last_hold_type.id AS last_hold_type_id,
last_hold_type.type AS last_hold_type_type,
last_trace_type.name AS last_trace_type_name,
last_proxy_type.name AS last_proxy_type_name,
current_interview.id AS current_interview_id,
current_interview.method AS current_interview_method,
current_qnaire.id AS current_qnaire_id,
current_assignment.id AS current_assignment_id,
current_assignment.end_datetime AS current_assignment_end_datetime,
previous_interview.method AS previous_interview_method,
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
    NULL,
    IF
    (
      "web" = current_interview.method,
      IF
      (
        current_interview.end_datetime IS NOT NULL,
        DATE(
          CASE next_qnaire.delay_unit
            WHEN "day" THEN current_interview.end_datetime + INTERVAL next_qnaire.delay_offset DAY
            WHEN "week" THEN current_interview.end_datetime + INTERVAL next_qnaire.delay_offset WEEK
            WHEN "month" THEN current_interview.end_datetime + INTERVAL next_qnaire.delay_offset MONTH
          END
        ),
        NULL
      ),
      IF
      (
        current_interview.end_datetime IS NOT NULL AND current_assignment.end_datetime IS NOT NULL,
        DATE(
          CASE next_qnaire.delay_unit
            WHEN "day" THEN current_assignment.end_datetime + INTERVAL next_qnaire.delay_offset DAY
            WHEN "week" THEN current_assignment.end_datetime + INTERVAL next_qnaire.delay_offset WEEK
            WHEN "month" THEN current_assignment.end_datetime + INTERVAL next_qnaire.delay_offset MONTH
          END
        ),
        NULL
      )
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

LEFT JOIN source ON participant.source_id = source.id
LEFT JOIN study_phase ON application.study_phase_id = study_phase.id
LEFT JOIN study ON study_phase.study_id = study.id
LEFT JOIN participant_last_consent
  ON participant.id = participant_last_consent.participant_id
  AND study.consent_type_id = participant_last_consent.consent_type_id
LEFT JOIN consent AS study_consent ON participant_last_consent.consent_id = study_consent.id

LEFT JOIN participant_primary_address ON participant.id = participant_primary_address.participant_id
LEFT JOIN address AS primary_address ON participant_primary_address.address_id = primary_address.id
LEFT JOIN region AS primary_region ON primary_address.region_id = primary_region.id

JOIN participant_last_hold ON participant.id = participant_last_hold.participant_id
LEFT JOIN hold AS last_hold ON participant_last_hold.hold_id = last_hold.id
LEFT JOIN hold_type AS last_hold_type ON last_hold.hold_type_id = last_hold_type.id

JOIN participant_last_trace ON participant.id = participant_last_trace.participant_id
LEFT JOIN trace AS last_trace ON participant_last_trace.trace_id = last_trace.id
LEFT JOIN trace_type AS last_trace_type ON last_trace.trace_type_id = last_trace_type.id

JOIN participant_last_proxy ON participant.id = participant_last_proxy.participant_id
LEFT JOIN proxy AS last_proxy ON participant_last_proxy.proxy_id = last_proxy.id
LEFT JOIN proxy_type AS last_proxy_type ON last_proxy.proxy_type_id = last_proxy_type.id

LEFT JOIN participant_last_interview AS participant_current_interview
   ON participant.id = participant_current_interview.participant_id
LEFT JOIN interview AS current_interview ON participant_current_interview.interview_id = current_interview.id
LEFT JOIN qnaire AS current_qnaire ON current_interview.qnaire_id = current_qnaire.id
LEFT JOIN interview_last_assignment AS interview_current_assignment
   ON current_interview.id = interview_current_assignment.interview_id
LEFT JOIN assignment AS current_assignment ON interview_current_assignment.assignment_id = current_assignment.id

CROSS JOIN qnaire AS first_qnaire ON first_qnaire.rank = 1

LEFT JOIN qnaire AS previous_qnaire ON previous_qnaire.rank = ( current_qnaire.rank - 1 )
LEFT JOIN interview AS previous_interview
  ON participant.id = previous_interview.participant_id
  AND previous_qnaire.id = previous_interview.qnaire_id

LEFT JOIN qnaire AS next_qnaire ON next_qnaire.rank = ( current_qnaire.rank + 1 )
SQL;
}
