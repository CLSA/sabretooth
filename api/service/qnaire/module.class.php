<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\qnaire;
use cenozo\lib, cenozo\log, cenozo\util;

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
      $modifier->where( 'qnaire.site_id', '=', $session->get_site()->id );
    }

    if( $select->has_table_columns( 'participant' ) )
    {
      $modifier->join( 'interview', 'qnaire.interview_id', 'interview.id' );
      $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    }

    // add the qnaire's last call's status column
    $modifier->left_join( 'qnaire_last_phone_call',
      'qnaire.id', 'qnaire_last_phone_call.qnaire_id' );
    $modifier->left_join( 'phone_call AS last_phone_call',
      'qnaire_last_phone_call.phone_call_id', 'last_phone_call.id' );
    $select->add_table_column( 'last_phone_call', 'status' );
  }
}
