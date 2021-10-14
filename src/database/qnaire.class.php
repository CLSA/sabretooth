<?php
/**
 * qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * qnaire: record
 */
class qnaire extends \cenozo\database\has_rank
{
  /**
   * Sets the interview method for a list of identifiers as a single operation
   * @param database\identifier $db_identifier The identifier (NULL if using native UIDs)
   * @param array $identifier_list
   * @param string $method Either "phone" or "web"
   */
  public function mass_set_method( $db_identifier, $identifier_list, $method )
  {
    $pine_qnaire_id = $this->get_script()->pine_qnaire_id;
    if( is_null( $pine_qnaire_id ) )
      throw lib::create( 'exception\runtime', 'Tried to set method for non Pine questionnaire.', __METHOD__ );

    ini_set( 'memory_limit', '1G' );
    set_time_limit( 900 ); // 15 minutes max

    if( 'phone' == $method )
    {
      // delete pending emails for all interviews
      $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );
      $cenozo_manager->post(
        sprintf( 'qnaire/%d/participant', $pine_qnaire_id ),
        array(
          'mode' => 'remove_mail',
          'identifier_id' => is_null( $db_identifier ) ? NULL : $db_identifier->id,
          'identifier_list' => $identifier_list
        )
      );
    }
    else if( 'web' == $method )
    {
      // first make sure that all interviews exist
      $modifier = lib::create( 'database\modifier' );

      if( is_null( $db_identifier ) )
      {
        $modifier->where( 'uid', 'IN', $identifier_list );
      }
      else
      {
        $modifier->join( 'participant_identifier', 'participant.id', 'participant_identifier.participant_id' );
        $modifier->where( 'participant_identifier.identifier_id', '=', $db_identifier->id );
        $modifier->where( 'participant_identifier.value', 'IN', $identifier_list );
      }

      static::db()->execute( sprintf(
        'INSERT INTO interview( qnaire_id, participant_id, method, start_datetime ) '.
        'SELECT %s, participant.id, "web", UTC_TIMESTAMP() '.
        'FROM participant '.
        '%s '.
        'ON DUPLICATE KEY UPDATE method = "web"',
        static::db()->format_string( $this->id ),
        $modifier->get_sql()
      ) );

      $this->launch_web_interviews( $db_identifier, $identifier_list );
    }

    // now set the method column
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );

    if( is_null( $db_identifier ) )
    {
      $modifier->where( 'uid', 'IN', $identifier_list );
    }
    else
    {
      $modifier->join( 'participant_identifier', 'participant.id', 'participant_identifier.participant_id' );
      $modifier->where( 'participant_identifier.identifier_id', '=', $db_identifier->id );
      $modifier->where( 'participant_identifier.value', 'IN', $identifier_list );
    }

    static::db()->execute( sprintf(
      "UPDATE interview %s\n".
      "SET method = %s\n".
      'WHERE %s',
      $modifier->get_join(),
      static::db()->format_string( $method ),
      $modifier->get_where()
    ) );
  }

  /**
   * Sends email and creates respondent records for this record's pine qnaire
   * @param database\identifier $db_identifier The identifier (NULL if using native UIDs)
   * @param array $identifier_list
   */
  public function launch_web_interviews( $db_identifier, $identifier_list )
  {
    $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );
    $pine_qnaire_id = $this->get_script()->pine_qnaire_id;
    if( is_null( $pine_qnaire_id ) )
      throw lib::create( 'exception\runtime', 'Tried to launch web interviews for non Pine questionnaire.', __METHOD__ );

    ini_set( 'memory_limit', '1G' );
    set_time_limit( 900 ); // 15 minutes max

    // make sure all existing respondents get mail
    $cenozo_manager->post(
      sprintf( 'qnaire/%d/participant', $pine_qnaire_id ),
      array(
        'mode' => 'add_mail',
        'identifier_id' => is_null( $db_identifier ) ? NULL : $db_identifier->id,
        'identifier_list' => $identifier_list
      )
    );

    // and finally, create missing pine respondents
    $cenozo_manager->post(
      sprintf( 'qnaire/%d/participant', $pine_qnaire_id ),
      array(
        'mode' => 'create',
        'identifier_id' => is_null( $db_identifier ) ? NULL : $db_identifier->id,
        'identifier_list' => $identifier_list
      )
    );
  }

  /**
   * Updates the progress of all interviews
   * @param database\participant Optional participant when only one interview needs to be updated.
   * @return integer The number of interviews updated
   */
  public function update_interview_progress( $db_participant = NULL )
  {
    $cenozo_manager = lib::create( 'business\cenozo_manager', 'pine' );
    
    $updated_total = 0;
    $db_script = $this->get_script();
    if( $cenozo_manager->exists() && !is_null( $db_script->pine_qnaire_id ) )
    {
      $select = array(
        'column' => array(
          'participant_id',
          array( 'table' => 'response', 'column' => 'current_page_rank' )
        )
      );
      $modifier = array(
        'where' => array(
          array( 'column' => 'current_page_rank', 'operator' => '!=', 'value' => NULL )
        ),
        'limit' => 1000000
      );
      if( !is_null( $db_participant ) )
        $modifier['where'][] = array( 'column' => 'participant_id', 'operator' => '=', 'value' => $db_participant->id );
      
      $service = sprintf(
        'qnaire/%d/respondent?no_activity=1&select=%s&modifier=%s',
        $db_script->pine_qnaire_id,
        util::json_encode( $select ),
        util::json_encode( $modifier )
      );

      $replace_list = array();
      foreach( $cenozo_manager->get( $service ) as $obj )
        $replace_list[] = sprintf( '(%s,%s,%s)', $obj->participant_id, $this->id, $obj->current_page_rank );

      // set the current_page_rank to NULL if they aren't already
      $update_mod = lib::create( 'database\modifier' );
      $update_mod->where( 'qnaire_id', '=', $this->id );
      $update_mod->where( 'current_page_rank', '!=', NULL );
      if( $db_participant ) $update_mod->where( 'participant_id', '=', $db_participant->id );
      static::db()->execute( sprintf( 'UPDATE interview SET current_page_rank = NULL %s', $update_mod->get_sql() ) );

      if( 0 < count( $replace_list ) )
      {
        // update the interview table with the data received from Pine
        $sql = sprintf(
          'INSERT IGNORE INTO interview( participant_id, qnaire_id, current_page_rank ) '.
          'VALUES %s '.
          'ON DUPLICATE KEY UPDATE current_page_rank = VALUES( current_page_rank )',
          implode( ',', $replace_list )
        );

        $updated_total = static::db()->execute( $sql );
      }
    }

    log::debug( $updated_total );
    return $updated_total;
  }

  /**
   * Extend parent method
   */
  public function add_stratum( $ids )
  {
    parent::add_stratum( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function remove_stratum( $ids )
  {
    parent::remove_stratum( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function replace_stratum( $ids )
  {
    parent::replace_stratum( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }
  /**
   * Extend parent method
   */
  public function add_site( $ids )
  {
    parent::add_site( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function remove_site( $ids )
  {
    parent::remove_site( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function replace_site( $ids )
  {
    parent::replace_site( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }
}
