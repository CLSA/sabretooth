<?php
/**
 * base_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all "view" widgets
 * 
 * This class abstracts all common functionality for managing records.
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_record extends widget
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
    $this->set_mode( $name );
    
    // determine properties based on the current user's permissions
    $session = \sabretooth\session::self();
    $this->editable = $session->is_allowed(
      \sabretooth\database\operation::get_operation( 'action', $subject, 'edit' ) );
    $this->removable = $session->is_allowed( 
      \sabretooth\database\operation::get_operation( 'action', $subject, 'delete' ) );
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
    if( 'view' == $this->mode || 'edit' == $this->mode )
      $this->set_variable( 'id', $this->record->id );
    $this->set_variable( 'item', $this->item );
  }
  
  /**
   * Set whether to be in 'view' or 'add' mode.
   * 
   * Since this class is the base class for both *_view and *_add widgets this method is called
   * by the constructor to differentiate between the two.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $mode Should be either 'view' or 'add'
   * @access private
   */
  private function set_mode( $mode )
  {
    $this->mode = $mode;

    if( 'view' == $this->mode )
    {
      // build the associated record
      $class_name = '\\sabretooth\\database\\'.$this->get_subject();
      $this->record = new $class_name( $this->get_argument( 'id' ) );
      if( is_null( $this->record ) ) throw new \sabretooth\exception\argument( 'id' );
      
      $this->set_heading( sprintf( 'Viewing %s details',
                                   $this->get_subject() ) );
    }
    else if( 'add' == $this->mode )
    {
      // build the associated record
      $class_name = '\\sabretooth\\database\\'.$this->get_subject();
      $this->record = new $class_name();
      
      $this->addable = true;
      $this->editable = false;
      $this->set_heading( 'Creating a new '.$this->get_subject() );
    }
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
   * "type" => the type of variable, should be one of "boolean", "date", "string" or "text", "constant"
   * "value" => the value of the column
   * "required" => boolean describes whether the value can be left blank
   * @var array
   * @access protected
   */
  protected $item = array();

  /**
   * An active record of the item being viewed.
   * @var active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
