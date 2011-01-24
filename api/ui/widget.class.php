<?php
/**
 * widget.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
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
abstract class widget extends \sabretooth\base_object
{
  /**
   * Constructor
   * 
   * Defines all variables available in every widget
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct( $args = NULL )
  {
    // define all template variables for this widget
    self::$variables[ 'widget_name' ] = self::get_class_name();
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // set all variables which have been changed/set after the construction of the widget
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
    self::$variables[ self::get_class_name() ][ $name ] = $value;
  }

  /**
   * Get the widget variables array.
   * 
   * This method is to be used by the widget engine to render display widgets.
   * Do not use this method to set variables, instead use {@link set_variable}.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public static function get_variables()
  {
    return self::$variables;
  }

  /**
   * An array which holds .ini variables.
   * @var array( array )
   * @static
   * @access private
   */
  private static $variables = array();
}
?>
