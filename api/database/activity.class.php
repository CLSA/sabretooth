<?php
/**
 * activity.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * activity: active record
 *
 * @package sabretooth\database
 */
class activity extends active_record
{
  /**
   * Gets the activity's user.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\user
   * @access public
   */
  public function get_user()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine user for activity record with no id.' );
      return NULL;
    }

    $id = self::get_one(
      'SELECT user_id '.
      'FROM '.static::get_table_name().' '.
      'WHERE id = '.$this->id );

    return is_null( $id ) ? NULL : new user( $id );
  }

  /**
   * Gets the activity's site.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\site
   * @access public
   */
  public function get_site()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine site for activity record with no id.' );
      return NULL;
    }

    $id = self::get_one(
      'SELECT site_id '.
      'FROM '.static::get_table_name().' '.
      'WHERE id = '.$this->id );

    return is_null( $id ) ? NULL : new site( $id );
  }

  /**
   * Gets the activity's role.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\role
   * @access public
   */
  public function get_role()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine role for activity record with no id.' );
      return NULL;
    }

    $id = self::get_one(
      'SELECT role_id '.
      'FROM '.static::get_table_name().' '.
      'WHERE id = '.$this->id );

    return is_null( $id ) ? NULL : new role( $id );
  }

  /**
   * Gets the activity's operation.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\operation
   * @access public
   */
  public function get_operation()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for activity record with no id.' );
      return NULL;
    }

    $id = self::get_one(
      'SELECT operation_id '.
      'FROM '.static::get_table_name().' '.
      'WHERE id = '.$this->id );

    return is_null( $id ) ? NULL : new operation( $id );
  }
}
?>
