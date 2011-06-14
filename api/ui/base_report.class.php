<?php
/**
 * base_report.class.php
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
 * Base class for all report widgets
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_report extends widget
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
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'report', $args );
    $this->set_heading( $this->get_subject().' report' );
  }
  
  /**
   * Add a parameter to the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $param_id The parameter's id, can be one of the record's column names.
   * @param string $type The parameter's type, one of "boolean", "date", "time", "datetime",
   *               "number", "string", "text", "enum" or "constant"
   * @param string $heading The parameter's heading as it will appear in the view
   * @param string $note A note to add below the parameter.
   * @access public
   */
  public function add_parameter( $param_id, $type, $heading = NULL, $note = NULL )
  {
    // add timezone info to the note if the parameter is a time or datetime
    if( 'time' == $type || 'datetime' == $type )
    {
      // build time time zone help text
      $date_obj = util::get_datetime_object();
      $time_note = sprintf( 'Time is in %s\'s time zone (%s)',
                            bus\session::self()->get_site()->name,
                            $date_obj->format( 'T' ) );
      $note = is_null( $note ) ? $time_note : $time_note.'<br>'.$note;
    }

    $this->parameters[$param_id] = array( 'type' => $type );
    if( !is_null( $heading ) ) $this->parameters[$param_id]['heading'] = $heading;
    if( !is_null( $note ) ) $this->parameters[$param_id]['note'] = $note;
  }

  /**
   * Sets a parameter's value and additional data.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $param_id The parameter's id, can be one of the record's column names.
   * @param mixed $value The parameter's value.
   * @param mixed $data For enum parameter types, an array of all possible values and for date and
   *              datetime types an associative array of min_date and/or max_date
   * @throws exception\argument
   * @access public
   */
  public function set_parameter( $param_id, $value, $required = false, $data = NULL )
  {
    // make sure the parameter exists
    if( !array_key_exists( $param_id, $this->parameters ) )
      throw new exc\argument( 'param_id', $param_id, __METHOD__ );

    // process the value so that it displays correctly
    if( 'boolean' == $this->parameters[$param_id]['type'] )
    {
      if( is_null( $value ) ) $value = '';
      else $value = $value ? 'Yes' : 'No';
    }
    else if( 'date' == $this->parameters[$param_id]['type'] )
    {
      if( strlen( $value ) )
      {
        $date_obj = util::get_datetime_object( $value );
        $value = $date_obj->format( 'Y-m-d' );
      }
      else $value = '';
    }
    else if( 'time' == $this->parameters[$param_id]['type'] )
    {
      if( strlen( $value ) )
      {
        $date_obj = util::get_datetime_object( $value );
        $value = $date_obj->format( 'H:i' );
      }
      else $value = '12:00';
    }
    else if( 'hidden' == $this->parameters[$param_id]['type'] )
    {
      if( is_bool( $value ) ) $value = $value ? 'true' : 'false';
    }
    else if( 'constant' == $this->parameters[$param_id]['type'] &&
             ( ( is_int( $value ) && 0 == $value ) ||
               ( is_string( $value ) && '0' == $value ) ) )
    {
      $value = ' 0';
    }
    else if( 'number' == $this->parameters[$param_id]['type'] )
    {
      $value = floatval( $value );
    }

    $this->parameters[$param_id]['value'] = $value;
    if( 'enum' == $this->parameters[$param_id]['type'] )
    {
      $enum = $data;
      if( is_null( $enum ) )
        throw new exc\runtime(
          'Trying to set enum parameter without enum values.', __METHOD__ );

      // add a null entry (to the front of the array) if the parameter is not required
      if( !$required )
      {
        $enum = array_reverse( $enum, true );
        $enum['NULL'] = '';
        $enum = array_reverse( $enum, true );
      }
      $this->parameters[$param_id]['enum'] = $enum;
    }
    else if( 'date' == $this->parameters[$param_id]['type'] ||
             'datetime' == $this->parameters[$param_id]['type'] )
    {
      if( is_array( $data ) )
      {
        $date_limits = $data;
        if( array_key_exists( 'min_date', $date_limits ) )
          $this->parameters[$param_id]['min_date'] = $date_limits['min_date'];
        if( array_key_exists( 'max_date', $date_limits ) )
          $this->parameters[$param_id]['max_date'] = $date_limits['max_date'];
      }
    }

    $this->parameters[$param_id]['required'] = $required;
  }

  /**
   * Must be called after all parameters have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish_setting_parameters()
  {
    $this->set_variable( 'parameters', $this->parameters );
  }

  /**
   * An associative array where the key is a unique identifier (usually a column name) and the
   * value is an associative array which includes:
   * "heading" => the label to display
   * "type" => the type of variable (see {@link add_parameter} for details)
   * "value" => the value of the column
   * "enum" => all possible values if the parameter type is "enum"
   * "required" => boolean describes whether the value can be left blank
   * @var array
   * @access private
   */
  private $parameters = array();
}
?>
