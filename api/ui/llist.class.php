<?php
/**
 * llist.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * llist widget
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class llist extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args = NULL )
  {
    parent::__construct( $args );

    // make sure to validate the argument ($args could be anything)
    if( isset( $args['page'] ) && is_numeric( $args['page'] ) )
    {
      $this->page = $args['page'];
    }
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

  protected $heading = "";
  protected $checkable = false;
  protected $viewable = false;
  protected $editable = false;
  protected $removable = false;
  protected $page = 1;
  protected $items_per_page = 10;
  protected $number_of_items = 0;
  protected $columns = array();
  protected $rows = array();
}
?>
