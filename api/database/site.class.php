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
   * @param int $count The number of records to return
   * @param int $offset The 0-based index of the first record to start selecting from
   * @param string $sort_column Which column to sort by during the select.
   * @param boolean $descending Whether to sort descending or ascending.
   * @param array $restrictions And array of restrictions to add to the were clause of the select.
   * @return array( active_record )
   * @static
   * @access public
   */
  public static function select(
    $count = 0, $offset = 0, $sort_column = NULL, $descending = false, $restrictions = NULL )
  {
    // no need to override the basic functionality
    if( 'activity.date' != $sort_column )
    {
      return parent::select( $count, $offset, $sort_column, $descending, $restrictions );
    }

    // create special sql that sorts by the foreign column association
    $records = array();

    // build the restriction list
    $where = '';
    if( is_array( $restrictions ) && 0 < count( $restrictions ) )
    {
      $first = true;
      $where = 'WHERE ';
      foreach( $restrictions as $column => $value )
      {
        $where .= ( $first ? '' : 'AND ' )."$column = $value ";
        $first = false;
      }
    }

    // sort by activity date
    if( 'activity.date' == $sort_column )
    {
      $id_list = self::get_col(
        'SELECT site.id '.
        'FROM site '.
        'LEFT JOIN site_last_activity '.
        'ON site.id = site_last_activity.site_id '.
        'LEFT JOIN activity '.
        'ON site_last_activity.activity_id = activity.id '.
        $where.
        'ORDER BY '.$sort_column.' '.( $descending ? 'DESC ' : '' ).
        ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );
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
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for site record with no id' );
      return 0;
    }

    return self::get_one(
      'SELECT COUNT( DISTINCT user_id ) '.
      'FROM user_access '.
      'WHERE site_id = '.$this->id );
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
  public function get_user_list( $count = 0, $offset = 0, $sort_column = NULL, $descending = false )
  {
    $users = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for site record with no id' );
      return $users;
    }

    $ids = self::get_col(
      'SELECT user_id '.
      'FROM user_access '.
      'WHERE site_id = '.$this->id.' '.
      'GROUP BY user_id '.
      ( !is_null( $sort_column )
          ? 'ORDER BY '.$sort_column.' '.( $descending ? 'DESC ' : '' )
          : '' ).
      ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );

    foreach( $ids as $id ) array_push( $users, new user( $id ) );
    return $users;
  }
}
?>
