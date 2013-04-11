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
  /*
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

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.participant_id', '=', $this->id );
    $modifier->where( 'end_datetime', '=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $modifier->limit( 1 );
    $assignment_class_name = lib::get_class_name( 'database\assignment' );
    $assignment_list = $assignment_class_name::select( $modifier );

    return 0 == count( $assignment_list ) ? NULL : current( $assignment_list );
  }

  /**
   * Override parent's magic get method so that supplementary data can be retrieved
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column or table being fetched from the database
   * @return mixed
   * @access public
   */
  public function __get( $column_name )
  {
    if( 'current_qnaire_id' == $column_name ||
        'start_qnaire_date' == $column_name )
    {
      $this->get_queue_data();
      return $this->$column_name;
    }

    return parent::__get( $column_name );
  }

  /**
   * Fills in the current qnaire id and start qnaire date
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function get_queue_data()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }

    $database_class_name = lib::get_class_name( 'database\database' );

    if( is_null( $this->current_qnaire_id ) && is_null( $this->start_qnaire_date ) )
    {
      $sql = sprintf(
        $this->participant_additional_sql,
        $database_class_name::format_string( $this->id ) );
      $row = static::db()->get_row( $sql );
      $this->current_qnaire_id = $row['current_qnaire_id'];
      $this->start_qnaire_date = $row['start_qnaire_date'];
    }
  }

  /**
   * The participant's current questionnaire id (from a custom query)
   * @var int
   * @access private
   */
  private $current_qnaire_id = NULL;

  /**
   * The date that the current questionnaire is to begin (from a custom query)
   * @var int
   * @access private
   */
  private $start_qnaire_date = NULL;

  /**
   * A string containing the SQL used to get the additional participant information used by
   * get_queue_data()
   * @var string
   * @access private
   */
  private $participant_additional_sql = <<<'SQL'
SELECT IF
(
  current_interview.id IS NULL,
  ( SELECT id FROM qnaire WHERE rank = 1 ),
  IF( current_interview.completed, next_qnaire.id, current_qnaire.id )
) AS current_qnaire_id,
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
) AS start_qnaire_date
FROM participant
LEFT JOIN interview AS current_interview
ON current_interview.participant_id = participant.id
LEFT JOIN interview_last_assignment
ON current_interview.id = interview_last_assignment.interview_id
LEFT JOIN assignment
ON interview_last_assignment.assignment_id = assignment.id
LEFT JOIN qnaire AS current_qnaire
ON current_qnaire.id = current_interview.qnaire_id
LEFT JOIN qnaire AS next_qnaire
ON next_qnaire.rank = ( current_qnaire.rank + 1 )
LEFT JOIN qnaire AS next_prev_qnaire
ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id
LEFT JOIN interview AS next_prev_interview
ON next_prev_interview.qnaire_id = next_prev_qnaire.id
AND next_prev_interview.participant_id = participant.id
LEFT JOIN assignment next_prev_assignment
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
AND participant.id = %s
SQL;
}
