<?php
/**
 * base_list_widget.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all listing widgets.
 * 
 * This class abstracts all common functionality for lists of records.
 * Concrete child classes represent a particular type of record in the database.
 * If a list is embedded into another widget, then the parent widget must implement similar
 * methods: determine_<subject>_list() and determine_<subject>_count() where <subject> is
 * the record type being listed.
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_list_widget extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being listed.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'list', $args );
    
    // make sure to validate the arguments ($args could be anything)
    $this->page = $this->get_argument( 'page', $this->page );
    $this->sort_column = $this->get_argument( 'sort_column', $this->sort_column );
    $this->sort_desc = 0 != $this->get_argument( 'sort_desc', $this->sort_desc );
    $this->set_heading( ucfirst( $subject ).' list' );

    // determine properties based on the current user's permissions
    $session = \sabretooth\session::self();
    $this->viewable = $session->is_allowed(
      \sabretooth\database\operation::get_operation( 'widget', $this->get_subject(), 'view' ) );
    $this->addable = $session->is_allowed(
      \sabretooth\database\operation::get_operation( 'widget', $this->get_subject(), 'add' ) );
    $this->removable = $session->is_allowed(
      \sabretooth\database\operation::get_operation( 'action', $this->get_subject(), 'delete' ) );
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
    
    // determine the record count and list
    $modifier = new \sabretooth\database\modifier();
    $method_name = 'determine_'.$this->get_subject().'_count';
    $this->record_count = $this->parent && method_exists( $this->parent, $method_name )
                        ? $this->parent->$method_name( $modifier )
                        : $this->determine_record_count( $modifier );

    // make sure the page is valid, then set the rows array based on the page
    $max_page = ceil( $this->record_count / $this->items_per_page );
    if( 1 > $max_page ) $max_page = 1; // lower limit
    if( 1 > $this->page ) $this->page = 1; // lower limit
    if( $this->page > $max_page ) $this->page = $max_page; // upper limit
    
    // build the sql modifier
    $modifier = new \sabretooth\database\modifier();
    if( strlen( $this->sort_column ) ) $modifier->order( $this->sort_column, $this->sort_desc );
    $modifier->limit( $this->items_per_page, ( $this->page - 1 ) * $this->items_per_page );

    $method_name = 'determine_'.$this->get_subject().'_list';
    $this->record_list =
      $this->parent && method_exists( $this->parent, $method_name )
      ? $this->parent->$method_name( $modifier )
      : $this->determine_record_list( $modifier );
    $this->set_rows();

    // define all template variables for this widget
    $this->set_variable( 'checkable', $this->checkable );
    $this->set_variable( 'viewable', $this->viewable );
    $this->set_variable( 'addable', $this->addable );
    $this->set_variable( 'removable', $this->removable );
    $this->set_variable( 'items_per_page', $this->items_per_page );
    $this->set_variable( 'number_of_items', $this->record_count );
    $this->set_variable( 'columns', $this->columns );
    $this->set_variable( 'page', $this->page );
    $this->set_variable( 'sort_column', $this->sort_column );
    $this->set_variable( 'sort_desc', $this->sort_desc );
    $this->set_variable( 'max_page', $max_page );
    $this->set_variable( 'rows', $this->rows );
  }
  
  /**
   * Set the widget's parent.
   * 
   * Embed this widget into a parent widget, or unparent the widget by setting the parent to NULL.
   * This should be done before the widget is finished (before {@link finish} is called).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param widget $parent
   * @param string $mode Whether the parent is viewing records or adding new records to itself,
                         which is defined by setting this parameter to 'view' or 'edit',
                         respectively.
   * @access public
   */
  public function set_parent( $parent = NULL, $mode = 'view' )
  {
    parent::set_parent( $parent );

    // remove any columns which belong to the parent's record
    $new_column_array = array();
    foreach( $this->columns as $column )
    {
      $type = strstr( $column['id'], '.', true );
      if( $type != $this->parent->get_subject() ) array_push( $new_column_array, $column );
    }
    $this->columns = $new_column_array;
    
    if( 'edit' == $mode )
    {
      // If we're adding/remove items of this list to another record we want to dissable
      // the removing and adding of widgets, and enable checking
      $this->removable = false;
      $this->addable = false;
      $this->checkable = true;
    }
    else // 'view' == $mode
    {
      // add/remove operations are relative to the parent
      $session = \sabretooth\session::self();
      $this->addable = $session->is_allowed( 
        \sabretooth\database\operation::get_operation(
          'widget', $this->parent->get_subject(), 'add_'.$this->get_subject() ) );
      $this->removable = $session->is_allowed(
        \sabretooth\database\operation::get_operation(
          'action', $this->parent->get_subject(), 'delete_'.$this->get_subject() ) );
    }
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * When implementing this method, child classes should fill the $rows member with column values
   * for each record in the record list returned by calling {@link get_record_list}.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @abstract
   * @access protected
   */
  abstract protected function set_rows();
  
  /**
   * Returns the total number of items in the list.
   * 
   * This method needs to be overriden by child classes when the number of items in the list is not
   * the same as what is returned by the database record object's count() method.
   * Furthermore, when embedding this widget into another, the parent widget also can set the number
   * of items by defining a determine_<record>_count() method, where <record> is the name of the
   * database record/table of the embedded widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier )
  {
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    return $class_name::count( $modifier );
  }

  /**
   * Returns the list of database records to be listed.
   * 
   * This method needs to be overriden by child classes when the items in the list are not the same
   * as what is returned by the database record object's select() method.
   * Furthermore, when embedding this widget into another, the parent widget can also set the items
   * by defining a determine_<record>_list() method, where <record> is the name of the database
   * record/table of the embedded widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  protected function determine_record_list( $modifier )
  {
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    return $class_name::select( $modifier );
  }
  
  /**
   * Get the list of records to be displayed by the widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_record_list()
  {
    return $this->record_list;
  }

  /**
   * Set whether itmes in the list can be checked/selected.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_checkable( $enable )
  {
    $this->checkable = $enable;
  }

  /**
   * Set whether itmes in the list can be viewed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_viewable( $enable )
  {
    $this->viewable = $enable;
  }

  /**
   * Set whether itmes in the list can be added.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_addable( $enable )
  {
    $this->addable = $enable;
  }

  /**
   * Set whether itmes in the list can be removed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_removable( $enable )
  {
    $this->removable = $enable;
  }
  
  /**
   * Remove a column from the list based on its unique id.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_id The column's id, as defined by in the list's constructor.
   * @access public
   */
  public function remove_column( $column_id )
  {
    if( array_key_exists( $column_id, $this->columns ) ) unset( $this->columns[$column_id] );
    // find and remove the column who's id is equal to the column name
    $new_column_array = array();
    foreach( $this->columns as $column )
    {
      if( $column_id != $column['id'] ) array_push( $new_column_array, $column );
    }
    $this->columns = $new_column_array;
  }

  /**
   * Which page to display.
   * @var int
   * @access private
   */
  private $page = 1;
  
  /**
   * Which column to sort by, or none if set to an empty string.
   * @var string
   * @access private
   */
  private $sort_column = '';
  
  /**
   * Whether to sort in descending order.
   * Starts as true so that when initial sorting is selected it will be ascending
   * @var boolean
   * @access private
   */
  private $sort_desc = true;
  
  /**
   * How many items should appear per page.
   * @var int
   * @access private
   */
  private $items_per_page = 10;
  
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
   * Whether new items can be added to the list.
   * @var boolean
   * @access protected
   */
  protected $addable = false;
  
  /**
   * Whether items in the list can be removed.
   * @var boolean
   * @access protected
   */
  protected $removable = false;
  
  /**
   * An array of columns.
   * 
   * Every item in the array must have the following:
   *   'id'       => a unique id identifying the column
   *   'heading'  => the name to display in in the column header
   *   'sortable' => whether or not the list can be sorted by the column
   * This member should only be set in the {@link set_columns} function.
   * @var array
   * @access protected
   */
  protected $columns = array();
  
  /**
   * An array of rows.
   * 
   * Every item in the array must have the following:
   *   'id'      => a unique identifying id
   *   'columns' => an array of values for each column listed in the columns array
   * This member should only be set in the set_rows() function.
   * @var array
   * @access protected
   */
  protected $rows = array();

  /**
   * The total number of records in the list.
   * @var array
   * @access private
   */
  private $record_count;

  /**
   * An array of records used by the list.
   * This is not the total list of all records in the list, only the ones currently displayed by
   * the list (see {@link page} and {@link items_per_page} members).
   * @var array
   * @access private
   */
  private $record_list;
}
?>
