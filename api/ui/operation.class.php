<?php
/**
 * operation.class.php
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
 * Base class for all operation.
 *
 * All operation classes extend this base operation class.  All classes that extend this class are
 * used to fulfill some purpose executed by the user-interface.
 * @package sabretooth\ui
 */
abstract class operation extends \sabretooth\base_object
{
  /**
   * Returns the associated database operation for the provided operation.
   * 
   * In addition to constructing the operation object, the operation is also validated against the
   * user's current role's access.  If the operation is not permitted a permission exception is
   * thrown.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $type The type of operation (either 'push', 'pull' or 'widget')
   * @param string $subject The subject of the operation.
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widgel
   * @throws excpetion\argument, exception\permission
   * @access public
   */
  public function __construct( $type, $subject, $name, $args )
  {
    // type must either be a pull, push or widget
    if( 'push' != $type && 'pull' != $type && 'widget' != $type )
      throw new exc\argument( 'type', $type, __METHOD__ );
    
    $this->operation_record =
      db\operation::get_operation( $type, $subject, $name );
    
    if( is_null( $this->operation_record ) )
      throw new exc\runtime(
        sprintf( 'Unable to create operation record for %s: %s_%s',
                 $type, $subject, $name ),
        __METHOD__ );

    if( is_array( $args ) ) $this->arguments = $args;
    
    // throw a permission exception if the user is not allowed to perform this operation
    if( !bus\session::self()->is_allowed( $this->operation_record ) )
      throw new exc\permission( $this->operation_record, __METHOD__ );

    $this->set_heading( $this->get_subject().' '.$this->get_name() );  
  }
  
  /**
   * Finish processing the operation.
   * 
   * Every operation must complete whatever processing it is responsible for in this
   * method.  This method may or may not return a mixed result.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @abstract
   * @access public
   */
  abstract public function finish();

  /**
   * Get the database id of the operation.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_id() { return $this->operation_record->id; }
  
  /**
   * Get the type of operation.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_type() { return $this->operation_record->type; }
  
  /**
   * Get the subject of operation.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_subject() { return $this->operation_record->subject; }
  
  /**
   * Get the name of operation.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_name() { return $this->operation_record->name; }
  
  /**
   * Get the full name of operation (subject_name)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_full_name()
  { return $this->operation_record->subject.'_'.$this->operation_record->name; }
 
  /**
   * Get a query argument passed to the operation.
   * 
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
    if( !array_key_exists( $name, $this->arguments ) )
    {
      if( 1 == func_num_args() ) throw new exc\argument( $name, NULL, __METHOD__ );
      $argument = $default;
    }
    else
    { // the argument exists
      $argument = $this->arguments[$name];
    }

    return $argument;
  }

  /**
   * Get the operation's heading.
   * @author Dean Inglis <inglisdd@mcmaster.ca>
   * @access public
   */
  public function get_heading() { return $this->heading; }

  /**
   * Set the operation's heading.
   * @author Dean Inglis <inglisdd@mcmaster.ca>
   * @param string $heading
   * @access public
   */
  public function set_heading( $heading )
  {
    $this->heading = ucwords( str_replace( '_', ' ', $heading ) );
  }

  /**
   * The operation's heading.
   * @var string
   * @access protected
   */
  private $heading = '';


  /**
   * The database record for this operation
   * @var database\record
   * @access protected
   */
  protected $operation_record = NULL;

  /**
   * The url query arguments.
   * @var array( array )
   * @access protected
   */
  protected $arguments = array();
}
?>
