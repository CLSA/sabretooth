<?php
/**
 * export_column.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * export_column: record
 */
class export_column extends \cenozo\database\export_column
{
  /**
   * Returns the alias used when referencing this object's table
   * 
   * @access public
   */
  public function get_column_alias()
  {
    $column_alias = NULL;

    if( 'interview' == $this->table_name )
    {
      $alias_parts = array( $this->table_name, preg_replace( '/_id$/', '', $this->column_name ) );

      if( 'interview' == $this->table_name )
      {
        // get the qnaire name
        array_unshift( $alias_parts, lib::create( 'database\qnaire', $this->subtype )->get_script()->name );
      }

      $column_alias = ucWords( str_replace( '_', ' ', implode( ' ', $alias_parts ) ) );
    }
    else
    {
      $column_alias = parent::get_column_alias();
    }

    return $column_alias;
  }
}
