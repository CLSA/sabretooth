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
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $session = lib::create( 'business\session' );

    if( $select->has_table_columns( 'participant' ) )
    {
      if( !$modifier->has_join( 'interview' ) )
        $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
      $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    }

    if( $select->has_table_columns( 'qnaire' ) )
    {
      if( !$modifier->has_join( 'interview' ) )
        $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
      $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    }

    if( $select->has_table_columns( 'user' ) )
    {
      if( !$modifier->has_join( 'assignment' ) )
        $modifier->left_join( 'assignment', 'appointment.assignment_id', 'assignment.id' );
      $modifier->left_join( 'user', 'assignment.user_id', 'user.id' );
    }

    if( $select->has_table_column( 'phone', 'name' ) )
    {
      $modifier->left_join( 'phone', 'appointment.phone_id', 'phone.id' );
      $select->add_table_column(
        'phone', 'CONCAT( "(", phone.rank, ") ", phone.type, ": ", phone.number )', 'phone', false );
    }

    if( $select->has_column( 'state' ) )
    {
      $select->add_column(
        'IF( reached IS NOT NULL, '.
            'IF( reached, "reached", "not reached" ), '.
            '"TODO" '.
        ')',
        'state', false );
    }
  }
}
