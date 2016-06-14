<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\callback;
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
      $db_callback = $this->get_resource();
      $db_interview = is_null( $db_callback ) ? $this->get_parent_resource() : $db_callback->get_interview();
      $method = $this->get_method();

      $db_application = lib::create( 'business\session' )->get_application();

      // make sure the application has access to the participant
      if( !is_null( $db_callback ) ) 
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
        // no writing of callbacks if interview is completed
        if( !is_null( $db_interview ) && null !== $db_interview->end_datetime )
        {
          $this->set_data( 'Callbacks cannot be changed after an interview is complete.' );
          $this->get_status()->set_code( 306 );
        }
        // no writing of callbacks if it is assigned
        else if( !is_null( $db_callback ) && !is_null( $db_callback->assignment_id ) )
        {
          $this->set_data( 'Callbacks cannot be changed after they have been assigned.' );
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
    $modifier->join( 'interview', 'callback.interview_id', 'interview.id' );
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

    // restrict by site
    $db_restricted_site = $this->get_restricted_site();
    if( !is_null( $db_restricted_site ) )
      $modifier->where( 'participant_site.site_id', '=', $db_restricted_site->id );

    if( $select->has_table_columns( 'script' ) )
      $modifier->join( 'script', 'qnaire.script_id', 'script.id' );

    if( $select->has_table_columns( 'assignment_user' ) )
    {
      $modifier->left_join( 'assignment', 'callback.assignment_id', 'assignment.id' );
      $modifier->left_join( 'user', 'assignment.user_id', 'assignment_user.id', 'assignment_user' );
    }

    if( $select->has_table_column( 'phone', 'name' ) )
    {
      $modifier->left_join( 'phone', 'callback.phone_id', 'phone.id' );
      $select->add_table_column(
        'phone', 'CONCAT( "(", phone.rank, ") ", phone.type, ": ", phone.number )', 'phone', false );
    }

    if( $select->has_column( 'state' ) )
    {
      if( !$modifier->has_join( 'assignment' ) )
        $modifier->left_join( 'assignment', 'callback.assignment_id', 'assignment.id' );

      $phone_call_join_mod = lib::create( 'database\modifier' );
      $phone_call_join_mod->where( 'assignment.id', '=', 'phone_call.assignment_id', false );
      $phone_call_join_mod->where( 'phone_call.end_datetime', '=', NULL );
      $modifier->join_modifier( 'phone_call', $phone_call_join_mod, 'left' );

      // specialized sql used to determine the callback's current state
      $sql =
        'IF( reached IS NOT NULL, '.
            // the callback has been fulfilled
            'IF( reached, "reached", "not reached" ), '.
            // the callback hasn't yet been fulfilled
            'IF( callback.assignment_id IS NOT NULL, '.
                // the callback has been assigned
                'IF( assignment.end_datetime IS NULL, '.
                    // the assignment is finished (the callback should be fulfilled, this is an error)
                    '"incomplete", '.
                    // the assignment is in progress (either in phone call or not)
                    'IF( phone_call.id IS NOT NULL, "in progress", "assigned" ) '.
                '), '.
                // the callback hasn't been assigned
                'IF( UTC_TIMESTAMP() < callback.datetime, '.
                    // the callback is in the pre-callback time
                    '"upcoming", '.
                    // the callback is in the post-callback time
                    '"assignable" '.
                ') '.
            ') '.
        ')';

      $select->add_column( $sql, 'state', false );
    }
  }
}
