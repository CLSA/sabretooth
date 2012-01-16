<?php
/**
 * base_list.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all list pull operations.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_list extends \sabretooth\ui\pull
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'list', $args );
    $this->process_restriction();
  }

  /**
   * Returns a list of all records in the list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    $modifier = new db\modifier();
    foreach( $this->restrictions as $restrict )
      $modifier->where( $restrict['column'], $restrict['operator'], $restrict['value'] );

    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $list = array();
    foreach( $class_name::select( $modifier ) as $record )
    {
      $item = array();
      foreach( $record->get_column_names() as $column ) $item[$column] = $record->$column;
      $list[] = $item;
    }

    return $list;
  }

  /**
   * Processes the restrictions argument, preparing restrictions for a database modifier
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function process_restriction()
  {
    $this->restrictions = array();
    $restrictions = $this->get_argument( 'restrictions', array() );
    
    $modifier = new db\modifier();
    if( is_array( $restrictions ) ) foreach( $restrictions as $column => $restrict )
    {
      $operator = NULL;
      $value = NULL;

      if( array_key_exists( 'value', $restrict ) && array_key_exists( 'compare', $restrict ) )
      {
        $value = $restrict['value'];
        if( 'is' == $restrict['compare'] ) $operator = '=';
        else if( 'is not' == $restrict['compare'] ) $operator = '!=';
        else if( 'like' == $restrict['compare'] )
        {
          $value = '%'.$value.'%';
          $operator = 'LIKE';
        }
        else if( 'not like' == $restrict['compare'] )
        {
          $value = '%'.$value.'%';
          $operator = 'NOT LIKE';
        }
        else log::error( 'Invalid comparison in list restriction.' );
      }

      if( !is_null( $operator ) && !is_null( $value ) )
      {
        $this->restrictions[] = array(
          'column' => $column,
          'operator' => $operator,
          'value' => $value );
      }
    }
  }
  
  /**
   * Lists are always returned in JSON format.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type() { return "json"; }

  /**
   * An associative array of restrictions to apply to the list.
   * @var array
   * @access protected
   */
  protected $restrictions = array();
}
?>
