<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
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

    if( $this->service->may_continue() )
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

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->left_join( 'trainee_user', 'user.id', 'trainee_user.user_id' );
    $select->add_column( 'trainee_user.id IS NOT NULL', 'trainee_user', false, 'boolean' );

    // when listing users only show those of the same review type
    if( is_null( $this->get_resource() ) )
    {
      $trainee_user = lib::create( 'business\session' )->get_user()->get_trainee_user();
      $modifier->where( 'trainee_user.id', $trainee_user ? '!=' : '=', NULL );
    }
  }
}
