<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\appointment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\patch
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list = array_merge(
      $this->extract_parameter_list,
      [ 'duration', 'start_vacancy_id', 'start_datetime']
    );

    parent::prepare();

    $this->get_file_as_array(); // run to make sure we've processed special patch data

    $duration = $this->get_argument( 'duration', NULL );
    $start_vacancy_id = $this->get_argument( 'start_vacancy_id', NULL );
    $start_datetime = $this->get_argument( 'start_datetime', NULL );

    if( !is_null( $duration ) || !is_null( $start_vacancy_id ) || !is_null( $start_datetime ) )
    {
      $db_appointment = $this->get_leaf_record();

      // determine the start datetime
      if( !is_null( $start_vacancy_id ) )
        $datetime = lib::create( 'database\vacancy', $start_vacancy_id )->datetime;
      else if( !is_null( $start_datetime ) )
        $datetime = util::get_datetime_object( $start_datetime );
      else $datetime = $db_appointment->get_start_vacancy()->datetime;

      $this->appointment_manager = lib::create( 'business\appointment_manager' );
      $this->appointment_manager->set_site(
        $db_appointment->get_interview()->get_participant()->get_effective_site()
      );
      $this->appointment_manager->set_datetime_and_duration(
        $datetime,
        is_null( $duration ) ? $db_appointment->get_duration() : $duration
      );
    }
  }

  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    if( $this->may_continue() )
    {
      // if the appointment manager is defined then we're changing the duration, vacancy or start datetime
      if( !is_null( $this->appointment_manager ) )
      {
        $db_role = lib::create( 'business\session' )->get_role();

        $this->appointment_manager->set_appointment( $this->get_leaf_record() );
        if( 2 > $db_role->tier &&
            'operator+' != $db_role->name &&
            $this->appointment_manager->has_missing_vacancy() )
        {
          $this->appointment_manager->release();
          $this->get_status()->set_code( 306 );
          $this->set_data( 'Unable to set appointment time and duration due to missing vacancy.' );
        }
      }
    }
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    // if the appointment manager is defined then we're changing the duration, vacancy or start datetime
    if( !is_null( $this->appointment_manager ) )
    {
      $this->appointment_manager->apply_vacancy_list();
      $this->appointment_manager->release();

      // repopulate the participant's queue
      $this->get_leaf_record()->get_interview()->get_participant()->repopulate_queue( true );
    }

    // PLEASE NOTE:
    // The "add_email" option is used to add an appointment's mail reminders after is has been created.
    // We can't do this at the time that the appointment is created because overridden appointments create
    // their vacancies as part of a trigger, so the software layer won't be aware of the change until after
    // the appointment has been created.  Therefore an additional request must be made after the new
    // appointment has been created.
    if( $this->get_argument( 'add_mail', false ) )
    {
      $db_appointment = $this->get_leaf_record();
      $db_appointment->add_mail();
    }

    // PLEASE NOTE:
    // The "update_email" option is used to update an appointment's mail reminders after the start vacancy
    // has been changed.  We can't do this at the time that the vacancy is changed because the start_vacancy_id
    // column is updated as part of a trigger, so the software layer won't be aware of the change until after
    // the process which made the change is complete.  Therefore an additional request must be made after
    // the change in start vacancy.
    if( $this->get_argument( 'update_mail', false ) )
    {
      $db_appointment = $this->get_leaf_record();
      $db_appointment->update_mail();
    }
  }

  /**
   * The appointment manager used by the patch service
   */
  protected $appointment_manager = NULL;
}
