<?php
/**
 * role.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * role: active record
 *
 * @package sabretooth\database
 */
class role extends active_record
{
  /**
   * Get the number of users that have this role at any site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_user_count()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query role record with no id' );
      return 0;
    }

    $count = self::get_one(
      'SELECT COUNT( DISTINCT user_id ) '.
      'FROM user_access '.
      'WHERE role_id = '.$this->id );

    return $count;
  }

  /**
   * Get the number of operations this role has access to.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_operation_count()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query role record with no id' );
      return 0;
    }

    $count = self::get_one(
      'SELECT COUNT( DISTINCT operation_id ) '.
      'FROM role_has_operation '.
      'WHERE role_id = '.$this->id );

    return $count;
  }

  /**
   * Returns whether the user has the role for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\operation $db_operation
   * @return bool
   */
  public function has_operation( $db_operation )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for role record with no id.' );
      return false;
    }

    if( is_null( $db_operation->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation without an id.' );
      return false;
    }

    $rows = self::get_one(
      'SELECT * '.
      'FROM role_has_operation '.
      'WHERE role_id = '.$this->id.' '.
      'AND operation_id = '.$db_operation->id );
    $result = 0 < count( $rows );

    return $result;
  }
  
  /**
   * Get a list of operations this role has access to.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $count The number of operations to return
   * @param int $offset The number of operations to offset the selection by.
   * @param string $sort_column The name of a column to sort by.
   * @param boolean $descending Whether to sort in descending order.
   * @return array( database\operation )
   * @access public
   */
  public function get_operation_list( $count = 0, $offset = 0, $sort_column = NULL, $descending = false )
  {
    $operations = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for role record with no id' );
      return $operations;
    }

    $ids = self::get_col(
      'SELECT operation.id '.
      'FROM role_has_operation rho, operation '.
      'WHERE rho.operation_id = operation.id '.
      'AND role_id = '.$this->id.' '.
      'GROUP BY operation.id '.
      ( !is_null( $sort_column )
          ? 'ORDER BY '.$sort_column.' '.( $descending ? 'DESC ' : '' )
          : '' ).
      ( 0 < $count ? 'LIMIT '.$count.' OFFSET '.$offset : '' ) );

    foreach( $ids as $id ) array_push( $operations, new operation( $id ) );
    return $operations;
  }
}
?>
