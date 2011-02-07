<?php
/**
 * widget.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget: The base class of all widgets
 * 
 * All templates have a corresponding widget class by the same name.  The widget's job is to set
 * the variables needed by the template in order to be rendered.
 * The constructor of every class which extends widget must define the names of the variables needed
 * by in the template by calling {@link add_variable_names}
 * @package sabretooth\ui
 */
abstract class widget extends operation
{
  /**
   * Constructor
   * 
   * Defines all variables available in every widget
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( 'widget', $subject, $name, $args );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $this->set_variable( 'widget_subject', $this->get_subject() );
    $this->set_variable( 'widget_name', $this->get_name() );
    $this->set_variable( 'widget_full_name',
      $this->parent ? $this->parent->get_full_name() : $this->get_full_name() );
    $this->set_variable( 'widget_heading', $this->heading );
  }

  /**
   * Set a widget variable.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the variable.
   * @param string $value The value to set the variable to.
   * @access public
   */
  public function set_variable( $name, $value )
  {
    // warn if overwriting a variable
    if( array_key_exists( $name, $this->variables ) )
      \sabretooth\log::warning(
        'Overwriting existing template variable "'.$name.
        '" which was "'.$this->variables[ $name ].'" and is now "'.$value.'"' );
    $this->variables[ $name ] = $value;
  }

  /**
   * Set the widget's parent.
   * 
   * Embed this widget into a parent widget, or unparent the widget by setting the parent to NULL.
   * This should be done before the widget is finished (before {@link finish} is called).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @var widget $parent
   * @access public
   */
  public function set_parent( $parent = NULL )
  {
    $this->parent = $parent;
  }

  /**
   * Get the widget's variables array.
   * 
   * This method is to be used by the widget engine to render display widgets.
   * Do not use this method to set variables, instead use {@link set_variable}.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_variables()
  {
    return $this->variables;
  }
  
  /**
   * Set the widget's heading.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $heading
   * @access public
   */
  public function set_heading( $heading )
  {
    $this->heading = $heading;
  }

  /**
   * The widget's heading.
   * @var string
   * @access protected
   */
  protected $heading = "";

  /**
   * The parent widget if this widget is embedded in another widget.
   * @var widget
   * @access protected
   */
  protected $parent = NULL;

  /**
   * An array which holds .ini variables.
   * @var array( array )
   * @access private
   */
  private $variables = array();
}
?>
