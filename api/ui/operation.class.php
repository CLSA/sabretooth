<?php
/**
 * operation.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * operation: abstract class for all operations
 *
 * All operation classes extend this base operation class.  All classes that extend this class are
 * used to fulfill some purpose executed by the user-interface.
 * @package sabretooth\ui
 */
abstract class operation extends \sabretooth\base_object
{
  /**
   * Returns the associated database operation for the provided action.
   * 
   * In addition to constructing the operation object, the operation is also validated against the
   * user's current role's access.  If the operation is not permitted a permission exception is
   * thrown.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $action
   * @throws exception\permission
   * @access public
   */
  public function __construct( $type, $subject, $name, $args )
  {
    // type must either be an action or widget
    assert( 'action' == $type || 'widget' == $type );
    
    $this->operation_record = \sabretooth\database\operation::get_operation( $type, $subject, $name );

    // throw a permission exception if the record is restricted and the user's current role does
    // not have access to the operation
    if( $this->operation_record->restricted &&
        !\sabretooth\session::self()->get_role()->has_operation( $this->operation_record ) )
      throw new \sabretooth\exception\permission( $this->operation_record );
  }

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
  public function get_full_name() { return $this->operation_record->subject.'_'.$this->operation_record->name; }
  
  /**
   * The database record for this operation
   * @var database\active_record
   * @access private
   */
  private $operation_record = NULL;
}
?>
