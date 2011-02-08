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
   * Select a number of records.
   * 
   * This method overrides its parent method by adding functionality to sort the list by elements
   * outside of the site's table columns.
   * Currently sites can be ordered by: activity.date
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
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
        sprintf( 'SELECT site.id '.
                 'FROM %s '.
                 'LEFT JOIN site_last_activity '.
                 'ON site.id = site_last_activity.site_id '.
                 'LEFT JOIN activity '.
                 'ON site_last_activity.activity_id = activity.id '.
                 '%s',
                 self::get_table_name(),
                 is_null( $modifier ) ? '' : $modifier->get_sql() ) );
    }

    foreach( $id_list as $id )
    {
      array_push( $records, new static( $id ) );
    }

    return $records;
  }

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
   * @return int
   * @access public
   */
  public function get_activity_count()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query site record with no id.' );
      return 0;
    }

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT id ) FROM activity WHERE site_id = %s',
               self::format_string( $this->id ) ) );
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
   * @return int
   * @access public
   */
  public function get_user_count()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query site record with no id.' );
      return 0;
    }

    return self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT user_id ) FROM user_access WHERE site_id = %s',
               self::format_string( $this->id ) ) );
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
