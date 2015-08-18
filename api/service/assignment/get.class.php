<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\assignment;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling 
 */
class get extends \cenozo\service\get
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    parent::prepare();

    if( 0 === intval( $this->get_resource_value( 0 ) ) && 404 == $this->status->get_code() )
      $this->status->set_code( 307 ); // temporary redirect since the user has no open assignment
  }

  /**
   * Override parent method
   */
  public function get_resource( $index )
  {
    if( 0 === intval( $this->get_resource_value( $index ) ) )
    {
      $db_user = lib::create( 'business\session' )->get_user();
      $assignment_mod = lib::create( 'database\modifier' );
      $assignment_mod->where( 'end_datetime', '=', NULL );
      $assignment_mod->order_desc( 'start_datetime' );
      $assignment_list = $db_user->get_assignment_object_list( $assignment_mod );
      if( 1 < count( $assignment_list ) )
        log::warning( sprintf( 'User %d (%s) has more than one open assignment!', $db_user->id, $db_user->name ) );
      return 0 < count( $assignment_list ) ? current( $assignment_list ) : NULL;
    }

    return parent::get_resource( $index );
  }
}
