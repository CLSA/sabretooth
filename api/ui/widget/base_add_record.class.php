<?php
/**
 * base_add_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all "add record" to record widgets
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_add_record extends base_record_widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being viewed.
   * @param string $child The the child item's subject.
   * @param array $args An associative array of arguments to be processed by th  widget
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $child, $args )
  {
    parent::__construct( $subject, 'add_'.$child, $args );
    
    // make sure we have an id (we don't actually need to use it since the parent does)
    $this->get_argument( 'id' );

    // build the child add widget
    $class_name = '\\sabretooth\\ui\\'.$child.'_add';
    $this->add_widget = new $class_name( $args );
    $this->add_widget->set_parent( $this, 'edit' );

    $this->add_widget->set_heading(
      sprintf( 'Choose %s to add to the %s',
               util::pluralize( $child ),
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
    $this->set_variable( 'record_subject', $this->add_widget->get_subject() );
    $this->set_variable( 'record_subjects',
                         util::pluralize( $this->add_widget->get_subject() ) );
    $this->set_variable( 'add_widget_name', $this->add_widget->get_class_name() );

    $this->add_widget->finish();
    $this->set_variable( 'record', $this->add_widget->get_variables() );
  }

  /**
   * The child add widget.
   * @var widget
   * @access protected
   */
  protected $add_widget = NULL;
}
?>
