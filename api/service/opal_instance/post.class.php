<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    if( 300 > $this->status->get_code() )
    {
      $db_user = $this->get_leaf_record()->get_user();

      // add the user to ldap
      $ldap_manager = lib::create( 'business\ldap_manager' );
      try
      {
        $object = $this->get_file_as_object();
        $ldap_manager->new_user( $db_user->name, $db_user->first_name, $db_user->last_name, $object->password );
      }
      catch( \cenozo\exception\ldap $e )
      {
        // catch already exists exceptions, no need to report them
        if( !$e->is_already_exists() ) throw $e;
      }
    }
  }

  /**
   * Extends parent method
   */
  protected function get_leaf_record()
  {
    // TODO: referencing problem.  We're setting the user_id, but then it isn't present in $this->new_record
    $db_opal_instance = parent::get_leaf_record();

    if( is_null( $db_opal_instance->user_id ) )
    { // create a user for this opal instance
      $role_class_name = lib::get_class_name( 'database\role' );

      $object = $this->get_file_as_object();
      $db_site = lib::create( 'business\session' )->get_site();
      $db_role = $role_class_name::get_unique_record( 'name', 'opal' );
      $db_user = lib::create( 'database\user' );

      foreach( $db_user->get_column_names() as $column_name )
        if( 'id' != $column_name && property_exists( $object, $column_name ) )
          $db_user->$column_name = $object->$column_name;
      $db_user->name = $object->username;
      $db_user->first_name = $db_site->name.' opal instance';
      $db_user->last_name = $object->username;
      $db_user->active = true;
      $db_user->password = util::encrypt( $object->password );
      $db_user->save();

      // grant opal-access to the user
      $db_access = lib::create( 'database\access' );
      $db_access->user_id = $db_user->id;
      $db_access->site_id = $db_site->id;
      $db_access->role_id = $db_role->id;

      $db_opal_instance = lib::create( 'database\opal_instance' );
      $db_opal_instance->user_id = $db_user->id;
    }

    return $db_opal_instance;
  }
}
