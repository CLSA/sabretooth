<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) ) $modifier->where( 'opal_instance.site_id', '=', $db_restrict_site->id );

    if( $select->has_table_columns( 'participant' ) )
    {
      $modifier->join( 'interview', 'opal_instance.interview_id', 'interview.id' );
      $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    }

    // add the opal_instance's user
    if( $select->has_alias( 'username' ) || $select->has_alias( 'active' ) )
    {
      $modifier->join( 'user', 'opal_instance.user_id', 'user.id' );
      if( $select->has_alias( 'active' ) ) $select->add_table_column( 'user', 'active', 'active' );
      if( $select->has_alias( 'username' ) ) $select->add_table_column( 'user', 'name', 'username' );
    }

    // add the opal_instance's last access column
    if( $select->has_alias( 'last_access_datetime' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'access' );
      $join_sel->add_column( 'user_id' );
      $join_sel->add_column( 'MAX( access.datetime )', 'last_access_datetime', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->group( 'user_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS user_join_access', $join_sel->get_sql(), $join_mod->get_sql() ),
        'opal_instance.user_id',
        'user_join_access.user_id' );

      $select->add_column( 'user_join_access.last_access_datetime', 'last_access_datetime', false );
    }
  }
}
