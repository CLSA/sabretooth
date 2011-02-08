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
      \sabretooth\log::warning( 'Tried to query role record with no id.' );
      return 0;
    }

    $count = self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT user_id ) FROM user_access WHERE role_id = %s',
               self::format_string( $this->id ) ) );

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
      \sabretooth\log::warning( 'Tried to query role record with no id.' );
      return 0;
    }

    $count = self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT operation_id ) FROM role_has_operation WHERE role_id = %s',
               self::format_string( $this->id ) ) );

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

    $count = self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT operation_id ) '.
               'FROM role_has_operation '.
               'WHERE role_id = %s '.
               'AND operation_id = %s',
               self::format_string( $this->id ),
               self::format_string( $db_operation->id ) ) );

    return 0 < $count;
  }
  
  /**
   * Get a list of operations this role has access to.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @return array( database\operation )
   * @access public
   */
  public function get_operation_list( $modifier = NULL )
  {
    $operations = array();
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine operation for role record with no id.' );
      return $operations;
    }
    
    $ids = self::get_col(
      sprintf( 'SELECT operation.id '.
               'FROM role_has_operation rho, operation '.
               'WHERE rho.operation_id = operation.id '.
               'AND role_id = %s '.
               'GROUP BY operation.id '.
               '%s',
               self::format_string( $this->id ),
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );

    foreach( $ids as $id ) array_push( $operations, new operation( $id ) );
    return $operations;
  }
}
?>
