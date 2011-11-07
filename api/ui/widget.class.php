<?php
/**
 * widget.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

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
   * Define generic widget variables for use by all templates.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $widget_variable = array(
      'subject' => $this->get_subject(),
      'subject_name' => str_replace( '_', ' ', $this->get_subject() ),
      'subject_names' => util::pluralize( str_replace( '_', ' ', $this->get_subject() ) ),
      'name' => $this->get_name(),
      'full' => $this->get_full_name(),
      'compound' => $this->get_full_name() );

    if( $this->parent )
    {
      $widget_variable['compound'] = $this->parent->get_full_name().'_'.$this->get_full_name();
      $this->set_variable( 'parent',
        array(
          'exists' => true,
          'id' => $this->parent->get_record()->id,
          'subject' => $this->parent->get_subject(),
          'subject_name' => str_replace( '_', ' ', $this->parent->get_subject() ),
          'subject_names' =>
            util::pluralize( str_replace( '_', ' ', $this->parent->get_subject() ) ),
          'name' => $this->parent->get_name(),
          'full' => $this->parent->get_full_name() ) );
    }
    else
    {
      $this->set_variable( 'parent',
        array( 'exists' => false,
               'subject' => '',
               'subject_name' => '',
               'subject_names' => '',
               'name' => '',
               'full' => '' ) );
    }

    $this->set_variable( 'widget', $widget_variable );
    $this->set_variable( 'widget_heading', $this->get_heading() );
    $this->set_variable( 'show_heading', $this->show_heading );
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
      if( 1 == func_num_args() )
      { // if only one argument was passed to this method then the argument is required
        throw new exc\argument( $name, NULL, __METHOD__ );
      }

      // if the argument was not required, then use the default instead
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
      log::warning(
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
   * Set whether or not to show the heading.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param bool $show
   * @access public
   */
  public function show_heading( $show )
  {
    $this->show_heading = $show;
  }

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
  
  /**
   * Determine whether or not to show the heading at the top of the widget
   * @var boolean
   * @access private
   */
  private $show_heading = true;
}
?>
