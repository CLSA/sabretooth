<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\participant;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\participant\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( $this->get_argument( 'assignment', false ) )
    {
      $user_id = lib::create( 'business\session' )->get_user()->id;

      $modifier->join( 'queue_has_participant', 'participant.id', 'queue_has_participant.participant_id' );
      $modifier->join( 'queue', 'queue_has_participant.queue_id', 'queue.id' );
      $modifier->join( 'qnaire', 'queue_has_participant.qnaire_id', 'qnaire.id' );
      $modifier->where( 'participant.active', '=', true );
      $modifier->where( 'queue.rank', '!=', NULL );
      
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'queue_has_participant.queue_id', '=', 'queue_state.queue_id', false );
      $join_mod->where( 'queue_has_participant.site_id', '=', 'queue_state.site_id', false );
      $join_mod->where( 'queue_has_participant.qnaire_id', '=', 'queue_state.qnaire_id', false );
      $modifier->join_modifier( 'queue_state', $join_mod, 'left' );
      $modifier->where( 'queue_state.id', '=', NULL );

      // only show reserved appointments to the reserved user
      $modifier->left_join(
        'participant_last_interview', 'participant.id', 'participant_last_interview.participant_id' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant_last_interview.interview_id', '=', 'appointment.interview_id', false );
      $join_mod->where( 'appointment.assignment_id', '=', NULL );
      $modifier->join_modifier( 'appointment', $join_mod, 'left' );

      $modifier->where_bracket( true );
      $modifier->where( 'queue.name', '!=', 'assignable appointment' );
      $modifier->or_where( sprintf( 'IFNULL( appointment.user_id, %d )', $user_id ), '=', $user_id );
      $modifier->where_bracket( false );

      // must force all queues to repopulate
      $queue_class_name = lib::get_class_name( 'database\queue' );
      $queue_class_name::repopulate_time_specific();
    }
  }
}
