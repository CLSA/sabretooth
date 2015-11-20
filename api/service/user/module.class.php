<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\user;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class module extends \cenozo\service\user\module
{
  /**
   * Extends parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // don't include instance users
    $join_sel = lib::create( 'database\select' );
    $join_sel->from( 'access' );
    $join_sel->add_column( 'user_id' );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->join( 'role', 'access.role_id', 'role.id' );
    $join_mod->where( 'role.name', '!=', 'opal' );
    $join_mod->group( 'user_id' );

    $modifier->left_join(
      sprintf( '( %s %s ) AS user_join_instance_access', $join_sel->get_sql(), $join_mod->get_sql() ),
      'user.id',
      'user_join_instance_access.user_id' );
    $modifier->where( 'user_join_instance_access.user_id', '!=', NULL );
  }
}
