<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\assignment;
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

    // restrict to participants in this site (for some roles)
    if( !$session->get_role()->all_sites )
    {
      $modifier->where( 'assignment.site_id', '=', $session->get_site()->id );
    }

    if( $select->has_table_columns( 'participant' ) || $select->has_table_columns( 'qnaire' ) )
    {
      $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
      if( $select->has_table_columns( 'participant' ) )
        $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
      if( $select->has_table_columns( 'qnaire' ) )
        $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    }

    // add the assignment's last call's status column
    $modifier->left_join( 'assignment_last_phone_call',
      'assignment.id', 'assignment_last_phone_call.assignment_id' );
    $modifier->left_join( 'phone_call AS last_phone_call',
      'assignment_last_phone_call.phone_call_id', 'last_phone_call.id' );
    $select->add_table_column( 'last_phone_call', 'status' );
  }
}
