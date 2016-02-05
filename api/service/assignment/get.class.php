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
  protected function create_resource( $index )
  {
    return 'assignment' == $this->get_subject( $index ) &&
           0 === intval( $this->get_resource_value( $index ) ) ?
      lib::create( 'business\session' )->get_user()->get_open_assignment() :
      parent::create_resource( $index );
  }
}
