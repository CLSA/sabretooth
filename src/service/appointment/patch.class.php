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
  public function get_file_as_array()
  {
    // store non-standard columns into temporary variables
    $patch_array = parent::get_file_as_array();
    if( array_key_exists( 'duration', $patch_array ) )
    {
      $this->update_vacancies = true;
      $this->duration = $patch_array['duration'];
      unset( $patch_array['duration'] );
    }
    if( array_key_exists( 'start_vacancy_id', $patch_array ) )
    {
      $this->update_vacancies = true;
      $this->start_vacancy_id = $patch_array['start_vacancy_id'];
      unset( $patch_array['start_vacancy_id'] );
    }
    else if( array_key_exists( 'start_datetime', $patch_array ) )
    {
      $this->update_vacancies = true;
      $this->start_datetime = $patch_array['start_datetime'];
      unset( $patch_array['start_datetime'] );
    }

    return $patch_array;
  }

  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    $this->get_file_as_array(); // run to make sure we've processed special patch data

    if( $this->update_vacancies )
    {
      $db_appointment = $this->get_leaf_record();

      // determine the start datetime
      if( !is_null( $this->start_vacancy_id ) )
        $datetime = lib::create( 'database\vacancy', $this->start_vacancy_id )->datetime;
      else if( !is_null( $this->start_datetime ) )
        $datetime = util::get_datetime_object( $this->start_datetime );
      else $datetime = $db_appointment->get_start_vacancy()->datetime;

      $this->appointment_manager = lib::create( 'business\appointment_manager' );
      $this->appointment_manager->set_site(
        $db_appointment->get_interview()->get_participant()->get_effective_site()
      );
      $this->appointment_manager->set_datetime_and_duration(
        $datetime,
        is_null( $this->duration ) ? $db_appointment->get_duration() : $this->duration
      );
    }
  }

  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      if( $this->update_vacancies )
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

    if( $this->update_vacancies )
    {
      $this->appointment_manager->apply_vacancy_list();
      $this->appointment_manager->release();

      // repopulate the participant's queue
      $this->get_leaf_record()->get_interview()->get_participant()->repopulate_queue( true );
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

  /**
   * Caching variable
   */
  protected $update_vacancies = false;

  /**
   * Caching variable
   */
  protected $start_vacancy_id = NULL;

  /**
   * Caching variable
   */
  protected $start_datetime = NULL;

  /**
   * Caching variable
   */
  protected $duration = NULL;
}
