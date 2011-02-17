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
 * Base class for all "add list" to record widgets
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_add_list extends base_record_widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being viewed.
   * @param string $child The the list item's subject.
   * @param array $args An associative array of arguments to be processed by th  widget
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $child, $args )
  {
    parent::__construct( $subject, 'add_'.$child, $args );
    
    // make sure we have an id (we don't actually need to use it since the parent does)
    $this->get_argument( 'id' );

    // build the list widget
    $class_name = '\\sabretooth\\ui\\'.$child.'_list';
    $this->list_widget = new $class_name( $args );
    $this->list_widget->set_parent( $this, 'edit' );

    $this->list_widget->set_heading(
      sprintf( 'Choose %s to add to the %s',
               \sabretooth\util::pluralize( $child ),
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
    $this->set_variable( 'list_subject', $this->list_widget->get_subject() );
    $this->set_variable( 'list_subjects',
                         \sabretooth\util::pluralize( $this->list_widget->get_subject() ) );
    $this->set_variable( 'list_widget_name', $this->list_widget->get_class_name() );

    $this->list_widget->finish();
    $this->set_variable( 'list', $this->list_widget->get_variables() );
  }
  
  /**
   * The list widget from which to add to the record.
   * @var list_widget
   * @access protected
   */
  protected $list_widget = NULL;
}
?>
