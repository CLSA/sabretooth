<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\role;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\role\query
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    $db_role = lib::create( 'business\session' )->get_role();
    if( $this->get_argument( 'granting', false ) )
    {
      // remove instance-based roles
      $this->modifier->where( 'role.name', '!=', 'opal' );

      // supervisors can only create other supervisors and operators
      if( 'supervisor' == $db_role->name )
        $this->modifier->where( 'role.name', 'IN', array( 'supervisor', 'operator' ) );
    }
  }
}
