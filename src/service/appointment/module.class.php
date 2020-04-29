<?php
/**
 * module.class.php
 *
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\appointment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\base_calendar_module
{
  /**
   * Contructor
   */
  public function __construct( $index, $service )
  {
    parent::__construct( $index, $service );
    $db_user = lib::create( 'business\session' )->get_user();
    $date_string = sprintf( 'DATE( CONVERT_TZ( start_vacancy.datetime, "UTC", "%s" ) )', $db_user->timezone );
    $this->lower_date = array( 'null' => false, 'column' => $date_string );
    $this->upper_date = array( 'null' => false, 'column' => $date_string );
  }

  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      $service_class_name = lib::get_class_name( 'service\service' );
      $vacancy_class_name = lib::get_class_name( 'database\vacancy' );
      $db_appointment = $this->get_resource();
      $db_interview = !is_null( $db_appointment )
                    ? $db_appointment->get_interview()
                    : ( 'interview' == $this->get_parent_subject() ? $this->get_parent_resource() : NULL );
      $db_participant = is_null( $db_interview ) ? NULL : $db_interview->get_participant();
      $db_effective_site = is_null( $db_participant ) ? NULL : $db_participant->get_effective_site();
      $method = $this->get_method();

      $session = lib::create( 'business\session' );
      $db_application = $session->get_application();
      $db_user = $session->get_user();
      $db_role = $session->get_role();

      // make sure the application has access to the participant
      if( !is_null( $db_appointment ) )
      {
        if( $db_application->release_based && !is_null( $db_participant ) )
        {
          $modifier = lib::create( 'database\modifier' );
          $modifier->where( 'participant_id', '=', $db_participant->id );
          if( 0 == $db_application->get_participant_count( $modifier ) )
          {
            $this->get_status()->set_code( 404 );
            return;
          }
        }

        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) )
        {
          if( is_null( $db_effective_site ) || $db_restrict_site->id != $db_effective_site->id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }

        // restrict operators to viewing appointments they are currently
        if( 'operator' == $db_role->name )
        {
          $db_assignment = $db_user->get_open_assignment();
          if( is_null( $db_assignment ) ||
              $db_participant->id != $db_assignment->get_interview()->participant_id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }
      }

      if( $service_class_name::is_write_method( $method ) )
      {
        $db_start_vacancy = is_null( $db_appointment ) ? NULL : $db_appointment->get_start_vacancy();
        $db_role = lib::create( 'business\session' )->get_role();

        // no writing of appointments if interview is completed
        if( !is_null( $db_interview ) && !is_null( $db_interview->end_datetime ) )
        {
          $this->set_data( 'Appointments cannot be changed after an interview is complete.' );
          $this->get_status()->set_code( 306 );
        }
        // no writing of appointments if they have passed
        else if( !is_null( $db_start_vacancy ) &&
                 $db_start_vacancy->datetime < util::get_datetime_object() )
        {
          $this->set_data( 'Appointments cannot be changed after they have passed.' );
          $this->get_status()->set_code( 306 );
        }
        // no writing of appointments if it is assigned
        else if( !is_null( $db_appointment ) && !is_null( $db_appointment->assignment_id ) )
        {
          $this->set_data( 'Appointments cannot be changed once they have been assigned.' );
          $this->get_status()->set_code( 306 );
        }
        // no new appointments if the script is complete
        else if( !is_null( $db_interview ) && $db_interview->is_survey_complete() )
        {
          $this->set_data(
            'Appointments cannot be created or changed for this interview since the associated survey has '.
            'been completed.  The participant must be advanced to the next interview before a new appointment '.
            'can be created.'
          );
          $this->get_status()->set_code( 306 );
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

    $session = lib::create( 'business\session' );
    $vacancy_size = lib::create( 'business\setting_manager' )->get_setting( 'general', 'vacancy_size' );

    $modifier->left_join( 'user', 'appointment.user_id', 'user.id' );
    $select->add_table_column( 'user', 'name', 'username' );

    if( $select->has_column( 'disable_mail' ) ) $select->add_constant( false, 'disable_mail', 'boolean' );

    if( !is_null( $this->get_resource() ) )
    {
      // include the user first/last/name as supplemental data
      $select->add_column(
        'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
        'formatted_user_id',
        false );
    }

    // include the participant uid, language and interview's qnaire rank as supplemental data
    $modifier->left_join( 'vacancy', 'appointment.start_vacancy_id', 'start_vacancy.id', 'start_vacancy' );
    $modifier->left_join( 'vacancy', 'appointment.end_vacancy_id', 'end_vacancy.id', 'end_vacancy' );
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $select->add_table_column( 'participant', 'uid' );
    $select->add_table_column( 'participant', 'global_note', 'help' );
    $select->add_table_column( 'language', 'code', 'language_code' );
    $select->add_table_column( 'qnaire', 'rank', 'qnaire_rank' );

    $participant_site_join_mod = lib::create( 'database\modifier' );
    $participant_site_join_mod->where(
      'interview.participant_id', '=', 'participant_site.participant_id', false );
    $participant_site_join_mod->where(
      'participant_site.application_id', '=', $session->get_application()->id );
    $modifier->join_modifier( 'participant_site', $participant_site_join_mod, 'left' );

    // restrict by site
    $db_restricted_site = $this->get_restricted_site();
    if( !is_null( $db_restricted_site ) )
      $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

    $modifier->join( 'setting', 'participant_site.site_id', 'setting.site_id' );

    if( $select->has_table_columns( 'script' ) )
      $modifier->join( 'script', 'qnaire.script_id', 'script.id' );

    $select->add_column( 'start_vacancy.datetime', 'start_datetime', false, 'datetime' );
    $select->add_column( sprintf( 'end_vacancy.datetime + INTERVAL %d MINUTE', $vacancy_size ), 'end_datetime', false, 'datetime' );

    if( $select->has_column( 'date' ) )
    {
      $date_string = sprintf(
        'DATE( CONVERT_TZ( start_vacancy.datetime, "UTC", "%s" ) )',
        $session->get_user()->timezone
      );
      $select->add_column( $date_string, 'date', false );
    }
    if( $select->has_column( 'start_time' ) )
      $select->add_column( 'TIME( start_vacancy.datetime )', 'start_time', false );
    if( $select->has_column( 'end_time' ) )
      $select->add_column( sprintf( 'TIME( end_vacancy.datetime + INTERVAL %d MINUTE )', $vacancy_size ), 'end_time', false );
    if( $select->has_column( 'duration' ) )
    {
      $select->add_column(
        sprintf( 'TIMESTAMPDIFF( MINUTE, start_vacancy.datetime, end_vacancy.datetime ) + %d', $vacancy_size ),
        'duration',
        false,
        'integer'
      );
    }

    if( $select->has_table_columns( 'assignment_user' ) )
    {
      $modifier->left_join( 'assignment', 'appointment.assignment_id', 'assignment.id' );
      $modifier->left_join( 'user', 'assignment.user_id', 'assignment_user.id', 'assignment_user' );
    }

    if( $select->has_table_column( 'phone', 'name' ) )
    {
      $modifier->left_join( 'phone', 'appointment.phone_id', 'phone.id' );
      $select->add_table_column(
        'phone', 'CONCAT( "(", phone.rank, ") ", phone.type, ": ", phone.number )', 'phone', false );
    }

    if( $select->has_column( 'state' ) )
    {
      if( !$modifier->has_join( 'assignment' ) )
        $modifier->left_join( 'assignment', 'appointment.assignment_id', 'assignment.id' );

      $phone_call_join_mod = lib::create( 'database\modifier' );
      $phone_call_join_mod->where( 'assignment.id', '=', 'phone_call.assignment_id', false );
      $phone_call_join_mod->where( 'phone_call.end_datetime', '=', NULL );
      $modifier->join_modifier( 'phone_call', $phone_call_join_mod, 'left' );

      // specialized sql used to determine the appointment's current state
      $sql =
        'IF( outcome IS NOT NULL, '.
            // the appointment has been fulfilled
            'outcome, '.
            // the appointment hasn't yet been fulfilled
            'IF( appointment.assignment_id IS NOT NULL, '.
                // the appointment has been assigned
                'IF( assignment.end_datetime IS NOT NULL, '.
                    // the assignment is finished (the appointment should be fulfilled, this is an error)
                    '"error", '.
                    // the assignment is in progress (either in phone call or not)
                    'IF( phone_call.id IS NOT NULL, "in progress", "assigned" ) '.
                '), '.
                // the appointment hasn't been assigned
                'IF( UTC_TIMESTAMP() < '.
                    'start_vacancy.datetime - INTERVAL IFNULL( pre_call_window, 0 ) MINUTE, '.
                    // the appointment is in the pre-appointment time
                    '"upcoming", '.
                    'IF( UTC_TIMESTAMP() < '.
                        'start_vacancy.datetime + INTERVAL IFNULL( post_call_window, 0 ) MINUTE, '.
                        // the appointment is in the post-appointment time
                        '"assignable", '.
                        // the appointment is after the post-appointment time
                        '"missed" '.
                    ') '.
                ') '.
            ') '.
        ')';

      $select->add_column( $sql, 'state', false );
    }
  }
}
