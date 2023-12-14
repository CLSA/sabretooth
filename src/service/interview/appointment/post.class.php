<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\interview\appointment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the post meta-resource
 */
class post extends \cenozo\service\post
{
  /**
   * Override parent method
   */
  public function get_file_as_object()
  {
    // store non-standard columns into temporary variables
    $post_object = parent::get_file_as_object();

    if( property_exists( $post_object, 'duration' ) )
    {
      $this->duration = $post_object->duration;
      unset( $post_object->duration );
    }
    if( property_exists( $post_object, 'disable_email' ) )
    {
      $this->disable_mail = $post_object->disable_mail;
      unset( $post_object->disable_mail );
    }

    return $post_object;
  }

  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    $post_array = $this->get_file_as_array();

    $this->appointment_manager = lib::create( 'business\appointment_manager' );
    $this->appointment_manager->set_site( $this->get_parent_record()->get_participant()->get_effective_site() );
    // note that the 'start_datetime' is only used when there is no vacancy record (when overriding)
    $datetime = util::get_datetime_object( $post_array['start_datetime'] );
    if( $post_array['start_vacancy_id'] )
    {
      $db_start_vacancy = lib::create( 'database\vacancy', $post_array['start_vacancy_id'] );
      $datetime = $db_start_vacancy->datetime;
    }
    $this->appointment_manager->set_datetime_and_duration( $datetime, $this->duration );
  }

  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    if( $this->may_continue() )
    {
      $db_role = lib::create( 'business\session' )->get_role();

      if( 2 > $db_role->tier &&
          'operator+' != $db_role->name &&
          $this->appointment_manager->has_missing_vacancy() )
      {
        $this->appointment_manager->release();
        $this->get_status()->set_code( 409 );
        $this->set_data( array( 'start_datetime', 'duration' ) );
      }
    }
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $db_appointment = $this->get_leaf_record();
    $this->appointment_manager->set_appointment( $db_appointment );
    $this->appointment_manager->apply_vacancy_list();
    $this->appointment_manager->release();

    // repopulate the participant's queue
    $db_appointment->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * The appointment manager used by the patch service
   */
  protected $appointment_manager = NULL;

  /**
   * Caching variable
   */
  protected $duration = NULL;

  /**
   * Caching variable
   */
  protected $disable_mail = false;
}
