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
 * Base class for widgets which view current or new records.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_view extends base_record_widget
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
    parent::__construct( $subject, $name, $args );
    
    if( 'view' == $this->get_name() )
    {
      // make sure we have an id (we don't actually need to use it since the parent does)
      $this->get_argument( 'id' );

      // determine properties based on the current user's permissions
      $session = \sabretooth\session::self();
      $this->editable = $session->is_allowed(
        \sabretooth\database\operation::get_operation( 'action', $subject, 'edit' ) );
      $this->removable = $session->is_allowed( 
        \sabretooth\database\operation::get_operation( 'action', $subject, 'delete' ) );

      $this->set_heading( 'Viewing '.$this->get_subject().' details' );
    }
    else // 'add' == $this->get_name()
    {
      $this->addable = true;
      $this->editable = false;
      $this->removable = false;
      $this->set_heading( 'Creating a new '.$this->get_subject() );
    }
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
    $this->set_variable( 'addable', $this->addable );
  }
  
  /**
   * Add an item to the view.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $item_id The item's id, can be one of the record's column names.
   * @param string $type The item's type, one of "boolean", "date", "time", "number", "string",
                   "text", "enum" or "constant"
   * @param string $heading The item's heading as it will appear in the view
   * @param string $note A note to add below the item.
   * @access public
   */
  public function add_item( $item_id, $type, $heading = NULL, $note = NULL )
  {
    $this->items[$item_id] = array( 'type' => $type );
    if( !is_null( $heading ) ) $this->items[$item_id]['heading'] = $heading;
    if( !is_null( $note ) ) $this->items[$item_id]['note'] = $note;
    else if( 'time' == $type )
    {
      // build time time zone help text
      $session = \sabretooth\session::self();
      $date_obj = new \DateTime( "now", new \DateTimeZone( $session->get_site()->timezone ) );
      $time_note = sprintf( 'Time is in %s\'s time zone (%s)',
                            $session->get_site()->name,
                            $date_obj->format( 'T' ) );
      $this->items[$item_id]['note'] = $time_note;
    }
  }

  /**
   * Sets and item's value (and enum values for enum types).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $item_id The item's id, can be one of the record's column names.
   * @param mixed $value The item's value.
   * @param array $enum For enum item types, all possible values.
   * @throws exception\argument
   * @access public
   */
  public function set_item( $item_id, $value, $required = false, $enum = NULL )
  {
    // make sure the item exists
    if( !array_key_exists( $item_id, $this->items ) )
      throw new \sabretooth\exception\argument( 'item_id', $item_id, __METHOD__ );

    // process the value so that it displays correctly
    if( 'boolean' == $this->items[$item_id]['type'] )
    {
      if( is_null( $value ) ) $value = '';
      else $value = $value ? 'Yes' : 'No';
    }
    else if( 'time' == $this->items[$item_id]['type'] )
    {
      $value = strlen( $value ) ? date( 'H:i', strtotime( $value ) ) : "12:00";
    }
    else if( 'constant' == $this->items[$item_id]['type'] &&
             ( ( is_int( $value ) && 0 == $value ) ||
               ( is_string( $value ) && '' != $value ) ) )
    {
      $value = ' 0';
    }
    else if( 'number' == $this->items[$item_id]['type'] )
    {
      $value = floatval( $value );
    }

    $this->items[$item_id]['value'] = $value;
    if( !is_null( $enum ) )
    {
      // add a null entry (to the front of the array) if the item is not required
      if( !$required )
      {
        $enum = array_reverse( $enum, true );
        $enum['NULL'] = '';
        $enum = array_reverse( $enum, true );
      }
      $this->items[$item_id]['enum'] = $enum;
    }
    else if( 'enum' == $this->items[$item_id]['type'] )
    { // make sure the type isn't an enum (since enum values aren't provided)
      throw new \sabretooth\exception\runtime(
        'Trying to set enum item without enum values.', __METHOD__ );
    }

    $this->items[$item_id]['required'] = $required;

  }

  /**
   * Must be called after all items have been set.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish_setting_items()
  {
    $this->set_variable( 'item', $this->items );
  }

  /**
   * Determines which mode the widget is in.
   * Must be one of 'view', 'edit' or 'add'.
   * @var string
   * @access private
   */
  private $mode = 'view';

  /**
   * When in view mode, determines whether an edit button should be available.
   * @var boolean
   * @access private
   */
  private $editable = false;

  /**
   * When in view mode, determines whether a remove button should be available.
   * @var boolean
   * @access private
   */
   private $removable = false;

  /**
   * Used by the add mode to display add/cancel buttons.
   * @var boolean
   * @access private
   */
   private $addable = false;

  /**
   * An associative array where the key is a unique identifier (usually a column name) and the
   * value is an associative array which includes:
   * "heading" => the label to display
   * "type" => the type of variable (see {@link add_item} for details)
   * "value" => the value of the column
   * "enum" => all possible values if the item type is "enum"
   * "required" => boolean describes whether the value can be left blank
   * @var array
   * @access private
   */
  private $items = array();
}
?>
