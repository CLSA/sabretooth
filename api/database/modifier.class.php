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
   * TODO: add in <, <=, >= and > comparisons
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @param boolean $in Whether to use the SQL IN() function instead of an equation.
                        When this is set to true $value may be an array of values.
   * @param boolean $not Whether to logically "not" the clause (default is false)
   * @param boolean $or Whether to logically "or" the clause (default is false, which means "and")
   * @throws exception\argument
   * @access public
   */
  public function where( $column, $value, $format = true, $in = false, $not = false, $or = false )
  {
    if( !is_string( $column ) || 0 == strlen( $column ) )
      throw new \sabretooth\exception\argument( 'column', $column, __METHOD__ );

    $this->where_list[$column] = array( 'value' => $value,
                                        'format' => $format,
                                        'in' => $in,
                                        'not' => $not,
                                        'or' => $or );
  }
  
  /**
   * Add a where-in statement to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function where_in( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, true );
  }

  /**
   * Add a logical "not" where statement to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function where_not( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, false, true );
  }

  /**
   * Add a logical "not" where-in statement to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function where_not_in( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, true, true );
  }

  /**
   * Add where statement which will be "or" combined to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function or_where( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, false, false, true );
  }

  /**
   * Add where-in statement which will be "or" combined to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function or_where_in( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, true, false, true );
  }

  /**
   * Add a logical "not" where statement which will be "or" combined to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function or_where_not( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, false, true, true );
  }

  /**
   * Add a logical "not" where-in statement which will be "or" combined to the modifier.
   * 
   * This is a convenience method which makes where() calls more readable.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column The column to restrict.
   * @param mixed $value The value to restrict to (will be sql-escaped, quotes not necessary).
   * @param boolean $format Set whether to format the $value argument.
   *                         This should only be set to false when $value is the name of a column
   *                         or a pre-formatted function, etc.
   * @access public
   */
  public function or_where_not_in( $column, $value, $format = true )
  {
    $this->where( $column, $value, $format, true, true, true );
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
      throw new \sabretooth\exception\argument( 'column', $column, __METHOD__ );

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
      throw new \sabretooth\exception\argument( 'column', $column, __METHOD__ );

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
    if( 0 > $count )
      throw new \sabretooth\exception\argument( 'count', $count, __METHOD__ );

    if( 0 > $offset )
      throw new \sabretooth\exception\argument( 'offset', $offset, __METHOD__ );

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
   * Get an array of where clauses.
   * 
   * Each element contains an associative array where the indeces 'value' and 'format' contain
   * the column's value and whether to format the value, respectively.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_where_columns()
  {
    return array_keys( $this->where_list );
  }

  /**
   * Get an array of group clauses.
   * 
   * The returned array is an array of table names.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_group_columns()
  {
    return array_keys( $this->group_list );
  }

  /**
   * Get an array of order clauses.
   * 
   * The returned array is an associative array of "column name" => "descending" values.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_order_columns()
  {
    return array_keys( $this->order_list );
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
    $first_item = true;
    foreach( $this->where_list as $column => $item )
    {
      if( $item['in'] )
      {
        if( is_array( $item['value'] ) )
        {
          $first_value = true;
          foreach( $item['value'] as $value )
          {
            $compare .= $first_value
                      ? sprintf( '%s%s IN( ', $column, $item['not'] ? ' NOT' : '' )
                      : ', ';
            $compare .= $item['format']
                      ? active_record::format_string( $item['value'] )
                      : $item['value'];
            $first_value = false;
          }
          $compare .= ' )';
        }
        else
        {
          $compare = sprintf( '%s%s IN( %s )',
                              $column,
                              $item['not'] ? ' NOT' : '',
                              $item['format'] ?
                                active_record::format_string( $item['value'] ) : $item['value'] );
        }
      }
      else
      {
        $compare = sprintf( '%s %s= %s',
                            $column,
                            $item['not'] ? '!' : '',
                            $item['format'] ?
                              active_record::format_string( $item['value'] ) : $item['value'] );
      }
      
      $logic_type = $item['or'] ? ' OR' : ' AND';
      $where .= ( $first_item ? 'WHERE' : $logic_type ).' '.$compare;
      $first_item = false;
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
   * Holds all where clauses in an associative array named after the column.
   * @var array
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
