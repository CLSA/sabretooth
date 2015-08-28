<?php
/**
 * participant.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * participant: record
 */
class participant extends \cenozo\database\participant
{
  /**
   * Override parent method
   */
  public function save()
  {
    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed(
      array( 'active', 'sex', 'age_group_id', 'state_id', 'source_id', 'override_quota' ) );
    parent::save();
    if( $update_queue ) $this->update_queue_status();
  }

  /**
   * Override parent method
   */
  public function set_preferred_site( $db_application, $site = NULL )
  {
    parent::set_preferred_site( $db_application, $site );
    $this->update_queue_status();
  }

  /**
   * Updates the participant's queue status.
   * 
   * The participant's entries in the queue_has_participant table are all removed and
   * re-determined.  This method should be called if any of the participant's details
   * which affect which queue they belong in change (eg: change to appointments, consent
   * status, state, etc).
   * WARNING: this operation is db-intensive so it should only be called after all
   * changes to the participant are complete (never more than once per operation).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function update_queue_status()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to update queue status of participant with no id.' );
      return NULL;
    }

    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate( $this );
    $queue_class_name::repopulate_time_specific( $this );
  }

  /**
   * Get the participant's most recent, closed assignment.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_finished_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.participant_id', '=', $this->id );
    $modifier->where( 'end_datetime', '!=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $modifier->limit( 1 );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $assignment_list = $assignment_class_name::select( $modifier );

    return 0 == count( $assignment_list ) ? NULL : current( $assignment_list );
  }

  /**
   * Get the participant's current assignment (or null if none is found)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_current_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }

    $assignment_class_name = lib::get_class_name( 'database\assignment' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
    $modifier->where( 'interview.participant_id', '=', $this->id );
    $modifier->where( 'assignment.end_datetime', '=', NULL );
    $modifier->order_desc( 'assignment.start_datetime' );
    $assignment_list = $assignment_class_name::select_objects( $modifier );

    if( 1 < count( $assignment_list ) )
      log::warning( sprintf( 'Participant %d (%s) has more than one open assignment!', $this->id, $this->uid ) );
    return 0 == count( $assignment_list ) ? NULL : current( $assignment_list );
  }

  /**
   * Extends parent method
   */
  public function __get( $column_name )
  {
    $queue_columns = array(
      'current_queue_id',
      'effective_qnaire_id',
      'start_qnaire_date' );

    if( in_array( $column_name, $queue_columns ) )
    {
      $this->load_queue_data();
      return $this->$column_name;
    }

    return parent::__get( $column_name );
  }

  /**
   * Returns the participant's current queue.
   * 
   * The "current" queue is only set if the participant is in a ranked queue.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\queue
   * @access public
   */
  public function get_current_queue()
  {
    $this->load_queue_data();
    return is_null( $this->current_queue_id ) ?
      NULL : lib::create( 'database\queue', $this->current_queue_id );
  }

  /**
   * Returns the participant's effective qnaire.
   * 
   * The "effective" qnaire is determined by the queue.
   * If they have not yet started an interview then the first qnaire is returned.
   * If they current have an incomplete interview then that interview's qnaire is returned.
   * If their current interview is complete then the next qnaire is returned, and if there
   * is no next qnaire then NULL is returned (ie: the participant has completed all qnaires).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\qnaire
   * @access public
   */
  public function get_effective_qnaire()
  {
    $this->load_queue_data();
    return is_null( $this->effective_qnaire_id ) ?
      NULL : lib::create( 'database\qnaire', $this->effective_qnaire_id );
  }

  /**
   * Returns the participant's effective interview.
   * 
   * The "effective" interview is determined by the queue.
   * If they have not yet started an interview then a new interview is created or the first qnaire and returned.
   * If they have an incomplete interview then that interviews is returned.
   * If their current interview is complete then a new interview is created for the next qnaire and returned,
   * and if there is no next qnaire then NULL is returned (ie: the participant has completed all interviews).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\qnaire
   * @access public
   */
  public function get_effective_interview()
  {
    $interview_class_name = lib::get_class_name( 'database\interview' );
    $this->load_queue_data();

    $db_interview = NULL;
    if( !is_null( $this->effective_qnaire_id ) )
    {
      $db_interview = $interview_class_name::get_unique_record(
        array( 'participant_id', 'qnaire_id' ),
        array( $this->id, $this->effective_qnaire_id ) );
      
      if( is_null( $db_interview ) )
      { // create the interview if it isn't found
        $db_interview = lib::create( 'database\interview' );
        $db_interview->participant_id = $this->id;
        $db_interview->qnaire_id = $this->effective_qnaire_id;
        $db_interview->save();
      }
    }

    return $db_interview;
  }

  /**
   * Returns whether the participant's quota is enabled or not.
   * 
   * This is determined by cross-referencing the participant's quota and their effective qnaire
   * since quotas can be enabled/disabled by qnaire.  If the participant does not belong to any
   * quota then NULL is returned instead.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function get_quota_enabled()
  {
    $this->load_queue_data();
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->where( 'qnaire_id', '=', $this->effective_qnaire_id );
    $db_quota = $this->get_quota();
    return is_null( $db_quota ) ? NULL : 0 == $db_quota->get_qnaire_count( $qnaire_mod );
  }

  /**
   * Returns the participant's qnaire start date.
   * 
   * The qnaire start date is determined based on the following rules:
   * If they have not yet started an interview then the date is based on the first qnaire's
   * delay and the start event needed to start that qnaire, or the current date if there is
   * no start event for the first qnaire.
   * If their current interview is complete then the date is based on the next qnaire's delay
   * and the greatest of its start event and the completed interview's completion date.
   * If there is no next qnaire or the current interview is not complete then NULL is returned,
   * meaning the qnaire has already started (ie: the start date is in the past).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return datetime
   * @access public
   */
  public function get_start_qnaire_date()
  {
    $this->load_queue_data();
    return $this->start_qnaire_date;
  }

  /**
   * Fills in the queue-based information about the participant
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function load_queue_data()
  {
    if( $this->queue_data_loaded ) return;

    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }

    $database_class_name = lib::get_class_name( 'database\database' );

    // the qnaire date is cached in the queue_has_participant joining table
    $row = static::db()->get_row( sprintf(
      'SELECT queue_has_participant.* FROM queue_has_participant '.
      'JOIN queue ON queue_has_participant.queue_id = queue.id '.
      'WHERE participant_id = %s '.
      'AND queue.rank IS NOT NULL '.
      'ORDER BY queue.rank '.
      'LIMIT 1',
      static::db()->format_string( $this->id ) ) );

    if( count( $row ) )
    {
      $this->current_queue_id = $row['queue_id'];     
      $this->effective_qnaire_id = $row['qnaire_id'];
      $this->start_qnaire_date = !$row['start_qnaire_date']
                               ? NULL
                               : util::get_datetime_object( $row['start_qnaire_date'] );
    }

    $this->queue_data_loaded = true;
  }

  /**
   * Whether the participants queue-specific data has been read from the database
   * @var boolean
   * @access private
   */
  private $queue_data_loaded = false;

  /**
   * The participant's current queue id (from a custom query)
   * @var int
   * @access private
   */
  private $current_queue_id = NULL;

  /**
   * The participant's current questionnaire id (from a custom query)
   * @var int
   * @access private
   */
  private $effective_qnaire_id = NULL;

  /**
   * The date that the current questionnaire is to begin (from a custom query)
   * @var string
   * @access private
   */
  private $start_qnaire_date = NULL;
}
