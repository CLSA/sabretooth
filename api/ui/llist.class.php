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
 * @package sabretooth\ui
 */
class llist extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct( $args = NULL )
  {
    parent::__construct( $args );
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
    $this->set_variable( 'title', $this->title );
    $this->set_variable( 'checkable', $this->checkable );
    $this->set_variable( 'viewable', $this->viewable );
    $this->set_variable( 'editable', $this->editable );
    $this->set_variable( 'removable', $this->removable );
    $this->set_variable( 'max_rows', $this->max_rows );
    $this->set_variable( 'columns', $this->columns );

    // trim the rows array to only those that should be visible (paging)
    if( $this->max_rows < count( $this->rows ) )
    {
      $this->rows = array_slice( $this->rows,
                                 ( $this->page - 1 ) * $this->max_rows,
                                 $this->max_rows );
    }

    $this->set_variable( 'rows', $this->rows );
  }

  protected $title = "";
  protected $checkable = false;
  protected $viewable = false;
  protected $editable = false;
  protected $removable = false;
  protected $page = 1;
  protected $max_rows = 10;
  protected $columns = array();
  protected $rows = array();
}
?>
