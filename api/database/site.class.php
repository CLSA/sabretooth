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

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT id ) FROM activity WHERE site_id = %s %s',
               self::format_string( $this->id ),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );
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

    $ids = self::get_col(
      sprintf( 'SELECT activity.id '.
               'FROM activity, user, site, role, operation '.
               'WHERE activity.user_id = user.id '.
               'AND activity.site_id = site.id '.
               'AND activity.role_id = role.id '.
               'AND activity.operation_id = operation.id '.
               'AND activity.site_id = %s '.
               '%s',
               self::format_string( $this->id ),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );

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

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT user_id ) FROM user_access WHERE site_id = %s %s',
               self::format_string( $this->id ),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );
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

    $ids = self::get_col(
      sprintf( 'SELECT user_access.user_id '.
               'FROM user_access, user '.
               'LEFT JOIN user_last_activity '.
               'ON user.id = user_last_activity.user_id '.
               'LEFT JOIN activity '.
               'ON user_last_activity.activity_id = activity.id '.
               'WHERE user_access.user_id = user.id '.
               'AND user_access.site_id = %s '.
               'GROUP BY user_access.user_id '.
               '%s',
               self::format_string( $this->id ),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );

    foreach( $ids as $id ) array_push( $users, new user( $id ) );
    return $users;
  }
}
?>
