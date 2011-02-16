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
 * The base class of all widgets.
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
   * @param string $subject The widget's subject.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget.
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
    $widget_variable = array( 'subject' => $this->get_subject(),
                              'name' => $this->get_name(),
                              'full' => $this->get_full_name(),
                              'compound' => $this->get_full_name() );

    if( $this->parent )
    {
      $widget_variable['compound'] = $this->parent->get_full_name.'_'.$this->get_full_name();
      $this->set_variable( 'parent',
        array( 'exists' => true,
               'id' => $this->parent->get_record()->id,
               'subject' => $this->parent->get_subject(),
               'name' => $this->parent->get_name(),
               'full' => $this->parent->get_full_name() ) );
    }
    else
    {
      $this->set_variable( 'parent',
        array( 'exists' => false,
               'subject' => '',
               'name' => '',
               'full' => '' ) );
    }

    $this->set_variable( 'widget', $widget_variable );
    $this->set_variable( 'widget_heading', $this->heading );
  }

  /**
   * Get a query argument passed to the widget.
   * 
   * This method overrides the behaviour in the parent class' method since widget arguments are
   * passed in associative arrays named after the widget.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the argument.
   * @param mixed $default The value to return if no argument exists.  If the default is null then
   *                       it is assumed that the argument must exist, throwing an argument
                           exception if it is not set.
   * @return mixed
   * @throws exception\argument
   * @access public
   */
  public function get_argument( $name, $default = NULL )
  {
    $argument = NULL;
    $widget_name = $this->get_full_name();

    if( !array_key_exists( $widget_name, $this->arguments ) ||
        !array_key_exists( $name, $this->arguments[$widget_name] ) )
    { // the argument is missing
      if( is_null( $default ) ) throw new \sabretooth\exception\argument( $name, NULL, __METHOD__ );
      $argument = $default;
    }
    else
    { // the argument exists
      $argument = $this->arguments[$widget_name][$name];
    }

    return $argument;
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
        sprintf( 'Overwriting existing template variable "%s" which was "%s" and is now "%s"',
                 $name,
                 $this->variables[$name],
                 $value ) );

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
  private $heading = '';

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
