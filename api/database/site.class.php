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
