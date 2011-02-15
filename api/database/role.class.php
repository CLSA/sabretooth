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
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @access public
   */
  public function get_user_count( $modifier = NULL )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query role record with no id.' );
      return 0;
    }
    
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'role_id', $this->id );

    $count = self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT user_id ) FROM user_access %s',
               $modifier->get_sql() ) );

    return $count;
  }

  /**
   * Get the number of operations this role has access to.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the count.
   * @return int
   * @access public
   */
  public function get_operation_count( $modifier = NULL )
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query role record with no id.' );
      return 0;
    }

    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'role_id', $this->id );

    $count = self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT operation_id ) FROM role_has_operation %s',
               $modifier->get_sql() ) );

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

    $modifier = new modifier();
    $modifier->where( 'role_id', $this->id );
    $modifier->where( 'operation_id', $db_operation->id );

    $count = self::get_one(
      sprintf( 'SELECT COUNT( DISTINCT operation_id ) FROM role_has_operation %s',
               $modifier->get_sql() ) );

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
    
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'rho.operation_id', 'operation.id', false );
    $modifier->where( 'role_id', $this->id );
    $modifier->group( 'operation.id' );

    $ids = self::get_col(
      sprintf( 'SELECT operation.id FROM role_has_operation rho, operation %s',
               $modifier->get_sql() ) );

    foreach( $ids as $id ) array_push( $operations, new operation( $id ) );
    return $operations;
  }
}
?>
