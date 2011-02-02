<?php
/**
 * operation.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
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
    
    $this->type = $type;
    $this->subject = $subject;
    $this->name = $name;

    $primary_keys = array( 'type' => $type,
                           'subject' => $subject,
                           'name' => $name );
    $this->record = new \sabretooth\database\operation( $primary_keys );

    // throw a permission exception if the record is restricted and the user's current role does
    // not have access to the operation
    if( $this->record->restricted &&
        !\sabretooth\session::self()->get_role()->has_operation( $this->record ) )
      throw new \sabretooth\exception\permission( $this->record );
  }
  
  /**
   * The type of operation (action or widget).
   * @var string
   * @access protected
   */
  protected $type;
  
  /**
   * The operation's subject.
   * @var string
   * @access protected
   */
  protected $subject;
  
  /**
   * The operation's name.
   * @var string
   * @access protected
   */
  protected $name;

  /**
   * The database record for this operation
   * @var database\active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
