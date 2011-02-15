<?php
/**
 * base_add_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all "add list" widgets
 * 
 * This class abstracts all common functionality for adding to record lists.
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_add_list extends widget implements contains_record
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
    parent::__construct( $subject, 'add_'.$name, $args );
    
    // build the record
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $this->set_record( new $class_name( $this->get_argument( 'id' ) ) );

    // build the list widget
    $class_name = '\\sabretooth\\ui\\'.$name.'_list';
    $this->list_widget = new $class_name( $args );
    $this->list_widget->set_parent( $this, 'edit' );
    $this->list_widget->set_heading(
      sprintf( 'Choose items from the %s list to add the %s',
               $name,
               $subject ) );
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
    $this->set_variable( 'id', $this->get_record()->id );
    $this->set_variable( 'list_widget_name', $this->list_widget->get_class_name() );

    $this->list_widget->finish();
    $this->set_variable( 'list', $this->list_widget->get_variables() );
  }
  
  /**
   * Method required by the contains_record interface.
   * @author Patrick Emond
   * @return database\active_record
   * @access public
   */
  public function get_record()
  {
    return $this->record;
  }

  /**
   * Method required by the contains_record interface.
   * @author Patrick Emond
   * @param database\active_record $record
   * @access public
   */
  public function set_record( $record )
  {
    $this->record = $record;
  }

  /**
   * An active record of the item being viewed.
   * @var active_record
   * @access private
   */
  private $record = NULL;

  /**
   * The list widget from which to add to the record.
   * @var list_widget
   * @access protected
   */
  protected $list_widget = NULL;
}
?>
