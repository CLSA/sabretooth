<?php
/**
 * base_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * base_list widget
 * 
 * This class abstracts all common functionality from all lists of records.
 * Concrete child classes represent a particular type of record in the database.
 * If child classes list something other than all records for its particular type then it must
 * override the determine_record_list(), determine_record_count() and
 * determine_record_sort_column() methods.
 * If a list is embedded into another widget, then the parent widget must implement similar
 * methods: determine_<subject>_list(), determine_<subject>_count() and
 * determine_<subject>_sort_column() where <subject> is the record type being listed.
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
    $method_name = 'determine_'.$this->get_subject().'_count';
    $this->record_count = $this->parent && method_exists( $this->parent, $method_name )
                        ? $this->parent->$method_name()
                        : $this->determine_record_count();

    // make sure the page is valid, then set the rows array based on the page
    $max_page = ceil( $this->record_count / $this->items_per_page );
    if( 1 > $this->page ) $this->page = 1; // lower limit
    if( $this->page > $max_page ) $this->page = $max_page; // upper limit
    
    // build the sql modifier
    $modifier = new \sabretooth\database\modifier();
    if( strlen( $this->sort_column ) )
      $modifier->order( $this->determine_record_sort_column( $this->sort_column ), $this->sort_desc );
    $modifier->limit( $this->items_per_page, ( $this->page - 1 ) * $this->items_per_page );

    $method_name = 'determine_'.$this->get_subject().'_list';
    $this->record_list =
      $this->parent && method_exists( $this->parent, $method_name )
      ? $this->parent->$method_name( $modifier )
      : $this->determine_record_list( $modifier );
    $this->set_rows();

    // define all template variables for this widget
    $this->set_variable( 'heading', $this->heading );
    $this->set_variable( 'checkable', $this->checkable );
    $this->set_variable( 'viewable', $this->viewable );
    $this->set_variable( 'editable', $this->editable );
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
   * Set the rows array needed by the template.
   * 
   * When implementing this method, child classes should fill the $rows member with column values
   * for each record in the record list returned by calling {@link get_record_list}.
   * TODO: We need some way for parents to affect the columns/rows included in embedded list
   *       widgets.  For instance, site_view should not have a site column in it's activity list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @abstract
   * @access protected
   */
  abstract protected function set_rows();
  
  /**
   * Returns the sql column name to use when building the record list.
   * 
   * This method needs to be overriden by child classes when the sort column is outside of the
   * record's table columns.
   * Furthermore, when embedding this widget into another, the parent widget can set the sort column
   * by defining a determine_<record>_sort_column() method, where <record> is the name of the
   * database record/table of the embedded widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access protected
   */
  protected function determine_record_sort_column( $sort_name )
  {
    // by default, the sort name IS the column name
    return $sort_name ? $sort_name : NULL;
  }

  /**
   * Returns the total number of items in the list.
   * 
   * This method needs to be overriden by child classes when the number of items in the list is not
   * the same as what is returned by the database record object's count() method.
   * Furthermore, when embedding this widget into another, the parent widget also can set the number
   * of items by defining a determine_<record>_count() method, where <record> is the name of the
   * database record/table of the embedded widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  protected function determine_record_count()
  {
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    return $class_name::count();
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
   * Set whether itmes in the list can be edited.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param boolean $enable
   * @access public
   */
  public function set_editable( $enable )
  {
    $this->editable = $enable;
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
   * An array of columns.
   * 
   * Every item in the array must have the following:
   *   'id'       => a unique id identifying the column
   *   'name'     => the name to display in in the column header
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
