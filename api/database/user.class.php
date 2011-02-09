<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
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
   * @param database\modifier $modifier Modifications to the selection.
   * @param array $restrictions And array of restrictions to add to the were clause of the select.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select( $modifier = NULL )
  {
    // no need to override the basic functionality
    if( !$modifier->has_order( 'activity.date' ) )
    {
      return parent::select( $modifier );
    }
    
    // create special sql that sorts by the foreign column association
    $records = array();
    
    if( $modifier->has_order( 'activity.date' ) )
    { // sort by activity date
      $id_list = self::get_col(
        sprintf( 'SELECT user.id '.
                 'FROM user '.
                 'LEFT JOIN user_last_activity '.
                 'ON user.id = user_last_activity.user_id '.
                 'LEFT JOIN activity '.
                 'ON user_last_activity.activity_id = activity.id '.
                 '%s',
                 is_null( $modifier ) ? '' : $modifier->get_sql() ) );
    }

    foreach( $id_list as $id ) array_push( $records, new static( $id ) );
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
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine access for user record with no id.' );
      return 0;
    }

    $rows = self::get_one(
      sprintf( 'SELECT user_id '.
               'FROM user_access '.
               'WHERE user_id = %s '.
               'AND site_id = %s '.
               'AND role_id = %s ',
               self::format_string( $this->id ),
               self::format_string( $db_site->id ),
               self::format_string( $db_role->id ) ) );

    return count( $rows );
  }
  
  /**
   * Returns an array of site objects the user has access to (empty array if none).
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array( database\site )
   * @access public
   */
  public function get_site_list()
  {
    $sites = array();

    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to get sites for user record with no id.' );
      return $sites;
    }

    $site_ids = self::get_col(
      sprintf( 'SELECT site_id '.
               'FROM user_access '.
               'WHERE user_id = '.$this->id.' '.
               'GROUP BY site_id '.
               'ORDER BY site_id',
               self::format_string( $this->id ) ) );
      
    foreach( $site_ids as $site_id ) array_push( $sites, new site( $site_id ) );
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
  public function get_role_list( $db_site = null )
  {
    $roles = array();

    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to get roles for user record with no id.' );
      return $roles;
    }

    $role_ids = self::get_col(
      sprintf( 'SELECT role_id '.
               'FROM user_access '.
               'WHERE user_id = %s '.
               ( !is_null( $db_site ) ? 'AND site_id = %s ' : '' ).
               'ORDER BY role_id',
               self::format_string( $this->id ),
               !is_null( $db_site ) ? self::format_string( $db_site->id ) : '' ) );

    foreach( $role_ids as $role_id ) array_push( $roles, new role( $role_id ) );
    return $roles;
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
      sprintf( 'SELECT activity_id FROM user_last_activity WHERE user_id = %s',
               self::format_string( $this->id ) ) );
    
    return is_null( $activity_id ) ? NULL : new activity( $activity_id );
  }
}
?>
