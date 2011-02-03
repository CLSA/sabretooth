<?php
/**
 * base_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * base_list widget
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_list extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'list', $args );

    // make sure to validate the argument ($args could be anything)
    if( isset( $args['page'] ) && is_numeric( $args['page'] ) )
      $this->page = $args['page'];
    if( isset( $args['sort_column'] ) && is_string( $args['sort_column'] ) )
      $this->sort_column = $args['sort_column'];
    if( isset( $args['sort_desc'] ) )
      $this->sort_desc = 0 != $args['sort_desc'];
  }
  
  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // define all template variables for this widget
    $this->set_variable( 'heading', $this->heading );
    $this->set_variable( 'checkable', $this->checkable );
    $this->set_variable( 'viewable', $this->viewable );
    $this->set_variable( 'editable', $this->editable );
    $this->set_variable( 'removable', $this->removable );
    $this->set_variable( 'items_per_page', $this->items_per_page );
    $this->set_variable( 'columns', $this->columns );
    $this->set_variable( 'number_of_items', $this->number_of_items );

    // make sure the page is valid, then set the rows array based on the page
    $max_page = ceil( $this->number_of_items / $this->items_per_page );
    if( 1 > $this->page ) $this->page = 1; // lower limit
    if( $this->page > $max_page ) $this->page = $max_page; // upper limit
    $this->set_rows( 10, ( $this->page - 1 ) * $this->items_per_page );

    $this->set_variable( 'page', $this->page );
    $this->set_variable( 'sort_column', $this->sort_column );
    $this->set_variable( 'sort_desc', $this->sort_desc );
    $this->set_variable( 'max_page', $max_page );
    $this->set_variable( 'rows', $this->rows );
  }
  
  /**
   * Set the rows array needed by this template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @abstract
   * @access protected
   */
  abstract protected function set_rows( $limit_count, $limit_offset );
  
  /**
   * Whether items in the list can be checked/selected.
   * @var boolean
   * @access protected
   */
  protected $checkable = false;
  
  /**
   * Whether items in the list can be viewed.
   * @var boolean
   * @access protected
   */
  protected $viewable = false;
  
  /**
   * Whether items in the list can be edited.
   * @var boolean
   * @access protected
   */
  protected $editable = false;
  
  /**
   * Whether items in the list can be removed.
   * @var boolean
   * @access protected
   */
  protected $removable = false;
  
  /**
   * Which page to display.
   * @var int
   * @access protected
   */
  protected $page = 1;
  
  /**
   * Which column to sort by, or none if set to an empty string.
   * @var string
   * @access protected
   */
  protected $sort_column = '';
  
  /**
   * Whether to sort in descending order.
   * @var boolean
   * @access protected
   */
  protected $sort_desc = false;
  
  /**
   * How many items should appear per page.
   * @var int
   * @access protected
   */
  protected $items_per_page = 10;
  
  /**
   * The total number of items in the list.
   * @var int
   * @access protected
   */
  protected $number_of_items = 0;
  
  /**
   * An array of columns
   * An array of columns.  Every item in the array must have the following:
   *                      'id' => a unique id identifying the column
   *                      'name' => the name to display in in the column header
   *                      'sortable' => whether or not the list can be sorted by the column
   * @var array
   * @access protected
   */
  protected $columns = array();
  
  /**
   * An array of items.  Every item in the array must have the following:
   *                     'id' => a unique identifying id
   *                     'columns' => an array of values for each column listed in the columns array
   * @var array
   * @access protected
   */
  protected $rows = array();
}
?>
