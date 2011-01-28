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
  public function get_user_count()
  {
    $count = 0;
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for record with no id' );
      return $count;
    }

    $count = self::get_one(
      'SELECT COUNT(*) '.
      'FROM user_access '.
      'WHERE site_id = '.$this->id );

    return $count;
  }

  public function get_users( $count, $offset = 0, $sort_column = NULL )
  {
    $users = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for record with no id' );
      return $users;
    }

    $ids = self::get_col(
      'SELECT user_id '.
      'FROM user_access '.
      'WHERE site_id = '.$this->id.' '.
      'GROUP BY user_id '.
      ( !is_null( $sort_column ) ? 'ORDER BY '.$sort_column.' ' : '' ).
      ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );

    foreach( $ids as $id )
    {
      array_push( $users, new user( $id ) );
    }

    return $users;
  }
}
?>
