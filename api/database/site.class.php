<?php
/**
 * site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
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
      $select .= ( $first ? '' : ', ' ).'site.'.$primary_key_name;
      $first = false;
    }

    // sort by activity date
    if( 'activity.date' == $sort_column )
    {
      $primary_ids_list = self::get_all(
        'SELECT '.$select.' '.
        'FROM site '.
        'LEFT JOIN site_last_activity '.
        'ON site.id = site_last_activity.site_id '.
        'LEFT JOIN activity '.
        'ON site_last_activity.activity_id = activity.id '.
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
   * Returns the most recent activity performed to this site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\activity
   * @access public
   */
  public function get_last_activity()
  {
    $activity_id = self::get_one(
      'SELECT activity_id '.
      'FROM site_last_activity '.
      'WHERE site_id = '.$this->id.' ' );

    return is_null( $activity_id ) ? NULL : new activity( $activity_id );
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
    $count = 0;
    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to determine operation for record with no id' );
    }
    else
    {
      $count = self::get_one(
        'SELECT COUNT(*) '.
        'FROM user_access '.
        'WHERE site_id = '.$this->id );
    }

    return $count;
  }

  /**
   * Get a list of users that have access to this site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $count The number of users to return
   * @param int $offset The number of users to offset the selection by.
   * @param string $sort_column The name of a column to sort by.
   * @param boolean $descending Whether to sort in descending order.
   * @return array( database\user )
   * @access public
   */
  public function get_users( $count, $offset = 0, $sort_column = NULL, $descending = false )
  {
    $users = array();
    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to determine operation for record with no id' );
    }
    else
    {
      $ids = self::get_col(
        'SELECT user_id '.
        'FROM user_access '.
        'WHERE site_id = '.$this->id.' '.
        'GROUP BY user_id '.
        ( !is_null( $sort_column )
            ? 'ORDER BY '.$sort_column.' '.( $descending ? 'DESC ' : '' )
            : '' ).
        ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );
  
      foreach( $ids as $id )
      {
        array_push( $users, new user( $id ) );
      }
    }

    return $users;
  }
}
?>
