<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\interview;
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

    if( $select->has_table_columns( 'participant' ) || !$session->get_role()->all_sites )
    {
      $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );

      // restrict to participants in this site (for some roles)
      if( !$session->get_role()->all_sites )
      {
        $sub_mod = lib::create( 'database\modifier' );
        $sub_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
        $sub_mod->where( 'participant_site.application_id', '=', $session->get_application()->id );

        $modifier->join_modifier( 'participant_site', $sub_mod );
        $modifier->where( 'participant_site.site_id', '=', $session->get_site()->id );
      }
    }
  }
}
