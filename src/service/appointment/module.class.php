<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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
    $this->lower_date = array( 'null' => false, 'column' => 'DATE( datetime )' );
    $this->upper_date = array( 'null' => false, 'column' => 'DATE( datetime )' );
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
      $db_appointment = $this->get_resource();
      $db_interview = is_null( $db_appointment ) ? $this->get_parent_resource() : $db_appointment->get_interview();
      $method = $this->get_method();

      $db_application = lib::create( 'business\session' )->get_application();

      // make sure the application has access to the participant
      if( !is_null( $db_appointment ) )
      {
        $db_participant = $db_interview->get_participant();
        if( $db_application->release_based )
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
          $db_effective_site = $db_participant->get_effective_site();
          if( is_null( $db_effective_site ) || $db_restrict_site->id != $db_effective_site->id )
          {
            $this->get_status()->set_code( 403 );
            return;
          }
        }
      }

      if( $service_class_name::is_write_method( $method ) )
      {
        // no writing of appointments if interview is completed
        if( !is_null( $db_interview ) && null !== $db_interview->end_datetime )
        {
          $this->set_data( 'Appointments cannot be changed after an interview is complete.' );
          $this->get_status()->set_code( 306 );
        }
        // no writing of appointments if they have passed
        else if( !is_null( $db_appointment ) && $db_appointment->datetime < util::get_datetime_object() )
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
        // don't allow tier-1 roles to override appointments
        else if( $db_appointment->override && 1 > lib::create( 'business\session' )->get_role()->tier )
        {
          $this->set_data( 'Your role does not allow appointments to be overridden.' );
          $this->get_status()->set_code( 306 );
        }
        else
        {
          // validate if we are changing the datetime
          if( 'POST' == $method ||
              ( 'PATCH' == $method && array_key_exists( 'datetime', $this->get_file_as_array() ) ) )
          {
            if( !$db_appointment->validate_date() )
            {
              $this->set_data( 'There are no operators available over the requested appointment timespan.' );
              $this->get_status()->set_code( 306 );
            }
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

    $session = lib::create( 'business\session' );

    // include the user first/last/name as supplemental data
    $modifier->left_join( 'user', 'appointment.user_id', 'user.id' );
    $select->add_column(
      'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
      'formatted_user_id',
      false );

    // include the participant uid and interview's qnaire rank as supplemental data
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $select->add_table_column( 'participant', 'uid' );
    $select->add_table_column( 'qnaire', 'rank', 'qnaire_rank' );

    $participant_site_join_mod = lib::create( 'database\modifier' );
    $participant_site_join_mod->where(
      'interview.participant_id', '=', 'participant_site.participant_id', false );
    $participant_site_join_mod->where(
      'participant_site.application_id', '=', $session->get_application()->id );
    $modifier->join_modifier( 'participant_site', $participant_site_join_mod, 'left' );

    // add the appointment's duration
    $modifier->join( 'setting', 'participant_site.site_id', 'setting.site_id' );
    $select->add_column(
      'IF( "long" = appointment.type, setting.long_appointment, setting.short_appointment )', 'duration', false );

    // restrict by site
    $db_restricted_site = $this->get_restricted_site();
    if( !is_null( $db_restricted_site ) )
      $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

    if( $select->has_table_columns( 'script' ) )
      $modifier->join( 'script', 'qnaire.script_id', 'script.id' );

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
                'IF( assignment.end_datetime IS NULL, '.
                    // the assignment is finished (the appointment should be fulfilled, this is an error)
                    '"incomplete", '.
                    // the assignment is in progress (either in phone call or not)
                    'IF( phone_call.id IS NOT NULL, "in progress", "assigned" ) '.
                '), '.
                // the appointment hasn't been assigned
                'IF( UTC_TIMESTAMP() < '.
                    'appointment.datetime - INTERVAL IFNULL( pre_call_window, 0 ) MINUTE, '.
                    // the appointment is in the pre-appointment time
                    '"upcoming", '.
                    'IF( UTC_TIMESTAMP() < '.
                        'appointment.datetime + INTERVAL IFNULL( post_call_window, 0 ) MINUTE, '.
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
