<?php
/**
 * delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class delete extends \cenozo\service\delete
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // make note of the user now so we can delete it after the instance is deleted
    $this->db_user = $this->get_leaf_record()->get_user();
  }

  /**
   * Extends parent method
   */
  protected function finish()
  {
    parent::finish();

    // remove the user from ldap
    $ldap_manager = lib::create( 'business\ldap_manager' );
    try
    {
      $ldap_manager->delete_user( $this->db_user->name );
    }
    catch( \cenozo\exception\ldap $e )
    {
      // only warn if there are problems deleting users
      log::warning( $e->get_raw_message() );
    }

    try
    {
      $this->db_user->delete();
    }
    catch( \cenozo\exception\notice $e )
    {
      $this->set_data( $e->get_notice() );
      $this->status->set_code( 406 );
    }
    catch( \cenozo\exception\database $e )
    {
      if( $e->is_constrained() )
      {
        $this->set_data( $e->get_failed_constraint_table() );
        $this->status->set_code( 409 );
      }
      else
      {
        $this->status->set_code( 500 );
        throw $e;
      }
    }
  }

  /**
   * Record cache
   * @var database\user
   * @access protected
   */
  protected $db_user = NULL;
}
