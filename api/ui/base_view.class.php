<?php
/**
 * base_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for widgets which view current or new records.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_view extends base_record_widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being viewed.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by th  widget
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( $subject, $name, $args );
    
    if( 'view' == $this->get_name() )
    {
      // make sure we have an id (we don't actually need to use it since the parent does)
      $this->get_argument( 'id' );

      // determine properties based on the current user's permissions
      $session = \sabretooth\session::self();
      $this->editable = $session->is_allowed(
        \sabretooth\database\operation::get_operation( 'action', $subject, 'edit' ) );
      $this->removable = $session->is_allowed( 
        \sabretooth\database\operation::get_operation( 'action', $subject, 'delete' ) );

      $this->set_heading( 'Viewing '.$this->get_subject().' details' );
    }
    else // 'add' == $this->get_name()
    {
      $this->addable = true;
      $this->editable = false;
      $this->removable = false;
      $this->set_heading( 'Creating a new '.$this->get_subject() );
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
    $this->set_variable( 'editable', $this->editable );
    $this->set_variable( 'removable', $this->removable );
    $this->set_variable( 'addable', $this->addable );
    $this->set_variable( 'item', $this->item );
  }
  
  /**
   * Determines which mode the widget is in.
   * Must be one of 'view', 'edit' or 'add'.
   * @var string
   * @access private
   */
  private $mode = 'view';

  /**
   * When in view mode, determines whether an edit button should be available.
   * @var boolean
   * @access private
   */
  private $editable = false;

  /**
   * When in view mode, determines whether a remove button should be available.
   * @var boolean
   * @access private
   */
   private $removable = false;

  /**
   * Used by the add mode to display add/cancel buttons.
   * @var boolean
   * @access private
   */
   private $addable = false;

  /**
   * An associative array where the key is a unique identifier (usually a column name) and the
   * value is an associative array which includes:
   * "heading" => the label to display
   * "type" => the type of variable, should be one of "boolean", "date", "string", "text", "enum" or "constant"
   * "value" => the value of the column
   * "enum" => all possible values if the item type is "enum"
   * "required" => boolean describes whether the value can be left blank
   * @var array
   * @access protected
   */
  protected $item = array();
}
?>
