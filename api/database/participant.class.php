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
class participant extends \cenozo\database\has_note
{
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
   * Get the participant's last consent
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return consent
   * @access public
   */
  public function get_last_consent()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $database_class_name = lib::get_class_name( 'database\database' );
    $consent_id = static::db()->get_one(
      sprintf( 'SELECT consent_id '.
               'FROM participant_last_consent '.
               'WHERE participant_id = %s',
               $database_class_name::format_string( $this->id ) ) );
    return $consent_id ? lib::create( 'database\consent', $consent_id ) : NULL;
  }

  /**
   * Get the participant's "primary" address.  This is the highest ranking canadian address.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return address
   * @access public
   */
  public function get_primary_address()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $database_class_name = lib::get_class_name( 'database\database' );
    $address_id = static::db()->get_one(
      sprintf( 'SELECT address_id FROM participant_primary_address WHERE participant_id = %s',
               $database_class_name::format_string( $this->id ) ) );
    return $address_id ? lib::create( 'database\address', $address_id ) : NULL;
  }

  /**
   * Get the participant's "first" address.  This is the highest ranking, active, available
   * address.
   * Note: this address may be in the United States
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return address
   * @access public
   */
  public function get_first_address()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $database_class_name = lib::get_class_name( 'database\database' );
    $address_id = static::db()->get_one(
      sprintf( 'SELECT address_id FROM participant_first_address WHERE participant_id = %s',
               $database_class_name::format_string( $this->id ) ) );
    return $address_id ? lib::create( 'database\address', $address_id ) : NULL;
  }

  /**
   * Get the default site that the participant belongs to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return site
   * @access public
   */
  public function get_default_site()
  {
    $db_site = NULL;
    $db_address = $this->get_primary_address();
    if( !is_null( $db_address ) ) $db_site = $db_address->get_region()->get_site();
    return $db_site;
  }

  /**
   * Get the site that the participant belongs to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return site
   * @access public
   */
  public function get_primary_site()
  {
    return is_null( $this->site_id ) ? $this->get_default_site() : $this->get_site();
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
    if( 'current_qnaire_id' == $column_name || 'start_qnaire_date' == $column_name )
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
    
    if( is_null( $this->current_qnaire_id ) && is_null( $this->start_qnaire_date ) )
    {
      $database_class_name = lib::get_class_name( 'database\database' );
      // special sql to get the current qnaire id and start date
      // NOTE: when updating this query database\queue::get_query_parts()
      //       should also be updated as it performs a very similar query
      $sql = sprintf(
        'SELECT IF( current_interview.id IS NULL, '.
        '           ( SELECT id FROM qnaire WHERE rank = 1 ), '.
        '           IF( current_interview.completed, next_qnaire.id, current_qnaire.id ) '.
        '       ) AS current_qnaire_id, '.
        '       IF( current_interview.id IS NULL, '.
        '           IF( participant.prior_contact_date IS NULL, '.
        '               NULL, '.
        '               participant.prior_contact_date + INTERVAL( '.
        '                 SELECT delay FROM qnaire WHERE rank = 1 '.
        '               ) WEEK ), '.
        '           IF( current_interview.completed, '.
        '               IF( next_qnaire.id IS NULL, '.
        '                   NULL, '.
        '                   IF( next_prev_assignment.end_datetime IS NULL, '.
        '                       participant.prior_contact_date, '.
        '                       next_prev_assignment.end_datetime '.
        '                   ) + INTERVAL next_qnaire.delay WEEK '.
        '               ), '.
        '               NULL '.
        '           ) '.
        '       ) AS start_qnaire_date '.
        'FROM participant '.

        'LEFT JOIN interview AS current_interview '.
        'ON current_interview.participant_id = participant.id '.
        'LEFT JOIN interview_last_assignment '.
        'ON current_interview.id = interview_last_assignment.interview_id '.
        'LEFT JOIN assignment '.
        'ON interview_last_assignment.assignment_id = assignment.id '.
        'LEFT JOIN qnaire AS current_qnaire '.
        'ON current_qnaire.id = current_interview.qnaire_id '.
        'LEFT JOIN qnaire AS next_qnaire '.
        'ON next_qnaire.rank = ( current_qnaire.rank + 1 ) '.
        'LEFT JOIN qnaire AS next_prev_qnaire '.
        'ON next_prev_qnaire.id = next_qnaire.prev_qnaire_id '.
        'LEFT JOIN interview AS next_prev_interview '.
        'ON next_prev_interview.qnaire_id = next_prev_qnaire.id '.
        'AND next_prev_interview.participant_id = participant.id '.
        'LEFT JOIN assignment next_prev_assignment '.
        'ON next_prev_assignment.interview_id = next_prev_interview.id '.
        'WHERE ( '.
        '  current_qnaire.rank IS NULL OR '.
        '  current_qnaire.rank = ( '.
        '    SELECT MAX( qnaire.rank ) '.
        '    FROM interview, qnaire '.
        '    WHERE qnaire.id = interview.qnaire_id '.
        '    AND current_interview.participant_id = interview.participant_id '.
        '    GROUP BY current_interview.participant_id ) ) '.
        'AND ( '.
        '  next_prev_assignment.end_datetime IS NULL OR '.
        '  next_prev_assignment.end_datetime = ( '.
        '    SELECT MAX( assignment.end_datetime ) '.
        '    FROM interview, assignment '.
        '    WHERE interview.qnaire_id = next_prev_qnaire.id '.
        '    AND interview.id = assignment.interview_id '.
        '    AND next_prev_assignment.id = assignment.id '.
        '    GROUP BY next_prev_assignment.interview_id ) ) '.
        'AND participant.id = %s',
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
}

// define the join to the address table
$address_mod = lib::create( 'database\modifier' );
$address_mod->where( 'participant.id', '=', 'participant_primary_address.participant_id', false );
$address_mod->where( 'participant_primary_address.address_id', '=', 'address.id', false );
participant::customize_join( 'address', $address_mod );
?>
