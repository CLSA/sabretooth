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
   * Add valid variable names to the widget
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array( string ) $names An array of template variable names.
   * @access public
   */
  public function add_variable_names( $names )
  {
    // merge the arrays, remove duplicates and sort
    $this->variable_names = array_merge( $this->variable_names, $names );
    $this->variable_names = array_unique( $this->variable_names );
    sort( $this->variable_names, SORT_STRING );
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
    // warn if setting an invalid template variable
    if( !in_array( $name, $this->variable_names ) )
      \sabretooth\log::singleton()->warning( "Setting unknown widget variable '$name'." );

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
   * @var array( string )
   * @access protected
   */
  protected $variable_names = array();

  /**
   * An array which holds .ini variables.
   * @var array( array )
   * @static
   * @access private
   */
  private static $variables = array();
}
?>
