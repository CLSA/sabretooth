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
   * Select a number of records.
   * 
   * This method overrides its parent method by adding functionality to sort the list by elements
   * outside of the user's table columns.
   * Currently users can be ordered by: activity.date
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $count The number of records to return
   * @param int $offset The 0-based index of the first record to start selecting from
   * @param string $sort_column Which column to sort by during the select.
   * @param boolean $descending Whether to sort descending or ascending.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select( $count = 0, $offset = 0, $sort_column = NULL, $descending = false )
  {
    // no need to override the basic functionality
    if( 'activity.date' != $sort_column )
    {
      return parent::select( $count, $offset, $sort_column, $descending );
    }
    
    // create special sql that sorts by the foreign column association
    $records = array();

    $primary_key_names = static::get_primary_key_names();
    $select = '';
    $first = true;
    foreach( $primary_key_names as $primary_key_name )
    {
      $select .= ( $first ? '' : ', ' ).'user.'.$primary_key_name;
      $first = false;
    }
    
    // sort by activity date
    if( 'activity.date' == $sort_column )
    {
      $primary_ids_list = self::get_all(
        'SELECT '.$select.' '.
        'FROM user '.
        'LEFT JOIN user_last_activity '.
        'ON user.id = user_last_activity.user_id '.
        'LEFT JOIN activity '.
        'ON user_last_activity.activity_id = activity.id '.
        'ORDER BY '.$sort_column.' '.( $descending ? 'DESC ' : '' ).
        ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );
    }

    foreach( $primary_ids_list as $primary_ids )
    {
      array_push( $records, new static( $primary_ids ) );
    }

    return $records;
  }
      
  /**
   * Returns whether the user has the role for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_site
   * @param database\role $db_role
   * @return bool
   * @access public
   */
  public function has_access( $db_site, $db_role )
  {
    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to determine access for record without primary ids' );
    }
    else
    {
      $rows = self::get_one(
        'SELECT user_id '.
        'FROM user_access '.
        'WHERE user_id = '.$this->id.' '.
        'AND site_id = '.$db_site->id.' '.
        'AND role_id = '.$db_role->id );
    }

    return count( $rows );
  }
  
  /**
   * Returns an array of site objects the user has access to (empty array if none).
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( database\site )
   * @access public
   */
  public function get_sites()
  {
    $sites = array();

    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to get sites for record without primary ids' );
    }
    else
    {
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
    }
    return $sites;
  }

  /**
   * Returns an array of role objects the user has for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\site $db_site Restrict to those roles for the given site, or if NULL then all.
   * @return array( database\role )
   * @access public
   */
  public function get_roles( $db_site = null )
  {
    $roles = array();

    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to get roles for record without primary ids' );
    }
    else
    {
      $role_ids = self::get_col(
        'SELECT role_id '.
        'FROM user_access '.
        'WHERE user_id = '.$this->id.' '.
        ( !is_null( $db_site ) ? 'AND site_id = '.$db_site->id.' ' : '' ).
        'ORDER BY role_id' );
      
      foreach( $role_ids as $role_id )
      {
        array_push( $roles, new role( $role_id ) );
      }
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
   * @access public
   */
  public function get_access_array()
  {
    $access_array = array();
    
    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to get access array for record without primary ids' );
    }
    else
    {
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
    }

    return $access_array;
  }

  /**
   * Returns the most recent activity performed by this user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\activity
   * @access public
   */
  public function get_last_activity()
  {
    $activity_id = self::get_one(
      'SELECT activity_id '.
      'FROM user_last_activity '.
      'WHERE user_id = '.$this->id.' ' );
    
    return is_null( $activity_id ) ? NULL : new activity( $activity_id );
  }
}
?>
