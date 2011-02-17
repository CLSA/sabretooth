<?php
/**
 * site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * site: active record
 *
 * @package sabretooth\database
 */
class site extends active_record
{
  /**
   * Returns the most recent activity performed to this site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\activity
   * @access public
   */
  public function get_last_activity()
  {
    $activity_id = self::get_one(
      sprintf( 'SELECT activity_id FROM site_last_activity WHERE site_id = %s',
               self::format_string( $this->id ) ) );

    return is_null( $activity_id ) ? NULL : new activity( $activity_id );
  }

  /**
   * Get the number of activity entries for this site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @access public
   */
  public function get_activity_count( $modifier = NULL )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query site record with no id.' );
      return 0;
    }
    
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'site_id', $this->id );

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT id ) FROM activity %s',
               $modifier->get_sql() ) );
  }

  /**
   * Get an activity list for this site.
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
      \sabretooth\log::warning( 'Tried to query site record with no id.' );
      return $activity_list;
    }

    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'activity.user_id', 'user.id', false );
    $modifier->where( 'activity.site_id', 'site.id', false );
    $modifier->where( 'activity.role_id', 'role.id', false );
    $modifier->where( 'activity.operation_id', 'operation.id', false );
    $modifier->where( 'activity.site_id', $this->id );

    $ids = self::get_col(
      sprintf( 'SELECT activity.id FROM activity, user, site, role, operation %s',
               $modifier->get_sql() ) );

    foreach( $ids as $id ) array_push( $activity_list, new activity( $id ) );
    return $activity_list;
  }

  /**
   * Get the number of users that have access to this site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return int
   * @access public
   */
  public function get_user_count( $modifier = NULL )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query site record with no id.' );
      return 0;
    }

    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'site_id', $this->id );

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT user_id ) FROM user_access %s',
               $modifier->get_sql() ) );
  }

  /**
   * Get a list of users that have access to this site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( database\user )
   * @access public
   */
  public function get_user_list( $modifier = NULL )
  {
    $users = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query site record with no id.' );
      return $users;
    }

    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'user_access.user_id', 'user.id', false );
    $modifier->where( 'user_access.site_id', $this->id );
    $modifier->group( 'user_access.user_id' );

    $ids = self::get_col(
      sprintf( 'SELECT user_access.user_id '.
               'FROM user_access, user '.
               'LEFT JOIN user_last_activity '.
               'ON user.id = user_last_activity.user_id '.
               'LEFT JOIN activity '.
               'ON user_last_activity.activity_id = activity.id '.
               '%s',
               $modifier->get_sql() ) );

    foreach( $ids as $id ) array_push( $users, new user( $id ) );
    return $users;
  }

  // TODO: implement add_user( $user_id_list, $role_id )
  // TODO: implement remove_user( $user_id_list, $role_id )
}
?>
