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
class module extends \cenozo\service\module
{
  public function validate()
  {
    parent::validate();

    $service_class_name = lib::get_class_name( 'service\service' );
    $db_callback = $this->get_resource();
    $db_interview = is_null( $db_callback ) ? $this->get_parent_resource() : $db_callback->get_interview();

    if( $service_class_name::is_write_method( $this->get_method() ) )
    {
      // no writing of callbacks if interview is completed
      if( !is_null( $db_interview ) && null !== $db_interview->end_datetime )
      {
        $this->set_data( 'Callbacks cannot be changed after an interview is complete.' );
        $this->get_status()->set_code( 406 );
      }
      // no writing of callbacks if it has passed
      else if( !is_null( $db_callback ) && $db_callback->datetime < util::get_datetime_object() )
      {
        $this->set_data( 'Callbacks cannot be changed after they have passed.' );
        $this->get_status()->set_code( 406 );
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

    if( $select->has_table_columns( 'participant' ) )
    {
      $modifier->join( 'interview', 'callback.interview_id', 'interview.id' );
      $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    }

    if( $select->has_table_columns( 'qnaire' ) || $select->has_table_columns( 'script' ) )
    {
      $modifier->join( 'interview', 'callback.interview_id', 'interview.id' );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
      if( $select->has_table_columns( 'script' ) )
        $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    }

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
      if( !$modifier->has_join( 'interview' ) )
        $modifier->join( 'interview', 'callback.interview_id', 'interview.id' );

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
