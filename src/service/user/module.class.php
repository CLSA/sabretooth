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
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\user\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      $record = $this->get_resource();

      if( $record && $record->id )
      {
        // only allow tier-1 users to get themselves
        $session = lib::create( 'business\session' );
        $db_user = $session->get_user();
        $db_role = $session->get_role();
        if( 1 == $db_role->tier && $record->id != $db_user->id ) $this->get_status()->set_code( 403 );
      }
    }
  }
}
