<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 */

namespace sabretooth\database;

/**
 * user: active record
 *
 * @package sabretooth\database
 */
class user extends active_record
{
  /**
   * Returns whether the user has the role for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_site
   * @param database\role $db_role
   * @return bool
   */
  public function has_access( $db_site, $db_role )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine access for record with no id' );
      return false;
    }

    $rows = self::get_one(
      'SELECT user_id '.
      'FROM user_access '.
      'WHERE user_id = '.$this->id.' '.
      'AND site_id = '.$db_site->id.' '.
      'AND role_id = '.$db_role->id );
    
    return count( $rows );
  }
  
  /**
   * Returns an array of site objects the user has access to (empty array if none).
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( database\site )
   */
  public function get_sites()
  {
    $sites = array();

    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to get sites for record with no id' );
      return $sites;
    }
    
    $site_ids = self::get_col(
      'SELECT site_id '.
      'FROM user_access '.
      'WHERE user_id = '.$this->id.' '.
      'GROUP BY site_id '.
      'ORDER BY site_id' );
    
    foreach( $site_ids as $site_id )
    {
      array_push( $sites, new site( $site_id ) );
    }

    return $sites;
  }

  /**
   * Returns an array of role objects the user has for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_site
   * @return array( database\role )
   */
  public function get_roles( $db_site )
  {
    $roles = array();

    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to get roles for record with no id' );
      return $roles;
    }
    
    if( is_null( $db_site ) )
    {
      \sabretooth\log::warning( 'Tried to get roles for null site' );
      return $roles;
    }
    
    $role_ids = self::get_col(
      'SELECT role_id '.
      'FROM user_access '.
      'WHERE user_id = '.$this->id.' '.
      'AND site_id = '.$db_site->id.' '.
      'ORDER BY role_id' );
    
    foreach( $role_ids as $role_id )
    {
      array_push( $roles, new role( $role_id ) );
    }

    return $roles;
  }

  /**
   * Returns an associative array of all sites the user has access to.
   * 
   * The access array is an array where every element has two elements: 'site' and 'roles'.
   * The 'site' element is an active record of the site which the user has access to, and 'roles'
   * is an array of active records of all roles the user has at that site.
   * If the user has no roles at any sites then an empty array is returned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   */
  public function get_access_array()
  {
    if( is_null( $this->id ) )
      throw new \sabretooth\exception\database( 'Tried to get access array for record with no id' );
    
    $access_array = array();

    $rows = self::get_all(
      'SELECT site_id, role_id '.
      'FROM user_access '.
      'WHERE user_id = '.$this->id.' '.
      'ORDER BY site_id, role_id' );
    
    $site = NULL;
    foreach( $rows as $row )
    {
      if( is_null( $site ) )
      { // first row, create the site and add its first role
        $site = new site( $row['site_id'] );
        $roles = array( new role( $row['role_id'] ) );
      }
      else if( $site->id == $row['site_id'] )
      { // same role as last time, add another role
        array_push( $roles, new role( $row['role_id'] ) );
      }
      else
      { // new site, add the current one to the access array and start a new one
        array_push( $access_array, array( 'site' => $site, 'roles' => $roles ) );
        $site = new site( $row['site_id'] );
        $roles = array( new role( $row['role_id'] ) );
      }
    }

    return $access_array;
  }
}
?>
