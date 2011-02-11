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
   * Get the number of activity entries for this user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_activity_count()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query user record with no id.' );
      return 0;
    }

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT id ) FROM activity WHERE user_id = %s',
               self::format_string( $this->id ) ) );
  }

  /**
   * Get an activity list for this user.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( database\activity )
   * @access public
   */
  public function get_activity_list( $modifier = NULL )
  {
    $activity_list = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query user record with no id.' );
      return $activity_list;
    }
    
    // need to further set up the modifier
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'activity.user_id', 'user.id', false );
    $modifier->where( 'activity.site_id', 'site.id', false );
    $modifier->where( 'activity.role_id', 'role.id', false );
    $modifier->where( 'activity.operation_id', 'operation.id', false );
    $modifier->where( 'activity.user_id', $this->id );

    $ids = self::get_col(
      sprintf( 'SELECT activity.id '.
               'FROM activity, user, site, role, operation '.
               '%s',
               $modifier->get_sql() ) );

    foreach( $ids as $id ) array_push( $activity_list, new activity( $id ) );
    return $activity_list;
  }

  /**
   * Get the number of sites that this user has access to.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_site_count()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query user record with no id.' );
      return 0;
    }

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT site_id ) FROM user_access WHERE user_id = %s',
               self::format_string( $this->id ) ) );
  }

  /**
   * Returns an array of site objects the user has access to (empty array if none).
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( database\site )
   * @access public
   */
  public function get_site_list( $modifier = NULL )
  {
    $sites = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to get sites for user record with no id.' );
      return $sites;
    }
    
    // need to further set up the modifier
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'user_access.site_id', 'site.id', false );
    $modifier->where( 'user_id', $this->id );
    $modifier->group( 'site_id' );

    $ids = self::get_col(
      sprintf( 'SELECT site_id '.
               'FROM user_access, site '.
               '%s',
               $modifier->get_sql() ) );
      
    foreach( $ids as $id ) array_push( $sites, new site( $id ) );
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
    
    $modifier = new modifier();
    $modifier->where( 'user_id', $this->id );
    if( !is_null( $db_site ) ) $modifier->where( 'site_id', $db_site->id );
    $modifier->order( 'role_id' );

    $role_ids = self::get_col(
      sprintf( 'SELECT role_id '.
               'FROM user_access '.
               '%s',
               $modifier->get_sql() ) );

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
