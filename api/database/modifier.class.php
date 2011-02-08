<?php
/**
 * modifier.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * This class is used to modify an SQL select statement.
 * 
 * To use this class create an instance, set whichever modifiers are needed then pass it to
 * select-like methods to limit/group/order/etc the query.
 * @package sabretooth\database
 */
class modifier extends \sabretooth\base_object
{
  /**
   * Add a where statement to the modifier.
   * 
   * This method appends where clauses onto the end of already existing where clauses.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @throws exception\argument
   * @access public
   */
  public function where( $column, $value )
  {
    if( !is_string( $column ) || 0 == strlen( $column ) )
      throw new \sabretooth\exception\argument( 'column', $column );

    $this->where_list[$column] = $value;
  }

  /**
   * Add a group by statement to the modifier.
   * 
   * This method appends group by clauses onto the end of already existing group by clauses.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to group by.
   * @throws exception\argument
   * @access public
   */
  public function group( $column )
  {
    if( !is_string( $column ) || 0 == strlen( $column ) )
      throw new \sabretooth\exception\argument( 'column', $column );

    array_push( $this->group_list, $column );
  }

  /**
   * Adds an order statement to the modifier.
   * 
   * This method appends order clauses onto the end of already existing order clauses.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to order by.
   * @param boolean $desc Whether to sort in descending order.
   * @throws exception\argument
   * @access public
   */
  public function order( $column, $desc = false )
  {
    if( !is_string( $column ) || 0 == strlen( $column ) )
      throw new \sabretooth\exception\argument( 'column', $column );

    $this->order_list[$column] = $desc;
  }

  /**
   * Sets a limit to how many rows are returned.
   * 
   * This method sets the total number of rows and offset to begin selecting by.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $count The number of rows to limit by.
   * @param int $offset The offset to begin the selection.
   * @throws exception\argument
   * @access public
   */
  public function limit( $count, $offset = 0 )
  {
    if( 0 > $count ) throw new \sabretooth\exception\argument( 'count', $count );
    if( 0 > $offset ) throw new \sabretooth\exception\argument( 'offset', $offset );

    $this->limit_count = $count;
    $this->limit_offset = $offset;
  }
  
  /**
   * Returns whether the modifier has a certain column in it's where clauses.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to search for.
   * @return boolean
   * @access public
   */
  public function has_where( $column )
  {
    return array_key_exists( $column, $this->where_list );
  }

  /**
   * Returns whether the modifier has a certain column in it's group clauses.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to search for.
   * @return boolean
   * @access public
   */
  public function has_group( $column )
  {
    return array_key_exists( $column, $this->group_list );
  }

  /**
   * Returns whether the modifier has a certain column in it's order clauses.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to search for.
   * @return boolean
   * @access public
   */
  public function has_order( $column )
  {
    return array_key_exists( $column, $this->order_list );
  }

  /**
   * Returns the modifier as an SQL statement (same as calling each individual get_*() method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_sql()
  {
    return sprintf( '%s %s %s %s',
                    $this->get_where(),
                    $this->get_group(),
                    $this->get_order(),
                    $this->get_limit() );
  }

  /**
   * Returns an SQL where statement.
   * 
   * This method should only be called by an active_record class and only after all modifications
   * have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_where()
  {
    $where = '';
    $first = true;
    foreach( $this->where_list as $column => $value )
    {
      $where .= sprintf( '%s %s = %s',
                         $first ? 'WHERE' : ' AND',
                         $column,
                         active_record::format_string( $value ) );
      $first = false;
    }

    return $where;
  }
  
  /**
   * Returns an SQL group statement.
   * 
   * This method should only be called by an active_record class and only after all modifications
   * have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_group()
  {
    $group = '';
    $first = true;
    foreach( $this->group_list as $column )
    {
      $group .= sprintf( '%s %s',
                         $first ? 'GROUP BY' : ',',
                         $column );
      $first = false;
    }

    return $group;
  }
  
  /**
   * Returns an SQL order statement.
   * 
   * This method should only be called by an active_record class and only after all modifications
   * have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_order()
  {
    $order = '';
    $first = true;
    foreach( $this->order_list as $column => $value )
    {
      $order .= sprintf( '%s %s %s',
                         $first ? 'ORDER BY' : ',',
                         $column,
                         $value ? 'DESC' : '' );
      $first = false;
    }

    return $order;
  }
  
  /**
   * Returns an SQL limit statement.
   * 
   * This method should only be called by an active_record class and only after all modifications
   * have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_limit()
  {
    $limit = '';
    if( 0 < $this->limit_count )
    {
      $limit .= sprintf( 'LIMIT %d OFFSET %d',
                         $this->limit_count,
                         $this->limit_offset );
    }

    return $limit;
  }
  
  /**
   * Holds all where clauses.
   * @var array( column => value )
   * @access private
   */
  private $where_list = array();
  
  /**
   * Holds all group clauses.
   * @var array( string )
   * @access private
   */
  private $group_list = array();
  
  /**
   * Holds all order clauses.
   * @var array( column => desc )
   * @access private
   */
  private $order_list = array();
  
  /**
   * The row limit value.
   * @var int
   * @access private
   */
  private $limit_count = 0;
  
  /**
   * The limit offset value.
   * @var array( column => value )
   * @access private
   */
  private $limit_offset = 0;
}
?>
