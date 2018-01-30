<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\participant\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();
      $db_role = $session->get_role();

      $db_participant = NULL;
      if( 'participant' == $this->get_subject() ) $db_participant = $this->get_resource();
      else if( 'participant' == $this->get_parent_subject() ) $db_participant = $this->get_parent_resource();

      if( 'operator' == $db_role->name )
      {
        if( !is_null( $db_participant ) )
        {
          // make sure that operators can only see the participant they are currently assigned to
          $db_assignment = $db_user->get_open_assignment();
          if( is_null( $db_assignment ) ||
              $db_participant->id != $db_assignment->get_interview()->participant_id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }
        else
        {
          // make sure that operators can only see participant lists when in assignment mode
          if( !$this->get_argument( 'assignment', false ) )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( $this->get_argument( 'assignment', false ) )
    {
      $session = lib::create( 'business\session' );
      $db_user = $session->get_user();

      // remove hold/proxy/trace/exclusion joins for efficiency
      $modifier->remove_join( 'exclusion' );
      $modifier->remove_join( 'participant_last_hold' );
      $modifier->remove_join( 'hold' );
      $modifier->remove_join( 'hold_type' );
      $modifier->remove_join( 'participant_last_proxy' );
      $modifier->remove_join( 'proxy' );
      $modifier->remove_join( 'proxy_type' );
      $modifier->remove_join( 'participant_last_trace' );
      $modifier->remove_join( 'trace' );
      $modifier->remove_join( 'trace_type' );
      $select->remove_column_by_alias( 'status' );

      $modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
      $modifier->where( 'queue.rank', '!=', NULL );

      // only show reserved appointments to the reserved user
      $modifier->left_join(
        'participant_last_interview', 'participant.id', 'participant_last_interview.participant_id' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant_last_interview.interview_id', '=', 'appointment.interview_id', false );
      $join_mod->where( 'appointment.assignment_id', '=', NULL );
      $join_mod->where( 'appointment.outcome', '=', NULL );
      $modifier->join_modifier( 'appointment', $join_mod, 'left' );

      $modifier->where_bracket( true );
      $modifier->where( 'queue.name', '!=', 'assignable appointment' );
      $modifier->or_where( sprintf( 'IFNULL( appointment.user_id, %d )', $db_user->id ), '=', $db_user->id );
      $modifier->where_bracket( false );

      // restrict the list to the user's languages
      $language_sel = lib::create( 'database\select' );
      $language_sel->add_column( 'id' );
      $language_list = $db_user->get_language_list( $language_sel );
      if( 0 < count( $language_list ) )
      {
        $language_array = array();
        foreach( $language_list as $language ) $language_array[] = $language['id'];
        $modifier->where( 'participant.language_id', 'IN', $language_array );
      }

      // add a variable defining whether this is a reserved appointment
      $select->add_column( 'appointment.user_id IS NOT NULL', 'reserved', false, 'boolean' );

      // repopulate queue if it is out of date
      $queue_class_name = lib::get_class_name( 'database\queue' );
      $interval = $queue_class_name::get_interval_since_last_repopulate();
      if( is_null( $interval ) || 0 < $interval->days || 22 < $interval->h )
      { // it's been at least 23 hours since the non time-based queues have been repopulated
        $queue_class_name::repopulate();
        $queue_class_name::repopulate_time();
      }
      else
      {
        $interval = $queue_class_name::get_interval_since_last_repopulate_time();
        if( is_null( $interval ) || 0 < $interval->days || 0 < $interval->h || 0 < $interval->i )
        { // it's been at least one minute since the time-based queues have been repopulated
          $queue_class_name::repopulate_time();
        }
      }

      // only provide the highest ranking participants to operators
      $role = $session->get_role()->name;
      if( 'operator' == $role || 'operator+' == $role )
      {
        $sub_modifier = clone $modifier;
        $sub_select = lib::create( 'database\select' );
        $sub_select->from( 'participant' );
        $sub_select->add_column( 'MIN( queue.rank )', 'min_rank', false );
        $modifier->where(
          'queue.rank',
          '=',
          sprintf( '( %s %s )', $sub_select->get_sql(), $sub_modifier->get_sql( true ) ),
          false
        );
      }
    }
    else
    {
      if( $select->has_table_columns( 'queue' ) || $select->has_table_columns( 'qnaire' ) )
      {
        // Special note: the following is needed when viewing a participant's details but not needed when
        // viewing a list of participants belonging to a queue (and the participant_max_queue join below
        // would drastically slow down the query if we were to use it)
        // We can work around this issue by not joining to this temporary table when the parent is "queue"
        if( 'queue' != $this->get_parent_subject() )
        {
          $join_sel = lib::create( 'database\select' );
          $join_sel->from( 'queue_has_participant' );
          $join_sel->add_column( 'participant_id' );
          $join_sel->add_column( 'MAX( queue_id )', 'queue_id', false );

          $join_mod = lib::create( 'database\modifier' );
          $join_mod->group( 'participant_id' );

          $modifier->left_join(
            sprintf( '( %s %s ) AS participant_max_queue',
                     $join_sel->get_sql(),
                     $join_mod->get_sql() ),
            'participant.id',
            'participant_max_queue.participant_id' );

          $join_mod = lib::create( 'database\modifier' );
          $join_mod->where(
            'participant_max_queue.queue_id', '=', 'queue_has_participant.queue_id', false );
          $join_mod->where(
            'participant_max_queue.participant_id', '=', 'queue_has_participant.participant_id', false );

          $modifier->join_modifier( 'queue_has_participant', $join_mod, 'left' );
        }

        if( $select->has_table_columns( 'queue' ) )
          $modifier->left_join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
        if( $select->has_table_columns( 'qnaire' ) )
        {
          $modifier->left_join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );

          // title is "qnaire.rank: script.name"
          if( $select->has_table_column( 'qnaire', 'title' ) )
          {
            $modifier->left_join( 'script', 'qnaire.script_id', 'script.id' );
            $select->add_table_column( 'qnaire', 'CONCAT( qnaire.rank, ": ", script.name )', 'title', false );
          }

          // fake the qnaire start-date
          if( $select->has_table_column( 'qnaire', 'start_date' ) )
            $select->add_table_column( 'qnaire', 'queue_has_participant.start_qnaire_date', 'start_date', false );
        }
      }
    }
  }
}
