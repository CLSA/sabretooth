<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * interview: record
 */
class interview extends \cenozo\database\interview
{
  /**
   * Override the parent method
   */
  public function save()
  {
    $is_new = is_null( $this->id );
    $update_method = $this->has_column_changed( 'method' );

    parent::save();

    if( $update_method )
    {
      // only update mail if necessary
      if( 'phone' == $this->method && !$is_new ) $this->remove_mail();
      else if( 'web' == $this->method ) $this->resend_mail();

      $this->get_participant()->repopulate_queue( true );
    }
  }

  /**
   * Send a message to Pine asking to remove mail for this interview
   * 
   * Note: this will only affect interviews linked to a Pine script
   */
  public function remove_mail()
  {
    $pine_qnaire_id = $this->get_qnaire()->get_script()->pine_qnaire_id;

    if( !is_null( $pine_qnaire_id ) )
    {
      $token = NULL;

      // try and get the respondent record from pine, if it exists
      $cenozo_manager = lib::create( 'business\cenozo_manager', lib::create( 'business\session' )->get_pine_application() );
      try
      {
        $response = $cenozo_manager->get( sprintf(
          'qnaire/%d/respondent/participant_id=%d?no_activity=1&select={"column":["token"]}',
          $pine_qnaire_id,
          $this->participant_id
        ) );

        $token = $response->token;
      }
      catch( \cenozo\exception\runtime $e )
      {
        // 404 errors simply means the respondent doesn't exit
        if( false === preg_match( '/Got response code 404/', $e->get_raw_message() ) ) throw $e;
      }

      if( !is_null( $token ) )
      {
        // changing an existing interview from web to phone
        $cenozo_manager->patch(
          sprintf( 'respondent/token=%s?no_activity=1&action=remove_mail', $token ),
          new \stdClass
        );
      }
    }
  }

  /**
   * Updates the interview's progress
   * 
   * Note that this will do nothing if the parent qnaire is not connected to a Pine-based script
   */
  public function update_progress()
  {
    $this->get_qnaire()->update_interview_progress( $this->get_participant() );
  }

  /**
   * Send a message to Pine asking to resend mail for this interview
   * 
   * Note: this will only affect interviews linked to a Pine script
   */
  public function resend_mail()
  {
    $pine_qnaire_id = $this->get_qnaire()->get_script()->pine_qnaire_id;

    if( !is_null( $pine_qnaire_id ) )
    {
      $token = NULL;

      // try and get the respondent record from pine, if it exists
      $cenozo_manager = lib::create( 'business\cenozo_manager', lib::create( 'business\session' )->get_pine_application() );
      try
      {
        $response = $cenozo_manager->get( sprintf(
          'qnaire/%d/respondent/participant_id=%d?no_activity=1&select={"column":["token"]}',
          $pine_qnaire_id,
          $this->participant_id
        ) );

        $token = $response->token;
      }
      catch( \cenozo\exception\runtime $e )
      {
        // 404 errors simply means the respondent doesn't exit
        if( false === preg_match( '/Got response code 404/', $e->get_raw_message() ) ) throw $e;
      }

      if( is_null( $token ) )
      {
        // create the missing respondent record (respondent mail will also be created)
        $cenozo_manager->post(
          sprintf( 'qnaire/%d/respondent', $pine_qnaire_id ),
          array( 'participant_id' => $this->participant_id )
        );
      }
      else
      {
        // resend mail for the respondent
        $cenozo_manager->patch(
          sprintf( 'respondent/token=%s?no_activity=1&action=resend_mail', $token ),
          new \stdClass
        );
      }
    }
  }

  /**
   * Determines whether the script associated with this interview has been completed
   * 
   * @return boolean
   * @access public
   */
  public function is_survey_complete()
  {
    $db_script = $this->get_qnaire()->get_script();

    $is_complete = false;
    if( 'pine' == $db_script->get_type() )
    {
      $cenozo_manager = lib::create( 'business\cenozo_manager', lib::create( 'business\session' )->get_pine_application() );
      try
      {
        $response = $cenozo_manager->get( sprintf(
          'qnaire/%d/respondent/participant_id=%d?no_activity=1&select={"column":"completed"}',
          $db_script->pine_qnaire_id,
          $this->get_participant()->id
        ) );
        $is_complete = $response->completed;
      }
      catch( \cenozo\exception\runtime $e )
      {
        // ignore 404s, it just mean the script hasn't been started yet
        if( false === preg_match( '/Got response code 404/', $e->get_raw_message() ) ) throw $e;
      }
    }

    return $is_complete;
  }

  /**
   * Extends parent method
   */
  public function complete( $db_credit_site = NULL )
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    parent::complete( $db_credit_site );

    $db_participant = $this->get_participant();

    // If there are any appointments belonging to this interview which are unassigned then immediately
    // create make the next interview an reassign it (or delete it if this is the last interview)
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->where( 'appointment.assignment_id', '=', NULL );
    $appointment_mod->where( 'appointment.outcome', '=', NULL );
    if( 0 < $this->get_appointment_count( clone $appointment_mod ) )
    {
      // see if there is a next qnaire
      $db_interview = NULL;
      $db_next_qnaire = $qnaire_class_name::get_unique_record( 'rank', $this->get_qnaire()->rank + 1 );
      if( !is_null( $db_next_qnaire ) )
      {
        $db_interview = new static();
        $db_interview->qnaire_id = $db_next_qnaire->id;
        $db_interview->participant_id = $db_participant->id;
        $db_interview->start_datetime = util::get_datetime_object();
        $db_interview->method = $this->method;
        $db_interview->save();
      }

      foreach( $this->get_appointment_object_list( $appointment_mod ) as $db_appointment )
      {
        if( is_null( $db_next_qnaire ) ) $db_appointment->delete();
        else
        {
          $db_appointment->interview_id = $db_interview->id;
          $db_appointment->save();
        }
      }
    }
  }

  /**
   * Removes an interview along with its appointments, phone calls, and assignments
   * @access public
   */
  public function force_delete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force delete interview with no primary key.' );
      return;
    }

    // delete all appointments
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview_id', '=', $this->id );
    static::db()->execute( sprintf( 'DELETE FROM appointment %s', $modifier->get_sql() ) );

    // delete all phone calls
    $sub_sel = lib::create( 'database\select' );
    $sub_sel->from( 'assignment' );
    $sub_sel->add_column( 'id' );
    $sub_mod = lib::create( 'database\modifier' );
    $sub_mod->where( 'interview_id', '=', $this->id );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'assignment_id', 'IN', sprintf( '( %s %s )', $sub_sel->get_sql(), $sub_mod->get_sql() ), false );
    static::db()->execute( sprintf( 'DELETE FROM phone_call %s', $modifier->get_sql() ) );

    // delete all assignments
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview_id', '=', $this->id );
    static::db()->execute( sprintf( 'DELETE FROM assignment %s', $modifier->get_sql() ) );

    $this->delete();
  }

  /**
   * Forces an interview to become completed.
   * 
   * This method will update an interview's status to be complete.
   * @access public
   */
  public function force_complete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force complete interview with no primary key.' );
      return;
    }

    // do nothing if the interview is already set as completed
    if( !is_null( $this->end_datetime ) ) return;

    $util_class_name = lib::get_class_name( 'util' );
    $db_participant = $this->get_participant();

    // update the token and survey associated with this interview
    $now = $util_class_name::get_datetime_object();
    $db_script = $this->get_qnaire()->get_script();

    if( 'pine' == $db_script->get_type() )
    {
      $cenozo_manager = lib::create(
        'business\cenozo_manager',
        lib::create( 'business\session' )->get_pine_application()
      );
      $cenozo_manager->patch(
        // note that we have to use the root respondent/<identifier> service for this operation
        sprintf(
          'respondent/qnaire_id=%d;participant_id=%d?action=force_submit',
          $db_script->pine_qnaire_id,
          $db_participant->id
        ),
        new \stdClass
      );
    }

    // finally, update the record
    $this->complete();
  }

  /**
   * Launches any web interviews which are ready to proceed.
   * 
   * @param database\participant $db_participant If provided then only that participant will be affected by the operation.
   * @access protected
   * @return array( qnaire-id => array( uids ) ) An array of qnaires and the UIDs of participants who have been added
   * @static
   */
  public static function process_completed_web_interviews( $db_participant = NULL )
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $db_application = lib::create( 'business\session' )->get_application();

    // update all web-based interviews
    // start by getting a list of all appointments which will need to be moved to the next interview
    $interview_sel = lib::create( 'database\select' );
    $interview_sel->add_table_column( 'appointment', 'id', 'appointment_id' );
    $interview_sel->add_column( 'participant_id' );
    $interview_sel->add_table_column( 'qnaire', 'rank' );
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $interview_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'event.event_type_id', '=', 'script.finished_event_type_id', false );
    $join_mod->where( 'event.participant_id', '=', 'interview.participant_id', false );
    $interview_mod->join_modifier( 'event', $join_mod );
    $interview_mod->join( 'appointment', 'interview.id', 'appointment.interview_id' );
    $interview_mod->where( 'interview.method', '=', 'web' );
    $interview_mod->where( 'interview.end_datetime', '=', NULL );
    $interview_mod->where( 'appointment.assignment_id', '=', NULL );
    $interview_mod->where( 'appointment.outcome', '=', NULL );
    if( !is_null( $db_participant ) ) $interview_mod->where( 'interview.participant_id', '=', $db_participant->id );

    $appointment_list = array();
    foreach( static::select( $interview_sel, $interview_mod ) as $interview )
    {
      $appointment_list[] = array(
        'appointment_id' => $interview['appointment_id'],
        'participant_id' => $interview['participant_id'],
        'rank' => $interview['rank']
      );
    }

    // now close all web interviews which have the correct completed event
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $interview_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'event.event_type_id', '=', 'script.finished_event_type_id', false );
    $join_mod->where( 'event.participant_id', '=', 'interview.participant_id', false );
    $interview_mod->join_modifier( 'event', $join_mod );
    $interview_mod->where( 'interview.method', '=', 'web' );
    $interview_mod->where( 'interview.end_datetime', '=', NULL );
    if( !is_null( $db_participant ) ) $interview_mod->where( 'interview.participant_id', '=', $db_participant->id );

    static::db()->execute( sprintf(
      "UPDATE interview %s\n".
      "SET end_datetime = event.datetime\n".
      "WHERE %s",
      $interview_mod->get_join(),
      $interview_mod->get_where()
    ) );

    // create any web interviews which are ready to proceed after the required delay offset/unit
    // (match same day, not datetime since this will be run as cron job overnight)
    $interview_sel = lib::create( 'database\select' );
    $interview_sel->add_column( 'participant_id' );
    $interview_sel->add_table_column( 'next_qnaire', 'id', 'qnaire_id' );
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $interview_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'event.event_type_id', '=', 'script.finished_event_type_id', false );
    $join_mod->where( 'event.participant_id', '=', 'interview.participant_id', false );
    $interview_mod->join_modifier( 'event', $join_mod );
    $interview_mod->join( 'qnaire', 'qnaire.rank + 1', 'next_qnaire.rank', '', 'next_qnaire' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'next_qnaire.id', '=', 'next_interview.qnaire_id', false );
    $join_mod->where( 'interview.participant_id', '=', 'next_interview.participant_id', false );
    $interview_mod->join_modifier( 'interview', $join_mod, 'left', 'next_interview' );
    $interview_mod->where( 'interview.method', '=', 'web' );
    $interview_mod->where( 'interview.end_datetime', '!=', NULL );
    $interview_mod->where(
      'DATE( '.
        'CASE next_qnaire.delay_unit '.
          'WHEN "day" THEN interview.end_datetime + INTERVAL next_qnaire.delay_offset DAY '.
          'WHEN "week" THEN interview.end_datetime + INTERVAL next_qnaire.delay_offset WEEK '.
          'WHEN "month" THEN interview.end_datetime + INTERVAL next_qnaire.delay_offset MONTH '.
        'END '.
      ')',
      '<=',
      'DATE( UTC_TIMESTAMP() )',
      false
    );
    $interview_mod->where( 'next_interview.id', '=', NULL );
    if( !is_null( $db_participant ) ) $interview_mod->where( 'interview.participant_id', '=', $db_participant->id );
    $interview_mod->order( 'qnaire.id' );

    $qnaire_list = array();
    foreach( static::select( $interview_sel, $interview_mod ) as $interview )
    {
      if( !array_key_exists( $interview['qnaire_id'], $qnaire_list ) ) $qnaire_list[$interview['qnaire_id']] = array();
      $qnaire_list[$interview['qnaire_id']][] = lib::create( 'database\participant', $interview['participant_id'] )->uid;
    }

    foreach( $qnaire_list as $qnaire_id => $uid_list )
    {
      // make sure the interview records exist
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'uid', 'IN', $uid_list );

      static::db()->execute( sprintf(
        'INSERT INTO interview( qnaire_id, participant_id, method, start_datetime ) '.
        'SELECT %s, participant.id, "web", UTC_TIMESTAMP() '.
        'FROM participant '.
        '%s '.
        'ON DUPLICATE KEY UPDATE method = "web"',
        static::db()->format_string( $qnaire_id ),
        $modifier->get_sql()
      ) );
    }

    // finally, move all orphaned appointments to the next interview (or delete them if there is none)
    foreach( $appointment_list as $appointment )
    {
      $db_appointment = lib::create( 'database\appointment', $appointment['appointment_id'] );
      $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', $appointment['rank'] );
      if( 0 < $db_qnaire->delay_offset )
      {
        // if there is a delay before the next qnaire then we can't create an appointment for it
        $db_appointment->delete();
      }
      else
      {
        $db_next_qnaire = $qnaire_class_name::get_unique_record( 'rank', $appointment['rank'] + 1 );
        if( is_null( $db_next_qnaire ) ) $db_appointment->delete();
        else
        {
          $db_interview = static::get_unique_record(
            array( 'participant_id', 'qnaire_id' ),
            array( $appointment['participant_id'], $db_next_qnaire->id )
          );
          $db_appointment->interview_id = $db_interview->id;
          $db_appointment->save();
        }
      }
    }

    return $qnaire_list;
  }

  /**
   * Returns the SQL column value for an interview's page-based progress through a script
   * 
   * This function assumes that the interview table is included in the table list joined to its qnaire's script table
   * @return string
   */
  public static function get_page_progress_column()
  {
    return
      "IF(\n".
        "script.total_pages IS NULL,\n".
        "'Unknown',\n".
        "CONCAT(\n".
          "IF(\n".
            "interview.end_datetime IS NOT NULL,\n".
            "script.total_pages,\n".
            "IF( interview.current_page_rank IS NULL, 0, interview.current_page_rank )\n".
          "),\n".
          "' of ', script.total_pages\n".
        ")\n".
      ")";
  }
}
