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
 * base.view widget
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_view extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being viewed.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'view', $args );
    
    // build the associated record
    $class_name = '\\sabretooth\\database\\'.$subject;
    $this->record = new $class_name( $this->get_argument( 'id' ) );
    if( is_null( $this->record ) ) throw new \sabretooth\exception\argument( 'id' );
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
    $this->set_variable( 'item', $this->item );
  }

  /**
   * Whether the item can be edited.
   * @var boolean
   * @access protected
   */
  protected $editable = false;

  /**
   * Whether the item can be removed.
   * @var boolean
   * @access protected
   */
  protected $removable = false;

  /**
   * An associative array of "name" => "value" pairs to include in the view.
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
